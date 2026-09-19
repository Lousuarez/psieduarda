const express = require('express');
const { pool, genId } = require('../db');
const asyncHandler = require('../asyncHandler');

const router = express.Router();

function toApi(row) {
  return {
    id: row.id,
    cicloId: row.ciclo_id,
    nome: row.nome,
    etapa: row.etapa || '',
    ordem: row.ordem,
    categoria: row.categoria,
    status: row.status,
    descricao: row.descricao || '',
    mentor: row.mentor || '',
    formato: row.formato || '',
    publicoAlvo: row.publico_alvo || '',
    cargaHoraria: row.carga_horaria || '',
    inicioPrevisto: row.inicio_previsto || '',
    linkMaterial: row.link_material || '',
  };
}

const FIELDS = [
  ['cicloId', 'ciclo_id', String],
  ['nome', 'nome', String],
  ['etapa', 'etapa', String],
  ['ordem', 'ordem', Number],
  ['categoria', 'categoria', String],
  ['status', 'status', String],
  ['descricao', 'descricao', String],
  ['mentor', 'mentor', String],
  ['formato', 'formato', String],
  ['publicoAlvo', 'publico_alvo', String],
  ['cargaHoraria', 'carga_horaria', String],
  ['inicioPrevisto', 'inicio_previsto', String],
  ['linkMaterial', 'link_material', String],
];

router.get('/', asyncHandler(async (req, res) => {
  const [rows] = await pool.query('SELECT * FROM modulos ORDER BY updated_at ASC');
  res.json({ docs: rows.map(toApi) });
}));

router.post('/', asyncHandler(async (req, res) => {
  const id = genId();
  const cols = ['id'];
  const vals = [id];
  const placeholders = ['?'];
  for (const [apiKey, col, cast] of FIELDS) {
    cols.push(col);
    placeholders.push('?');
    if (apiKey === 'ordem') {
      vals.push(Number.isFinite(Number(req.body[apiKey])) ? Number(req.body[apiKey]) : 0);
    } else if (apiKey === 'categoria') {
      vals.push(req.body[apiKey] || 'Liderança');
    } else if (apiKey === 'status') {
      vals.push(req.body[apiKey] || 'A iniciar');
    } else {
      vals.push(req.body[apiKey] !== undefined ? cast(req.body[apiKey]) : '');
    }
  }
  await pool.query(`INSERT INTO modulos (${cols.join(', ')}) VALUES (${placeholders.join(', ')})`, vals);
  const [rows] = await pool.query('SELECT * FROM modulos WHERE id = ?', [id]);
  res.json(toApi(rows[0]));
}));

router.put('/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  const [rows] = await pool.query('SELECT * FROM modulos WHERE id = ?', [id]);
  if (!rows.length) return res.status(404).json({ error: 'not_found' });
  const sets = [];
  const vals = [];
  for (const [apiKey, col, cast] of FIELDS) {
    if (req.body[apiKey] === undefined) continue;
    sets.push(`${col} = ?`);
    vals.push(apiKey === 'ordem' ? Number(req.body[apiKey]) : cast(req.body[apiKey]));
  }
  if (sets.length) {
    vals.push(id);
    await pool.query(`UPDATE modulos SET ${sets.join(', ')} WHERE id = ?`, vals);
  }
  const [updated] = await pool.query('SELECT * FROM modulos WHERE id = ?', [id]);
  res.json(toApi(updated[0]));
}));

router.delete('/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  await pool.query('DELETE FROM modulos WHERE id = ?', [id]);
  res.json({ ok: true });
}));

module.exports = router;
