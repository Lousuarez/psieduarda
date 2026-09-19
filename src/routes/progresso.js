const express = require('express');
const { pool } = require('../db');
const asyncHandler = require('../asyncHandler');

const router = express.Router();

function toApi(row) {
  return {
    id: `${row.colaborador_id}-${row.modulo_id}`,
    colaboradorId: row.colaborador_id,
    moduloId: row.modulo_id,
    status: row.status,
  };
}

router.get('/', asyncHandler(async (req, res) => {
  const [rows] = await pool.query('SELECT * FROM progresso');
  res.json({ docs: rows.map(toApi) });
}));

router.put('/:colaboradorId/:moduloId', asyncHandler(async (req, res) => {
  const { colaboradorId, moduloId } = req.params;
  const status = String(req.body.status || '');

  if (status === 'Não iniciado') {
    await pool.query(
      'DELETE FROM progresso WHERE colaborador_id = ? AND modulo_id = ?',
      [colaboradorId, moduloId]
    );
    return res.json({ id: `${colaboradorId}-${moduloId}`, colaboradorId, moduloId, status: 'Não iniciado' });
  }

  await pool.query(
    `INSERT INTO progresso (colaborador_id, modulo_id, status) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE status = VALUES(status)`,
    [colaboradorId, moduloId, status]
  );
  res.json({ id: `${colaboradorId}-${moduloId}`, colaboradorId, moduloId, status });
}));

module.exports = router;
