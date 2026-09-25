const express = require('express');
const { attemptLogin, issueToken, revokeToken, verifyToken, getBearerToken } = require('../auth');
const asyncHandler = require('../asyncHandler');
const { pool, genId } = require('../db');

const router = express.Router();

router.post('/login', asyncHandler(async (req, res) => {
  const username = String(req.body.user || '').trim();
  const pass = String(req.body.pass || '');
  const user = await attemptLogin(username, pass);
  if (!user) return res.status(401).json({ error: 'invalid_credentials' });
  const token = await issueToken(user.id);
  try {
    await pool.query(
      'INSERT INTO login_log (id, usuario_id, usuario_nome, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)',
      [genId(), user.id, user.username, req.ip || null, req.headers['user-agent'] || null]
    );
  } catch (err) {
    console.error('Falha ao gravar login_log', err);
  }
  res.json({ ok: true, token, id: user.id, username: user.username, isAdmin: user.isAdmin });
}));

router.post('/logout', asyncHandler(async (req, res) => {
  await revokeToken(getBearerToken(req));
  res.json({ ok: true });
}));

router.get('/me', asyncHandler(async (req, res) => {
  const user = await verifyToken(getBearerToken(req));
  if (!user) return res.status(401).json({ error: 'not_authenticated' });
  res.json({ ok: true, id: user.id, username: user.username, isAdmin: user.isAdmin });
}));

module.exports = router;
