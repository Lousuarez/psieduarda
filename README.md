# Juntos a Distância

Ferramenta interna da Construtora Prestes para cadastro, consulta e
acompanhamento de trilhas de desenvolvimento dos colaboradores.

Esta versão é uma aplicação PHP + MySQL, pronta para ser hospedada na
Hostinger no domínio **psieduarda.com.br**. É a mesma interface do
protótipo validado anteriormente, agora com um banco de dados e um login
de verdade (rodando no servidor, não mais só no navegador).

## Acesso

- Usuário: `Admin`
- Senha: `Prestes@admin2026`

Para trocar a senha depois, gere um novo hash rodando (no terminal, ou em
qualquer ambiente com PHP):

```
php -r "echo password_hash('sua_nova_senha', PASSWORD_DEFAULT);"
```

e substitua o valor de `admin_pass_hash` em `config.local.php` no servidor.

## Como implantar na Hostinger (passo a passo)

1. **Implantar via Git**
   Em hPanel → seu site → Avançado → Git, selecione o repositório
   `Lousuarez/psieduarda`, a branch `main`, e como diretório raiz use
   `public_html` (ou uma subpasta, se preferir). Clique em Implantar.

2. **Criar o banco de dados MySQL**
   Em hPanel → Bancos de dados → Bancos de dados MySQL, crie um novo banco
   e um novo usuário com acesso a ele. Anote: nome do banco, usuário, senha
   (o host quase sempre é `localhost`).

3. **Criar o arquivo de configuração no servidor**
   Pelo Gerenciador de Arquivos do hPanel, dentro da pasta onde o site foi
   implantado, duplique `config.example.php`, renomeie a cópia para
   `config.local.php` e preencha com os dados do banco criado no passo 2.
   Esse arquivo não vem no repositório de propósito — ele nunca deve ser
   enviado ao GitHub, pois guarda uma credencial de verdade.

4. **Rodar o schema (opcional, mas recomendado)**
   Em hPanel → Bancos de dados → phpMyAdmin, abra o banco criado e rode o
   conteúdo de `schema.mysql.sql`. Isso cria a tabela usada pela aplicação.
   Se você pular esse passo, a aplicação cria a tabela sozinha no primeiro
   acesso — rodar o script só serve para confirmar que as credenciais em
   `config.local.php` estão corretas.

5. **Testar**
   Acesse `https://psieduarda.com.br`. Deve aparecer a tela de login. Entre
   com as credenciais acima e confirme que consegue cadastrar uma unidade
   e um colaborador de teste.

## Estrutura do projeto

```
index.php              Página única da aplicação (login + app, conforme sessão)
login.php / logout.php Entram/saem da sessão administrativa
inc/config.php          Carrega config.local.php
inc/db.php              Conexão PDO + criação da tabela
inc/auth.php            Sessão e verificação de senha
api/db.php              Endpoint único de dados (colaboradores, trilhas,
                        ciclos, módulos, progresso, unidades)
schema.mysql.sql        Script de criação da tabela, para rodar manualmente
config.example.php      Modelo de config.local.php (copiar e preencher)
```

Cada registro (colaborador, trilha, ciclo, módulo, progresso, unidade) é
salvo como uma linha na tabela `docs`, identificada por coleção + id, com
os campos em uma coluna JSON. Isso mantém a aplicação simples de manter e
evita ter que alterar o banco de dados sempre que um campo novo for
adicionado no futuro.

## Segurança

- A senha do admin fica com hash (bcrypt) em `config.local.php`, nunca em
  texto puro, e esse arquivo nunca é enviado ao GitHub.
- A sessão de login usa cookie `HttpOnly` e, quando o site estiver em
  HTTPS (o certificado SSL grátis da Hostinger já está ativo), o cookie
  também é marcado como seguro.
- Todas as rotas de dados (`api/db.php`) exigem sessão autenticada — sem
  login válido, a API responde 401 e nenhum dado é retornado.
