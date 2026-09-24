// Apaga toda a estrutura de trilhas/ciclos/temas/módulos (e tudo que depende
// dela: cronograma, frequência/chamadas, matrículas e progresso) e reimporta
// a partir de uma planilha no novo formato com a coluna "Tema" (colunas:
// Trilha, Ciclo, Tema, Mês, Módulo, Descrição, Categoria, Mentor, Formato,
// Público alvo, Status do Módulo, Ordem no ciclo). Preserva colaboradores,
// unidades e usuários.
//
// Pressupõe que o schema já está no formato novo (modulos.tema_id, tabela
// "temas") — rode scripts/reset-temas-schema.js antes, se ainda não rodou.
//
// Uso:
//   node scripts/import-trilha-temas.js <planilha.xlsx>            (usa DB_* de .env, banco local)
//   REMOTE_DB_HOST=... REMOTE_DB_PORT=... REMOTE_DB_USER=... REMOTE_DB_PASS=... REMOTE_DB_NAME=... node scripts/import-trilha-temas.js <planilha.xlsx>
//
// Regras de conversão:
// - Categoria fora do enum (Liderança/Método/Liderança e Método/Autoconhecimento/
//   Inovação) cai para "Liderança".
// - Status fora do enum (A iniciar/Em andamento/Concluído) cai para "Em andamento".
// - "Mês" do Tema = valor da primeira linha em que aquele Tema aparece
//   (formatado "Mês N") — algumas planilhas variam o Mês entre módulos de um
//   mesmo tema; o tema fica com o mês da primeira ocorrência.
// - "Ordem no ciclo" vira a "ordem" do módulo (ordenação dentro do tema).
// - Ordem de trilhas/ciclos/temas = ordem de primeira aparição na planilha.
// - Ícone do tema = ícone padrão do ciclo (mesmo mapeamento usado antes por
//   ciclo); dá pra trocar por módulo/tema depois pelo cadastro.
// - Carga horária do módulo não vem na planilha — fica em branco (o usuário
//   preenche depois pelo cadastro; a carga horária do tema é a soma).
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
  'temas',
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
  const temas = new Map();
  const modulos = [];
  const avisos = [];
  let trilhaOrdem = 0;
  let cicloOrdem = 0;
  let temaOrdem = 0;

  for (const r of data) {
    const trilhaNome = String(r[idx['Trilha']] || '').trim();
    const cicloNome = String(r[idx['Ciclo']] || '').trim();
    const temaTitulo = String(r[idx['Tema']] || '').trim();
    const moduloNome = String(r[idx['Módulo']] || '').trim();
    if (!trilhaNome || !cicloNome || !temaTitulo || !moduloNome) continue;

    if (!trilhas.has(trilhaNome)) trilhas.set(trilhaNome, { nome: trilhaNome, ordem: trilhaOrdem++ });

    const cicloKey = `${trilhaNome}|${cicloNome}`;
    if (!ciclos.has(cicloKey)) {
      ciclos.set(cicloKey, { trilhaNome, nome: cicloNome, ordem: cicloOrdem++, icon: CICLO_ICON[cicloNome] || 'layers' });
    }

    const mes = r[idx['Mês']];
    const temaKey = `${cicloKey}|${temaTitulo}`;
    if (!temas.has(temaKey)) {
      temas.set(temaKey, {
        cicloKey,
        titulo: temaTitulo,
        ordem: temaOrdem++,
        mes: mes !== '' && mes !== undefined && mes !== null ? `Mês ${mes}` : '',
        icon: CICLO_ICON[cicloNome] || 'layers',
      });
    }

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
      temaKey,
      nome: moduloNome,
      ordem: Number(r[idx['Ordem no ciclo']]) || 0,
      categoria,
      status,
      descricao: String(r[idx['Descrição']] || ''),
      mentor: String(r[idx['Mentor']] || ''),
      formato: String(r[idx['Formato']] || ''),
      publicoAlvo: String(r[idx['Público alvo']] || ''),
    });
  }

  return { trilhas, ciclos, temas, modulos, avisos };
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
  const { trilhas, ciclos, temas, modulos } = parsed;

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

  const temaIdByKey = new Map();
  for (const [key, t] of temas) {
    const id = genId();
    await conn.query(
      'INSERT INTO temas (id, ciclo_id, titulo, mes, ordem, icon) VALUES (?, ?, ?, ?, ?, ?)',
      [id, cicloIdByKey.get(t.cicloKey), t.titulo, t.mes, t.ordem, t.icon]
    );
    temaIdByKey.set(key, id);
  }

  for (const m of modulos) {
    const id = genId();
    await conn.query(
      `INSERT INTO modulos (id, tema_id, nome, ordem, categoria, status, descricao, mentor, formato, publico_alvo)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [id, temaIdByKey.get(m.temaKey), m.nome, m.ordem, m.categoria, m.status, m.descricao, m.mentor, m.formato, m.publicoAlvo]
    );
  }

  return { trilhas: trilhas.size, ciclos: ciclos.size, temas: temas.size, modulos: modulos.length };
}

async function main() {
  const filePath = process.argv[2];
  if (!filePath) {
    console.error('Uso: node scripts/import-trilha-temas.js <planilha.xlsx>');
    process.exit(1);
  }
  const parsed = lerPlanilha(filePath);
  if (parsed.avisos.length) {
    console.log('Avisos de conversão:');
    parsed.avisos.forEach((a) => console.log(' -', a));
  }

  const host = process.env.REMOTE_DB_HOST || process.env.DB_HOST;
  const port = process.env.REMOTE_DB_PORT || process.env.DB_PORT;
  const user = process.env.REMOTE_DB_USER || process.env.DB_USER;
  const password = process.env.REMOTE_DB_PASS || process.env.DB_PASS;
  const database = process.env.REMOTE_DB_NAME || process.env.DB_NAME;

  const conn = await mysql.createConnection({
    host, port: port ? Number(port) : 3306, user, password, database,
  });
  console.log(`Conectado em ${host}/${database}`);

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
