// Leitura simples de user-agent pra exibir "sistema · navegador" no
// histórico de acesso — sem depender de pacote externo (mesmo motivo do
// sanitizeDescricao: dependência nova não instalada derruba o app no deploy
// se o host não rodar "npm install").
function parseUserAgent(ua) {
  const s = String(ua || '');
  if (!s) return { os: '', browser: '' };

  let os = 'Desconhecido';
  if (/Windows NT 10\.0/.test(s)) os = 'Windows 10/11';
  else if (/Windows NT/.test(s)) os = 'Windows';
  else if (/Mac OS X/.test(s)) os = 'macOS';
  else if (/Android/.test(s)) os = 'Android';
  else if (/iPhone|iPad|iPod/.test(s)) os = 'iOS';
  else if (/Linux/.test(s)) os = 'Linux';

  let browser = 'Desconhecido';
  const edge = /Edg\/([\d.]+)/.exec(s);
  const opera = /OPR\/([\d.]+)/.exec(s);
  const chrome = /Chrome\/([\d.]+)/.exec(s);
  const firefox = /Firefox\/([\d.]+)/.exec(s);
  const safari = /Version\/([\d.]+).*Safari\//.exec(s);
  if (edge) browser = `Edge ${edge[1].split('.')[0]}`;
  else if (opera) browser = `Opera ${opera[1].split('.')[0]}`;
  else if (firefox) browser = `Firefox ${firefox[1].split('.')[0]}`;
  else if (chrome) browser = `Chrome ${chrome[1].split('.')[0]}`;
  else if (safari) browser = `Safari ${safari[1].split('.')[0]}`;

  return { os, browser };
}

module.exports = { parseUserAgent };
