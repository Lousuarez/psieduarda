// Espelha os cadastros do banco remoto (produção/homologação) para o banco
// local, preservando os IDs originais (mantém as relações intactas) e sem
// tocar em `users`/`auth_tokens` (login é por ambiente, não é sincronizado).
// Uso:
//   REMOTE_DB_HOST=... REMOTE_DB_PORT=... REMOTE_DB_USER=... REMOTE_DB_PASS=... REMOTE_DB_NAME=... \
//   node scripts/sync-from-remote.js
// O banco de destino (local) vem de DB_* em .env, como de costume.
require('dotenv').config();
const mysql = require('mysql2/promise');

const TABLES = ['unidades', 'trilhas', 'ciclos', 'modulos', 'colaboradores', 'colaborador_trilhas', 'progresso'];
// Ordem de exclusão respeitando FKs (filhos antes dos pais).
const DELETE_ORDER = ['progresso', 'colaborador_trilhas', 'colaboradores', 'modulos', 'ciclos', 'trilhas', 'unidades'];
// Ordem de inserção (pais antes dos filhos).
const INSERT_ORDER = ['unidades', 'trilhas', 'ciclos', 'modulos', 'colaboradores', 'colaborador_trilhas', 'progresso'];

async function main() {
  const remote = await mysql.createConnection({
    host: process.env.REMOTE_DB_HOST,
    port: process.env.REMOTE_DB_PORT ? Number(process.env.REMOTE_DB_PORT) : 3306,
    user: process.env.REMOTE_DB_USER,
    password: process.env.REMOTE_DB_PASS,
    database: process.env.REMOTE_DB_NAME,
  });
  console.log(`Lendo de ${process.env.REMOTE_DB_HOST}/${process.env.REMOTE_DB_NAME}`);

  const rowsByTable = {};
  for (const t of TABLES) {
    const [rows] = await remote.query(`SELECT * FROM ${t}`);
    rowsByTable[t] = rows;
    console.log(`  ${t}: ${rows.length} linhas`);
  }
  await remote.end();

  const local = await mysql.createConnection({
    host: process.env.DB_HOST,
    port: process.env.DB_PORT ? Number(process.env.DB_PORT) : 3306,
    user: process.env.DB_USER,
    password: process.env.DB_PASS,
    database: process.env.DB_NAME,
  });
  console.log(`Gravando em ${process.env.DB_HOST}/${process.env.DB_NAME}`);

  await local.beginTransaction();
  try {
    for (const t of DELETE_ORDER) {
      await local.query(`DELETE FROM ${t}`);
    }
    for (const t of INSERT_ORDER) {
      for (const row of rowsByTable[t]) {
        const cols = Object.keys(row);
        const placeholders = cols.map(() => '?').join(', ');
        const values = cols.map(c => row[c]);
        await local.query(`INSERT INTO ${t} (${cols.join(', ')}) VALUES (${placeholders})`, values);
      }
    }
    await local.commit();
    console.log('Sincronização concluída:', Object.fromEntries(TABLES.map(t => [t, rowsByTable[t].length])));
  } catch (err) {
    await local.rollback();
    console.error('Erro durante a sincronização, tudo desfeito (rollback):', err);
    process.exitCode = 1;
  } finally {
    await local.end();
  }
}

main();
