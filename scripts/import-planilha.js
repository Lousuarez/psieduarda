// Importa a planilha "Trilha de desenvolvimento - Por Colaborador.xlsx" (aba
// "Trilha - Por Colaborador") para as tabelas normalizadas atuais, preservando
// unidades e usuários já cadastrados. Uso:
//   node scripts/import-planilha.js <planilha.xlsx>
// Lê DB_HOST/DB_PORT/DB_USER/DB_PASS/DB_NAME de .env (ou do ambiente).
//
// Regras de negócio combinadas com o usuário pra esta importação:
// - "Status do colaborador" = "Pendente" não grava linha em progresso — ausência
//   de linha já representa "Não iniciado" (mesma convenção do resto do app).
// - Não existe coluna "Etapa" na planilha; fica em branco (sem "Mês N").
// - Módulos sem "Ordem" preenchida recebem ordem sequencial, na ordem em que
//   aparecem na planilha, depois do maior valor de Ordem já usado.
// - Colaboradores só têm o nome na planilha: todos entram com unidade "Ponta
//   Grossa" (criada se não existir), lideranca=true, ativo=true, email em branco.
// - Nomes duplicados/grafias divergentes da mesma pessoa são unificados via
//   CANON_COLABORADOR antes de gravar.
// - Linhas com Colaborador "(a definir)" são ignoradas (os módulos que elas
//   referenciam já são criados normalmente a partir de outras linhas).
// - Quando a mesma pessoa aparece 2x pro mesmo módulo com status diferente,
//   prevalece "Concluído"; com o mesmo status, a repetição é ignorada.
require('dotenv').config();
const crypto = require('crypto');
const mysql = require('mysql2/promise');
const XLSX = require('xlsx');

const CANON_COLABORADOR = {
  'Tiago Czlusniak': 'Tiago Czelusniak',
  'Gabriel Correa': 'Gabriel Correa da Silva',
  'Igor Andreotti': 'Igor Augusto Andreotti dos Santos',
  'João Carlos': 'João Carlos da Silva Junior',
  'Leonardo Chafas': 'Leonardo Ravaneli Chagas',
  'Leonardo Chagas': 'Leonardo Ravaneli Chagas',
  'Murilo Dropa': 'Murilo Fortunato Dropa',
  'Nicolli Souza': 'Nicolli Cunningham de Souza',
  'Rircham Araujo': 'Rircham Augusto Veronez de Araujo',
  'Denis Souza': 'Denis Willian Silva de Souza',
};
const UNIDADE_NOME = 'Ponta Grossa';
const STATUS_PRIORITY = { 'Em andamento': 1, 'Concluído': 2 };

function genId() {
  return crypto.randomBytes(9).toString('hex');
}
function canon(nome) {
  return CANON_COLABORADOR[nome] || nome;
}

function lerPlanilha(filePath) {
  const wb = XLSX.readFile(filePath);
  const ws = wb.Sheets['Trilha - Por Colaborador'];
  if (!ws) throw new Error('Aba "Trilha - Por Colaborador" não encontrada na planilha.');
  const rows = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });
  const header = rows[0];
  const idx = {};
  header.forEach((h, i) => { idx[h] = i; });
  const data = rows.slice(1).filter(r => r.some(c => String(c).trim() !== ''));

  const trilhas = new Map();     // nome -> {nome}
  const ciclos = new Map();      // "trilha|ciclo" -> {trilhaNome, nome}
  const modulos = new Map();     // "trilha|ciclo|modulo" -> {...}
  const moduloOrdemAppearance = [];
  const colaboradores = new Set();
  const matriculas = new Set();  // "colab||trilha"
  const progresso = new Map();   // "colab||trilha|ciclo|modulo" -> status
  let placeholdersIgnorados = 0;

  for (const r of data) {
    const trilhaNome = String(r[idx['Trilha']]).trim();
    const cicloNome = String(r[idx['Ciclo']]).trim();
    const moduloNome = String(r[idx['Módulo']]).trim();
    if (!trilhaNome || !cicloNome || !moduloNome) continue;

    if (!trilhas.has(trilhaNome)) trilhas.set(trilhaNome, { nome: trilhaNome });

    const cicloKey = `${trilhaNome}|${cicloNome}`;
    if (!ciclos.has(cicloKey)) ciclos.set(cicloKey, { trilhaNome, nome: cicloNome });

    const moduloKey = `${cicloKey}|${moduloNome}`;
    if (!modulos.has(moduloKey)) {
      modulos.set(moduloKey, {
        cicloKey,
        nome: moduloNome,
        categoria: r[idx['Categoria']] || 'Liderança',
        descricao: r[idx['Descrição']] || '',
        mentor: r[idx['Mentor']] || '',
        formato: r[idx['Formato']] || '',
        publicoAlvo: r[idx['Público-alvo']] || '',
        cargaHoraria: String(r[idx['Carga horária']] ?? ''),
        status: r[idx['Status do módulo']] || 'A iniciar',
        ordem: r[idx['Ordem']],
        linkMaterial: r[idx['Link do material (PDF)']] || '',
        inicioPrevisto: String(r[idx['Início previsto']] ?? ''),
      });
      moduloOrdemAppearance.push(moduloKey);
    }

    const rawColab = String(r[idx['Colaborador']]).trim();
    if (!rawColab || rawColab === '(a definir)') { placeholdersIgnorados++; continue; }
    const colabNome = canon(rawColab);
    colaboradores.add(colabNome);
    matriculas.add(`${colabNome}||${trilhaNome}`);

    const statusColab = String(r[idx['Status do colaborador']]).trim();
    if (statusColab !== 'Em andamento' && statusColab !== 'Concluído') continue; // "Pendente"/vazio = não iniciado (sem linha)

    const progKey = `${colabNome}||${moduloKey}`;
    const atual = progresso.get(progKey);
    if (!atual || STATUS_PRIORITY[statusColab] > STATUS_PRIORITY[atual]) {
      progresso.set(progKey, statusColab);
    }
  }

  // Ordem sequencial pros módulos sem "Ordem" preenchida, na ordem de aparição.
  let maxOrdem = 0;
  for (const m of modulos.values()) {
    const n = Number(m.ordem);
    if (Number.isFinite(n) && n > maxOrdem) maxOrdem = n;
  }
  for (const key of moduloOrdemAppearance) {
    const m = modulos.get(key);
    const n = Number(m.ordem);
    m.ordem = Number.isFinite(n) && m.ordem !== '' ? n : (maxOrdem += 1);
  }

  return { trilhas, ciclos, modulos, colaboradores, matriculas, progresso, placeholdersIgnorados };
}

async function gravar(conn, parsed) {
  const { trilhas, ciclos, modulos, colaboradores, matriculas, progresso, placeholdersIgnorados } = parsed;

  let unidadeId;
  const [uRows] = await conn.query('SELECT id FROM unidades WHERE nome = ?', [UNIDADE_NOME]);
  if (uRows.length) {
    unidadeId = uRows[0].id;
  } else {
    unidadeId = genId();
    await conn.query('INSERT INTO unidades (id, nome) VALUES (?, ?)', [unidadeId, UNIDADE_NOME]);
  }

  const trilhaIdByNome = new Map();
  let ordemTrilha = 0;
  for (const nome of trilhas.keys()) {
    const id = genId();
    await conn.query('INSERT INTO trilhas (id, nome, ordem) VALUES (?, ?, ?)', [id, nome, ordemTrilha++]);
    trilhaIdByNome.set(nome, id);
  }

  const cicloIdByKey = new Map();
  let ordemCiclo = 0;
  for (const [key, c] of ciclos) {
    const id = genId();
    await conn.query('INSERT INTO ciclos (id, trilha_id, nome, ordem) VALUES (?, ?, ?, ?)', [id, trilhaIdByNome.get(c.trilhaNome), c.nome, ordemCiclo++]);
    cicloIdByKey.set(key, id);
  }

  const moduloIdByKey = new Map();
  for (const [key, m] of modulos) {
    const id = genId();
    await conn.query(
      `INSERT INTO modulos (id, ciclo_id, nome, ordem, categoria, status, descricao, mentor, formato, publico_alvo, carga_horaria, inicio_previsto, link_material)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [id, cicloIdByKey.get(m.cicloKey), m.nome, m.ordem, m.categoria, m.status, m.descricao, m.mentor, m.formato, m.publicoAlvo, m.cargaHoraria, m.inicioPrevisto, m.linkMaterial]
    );
    moduloIdByKey.set(key, id);
  }

  const colabIdByNome = new Map();
  for (const nome of colaboradores) {
    const id = genId();
    await conn.query('INSERT INTO colaboradores (id, nome, unidade_id, lideranca, ativo) VALUES (?, ?, ?, TRUE, TRUE)', [id, nome, unidadeId]);
    colabIdByNome.set(nome, id);
  }

  for (const key of matriculas) {
    const [colabNome, trilhaNome] = key.split('||');
    await conn.query('INSERT IGNORE INTO colaborador_trilhas (colaborador_id, trilha_id) VALUES (?, ?)', [colabIdByNome.get(colabNome), trilhaIdByNome.get(trilhaNome)]);
  }

  for (const [key, status] of progresso) {
    const [colabNome, moduloKey] = key.split('||');
    await conn.query('INSERT INTO progresso (colaborador_id, modulo_id, status) VALUES (?, ?, ?)', [colabIdByNome.get(colabNome), moduloIdByKey.get(moduloKey), status]);
  }

  return {
    unidade: UNIDADE_NOME,
    trilhas: trilhas.size,
    ciclos: ciclos.size,
    modulos: modulos.size,
    colaboradores: colaboradores.size,
    matriculas: matriculas.size,
    progresso: progresso.size,
    linhasComColaboradorADefinirIgnoradas: placeholdersIgnorados,
  };
}

async function main() {
  const filePath = process.argv[2];
  if (!filePath) {
    console.error('Uso: node scripts/import-planilha.js <planilha.xlsx>');
    process.exit(1);
  }

  const parsed = lerPlanilha(filePath);

  const conn = await mysql.createConnection({
    host: process.env.DB_HOST,
    port: process.env.DB_PORT ? Number(process.env.DB_PORT) : 3306,
    user: process.env.DB_USER,
    password: process.env.DB_PASS,
    database: process.env.DB_NAME,
  });
  console.log(`Conectado em ${process.env.DB_HOST}/${process.env.DB_NAME}`);

  await conn.beginTransaction();
  try {
    const resumo = await gravar(conn, parsed);
    await conn.commit();
    console.log('Importação concluída:');
    console.log(resumo);
  } catch (err) {
    await conn.rollback();
    console.error('Erro durante a importação, tudo desfeito (rollback):', err);
    process.exitCode = 1;
  } finally {
    await conn.end();
  }
}

main();
