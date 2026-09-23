const express = require('express');
const { pool, genId } = require('../db');
const asyncHandler = require('../asyncHandler');
const { logAudit } = require('../audit');

const router = express.Router();

function toApi(row) {
  return {
    id: row.id,
    trilhaId: row.trilha_id,
    nome: row.nome,
    tema: row.tema || '',
    ordem: row.ordem,
    icon: row.icon,
    transversal: !!row.transversal,
  };
}

router.get('/', asyncHandler(async (req, res) => {
  const [rows] = await pool.query('SELECT * FROM ciclos ORDER BY updated_at ASC');
  res.json({ docs: rows.map(toApi) });
}));

router.post('/', asyncHandler(async (req, res) => {
  const id = genId();
  const trilhaId = String(req.body.trilhaId || '');
  const nome = String(req.body.nome || '');
  const tema = req.body.tema !== undefined ? String(req.body.tema) : '';
  const ordem = Number.isFinite(Number(req.body.ordem)) ? Number(req.body.ordem) : 0;
  const icon = req.body.icon !== undefined ? String(req.body.icon) : 'layers';
  const transversal = !!req.body.transversal;
  await pool.query(
    'INSERT INTO ciclos (id, trilha_id, nome, tema, ordem, icon, transversal) VALUES (?, ?, ?, ?, ?, ?, ?)',
    [id, trilhaId, nome, tema, ordem, icon, transversal]
  );
  const depois = { id, trilhaId, nome, tema, ordem, icon, transversal };
  await logAudit({ entidade: 'ciclo', entidadeId: id, acao: 'create', antes: null, depois, req });
  res.json(depois);
}));

router.put('/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  const [rows] = await pool.query('SELECT * FROM ciclos WHERE id = ?', [id]);
  if (!rows.length) return res.status(404).json({ error: 'not_found' });
  const cur = rows[0];
  const antes = toApi(cur);
  const trilhaId = req.body.trilhaId !== undefined ? String(req.body.trilhaId) : cur.trilha_id;
  const nome = req.body.nome !== undefined ? String(req.body.nome) : cur.nome;
  const tema = req.body.tema !== undefined ? String(req.body.tema) : cur.tema;
  const ordem = req.body.ordem !== undefined ? Number(req.body.ordem) : cur.ordem;
  const icon = req.body.icon !== undefined ? String(req.body.icon) : cur.icon;
  const transversal = req.body.transversal !== undefined ? !!req.body.transversal : !!cur.transversal;
  await pool.query(
    'UPDATE ciclos SET trilha_id = ?, nome = ?, tema = ?, ordem = ?, icon = ?, transversal = ? WHERE id = ?',
    [trilhaId, nome, tema, ordem, icon, transversal, id]
  );
  const depois = { id, trilhaId, nome, tema, ordem, icon, transversal };
  await logAudit({ entidade: 'ciclo', entidadeId: id, acao: 'update', antes, depois, req });
  res.json(depois);
}));

router.delete('/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  try {
    const [rows] = await pool.query('SELECT * FROM ciclos WHERE id = ?', [id]);
    await pool.query('DELETE FROM ciclos WHERE id = ?', [id]);
    if (rows.length) await logAudit({ entidade: 'ciclo', entidadeId: id, acao: 'delete', antes: toApi(rows[0]), depois: null, req });
    res.json({ ok: true });
  } catch (err) {
    if (err && err.code === 'ER_ROW_IS_REFERENCED_2') {
      return res.status(409).json({ error: 'has_dependents' });
    }
    throw err;
  }
}));

module.exports = router;
