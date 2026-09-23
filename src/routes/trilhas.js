const express = require('express');
const { pool, genId } = require('../db');
const asyncHandler = require('../asyncHandler');
const { logAudit } = require('../audit');

const router = express.Router();

function toApi(row) {
  return {
    id: row.id,
    nome: row.nome,
    descricao: row.descricao || '',
    ordem: row.ordem,
  };
}

router.get('/', asyncHandler(async (req, res) => {
  const [rows] = await pool.query('SELECT * FROM trilhas ORDER BY updated_at ASC');
  res.json({ docs: rows.map(toApi) });
}));

router.post('/', asyncHandler(async (req, res) => {
  const id = genId();
  const nome = String(req.body.nome || '');
  const descricao = req.body.descricao !== undefined ? String(req.body.descricao) : '';
  const ordem = Number.isFinite(Number(req.body.ordem)) ? Number(req.body.ordem) : 0;
  await pool.query('INSERT INTO trilhas (id, nome, descricao, ordem) VALUES (?, ?, ?, ?)', [id, nome, descricao, ordem]);
  const depois = { id, nome, descricao, ordem };
  await logAudit({ entidade: 'trilha', entidadeId: id, acao: 'create', antes: null, depois, req });
  res.json(depois);
}));

router.put('/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  const [rows] = await pool.query('SELECT * FROM trilhas WHERE id = ?', [id]);
  if (!rows.length) return res.status(404).json({ error: 'not_found' });
  const cur = rows[0];
  const antes = toApi(cur);
  const nome = req.body.nome !== undefined ? String(req.body.nome) : cur.nome;
  const descricao = req.body.descricao !== undefined ? String(req.body.descricao) : cur.descricao;
  const ordem = req.body.ordem !== undefined ? Number(req.body.ordem) : cur.ordem;
  await pool.query('UPDATE trilhas SET nome = ?, descricao = ?, ordem = ? WHERE id = ?', [nome, descricao, ordem, id]);
  const depois = { id, nome, descricao, ordem };
  await logAudit({ entidade: 'trilha', entidadeId: id, acao: 'update', antes, depois, req });
  res.json(depois);
}));

router.delete('/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  try {
    const [rows] = await pool.query('SELECT * FROM trilhas WHERE id = ?', [id]);
    await pool.query('DELETE FROM trilhas WHERE id = ?', [id]);
    if (rows.length) await logAudit({ entidade: 'trilha', entidadeId: id, acao: 'delete', antes: toApi(rows[0]), depois: null, req });
    res.json({ ok: true });
  } catch (err) {
    if (err && err.code === 'ER_ROW_IS_REFERENCED_2') {
      return res.status(409).json({ error: 'has_dependents' });
    }
    throw err;
  }
}));

module.exports = router;
