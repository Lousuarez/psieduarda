// Apaga toda a estrutura de trilhas/ciclos/módulos (e tudo que depende dela:
// cronograma, frequência/chamadas, matrículas e progresso) e reimporta a
// partir de uma planilha no formato "Trilha_2.xlsx" (colunas: Trilha, Ciclo,
// Módulo, Mês, Descrição, Categoria, Mentor, Formato, Público alvo, Status do
// Módulo, Ordem no ciclo). Preserva colaboradores, unidades e usuários.
//
// Uso:
//   node scripts/rebuild-trilhas.js <planilha.xlsx>            (usa DB_* de .env, banco local)
//   DB_HOST=... DB_PORT=... DB_USER=... DB_PASS=... DB_NAME=... node scripts/rebuild-trilhas.js <planilha.xlsx>
//
// Regras combinadas com o usuário para esta importação:
// - Categoria fora do enum (Liderança/Método/Liderança e Método/Autoconhecimento/
//   Inovação) cai para "Liderança".
// - Status fora do enum (A iniciar/Em andamento/Concluído) cai para "Em
//   andamento" — caso real: linha 8 da planilha tem "Múltiplos — ver descrição".
// - "Mês" vira o campo "etapa" do módulo, formatado como "Mês N".
// - "Ordem no ciclo" vira a "ordem" do módulo (ordenação dentro do ciclo).
// - Ordem de trilhas/ciclos = ordem de primeira aparição na planilha.
require('dotenv').config();
const crypto = require('crypto');
const mysql = require('mysql2/promise');
const XLSX = require('xlsx');

function genId() {
  return crypto.randomBytes(9).toString('hex');
}

const CICLO_ICON = {
  'Liderar a Si Mesmo': 'bulb',
  'Liderar Pessoas': 'users',
  'Liderar a Operação': 'hardhat',
  'Liderar o Futuro': 'compass',
};
const VALID_CATEGORIAS = new Set(['Liderança', 'Método', 'Liderança e Método', 'Autoconhecimento', 'Inovação']);
const VALID_STATUS = new Set(['A iniciar', 'Em andamento', 'Concluído']);
const STATUS_FALLBACK = 'Em andamento';

const WIPE_TABLES_ORDER = [
  'modulo_etapa_chamada_versao_presenca',
  'modulo_etapa_chamada_versao',
  'modulo_etapa_chamada',
  'modulo_etapa_frequencia',
  'progresso_historico',
  'progresso',
  'colaborador_trilhas',
  'modulo_etapas',
  'modulos',
  'ciclos',
  'trilhas',
];

function lerPlanilha(filePath) {
  const wb = XLSX.readFile(filePath);
  const ws = wb.Sheets[wb.SheetNames[0]];
  const rows = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });
  const header = rows[0];
  const idx = {};
  header.forEach((h, i) => { idx[h] = i; });
  const data = rows.slice(1).filter((r) => r.some((c) => String(c).trim() !== ''));

  const trilhas = new Map();
  const ciclos = new Map();
  const modulos = [];
  const avisos = [];
  let trilhaOrdem = 0;
  let cicloOrdem = 0;

  for (const r of data) {
    const trilhaNome = String(r[idx['Trilha']] || '').trim();
    const cicloNome = String(r[idx['Ciclo']] || '').trim();
    const moduloNome = String(r[idx['Módulo']] || '').trim();
    if (!trilhaNome || !cicloNome || !moduloNome) continue;

    if (!trilhas.has(trilhaNome)) trilhas.set(trilhaNome, { nome: trilhaNome, ordem: trilhaOrdem++ });

    const cicloKey = `${trilhaNome}|${cicloNome}`;
    if (!ciclos.has(cicloKey)) {
      ciclos.set(cicloKey, { trilhaNome, nome: cicloNome, ordem: cicloOrdem++, icon: CICLO_ICON[cicloNome] || 'layers' });
    }

    const mes = r[idx['Mês']];
    let categoria = String(r[idx['Categoria']] || '').trim();
    if (!VALID_CATEGORIAS.has(categoria)) {
      avisos.push(`Módulo "${moduloNome}": categoria "${categoria}" inválida, gravada como "Liderança".`);
      categoria = 'Liderança';
    }
    let status = String(r[idx['Status do Módulo']] || '').trim();
    if (!VALID_STATUS.has(status)) {
      avisos.push(`Módulo "${moduloNome}": status "${status}" inválido, gravado como "${STATUS_FALLBACK}".`);
      status = STATUS_FALLBACK;
    }

    modulos.push({
      cicloKey,
      nome: moduloNome,
      etapa: mes !== '' && mes !== undefined && mes !== null ? `Mês ${mes}` : '',
      ordem: Number(r[idx['Ordem no ciclo']]) || 0,
      categoria,
      status,
      descricao: String(r[idx['Descrição']] || ''),
      mentor: String(r[idx['Mentor']] || ''),
      formato: String(r[idx['Formato']] || ''),
      publicoAlvo: String(r[idx['Público alvo']] || ''),
    });
  }

  return { trilhas, ciclos, modulos, avisos };
}

async function wipe(conn) {
  const counts = {};
  for (const table of WIPE_TABLES_ORDER) {
    const [result] = await conn.query(`DELETE FROM ${table}`);
    counts[table] = result.affectedRows;
  }
  return counts;
}

async function importar(conn, parsed) {
  const { trilhas, ciclos, modulos } = parsed;

  const trilhaIdByNome = new Map();
  for (const [nome, t] of trilhas) {
    const id = genId();
    await conn.query('INSERT INTO trilhas (id, nome, ordem) VALUES (?, ?, ?)', [id, nome, t.ordem]);
    trilhaIdByNome.set(nome, id);
  }

  const cicloIdByKey = new Map();
  for (const [key, c] of ciclos) {
    const id = genId();
    await conn.query(
      'INSERT INTO ciclos (id, trilha_id, nome, ordem, icon) VALUES (?, ?, ?, ?, ?)',
      [id, trilhaIdByNome.get(c.trilhaNome), c.nome, c.ordem, c.icon]
    );
    cicloIdByKey.set(key, id);
  }

  for (const m of modulos) {
    const id = genId();
    await conn.query(
      `INSERT INTO modulos (id, ciclo_id, nome, etapa, ordem, categoria, status, descricao, mentor, formato, publico_alvo)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [id, cicloIdByKey.get(m.cicloKey), m.nome, m.etapa, m.ordem, m.categoria, m.status, m.descricao, m.mentor, m.formato, m.publicoAlvo]
    );
  }

  return { trilhas: trilhas.size, ciclos: ciclos.size, modulos: modulos.length };
}

async function main() {
  const filePath = process.argv[2];
  if (!filePath) {
    console.error('Uso: node scripts/rebuild-trilhas.js <planilha.xlsx>');
    process.exit(1);
  }
  const parsed = lerPlanilha(filePath);
  if (parsed.avisos.length) {
    console.log('Avisos de conversão:');
    parsed.avisos.forEach((a) => console.log(' -', a));
  }

  // Usa REMOTE_DB_* se estiver definido (mesma convenção de
  // scripts/sync-from-remote.js), senão cai pro DB_* de .env (banco local).
  const host = process.env.REMOTE_DB_HOST || process.env.DB_HOST;
  const port = process.env.REMOTE_DB_PORT || process.env.DB_PORT;
  const user = process.env.REMOTE_DB_USER || process.env.DB_USER;
  const password = process.env.REMOTE_DB_PASS || process.env.DB_PASS;
  const database = process.env.REMOTE_DB_NAME || process.env.DB_NAME;

  const conn = await mysql.createConnection({
    host, port: port ? Number(port) : 3306, user, password, database,
  });
  console.log(`Conectado em ${host}/${database}`);

  // A alteração do ENUM de categoria (Autoconhecimento/Inovação) precisa
  // rodar fora da transação — ALTER TABLE no MySQL faz commit implícito.
  await conn.query(
    "ALTER TABLE modulos MODIFY COLUMN categoria ENUM('Liderança','Método','Liderança e Método','Autoconhecimento','Inovação') NOT NULL DEFAULT 'Liderança'"
  );
  console.log('ENUM de categoria conferido/atualizado.');

  await conn.beginTransaction();
  try {
    const wiped = await wipe(conn);
    console.log('Apagado:', wiped);
    const imported = await importar(conn, parsed);
    await conn.commit();
    console.log('Importado:', imported);
  } catch (err) {
    await conn.rollback();
    console.error('Erro durante a operação, tudo desfeito (rollback):', err);
    process.exitCode = 1;
  } finally {
    await conn.end();
  }
}

main();
