const express = require('express');
const { attemptLogin, issueToken, revokeToken, verifyToken, getBearerToken } = require('../auth');
const asyncHandler = require('../asyncHandler');

const router = express.Router();

router.post('/login', asyncHandler(async (req, res) => {
  const username = String(req.body.user || '').trim();
  const pass = String(req.body.pass || '');
  const user = await attemptLogin(username, pass);
  if (!user) return res.status(401).json({ error: 'invalid_credentials' });
  const token = await issueToken(user.id);
  res.json({ ok: true, token, id: user.id, username: user.username });
}));

router.post('/logout', asyncHandler(async (req, res) => {
  await revokeToken(getBearerToken(req));
  res.json({ ok: true });
}));

router.get('/me', asyncHandler(async (req, res) => {
  const user = await verifyToken(getBearerToken(req));
  if (!user) return res.status(401).json({ error: 'not_authenticated' });
  res.json({ ok: true, id: user.id, username: user.username });
}));

module.exports = router;
