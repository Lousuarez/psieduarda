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
    transversal BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (trilha_id) REFERENCES trilhas(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rodar de novo é seguro (idempotente) — cobre bancos que já tinham a
-- tabela "ciclos" criada antes da coluna transversal existir. Um ciclo
-- transversal representa temas que valem para a trilha inteira, fora da
-- sequência numerada de ciclos (ver renderViewTrilhaExtra no front-end).
ALTER TABLE ciclos ADD COLUMN IF NOT EXISTS transversal BOOLEAN NOT NULL DEFAULT FALSE;

-- Tema: novo nível entre ciclo e módulo (Trilha > Ciclo > Tema > Módulo).
-- É o Tema que aparece como "bolinha" na Trilha-Extra agora (não mais o
-- módulo) — por isso carrega o ícone/imagem, mês, formato e descrição.
-- "carga_horaria" do tema NÃO é gravada aqui: é sempre calculada como a
-- soma de modulos.carga_horaria dos módulos do tema (ver GET /api/temas).
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS modulos (
    id CHAR(18) PRIMARY KEY,
    tema_id CHAR(18) NOT NULL,
    nome VARCHAR(191) NOT NULL,
    etapa VARCHAR(191),
    ordem INT NOT NULL DEFAULT 0,
    categoria ENUM('Liderança','Método','Liderança e Método','Autoconhecimento','Inovação') NOT NULL DEFAULT 'Liderança',
    status ENUM('A iniciar','Em andamento','Concluído') NOT NULL DEFAULT 'A iniciar',
    descricao TEXT,
    mentor VARCHAR(191),
    formato VARCHAR(191),
    publico_alvo VARCHAR(191),
    carga_horaria DECIMAL(6,2),
    inicio_previsto VARCHAR(64),
    link_material VARCHAR(500),
    tem_cronograma BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tema_id) REFERENCES temas(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rodar de novo é seguro (idempotente) — cobre bancos que já tinham a
-- tabela "modulos" criada antes da coluna tem_cronograma existir.
ALTER TABLE modulos ADD COLUMN IF NOT EXISTS tem_cronograma BOOLEAN NOT NULL DEFAULT FALSE;

-- Rodar de novo é seguro — cobre bancos que já tinham "modulos" criada antes
-- de "Autoconhecimento"/"Inovação" existirem como categoria.
ALTER TABLE modulos MODIFY COLUMN categoria ENUM('Liderança','Método','Liderança e Método','Autoconhecimento','Inovação') NOT NULL DEFAULT 'Liderança';

-- NOTA: a migração de "modulos.ciclo_id" (+ "icon"/"imagem", que voltaram a
-- não existir em módulo) para "modulos.tema_id" é destrutiva e não cabe
-- num ALTER idempotente — foi feita via script avulso (rebuild-trilhas),
-- que também apaga toda a estrutura de trilhas/ciclos/temas/módulos antes
-- de recriar (preserva colaboradores/unidades/usuários).

-- Cronograma de etapas de um módulo (opcional — só usado quando
-- modulos.tem_cronograma = TRUE). Cada etapa tem seu próprio período e
-- status de execução, independente do status geral da turma do módulo.
CREATE TABLE IF NOT EXISTS modulo_etapas (
    id CHAR(18) PRIMARY KEY,
    modulo_id CHAR(18) NOT NULL,
    nome VARCHAR(191) NOT NULL,
    data_inicio DATE,
    data_fim DATE,
    status ENUM('A iniciar','Em andamento','Concluído') NOT NULL DEFAULT 'A iniciar',
    ordem INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE CASCADE
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
    access_token CHAR(64) UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (unidade_id) REFERENCES unidades(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rodar de novo é seguro (idempotente) — cobre bancos que já tinham a
-- tabela "colaboradores" criada antes da coluna access_token existir.
ALTER TABLE colaboradores ADD COLUMN IF NOT EXISTS access_token CHAR(64) UNIQUE;

-- Frequência (presença) de colaboradores em cada etapa/aula do cronograma
-- de um módulo. Uma linha só existe quando a presença já foi marcada
-- (ausência de linha = frequência ainda não registrada para aquele par).
CREATE TABLE IF NOT EXISTS modulo_etapa_frequencia (
    etapa_id CHAR(18) NOT NULL,
    colaborador_id CHAR(18) NOT NULL,
    presente BOOLEAN NOT NULL DEFAULT FALSE,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (etapa_id, colaborador_id),
    FOREIGN KEY (etapa_id) REFERENCES modulo_etapas(id) ON DELETE CASCADE,
    FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estado da "chamada" (processo de presença) de uma etapa. Ausência de linha
-- = chamada ainda não iniciada (tela de frequência fica bloqueada). Uma vez
-- fechada, pode ser reaberta pra corrigir — cada fechamento grava uma versão
-- em modulo_etapa_chamada_versao (auditoria: mostra o "antes" e o "depois").
CREATE TABLE IF NOT EXISTS modulo_etapa_chamada (
    etapa_id CHAR(18) PRIMARY KEY,
    status ENUM('aberta','fechada') NOT NULL DEFAULT 'aberta',
    iniciada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    iniciada_por VARCHAR(191),
    fechada_em TIMESTAMP NULL,
    fechada_por VARCHAR(191),
    FOREIGN KEY (etapa_id) REFERENCES modulo_etapas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Um snapshot completo da lista de presença sempre que a chamada de uma
-- etapa é fechada (a primeira vez = motivo "fechamento"; reaberturas
-- seguintes = motivo "edicao"). "criada_por" fica como texto (não FK) de
-- propósito — é registro de auditoria, deve sobreviver à exclusão do usuário.
CREATE TABLE IF NOT EXISTS modulo_etapa_chamada_versao (
    id CHAR(18) PRIMARY KEY,
    etapa_id CHAR(18) NOT NULL,
    versao INT NOT NULL,
    motivo ENUM('fechamento','edicao') NOT NULL DEFAULT 'fechamento',
    criada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    criada_por VARCHAR(191),
    FOREIGN KEY (etapa_id) REFERENCES modulo_etapas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS modulo_etapa_chamada_versao_presenca (
    versao_id CHAR(18) NOT NULL,
    colaborador_id CHAR(18) NOT NULL,
    presente BOOLEAN NOT NULL DEFAULT FALSE,
    PRIMARY KEY (versao_id, colaborador_id),
    FOREIGN KEY (versao_id) REFERENCES modulo_etapa_chamada_versao(id) ON DELETE CASCADE,
    FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE
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

-- Histórico de mudanças de status em progresso — cada PUT /api/progresso
-- grava uma linha aqui além de atualizar (ou apagar) a linha em "progresso".
-- 'Não iniciado' é registrado aqui (diferente da tabela "progresso", onde
-- esse status nunca é uma linha) porque isso é log de evento, não estado.
CREATE TABLE IF NOT EXISTS progresso_historico (
    id CHAR(18) PRIMARY KEY,
    colaborador_id CHAR(18) NOT NULL,
    modulo_id CHAR(18) NOT NULL,
    status ENUM('Não iniciado','Em andamento','Concluído') NOT NULL,
    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE,
    FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE CASCADE,
    KEY idx_progresso_historico_colab_mod (colaborador_id, modulo_id, changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Login por usuário/senha (substitui as variáveis ADMIN_USER/ADMIN_PASS_HASH).
-- Só usuários com is_admin=TRUE enxergam e usam o menu Administrativo
-- (cadastro de outros usuários).
CREATE TABLE IF NOT EXISTS users (
    id CHAR(18) PRIMARY KEY,
    username VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(191) NOT NULL,
    is_admin BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rodar de novo é seguro (idempotente) — cobre bancos que já tinham a
-- tabela "users" criada antes da coluna is_admin existir.
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin BOOLEAN NOT NULL DEFAULT FALSE;

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

-- Log de auditoria genérico — uma linha por criação/edição/exclusão em
-- qualquer entidade do sistema. "valores_antes"/"valores_depois" guardam o
-- registro (no formato camelCase que a API já expõe) antes e depois da
-- mudança, em JSON — null em "antes" significa criação, null em "depois"
-- significa exclusão. "usuario_nome" e "interface" ficam como texto (não
-- FK) de propósito: são registro de auditoria, devem sobreviver mesmo que o
-- usuário seja excluído depois.
CREATE TABLE IF NOT EXISTS audit_log (
    id CHAR(18) PRIMARY KEY,
    entidade VARCHAR(64) NOT NULL,
    entidade_id VARCHAR(64) NOT NULL,
    acao ENUM('create','update','delete') NOT NULL,
    valores_antes JSON,
    valores_depois JSON,
    usuario_id CHAR(18),
    usuario_nome VARCHAR(191),
    interface VARCHAR(191),
    rota VARCHAR(191),
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_entidade (entidade, entidade_id, criado_em),
    KEY idx_audit_usuario (usuario_id, criado_em),
    KEY idx_audit_criado (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Histórico de acesso — uma linha por login bem-sucedido. "ip_address" e
-- "user_agent" vêm da requisição (não tem como o navegador informar o nome
-- do computador, só o servidor de arquivos locais teria isso). Sem FK em
-- usuario_id pelo mesmo motivo do audit_log: o histórico sobrevive à
-- exclusão do usuário.
CREATE TABLE IF NOT EXISTS login_log (
    id CHAR(18) PRIMARY KEY,
    usuario_id CHAR(18),
    usuario_nome VARCHAR(191) NOT NULL,
    ip_address VARCHAR(64),
    user_agent VARCHAR(255),
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_login_usuario (usuario_id, criado_em),
    KEY idx_login_criado (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Usuário administrador padrão (senha: Prestes@admin2026 — troque depois
-- gerando um novo hash com:
-- node -e "console.log(require('bcryptjs').hashSync('nova_senha', 10))"
-- e rodando: UPDATE users SET password_hash='...' WHERE username='Admin';)
INSERT INTO users (id, username, password_hash, is_admin)
VALUES ('3f59b64087d9d61b89', 'Admin', '$2a$10$rNYeFW2gepBBnRoPQ7WVY.O.14pf4ubBS38C.vWe0e.2iVHqWw2iq', TRUE)
ON DUPLICATE KEY UPDATE is_admin = TRUE;
