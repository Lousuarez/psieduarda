const mysql = require('mysql2/promise');
const crypto = require('crypto');

const pool = mysql.createPool({
  host: process.env.DB_HOST,
  port: process.env.DB_PORT ? Number(process.env.DB_PORT) : 3306,
  user: process.env.DB_USER,
  password: process.env.DB_PASS,
  database: process.env.DB_NAME,
  waitForConnections: true,
  connectionLimit: 10,
  charset: 'utf8mb4_general_ci',
});

function genId() {
  return crypto.randomBytes(9).toString('hex');
}

function genToken() {
  return crypto.randomBytes(32).toString('hex');
}

module.exports = { pool, genId, genToken };
