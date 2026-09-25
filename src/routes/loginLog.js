const express = require('express');
const { pool } = require('../db');
const asyncHandler = require('../asyncHandler');
const { parseUserAgent } = require('../userAgent');

const router = express.Router();

function toApi(row) {
  const { os, browser } = parseUserAgent(row.user_agent);
  return {
    id: row.id,
    usuarioId: row.usuario_id,
    usuarioNome: row.usuario_nome || '',
    ipAddress: row.ip_address || '',
    os,
    browser,
    criadoEm: row.criado_em,
  };
}

router.get('/', asyncHandler(async (req, res) => {
  const where = [];
  const vals = [];
  if (req.query.usuarioNome) { where.push('usuario_nome = ?'); vals.push(String(req.query.usuarioNome)); }
  if (req.query.de) { where.push('criado_em >= ?'); vals.push(String(req.query.de) + ' 00:00:00'); }
  if (req.query.ate) { where.push('criado_em <= ?'); vals.push(String(req.query.ate) + ' 23:59:59'); }
  if (req.query.q) {
    where.push('(usuario_nome LIKE ? OR ip_address LIKE ? OR user_agent LIKE ?)');
    const like = `%${req.query.q}%`;
    vals.push(like, like, like);
  }
  const limit = Math.min(Number(req.query.limit) || 100, 500);
  const offset = Math.max(Number(req.query.offset) || 0, 0);

  const whereSql = where.length ? `WHERE ${where.join(' AND ')}` : '';
  const [rows] = await pool.query(
    `SELECT * FROM login_log ${whereSql} ORDER BY criado_em DESC LIMIT ? OFFSET ?`,
    [...vals, limit, offset]
  );
  const [[{ total }]] = await pool.query(`SELECT COUNT(*) AS total FROM login_log ${whereSql}`, vals);
  res.json({ docs: rows.map(toApi), total });
}));

router.get('/filtros', asyncHandler(async (req, res) => {
  const [usuarios] = await pool.query('SELECT DISTINCT usuario_nome FROM login_log ORDER BY usuario_nome');
  res.json({ usuarios: usuarios.map((r) => r.usuario_nome) });
}));

module.exports = router;
