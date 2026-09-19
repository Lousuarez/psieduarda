// Exporta o estado atual do banco no mesmo formato "Por Colaborador" usado
// pra importar (uma linha por colaborador x módulo em que está matriculado),
// pra fechar o ciclo: dá pra reimportar esse mesmo arquivo depois de editado.
const express = require('express');
const XLSX = require('xlsx');
const { pool } = require('../db');
const asyncHandler = require('../asyncHandler');

const router = express.Router();

router.get('/', asyncHandler(async (req, res) => {
  const [unidades] = await pool.query('SELECT * FROM unidades');
  const [trilhas] = await pool.query('SELECT * FROM trilhas ORDER BY ordem');
  const [ciclos] = await pool.query('SELECT * FROM ciclos ORDER BY ordem');
  const [modulos] = await pool.query('SELECT * FROM modulos ORDER BY ordem');
  const [colaboradores] = await pool.query('SELECT * FROM colaboradores ORDER BY nome');
  const [matriculas] = await pool.query('SELECT * FROM colaborador_trilhas');
  const [progresso] = await pool.query('SELECT * FROM progresso');

  const unidadeById = Object.fromEntries(unidades.map((u) => [u.id, u]));
  const trilhaById = Object.fromEntries(trilhas.map((t) => [t.id, t]));
  const ciclosByTrilha = {};
  ciclos.forEach((ci) => { (ciclosByTrilha[ci.trilha_id] ||= []).push(ci); });
  const modulosByCiclo = {};
  modulos.forEach((m) => { (modulosByCiclo[m.ciclo_id] ||= []).push(m); });
  const trilhaIdsByColab = {};
  matriculas.forEach((mt) => { (trilhaIdsByColab[mt.colaborador_id] ||= []).push(mt.trilha_id); });
  const statusByColabModulo = {};
  progresso.forEach((p) => { statusByColabModulo[`${p.colaborador_id}|${p.modulo_id}`] = p.status; });

  const rows = [];
  for (const c of colaboradores) {
    const tids = trilhaIdsByColab[c.id] || [];
    for (const tid of tids) {
      const t = trilhaById[tid];
      if (!t) continue;
      for (const ci of ciclosByTrilha[tid] || []) {
        for (const m of modulosByCiclo[ci.id] || []) {
          rows.push({
            Colaborador: c.nome,
            Unidade: unidadeById[c.unidade_id]?.nome || '',
            Trilha: t.nome,
            Ciclo: ci.nome,
            'Status do colaborador': statusByColabModulo[`${c.id}|${m.id}`] || 'Não iniciado',
            Módulo: m.nome,
            Categoria: m.categoria,
            Descrição: m.descricao || '',
            Mentor: m.mentor || '',
            Formato: m.formato || '',
            'Público-alvo': m.publico_alvo || '',
            'Carga horária': m.carga_horaria || '',
            'Status do módulo': m.status,
            Ordem: m.ordem,
            'Link do material (PDF)': m.link_material || '',
            'Início previsto': m.inicio_previsto || '',
          });
        }
      }
    }
  }

  const wb = XLSX.utils.book_new();
  const ws = XLSX.utils.json_to_sheet(rows);
  XLSX.utils.book_append_sheet(wb, ws, 'Trilha - Por Colaborador');
  const buf = XLSX.write(wb, { type: 'buffer', bookType: 'xlsx' });

  const filename = `juntos-a-distancia-${new Date().toISOString().slice(0, 10)}.xlsx`;
  res.setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  res.setHeader('Content-Disposition', `attachment; filename="${filename}"`);
  res.send(buf);
}));

module.exports = router;
