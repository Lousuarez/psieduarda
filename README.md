# Juntos a Distância

Ferramenta interna da Construtora Prestes para cadastro, consulta e
acompanhamento de trilhas de desenvolvimento dos colaboradores.

Esta versão é uma aplicação **Node.js (Express) + MySQL**, pronta para ser
hospedada na Hostinger via o pacote Node.js do plano, no domínio
**psieduarda.com.br**.

## Acesso

Usuário e senha ficam nas variáveis de ambiente `ADMIN_USER` /
`ADMIN_PASS_HASH` (veja `.env.example`). Para gerar um hash de senha novo,
rode no terminal (com Node instalado):

```
node -e "console.log(require('bcryptjs').hashSync('sua_nova_senha', 10))"
```

e substitua o valor de `ADMIN_PASS_HASH` nas variáveis de ambiente do
servidor.

## Como implantar na Hostinger (passo a passo)

1. **Criar a aplicação Node.js**
   Em hPanel → seu site → **Node.js** → Criar aplicação:
   - Versão do Node: a LTS mais recente disponível (18 ou superior)
   - Application root: raiz do repositório
   - Application startup file: `server.js`
   - Application URL: `psieduarda.com.br`

2. **Implantar via Git**
   Na mesma tela (ou em Avançado → Git), aponte pro repositório
   `Lousuarez/psieduarda`, branch `main`, apontando pra raiz configurada no
   passo 1.

3. **Criar o banco de dados MySQL**
   Em hPanel → Bancos de dados → Bancos de dados MySQL, crie um novo banco
   e um novo usuário com acesso a ele. Anote: nome do banco, usuário, senha
   (o host quase sempre é `localhost`).

4. **Rodar o schema**
   Em hPanel → Bancos de dados → phpMyAdmin, abra o banco criado e rode o
   conteúdo de `schema.sql`. Isso cria as tabelas usadas pela aplicação
   (diferente da versão anterior, aqui o schema precisa ser rodado antes do
   primeiro acesso — a aplicação não cria as tabelas sozinha).

5. **Definir as variáveis de ambiente**
   No painel Node.js da Hostinger, defina: `DB_HOST`, `DB_PORT`, `DB_USER`,
   `DB_PASS`, `DB_NAME`, `SESSION_SECRET`, `ADMIN_USER`, `ADMIN_PASS_HASH`
   (veja `.env.example` para o formato de cada uma). Essas variáveis nunca
   são commitadas no repositório.

6. **Instalar as dependências**
   No painel Node.js, use o botão "Run NPM Install" (ou rode `npm install`
   via terminal SSH, se disponível).

7. **Testar**
   Acesse `https://psieduarda.com.br`. Deve aparecer a tela de login. Entre
   com as credenciais configuradas e confirme que consegue cadastrar uma
   unidade e um colaborador de teste.

## Rodando localmente

```
npm install
cp .env.example .env   # preencha com um MySQL local
npm run dev
```

Acesse `http://localhost:3000`.

## Estrutura do projeto

```
server.js               Entry point (Startup File da Hostinger)
src/db.js                Pool de conexão MySQL + gerador de IDs
src/auth.js               Login (bcrypt) e middleware de sessão
src/routes/auth.js        POST /login, GET /logout
src/routes/*.js           Uma rota REST por entidade (unidades, trilhas,
                          ciclos, modulos, colaboradores, progresso)
views/app.ejs             Página única da aplicação (login + app, conforme
                          sessão) — HTML/CSS/JS do front-end
public/assets/            Logos e outros arquivos estáticos
schema.sql                Script de criação das tabelas (rodar manualmente
                          antes do primeiro acesso)
```

O modelo de dados usa **tabelas normalizadas por entidade** (colaboradores,
trilhas, ciclos, módulos, progresso, unidades), com chaves estrangeiras
reais e exclusão em cascata onde faz sentido (ex.: excluir um colaborador
remove automaticamente o progresso e as matrículas em trilhas dele).

## Segurança

- A senha do admin fica com hash (bcrypt) em variável de ambiente, nunca em
  texto puro, e nunca é enviada ao GitHub.
- A sessão de login é persistida no próprio MySQL (`express-mysql-session`)
  com cookie `HttpOnly`; em produção (`NODE_ENV=production`, atrás de
  HTTPS) o cookie também é marcado como seguro.
- Todas as rotas de dados (`/api/*`) exigem sessão autenticada — sem login
  válido, a API responde 401 e nenhum dado é retornado.
