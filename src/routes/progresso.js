const express = require('express');
const { pool, genId } = require('../db');
const asyncHandler = require('../asyncHandler');

const router = express.Router();

async function logHistorico(colaboradorId, moduloId, status) {
  await pool.query(
    'INSERT INTO progresso_historico (id, colaborador_id, modulo_id, status) VALUES (?, ?, ?, ?)',
    [genId(), colaboradorId, moduloId, status]
  );
}

function toApi(row) {
  return {
    id: `${row.colaborador_id}-${row.modulo_id}`,
    colaboradorId: row.colaborador_id,
    moduloId: row.modulo_id,
    status: row.status,
    updatedAt: row.updated_at,
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
    await logHistorico(colaboradorId, moduloId, 'Não iniciado');
    return res.json({ id: `${colaboradorId}-${moduloId}`, colaboradorId, moduloId, status: 'Não iniciado' });
  }

  await pool.query(
    `INSERT INTO progresso (colaborador_id, modulo_id, status) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE status = VALUES(status)`,
    [colaboradorId, moduloId, status]
  );
  await logHistorico(colaboradorId, moduloId, status);
  res.json({ id: `${colaboradorId}-${moduloId}`, colaboradorId, moduloId, status });
}));

module.exports = router;
