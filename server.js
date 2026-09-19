require('dotenv').config();

const path = require('path');
const express = require('express');
const session = require('express-session');
const MySQLStore = require('express-mysql-session')(session);

const { pool } = require('./src/db');
const { requireAuth } = require('./src/auth');
const authRoutes = require('./src/routes/auth');
const unidadesRoutes = require('./src/routes/unidades');
const trilhasRoutes = require('./src/routes/trilhas');
const ciclosRoutes = require('./src/routes/ciclos');
const modulosRoutes = require('./src/routes/modulos');
const colaboradoresRoutes = require('./src/routes/colaboradores');
const progressoRoutes = require('./src/routes/progresso');

const app = express();
const isProd = process.env.NODE_ENV === 'production';

const sessionStore = new MySQLStore({}, pool);

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));

app.use(express.json());
app.use(express.urlencoded({ extended: false }));
app.use(express.static(path.join(__dirname, 'public')));

app.use(
  session({
    key: 'jad_sid',
    secret: process.env.SESSION_SECRET || 'dev-secret-change-me',
    store: sessionStore,
    resave: false,
    saveUninitialized: false,
    cookie: {
      httpOnly: true,
      sameSite: 'lax',
      secure: isProd,
      maxAge: 1000 * 60 * 60 * 24 * 30,
    },
  })
);

app.get('/', (req, res) => {
  const authed = !!(req.session && req.session.authed);
  const loginError = !!(req.session && req.session.loginError);
  if (req.session) delete req.session.loginError;
  res.render('app', { authed, loginError });
});

app.use('/', authRoutes);

app.use('/api/unidades', requireAuth, unidadesRoutes);
app.use('/api/trilhas', requireAuth, trilhasRoutes);
app.use('/api/ciclos', requireAuth, ciclosRoutes);
app.use('/api/modulos', requireAuth, modulosRoutes);
app.use('/api/colaboradores', requireAuth, colaboradoresRoutes);
app.use('/api/progresso', requireAuth, progressoRoutes);

// eslint-disable-next-line no-unused-vars
app.use((err, req, res, next) => {
  console.error(err);
  if (req.path.startsWith('/api/')) {
    return res.status(500).json({ error: 'internal_error' });
  }
  res.status(500).send('Erro interno.');
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log(`Juntos a Distância rodando em http://localhost:${PORT}`);
});
