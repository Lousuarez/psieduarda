// Versão exibida no rodapé da barra lateral — hash curto do commit + data,
// calculado uma vez na subida do processo (não muda até o próximo deploy).
// Sem .git disponível (ex.: deploy sem a pasta .git), cai pro número de
// versão do package.json.
const { execSync } = require('child_process');
const pkg = require('../package.json');

function readGit(cmd) {
  return execSync(cmd, { cwd: __dirname + '/..', stdio: ['ignore', 'pipe', 'ignore'] }).toString().trim();
}

function computeVersion() {
  try {
    return {
      hash: readGit('git rev-parse --short HEAD'),
      date: readGit('git log -1 --format=%cd --date=format:%d/%m/%Y'),
    };
  } catch (err) {
    return { hash: `v${pkg.version}`, date: '' };
  }
}

module.exports = { version: computeVersion() };
