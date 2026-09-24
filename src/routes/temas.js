const express = require('express');
const { sanitizeDescricao } = require('../sanitizeDescricao');
const { pool, genId } = require('../db');
const asyncHandler = require('../asyncHandler');
const { logAudit } = require('../audit');

const router = express.Router();

// "cargaHoraria" nunca é gravada em temas — é sempre a soma de
// modulos.carga_horaria dos módulos deste tema (ver pedido do usuário:
// carga horária do tema = soma da carga horária dos módulos que o compõem).
function toApi(row) {
  return {
    id: row.id,
    cicloId: row.ciclo_id,
    titulo: row.titulo,
    descricao: row.descricao || '',
    mes: row.mes || '',
    formato: row.formato || '',
    icon: row.icon || 'layers',
    imagem: row.imagem || '',
    ordem: row.ordem,
    cargaHoraria: row.carga_horaria_total !== undefined && row.carga_horaria_total !== null ? Number(row.carga_horaria_total) : 0,
  };
}

const FIELDS = [
  ['cicloId', 'ciclo_id', String],
  ['titulo', 'titulo', String],
  ['descricao', 'descricao', String],
  ['mes', 'mes', String],
  ['formato', 'formato', String],
  ['icon', 'icon', String],
  ['imagem', 'imagem', String],
  ['ordem', 'ordem', Number],
];

const SELECT_WITH_CARGA = `
  SELECT t.*, (SELECT COALESCE(SUM(m.carga_horaria), 0) FROM modulos m WHERE m.tema_id = t.id) AS carga_horaria_total
  FROM temas t
`;

router.get('/', asyncHandler(async (req, res) => {
  const [rows] = await pool.query(`${SELECT_WITH_CARGA} ORDER BY t.updated_at ASC`);
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
    } else if (apiKey === 'icon') {
      vals.push(req.body[apiKey] || 'layers');
    } else if (apiKey === 'descricao') {
      vals.push(sanitizeDescricao(req.body[apiKey]));
    } else {
      vals.push(req.body[apiKey] !== undefined ? String(req.body[apiKey]) : '');
    }
  }
  await pool.query(`INSERT INTO temas (${cols.join(', ')}) VALUES (${placeholders.join(', ')})`, vals);
  const [rows] = await pool.query(`${SELECT_WITH_CARGA} WHERE t.id = ?`, [id]);
  const depois = toApi(rows[0]);
  await logAudit({ entidade: 'tema', entidadeId: id, acao: 'create', antes: null, depois, req });
  res.json(depois);
}));

router.put('/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  const [rows] = await pool.query(`${SELECT_WITH_CARGA} WHERE t.id = ?`, [id]);
  if (!rows.length) return res.status(404).json({ error: 'not_found' });
  const antes = toApi(rows[0]);
  const sets = [];
  const vals = [];
  for (const [apiKey, col] of FIELDS) {
    if (req.body[apiKey] === undefined) continue;
    sets.push(`${col} = ?`);
    if (apiKey === 'ordem') vals.push(Number(req.body[apiKey]));
    else if (apiKey === 'descricao') vals.push(sanitizeDescricao(req.body[apiKey]));
    else if (apiKey === 'icon') vals.push(req.body[apiKey] || 'layers');
    else vals.push(String(req.body[apiKey]));
  }
  if (sets.length) {
    vals.push(id);
    await pool.query(`UPDATE temas SET ${sets.join(', ')} WHERE id = ?`, vals);
  }
  const [updated] = await pool.query(`${SELECT_WITH_CARGA} WHERE t.id = ?`, [id]);
  const depois = toApi(updated[0]);
  await logAudit({ entidade: 'tema', entidadeId: id, acao: 'update', antes, depois, req });
  res.json(depois);
}));

router.delete('/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  try {
    const [rows] = await pool.query(`${SELECT_WITH_CARGA} WHERE t.id = ?`, [id]);
    await pool.query('DELETE FROM temas WHERE id = ?', [id]);
    if (rows.length) await logAudit({ entidade: 'tema', entidadeId: id, acao: 'delete', antes: toApi(rows[0]), depois: null, req });
    res.json({ ok: true });
  } catch (err) {
    if (err && err.code === 'ER_ROW_IS_REFERENCED_2') {
      return res.status(409).json({ error: 'has_dependents' });
    }
    throw err;
  }
}));

module.exports = router;
