// Apaga todo o "cadastro" (trilhas, ciclos, módulos, colaboradores,
// matrículas e progresso), preservando unidades e usuários — usado antes de
// reimportar uma planilha atualizada via scripts/import-seed.js.
// Uso:
//   node scripts/wipe-cadastros.js            (usa DB_* de .env)
//   DB_HOST=... DB_PORT=... DB_USER=... DB_PASS=... DB_NAME=... node scripts/wipe-cadastros.js
require('dotenv').config();
const mysql = require('mysql2/promise');

async function main() {
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
    const counts = {};
    for (const table of ['progresso', 'colaborador_trilhas', 'colaboradores', 'modulos', 'ciclos', 'trilhas']) {
      const [result] = await conn.query(`DELETE FROM ${table}`);
      counts[table] = result.affectedRows;
    }
    await conn.commit();
    console.log('Limpeza concluída (unidades e usuarios preservados):');
    console.log(counts);
  } catch (err) {
    await conn.rollback();
    console.error('Erro durante a limpeza, tudo desfeito (rollback):', err);
    process.exitCode = 1;
  } finally {
    await conn.end();
  }
}

main();
