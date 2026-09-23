const express = require('express');
const { pool } = require('../db');
const asyncHandler = require('../asyncHandler');
const { requireAdmin } = require('../auth');

const router = express.Router();

function toApi(row) {
  return {
    id: `${row.etapa_id}-${row.colaborador_id}`,
    etapaId: row.etapa_id,
    colaboradorId: row.colaborador_id,
    presente: !!row.presente,
    updatedAt: row.updated_at,
  };
}

router.get('/', asyncHandler(async (req, res) => {
  const [rows] = await pool.query('SELECT * FROM modulo_etapa_frequencia');
  res.json({ docs: rows.map(toApi) });
}));

router.put('/:etapaId/:colaboradorId', requireAdmin, asyncHandler(async (req, res) => {
  const { etapaId, colaboradorId } = req.params;
  const presente = !!req.body.presente;
  const [chamada] = await pool.query('SELECT status FROM modulo_etapa_chamada WHERE etapa_id = ?', [etapaId]);
  if (!chamada.length || chamada[0].status !== 'aberta') {
    return res.status(409).json({ error: 'chamada_nao_esta_aberta' });
  }
  await pool.query(
    `INSERT INTO modulo_etapa_frequencia (etapa_id, colaborador_id, presente) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE presente = VALUES(presente)`,
    [etapaId, colaboradorId, presente]
  );
  res.json({ id: `${etapaId}-${colaboradorId}`, etapaId, colaboradorId, presente });
}));

module.exports = router;
