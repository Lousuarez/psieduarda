const express = require('express');
const { sanitizeDescricao } = require('../sanitizeDescricao');
const { pool, genId } = require('../db');
const asyncHandler = require('../asyncHandler');
const { logAudit } = require('../audit');

const router = express.Router();

function toApi(row) {
  return {
    id: row.id,
    temaId: row.tema_id,
    nome: row.nome,
    etapa: row.etapa || '',
    ordem: row.ordem,
    categoria: row.categoria,
    status: row.status,
    descricao: row.descricao || '',
    mentor: row.mentor || '',
    formato: row.formato || '',
    publicoAlvo: row.publico_alvo || '',
    cargaHoraria: row.carga_horaria !== null && row.carga_horaria !== undefined ? Number(row.carga_horaria) : null,
    inicioPrevisto: row.inicio_previsto || '',
    linkMaterial: row.link_material || '',
    temCronograma: !!row.tem_cronograma,
  };
}

const FIELDS = [
  ['temaId', 'tema_id', String],
  ['nome', 'nome', String],
  ['etapa', 'etapa', String],
  ['ordem', 'ordem', Number],
  ['categoria', 'categoria', String],
  ['status', 'status', String],
  ['descricao', 'descricao', String],
  ['mentor', 'mentor', String],
  ['formato', 'formato', String],
  ['publicoAlvo', 'publico_alvo', String],
  ['cargaHoraria', 'carga_horaria', Number],
  ['inicioPrevisto', 'inicio_previsto', String],
  ['linkMaterial', 'link_material', String],
  ['temCronograma', 'tem_cronograma', Boolean],
];

function cargaHorariaVal(raw) {
  if (raw === undefined || raw === null || raw === '') return null;
  const n = Number(raw);
  return Number.isFinite(n) ? n : null;
}

router.get('/', asyncHandler(async (req, res) => {
  const [rows] = await pool.query('SELECT * FROM modulos ORDER BY updated_at ASC');
  res.json({ docs: rows.map(toApi) });
}));

router.post('/', asyncHandler(async (req, res) => {
  const id = genId();
  const cols = ['id'];
  const vals = [id];
  const placeholders = ['?'];
  for (const [apiKey, col] of FIELDS) {
    cols.push(col);
    placeholders.push('?');
    if (apiKey === 'ordem') {
      vals.push(Number.isFinite(Number(req.body[apiKey])) ? Number(req.body[apiKey]) : 0);
    } else if (apiKey === 'categoria') {
      vals.push(req.body[apiKey] || 'Liderança');
    } else if (apiKey === 'status') {
      vals.push(req.body[apiKey] || 'A iniciar');
    } else if (apiKey === 'temCronograma') {
      vals.push(!!req.body[apiKey]);
    } else if (apiKey === 'descricao') {
      vals.push(sanitizeDescricao(req.body[apiKey]));
    } else if (apiKey === 'cargaHoraria') {
      vals.push(cargaHorariaVal(req.body[apiKey]));
    } else {
      vals.push(req.body[apiKey] !== undefined ? String(req.body[apiKey]) : '');
    }
  }
  await pool.query(`INSERT INTO modulos (${cols.join(', ')}) VALUES (${placeholders.join(', ')})`, vals);
  const [rows] = await pool.query('SELECT * FROM modulos WHERE id = ?', [id]);
  const depois = toApi(rows[0]);
  await logAudit({ entidade: 'modulo', entidadeId: id, acao: 'create', antes: null, depois, req });
  res.json(depois);
}));

router.put('/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  const [rows] = await pool.query('SELECT * FROM modulos WHERE id = ?', [id]);
  if (!rows.length) return res.status(404).json({ error: 'not_found' });
  const antes = toApi(rows[0]);
  const sets = [];
  const vals = [];
  for (const [apiKey, col] of FIELDS) {
    if (req.body[apiKey] === undefined) continue;
    sets.push(`${col} = ?`);
    if (apiKey === 'ordem') vals.push(Number(req.body[apiKey]));
    else if (apiKey === 'descricao') vals.push(sanitizeDescricao(req.body[apiKey]));
    else if (apiKey === 'cargaHoraria') vals.push(cargaHorariaVal(req.body[apiKey]));
    else if (apiKey === 'temCronograma') vals.push(!!req.body[apiKey]);
    else vals.push(String(req.body[apiKey]));
  }
  if (sets.length) {
    vals.push(id);
    await pool.query(`UPDATE modulos SET ${sets.join(', ')} WHERE id = ?`, vals);
  }
  const [updated] = await pool.query('SELECT * FROM modulos WHERE id = ?', [id]);
  const depois = toApi(updated[0]);
  await logAudit({ entidade: 'modulo', entidadeId: id, acao: 'update', antes, depois, req });
  res.json(depois);
}));

router.delete('/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  const [rows] = await pool.query('SELECT * FROM modulos WHERE id = ?', [id]);
  await pool.query('DELETE FROM modulos WHERE id = ?', [id]);
  if (rows.length) await logAudit({ entidade: 'modulo', entidadeId: id, acao: 'delete', antes: toApi(rows[0]), depois: null, req });
  res.json({ ok: true });
}));

module.exports = router;
