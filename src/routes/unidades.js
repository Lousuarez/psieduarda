const express = require('express');
const { pool, genId } = require('../db');
const asyncHandler = require('../asyncHandler');

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
  res.json({ id, nome });
}));

router.put('/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  const [existingRows] = await pool.query('SELECT * FROM unidades WHERE id = ?', [id]);
  if (!existingRows.length) return res.status(404).json({ error: 'not_found' });
  const nome = req.body.nome !== undefined ? String(req.body.nome) : existingRows[0].nome;
  await pool.query('UPDATE unidades SET nome = ? WHERE id = ?', [nome, id]);
  res.json({ id, nome });
}));

router.delete('/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  try {
    await pool.query('DELETE FROM unidades WHERE id = ?', [id]);
    res.json({ ok: true });
  } catch (err) {
    if (err && err.code === 'ER_ROW_IS_REFERENCED_2') {
      return res.status(409).json({ error: 'has_dependents' });
    }
    throw err;
  }
}));

module.exports = router;
