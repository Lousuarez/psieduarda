-- Rode este script uma vez no phpMyAdmin da Hostinger (hPanel > Bancos de
-- dados > phpMyAdmin), no banco que você criou para o projeto.

CREATE TABLE IF NOT EXISTS unidades (
    id CHAR(18) PRIMARY KEY,
    nome VARCHAR(191) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS trilhas (
    id CHAR(18) PRIMARY KEY,
    nome VARCHAR(191) NOT NULL,
    descricao TEXT,
    ordem INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ciclos (
    id CHAR(18) PRIMARY KEY,
    trilha_id CHAR(18) NOT NULL,
    nome VARCHAR(191) NOT NULL,
    tema VARCHAR(191),
    ordem INT NOT NULL DEFAULT 0,
    icon VARCHAR(32) NOT NULL DEFAULT 'layers',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (trilha_id) REFERENCES trilhas(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS modulos (
    id CHAR(18) PRIMARY KEY,
    ciclo_id CHAR(18) NOT NULL,
    nome VARCHAR(191) NOT NULL,
    etapa VARCHAR(191),
    ordem INT NOT NULL DEFAULT 0,
    categoria ENUM('Liderança','Método','Liderança e Método') NOT NULL DEFAULT 'Liderança',
    status ENUM('A iniciar','Em andamento','Concluído') NOT NULL DEFAULT 'A iniciar',
    descricao TEXT,
    mentor VARCHAR(191),
    formato VARCHAR(191),
    publico_alvo VARCHAR(191),
    carga_horaria VARCHAR(64),
    inicio_previsto VARCHAR(64),
    link_material VARCHAR(500),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ciclo_id) REFERENCES ciclos(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS colaboradores (
    id CHAR(18) PRIMARY KEY,
    nome VARCHAR(191) NOT NULL,
    email VARCHAR(191),
    cargo VARCHAR(191),
    unidade_id CHAR(18),
    gestor VARCHAR(191),
    data_admissao VARCHAR(32),
    lideranca BOOLEAN NOT NULL DEFAULT FALSE,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    nota TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (unidade_id) REFERENCES unidades(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS colaborador_trilhas (
    colaborador_id CHAR(18) NOT NULL,
    trilha_id CHAR(18) NOT NULL,
    PRIMARY KEY (colaborador_id, trilha_id),
    FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE,
    FOREIGN KEY (trilha_id) REFERENCES trilhas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS progresso (
    colaborador_id CHAR(18) NOT NULL,
    modulo_id CHAR(18) NOT NULL,
    status ENUM('Em andamento','Concluído') NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (colaborador_id, modulo_id),
    FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE,
    FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Login por usuário/senha (substitui as variáveis ADMIN_USER/ADMIN_PASS_HASH).
CREATE TABLE IF NOT EXISTS users (
    id CHAR(18) PRIMARY KEY,
    username VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(191) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tokens de acesso emitidos no login. Usados em vez de cookie de sessão porque
-- a infraestrutura da Hostinger na frente da aplicação remove o header
-- Set-Cookie das respostas, impedindo o navegador de guardar a sessão.
CREATE TABLE IF NOT EXISTS auth_tokens (
    token CHAR(64) PRIMARY KEY,
    user_id CHAR(18) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Usuário administrador padrão (senha: Prestes@admin2026 — troque depois
-- gerando um novo hash com:
-- node -e "console.log(require('bcryptjs').hashSync('nova_senha', 10))"
-- e rodando: UPDATE users SET password_hash='...' WHERE username='Admin';)
INSERT INTO users (id, username, password_hash)
VALUES ('3f59b64087d9d61b89', 'Admin', '$2a$10$rNYeFW2gepBBnRoPQ7WVY.O.14pf4ubBS38C.vWe0e.2iVHqWw2iq')
ON DUPLICATE KEY UPDATE username = username;
