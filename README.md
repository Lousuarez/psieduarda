# Juntos a Distância

Ferramenta interna da Construtora Prestes para cadastro, consulta e
acompanhamento de trilhas de desenvolvimento dos colaboradores.

Esta versão é uma aplicação **Node.js (Express) + MySQL**, pronta para ser
hospedada na Hostinger via o pacote Node.js do plano, no domínio
**psieduarda.com.br**.

## Acesso

Usuário e senha ficam na tabela `users` do banco de dados (criada e já
populada com um usuário padrão pelo `schema.sql` — veja o próprio arquivo
pro usuário/senha padrão). Login é feito por **token** (não por cookie de
sessão): o navegador guarda o token no `localStorage` depois do login e
manda ele em cada requisição via header `Authorization`. Isso evita
depender de `Set-Cookie`, que alguma infraestrutura de proxy/CDN pode
remover das respostas antes de chegar ao navegador.

Para trocar a senha de um usuário, gere um novo hash e atualize a linha
direto no banco (via phpMyAdmin):

```
node -e "console.log(require('bcryptjs').hashSync('sua_nova_senha', 10))"
```

```sql
UPDATE users SET password_hash = 'HASH_GERADO_ACIMA' WHERE username = 'Admin';
```

Pra criar outro usuário:

```sql
INSERT INTO users (id, username, password_hash)
VALUES (HEX(RANDOM_BYTES(9)), 'novo_usuario', 'HASH_GERADO_ACIMA');
```

## Como implantar na Hostinger (passo a passo)

1. **Criar a aplicação Node.js**
   No painel de deploy, importe o repositório `Lousuarez/psieduarda`,
   branch `main`, diretório raiz `./`, versão do Node 18 ou superior.

2. **Criar o banco de dados MySQL**
   Em hPanel → Bancos de dados → Bancos de dados MySQL, crie um novo banco
   e um novo usuário com acesso a ele. Anote: nome do banco, usuário, senha
   (o host quase sempre é `localhost`, ou o host remoto informado em
   "MySQL Remoto" se a aplicação não rodar na mesma rede do banco).

3. **Rodar o schema**
   Em hPanel → Bancos de dados → phpMyAdmin, abra o banco criado e rode o
   conteúdo de `schema.sql`. Isso cria as tabelas usadas pela aplicação
   **e** já insere o usuário administrador padrão — o schema precisa ser
   rodado antes do primeiro acesso, a aplicação não cria as tabelas
   sozinha.

4. **Definir as variáveis de ambiente**
   No painel de deploy, defina: `NODE_ENV=production`, `DB_HOST`,
   `DB_PORT`, `DB_USER`, `DB_PASS`, `DB_NAME` (veja `.env.example` para o
   formato de cada uma). Essas variáveis nunca são commitadas no
   repositório.

5. **Instalar as dependências e implantar**
   O próprio painel roda `npm install` e `npm start` (que executa
   `server.js`) automaticamente ao importar do GitHub.

6. **Testar**
   Acesse o domínio configurado. Deve aparecer a tela de login. Entre com
   as credenciais configuradas e confirme que consegue cadastrar uma
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
server.js               Entry point (Startup File)
src/db.js                Pool de conexão MySQL + gerador de IDs
src/auth.js               Login (bcrypt) e emissão/validação de tokens
src/routes/auth.js        POST /api/login, POST /api/logout, GET /api/me
src/routes/*.js           Uma rota REST por entidade (unidades, trilhas,
                          ciclos, modulos, colaboradores, progresso)
views/app.ejs             Página única da aplicação (login + app, alternados
                          via JS conforme o token) — HTML/CSS/JS do front-end
public/assets/            Logos e outros arquivos estáticos
schema.sql                Script de criação das tabelas + usuário admin
                          padrão (rodar manualmente antes do primeiro acesso)
```

O modelo de dados usa **tabelas normalizadas por entidade** (colaboradores,
trilhas, ciclos, módulos, progresso, unidades, usuários), com chaves
estrangeiras reais e exclusão em cascata onde faz sentido (ex.: excluir um
colaborador remove automaticamente o progresso e as matrículas em trilhas
dele).

## Segurança

- As senhas ficam com hash (bcrypt) na tabela `users`, nunca em texto puro,
  e nunca são enviadas ao GitHub.
- Login é por token opaco (tabela `auth_tokens`, expira em 30 dias),
  enviado pelo cliente via header `Authorization: Bearer <token>` — não
  depende de cookies, então funciona mesmo atrás de proxies/CDNs que
  removem `Set-Cookie`.
- Todas as rotas de dados (`/api/*`) exigem um token válido — sem login,
  a API responde 401 e nenhum dado é retornado.
