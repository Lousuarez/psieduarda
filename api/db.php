<?php
/**
 * Endpoint genérico de dados, no mesmo espírito do armazenamento de
 * documentos que a ferramenta usava antes (coleção + id + campos livres).
 * Isso evita ter que criar um arquivo de API por entidade (colaboradores,
 * trilhas, ciclos, módulos, progresso, unidades) e mantém o front-end
 * praticamente idêntico ao protótipo original.
 *
 * GET    api/db.php?collection=X            -> {"docs":[{"id":..., ...campos}, ...]}
 * POST   api/db.php?collection=X             body: JSON com os campos      -> {"id":...,...campos}
 * PUT    api/db.php?collection=X&id=Y         body: JSON (mescla campos)    -> {"id":...,...campos}
 * PUT    api/db.php?collection=X&id=Y&mode=set body: JSON (substitui tudo)  -> {"id":...,...campos}
 * DELETE api/db.php?collection=X&id=Y                                       -> {"ok":true}
 */

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';

header('Content-Type: application/json; charset=utf-8');

jad_start_session();
jad_require_auth();

const JAD_COLLECTIONS = ['unidades', 'trilhas', 'ciclos', 'modulos', 'colaboradores', 'progresso'];

function jad_fail(int $code, string $error): void {
    http_response_code($code);
    echo json_encode(['error' => $error]);
    exit;
}

function jad_gen_id(): string {
    return bin2hex(random_bytes(9));
}

$collection = (string)($_GET['collection'] ?? '');
if (!in_array($collection, JAD_COLLECTIONS, true)) {
    jad_fail(400, 'invalid_collection');
}

$pdo = jad_pdo();
jad_ensure_schema($pdo);

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (string)$_GET['id'] : null;

function jad_row_to_doc(array $row): array {
    $data = json_decode($row['data'], true) ?: [];
    return ['id' => $row['id']] + $data;
}

if ($method === 'GET') {
    if ($id !== null) {
        $stmt = $pdo->prepare('SELECT id, data FROM docs WHERE collection = ? AND id = ?');
        $stmt->execute([$collection, $id]);
        $row = $stmt->fetch();
        if (!$row) jad_fail(404, 'not_found');
        echo json_encode(jad_row_to_doc($row));
        exit;
    }
    $stmt = $pdo->prepare('SELECT id, data FROM docs WHERE collection = ? ORDER BY updated_at ASC');
    $stmt->execute([$collection]);
    $docs = array_map('jad_row_to_doc', $stmt->fetchAll());
    echo json_encode(['docs' => $docs]);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) $body = [];

if ($method === 'POST') {
    $newId = jad_gen_id();
    $stmt = $pdo->prepare('INSERT INTO docs (collection, id, data) VALUES (?, ?, ?)');
    $stmt->execute([$collection, $newId, json_encode($body, JSON_UNESCAPED_UNICODE)]);
    echo json_encode(['id' => $newId] + $body);
    exit;
}

if ($method === 'PUT') {
    if ($id === null) jad_fail(400, 'missing_id');
    $mode = (string)($_GET['mode'] ?? 'update');

    $stmt = $pdo->prepare('SELECT data FROM docs WHERE collection = ? AND id = ?');
    $stmt->execute([$collection, $id]);
    $existingRow = $stmt->fetch();
    $existing = $existingRow ? (json_decode($existingRow['data'], true) ?: []) : null;

    if ($mode === 'set') {
        // .set() do protótipo original: cria ou substitui o documento inteiro.
        $final = $body;
    } else {
        // .update() do protótipo original: exige que o documento já exista e
        // mescla só os campos enviados, preservando os demais.
        if ($existing === null) jad_fail(404, 'not_found');
        $final = array_merge($existing, $body);
    }

    if ($existingRow) {
        $stmt = $pdo->prepare('UPDATE docs SET data = ? WHERE collection = ? AND id = ?');
        $stmt->execute([json_encode($final, JSON_UNESCAPED_UNICODE), $collection, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO docs (collection, id, data) VALUES (?, ?, ?)');
        $stmt->execute([$collection, $id, json_encode($final, JSON_UNESCAPED_UNICODE)]);
    }
    echo json_encode(['id' => $id] + $final);
    exit;
}

if ($method === 'DELETE') {
    if ($id === null) jad_fail(400, 'missing_id');
    $stmt = $pdo->prepare('DELETE FROM docs WHERE collection = ? AND id = ?');
    $stmt->execute([$collection, $id]);
    echo json_encode(['ok' => true]);
    exit;
}

jad_fail(405, 'method_not_allowed');
