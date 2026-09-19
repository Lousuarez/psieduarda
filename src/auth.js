const bcrypt = require('bcryptjs');
const crypto = require('crypto');

function timingSafeEqualStr(a, b) {
  const bufA = Buffer.from(String(a));
  const bufB = Buffer.from(String(b));
  if (bufA.length !== bufB.length) {
    // Still run a comparison of equal length to avoid leaking length via timing.
    crypto.timingSafeEqual(bufA, bufA);
    return false;
  }
  return crypto.timingSafeEqual(bufA, bufB);
}

async function attemptLogin(user, pass) {
  const expectedUser = process.env.ADMIN_USER || '';
  const expectedHash = process.env.ADMIN_PASS_HASH || '';
  if (!timingSafeEqualStr(user, expectedUser)) return false;
  if (!expectedHash) return false;
  return bcrypt.compare(pass, expectedHash);
}

function requireAuth(req, res, next) {
  if (!req.session || !req.session.authed) {
    return res.status(401).json({ error: 'not_authenticated' });
  }
  next();
}

module.exports = { attemptLogin, requireAuth };
