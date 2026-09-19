<?php
/**
 * Carrega a configuração local (config.local.php). Esse arquivo não existe
 * no repositório de propósito — precisa ser criado no servidor a partir de
 * config.example.php, com os dados reais do banco MySQL da Hostinger.
 */

function jad_config(): array {
    static $config = null;
    if ($config !== null) {
        return $config;
    }
    $path = __DIR__ . '/../config.local.php';
    if (!file_exists($path)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Erro de configuração: config.local.php não encontrado.\n";
        echo "Copie config.example.php para config.local.php e preencha com os dados do seu banco MySQL.";
        exit;
    }
    $config = require $path;
    return $config;
}
