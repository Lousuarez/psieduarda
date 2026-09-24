// Migração destrutiva pra introduzir a entidade Tema (Trilha > Ciclo > Tema >
// Módulo, era Trilha > Ciclo > Módulo). Apaga toda a estrutura de
// trilhas/ciclos/temas/módulos (e tudo que depende dela: cronograma,
// frequência/chamadas, matrículas e progresso), preservando colaboradores,
// unidades e usuários, e reestrutura a tabela "modulos":
//   - ciclo_id (+ icon/imagem, que voltaram a não existir em módulo) sai
//   - tema_id entra (FK pra "temas", tabela nova)
//   - carga_horaria vira numérica (DECIMAL) — antes era texto livre
//
// Uso:
//   node scripts/reset-temas-schema.js                    (usa DB_* de .env, banco local)
//   REMOTE_DB_HOST=... REMOTE_DB_PORT=... REMOTE_DB_USER=... REMOTE_DB_PASS=... REMOTE_DB_NAME=... node scripts/reset-temas-schema.js
require('dotenv').config();
const mysql = require('mysql2/promise');

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

async function main() {
  const host = process.env.REMOTE_DB_HOST || process.env.DB_HOST;
  const port = process.env.REMOTE_DB_PORT || process.env.DB_PORT;
  const user = process.env.REMOTE_DB_USER || process.env.DB_USER;
  const password = process.env.REMOTE_DB_PASS || process.env.DB_PASS;
  const database = process.env.REMOTE_DB_NAME || process.env.DB_NAME;

  const conn = await mysql.createConnection({ host, port: port ? Number(port) : 3306, user, password, database });
  console.log(`Conectado em ${host}/${database}`);

  // Cria "temas" se ainda não existir (schema.sql também cria, mas essa
  // ordem garante que a tabela exista antes de recriar o FK de modulos).
  await conn.query(`
    CREATE TABLE IF NOT EXISTS temas (
      id CHAR(18) PRIMARY KEY,
      ciclo_id CHAR(18) NOT NULL,
      titulo VARCHAR(191) NOT NULL,
      descricao TEXT,
      mes VARCHAR(191),
      formato VARCHAR(191),
      icon VARCHAR(32) NOT NULL DEFAULT 'layers',
      imagem MEDIUMTEXT,
      ordem INT NOT NULL DEFAULT 0,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (ciclo_id) REFERENCES ciclos(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  `);
  console.log('Tabela "temas" OK.');

  console.log('Apagando estrutura de trilhas/ciclos/temas/módulos (preservando colaboradores/unidades/usuários)...');
  const counts = {};
  for (const table of WIPE_TABLES_ORDER) {
    const [result] = await conn.query(`DELETE FROM ${table}`);
    counts[table] = result.affectedRows;
  }
  console.log('Apagado:', counts);

  // ALTER TABLE faz commit implícito no MySQL/MariaDB — roda fora de transação.
  const [cols] = await conn.query(
    `SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'modulos'`,
    [database]
  );
  const colNames = new Set(cols.map((c) => c.COLUMN_NAME));

  if (colNames.has('ciclo_id')) {
    // Precisa achar e derrubar o nome real da FK antes de poder tirar a coluna.
    const [fks] = await conn.query(
      `SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
       WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'modulos' AND COLUMN_NAME = 'ciclo_id' AND REFERENCED_TABLE_NAME IS NOT NULL`,
      [database]
    );
    for (const fk of fks) {
      await conn.query(`ALTER TABLE modulos DROP FOREIGN KEY ${fk.CONSTRAINT_NAME}`);
    }
    await conn.query('ALTER TABLE modulos DROP COLUMN ciclo_id');
    console.log('modulos.ciclo_id removido.');
  }
  if (colNames.has('icon')) {
    await conn.query('ALTER TABLE modulos DROP COLUMN icon');
    console.log('modulos.icon removido.');
  }
  if (colNames.has('imagem')) {
    await conn.query('ALTER TABLE modulos DROP COLUMN imagem');
    console.log('modulos.imagem removido.');
  }
  if (!colNames.has('tema_id')) {
    await conn.query('ALTER TABLE modulos ADD COLUMN tema_id CHAR(18) NOT NULL AFTER id');
    await conn.query('ALTER TABLE modulos ADD FOREIGN KEY (tema_id) REFERENCES temas(id) ON DELETE RESTRICT');
    console.log('modulos.tema_id adicionado.');
  }
  await conn.query('ALTER TABLE modulos MODIFY COLUMN carga_horaria DECIMAL(6,2)');
  console.log('modulos.carga_horaria convertido para numérico.');

  await conn.end();
  console.log('Migração concluída.');
}

main().catch((err) => { console.error('ERRO:', err); process.exit(1); });
