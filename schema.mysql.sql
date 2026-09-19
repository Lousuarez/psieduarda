-- Rode este script uma vez no phpMyAdmin da Hostinger (hPanel > Bancos de
-- dados > phpMyAdmin), no banco que você criou para o projeto.
--
-- Observação: a aplicação também cria esta tabela sozinha na primeira vez
-- que alguém acessa a página (função jad_ensure_schema em inc/db.php), então
-- rodar este script é opcional — mas é uma boa forma de confirmar que as
-- credenciais em config.local.php estão corretas antes de testar o site.

CREATE TABLE IF NOT EXISTS docs (
    collection VARCHAR(32) NOT NULL,
    id VARCHAR(64) NOT NULL,
    data JSON NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (collection, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
