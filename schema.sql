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
