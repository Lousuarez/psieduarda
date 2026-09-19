const express = require('express');
const { pool, genId } = require('../db');
const asyncHandler = require('../asyncHandler');

const router = express.Router();

function toApi(row, trilhaIds) {
  return {
    id: row.id,
    nome: row.nome,
    email: row.email || '',
    cargo: row.cargo || '',
    unidadeId: row.unidade_id || '',
    gestor: row.gestor || '',
    dataAdmissao: row.data_admissao || '',
    lideranca: !!row.lideranca,
    ativo: !!row.ativo,
    nota: row.nota || '',
    trilhaIds: trilhaIds || [],
  };
}

async function loadTrilhaIdsMap() {
  const [rows] = await pool.query('SELECT colaborador_id, trilha_id FROM colaborador_trilhas');
  const map = {};
  for (const r of rows) {
    if (!map[r.colaborador_id]) map[r.colaborador_id] = [];
    map[r.colaborador_id].push(r.trilha_id);
  }
  return map;
}

router.get('/', asyncHandler(async (req, res) => {
  const [rows] = await pool.query('SELECT * FROM colaboradores ORDER BY updated_at ASC');
  const trilhaMap = await loadTrilhaIdsMap();
  res.json({ docs: rows.map((r) => toApi(r, trilhaMap[r.id])) });
}));

router.post('/', asyncHandler(async (req, res) => {
  const id = genId();
  const nome = String(req.body.nome || '');
  const email = req.body.email !== undefined ? String(req.body.email) : '';
  const cargo = req.body.cargo !== undefined ? String(req.body.cargo) : '';
  const unidadeId = req.body.unidadeId ? String(req.body.unidadeId) : null;
  const gestor = req.body.gestor !== undefined ? String(req.body.gestor) : '';
  const dataAdmissao = req.body.dataAdmissao !== undefined ? String(req.body.dataAdmissao) : '';
  const lideranca = !!req.body.lideranca;
  const ativo = req.body.ativo !== undefined ? !!req.body.ativo : true;
  const nota = req.body.nota !== undefined ? String(req.body.nota) : '';
  await pool.query(
    `INSERT INTO colaboradores
      (id, nome, email, cargo, unidade_id, gestor, data_admissao, lideranca, ativo, nota)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
    [id, nome, email, cargo, unidadeId, gestor, dataAdmissao, lideranca, ativo, nota]
  );
  res.json(toApi({ id, nome, email, cargo, unidade_id: unidadeId, gestor, data_admissao: dataAdmissao, lideranca, ativo, nota }, []));
}));

router.put('/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  const [rows] = await pool.query('SELECT * FROM colaboradores WHERE id = ?', [id]);
  if (!rows.length) return res.status(404).json({ error: 'not_found' });
  const cur = rows[0];
  const b = req.body;
  const nome = b.nome !== undefined ? String(b.nome) : cur.nome;
  const email = b.email !== undefined ? String(b.email) : cur.email;
  const cargo = b.cargo !== undefined ? String(b.cargo) : cur.cargo;
  const unidadeId = b.unidadeId !== undefined ? (b.unidadeId ? String(b.unidadeId) : null) : cur.unidade_id;
  const gestor = b.gestor !== undefined ? String(b.gestor) : cur.gestor;
  const dataAdmissao = b.dataAdmissao !== undefined ? String(b.dataAdmissao) : cur.data_admissao;
  const lideranca = b.lideranca !== undefined ? !!b.lideranca : !!cur.lideranca;
  const ativo = b.ativo !== undefined ? !!b.ativo : !!cur.ativo;
  const nota = b.nota !== undefined ? String(b.nota) : cur.nota;
  await pool.query(
    `UPDATE colaboradores SET nome=?, email=?, cargo=?, unidade_id=?, gestor=?, data_admissao=?, lideranca=?, ativo=?, nota=? WHERE id=?`,
    [nome, email, cargo, unidadeId, gestor, dataAdmissao, lideranca, ativo, nota, id]
  );
  const trilhaMap = await loadTrilhaIdsMap();
  res.json(toApi({ id, nome, email, cargo, unidade_id: unidadeId, gestor, data_admissao: dataAdmissao, lideranca, ativo, nota }, trilhaMap[id]));
}));

router.delete('/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  await pool.query('DELETE FROM colaboradores WHERE id = ?', [id]);
  res.json({ ok: true });
}));

router.post('/:id/trilhas/:trilhaId', asyncHandler(async (req, res) => {
  const { id, trilhaId } = req.params;
  await pool.query(
    'INSERT IGNORE INTO colaborador_trilhas (colaborador_id, trilha_id) VALUES (?, ?)',
    [id, trilhaId]
  );
  res.json({ ok: true });
}));

router.delete('/:id/trilhas/:trilhaId', asyncHandler(async (req, res) => {
  const { id, trilhaId } = req.params;
  await pool.query(
    'DELETE FROM colaborador_trilhas WHERE colaborador_id = ? AND trilha_id = ?',
    [id, trilhaId]
  );
  res.json({ ok: true });
}));

module.exports = router;
