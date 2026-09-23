const express = require('express');
const { pool, genId } = require('../db');
const asyncHandler = require('../asyncHandler');
const { logAudit } = require('../audit');

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
  const entidadeId = `${colaboradorId}-${moduloId}`;
  const [existing] = await pool.query('SELECT status FROM progresso WHERE colaborador_id = ? AND modulo_id = ?', [colaboradorId, moduloId]);
  const antes = { colaboradorId, moduloId, status: existing[0]?.status || 'Não iniciado' };

  if (status === 'Não iniciado') {
    await pool.query(
      'DELETE FROM progresso WHERE colaborador_id = ? AND modulo_id = ?',
      [colaboradorId, moduloId]
    );
    await logHistorico(colaboradorId, moduloId, 'Não iniciado');
    const depois = { colaboradorId, moduloId, status: 'Não iniciado' };
    await logAudit({ entidade: 'progresso', entidadeId, acao: 'update', antes, depois, req });
    return res.json({ id: entidadeId, ...depois });
  }

  await pool.query(
    `INSERT INTO progresso (colaborador_id, modulo_id, status) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE status = VALUES(status)`,
    [colaboradorId, moduloId, status]
  );
  await logHistorico(colaboradorId, moduloId, status);
  const depois = { colaboradorId, moduloId, status };
  await logAudit({ entidade: 'progresso', entidadeId, acao: existing.length ? 'update' : 'create', antes: existing.length ? antes : null, depois, req });
  res.json({ id: entidadeId, ...depois });
}));

module.exports = router;
