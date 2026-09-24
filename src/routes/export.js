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
  const [temas] = await pool.query('SELECT * FROM temas ORDER BY ordem');
  const [modulos] = await pool.query('SELECT * FROM modulos ORDER BY ordem');
  const [colaboradores] = await pool.query('SELECT * FROM colaboradores ORDER BY nome');
  const [matriculas] = await pool.query('SELECT * FROM colaborador_trilhas');
  const [progresso] = await pool.query('SELECT * FROM progresso');

  const unidadeById = Object.fromEntries(unidades.map((u) => [u.id, u]));
  const trilhaById = Object.fromEntries(trilhas.map((t) => [t.id, t]));
  const ciclosByTrilha = {};
  ciclos.forEach((ci) => { (ciclosByTrilha[ci.trilha_id] ||= []).push(ci); });
  const temasByCiclo = {};
  temas.forEach((te) => { (temasByCiclo[te.ciclo_id] ||= []).push(te); });
  const modulosByTema = {};
  modulos.forEach((m) => { (modulosByTema[m.tema_id] ||= []).push(m); });
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
        for (const te of temasByCiclo[ci.id] || []) {
          for (const m of modulosByTema[te.id] || []) {
            rows.push({
              Colaborador: c.nome,
              Unidade: unidadeById[c.unidade_id]?.nome || '',
              Trilha: t.nome,
              Ciclo: ci.nome,
              Tema: te.titulo,
              'Status do colaborador': statusByColabModulo[`${c.id}|${m.id}`] || 'Não iniciado',
              Módulo: m.nome,
              Categoria: m.categoria,
              Descrição: m.descricao || '',
              Mentor: m.mentor || '',
              Formato: m.formato || '',
              'Público-alvo': m.publico_alvo || '',
              'Carga horária': m.carga_horaria ?? '',
              'Status do módulo': m.status,
              Ordem: m.ordem,
              'Link do material (PDF)': m.link_material || '',
              'Início previsto': m.inicio_previsto || '',
            });
          }
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

// Exporta o histórico completo de frequência (todas as versões de todas as
// chamadas já fechadas) — uma linha por colaborador x versão, pra dar pra
// auditar exatamente o que mudou entre um fechamento e uma edição posterior.
router.get('/frequencia', asyncHandler(async (req, res) => {
  const [versoes] = await pool.query(
    `SELECT v.*, e.nome AS etapa_nome, e.data_inicio, e.modulo_id
     FROM modulo_etapa_chamada_versao v
     JOIN modulo_etapas e ON e.id = v.etapa_id
     ORDER BY e.modulo_id, v.etapa_id, v.versao`
  );
  if (!versoes.length) {
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.json_to_sheet([]);
    XLSX.utils.book_append_sheet(wb, ws, 'Frequência');
    const buf = XLSX.write(wb, { type: 'buffer', bookType: 'xlsx' });
    res.setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    res.setHeader('Content-Disposition', 'attachment; filename="frequencia.xlsx"');
    return res.send(buf);
  }

  const [presencas] = await pool.query('SELECT * FROM modulo_etapa_chamada_versao_presenca');
  const presencasByVersao = {};
  presencas.forEach((p) => { (presencasByVersao[p.versao_id] ||= []).push(p); });

  const [modulos] = await pool.query('SELECT * FROM modulos');
  const [temas] = await pool.query('SELECT * FROM temas');
  const [ciclos] = await pool.query('SELECT * FROM ciclos');
  const [trilhas] = await pool.query('SELECT * FROM trilhas');
  const [colaboradores] = await pool.query('SELECT * FROM colaboradores');
  const moduloById = Object.fromEntries(modulos.map((m) => [m.id, m]));
  const temaById = Object.fromEntries(temas.map((te) => [te.id, te]));
  const cicloById = Object.fromEntries(ciclos.map((c) => [c.id, c]));
  const trilhaById = Object.fromEntries(trilhas.map((t) => [t.id, t]));
  const colabById = Object.fromEntries(colaboradores.map((c) => [c.id, c]));

  const rows = [];
  for (const v of versoes) {
    const m = moduloById[v.modulo_id];
    const te = m ? temaById[m.tema_id] : null;
    const ci = te ? cicloById[te.ciclo_id] : null;
    const t = ci ? trilhaById[ci.trilha_id] : null;
    const lista = presencasByVersao[v.id] || [];
    for (const p of lista) {
      const c = colabById[p.colaborador_id];
      rows.push({
        Trilha: t?.nome || '',
        Ciclo: ci?.nome || '',
        Tema: te?.titulo || '',
        Módulo: m?.nome || '',
        Etapa: v.etapa_nome,
        'Data da etapa': v.data_inicio ? new Date(v.data_inicio).toLocaleDateString('pt-BR') : '',
        Versão: v.versao,
        Motivo: v.motivo === 'fechamento' ? 'Fechamento original' : 'Edição',
        'Registrada em': v.criada_em ? new Date(v.criada_em).toLocaleString('pt-BR') : '',
        'Registrada por': v.criada_por || '',
        Colaborador: c?.nome || '',
        Presente: p.presente ? 'Sim' : 'Não',
      });
    }
  }

  const wb = XLSX.utils.book_new();
  const ws = XLSX.utils.json_to_sheet(rows);
  XLSX.utils.book_append_sheet(wb, ws, 'Frequência');
  const buf = XLSX.write(wb, { type: 'buffer', bookType: 'xlsx' });

  const filename = `frequencia-${new Date().toISOString().slice(0, 10)}.xlsx`;
  res.setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  res.setHeader('Content-Disposition', `attachment; filename="${filename}"`);
  res.send(buf);
}));

module.exports = router;
