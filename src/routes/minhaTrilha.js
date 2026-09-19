// Endpoint público (sem login) pro link mágico de cada colaborador — só
// devolve o próprio recorte de dados da pessoa dona do token, nunca a lista
// geral de colaboradores/trilhas.
const express = require('express');
const { pool } = require('../db');
const asyncHandler = require('../asyncHandler');

const router = express.Router();

router.get('/:token', asyncHandler(async (req, res) => {
  const { token } = req.params;
  const [colabRows] = await pool.query('SELECT * FROM colaboradores WHERE access_token = ?', [token]);
  if (!colabRows.length) return res.status(404).json({ error: 'not_found' });
  const c = colabRows[0];

  let unidadeNome = '';
  if (c.unidade_id) {
    const [unidadeRows] = await pool.query('SELECT nome FROM unidades WHERE id = ?', [c.unidade_id]);
    unidadeNome = unidadeRows[0]?.nome || '';
  }

  const [trilhaIdRows] = await pool.query('SELECT trilha_id FROM colaborador_trilhas WHERE colaborador_id = ?', [c.id]);
  const trilhaIds = trilhaIdRows.map((r) => r.trilha_id);

  let trilhas = [];
  if (trilhaIds.length) {
    const tPlaceholders = trilhaIds.map(() => '?').join(',');
    const [trilhaRows] = await pool.query(`SELECT * FROM trilhas WHERE id IN (${tPlaceholders}) ORDER BY ordem`, trilhaIds);
    const [cicloRows] = await pool.query(`SELECT * FROM ciclos WHERE trilha_id IN (${tPlaceholders}) ORDER BY ordem`, trilhaIds);
    const cicloIds = cicloRows.map((r) => r.id);

    let moduloRows = [];
    if (cicloIds.length) {
      const cPlaceholders = cicloIds.map(() => '?').join(',');
      [moduloRows] = await pool.query(`SELECT * FROM modulos WHERE ciclo_id IN (${cPlaceholders}) ORDER BY ordem`, cicloIds);
    }
    const moduloIds = moduloRows.map((r) => r.id);

    let progRows = [];
    if (moduloIds.length) {
      const mPlaceholders = moduloIds.map(() => '?').join(',');
      [progRows] = await pool.query(
        `SELECT modulo_id, status FROM progresso WHERE colaborador_id = ? AND modulo_id IN (${mPlaceholders})`,
        [c.id, ...moduloIds]
      );
    }
    const statusByModulo = Object.fromEntries(progRows.map((p) => [p.modulo_id, p.status]));

    trilhas = trilhaRows.map((t) => ({
      id: t.id,
      nome: t.nome,
      ciclos: cicloRows
        .filter((ci) => ci.trilha_id === t.id)
        .map((ci) => ({
          id: ci.id,
          nome: ci.nome,
          modulos: moduloRows
            .filter((m) => m.ciclo_id === ci.id)
            .map((m) => ({
              id: m.id,
              nome: m.nome,
              status: statusByModulo[m.id] || 'Não iniciado',
            })),
        })),
    }));
  }

  res.json({
    nome: c.nome,
    cargo: c.cargo || '',
    unidade: unidadeNome,
    lideranca: !!c.lideranca,
    trilhas,
  });
}));

module.exports = router;
