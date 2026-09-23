const express = require('express');
const { pool, genId } = require('../db');
const asyncHandler = require('../asyncHandler');

const router = express.Router();

function toApi(row) {
  return {
    id: row.id,
    moduloId: row.modulo_id,
    nome: row.nome,
    dataInicio: row.data_inicio ? formatDate(row.data_inicio) : '',
    dataFim: row.data_fim ? formatDate(row.data_fim) : '',
    status: row.status,
    ordem: row.ordem,
  };
}

// mysql2 devolve DATE como objeto Date; convertemos pra "YYYY-MM-DD" (o
// formato que <input type="date"> espera) sem passar pelo fuso do servidor.
function formatDate(d) {
  const yyyy = d.getFullYear();
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  return `${yyyy}-${mm}-${dd}`;
}

router.get('/', asyncHandler(async (req, res) => {
  const [rows] = await pool.query('SELECT * FROM modulo_etapas ORDER BY ordem ASC');
  res.json({ docs: rows.map(toApi) });
}));

router.post('/', asyncHandler(async (req, res) => {
  const id = genId();
  const moduloId = String(req.body.moduloId || '');
  const nome = String(req.body.nome || '');
  const dataInicio = req.body.dataInicio || null;
  const dataFim = req.body.dataFim || null;
  const status = req.body.status || 'A iniciar';
  const ordem = Number.isFinite(Number(req.body.ordem)) ? Number(req.body.ordem) : 0;
  if (!moduloId || !nome) return res.status(400).json({ error: 'missing_fields' });
  await pool.query(
    'INSERT INTO modulo_etapas (id, modulo_id, nome, data_inicio, data_fim, status, ordem) VALUES (?, ?, ?, ?, ?, ?, ?)',
    [id, moduloId, nome, dataInicio, dataFim, status, ordem]
  );
  const [rows] = await pool.query('SELECT * FROM modulo_etapas WHERE id = ?', [id]);
  res.json(toApi(rows[0]));
}));

router.put('/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  const [rows] = await pool.query('SELECT * FROM modulo_etapas WHERE id = ?', [id]);
  if (!rows.length) return res.status(404).json({ error: 'not_found' });
  const cur = rows[0];
  const b = req.body;
  const nome = b.nome !== undefined ? String(b.nome) : cur.nome;
  const dataInicio = b.dataInicio !== undefined ? (b.dataInicio || null) : cur.data_inicio;
  const dataFim = b.dataFim !== undefined ? (b.dataFim || null) : cur.data_fim;
  const status = b.status !== undefined ? b.status : cur.status;
  const ordem = b.ordem !== undefined ? Number(b.ordem) : cur.ordem;
  await pool.query(
    'UPDATE modulo_etapas SET nome=?, data_inicio=?, data_fim=?, status=?, ordem=? WHERE id=?',
    [nome, dataInicio, dataFim, status, ordem, id]
  );
  const [updated] = await pool.query('SELECT * FROM modulo_etapas WHERE id = ?', [id]);
  res.json(toApi(updated[0]));
}));

router.delete('/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  await pool.query('DELETE FROM modulo_etapas WHERE id = ?', [id]);
  res.json({ ok: true });
}));

module.exports = router;
