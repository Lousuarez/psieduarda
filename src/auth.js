const bcrypt = require('bcryptjs');
const crypto = require('crypto');
const { pool } = require('./db');

const TOKEN_TTL_MS = 1000 * 60 * 60 * 24 * 30; // 30 dias

async function attemptLogin(username, pass) {
  const [rows] = await pool.query('SELECT * FROM users WHERE username = ?', [username]);
  const user = rows[0];
  if (!user) return null;
  const ok = await bcrypt.compare(pass, user.password_hash);
  return ok ? user : null;
}

async function issueToken(userId) {
  const token = crypto.randomBytes(32).toString('hex');
  const expiresAt = new Date(Date.now() + TOKEN_TTL_MS);
  await pool.query('INSERT INTO auth_tokens (token, user_id, expires_at) VALUES (?, ?, ?)', [token, userId, expiresAt]);
  return token;
}

async function verifyToken(token) {
  if (!token) return null;
  const [rows] = await pool.query(
    `SELECT u.id, u.username FROM auth_tokens t
     JOIN users u ON u.id = t.user_id
     WHERE t.token = ? AND t.expires_at > NOW()`,
    [token]
  );
  return rows[0] || null;
}

async function revokeToken(token) {
  if (!token) return;
  await pool.query('DELETE FROM auth_tokens WHERE token = ?', [token]);
}

function getBearerToken(req) {
  const header = req.headers.authorization || '';
  const match = /^Bearer\s+(.+)$/i.exec(header);
  return match ? match[1] : null;
}

async function requireAuth(req, res, next) {
  const token = getBearerToken(req);
  const user = await verifyToken(token);
  if (!user) return res.status(401).json({ error: 'not_authenticated' });
  req.user = user;
  next();
}

module.exports = { attemptLogin, issueToken, verifyToken, revokeToken, getBearerToken, requireAuth };
