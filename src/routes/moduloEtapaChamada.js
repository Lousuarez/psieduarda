const express = require('express');
const { pool, genId } = require('../db');
const asyncHandler = require('../asyncHandler');
const { requireAdmin } = require('../auth');
const { logAudit } = require('../audit');

const router = express.Router();

function toApi(row) {
  return {
    id: row.etapa_id,
    etapaId: row.etapa_id,
    status: row.status,
    iniciadaEm: row.iniciada_em,
    iniciadaPor: row.iniciada_por || '',
    fechadaEm: row.fechada_em,
    fechadaPor: row.fechada_por || '',
    versoesCount: Number(row.versoes_count || 0),
  };
}

router.get('/', asyncHandler(async (req, res) => {
  const [rows] = await pool.query(
    `SELECT c.*, (SELECT COUNT(*) FROM modulo_etapa_chamada_versao v WHERE v.etapa_id = c.etapa_id) AS versoes_count
     FROM modulo_etapa_chamada c`
  );
  res.json({ docs: rows.map(toApi) });
}));

// Presença "oficial" atual de cada etapa fechada: a última versão salva
// (a mais recente ao fechar, ou ao reabrir e fechar de novo). Usado pelo
// relatório "Frequência por colaborador" pra não ter que buscar o
// histórico completo de cada etapa uma a uma.
router.get('/presencas', asyncHandler(async (req, res) => {
  const [rows] = await pool.query(
    `SELECT vp.colaborador_id, vp.presente, v.etapa_id
     FROM modulo_etapa_chamada_versao_presenca vp
     JOIN modulo_etapa_chamada_versao v ON v.id = vp.versao_id
     JOIN (
       SELECT etapa_id, MAX(versao) AS max_versao
       FROM modulo_etapa_chamada_versao
       GROUP BY etapa_id
     ) latest ON latest.etapa_id = v.etapa_id AND latest.max_versao = v.versao`
  );
  res.json({
    docs: rows.map((r) => ({
      id: `${r.etapa_id}-${r.colaborador_id}`,
      etapaId: r.etapa_id,
      colaboradorId: r.colaborador_id,
      presente: !!r.presente,
    })),
  });
}));

router.get('/:etapaId/versoes', asyncHandler(async (req, res) => {
  const { etapaId } = req.params;
  const [versoes] = await pool.query(
    'SELECT * FROM modulo_etapa_chamada_versao WHERE etapa_id = ? ORDER BY versao DESC',
    [etapaId]
  );
  if (!versoes.length) return res.json({ docs: [] });
  const [presencas] = await pool.query(
    `SELECT p.* FROM modulo_etapa_chamada_versao_presenca p
     JOIN modulo_etapa_chamada_versao v ON v.id = p.versao_id
     WHERE v.etapa_id = ?`,
    [etapaId]
  );
  const presencasByVersao = {};
  presencas.forEach((p) => { (presencasByVersao[p.versao_id] ||= []).push({ colaboradorId: p.colaborador_id, presente: !!p.presente }); });
  res.json({
    docs: versoes.map((v) => ({
      id: v.id,
      etapaId: v.etapa_id,
      versao: v.versao,
      motivo: v.motivo,
      criadaEm: v.criada_em,
      criadaPor: v.criada_por || '',
      presencas: presencasByVersao[v.id] || [],
    })),
  });
}));

router.put('/:etapaId', requireAdmin, asyncHandler(async (req, res) => {
  const { etapaId } = req.params;
  const status = String(req.body.status || '');
  const username = req.user.username;
  const [statusAntes] = await pool.query('SELECT status FROM modulo_etapa_chamada WHERE etapa_id = ?', [etapaId]);
  const antes = { etapaId, status: statusAntes[0]?.status || 'nao_iniciada' };

  if (status === 'aberta') {
    const [existing] = await pool.query('SELECT etapa_id FROM modulo_etapa_chamada WHERE etapa_id = ?', [etapaId]);
    if (!existing.length) {
      await pool.query(
        'INSERT INTO modulo_etapa_chamada (etapa_id, status, iniciada_em, iniciada_por) VALUES (?, "aberta", NOW(), ?)',
        [etapaId, username]
      );
    } else {
      await pool.query('UPDATE modulo_etapa_chamada SET status = "aberta" WHERE etapa_id = ?', [etapaId]);
    }
    await logAudit({ entidade: 'moduloEtapaChamada', entidadeId: etapaId, acao: existing.length ? 'update' : 'create', antes: existing.length ? antes : null, depois: { etapaId, status: 'aberta' }, req });
  } else if (status === 'fechada') {
    const [existing] = await pool.query('SELECT etapa_id FROM modulo_etapa_chamada WHERE etapa_id = ? AND status = "aberta"', [etapaId]);
    if (!existing.length) return res.status(409).json({ error: 'chamada_nao_esta_aberta' });

    const [matriculados] = await pool.query(
      `SELECT pr.colaborador_id, COALESCE(f.presente, FALSE) AS presente
       FROM modulo_etapas e
       JOIN progresso pr ON pr.modulo_id = e.modulo_id
       LEFT JOIN modulo_etapa_frequencia f ON f.etapa_id = e.id AND f.colaborador_id = pr.colaborador_id
       WHERE e.id = ?`,
      [etapaId]
    );
    const [[{ n }]] = await pool.query('SELECT COUNT(*) AS n FROM modulo_etapa_chamada_versao WHERE etapa_id = ?', [etapaId]);
    const versao = n + 1;
    const motivo = versao === 1 ? 'fechamento' : 'edicao';
    const versaoId = genId();
    await pool.query(
      'INSERT INTO modulo_etapa_chamada_versao (id, etapa_id, versao, motivo, criada_em, criada_por) VALUES (?, ?, ?, ?, NOW(), ?)',
      [versaoId, etapaId, versao, motivo, username]
    );
    if (matriculados.length) {
      const values = matriculados.map((m) => [versaoId, m.colaborador_id, !!m.presente]);
      await pool.query('INSERT INTO modulo_etapa_chamada_versao_presenca (versao_id, colaborador_id, presente) VALUES ?', [values]);
    }
    await pool.query(
      'UPDATE modulo_etapa_chamada SET status = "fechada", fechada_em = NOW(), fechada_por = ? WHERE etapa_id = ?',
      [username, etapaId]
    );
    await logAudit({ entidade: 'moduloEtapaChamada', entidadeId: etapaId, acao: 'update', antes, depois: { etapaId, status: 'fechada', versao, motivo, presentes: matriculados.filter((m) => m.presente).length, total: matriculados.length }, req });
  } else {
    return res.status(400).json({ error: 'status_invalido' });
  }

  const [[row]] = await pool.query(
    `SELECT c.*, (SELECT COUNT(*) FROM modulo_etapa_chamada_versao v WHERE v.etapa_id = c.etapa_id) AS versoes_count
     FROM modulo_etapa_chamada c WHERE c.etapa_id = ?`,
    [etapaId]
  );
  res.json(toApi(row));
}));

module.exports = router;
