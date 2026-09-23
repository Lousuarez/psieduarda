const { pool, genId } = require('./db');

// Rótulo legível de "em qual parte do sistema" cada entidade é editada —
// usado no relatório de auditoria (não depende do front-end mandar nada).
const ENTIDADE_LABELS = {
  unidade: 'Cadastros Gerais · Unidades',
  trilha: 'Cadastro de Trilhas',
  ciclo: 'Cadastro de Ciclos',
  modulo: 'Cadastro de Módulos',
  moduloEtapa: 'Cadastro de Módulos · Cronograma',
  moduloFrequencia: 'Frequência',
  moduloEtapaChamada: 'Frequência · Chamada',
  colaborador: 'Cadastro de Colaboradores',
  colaboradorTrilha: 'Colaborador · Matrícula em trilha',
  progresso: 'Colaborador · Progresso em módulo',
  usuario: 'Administrativo · Usuários',
};

// Grava uma linha de auditoria. Não lança em caso de erro — auditoria não
// pode derrubar a operação principal que está sendo registrada.
async function logAudit({ entidade, entidadeId, acao, antes, depois, req }) {
  try {
    await pool.query(
      `INSERT INTO audit_log (id, entidade, entidade_id, acao, valores_antes, valores_depois, usuario_id, usuario_nome, interface, rota)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [
        genId(),
        entidade,
        String(entidadeId),
        acao,
        antes !== undefined && antes !== null ? JSON.stringify(antes) : null,
        depois !== undefined && depois !== null ? JSON.stringify(depois) : null,
        req?.user?.id || null,
        req?.user?.username || null,
        ENTIDADE_LABELS[entidade] || entidade,
        req ? `${req.method} ${req.originalUrl}` : null,
      ]
    );
  } catch (err) {
    console.error('Falha ao gravar audit_log', err);
  }
}

module.exports = { logAudit, ENTIDADE_LABELS };
