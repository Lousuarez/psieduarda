const express = require('express');
const bcrypt = require('bcryptjs');
const { pool, genId } = require('../db');
const asyncHandler = require('../asyncHandler');
const { logAudit } = require('../audit');

const router = express.Router();

function toApi(row) {
  return { id: row.id, username: row.username, isAdmin: !!row.is_admin };
}

async function countAdmins() {
  const [rows] = await pool.query('SELECT COUNT(*) AS count FROM users WHERE is_admin = TRUE');
  return rows[0].count;
}

router.get('/', asyncHandler(async (req, res) => {
  const [rows] = await pool.query('SELECT id, username, is_admin FROM users ORDER BY username ASC');
  res.json({ docs: rows.map(toApi) });
}));

router.post('/', asyncHandler(async (req, res) => {
  const username = String(req.body.username || '').trim();
  const password = String(req.body.password || '');
  const isAdmin = !!req.body.isAdmin;
  if (!username || !password) return res.status(400).json({ error: 'missing_fields' });
  const id = genId();
  const passwordHash = await bcrypt.hash(password, 10);
  try {
    await pool.query(
      'INSERT INTO users (id, username, password_hash, is_admin) VALUES (?, ?, ?, ?)',
      [id, username, passwordHash, isAdmin]
    );
  } catch (err) {
    if (err && err.code === 'ER_DUP_ENTRY') return res.status(409).json({ error: 'username_taken' });
    throw err;
  }
  const depois = { id, username, isAdmin };
  await logAudit({ entidade: 'usuario', entidadeId: id, acao: 'create', antes: null, depois, req });
  res.json(depois);
}));

router.put('/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  const [rows] = await pool.query('SELECT * FROM users WHERE id = ?', [id]);
  if (!rows.length) return res.status(404).json({ error: 'not_found' });
  const cur = rows[0];
  const antes = toApi(cur);
  const username = req.body.username !== undefined ? String(req.body.username).trim() : cur.username;
  const passwordChanged = !!req.body.password;
  const passwordHash = req.body.password ? await bcrypt.hash(String(req.body.password), 10) : cur.password_hash;
  const isAdmin = req.body.isAdmin !== undefined ? !!req.body.isAdmin : !!cur.is_admin;

  if (cur.is_admin && !isAdmin && (await countAdmins()) <= 1) {
    return res.status(409).json({ error: 'last_admin' });
  }

  try {
    await pool.query(
      'UPDATE users SET username = ?, password_hash = ?, is_admin = ? WHERE id = ?',
      [username, passwordHash, isAdmin, id]
    );
  } catch (err) {
    if (err && err.code === 'ER_DUP_ENTRY') return res.status(409).json({ error: 'username_taken' });
    throw err;
  }
  // Nunca gravar o hash da senha no log de auditoria — só se ela foi trocada.
  const depois = { id, username, isAdmin, senhaAlterada: passwordChanged || undefined };
  await logAudit({ entidade: 'usuario', entidadeId: id, acao: 'update', antes, depois, req });
  res.json({ id, username, isAdmin });
}));

router.delete('/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  if (req.user && req.user.id === id) return res.status(409).json({ error: 'cannot_delete_self' });
  const [rows] = await pool.query('SELECT * FROM users WHERE id = ?', [id]);
  if (!rows.length) return res.status(404).json({ error: 'not_found' });
  const [countRows] = await pool.query('SELECT COUNT(*) AS count FROM users');
  if (countRows[0].count <= 1) return res.status(409).json({ error: 'last_user' });
  if (rows[0].is_admin && (await countAdmins()) <= 1) {
    return res.status(409).json({ error: 'last_admin' });
  }
  await pool.query('DELETE FROM users WHERE id = ?', [id]);
  await logAudit({ entidade: 'usuario', entidadeId: id, acao: 'delete', antes: toApi(rows[0]), depois: null, req });
  res.json({ ok: true });
}));

module.exports = router;
