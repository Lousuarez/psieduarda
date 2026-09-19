const express = require('express');
const bcrypt = require('bcryptjs');
const { pool, genId } = require('../db');
const asyncHandler = require('../asyncHandler');

const router = express.Router();

function toApi(row) {
  return { id: row.id, username: row.username };
}

router.get('/', asyncHandler(async (req, res) => {
  const [rows] = await pool.query('SELECT id, username FROM users ORDER BY username ASC');
  res.json({ docs: rows.map(toApi) });
}));

router.post('/', asyncHandler(async (req, res) => {
  const username = String(req.body.username || '').trim();
  const password = String(req.body.password || '');
  if (!username || !password) return res.status(400).json({ error: 'missing_fields' });
  const id = genId();
  const passwordHash = await bcrypt.hash(password, 10);
  try {
    await pool.query('INSERT INTO users (id, username, password_hash) VALUES (?, ?, ?)', [id, username, passwordHash]);
  } catch (err) {
    if (err && err.code === 'ER_DUP_ENTRY') return res.status(409).json({ error: 'username_taken' });
    throw err;
  }
  res.json({ id, username });
}));

router.put('/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  const [rows] = await pool.query('SELECT * FROM users WHERE id = ?', [id]);
  if (!rows.length) return res.status(404).json({ error: 'not_found' });
  const cur = rows[0];
  const username = req.body.username !== undefined ? String(req.body.username).trim() : cur.username;
  const passwordHash = req.body.password ? await bcrypt.hash(String(req.body.password), 10) : cur.password_hash;
  try {
    await pool.query('UPDATE users SET username = ?, password_hash = ? WHERE id = ?', [username, passwordHash, id]);
  } catch (err) {
    if (err && err.code === 'ER_DUP_ENTRY') return res.status(409).json({ error: 'username_taken' });
    throw err;
  }
  res.json({ id, username });
}));

router.delete('/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  if (req.user && req.user.id === id) return res.status(409).json({ error: 'cannot_delete_self' });
  const [countRows] = await pool.query('SELECT COUNT(*) AS count FROM users');
  if (countRows[0].count <= 1) return res.status(409).json({ error: 'last_user' });
  await pool.query('DELETE FROM users WHERE id = ?', [id]);
  res.json({ ok: true });
}));

module.exports = router;
