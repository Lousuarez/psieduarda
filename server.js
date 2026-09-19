require('dotenv').config();

const path = require('path');
const express = require('express');

const { requireAuth, requireAdmin } = require('./src/auth');
const asyncHandler = require('./src/asyncHandler');
const authRoutes = require('./src/routes/auth');
const unidadesRoutes = require('./src/routes/unidades');
const trilhasRoutes = require('./src/routes/trilhas');
const ciclosRoutes = require('./src/routes/ciclos');
const modulosRoutes = require('./src/routes/modulos');
const colaboradoresRoutes = require('./src/routes/colaboradores');
const progressoRoutes = require('./src/routes/progresso');
const usuariosRoutes = require('./src/routes/usuarios');

const app = express();

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));

app.use(express.json());
app.use(express.urlencoded({ extended: false }));
app.use(express.static(path.join(__dirname, 'public')));

app.get('/', (req, res) => {
  res.render('app');
});

app.use('/api', authRoutes);

app.use('/api/unidades', asyncHandler(requireAuth), unidadesRoutes);
app.use('/api/trilhas', asyncHandler(requireAuth), trilhasRoutes);
app.use('/api/ciclos', asyncHandler(requireAuth), ciclosRoutes);
app.use('/api/modulos', asyncHandler(requireAuth), modulosRoutes);
app.use('/api/colaboradores', asyncHandler(requireAuth), colaboradoresRoutes);
app.use('/api/progresso', asyncHandler(requireAuth), progressoRoutes);
app.use('/api/usuarios', asyncHandler(requireAuth), requireAdmin, usuariosRoutes);

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
