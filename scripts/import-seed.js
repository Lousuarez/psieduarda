// Importa dados do antigo formato "docs" (collection+id+JSON) do protótipo
// para as tabelas normalizadas atuais. Uso:
//   node scripts/import-seed.js <caminho-do-seed.sql>
// Lê DB_HOST/DB_PORT/DB_USER/DB_PASS/DB_NAME de .env (ou do ambiente).
require('dotenv').config();
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const mysql = require('mysql2/promise');

function genId() {
  return crypto.randomBytes(9).toString('hex');
}

async function main() {
  const seedPath = process.argv[2];
  if (!seedPath) {
    console.error('Uso: node scripts/import-seed.js <caminho-do-seed.sql>');
    process.exit(1);
  }
  const seedSql = fs.readFileSync(path.resolve(seedPath), 'utf8');

  const conn = await mysql.createConnection({
    host: process.env.DB_HOST,
    port: process.env.DB_PORT ? Number(process.env.DB_PORT) : 3306,
    user: process.env.DB_USER,
    password: process.env.DB_PASS,
    database: process.env.DB_NAME,
    multipleStatements: true,
    charset: 'utf8mb4_general_ci',
  });

  console.log(`Conectado em ${process.env.DB_HOST}/${process.env.DB_NAME}`);

  // 1) Carrega o seed numa tabela temporária "docs" (mesmo formato do
  //    protótipo antigo) só pra deixar o próprio MySQL desfazer o escape SQL
  //    (''  -> ') dos JSONs — mais seguro que reimplementar um parser de SQL.
  await conn.query('DROP TABLE IF EXISTS docs');
  await conn.query(`
    CREATE TABLE docs (
      collection VARCHAR(32) NOT NULL,
      id VARCHAR(191) NOT NULL,
      data JSON NOT NULL,
      PRIMARY KEY (collection, id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  `);
  await conn.query(seedSql);

  const [rows] = await conn.query('SELECT collection, id, data FROM docs ORDER BY collection, id');
  await conn.query('DROP TABLE docs');

  const byCollection = {};
  for (const row of rows) {
    const data = typeof row.data === 'string' ? JSON.parse(row.data) : row.data;
    (byCollection[row.collection] ||= []).push({ oldId: row.id, data });
  }
  for (const c of ['unidades', 'trilhas', 'ciclos', 'modulos', 'colaboradores', 'progresso']) {
    console.log(`  ${c}: ${(byCollection[c] || []).length} registros no seed`);
  }

  // 2) Mapa de id-antigo -> id-novo (CHAR(18)), por coleção.
  const idMap = {}; // `${collection}:${oldId}` -> newId
  function mapId(collection, oldId) {
    const key = `${collection}:${oldId}`;
    if (!idMap[key]) idMap[key] = genId();
    return idMap[key];
  }

  await conn.beginTransaction();
  try {
    const counts = { unidades: 0, trilhas: 0, ciclos: 0, modulos: 0, colaboradores: 0, colaborador_trilhas: 0, progresso: 0, progresso_puladas: 0 };

    for (const { oldId, data } of byCollection.unidades || []) {
      const newId = mapId('unidades', oldId);
      await conn.query('INSERT INTO unidades (id, nome) VALUES (?, ?)', [newId, data.nome || '']);
      counts.unidades++;
    }

    for (const { oldId, data } of byCollection.trilhas || []) {
      const newId = mapId('trilhas', oldId);
      await conn.query(
        'INSERT INTO trilhas (id, nome, descricao, ordem) VALUES (?, ?, ?, ?)',
        [newId, data.nome || '', data.descricao || '', data.ordem || 0]
      );
      counts.trilhas++;
    }

    for (const { oldId, data } of byCollection.ciclos || []) {
      const newId = mapId('ciclos', oldId);
      const trilhaId = mapId('trilhas', data.trilhaId);
      await conn.query(
        'INSERT INTO ciclos (id, trilha_id, nome, tema, ordem, icon) VALUES (?, ?, ?, ?, ?, ?)',
        [newId, trilhaId, data.nome || '', data.tema || '', data.ordem || 0, data.icon || 'layers']
      );
      counts.ciclos++;
    }

    for (const { oldId, data } of byCollection.modulos || []) {
      const newId = mapId('modulos', oldId);
      const cicloId = mapId('ciclos', data.cicloId);
      await conn.query(
        `INSERT INTO modulos
          (id, ciclo_id, nome, etapa, ordem, categoria, status, descricao, mentor, formato, publico_alvo, carga_horaria, inicio_previsto, link_material)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
        [
          newId, cicloId, data.nome || '', data.etapa || '', data.ordem || 0,
          data.categoria || 'Liderança', data.status || 'A iniciar', data.descricao || '',
          data.mentor || '', data.formato || '', data.publicoAlvo || '',
          data.cargaHoraria || '', data.inicioPrevisto || '', data.linkMaterial || '',
        ]
      );
      counts.modulos++;
    }

    for (const { oldId, data } of byCollection.colaboradores || []) {
      const newId = mapId('colaboradores', oldId);
      const unidadeId = data.unidadeId ? mapId('unidades', data.unidadeId) : null;
      await conn.query(
        `INSERT INTO colaboradores
          (id, nome, email, cargo, unidade_id, gestor, data_admissao, lideranca, ativo, nota)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
        [
          newId, data.nome || '', data.email || '', data.cargo || '', unidadeId,
          data.gestor || '', data.dataAdmissao || '', !!data.lideranca,
          data.ativo !== false, data.nota || '',
        ]
      );
      counts.colaboradores++;
      for (const tid of data.trilhaIds || []) {
        const trilhaId = mapId('trilhas', tid);
        await conn.query(
          'INSERT IGNORE INTO colaborador_trilhas (colaborador_id, trilha_id) VALUES (?, ?)',
          [newId, trilhaId]
        );
        counts.colaborador_trilhas++;
      }
    }

    for (const { oldId, data } of byCollection.progresso || []) {
      // "Não iniciado" não é gravado no schema atual — ausência de linha já
      // significa isso (ver progresso.status ENUM, só tem os outros 2 valores).
      if (data.status === 'Não iniciado') {
        counts.progresso_puladas++;
        continue;
      }
      const colaboradorId = mapId('colaboradores', data.colaboradorId);
      const moduloId = mapId('modulos', data.moduloId);
      await conn.query(
        'INSERT INTO progresso (colaborador_id, modulo_id, status) VALUES (?, ?, ?)',
        [colaboradorId, moduloId, data.status]
      );
      counts.progresso++;
    }

    await conn.commit();
    console.log('\nImportação concluída:');
    console.log(counts);
  } catch (err) {
    await conn.rollback();
    console.error('Erro durante a importação, tudo desfeito (rollback):', err);
    process.exitCode = 1;
  } finally {
    await conn.end();
  }
}

main();
