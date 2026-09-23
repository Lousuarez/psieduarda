const express = require('express');
const { pool, genId, genToken } = require('../db');
const asyncHandler = require('../asyncHandler');
const { logAudit } = require('../audit');

const router = express.Router();

function toApi(row, trilhaIds) {
  return {
    id: row.id,
    nome: row.nome,
    email: row.email || '',
    cargo: row.cargo || '',
    unidadeId: row.unidade_id || '',
    gestor: row.gestor || '',
    dataAdmissao: row.data_admissao || '',
    lideranca: !!row.lideranca,
    ativo: !!row.ativo,
    nota: row.nota || '',
    trilhaIds: trilhaIds || [],
    hasAccessToken: !!row.access_token,
  };
}

async function loadTrilhaIdsMap() {
  const [rows] = await pool.query('SELECT colaborador_id, trilha_id FROM colaborador_trilhas');
  const map = {};
  for (const r of rows) {
    if (!map[r.colaborador_id]) map[r.colaborador_id] = [];
    map[r.colaborador_id].push(r.trilha_id);
  }
  return map;
}

router.get('/', asyncHandler(async (req, res) => {
  const [rows] = await pool.query('SELECT * FROM colaboradores ORDER BY updated_at ASC');
  const trilhaMap = await loadTrilhaIdsMap();
  res.json({ docs: rows.map((r) => toApi(r, trilhaMap[r.id])) });
}));

router.post('/', asyncHandler(async (req, res) => {
  const id = genId();
  const nome = String(req.body.nome || '');
  const email = req.body.email !== undefined ? String(req.body.email) : '';
  const cargo = req.body.cargo !== undefined ? String(req.body.cargo) : '';
  const unidadeId = req.body.unidadeId ? String(req.body.unidadeId) : null;
  const gestor = req.body.gestor !== undefined ? String(req.body.gestor) : '';
  const dataAdmissao = req.body.dataAdmissao !== undefined ? String(req.body.dataAdmissao) : '';
  const lideranca = !!req.body.lideranca;
  const ativo = req.body.ativo !== undefined ? !!req.body.ativo : true;
  const nota = req.body.nota !== undefined ? String(req.body.nota) : '';
  await pool.query(
    `INSERT INTO colaboradores
      (id, nome, email, cargo, unidade_id, gestor, data_admissao, lideranca, ativo, nota)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
    [id, nome, email, cargo, unidadeId, gestor, dataAdmissao, lideranca, ativo, nota]
  );
  const depois = toApi({ id, nome, email, cargo, unidade_id: unidadeId, gestor, data_admissao: dataAdmissao, lideranca, ativo, nota }, []);
  await logAudit({ entidade: 'colaborador', entidadeId: id, acao: 'create', antes: null, depois, req });
  res.json(depois);
}));

router.put('/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  const [rows] = await pool.query('SELECT * FROM colaboradores WHERE id = ?', [id]);
  if (!rows.length) return res.status(404).json({ error: 'not_found' });
  const cur = rows[0];
  const trilhaMapAntes = await loadTrilhaIdsMap();
  const antes = toApi(cur, trilhaMapAntes[id]);
  const b = req.body;
  const nome = b.nome !== undefined ? String(b.nome) : cur.nome;
  const email = b.email !== undefined ? String(b.email) : cur.email;
  const cargo = b.cargo !== undefined ? String(b.cargo) : cur.cargo;
  const unidadeId = b.unidadeId !== undefined ? (b.unidadeId ? String(b.unidadeId) : null) : cur.unidade_id;
  const gestor = b.gestor !== undefined ? String(b.gestor) : cur.gestor;
  const dataAdmissao = b.dataAdmissao !== undefined ? String(b.dataAdmissao) : cur.data_admissao;
  const lideranca = b.lideranca !== undefined ? !!b.lideranca : !!cur.lideranca;
  const ativo = b.ativo !== undefined ? !!b.ativo : !!cur.ativo;
  const nota = b.nota !== undefined ? String(b.nota) : cur.nota;
  await pool.query(
    `UPDATE colaboradores SET nome=?, email=?, cargo=?, unidade_id=?, gestor=?, data_admissao=?, lideranca=?, ativo=?, nota=? WHERE id=?`,
    [nome, email, cargo, unidadeId, gestor, dataAdmissao, lideranca, ativo, nota, id]
  );
  const trilhaMap = await loadTrilhaIdsMap();
  const depois = toApi({ id, nome, email, cargo, unidade_id: unidadeId, gestor, data_admissao: dataAdmissao, lideranca, ativo, nota }, trilhaMap[id]);
  await logAudit({ entidade: 'colaborador', entidadeId: id, acao: 'update', antes, depois, req });
  res.json(depois);
}));

router.delete('/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  const [rows] = await pool.query('SELECT * FROM colaboradores WHERE id = ?', [id]);
  const trilhaMap = await loadTrilhaIdsMap();
  await pool.query('DELETE FROM colaboradores WHERE id = ?', [id]);
  if (rows.length) await logAudit({ entidade: 'colaborador', entidadeId: id, acao: 'delete', antes: toApi(rows[0], trilhaMap[id]), depois: null, req });
  res.json({ ok: true });
}));

router.post('/:id/trilhas/:trilhaId', asyncHandler(async (req, res) => {
  const { id, trilhaId } = req.params;
  await pool.query(
    'INSERT IGNORE INTO colaborador_trilhas (colaborador_id, trilha_id) VALUES (?, ?)',
    [id, trilhaId]
  );
  await logAudit({ entidade: 'colaboradorTrilha', entidadeId: `${id}-${trilhaId}`, acao: 'create', antes: null, depois: { colaboradorId: id, trilhaId }, req });
  res.json({ ok: true });
}));

router.delete('/:id/trilhas/:trilhaId', asyncHandler(async (req, res) => {
  const { id, trilhaId } = req.params;
  await pool.query(
    'DELETE FROM colaborador_trilhas WHERE colaborador_id = ? AND trilha_id = ?',
    [id, trilhaId]
  );
  await logAudit({ entidade: 'colaboradorTrilha', entidadeId: `${id}-${trilhaId}`, acao: 'delete', antes: { colaboradorId: id, trilhaId }, depois: null, req });
  res.json({ ok: true });
}));

// (Re)gera o link de acesso pessoal do colaborador (mata o link anterior,
// se existir, já que access_token é único por linha). O token em si nunca
// vai pro log de auditoria — só o fato de ter sido gerado/revogado.
router.post('/:id/link', asyncHandler(async (req, res) => {
  const { id } = req.params;
  const [rows] = await pool.query('SELECT id FROM colaboradores WHERE id = ?', [id]);
  if (!rows.length) return res.status(404).json({ error: 'not_found' });
  const token = genToken();
  await pool.query('UPDATE colaboradores SET access_token = ? WHERE id = ?', [token, id]);
  await logAudit({ entidade: 'colaborador', entidadeId: id, acao: 'update', antes: { linkAcesso: false }, depois: { linkAcesso: true }, req });
  res.json({ token });
}));

router.delete('/:id/link', asyncHandler(async (req, res) => {
  const { id } = req.params;
  await pool.query('UPDATE colaboradores SET access_token = NULL WHERE id = ?', [id]);
  await logAudit({ entidade: 'colaborador', entidadeId: id, acao: 'update', antes: { linkAcesso: true }, depois: { linkAcesso: false }, req });
  res.json({ ok: true });
}));

router.get('/:id/historico', asyncHandler(async (req, res) => {
  const { id } = req.params;
  const [rows] = await pool.query(
    `SELECT h.status, h.changed_at, m.nome AS modulo_nome
     FROM progresso_historico h
     JOIN modulos m ON m.id = h.modulo_id
     WHERE h.colaborador_id = ?
     ORDER BY h.changed_at DESC
     LIMIT 50`,
    [id]
  );
  res.json({
    docs: rows.map((r) => ({ status: r.status, changedAt: r.changed_at, moduloNome: r.modulo_nome })),
  });
}));

module.exports = router;
