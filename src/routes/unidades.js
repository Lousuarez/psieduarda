const express = require('express');
const { pool, genId } = require('../db');
const asyncHandler = require('../asyncHandler');
const { logAudit } = require('../audit');

const router = express.Router();

function toApi(row) {
  return { id: row.id, nome: row.nome };
}

router.get('/', asyncHandler(async (req, res) => {
  const [rows] = await pool.query('SELECT * FROM unidades ORDER BY updated_at ASC');
  res.json({ docs: rows.map(toApi) });
}));

router.post('/', asyncHandler(async (req, res) => {
  const id = genId();
  const nome = String(req.body.nome || '');
  await pool.query('INSERT INTO unidades (id, nome) VALUES (?, ?)', [id, nome]);
  const depois = { id, nome };
  await logAudit({ entidade: 'unidade', entidadeId: id, acao: 'create', antes: null, depois, req });
  res.json(depois);
}));

router.put('/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  const [existingRows] = await pool.query('SELECT * FROM unidades WHERE id = ?', [id]);
  if (!existingRows.length) return res.status(404).json({ error: 'not_found' });
  const antes = toApi(existingRows[0]);
  const nome = req.body.nome !== undefined ? String(req.body.nome) : existingRows[0].nome;
  await pool.query('UPDATE unidades SET nome = ? WHERE id = ?', [nome, id]);
  const depois = { id, nome };
  await logAudit({ entidade: 'unidade', entidadeId: id, acao: 'update', antes, depois, req });
  res.json(depois);
}));

router.delete('/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  try {
    const [rows] = await pool.query('SELECT * FROM unidades WHERE id = ?', [id]);
    await pool.query('DELETE FROM unidades WHERE id = ?', [id]);
    if (rows.length) await logAudit({ entidade: 'unidade', entidadeId: id, acao: 'delete', antes: toApi(rows[0]), depois: null, req });
    res.json({ ok: true });
  } catch (err) {
    if (err && err.code === 'ER_ROW_IS_REFERENCED_2') {
      return res.status(409).json({ error: 'has_dependents' });
    }
    throw err;
  }
}));

module.exports = router;
