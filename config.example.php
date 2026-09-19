<?php
/**
 * Copie este arquivo para "config.local.php" (mesma pasta) e preencha com os
 * dados reais do banco de dados MySQL criado no hPanel da Hostinger.
 *
 * IMPORTANTE: config.local.php NUNCA deve ser enviado ao GitHub — ele já
 * está no .gitignore. Ele deve ser criado diretamente no servidor (pelo
 * Gerenciador de Arquivos do hPanel), depois que o site for implantado.
 */
return [
    // Em hPanel > Bancos de dados > Bancos de dados MySQL, o host quase
    // sempre é "localhost". O nome do banco e do usuário na Hostinger
    // costumam vir com um prefixo, ex: u123456789_juntos
    'dsn'  => 'mysql:host=localhost;dbname=SEU_BANCO;charset=utf8mb4',
    'user' => 'SEU_USUARIO_MYSQL',
    'pass' => 'SUA_SENHA_MYSQL',

    // Usuário e senha de acesso ao painel administrativo da ferramenta.
    // A senha abaixo já corresponde a "Prestes@admin2026" (o hash nunca
    // expõe a senha em texto puro). Para trocar a senha, gere um novo hash
    // rodando no terminal: php -r "echo password_hash('nova_senha', PASSWORD_DEFAULT);"
    'admin_user' => 'Admin',
    'admin_pass_hash' => '$2y$12$MDABQriibvpCcmVlWUXeeetf.Id/n2bX5CFybpdJUY/84dLrgEuv6',
];
