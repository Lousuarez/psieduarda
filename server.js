require('dotenv').config();

const path = require('path');
const express = require('express');

const { requireAuth, requireAdmin } = require('./src/auth');
const asyncHandler = require('./src/asyncHandler');
const authRoutes = require('./src/routes/auth');
const unidadesRoutes = require('./src/routes/unidades');
const trilhasRoutes = require('./src/routes/trilhas');
const ciclosRoutes = require('./src/routes/ciclos');
const temasRoutes = require('./src/routes/temas');
const modulosRoutes = require('./src/routes/modulos');
const moduloEtapasRoutes = require('./src/routes/moduloEtapas');
const moduloFrequenciaRoutes = require('./src/routes/moduloFrequencia');
const moduloEtapaChamadaRoutes = require('./src/routes/moduloEtapaChamada');
const colaboradoresRoutes = require('./src/routes/colaboradores');
const progressoRoutes = require('./src/routes/progresso');
const usuariosRoutes = require('./src/routes/usuarios');
const minhaTrilhaRoutes = require('./src/routes/minhaTrilha');
const exportRoutes = require('./src/routes/export');
const auditLogRoutes = require('./src/routes/auditLog');
const loginLogRoutes = require('./src/routes/loginLog');
const { version } = require('./src/version');

const app = express();

// Atrás do proxy da Hostinger — sem isso, req.ip mostra o IP interno do
// proxy em vez do IP real de quem acessou (usado no histórico de acesso).
app.set('trust proxy', true);

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));

app.use(express.json({ limit: '3mb' }));
app.use(express.urlencoded({ extended: false }));
app.use(express.static(path.join(__dirname, 'public')));

app.get('/', (req, res) => {
  res.render('app', { version });
});

app.get('/minha-trilha/:token', (req, res) => {
  res.render('minha-trilha');
});

app.use('/api', authRoutes);

// Público (sem requireAuth) — protegido pelo próprio token do link, que só o
// dono do link conhece.
app.use('/api/minha-trilha', minhaTrilhaRoutes);

app.use('/api/unidades', asyncHandler(requireAuth), unidadesRoutes);
app.use('/api/trilhas', asyncHandler(requireAuth), trilhasRoutes);
app.use('/api/ciclos', asyncHandler(requireAuth), ciclosRoutes);
app.use('/api/temas', asyncHandler(requireAuth), temasRoutes);
app.use('/api/modulos', asyncHandler(requireAuth), modulosRoutes);
app.use('/api/modulo-etapas', asyncHandler(requireAuth), moduloEtapasRoutes);
app.use('/api/modulo-frequencia', asyncHandler(requireAuth), moduloFrequenciaRoutes);
app.use('/api/modulo-etapa-chamada', asyncHandler(requireAuth), moduloEtapaChamadaRoutes);
app.use('/api/colaboradores', asyncHandler(requireAuth), colaboradoresRoutes);
app.use('/api/progresso', asyncHandler(requireAuth), progressoRoutes);
app.use('/api/usuarios', asyncHandler(requireAuth), requireAdmin, usuariosRoutes);
app.use('/api/export', asyncHandler(requireAuth), exportRoutes);
app.use('/api/auditoria', asyncHandler(requireAuth), requireAdmin, auditLogRoutes);
app.use('/api/login-log', asyncHandler(requireAuth), requireAdmin, loginLogRoutes);

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
