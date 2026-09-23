const express = require('express');
const { pool } = require('../db');
const asyncHandler = require('../asyncHandler');
const { ENTIDADE_LABELS } = require('../audit');

const router = express.Router();

function toApi(row) {
  return {
    id: row.id,
    entidade: row.entidade,
    entidadeId: row.entidade_id,
    acao: row.acao,
    valoresAntes: row.valores_antes ?? null,
    valoresDepois: row.valores_depois ?? null,
    usuarioId: row.usuario_id,
    usuarioNome: row.usuario_nome || '',
    interface: row.interface || '',
    rota: row.rota || '',
    criadoEm: row.criado_em,
  };
}

router.get('/', asyncHandler(async (req, res) => {
  const where = [];
  const vals = [];
  if (req.query.entidade) { where.push('entidade = ?'); vals.push(String(req.query.entidade)); }
  if (req.query.acao) { where.push('acao = ?'); vals.push(String(req.query.acao)); }
  if (req.query.usuarioNome) { where.push('usuario_nome = ?'); vals.push(String(req.query.usuarioNome)); }
  if (req.query.de) { where.push('criado_em >= ?'); vals.push(String(req.query.de) + ' 00:00:00'); }
  if (req.query.ate) { where.push('criado_em <= ?'); vals.push(String(req.query.ate) + ' 23:59:59'); }
  if (req.query.q) {
    where.push('(entidade_id LIKE ? OR usuario_nome LIKE ? OR interface LIKE ? OR valores_antes LIKE ? OR valores_depois LIKE ?)');
    const like = `%${req.query.q}%`;
    vals.push(like, like, like, like, like);
  }
  const limit = Math.min(Number(req.query.limit) || 200, 500);
  const offset = Math.max(Number(req.query.offset) || 0, 0);

  const whereSql = where.length ? `WHERE ${where.join(' AND ')}` : '';
  const [rows] = await pool.query(
    `SELECT * FROM audit_log ${whereSql} ORDER BY criado_em DESC LIMIT ? OFFSET ?`,
    [...vals, limit, offset]
  );
  const [[{ total }]] = await pool.query(`SELECT COUNT(*) AS total FROM audit_log ${whereSql}`, vals);
  res.json({ docs: rows.map(toApi), total });
}));

// Listas pra popular os filtros (entidades e usuários que já geraram algum
// registro, sem precisar carregar o log inteiro no cliente).
router.get('/filtros', asyncHandler(async (req, res) => {
  const [entidades] = await pool.query('SELECT DISTINCT entidade FROM audit_log ORDER BY entidade');
  const [usuarios] = await pool.query('SELECT DISTINCT usuario_nome FROM audit_log WHERE usuario_nome IS NOT NULL ORDER BY usuario_nome');
  res.json({
    entidades: entidades.map((r) => ({ valor: r.entidade, label: ENTIDADE_LABELS[r.entidade] || r.entidade })),
    usuarios: usuarios.map((r) => r.usuario_nome),
  });
}));

module.exports = router;
