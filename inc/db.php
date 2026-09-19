<?php
require_once __DIR__ . '/config.php';

function jad_pdo(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    $cfg = jad_config();
    try {
        $pdo = new PDO($cfg['dsn'], $cfg['user'] ?? null, $cfg['pass'] ?? null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'db_connection_failed']);
        exit;
    }
    return $pdo;
}

/**
 * Garante que a tabela genérica de documentos existe. Chamado uma vez por
 * requisição antes de qualquer leitura/escrita — barato o suficiente para
 * não precisar de uma etapa de instalação separada além de rodar o
 * schema.mysql.sql (mas funciona mesmo que o usuário esqueça de rodá-lo).
 */
function jad_ensure_schema(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        // Apenas para desenvolvimento/teste local — produção usa MySQL (schema.mysql.sql).
        $pdo->exec("CREATE TABLE IF NOT EXISTS docs (
            collection VARCHAR(32) NOT NULL,
            id VARCHAR(64) NOT NULL,
            data TEXT NOT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (collection, id)
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS docs (
            collection VARCHAR(32) NOT NULL,
            id VARCHAR(64) NOT NULL,
            data JSON NOT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (collection, id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    $done = true;
}
