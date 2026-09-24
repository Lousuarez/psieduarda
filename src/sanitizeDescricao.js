// Sanitiza o HTML do rich text (negrito/itálico/listas) do editor de
// descrição — sem depender de pacote externo, pra não exigir "npm install"
// no host a cada deploy (uma dependência nova não instalada derruba o app
// com MODULE_NOT_FOUND assim que a rota é carregada).
const ALLOWED_TAGS = new Set(['b', 'strong', 'i', 'em', 'u', 'ul', 'ol', 'li', 'p', 'br', 'a']);
const VOID_TAGS = new Set(['br']);
const ALLOWED_SCHEMES = new Set(['http:', 'https:', 'mailto:']);

const TAG_RE = /<\/?([a-zA-Z][a-zA-Z0-9]*)((?:\s+[a-zA-Z_:][-a-zA-Z0-9_:.]*(?:\s*=\s*(?:"[^"]*"|'[^']*'|[^\s"'>]+))?)*)\s*\/?>/g;
const ATTR_RE = /([a-zA-Z_:][-a-zA-Z0-9_:.]*)\s*=\s*(?:"([^"]*)"|'([^']*)'|([^\s"'>]+))/g;

function safeHref(raw) {
  if (!raw) return null;
  const value = raw.trim();
  try {
    const url = new URL(value, 'https://placeholder.invalid/');
    return ALLOWED_SCHEMES.has(url.protocol) ? value : null;
  } catch {
    return null;
  }
}

function escapeAttr(value) {
  return String(value).replace(/&/g, '&amp;').replace(/"/g, '&quot;');
}

function extractHref(attrsStr) {
  ATTR_RE.lastIndex = 0;
  let m;
  while ((m = ATTR_RE.exec(attrsStr))) {
    if (m[1].toLowerCase() === 'href') return m[2] ?? m[3] ?? m[4] ?? '';
  }
  return null;
}

function sanitizeDescricao(html) {
  const input = String(html || '')
    .replace(/<!--[\s\S]*?-->/g, '')
    .replace(/<(script|style)[\s\S]*?<\/\1\s*>/gi, '');

  return input.replace(TAG_RE, (full, rawName, attrsStr) => {
    const name = rawName.toLowerCase();
    if (!ALLOWED_TAGS.has(name)) return '';
    const isClosing = full.startsWith('</');
    if (isClosing) return VOID_TAGS.has(name) ? '' : `</${name}>`;
    if (VOID_TAGS.has(name)) return `<${name}>`;
    if (name === 'a') {
      const safe = safeHref(extractHref(attrsStr));
      return `<a${safe ? ` href="${escapeAttr(safe)}"` : ''} target="_blank" rel="noopener noreferrer">`;
    }
    return `<${name}>`;
  });
}

module.exports = { sanitizeDescricao };
