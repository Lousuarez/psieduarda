<?php
require_once __DIR__ . '/inc/auth.php';
jad_start_session();
$authed = jad_is_authed();
$loginError = !empty($_SESSION['jad_login_error']);
unset($_SESSION['jad_login_error']);
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Juntos a Distância</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:#FAFAF8; --bg-elevated:#FFFFFF; --bg-sunken:#F1F0EA; --line:#E5E4DE;
    --ink:#181A16; --ink-dim: rgba(24,26,22,.6); --ink-faint: rgba(24,26,22,.54);
    --verde:#84E115; --verde-ink:#044D41; --accent: var(--verde-ink);
    --status-done-bg: var(--verde); --status-done-ink: var(--verde-ink);
    --status-prog-bg:#FCEBC2; --status-prog-ink:#8A5A00;
    --status-todo-bg: var(--line); --status-todo-ink: var(--ink-dim);
    --warn-bg:#FCEBC2; --warn-ink:#8A5A00;
    --shadow: 0 1px 2px rgba(20,20,16,.05);
    --font:'Urbanist', -apple-system, BlinkMacSystemFont,"Segoe UI",sans-serif;
    --danger:#B3261E;
  }
  @media (prefers-color-scheme: dark){
    :root:not([data-theme="light"]){
      --bg:#0A0F0C; --bg-elevated:#131A15; --bg-sunken:#0E1411; --line: rgba(255,255,255,.10);
      --ink:#F5F7F1; --ink-dim: rgba(245,247,241,.62); --ink-faint: rgba(245,247,241,.42);
      --verde:#84E115; --verde-ink:#044D41; --accent: var(--verde);
      --status-done-bg: rgba(132,225,21,.18); --status-done-ink:#9BE62A;
      --status-prog-bg: rgba(242,176,61,.18); --status-prog-ink:#F2B03D;
      --status-todo-bg: rgba(255,255,255,.07); --status-todo-ink: var(--ink-dim);
      --warn-bg: rgba(242,176,61,.16); --warn-ink:#F2B03D;
      --shadow: 0 1px 2px rgba(0,0,0,.35);
    }
  }
  :root[data-theme="dark"]{
    --bg:#0A0F0C; --bg-elevated:#131A15; --bg-sunken:#0E1411; --line: rgba(255,255,255,.10);
    --ink:#F5F7F1; --ink-dim: rgba(245,247,241,.62); --ink-faint: rgba(245,247,241,.42);
    --verde:#84E115; --verde-ink:#044D41; --accent: var(--verde);
    --status-done-bg: rgba(132,225,21,.18); --status-done-ink:#9BE62A;
    --status-prog-bg: rgba(242,176,61,.18); --status-prog-ink:#F2B03D;
    --status-todo-bg: rgba(255,255,255,.07); --status-todo-ink: var(--ink-dim);
    --warn-bg: rgba(242,176,61,.16); --warn-ink:#F2B03D;
    --shadow: 0 1px 2px rgba(0,0,0,.35);
  }
  @media (prefers-reduced-motion: reduce){ *{ animation-duration:.01ms !important; transition-duration:.01ms !important; } }
  *{ box-sizing:border-box; }
  :focus-visible{ outline:2px solid var(--verde-ink); outline-offset:2px; border-radius:4px; }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) :focus-visible{ outline-color:var(--verde); } }
  :root[data-theme="dark"] :focus-visible{ outline-color:var(--verde); }
  button, .colab-btn, .reg-row, .module-node, .cycle-head, .chip button, .status-pill, .icon-opt{ transition:background .15s ease-out, border-color .15s ease-out, color .15s ease-out, transform .1s ease-out, box-shadow .15s ease-out; }
  button:active:not(:disabled){ transform:scale(.97); }
  button:disabled{ opacity:.5; cursor:not-allowed; transform:none; }
  .spinner{ width:16px; height:16px; border:2px solid var(--line); border-top-color:var(--verde-ink); border-radius:50%; animation:spin .7s linear infinite; display:inline-block; flex-shrink:0; }
  @keyframes spin{ to{ transform:rotate(360deg); } }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .spinner{ border-top-color:var(--verde); } }
  :root[data-theme="dark"] .spinner{ border-top-color:var(--verde); }
  .loading-row{ display:flex; align-items:center; gap:10px; padding:20px 10px; color:var(--ink-dim); font-size:12.5px; }
  .empty-cta{ padding:44px 20px; text-align:center; border:1.5px dashed var(--line); border-radius:12px; }
  .empty-cta svg{ width:28px; height:28px; color:var(--ink-faint); margin-bottom:10px; }
  .empty-cta p{ margin:0; font-size:12.5px; color:var(--ink-dim); }
  .empty-cta .empty-title{ font-weight:700; color:var(--ink); font-size:13.5px; margin-bottom:4px; }
  @media (max-width:760px){
    .btn-ghost-sm{ padding:9px; }
    .reg-new-btn{ min-height:44px; }
    .nav-item{ padding:12px; font-size:14px; }
    .nav-submenu-toggle{ padding:12px; font-size:14px; }
  }
  html,body{ height:100%; }
  body{ margin:0; background:var(--bg); color:var(--ink); font-family:var(--font); transition: background .2s ease, color .2s ease; }
  ::selection{ background: var(--verde); color: var(--verde-ink); }

  .brand-mark{ display:flex; align-items:center; gap:9px; min-width:0; }
  .logo-img{ height:20px; width:auto; display:block; flex-shrink:0; }
  .brand-name{ font-weight:800; font-size:10.5px; letter-spacing:.14em; color:var(--ink-faint); text-transform:uppercase; display:block; }
  .brand-product{ font-weight:800; font-size:13px; letter-spacing:.02em; color:var(--ink); white-space:nowrap; }
  .brand-product b{ color:var(--verde-ink); }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .brand-product b{ color:var(--verde); } }
  :root[data-theme="dark"] .brand-product b{ color:var(--verde); }
  .sidebar-brand .brand-mark{ flex:1; min-width:0; }
  .sidebar-brand .brand-mark > div{ min-width:0; }
  .sidebar-brand .brand-product{ font-size:12px; white-space:normal; }
  .topbar .brand-mark{ gap:8px; }
  .brand-product-inline{ font-weight:700; font-size:12.5px; color:var(--ink-dim); }

  .kpi-row{ display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px; margin-bottom:24px; }
  .kpi-card{ background:var(--bg-elevated); border:1px solid var(--line); border-radius:8px; padding:14px 16px; box-shadow:var(--shadow); }
  .kpi-num{ font-weight:800; font-size:24px; color:var(--accent); line-height:1; margin-bottom:5px; font-variant-numeric:tabular-nums; }
  .kpi-label{ font-weight:600; font-size:10.5px; color:var(--ink-dim); line-height:1.3; }

  /* ===== App shell: sidebar + main content ===== */
  .app-shell{ display:flex; align-items:stretch; min-height:100vh; }
  .sidebar{ width:254px; flex-shrink:0; background:var(--bg-elevated); border-right:1px solid var(--line); display:flex; flex-direction:column; position:sticky; top:0; align-self:flex-start; height:100vh; overflow-y:auto; padding:20px 14px 16px; z-index:60; }
  .sidebar-brand{ display:flex; align-items:center; gap:10px; padding:2px 8px 18px; margin-bottom:14px; border-bottom:2px solid var(--verde); }
  .sidebar-brand-text{ display:flex; flex-direction:column; gap:2px; min-width:0; }
  .sidebar-close{ display:none; margin-left:auto; appearance:none; background:none; border:none; font-size:22px; line-height:1; color:var(--ink-faint); cursor:pointer; padding:2px 4px; }
  .sidebar-nav{ flex:1; display:flex; flex-direction:column; }
  .nav-group{ margin-bottom:22px; }
  .nav-group-label{ font-size:10.5px; font-weight:800; letter-spacing:.09em; text-transform:uppercase; color:var(--ink-faint); padding:0 12px; margin:0 0 8px; }
  .nav-item{ position:relative; display:flex; align-items:center; gap:10px; width:100%; text-align:left; padding:9px 12px; margin:0 0 2px; border-radius:8px; font-family:var(--font); font-weight:700; font-size:13.5px; color:var(--ink-dim); background:none; border:none; cursor:pointer; }
  .nav-item svg{ width:16px; height:16px; flex-shrink:0; opacity:.75; }
  .nav-item:hover{ background:var(--bg-sunken); color:var(--ink); }
  .nav-item.active{ background:var(--verde-ink); color:#fff; }
  .nav-item.active svg{ opacity:1; }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .nav-item.active{ background:var(--verde); color:var(--verde-ink); } }
  :root[data-theme="dark"] .nav-item.active{ background:var(--verde); color:var(--verde-ink); }

  .nav-submenu{ margin:0 0 2px; }
  .nav-submenu-toggle{ position:relative; display:flex; align-items:center; gap:10px; width:100%; text-align:left; padding:9px 12px; border-radius:8px; font-family:var(--font); font-weight:700; font-size:13.5px; color:var(--ink-dim); background:none; border:none; cursor:pointer; }
  .nav-submenu-toggle svg{ flex-shrink:0; opacity:.75; }
  .nav-submenu-toggle svg.nav-icon{ width:16px; height:16px; }
  .nav-submenu-toggle:hover{ background:var(--bg-sunken); color:var(--ink); }
  .nav-submenu-label{ flex:1; min-width:0; }
  .nav-chevron{ width:14px !important; height:14px !important; transition:transform .18s ease-out; }
  .nav-submenu.open .nav-chevron{ transform:rotate(90deg); }
  .nav-submenu-list{ display:grid; grid-template-rows:0fr; transition:grid-template-rows .2s ease-out; }
  .nav-submenu-list > div{ overflow:hidden; }
  .nav-submenu.open .nav-submenu-list{ grid-template-rows:1fr; }
  .nav-subitem{ padding-left:34px; font-size:13px; }
  .sidebar-foot{ font-size:10.5px; font-weight:600; color:var(--ink-faint); padding:14px 12px 2px; border-top:1px solid var(--line); margin-top:6px; letter-spacing:.02em; }
  .sidebar-overlay{ display:none; }

  .main-col{ flex:1; min-width:0; display:flex; flex-direction:column; }
  .topbar{ display:none; align-items:center; gap:12px; padding:12px 16px; border-bottom:1px solid var(--line); background:var(--bg-elevated); position:sticky; top:0; z-index:55; }
  .topbar-hamburger{ appearance:none; background:none; border:1px solid var(--line); border-radius:8px; width:38px; height:38px; display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--ink); flex-shrink:0; }
  .topbar-hamburger svg{ width:18px; height:18px; }

  .main-content{ flex:1; width:100%; max-width:1180px; padding:28px 32px 60px; }

  @media (max-width:920px){
    .sidebar{ position:fixed; left:0; top:0; bottom:0; height:100vh; width:min(280px,84vw); transform:translateX(-100%); transition:transform .25s cubic-bezier(.2,.7,.2,1); box-shadow:16px 0 40px rgba(0,0,0,.18); }
    .sidebar.show{ transform:translateX(0); }
    .sidebar-close{ display:flex; }
    .sidebar-overlay{ display:none; position:fixed; inset:0; background:rgba(10,15,12,.45); z-index:59; }
    .sidebar-overlay.show{ display:block; }
    .topbar{ display:flex; }
    .main-content{ padding:20px 16px 50px; }
  }

  .window{ background:var(--bg-elevated); border:1px solid var(--line); border-radius:14px; box-shadow:var(--shadow); overflow:hidden; margin-bottom:24px; animation:windowIn .28s ease-out; }
  @keyframes windowIn{ from{ opacity:0; transform:translateY(8px); } to{ opacity:1; transform:translateY(0); } }
  .window-head{ display:flex; justify-content:space-between; align-items:flex-start; gap:16px; padding:20px 24px 18px; border-bottom:1px solid var(--line); flex-wrap:wrap; }
  .window-title{ font-weight:800; font-size:18px; margin:0 0 4px; color:var(--ink); }
  .window-sub{ font-size:12.5px; color:var(--ink-dim); margin:0; max-width:520px; }
  .window-body{ padding:22px 24px; }
  @media (max-width:600px){ .window-head, .window-body{ padding-inline:16px; } }

  .view{ display:none; } .view.active{ display:block; }

  /* Cadastro list rows (Ciclos / Módulos) */
  .reg-toolbar{ display:flex; gap:8px; margin-bottom:14px; }
  .reg-search{ flex:1; font-family:var(--font); font-size:13px; color:var(--ink); border:1px solid var(--line); border-radius:6px; padding:9px 12px; background:var(--bg-elevated); outline:none; }
  .reg-search:focus{ border-color:var(--verde-ink); }
  .reg-new-btn{ appearance:none; background:var(--verde-ink); color:#fff; border:none; border-radius:6px; padding:0 16px; flex-shrink:0; cursor:pointer; font-family:var(--font); font-weight:700; font-size:12.5px; display:flex; align-items:center; gap:6px; white-space:nowrap; }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .reg-new-btn{ background:var(--verde); color:var(--verde-ink); } }
  :root[data-theme="dark"] .reg-new-btn{ background:var(--verde); color:var(--verde-ink); }
  .reg-new-btn svg{ width:14px; height:14px; }
  .reg-list{ display:flex; flex-direction:column; gap:1px; }
  .reg-row{ display:flex; align-items:center; gap:10px; padding:11px 6px; border-radius:6px; border-bottom:1px solid var(--line); cursor:pointer; }
  .reg-row:last-child{ border-bottom:none; }
  .reg-row:hover{ background:var(--bg-sunken); }
  .reg-row-main{ flex:1; min-width:0; }
  .reg-row-title{ font-size:13.5px; font-weight:700; color:var(--ink); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .reg-row-sub{ font-size:11.5px; color:var(--ink-dim); margin-top:2px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

  /* Visão geral (read-only) */
  .module-node.readonly, .cycle-head.readonly{ cursor:default; }
  .module-node.readonly:hover{ border-color:var(--line); }
  .sysmap-stat{ font-size:10.5px; color:var(--ink-dim); margin-top:6px; }

  /* Jornada da trilha (visão geral > trilhas) */
  .jornada-block{ margin-bottom:30px; } .jornada-block:last-child{ margin-bottom:0; }
  .jornada-head{ margin-bottom:14px; }
  .jornada-head h3{ font-size:16.5px; font-weight:700; margin:0 0 4px; color:var(--ink); }
  .jornada-head p{ font-size:12.5px; color:var(--ink-dim); margin:0 0 4px; max-width:640px; }
  .legend{ display:flex; gap:16px; flex-wrap:wrap; margin-bottom:16px; }
  .legend-item{ display:flex; align-items:center; gap:6px; font-size:11px; font-weight:700; color:var(--ink-dim); }
  .legend-dot{ width:8px; height:8px; border-radius:50%; flex-shrink:0; }
  .legend-dot.lideranca{ background:var(--verde-ink); } .legend-dot.metodo{ background:var(--ink-faint); } .legend-dot.ambos{ background:var(--verde); }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .legend-dot.lideranca{ background:var(--verde); } }
  :root[data-theme="dark"] .legend-dot.lideranca{ background:var(--verde); }
  .jornada-track{ position:relative; height:4px; background:var(--line); border-radius:4px; margin:0 0 22px; }
  .jornada-track-fill{ position:absolute; left:0; top:0; height:100%; background:var(--verde-ink); border-radius:4px; transition:width .3s ease-out; }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .jornada-track-fill{ background:var(--verde); } }
  :root[data-theme="dark"] .jornada-track-fill{ background:var(--verde); }
  .jornada-ciclo-group{ margin-bottom:18px; }
  .jornada-ciclo-label{ font-size:10.5px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:var(--verde-ink); margin:0 0 10px; display:flex; align-items:center; gap:7px; }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .jornada-ciclo-label{ color:var(--verde); } }
  :root[data-theme="dark"] .jornada-ciclo-label{ color:var(--verde); }
  .jornada-ciclo-label svg{ width:14px; height:14px; flex-shrink:0; }
  .jornada-stations{ display:flex; flex-wrap:wrap; gap:8px; }
  .station{ appearance:none; display:flex; align-items:center; gap:8px; background:var(--bg-elevated); border:1px solid var(--line); border-radius:10px; padding:8px 14px 8px 8px; cursor:pointer; font-family:var(--font); text-align:left; }
  .station:hover{ border-color:var(--verde-ink); }
  .station.selected{ border-color:var(--verde-ink); background:var(--status-done-bg); }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .station:hover, :root:not([data-theme="light"]) .station.selected{ border-color:var(--verde); } }
  :root[data-theme="dark"] .station:hover, :root[data-theme="dark"] .station.selected{ border-color:var(--verde); }
  .st-index{ width:20px; height:20px; border-radius:50%; background:var(--bg-sunken); color:var(--ink-dim); font-size:10px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .station.selected .st-index{ background:var(--verde-ink); color:#fff; }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .station.selected .st-index{ background:var(--verde); color:var(--verde-ink); } }
  :root[data-theme="dark"] .station.selected .st-index{ background:var(--verde); color:var(--verde-ink); }
  .st-name{ font-size:12.5px; font-weight:700; color:var(--ink); display:flex; align-items:center; gap:7px; line-height:1.3; max-width:230px; }
  .st-dot{ width:6px; height:6px; border-radius:50%; flex-shrink:0; }
  .st-dot.lideranca{ background:var(--verde-ink); } .st-dot.metodo{ background:var(--ink-faint); } .st-dot.ambos{ background:var(--verde); }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .st-dot.lideranca{ background:var(--verde); } }
  :root[data-theme="dark"] .st-dot.lideranca{ background:var(--verde); }
  .jornada-detail{ margin-top:18px; padding-top:18px; border-top:1px solid var(--line); animation:windowIn .2s ease-out; }
  .d-eyebrow{ font-size:10.5px; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:var(--ink-dim); margin:0 0 6px; }
  .d-cat-label.lideranca{ color:var(--verde-ink); } .d-cat-label.metodo{ color:var(--ink-dim); } .d-cat-label.ambos{ color:var(--verde-ink); }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .d-cat-label.lideranca, :root:not([data-theme="light"]) .d-cat-label.ambos{ color:var(--verde); } }
  :root[data-theme="dark"] .d-cat-label.lideranca, :root[data-theme="dark"] .d-cat-label.ambos{ color:var(--verde); }
  .d-title{ font-size:19px; font-weight:800; color:var(--ink); margin:0 0 10px; }
  .d-desc{ font-size:13px; color:var(--ink-dim); line-height:1.55; margin:14px 0; max-width:680px; }
  .meta-grid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px; margin:14px 0; }
  .meta-label{ font-size:10px; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:var(--ink-faint); margin-bottom:3px; }
  .meta-val{ font-size:13px; font-weight:600; color:var(--ink); }
  .people-block{ margin-top:16px; }
  .people-label{ font-size:11.5px; font-weight:700; color:var(--ink-dim); margin:0 0 8px; }
  .chip-list{ display:flex; flex-wrap:wrap; gap:8px; margin-bottom:10px; }
  .chip-list:last-child{ margin-bottom:0; }
  .chip-list .chip.concluido{ background:var(--status-done-bg); color:var(--status-done-ink); border-color:transparent; }
  .status-group-label{ display:flex; align-items:center; gap:8px; font-size:11.5px; font-weight:700; color:var(--ink-dim); margin:0 0 7px; }
  .material-btn{ display:inline-flex; align-items:center; gap:8px; margin-top:16px; background:var(--verde-ink); color:#fff; text-decoration:none; font-weight:700; font-size:12.5px; padding:10px 16px; border-radius:8px; }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .material-btn{ background:var(--verde); color:var(--verde-ink); } }
  :root[data-theme="dark"] .material-btn{ background:var(--verde); color:var(--verde-ink); }
  .material-btn svg{ width:15px; height:15px; }
  .no-material{ font-size:12px; color:var(--ink-faint); font-style:italic; margin-top:14px; }

  /* Trilha - Extra: roadmap sequencial por ciclos, sem rolagem lateral (quebra em nova linha) */
  .tx-select-wrap{ display:flex; flex-direction:column; gap:4px; }
  .tx-select-label{ font-size:10px; font-weight:800; letter-spacing:.07em; text-transform:uppercase; color:var(--ink-faint); }
  .tx-select-wrap select{ appearance:none; font-family:var(--font); font-size:13px; font-weight:700; color:var(--ink); background:var(--bg-elevated); border:1px solid var(--line); border-radius:8px; padding:9px 30px 9px 12px; min-width:220px; cursor:pointer; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 8px center; background-size:15px; }
  .tx-select-wrap select:focus{ border-color:var(--verde-ink); }
  .tx-roadmap-stat{ font-size:12px; color:var(--ink-dim); margin:0 0 20px; }

  .tx-row{ position:relative; display:flex; flex-wrap:wrap; align-items:flex-start; row-gap:56px; column-gap:34px; }
  .tx-connector-svg{ position:absolute; top:0; left:0; pointer-events:none; overflow:visible; z-index:0; }
  .tx-conn-path{ fill:none; stroke:var(--verde-ink); stroke-width:2.6; stroke-linecap:round; stroke-dasharray:0.1 10; opacity:.85; }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .tx-conn-path{ stroke:var(--verde); } }
  :root[data-theme="dark"] .tx-conn-path{ stroke:var(--verde); }
  .tx-ciclo{ position:relative; z-index:1; flex:1 1 250px; max-width:300px; background:var(--bg-sunken); border:1px solid var(--line); border-radius:14px; padding:16px 16px 18px; display:flex; flex-direction:column; }
  .tx-ciclo-head{ display:flex; align-items:center; gap:10px; margin-bottom:14px; padding-bottom:12px; border-bottom:1px solid var(--line); }
  .tx-ciclo-num{ flex-shrink:0; width:30px; height:30px; border-radius:9px; background:var(--verde-ink); color:#fff; font-weight:800; font-size:13px; display:flex; align-items:center; justify-content:center; }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .tx-ciclo-num{ background:var(--verde); color:var(--verde-ink); } }
  :root[data-theme="dark"] .tx-ciclo-num{ background:var(--verde); color:var(--verde-ink); }
  .tx-ciclo-title{ min-width:0; }
  .tx-ciclo-eyebrow{ font-size:9.5px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:var(--verde-ink); margin:0; }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .tx-ciclo-eyebrow{ color:var(--verde); } }
  :root[data-theme="dark"] .tx-ciclo-eyebrow{ color:var(--verde); }
  .tx-ciclo-name{ font-size:13.5px; font-weight:800; color:var(--ink); margin:1px 0 0; line-height:1.25; }

  .tx-nodes{ display:flex; flex-wrap:wrap; row-gap:18px; column-gap:0; flex:1; align-items:flex-start; }
  .tx-node{ flex:1 1 88px; min-width:76px; max-width:118px; display:flex; flex-direction:column; align-items:center; text-align:center; position:relative; padding:6px 4px; border-radius:10px; cursor:pointer; }
  .tx-node:hover{ background:var(--bg-elevated); }
  .tx-node:focus-visible{ outline:2px solid var(--verde-ink); outline-offset:2px; }
  .tx-node.selected{ background:var(--status-done-bg); }
  .tx-node::before, .tx-node::after{ content:""; position:absolute; top:25px; height:1.5px; background:var(--line); z-index:0; }
  .tx-node::before{ left:0; width:50%; }
  .tx-node::after{ right:0; width:50%; }
  .tx-node:first-child::before{ display:none; }
  .tx-node:last-child::after{ display:none; }
  .tx-node-icon{ position:relative; width:38px; height:38px; border-radius:50%; background:var(--bg-elevated); border:1.5px solid var(--verde-ink); color:var(--verde-ink); display:flex; align-items:center; justify-content:center; flex-shrink:0; z-index:1; }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .tx-node-icon{ border-color:var(--verde); color:var(--verde); } }
  :root[data-theme="dark"] .tx-node-icon{ border-color:var(--verde); color:var(--verde); }
  .tx-node.selected .tx-node-icon{ background:var(--verde-ink); color:#fff; }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .tx-node.selected .tx-node-icon{ background:var(--verde); color:var(--verde-ink); } }
  :root[data-theme="dark"] .tx-node.selected .tx-node-icon{ background:var(--verde); color:var(--verde-ink); }
  .tx-node-icon svg{ width:17px; height:17px; }
  .tx-node-label{ font-size:10.5px; font-weight:800; color:var(--verde-ink); text-transform:uppercase; letter-spacing:.03em; margin:9px 0 2px; }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .tx-node-label{ color:var(--verde); } }
  :root[data-theme="dark"] .tx-node-label{ color:var(--verde); }
  .tx-node-name{ font-size:10.5px; font-weight:700; color:var(--ink); line-height:1.3; margin:0 0 3px; }
  .tx-node-desc{ font-size:9.5px; color:var(--ink-dim); line-height:1.4; }
  .tx-node-status{ width:7px; height:7px; border-radius:50%; margin-top:6px; }
  .tx-node-status.done{ background:var(--verde-ink); }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .tx-node-status.done{ background:var(--verde); } }
  :root[data-theme="dark"] .tx-node-status.done{ background:var(--verde); }
  .tx-node-status.on{ background:var(--status-prog-ink); }
  .tx-node-status.off{ background:var(--line); }

  @media (max-width:640px){
    .tx-connector-svg{ display:none; }
    .tx-ciclo{ max-width:100%; flex:1 1 100%; }
  }

  /* Modal de detalhe do módulo (Trilha - Extra) */
  .tx-modal-overlay{ backdrop-filter:blur(2px); }
  .modal-box.tx-modal-box{ position:relative; padding:0; width:100%; max-width:840px; max-height:86vh; overflow:hidden; display:flex; flex-direction:column; transform:scale(.94) translateY(10px); opacity:0; transition:transform .22s cubic-bezier(.2,.8,.2,1), opacity .18s ease-out; }
  .modal-center.show .tx-modal-box{ transform:scale(1) translateY(0); opacity:1; }
  #tx-modal-inner{ display:flex; flex-direction:column; flex:1; min-height:0; }
  .tx-modal-head{ padding:24px 26px 20px; flex-shrink:0; background:linear-gradient(135deg, var(--bg-sunken), var(--bg-elevated)); border-bottom:1px solid var(--line); }
  .tx-modal-accent{ position:absolute; left:0; top:0; bottom:0; width:5px; }
  .tx-modal-accent.lideranca{ background:var(--verde-ink); } .tx-modal-accent.metodo{ background:var(--ink-faint); } .tx-modal-accent.ambos{ background:var(--verde); }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .tx-modal-accent.lideranca, :root:not([data-theme="light"]) .tx-modal-accent.ambos{ background:var(--verde); } }
  :root[data-theme="dark"] .tx-modal-accent.lideranca, :root[data-theme="dark"] .tx-modal-accent.ambos{ background:var(--verde); }
  .tx-modal-close{ position:absolute; top:16px; right:16px; appearance:none; background:var(--bg-elevated); border:1px solid var(--line); border-radius:50%; width:30px; height:30px; display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--ink-dim); font-size:17px; line-height:1; }
  .tx-modal-close:hover{ color:var(--ink); border-color:var(--ink-dim); }
  .tx-modal-head .d-eyebrow{ margin:0 0 8px; padding-left:2px; }
  .tx-modal-head .d-title{ margin:0 60px 12px 2px; font-size:20px; }
  .tx-modal-body{ padding:22px 26px 26px; overflow-y:auto; flex:1; min-height:0; }
  .tx-modal-body .d-desc{ margin:0 0 16px; }
  .tx-modal-body .meta-grid{ margin:0 0 18px; }
  .tx-modal-body .people-block{ margin-top:0; }
  @media (max-width:600px){ .tx-modal-head{ padding:20px 18px 16px; } .tx-modal-body{ padding:18px 18px 22px; } .tx-modal-head .d-title{ margin-right:46px; } }

  .btn-primary{ appearance:none; background:var(--verde-ink); color:#fff; border:none; border-radius:8px; padding:10px 18px; font-family:var(--font); font-weight:700; font-size:13px; cursor:pointer; white-space:nowrap; }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .btn-primary{ background:var(--verde); color:var(--verde-ink); } }
  :root[data-theme="dark"] .btn-primary{ background:var(--verde); color:var(--verde-ink); }
  .btn-secondary{ appearance:none; background:none; border:1px solid var(--line); border-radius:7px; padding:9px 15px; font-family:var(--font); font-weight:700; font-size:12.5px; color:var(--ink-dim); cursor:pointer; }
  .btn-secondary:hover{ border-color:var(--ink-dim); color:var(--ink); }
  .btn-danger{ appearance:none; background:none; border:none; color:var(--danger); font-family:var(--font); font-weight:700; font-size:12.5px; cursor:pointer; padding:9px 4px; }
  .btn-save{ appearance:none; background:var(--verde-ink); color:#fff; border:none; border-radius:7px; padding:9px 18px; font-family:var(--font); font-weight:700; font-size:12.5px; cursor:pointer; }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .btn-save{ background:var(--verde); color:var(--verde-ink); } }
  :root[data-theme="dark"] .btn-save{ background:var(--verde); color:var(--verde-ink); }
  .btn-ghost-sm{ appearance:none; background:none; border:none; cursor:pointer; color:var(--ink-faint); padding:4px; display:inline-flex; }
  .btn-ghost-sm:hover{ color:var(--ink); }
  .btn-ghost-sm svg{ width:14px; height:14px; display:block; }

  .card{ background:var(--bg-elevated); border:1px solid var(--line); border-radius:12px; padding:20px 22px; box-shadow:var(--shadow); }

  /* Colaboradores */
  .colab-layout{ display:grid; grid-template-columns:280px 1fr; gap:20px; align-items:start; }
  .colab-toolbar{ display:flex; gap:8px; margin-bottom:10px; }
  .colab-search{ flex:1; font-family:var(--font); font-size:13px; color:var(--ink); border:1px solid var(--line); border-radius:6px; padding:9px 12px; background:var(--bg-elevated); outline:none; }
  .colab-search:focus{ border-color:var(--verde-ink); }
  .colab-list{ display:flex; flex-direction:column; gap:2px; max-height:560px; overflow-y:auto; }
  .colab-btn{ appearance:none; text-align:left; background:none; border:none; cursor:pointer; font-family:var(--font); padding:9px 10px; border-radius:6px; display:flex; align-items:center; gap:8px; }
  .colab-btn:hover{ background:var(--bg-sunken); } .colab-btn.selected{ background:var(--bg-sunken); }
  .colab-btn-name{ font-size:13px; font-weight:600; color:var(--ink); flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .lead-dot{ width:6px; height:6px; border-radius:50%; background:var(--verde-ink); flex-shrink:0; }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .lead-dot{ background:var(--verde); } }
  :root[data-theme="dark"] .lead-dot{ background:var(--verde); }
  .colab-btn.inativo .colab-btn-name{ color:var(--ink-dim); text-decoration:line-through; }
  .colab-empty{ font-size:12.5px; color:var(--ink-dim); font-style:italic; padding:8px 10px; }
  .colab-detail{ background:var(--bg-elevated); border:1px solid var(--line); border-radius:12px; padding:26px 28px; min-height:300px; }
  .colab-head-row{ display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:6px; }
  .colab-detail h2{ font-weight:700; font-size:21px; margin:0 0 6px; color:var(--ink); }
  .colab-meta-row{ display:flex; gap:8px; flex-wrap:wrap; margin-bottom:18px; }
  .badge{ font-size:10.5px; font-weight:800; letter-spacing:.03em; text-transform:uppercase; padding:4px 10px; border-radius:20px; background:var(--bg-sunken); color:var(--ink-dim); }
  .badge.lideranca{ background:var(--status-done-bg); color:var(--status-done-ink); }
  .badge.ativo{ background:var(--status-done-bg); color:var(--status-done-ink); }
  .badge.inativo{ background:var(--status-todo-bg); color:var(--status-todo-ink); }

  /* Cadastro de colaboradores — tabela sistêmica */
  .mc-filters{ display:flex; flex-wrap:wrap; gap:8px; margin-bottom:12px; }
  .mc-filters input, .mc-filters select{ font-family:var(--font); font-size:13px; color:var(--ink); border:1px solid var(--line); border-radius:6px; padding:9px 12px; background:var(--bg-elevated); outline:none; }
  .mc-filters input:focus, .mc-filters select:focus{ border-color:var(--verde-ink); }
  .mc-filters input{ flex:1 1 220px; min-width:180px; }
  .mc-filters select{ flex:0 0 auto; min-width:150px; cursor:pointer; }
  .mc-result-count{ font-size:11.5px; font-weight:600; color:var(--ink-dim); margin:0 0 10px; }
  .mc-table-wrap{ overflow-x:auto; border:1px solid var(--line); border-radius:10px; }
  .mc-table{ width:100%; min-width:760px; border-collapse:collapse; font-size:12.5px; }
  .mc-table thead th{ text-align:left; font-size:10px; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:var(--ink-faint); padding:11px 14px; background:var(--bg-sunken); border-bottom:1px solid var(--line); white-space:nowrap; }
  .mc-table tbody td{ padding:11px 14px; border-bottom:1px solid var(--line); color:var(--ink); vertical-align:middle; }
  .mc-table tbody tr:last-child td{ border-bottom:none; }
  .mc-table tbody tr{ cursor:pointer; }
  .mc-table tbody tr:hover td{ background:var(--bg-sunken); }
  .mc-table tbody tr.mc-row-inativo td{ color:var(--ink-faint); }
  .mc-cell-name{ display:flex; align-items:center; gap:7px; font-weight:700; color:var(--ink); white-space:nowrap; }
  .mc-progress-mini{ display:flex; align-items:center; gap:8px; min-width:110px; }
  .mc-progress-mini .progress-bar-outer{ flex:1; min-width:56px; }
  .mc-progress-mini span{ font-size:11px; font-weight:700; color:var(--ink-dim); font-variant-numeric:tabular-nums; white-space:nowrap; }
  .mc-dim{ color:var(--ink-faint); font-style:italic; }
  .mc-chip-mini{ display:inline-flex; align-items:center; background:var(--bg-sunken); border:1px solid var(--line); border-radius:12px; padding:3px 9px; font-size:10.5px; font-weight:700; color:var(--ink-dim); margin:0 4px 4px 0; white-space:nowrap; }
  .mc-actions{ text-align:right; white-space:nowrap; }
  .mc-empty-cell{ padding:36px 20px !important; text-align:center; color:var(--ink-dim); font-size:13px; }
  @media (max-width:600px){ .mc-filters select{ flex:1 1 46%; min-width:0; } }
  .warn-box{ background:var(--warn-bg); color:var(--warn-ink); border-radius:8px; padding:10px 14px; font-size:12.5px; font-weight:600; margin-bottom:18px; display:flex; gap:8px; align-items:flex-start; }
  .warn-box svg{ width:15px; height:15px; flex-shrink:0; margin-top:1px; }
  .section-h{ font-weight:700; font-size:11px; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-dim); margin:22px 0 10px; display:flex; align-items:center; justify-content:space-between; }
  .section-h:first-of-type{ margin-top:4px; }
  .chip-row{ display:flex; flex-wrap:wrap; gap:8px; }
  .chip{ background:var(--bg-sunken); border:1px solid var(--line); border-radius:16px; padding:6px 8px 6px 13px; font-size:12.5px; font-weight:600; color:var(--ink); display:flex; align-items:center; gap:6px; }
  .chip button{ appearance:none; background:none; border:none; cursor:pointer; color:var(--ink-faint); font-size:14px; line-height:1; padding:2px; }
  .chip button:hover{ color:var(--danger); }
  .add-chip-select{ font-family:var(--font); font-size:12px; color:var(--ink-dim); border:1.5px dashed var(--line); border-radius:16px; padding:6px 12px; background:none; cursor:pointer; }
  .progress-group{ margin-bottom:16px; }
  .progress-group-title{ font-size:13px; font-weight:800; color:var(--ink); margin-bottom:8px; }
  .progress-cycle-title{ font-size:11px; font-weight:700; color:var(--verde-ink); text-transform:uppercase; letter-spacing:.04em; margin:10px 0 4px; }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .progress-cycle-title{ color:var(--verde); } }
  :root[data-theme="dark"] .progress-cycle-title{ color:var(--verde); }
  .prog-row{ display:flex; justify-content:space-between; align-items:center; gap:12px; padding:9px 0; border-bottom:1px solid var(--line); }
  .prog-row:last-child{ border-bottom:none; }
  .prog-name{ font-size:13px; font-weight:600; color:var(--ink); }
  .status-pill{ appearance:none; border:none; cursor:pointer; font-family:var(--font); font-size:9.5px; font-weight:800; letter-spacing:.04em; text-transform:uppercase; padding:4px 10px; border-radius:20px; white-space:nowrap; }
  .status-pill.done{ background:var(--status-done-bg); color:var(--status-done-ink); }
  .status-pill.on{ background:var(--status-prog-bg); color:var(--status-prog-ink); }
  .status-pill.off{ background:var(--status-todo-bg); color:var(--status-todo-ink); }
  .progress-bar-outer{ height:5px; border-radius:4px; background:var(--status-todo-bg); overflow:hidden; margin-top:4px; }
  .progress-bar-inner{ height:100%; background:var(--verde); border-radius:4px; }

  @media (max-width:760px){ .colab-layout{ grid-template-columns:1fr; } .colab-list{ max-height:220px; } }

  /* Trilhas / board */
  .trilha-card{ margin-bottom:22px; }
  .trilha-head{ display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:16px; }
  .trilha-titles h3{ font-size:17px; font-weight:700; margin:0 0 3px; color:var(--ink); }
  .trilha-titles p{ font-size:12.5px; color:var(--ink-dim); margin:0; max-width:560px; }
  .trilha-actions{ display:flex; gap:4px; flex-shrink:0; }
  .board{ display:flex; gap:14px; overflow-x:auto; padding:4px 4px 10px; }
  .cycle-col{ flex:0 0 250px; background:var(--bg-sunken); border:1px solid var(--line); border-radius:12px; padding:15px; display:flex; flex-direction:column; }
  .cycle-col.drag-over{ outline:2px dashed var(--verde-ink); outline-offset:2px; }
  .cycle-head{ display:flex; align-items:flex-start; gap:9px; padding-bottom:12px; margin-bottom:12px; border-bottom:1px solid var(--line); cursor:grab; }
  .cycle-head:active{ cursor:grabbing; }
  .cycle-icon{ width:30px; height:30px; border-radius:8px; background:var(--status-done-bg); color:var(--status-done-ink); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .cycle-icon svg{ width:16px; height:16px; }
  .cycle-titles{ flex:1; min-width:0; }
  .cycle-eyebrow{ font-size:9.5px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:var(--verde-ink); margin:0 0 2px; }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .cycle-eyebrow{ color:var(--verde); } }
  :root[data-theme="dark"] .cycle-eyebrow{ color:var(--verde); }
  .cycle-name{ font-size:14px; font-weight:700; color:var(--ink); line-height:1.25; }
  .module-node{ position:relative; padding:11px 12px 11px 16px; cursor:pointer; border-radius:10px; background:var(--bg-elevated); border:1px solid var(--line); margin-bottom:8px; }
  .module-node:hover{ border-color:var(--verde-ink); box-shadow:var(--shadow); }
  .module-node::before{ content:""; position:absolute; left:0; top:9px; bottom:9px; width:3px; border-radius:3px; background:var(--line); }
  .module-node.cat-lideranca::before{ background:var(--verde-ink); }
  .module-node.cat-metodo::before{ background:var(--ink-faint); }
  .module-node.cat-ambos::before{ background:var(--verde); }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .module-node.cat-lideranca::before, :root:not([data-theme="light"]) .module-node.cat-ambos::before{ background:var(--verde); } }
  :root[data-theme="dark"] .module-node.cat-lideranca::before, :root[data-theme="dark"] .module-node.cat-ambos::before{ background:var(--verde); }
  .module-etapa{ font-size:9px; font-weight:800; letter-spacing:.07em; text-transform:uppercase; color:var(--verde-ink); margin:0 0 4px; }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .module-etapa{ color:var(--verde); } }
  :root[data-theme="dark"] .module-etapa{ color:var(--verde); }
  .module-name{ font-size:12.5px; font-weight:700; color:var(--ink); line-height:1.35; margin-bottom:9px; }
  .module-meta{ display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap; }
  .module-cat-label{ display:flex; align-items:center; gap:5px; font-size:10px; font-weight:700; color:var(--ink-dim); }
  .cat-dot{ width:6px; height:6px; border-radius:50%; flex-shrink:0; }
  .cat-dot.lideranca{ background:var(--verde-ink); } .cat-dot.metodo{ background:var(--ink-faint); } .cat-dot.ambos{ background:var(--verde); }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .cat-dot.lideranca{ background:var(--verde); } }
  :root[data-theme="dark"] .cat-dot.lideranca{ background:var(--verde); }
  .add-tile{ appearance:none; width:100%; text-align:left; background:none; border:1.5px dashed var(--line); border-radius:8px; padding:9px 10px; margin-top:2px; font-family:var(--font); font-size:12px; font-weight:700; color:var(--ink-dim); cursor:pointer; display:flex; align-items:center; gap:7px; }
  .add-tile:hover{ border-color:var(--verde-ink); color:var(--verde-ink); }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .add-tile:hover{ border-color:var(--verde); color:var(--verde); } }
  :root[data-theme="dark"] .add-tile:hover{ border-color:var(--verde); color:var(--verde); }
  .add-tile svg{ width:13px; height:13px; flex-shrink:0; }
  .cycle-col.add-col{ flex:0 0 190px; align-items:center; justify-content:center; border-style:dashed; background:none; }
  .add-cycle-btn{ appearance:none; background:none; border:none; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:8px; color:var(--ink-dim); font-family:var(--font); font-weight:700; font-size:12px; padding:24px 8px; width:100%; }
  .add-cycle-btn:hover{ color:var(--verde-ink); }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .add-cycle-btn:hover{ color:var(--verde); } }
  :root[data-theme="dark"] .add-cycle-btn:hover{ color:var(--verde); }
  .plus-circle{ width:34px; height:34px; border-radius:50%; border:1.5px dashed var(--line); display:flex; align-items:center; justify-content:center; }
  .plus-circle svg{ width:14px; height:14px; }
  .empty-board{ padding:36px 20px; text-align:center; border:1.5px dashed var(--line); border-radius:12px; color:var(--ink-dim); font-size:13px; }
  .drag-hint{ font-size:11.5px; color:var(--ink-dim); margin:-10px 0 16px; }

  /* Unidades */
  .unidade-row{ display:flex; align-items:center; gap:10px; padding:11px 0; border-bottom:1px solid var(--line); }
  .unidade-row:last-child{ border-bottom:none; }
  .unidade-name{ flex:1; font-size:13.5px; font-weight:600; color:var(--ink); }
  .unidade-count{ font-size:11.5px; color:var(--ink-dim); }
  .unidade-add-row{ display:flex; gap:8px; margin-top:14px; }
  .unidade-add-row input{ flex:1; }

  .empty-state{ padding:56px 20px; text-align:center; color:var(--ink-dim); font-size:13.5px; }

  /* Drawer + modal (shared) */
  .overlay{ display:none; position:fixed; inset:0; background:rgba(10,15,12,.45); z-index:100; }
  .overlay.show{ display:block; }
  .drawer{ position:fixed; top:0; right:0; bottom:0; width:min(440px,100%); background:var(--bg-elevated); z-index:101; box-shadow:-16px 0 48px rgba(0,0,0,.18); transform:translateX(100%); transition:transform .28s cubic-bezier(.2,.7,.2,1); overflow-y:auto; padding:26px 26px 100px; }
  .drawer.show{ transform:translateX(0); }
  .modal-center{ display:none; position:fixed; inset:0; z-index:102; align-items:center; justify-content:center; padding:20px; }
  .modal-center.show{ display:flex; }
  .modal-box{ background:var(--bg-elevated); border-radius:12px; padding:26px 28px; width:100%; max-width:440px; max-height:88vh; overflow-y:auto; box-shadow:0 16px 48px rgba(0,0,0,.22); }
  .modal-box.colab-modal-box{ padding:0; max-width:760px; overflow:hidden; display:flex; flex-direction:column; transform:scale(.96) translateY(8px); opacity:0; transition:transform .2s cubic-bezier(.2,.8,.2,1), opacity .16s ease-out; }
  .modal-center.show .colab-modal-box{ transform:scale(1) translateY(0); opacity:1; }
  .colab-modal-head{ display:flex; justify-content:space-between; align-items:flex-start; gap:12px; padding:26px 28px 0; flex-shrink:0; }
  .colab-modal-head h3{ font-family:var(--font); font-weight:700; font-size:18px; color:var(--ink); margin:0; }
  .colab-modal-tabs.form-tabs{ margin:16px 28px 0; flex-shrink:0; }
  .colab-modal-body{ padding:20px 28px 4px; overflow-y:auto; flex:1; min-height:0; }
  .colab-modal-actions.panel-actions{ margin:0; padding:16px 28px 24px; border-top:1px solid var(--line); flex-shrink:0; }
  @media (max-width:600px){ .colab-modal-head, .colab-modal-tabs.form-tabs, .colab-modal-body, .colab-modal-actions.panel-actions{ padding-inline:18px; } }
  .panel-head{ display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:18px; }
  .panel-head h3{ font-family:var(--font); font-weight:700; font-size:17px; color:var(--ink); margin:0; }
  .panel-close{ appearance:none; background:none; border:none; cursor:pointer; font-size:20px; line-height:1; color:var(--ink-faint); padding:0; }
  .panel-close:hover{ color:var(--ink); }
  .field{ margin-bottom:14px; }
  .field label{ display:block; font-size:11px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:var(--ink-dim); margin-bottom:6px; }
  .field input[type=text], .field input[type=email], .field input[type=number], .field select, .field textarea{
    width:100%; font-family:var(--font); font-size:13.5px; color:var(--ink); background:var(--bg); border:1px solid var(--line); border-radius:7px; padding:9px 11px; outline:none;
  }
  .field textarea{ resize:vertical; min-height:64px; }
  .field input:focus, .field select:focus, .field textarea:focus{ border-color:var(--verde-ink); }
  .field-row{ display:flex; gap:10px; } .field-row .field{ flex:1; min-width:0; }
  .field-check{ display:flex; align-items:center; gap:8px; font-size:13px; font-weight:600; color:var(--ink); margin-bottom:14px; }
  .field-check input{ width:16px; height:16px; }
  .icon-pick{ display:grid; grid-template-columns:repeat(5,1fr); gap:6px; }
  .icon-opt{ appearance:none; border:1.5px solid var(--line); background:var(--bg); border-radius:8px; padding:8px 0; cursor:pointer; display:flex; align-items:center; justify-content:center; color:var(--ink-dim); }
  .icon-opt svg{ width:16px; height:16px; }
  .icon-opt.selected{ border-color:var(--verde-ink); color:var(--verde-ink); background:var(--status-done-bg); }
  .panel-actions{ display:flex; justify-content:space-between; align-items:center; gap:10px; margin-top:22px; padding-top:18px; border-top:1px solid var(--line); }
  .form-tabs{ display:flex; gap:18px; margin:0 0 20px; border-bottom:1px solid var(--line); }
  .form-tab{ appearance:none; background:none; border:none; cursor:pointer; font-family:var(--font); font-weight:700; font-size:12.5px; color:var(--ink-dim); padding:0 0 10px; border-bottom:2px solid transparent; }
  .form-tab:hover{ color:var(--ink); }
  .form-tab.active{ color:var(--verde-ink); border-bottom-color:var(--verde-ink); }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .form-tab.active{ color:var(--verde); border-bottom-color:var(--verde); } }
  :root[data-theme="dark"] .form-tab.active{ color:var(--verde); border-bottom-color:var(--verde); }
  .form-tab-panel{ display:none; }
  .form-tab-panel.active{ display:block; }
  .hint{ font-size:11.5px; color:var(--ink-dim); margin:-8px 0 14px; }
  .save-toast{ position:fixed; bottom:22px; left:50%; transform:translateX(-50%) translateY(12px); background:var(--verde-ink); color:#fff; font-size:12.5px; font-weight:700; padding:10px 18px; border-radius:20px; z-index:200; opacity:0; pointer-events:none; transition:opacity .2s ease, transform .2s ease; }
  .save-toast.show{ opacity:1; transform:translateX(-50%) translateY(0); }
  .confirm-box p{ font-size:13px; color:var(--ink-dim); line-height:1.5; margin:0 0 20px; }
  .foot{ margin-top:30px; font-weight:600; font-size:11px; color:var(--ink-dim); text-align:right; letter-spacing:.03em; }

  /* Login gate */
  .login-screen{ position:fixed; inset:0; z-index:500; display:none; align-items:center; justify-content:center; background:var(--bg); padding:20px; }
  .login-card{ width:100%; max-width:380px; background:var(--bg-elevated); border:1px solid var(--line); border-radius:14px; box-shadow:0 16px 48px rgba(0,0,0,.14); padding:32px 30px 30px; animation:windowIn .3s ease-out; }
  .login-brand{ display:flex; align-items:center; gap:10px; margin-bottom:24px; }
  .login-brand-mark{ width:38px; height:38px; border-radius:10px; background:var(--verde-ink); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:15px; flex-shrink:0; }
  @media (prefers-color-scheme: dark){ :root:not([data-theme="light"]) .login-brand-mark{ background:var(--verde); color:var(--verde-ink); } }
  :root[data-theme="dark"] .login-brand-mark{ background:var(--verde); color:var(--verde-ink); }
  .login-title{ font-size:19px; font-weight:800; color:var(--ink); margin:0 0 6px; }
  .login-sub{ font-size:12.5px; color:var(--ink-dim); margin:0 0 22px; line-height:1.5; }
  .login-error{ font-size:12px; font-weight:700; color:var(--danger); background:rgba(179,38,30,.08); border-radius:6px; padding:8px 11px; margin:-4px 0 14px; }
  .login-submit{ width:100%; margin-top:6px; padding:11px 18px; font-size:13.5px; }
  .sidebar-logout{ appearance:none; background:none; border:none; cursor:pointer; font-family:var(--font); font-weight:700; font-size:11px; color:var(--ink-faint); padding:9px 12px 0; text-align:left; width:100%; }
  .sidebar-logout:hover{ color:var(--danger); }
</style>
</head>
<body>
<?php if (!$authed): ?>
<div class="login-screen" id="login-screen" style="display:flex">
  <form class="login-card" action="login.php" method="post" autocomplete="on">
    <div class="login-brand">
      <div class="login-brand-mark">P</div>
      <div>
        <span class="brand-name">Prestes</span>
        <div class="brand-product">Juntos <b>a distância</b></div>
      </div>
    </div>
    <h1 class="login-title">Acesso restrito</h1>
    <p class="login-sub">Entre com suas credenciais de administrador para continuar.</p>
    <?php if ($loginError): ?><p class="login-error">Usuário ou senha incorretos.</p><?php endif; ?>
    <div class="field">
      <label for="login-user">Usuário</label>
      <input type="text" id="login-user" name="user" autocomplete="username" required>
    </div>
    <div class="field">
      <label for="login-pass">Senha</label>
      <input type="password" id="login-pass" name="pass" autocomplete="current-password" required>
    </div>
    <button type="submit" class="btn-primary login-submit">Entrar</button>
  </form>
</div>
<?php else: ?>
<div class="app-shell" id="app-shell">
  <div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="brand-mark">
        <img class="logo-img" src="data:image/png;base64,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" alt="Prestes">
        <div>
          <span class="brand-name">Prestes</span>
          <div class="brand-product">Juntos <b>a distância</b></div>
        </div>
      </div>
      <button class="sidebar-close" onclick="closeSidebar()" aria-label="Fechar menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <nav class="sidebar-nav" aria-label="Navegação principal">
      <div class="nav-group">
        <p class="nav-group-label">Visão geral</p>
        <button class="nav-item active" id="navv-colab" aria-current="page" onclick="navTo('view','colaborador')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a5 5 0 0 1 5-5h6a5 5 0 0 1 5 5v1"/></svg>
          Colaboradores
        </button>
        <div class="nav-submenu" id="navsub-view-trilhas">
          <button class="nav-submenu-toggle" onclick="toggleNavSubmenu('view-trilhas')" aria-expanded="false" aria-controls="navsub-view-trilhas-list">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="6" r="2.4"/><circle cx="6" cy="18" r="2.4"/><circle cx="18" cy="6" r="2.4"/><path d="M6 8.4V15.6M8.4 6H15.6a2.4 2.4 0 0 1 2.4 2.4V6"/><path d="M8.4 18H14a4 4 0 0 0 4-4v-.2"/></svg>
            <span class="nav-submenu-label">Trilhas</span>
            <svg class="nav-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
          </button>
          <div class="nav-submenu-list" id="navsub-view-trilhas-list"><div>
            <button class="nav-item nav-subitem" id="navv-trilhas" onclick="navTo('view','trilhas')">Trilhas - Geral</button>
            <button class="nav-item nav-subitem" id="navv-trilhaExtra" onclick="navTo('view','trilhaExtra')">Trilha - Extra</button>
          </div></div>
        </div>
      </div>

      <div class="nav-group">
        <p class="nav-group-label">Cadastros</p>
        <div class="nav-submenu" id="navsub-manage-trilhas">
          <button class="nav-submenu-toggle" onclick="toggleNavSubmenu('manage-trilhas')" aria-expanded="false" aria-controls="navsub-manage-trilhas-list">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="6" r="2.4"/><circle cx="6" cy="18" r="2.4"/><circle cx="18" cy="6" r="2.4"/><path d="M6 8.4V15.6M8.4 6H15.6a2.4 2.4 0 0 1 2.4 2.4V6"/><path d="M8.4 18H14a4 4 0 0 0 4-4v-.2"/></svg>
            <span class="nav-submenu-label">Trilhas</span>
            <svg class="nav-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
          </button>
          <div class="nav-submenu-list" id="navsub-manage-trilhas-list"><div>
            <button class="nav-item nav-subitem" id="navm-trilhas" onclick="navTo('manage','trilhas')">Trilhas</button>
            <button class="nav-item nav-subitem" id="navm-ciclos" onclick="navTo('manage','ciclos')">Ciclos</button>
            <button class="nav-item nav-subitem" id="navm-modulos" onclick="navTo('manage','modulos')">Módulos</button>
          </div></div>
        </div>
        <div class="nav-submenu" id="navsub-manage-geral">
          <button class="nav-submenu-toggle" onclick="toggleNavSubmenu('manage-geral')" aria-expanded="false" aria-controls="navsub-manage-geral-list">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7.5" height="7.5" rx="1.2"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="1.2"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="1.2"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1.2"/></svg>
            <span class="nav-submenu-label">Cadastros Gerais</span>
            <svg class="nav-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
          </button>
          <div class="nav-submenu-list" id="navsub-manage-geral-list"><div>
            <button class="nav-item nav-subitem" id="navm-colab" onclick="navTo('manage','colaborador')">Colaboradores</button>
            <button class="nav-item nav-subitem" id="navm-unidades" onclick="navTo('manage','unidades')">Unidades</button>
          </div></div>
        </div>
      </div>
    </nav>

    <div class="sidebar-foot">
      <p class="foot" id="foot-summary"></p>
      <a class="sidebar-logout" href="logout.php" style="text-decoration:none; display:block;">Sair</a>
    </div>
  </aside>

  <div class="main-col">
    <div class="topbar">
      <button class="topbar-hamburger" onclick="openSidebar()" aria-label="Abrir menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <div class="brand-mark">
        <span class="brand-name">Prestes</span>
        <span class="brand-product-inline">Juntos a distância</span>
      </div>
    </div>

    <main class="main-content">
      <div class="kpi-row" id="kpi-row"></div>

      <!-- ============ VISÃO GERAL (somente consulta) ============ -->
      <div class="view active" id="view-v-colaborador" role="tabpanel">
        <div class="window">
          <div class="window-head">
            <div>
              <p class="window-title">Colaboradores</p>
              <p class="window-sub">Consulte o panorama de cada colaborador — nada aqui é editável.</p>
            </div>
          </div>
          <div class="window-body">
            <div class="colab-layout">
              <div>
                <div class="colab-toolbar">
                  <input type="text" class="colab-search" id="vcolab-search" placeholder="Buscar colaborador…" aria-label="Buscar colaborador" oninput="renderViewColabList(this.value)">
                </div>
                <div class="colab-list" id="vcolab-list"></div>
              </div>
              <div class="colab-detail" id="vcolab-detail"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="view" id="view-v-trilhas">
        <div class="window">
          <div class="window-head">
            <div>
              <p class="window-title">Trilhas</p>
              <p class="window-sub">Panorama dos ciclos e módulos de cada trilha — nada aqui é editável.</p>
            </div>
          </div>
          <div class="window-body">
            <div id="vtrilhas-list"></div>
          </div>
        </div>
      </div>

      <div class="view" id="view-v-trilhaExtra">
        <div class="window">
          <div class="window-head">
            <div>
              <p class="window-title">Trilha - Extra</p>
              <p class="window-sub">Visualização sequencial dos ciclos e módulos da trilha escolhida — respeitando a ordem de cadastro, sem rolagem lateral.</p>
            </div>
            <div class="tx-select-wrap">
              <label for="tx-trilha-select" class="tx-select-label">Trilha</label>
              <select id="tx-trilha-select" onchange="selectTrilhaExtra(this.value)"></select>
            </div>
          </div>
          <div class="window-body">
            <div id="vtrilhaextra-roadmap"></div>
          </div>
        </div>
      </div>

      <!-- ============ CADASTROS (gestão) ============ -->
      <div class="view" id="view-m-colaborador">
        <div class="window">
          <div class="window-head">
            <div>
              <p class="window-title">Cadastro de colaboradores</p>
              <p class="window-sub">Registro completo da equipe — consulte, filtre e edite cada colaborador.</p>
            </div>
            <button class="reg-new-btn" onclick="openColabForm(null)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>Novo colaborador</button>
          </div>
          <div class="window-body">
            <div class="mc-filters">
              <input type="text" class="reg-search" id="mc-search" placeholder="Buscar por nome, cargo ou e-mail…" aria-label="Buscar colaborador" oninput="renderManageColabTable()">
              <select id="mc-filter-unidade" aria-label="Filtrar por unidade" onchange="renderManageColabTable()"><option value="">Todas as unidades</option></select>
              <select id="mc-filter-status" aria-label="Filtrar por status" onchange="renderManageColabTable()">
                <option value="">Ativos e inativos</option>
                <option value="ativo">Somente ativos</option>
                <option value="inativo">Somente inativos</option>
              </select>
              <select id="mc-filter-lideranca" aria-label="Filtrar por liderança" onchange="renderManageColabTable()">
                <option value="">Liderança e time</option>
                <option value="sim">Somente liderança</option>
                <option value="nao">Somente time</option>
              </select>
            </div>
            <p class="mc-result-count" id="mc-result-count"></p>
            <div class="mc-table-wrap">
              <table class="mc-table">
                <thead>
                  <tr>
                    <th>Nome</th><th>Cargo</th><th>Unidade</th><th>Status</th><th>Trilhas</th><th>Progresso</th><th aria-label="Ações"></th>
                  </tr>
                </thead>
                <tbody id="mc-table-body"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="view" id="view-m-trilhas">
        <div class="window">
          <div class="window-head">
            <div>
              <p class="window-title">Cadastro de trilhas</p>
              <p class="window-sub">Localize uma trilha para editar, ou cadastre uma nova.</p>
            </div>
            <button class="reg-new-btn" onclick="openTrilhaForm(null)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>Nova trilha</button>
          </div>
          <div class="window-body">
            <div class="reg-toolbar">
              <input type="text" class="reg-search" id="trilhas-search" placeholder="Localizar trilha…" oninput="renderTrilhas(this.value)">
            </div>
            <p class="drag-hint" id="trilhas-hint">Arraste o cabeçalho de um ciclo para o quadro de outra trilha para movê-lo — ou use "Editar" no ciclo.</p>
            <div id="trilhas-list"></div>
          </div>
        </div>
      </div>

      <div class="view" id="view-m-ciclos">
        <div class="window">
          <div class="window-head">
            <div>
              <p class="window-title">Cadastro de ciclos</p>
              <p class="window-sub">Localize um ciclo para editar, ou cadastre um novo.</p>
            </div>
            <button class="reg-new-btn" onclick="openCycleModal(null)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>Novo ciclo</button>
          </div>
          <div class="window-body">
            <div class="reg-toolbar">
              <input type="text" class="reg-search" id="ciclos-search" placeholder="Localizar ciclo…" oninput="renderCadastroCiclos(this.value)">
            </div>
            <div class="reg-list" id="ciclos-reg-list"></div>
          </div>
        </div>
      </div>

      <div class="view" id="view-m-modulos">
        <div class="window">
          <div class="window-head">
            <div>
              <p class="window-title">Cadastro de módulos</p>
              <p class="window-sub">Localize um módulo para editar, ou cadastre um novo.</p>
            </div>
            <button class="reg-new-btn" onclick="openModuleForm(null)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>Novo módulo</button>
          </div>
          <div class="window-body">
            <div class="reg-toolbar">
              <input type="text" class="reg-search" id="modulos-search" placeholder="Localizar módulo…" oninput="renderCadastroModulos(this.value)">
            </div>
            <div class="reg-list" id="modulos-reg-list"></div>
          </div>
        </div>
      </div>

      <div class="view" id="view-m-unidades">
        <div class="window">
          <div class="window-head">
            <div>
              <p class="window-title">Unidades / diretorias</p>
              <p class="window-sub">Lista fechada usada no cadastro do colaborador.</p>
            </div>
          </div>
          <div class="window-body">
            <div id="unidades-list"></div>
            <div class="unidade-add-row">
              <input type="text" id="unidade-new-nome" placeholder="Nova unidade / diretoria" aria-label="Nome da nova unidade" onkeydown="if(event.key==='Enter') addUnidade();">
              <button class="reg-new-btn" onclick="addUnidade()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>Adicionar</button>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- Modal: colaborador -->
<div class="overlay" id="overlay-colab" onclick="closeColabForm()"></div>
<div class="modal-center" id="modal-colab" onclick="if(event.target===this) closeColabForm();">
  <div class="modal-box colab-modal-box">
    <div class="colab-modal-head">
      <h3 id="colab-form-title">Colaborador</h3>
      <button class="panel-close" onclick="closeColabForm()">×</button>
    </div>
    <div class="form-tabs colab-modal-tabs" id="colab-form-tabs">
      <button type="button" class="form-tab active" data-tab="dados" onclick="switchColabFormTab('dados')">Dados cadastrais</button>
      <button type="button" class="form-tab" data-tab="trilhas" onclick="switchColabFormTab('trilhas')">Trilhas &amp; progresso</button>
    </div>

    <div class="colab-modal-body">
      <div class="form-tab-panel active" id="cftab-panel-dados">
        <form onsubmit="return false;">
          <div class="field"><label for="cf-nome">Nome</label><input type="text" id="cf-nome" placeholder="Nome completo"></div>
          <div class="field-row">
            <div class="field"><label for="cf-email">E-mail</label><input type="email" id="cf-email" placeholder="nome@prestes.com.br"></div>
            <div class="field"><label for="cf-cargo">Cargo / função</label><input type="text" id="cf-cargo" placeholder="Ex.: Coordenador"></div>
          </div>
          <div class="field-row">
            <div class="field"><label for="cf-unidade">Unidade / diretoria</label><select id="cf-unidade"></select></div>
            <div class="field"><label for="cf-gestor">Gestor direto</label><input type="text" id="cf-gestor"></div>
          </div>
          <div class="field-row">
            <div class="field"><label for="cf-admissao">Data de admissão</label><input type="text" id="cf-admissao" placeholder="Ex.: 03/2022"></div>
            <div class="field" style="display:flex; flex-direction:column; justify-content:center; gap:10px; padding-top:22px;">
              <label class="field-check" style="margin:0;"><input type="checkbox" id="cf-lideranca"> É liderança</label>
              <label class="field-check" style="margin:0;"><input type="checkbox" id="cf-ativo" checked> Ativo</label>
            </div>
          </div>
          <div class="field"><label for="cf-nota">Nota / alerta (opcional)</label><textarea id="cf-nota" placeholder="Ex.: afastamento previsto, observação da liderança…"></textarea></div>
        </form>
      </div>

      <div class="form-tab-panel" id="cftab-panel-trilhas">
        <div id="colab-form-trilhas-content"></div>
      </div>
    </div>

    <div class="panel-actions colab-modal-actions" id="cf-actions-dados">
      <button type="button" class="btn-danger" id="colab-delete-btn" onclick="askDeleteColab()">Excluir colaborador</button>
      <div style="display:flex;gap:8px;">
        <button type="button" class="btn-secondary" onclick="closeColabForm()">Cancelar</button>
        <button type="button" class="btn-save" onclick="saveColab()">Salvar</button>
      </div>
    </div>
    <div class="panel-actions colab-modal-actions" id="cf-actions-trilhas" style="display:none;">
      <div></div>
      <button type="button" class="btn-secondary" onclick="closeColabForm()">Fechar</button>
    </div>
  </div>
</div>

<!-- Modal: trilha -->
<div class="overlay" id="overlay-trilha" onclick="closeTrilhaForm()"></div>
<div class="modal-center" id="modal-trilha">
  <div class="modal-box">
    <div class="panel-head"><h3 id="trilha-form-title">Trilha</h3><button class="panel-close" onclick="closeTrilhaForm()">×</button></div>
    <div class="field"><label for="tf-nome">Nome da trilha</label><input type="text" id="tf-nome" placeholder="Ex.: Trilha de Liderança — Operações"></div>
    <div class="field"><label for="tf-desc">Descrição (opcional)</label><textarea id="tf-desc"></textarea></div>
    <div class="panel-actions">
      <button type="button" class="btn-danger" id="trilha-delete-btn" onclick="askDeleteTrilha()">Excluir trilha</button>
      <div style="display:flex;gap:8px;">
        <button type="button" class="btn-secondary" onclick="closeTrilhaForm()">Cancelar</button>
        <button type="button" class="btn-save" onclick="saveTrilha()">Salvar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: ciclo -->
<div class="overlay" id="overlay-cycle" onclick="closeCycleModal()"></div>
<div class="modal-center" id="modal-cycle">
  <div class="modal-box">
    <div class="panel-head"><h3 id="cycle-modal-title">Ciclo</h3><button class="panel-close" onclick="closeCycleModal()">×</button></div>
    <div class="field"><label for="cf2-trilha">Trilha</label><select id="cf2-trilha"></select></div>
    <div class="field"><label for="cf2-nome">Nome do ciclo</label><input type="text" id="cf2-nome" placeholder="Ex.: Liderar a Operação"></div>
    <div class="field"><label for="cf2-tema">Subtítulo / tema (opcional)</label><input type="text" id="cf2-tema"></div>
    <div class="field"><label for="cf2-ordem">Ordem</label><input type="number" id="cf2-ordem" step="1"></div>
    <div class="field"><label>Ícone</label><div class="icon-pick" id="cf2-icon-pick"></div></div>
    <div class="panel-actions">
      <button type="button" class="btn-danger" id="cycle-delete-btn" onclick="askDeleteCycle()">Excluir ciclo</button>
      <div style="display:flex;gap:8px;">
        <button type="button" class="btn-secondary" onclick="closeCycleModal()">Cancelar</button>
        <button type="button" class="btn-save" onclick="saveCycle()">Salvar</button>
      </div>
    </div>
  </div>
</div>

<!-- Drawer: módulo -->
<div class="overlay" id="overlay-module" onclick="closeModuleDrawer()"></div>
<div class="drawer" id="drawer-module">
  <div class="panel-head"><h3 id="module-drawer-title">Módulo</h3><button class="panel-close" onclick="closeModuleDrawer()">×</button></div>
  <form onsubmit="return false;">
    <div class="field"><label for="mf-ciclo">Ciclo</label><select id="mf-ciclo"></select></div>
    <div class="field-row">
      <div class="field"><label for="mf-etapa">Etapa (ex.: Mês 1)</label><input type="text" id="mf-etapa"></div>
      <div class="field"><label for="mf-ordem">Ordem</label><input type="number" id="mf-ordem" step="1"></div>
    </div>
    <div class="field"><label for="mf-nome">Nome do módulo</label><input type="text" id="mf-nome"></div>
    <div class="field-row">
      <div class="field"><label for="mf-categoria">Categoria</label><select id="mf-categoria"><option>Liderança</option><option>Método</option><option>Liderança e Método</option></select></div>
      <div class="field"><label for="mf-status">Status da turma</label><select id="mf-status"><option>A iniciar</option><option>Em andamento</option><option>Concluído</option></select></div>
    </div>
    <div class="field"><label for="mf-desc">Descrição</label><textarea id="mf-desc"></textarea></div>
    <div class="field-row">
      <div class="field"><label for="mf-mentor">Mentor(es)</label><input type="text" id="mf-mentor"></div>
      <div class="field"><label for="mf-formato">Formato</label><input type="text" id="mf-formato"></div>
    </div>
    <div class="field-row">
      <div class="field"><label for="mf-publico">Público-alvo</label><input type="text" id="mf-publico"></div>
      <div class="field"><label for="mf-carga">Carga horária</label><input type="text" id="mf-carga"></div>
    </div>
    <div class="field"><label for="mf-inicio">Início previsto</label><input type="text" id="mf-inicio"></div>
    <div class="field"><label for="mf-material">Link do material</label><input type="text" id="mf-material" placeholder="https://…"></div>
    <div class="panel-actions">
      <button type="button" class="btn-danger" id="module-delete-btn" onclick="askDeleteModule()">Excluir módulo</button>
      <div style="display:flex;gap:8px;">
        <button type="button" class="btn-secondary" onclick="closeModuleDrawer()">Cancelar</button>
        <button type="button" class="btn-save" onclick="saveModule()">Salvar</button>
      </div>
    </div>
  </form>
</div>

<!-- Confirm modal -->
<div class="overlay" id="overlay-confirm" onclick="closeConfirm()"></div>
<div class="modal-center" id="modal-confirm">
  <div class="modal-box confirm-box">
    <div class="panel-head"><h3 id="confirm-title">Confirmar</h3><button class="panel-close" onclick="closeConfirm()">×</button></div>
    <p id="confirm-text"></p>
    <div class="panel-actions" style="justify-content:flex-end;">
      <button type="button" class="btn-secondary" onclick="closeConfirm()">Cancelar</button>
      <button type="button" class="btn-save" style="background:var(--danger);color:#fff;" onclick="confirmActionRun()">Excluir</button>
    </div>
  </div>
</div>

<!-- Modal: detalhe do módulo (Trilha - Extra) -->
<div class="overlay tx-modal-overlay" id="overlay-tx-detail" onclick="closeTxModuleModal()"></div>
<div class="modal-center" id="modal-tx-detail" onclick="if(event.target===this) closeTxModuleModal();">
  <div class="modal-box tx-modal-box">
    <div id="tx-modal-inner"></div>
  </div>
</div>

<div class="save-toast" id="save-toast"></div>

<script>
/* ---------- Icons ---------- */
const ICONS = {
  compass:  '<circle cx="12" cy="12" r="9"/><polygon points="15,9 13,13 9,15 11,11" fill="currentColor" stroke="none"/>',
  users:    '<circle cx="8.7" cy="8.3" r="3"/><circle cx="16.3" cy="9.4" r="2.3"/><path d="M3 19.5c0-3.7 2.6-5.8 5.7-5.8s5.7 2.1 5.7 5.8"/><path d="M14.8 14.2c2.4.3 3.7 2.1 3.7 5"/>',
  hardhat:  '<path d="M4 15.5a8 8 0 0 1 16 0z"/><path d="M2 15.5h20"/><path d="M12 5.5v4"/>',
  bulb:     '<path d="M9.3 18.2h5.4"/><path d="M10.1 21h3.8"/><path d="M12 3a6 6 0 0 0-3.3 11c.6.5.9 1.2.9 2h4.8c0-.8.3-1.5.9-2A6 6 0 0 0 12 3z"/>',
  layers:   '<polygon points="12,3.3 20.5,8 12,12.7 3.5,8"/><path d="M3.5 13l8.5 4.7 8.5-4.7"/><path d="M3.5 17.3L12 22l8.5-4.7"/>',
  target:   '<circle cx="12" cy="12" r="8.3"/><circle cx="12" cy="12" r="4.6"/><circle cx="12" cy="12" r="1"/>',
  shield:   '<path d="M12 3l7 3v5.5c0 4.6-3 7.7-7 9-4-1.3-7-4.4-7-9V6z"/><path d="M9 12l2 2 4-4.3"/>',
  message:  '<path d="M4 5h16v10.5H10l-4.2 3.6V15.5H4z"/>'
};
const ICON_KEYS = Object.keys(ICONS);
function iconSvg(key){ return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">' + (ICONS[key] || ICONS.layers) + '</svg>'; }
function trashSvg(){ return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"/><path d="M9 7V4h6v3"/><path d="M6 7l1 13h10l1-13"/></svg>'; }
function editSvg(){ return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>'; }
function warnSvg(){ return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.9 2.6 18a1.8 1.8 0 0 0 1.6 2.7h15.6a1.8 1.8 0 0 0 1.6-2.7L13.7 3.9a1.8 1.8 0 0 0-3.4 0z"/></svg>'; }

/* ---------- State ---------- */
let db = null;
let unidades = [], trilhas = [], ciclos = [], modulos = [], colaboradores = [], progresso = [];
let dbUnavailable = false;
let currentMode = 'view';
let currentTab = 'colaborador';
let colabSelected = null;
let editingColabId = null;
let editingTrilhaId = null;
let editingCycleId = null;
let editingModuleId = null;
let confirmActionFn = null;
let unidadeEditingId = null;
let trilhaExtraSelected = null;
let txSelectedModulo = null;
const loadedCols = new Set();
function isLoaded(name){ return dbUnavailable || loadedCols.has(name); }

function esc(s){ return (s ?? '').toString().replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function norm(s){ return (s ?? '').toString().normalize('NFD').replace(/[̀-ͯ]/g,'').toLowerCase(); }
function toast(msg){ const t = document.getElementById('save-toast'); t.textContent = msg; t.classList.add('show'); clearTimeout(toast._t); toast._t = setTimeout(()=>t.classList.remove('show'), 2000); }

/* ---------- Camada de dados (API PHP + MySQL própria, no lugar do capability
   "db" do Claude Artifact) ---------- */
function makeDbShim(){
  const listeners = {};
  function apiUrl(name, id, extra){
    let u = 'api/db.php?collection=' + encodeURIComponent(name);
    if(id != null) u += '&id=' + encodeURIComponent(id);
    if(extra) u += extra;
    return u;
  }
  async function apiCall(url, method, body){
    const opts = { method, credentials: 'same-origin' };
    if(body !== undefined){ opts.headers = {'Content-Type':'application/json'}; opts.body = JSON.stringify(body); }
    const res = await fetch(url, opts);
    if(!res.ok) throw new Error('api_error_'+res.status);
    return res.status === 204 ? null : res.json();
  }
  async function refresh(name){
    const l = listeners[name];
    if(!l) return;
    try{
      const data = await apiCall(apiUrl(name), 'GET');
      const docs = (data.docs||[]).map(d => { const {id, ...rest} = d; return { id, data: () => rest }; });
      l.success({ docs });
    }catch(e){ l.error && l.error(e); }
  }
  return {
    collection(name){
      return {
        onSnapshot(success, error){
          listeners[name] = { success, error };
          refresh(name);
        },
        async add(data){
          const created = await apiCall(apiUrl(name), 'POST', data);
          await refresh(name);
          return { id: created.id };
        }
      };
    },
    doc(path){
      const idx = path.indexOf('/');
      const name = path.slice(0, idx);
      const id = path.slice(idx+1);
      return {
        async update(data){
          await apiCall(apiUrl(name, id), 'PUT', data);
          await refresh(name);
        },
        async set(data){
          await apiCall(apiUrl(name, id, '&mode=set'), 'PUT', data);
          await refresh(name);
        },
        async delete(){
          await apiCall(apiUrl(name, id), 'DELETE');
          await refresh(name);
        }
      };
    }
  };
}

/* ---------- Boot ---------- */
async function boot(){
  renderAll();
  db = makeDbShim();
  db.collection('unidades').onSnapshot(s => { unidades = s.docs.map(d=>({id:d.id, ...d.data()})); loadedCols.add('unidades'); renderAll(); }, ()=>{ dbUnavailable = true; renderAll(); });
  db.collection('trilhas').onSnapshot(s => { trilhas = s.docs.map(d=>({id:d.id, ...d.data()})); loadedCols.add('trilhas'); renderAll(); }, ()=>{ dbUnavailable = true; renderAll(); });
  db.collection('ciclos').onSnapshot(s => { ciclos = s.docs.map(d=>({id:d.id, ...d.data()})); loadedCols.add('ciclos'); renderAll(); }, ()=>{ dbUnavailable = true; renderAll(); });
  db.collection('modulos').onSnapshot(s => { modulos = s.docs.map(d=>({id:d.id, ...d.data()})); loadedCols.add('modulos'); renderAll(); }, ()=>{ dbUnavailable = true; renderAll(); });
  db.collection('colaboradores').onSnapshot(s => { colaboradores = s.docs.map(d=>({id:d.id, ...d.data()})); loadedCols.add('colaboradores'); renderAll(); }, ()=>{ dbUnavailable = true; renderAll(); });
  db.collection('progresso').onSnapshot(s => { progresso = s.docs.map(d=>({id:d.id, ...d.data()})); loadedCols.add('progresso'); renderAll(); }, ()=>{ dbUnavailable = true; renderAll(); });
}

/* ---------- Derived helpers ---------- */
function sortedTrilhas(){ return [...trilhas].sort((a,b)=>(a.ordem??0)-(b.ordem??0) || (a.nome||'').localeCompare(b.nome||'')); }
function sortedCiclosOf(trilhaId){ return ciclos.filter(c=>c.trilhaId===trilhaId).sort((a,b)=>(a.ordem??0)-(b.ordem??0)); }
function modulosOf(cicloId){ return modulos.filter(m=>m.cicloId===cicloId).sort((a,b)=>(a.ordem??0)-(b.ordem??0)); }
function cicloById(id){ return ciclos.find(c=>c.id===id); }
function trilhaById(id){ return trilhas.find(t=>t.id===id); }
function unidadeById(id){ return unidades.find(u=>u.id===id); }
function moduloById(id){ return modulos.find(m=>m.id===id); }
function progressoDoc(colabId, modId){ return progresso.find(p=>p.colaboradorId===colabId && p.moduloId===modId); }
function modulosDaTrilha(trilhaId){ const cids = ciclos.filter(c=>c.trilhaId===trilhaId).map(c=>c.id); return modulos.filter(m=>cids.includes(m.cicloId)); }
function catDotClass(cat){ if(cat==='Liderança') return 'lideranca'; if(cat==='Método') return 'metodo'; return 'ambos'; }
function statusPillClass(status){ if(status==='Concluído') return 'done'; if(status==='Em andamento') return 'on'; return 'off'; }
function nextStatus(status){ if(status==='Não iniciado' || !status) return 'Em andamento'; if(status==='Em andamento') return 'Concluído'; return 'Não iniciado'; }

function colabConclusao(colab){
  const mods = (colab.trilhaIds||[]).flatMap(tid => modulosDaTrilha(tid));
  const uniqMods = [...new Map(mods.map(m=>[m.id,m])).values()];
  if(uniqMods.length===0) return {pct:0, done:0, total:0};
  const done = uniqMods.filter(m => progressoDoc(colab.id, m.id)?.status === 'Concluído').length;
  return {pct: Math.round(done/uniqMods.length*100), done, total: uniqMods.length};
}

/* ---------- Orchestration ---------- */
function renderAll(){
  renderKpis(); renderFoot();
  if(currentMode==='view'){
    if(currentTab==='colaborador'){ renderViewColabList(document.getElementById('vcolab-search').value); renderViewColabDetail(); }
    if(currentTab==='trilhas') renderViewTrilhas();
    if(currentTab==='trilhaExtra') renderViewTrilhaExtra();
  } else {
    if(currentTab==='colaborador') renderManageColabTable();
    if(currentTab==='trilhas') renderTrilhas(document.getElementById('trilhas-search')?.value);
    if(currentTab==='ciclos') renderCadastroCiclos(document.getElementById('ciclos-search')?.value);
    if(currentTab==='modulos') renderCadastroModulos(document.getElementById('modulos-search')?.value);
    if(currentTab==='unidades') renderUnidades();
  }
  if(document.getElementById('modal-colab').classList.contains('show')) renderColabFormTrilhas();
  populateSelects();
}

function renderKpis(){
  const ativos = colaboradores.filter(c=>c.ativo!==false).length;
  const totalMod = modulos.length;
  let somaPct = 0, comTrilha = 0;
  colaboradores.forEach(c => { if((c.trilhaIds||[]).length){ somaPct += colabConclusao(c).pct; comTrilha++; } });
  const mediaPct = comTrilha ? Math.round(somaPct/comTrilha) : 0;
  document.getElementById('kpi-row').innerHTML = `
    <div class="kpi-card"><div class="kpi-num">${ativos}</div><div class="kpi-label">Colaboradores ativos</div></div>
    <div class="kpi-card"><div class="kpi-num">${trilhas.length}</div><div class="kpi-label">Trilhas cadastradas</div></div>
    <div class="kpi-card"><div class="kpi-num">${ciclos.length}</div><div class="kpi-label">Ciclos</div></div>
    <div class="kpi-card"><div class="kpi-num">${totalMod}</div><div class="kpi-label">Módulos</div></div>
    <div class="kpi-card"><div class="kpi-num">${mediaPct}%</div><div class="kpi-label">Conclusão média da trilha</div></div>
  `;
}
function renderFoot(){
  document.getElementById('foot-summary').textContent = `${String(colaboradores.length).padStart(2,'0')} colaboradores · ${String(trilhas.length).padStart(2,'0')} trilhas · ${String(modulos.length).padStart(2,'0')} módulos`;
}

const NAV_IDS = {
  view: {colaborador:'navv-colab', trilhas:'navv-trilhas', trilhaExtra:'navv-trilhaExtra'},
  manage: {colaborador:'navm-colab', trilhas:'navm-trilhas', ciclos:'navm-ciclos', modulos:'navm-modulos', unidades:'navm-unidades'}
};
const PANE_IDS = {
  view: {colaborador:'view-v-colaborador', trilhas:'view-v-trilhas', trilhaExtra:'view-v-trilhaExtra'},
  manage: {colaborador:'view-m-colaborador', trilhas:'view-m-trilhas', ciclos:'view-m-ciclos', modulos:'view-m-modulos', unidades:'view-m-unidades'}
};

function navTo(mode, name){
  currentMode = mode; currentTab = name;
  document.querySelectorAll('.nav-item').forEach(el=>{ el.classList.remove('active'); el.removeAttribute('aria-current'); });
  const activeNav = document.getElementById(NAV_IDS[mode][name]);
  activeNav.classList.add('active'); activeNav.setAttribute('aria-current','page');
  const parentSubmenu = activeNav.closest('.nav-submenu');
  if(parentSubmenu) openNavSubmenu(parentSubmenu);

  document.querySelectorAll('.view').forEach(v=>v.classList.remove('active'));
  document.getElementById(PANE_IDS[mode][name]).classList.add('active');

  closeSidebar();
  renderAll();
}

function openNavSubmenu(el){
  el.classList.add('open');
  el.querySelector('.nav-submenu-toggle')?.setAttribute('aria-expanded','true');
}
function toggleNavSubmenu(key){
  const el = document.getElementById('navsub-'+key);
  const isOpen = el.classList.toggle('open');
  el.querySelector('.nav-submenu-toggle').setAttribute('aria-expanded', isOpen ? 'true' : 'false');
}

function openSidebar(){ document.getElementById('sidebar').classList.add('show'); document.getElementById('sidebar-overlay').classList.add('show'); }
function closeSidebar(){ document.getElementById('sidebar').classList.remove('show'); document.getElementById('sidebar-overlay').classList.remove('show'); }

function populateSelects(){
  const uSel = document.getElementById('cf-unidade');
  if(uSel) uSel.innerHTML = unidades.map(u=>`<option value="${esc(u.id)}">${esc(u.nome)}</option>`).join('') || '<option value="">(nenhuma unidade cadastrada)</option>';
  const tSel = document.getElementById('cf2-trilha');
  if(tSel) tSel.innerHTML = sortedTrilhas().map(t=>`<option value="${esc(t.id)}">${esc(t.nome)}</option>`).join('');
  const cSel = document.getElementById('mf-ciclo');
  if(cSel) cSel.innerHTML = sortedTrilhas().flatMap(t => sortedCiclosOf(t.id).map(c=>`<option value="${esc(c.id)}">${esc(t.nome)} / ${esc(c.nome)}</option>`)).join('');
  const txSel = document.getElementById('tx-trilha-select');
  if(txSel) txSel.innerHTML = sortedTrilhas().map(t=>`<option value="${esc(t.id)}" ${t.id===trilhaExtraSelected?'selected':''}>${esc(t.nome)}</option>`).join('') || '<option value="">(nenhuma trilha cadastrada)</option>';
  const mcuSel = document.getElementById('mc-filter-unidade');
  if(mcuSel){
    const cur = mcuSel.value;
    mcuSel.innerHTML = '<option value="">Todas as unidades</option>' + unidades.map(u=>`<option value="${esc(u.id)}">${esc(u.nome)}</option>`).join('');
    if(cur && [...mcuSel.options].some(o=>o.value===cur)) mcuSel.value = cur;
  }
}

/* ================= VISÃO GERAL (somente consulta) ================= */
function renderViewColabList(filter){
  const list = document.getElementById('vcolab-list');
  const f = norm(filter||'');
  const items = [...colaboradores].filter(c => norm(c.nome).includes(f)).sort((a,b)=>(a.nome||'').localeCompare(b.nome||''));
  if(dbUnavailable){ list.innerHTML = '<p class="colab-empty">Banco de dados indisponível nesta visualização.</p>'; return; }
  if(!isLoaded('colaboradores')){ list.innerHTML = '<div class="loading-row"><span class="spinner"></span>Carregando…</div>'; return; }
  if(colaboradores.length===0){ list.innerHTML = '<p class="colab-empty">Nenhum colaborador cadastrado ainda.</p>'; return; }
  if(items.length===0){ list.innerHTML = `<p class="colab-empty">Nenhum resultado para "${esc(filter||'')}".</p>`; return; }
  list.innerHTML = items.map(c => `
    <button class="colab-btn ${c.id===colabSelected?'selected':''} ${c.ativo===false?'inativo':''}" onclick="selectColabView('${c.id}')">
      ${c.lideranca ? '<span class="lead-dot" title="Liderança"></span>' : '<span style="width:6px"></span>'}
      <span class="colab-btn-name">${esc(c.nome)}</span>
    </button>`).join('');
}
function selectColabView(id){ colabSelected = id; renderViewColabDetail(); renderViewColabList(document.getElementById('vcolab-search').value); }

function colabEnrolledTrilhas(c){
  // Only trilhas/ciclos/módulos this specific person actually has a progresso record in —
  // trilhaIds is just program membership; being "inserido" means having a real progresso doc.
  return (c.trilhaIds||[]).map(tid=>{
    const t = trilhaById(tid); if(!t) return null;
    const ciclosComMods = sortedCiclosOf(tid).map(ci=>{
      const mods = modulosOf(ci.id).filter(m => progressoDoc(c.id, m.id));
      return {ci, mods};
    }).filter(x=>x.mods.length);
    return ciclosComMods.length ? {t, ciclosComMods} : null;
  }).filter(Boolean);
}

function renderViewColabDetail(){
  const el = document.getElementById('vcolab-detail');
  if(dbUnavailable){ el.innerHTML = '<div class="empty-state">Este visualizador não tem acesso ao banco de dados do artifact.</div>'; return; }
  const c = colaboradores.find(x=>x.id===colabSelected);
  if(!c){ el.innerHTML = '<div class="empty-state">Selecione um colaborador na lista.</div>'; return; }
  const unidadeNome = unidadeById(c.unidadeId)?.nome || '—';
  let html = `
    <div class="colab-head-row">
      <div>
        <h2>${esc(c.nome)}</h2>
        <div class="colab-meta-row">
          ${c.lideranca ? '<span class="badge lideranca">Liderança</span>' : ''}
          ${c.ativo===false ? '<span class="badge inativo">Inativo</span>' : ''}
          <span class="badge">${esc(unidadeNome)}</span>
          ${c.cargo ? `<span class="badge">${esc(c.cargo)}</span>` : ''}
        </div>
      </div>
    </div>`;
  if(c.nota){ html += `<div class="warn-box">${warnSvg()}<span>${esc(c.nota)}</span></div>`; }
  if(c.email || c.gestor || c.dataAdmissao){
    html += `<p style="font-size:12.5px;color:var(--ink-dim);margin:0 0 6px;">${[c.email, c.gestor?('gestor: '+c.gestor):'', c.dataAdmissao?('desde '+c.dataAdmissao):''].filter(Boolean).map(esc).join(' · ')}</p>`;
  }

  const enrolled = colabEnrolledTrilhas(c);
  html += `<div class="section-h">Trilhas</div>`;
  if(enrolled.length){
    html += `<div class="chip-row">` + enrolled.map(({t})=>`<span class="chip" style="padding:6px 13px;">${esc(t.nome)}</span>`).join('') + `</div>`;
  } else {
    html += `<p style="font-size:12.5px;color:var(--ink-dim);">Ainda não está inserido em nenhuma trilha.</p>`;
  }

  if(enrolled.length){
    const allMods = enrolled.flatMap(({ciclosComMods})=>ciclosComMods.flatMap(x=>x.mods));
    const done = allMods.filter(m=>progressoDoc(c.id,m.id)?.status==='Concluído').length;
    const pct = allMods.length ? Math.round(done/allMods.length*100) : 0;
    html += `<div class="section-h">Progresso <span style="text-transform:none;font-weight:600;color:var(--ink-dim);">${done}/${allMods.length} módulos · ${pct}%</span></div>`;
    html += `<div class="progress-bar-outer"><div class="progress-bar-inner" style="width:${pct}%"></div></div>`;
    enrolled.forEach(({t, ciclosComMods}) => {
      html += `<div class="progress-group"><div class="progress-group-title">${esc(t.nome)}</div>`;
      ciclosComMods.forEach(({ci, mods}) => {
        html += `<div class="progress-cycle-title">${esc(ci.nome)}</div>`;
        mods.forEach(m => {
          const st = progressoDoc(c.id, m.id)?.status || 'Não iniciado';
          html += `<div class="prog-row"><span class="prog-name">${esc(m.nome)}</span>
            <span class="status-pill ${statusPillClass(st)}" style="pointer-events:none;">${esc(st)}</span></div>`;
        });
      });
      html += `</div>`;
    });
  }
  el.innerHTML = html;
}

let viewSelectedModulo = null;
function selectStationView(modId){
  viewSelectedModulo = (viewSelectedModulo === modId) ? null : modId;
  renderViewTrilhas();
}

function catLabel(cat){ if(cat==='Método') return 'Método'; if(cat==='Liderança e Método') return 'Liderança e Método'; return 'Liderança'; }

function renderViewTrilhas(){
  const el = document.getElementById('vtrilhas-list');
  if(dbUnavailable){ el.innerHTML = '<div class="empty-state">Este visualizador não tem acesso ao banco de dados do artifact.</div>'; return; }
  if(!isLoaded('trilhas')){ el.innerHTML = '<div class="loading-row"><span class="spinner"></span>Carregando trilhas…</div>'; return; }
  const list = sortedTrilhas();
  if(!list.length){ el.innerHTML = '<div class="empty-cta"><p class="empty-title">Nenhuma trilha cadastrada ainda</p><p>Crie a primeira trilha em Cadastros → Trilhas.</p></div>'; return; }
  el.innerHTML = list.map(t => {
    const membros = colaboradores.filter(c=>(c.trilhaIds||[]).includes(t.id));
    const ciclosT = sortedCiclosOf(t.id);
    const allMods = ciclosT.flatMap(ci => modulosOf(ci.id));

    let idx = 0;
    const ciclosHtml = ciclosT.map(ci => {
      const mods = modulosOf(ci.id);
      if(!mods.length) return '';
      const stationsHtml = mods.map(m => {
        idx++;
        return `
        <button class="station ${m.id===viewSelectedModulo?'selected':''}" onclick="selectStationView('${m.id}')">
          <span class="st-index">${idx}</span>
          <span class="st-name"><span class="st-dot ${catDotClass(m.categoria)}"></span>${esc(m.nome)}</span>
        </button>`;
      }).join('');
      return `
      <div class="jornada-ciclo-group">
        <p class="jornada-ciclo-label">${iconSvg(ci.icon)}${esc(ci.nome)}</p>
        <div class="jornada-stations">${stationsHtml}</div>
      </div>`;
    }).join('');

    const total = allMods.length;
    const doneCount = allMods.filter(m=>m.status==='Concluído').length;
    const onCount = allMods.filter(m=>m.status==='Em andamento').length;
    const fillPct = total ? Math.round(((doneCount + onCount*0.5)/total)*100) : 0;

    const selMod = viewSelectedModulo ? allMods.find(m=>m.id===viewSelectedModulo) : null;
    let detailHtml = '';
    if(selMod){
      const ci = cicloById(selMod.cicloId);
      const cat = catDotClass(selMod.categoria);
      const posIdx = allMods.findIndex(m=>m.id===selMod.id) + 1;
      const participantes = progresso.filter(p=>p.moduloId===selMod.id)
        .map(p=>({...p, colab: colaboradores.find(c=>c.id===p.colaboradorId)}))
        .filter(p=>p.colab);
      const concluidos = participantes.filter(p=>p.status==='Concluído');
      const emAndamento = participantes.filter(p=>p.status!=='Concluído');
      detailHtml = `
        <div class="jornada-detail">
          <p class="d-eyebrow">Etapa ${posIdx} · ${esc(ci?.nome||'')} · <span class="d-cat-label ${cat}">${esc(catLabel(selMod.categoria))}</span></p>
          <h2 class="d-title">${esc(selMod.nome)}</h2>
          <span class="status-pill ${statusPillClass(selMod.status)}" style="pointer-events:none;">${esc(selMod.status||'A iniciar')}</span>
          ${selMod.descricao ? `<p class="d-desc">${esc(selMod.descricao)}</p>` : ''}
          <div class="meta-grid">
            ${selMod.formato ? `<div><div class="meta-label">Formato</div><div class="meta-val">${esc(selMod.formato)}</div></div>` : ''}
            ${selMod.publicoAlvo ? `<div><div class="meta-label">Público</div><div class="meta-val">${esc(selMod.publicoAlvo)}</div></div>` : ''}
            ${selMod.mentor ? `<div><div class="meta-label">Mentor</div><div class="meta-val">${esc(selMod.mentor)}</div></div>` : ''}
            ${selMod.cargaHoraria ? `<div><div class="meta-label">Carga horária</div><div class="meta-val">${esc(selMod.cargaHoraria)}</div></div>` : ''}
          </div>
          ${participantes.length ? `
          <div class="people-block">
            <p class="people-label">Participantes (${participantes.length})</p>
            <div class="chip-list">
              ${concluidos.map(p=>`<span class="chip concluido">${esc(p.colab.nome)}</span>`).join('')}
              ${emAndamento.map(p=>`<span class="chip">${esc(p.colab.nome)}</span>`).join('')}
            </div>
          </div>` : ''}
          ${selMod.linkMaterial ? `
          <a class="material-btn" href="${esc(selMod.linkMaterial)}" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
            Ver material do módulo
          </a>` : `<p class="no-material">Material do módulo ainda não disponibilizado</p>`}
        </div>`;
    }

    return `
      <div class="jornada-block">
        <div class="jornada-head">
          <h3>${esc(t.nome)}</h3>
          ${t.descricao?`<p>${esc(t.descricao)}</p>`:''}
          <p class="sysmap-stat">${membros.length} colaborador${membros.length===1?'':'es'} nesta trilha · ${doneCount}/${total} etapas concluídas</p>
        </div>
        <div class="legend">
          <span class="legend-item"><span class="legend-dot lideranca"></span>Liderança</span>
          <span class="legend-item"><span class="legend-dot metodo"></span>Método</span>
          <span class="legend-item"><span class="legend-dot ambos"></span>Liderança e Método</span>
        </div>
        <div class="jornada-track"><div class="jornada-track-fill" style="width:${fillPct}%"></div></div>
        ${ciclosHtml || '<p style="font-size:12px;color:var(--ink-dim);">Nenhum ciclo nesta trilha ainda.</p>'}
        ${detailHtml}
      </div>`;
  }).join('');
}

/* ================= Trilha - Extra: roadmap sequencial por trilha ================= */
function selectTrilhaExtra(id){
  if(id !== trilhaExtraSelected){ txSelectedModulo = null; closeTxModuleModal(); }
  trilhaExtraSelected = id; renderViewTrilhaExtra();
}
function openTxModuleModal(id){
  const m = moduloById(id);
  const ci = m ? cicloById(m.cicloId) : null;
  if(!m || !ci) return;
  txSelectedModulo = id;
  document.querySelectorAll('#tx-roadmap-row .tx-node.selected').forEach(n => { n.classList.remove('selected'); n.setAttribute('aria-pressed','false'); });
  const node = document.querySelector(`#tx-roadmap-row .tx-node[data-mod-id="${id}"]`);
  if(node){ node.classList.add('selected'); node.setAttribute('aria-pressed','true'); }

  const cat = catDotClass(m.categoria);
  const grp = groupParticipantesByStatus(m.id);
  document.getElementById('tx-modal-inner').innerHTML = `
    <div class="tx-modal-accent ${cat}"></div>
    <button class="tx-modal-close" onclick="closeTxModuleModal()" aria-label="Fechar">×</button>
    <div class="tx-modal-head">
      <p class="d-eyebrow">${esc(ci.nome)}${m.etapa?' · '+esc(m.etapa):''} · <span class="d-cat-label ${cat}">${esc(catLabel(m.categoria))}</span></p>
      <h2 class="d-title">${esc(m.nome)}</h2>
      <span class="status-pill ${statusPillClass(m.status)}" style="pointer-events:none;">${esc(m.status||'A iniciar')}</span>
    </div>
    <div class="tx-modal-body">
      ${m.descricao ? `<p class="d-desc">${esc(m.descricao)}</p>` : '<p class="d-desc" style="font-style:italic;color:var(--ink-faint);">Descrição ainda não cadastrada.</p>'}
      <div class="meta-grid">
        <div><div class="meta-label">Formato</div><div class="meta-val">${m.formato?esc(m.formato):'—'}</div></div>
        <div><div class="meta-label">Mentor</div><div class="meta-val">${m.mentor?esc(m.mentor):'—'}</div></div>
        <div><div class="meta-label">Carga horária</div><div class="meta-val">${m.cargaHoraria?esc(m.cargaHoraria):'—'}</div></div>
      </div>
      <div class="people-block">
        <p class="people-label">Inscritos por status (${grp.total})</p>
        ${grp.total===0 ? '<p class="no-material" style="margin-top:0;">Nenhum colaborador inscrito ainda.</p>' : `
          ${grp.concluido.length?`<p class="status-group-label"><span class="status-pill done" style="pointer-events:none;">Concluído</span>${grp.concluido.length}</p><div class="chip-list">${grp.concluido.map(p=>`<span class="chip concluido">${esc(p.colab.nome)}</span>`).join('')}</div>`:''}
          ${grp.andamento.length?`<p class="status-group-label"><span class="status-pill on" style="pointer-events:none;">Em andamento</span>${grp.andamento.length}</p><div class="chip-list">${grp.andamento.map(p=>`<span class="chip">${esc(p.colab.nome)}</span>`).join('')}</div>`:''}
          ${grp.naoIniciado.length?`<p class="status-group-label"><span class="status-pill off" style="pointer-events:none;">Não iniciado</span>${grp.naoIniciado.length}</p><div class="chip-list">${grp.naoIniciado.map(p=>`<span class="chip">${esc(p.colab.nome)}</span>`).join('')}</div>`:''}
        `}
      </div>
      ${m.linkMaterial ? `
      <a class="material-btn" href="${esc(m.linkMaterial)}" target="_blank" rel="noopener">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
        Ver material do módulo
      </a>` : `<p class="no-material">Material do módulo ainda não disponibilizado</p>`}
    </div>`;

  document.getElementById('overlay-tx-detail').classList.add('show');
  document.getElementById('modal-tx-detail').classList.add('show');
}
function closeTxModuleModal(){
  document.getElementById('overlay-tx-detail').classList.remove('show');
  document.getElementById('modal-tx-detail').classList.remove('show');
  document.querySelectorAll('#tx-roadmap-row .tx-node.selected').forEach(n => { n.classList.remove('selected'); n.setAttribute('aria-pressed','false'); });
  txSelectedModulo = null;
}

function groupParticipantesByStatus(modId){
  const participantes = progresso.filter(p=>p.moduloId===modId)
    .map(p=>({...p, colab: colaboradores.find(c=>c.id===p.colaboradorId)}))
    .filter(p=>p.colab);
  return {
    concluido: participantes.filter(p=>p.status==='Concluído'),
    andamento: participantes.filter(p=>p.status==='Em andamento'),
    naoIniciado: participantes.filter(p=>p.status!=='Concluído' && p.status!=='Em andamento'),
    total: participantes.length
  };
}

function renderViewTrilhaExtra(){
  const el = document.getElementById('vtrilhaextra-roadmap');
  if(dbUnavailable){ el.innerHTML = '<div class="empty-state">Este visualizador não tem acesso ao banco de dados do artifact.</div>'; return; }
  if(!isLoaded('trilhas') || !isLoaded('ciclos') || !isLoaded('modulos')){ el.innerHTML = '<div class="loading-row"><span class="spinner"></span>Carregando trilha…</div>'; return; }
  const list = sortedTrilhas();
  if(!list.length){ el.innerHTML = '<div class="empty-cta"><p class="empty-title">Nenhuma trilha cadastrada ainda</p><p>Crie a primeira trilha em Cadastros → Trilhas.</p></div>'; return; }
  if(!trilhaExtraSelected || !list.find(t=>t.id===trilhaExtraSelected)){ trilhaExtraSelected = list[0].id; }
  const t = list.find(x=>x.id===trilhaExtraSelected);
  const ciclosT = sortedCiclosOf(t.id).filter(ci => modulosOf(ci.id).length);

  if(!ciclosT.length){
    el.innerHTML = `<p class="tx-roadmap-stat">${esc(t.nome)}</p><div class="empty-cta"><p class="empty-title">Esta trilha ainda não tem ciclos com módulos</p><p>Cadastre ciclos e módulos em Cadastros para ver a sequência aqui.</p></div>`;
    return;
  }

  let totalMods = 0;
  const rowHtml = ciclosT.map((ci, ciIdx) => {
    const mods = modulosOf(ci.id);
    totalMods += mods.length;
    const nodesHtml = mods.map((m, mIdx) => {
      const st = m.status || 'A iniciar';
      const stClass = st==='Concluído' ? 'done' : (st==='Em andamento' ? 'on' : 'off');
      const label = m.etapa ? m.etapa : `Módulo ${mIdx+1}`;
      const descFull = m.descricao || '';
      const descShort = descFull.length > 85 ? descFull.slice(0,82)+'…' : descFull;
      const tip = esc([m.nome, descFull].filter(Boolean).join(' — '));
      const isSel = m.id === txSelectedModulo;
      return `
        <div class="tx-node ${isSel?'selected':''}" data-mod-id="${m.id}" title="${tip}" role="button" tabindex="0" aria-pressed="${isSel}"
          onclick="openTxModuleModal('${m.id}')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openTxModuleModal('${m.id}');}">
          <div class="tx-node-icon">${iconSvg(ci.icon)}</div>
          <div class="tx-node-label">${esc(label)}</div>
          <div class="tx-node-name">${esc(m.nome)}</div>
          ${descShort ? `<div class="tx-node-desc">${esc(descShort)}</div>` : ''}
          <span class="tx-node-status ${stClass}"></span>
        </div>`;
    }).join('');
    const cardHtml = `
      <div class="tx-ciclo">
        <div class="tx-ciclo-head">
          <div class="tx-ciclo-num">${ciIdx+1}</div>
          <div class="tx-ciclo-title">
            <p class="tx-ciclo-eyebrow">Ciclo ${ciIdx+1}</p>
            <p class="tx-ciclo-name">${esc(ci.nome)}</p>
          </div>
        </div>
        <div class="tx-nodes">${nodesHtml}</div>
      </div>`;
    return cardHtml;
  }).join('');

  el.innerHTML = `
    <p class="tx-roadmap-stat">${esc(t.nome)} · ${ciclosT.length} ciclo${ciclosT.length===1?'':'s'} · ${totalMods} módulo${totalMods===1?'':'s'}</p>
    <div class="tx-row" id="tx-roadmap-row">${rowHtml}</div>`;

  requestAnimationFrame(layoutTxConnectors);
}

/* Converte uma lista de pontos [x,y] num "d" de path SVG com cantos arredondados
   (curva quadrática em cada vértice interno), para que os conectores da Trilha
   Extra pareçam um caminho contínuo em vez de segmentos retos colados. */
function roundedPath(points, r){
  if(points.length < 2) return '';
  if(points.length === 2) return `M${points[0][0]},${points[0][1]} L${points[1][0]},${points[1][1]}`;
  const dist = (p,q) => Math.hypot(q[0]-p[0], q[1]-p[1]);
  const lerp = (from, to, t) => [ from[0] + (to[0]-from[0])*t, from[1] + (to[1]-from[1])*t ];
  let d = `M${points[0][0]},${points[0][1]}`;
  for(let i=1; i<points.length-1; i++){
    const p0 = points[i-1], p1 = points[i], p2 = points[i+1];
    const d0 = dist(p0,p1), d1 = dist(p1,p2);
    const rr = Math.max(0, Math.min(r, d0/2, d1/2));
    const t0 = d0 ? lerp(p1, p0, rr/d0) : p1;
    const t1 = d1 ? lerp(p1, p2, rr/d1) : p1;
    d += ` L${t0[0]},${t0[1]} Q${p1[0]},${p1[1]} ${t1[0]},${t1[1]}`;
  }
  const last = points[points.length-1];
  d += ` L${last[0]},${last[1]}`;
  return d;
}

/* Desenha as ligações entre ciclos: seta simples quando estão na mesma linha,
   e um conector contornando as linhas quando o cartão seguinte quebrou
   para uma nova linha (sem depender de rolagem lateral). Os traços usam
   cantos arredondados e um tracejado em pontos, remetendo a uma trilha. */
function layoutTxConnectors(){
  const row = document.getElementById('tx-roadmap-row');
  if(!row) return;
  const cards = [...row.querySelectorAll('.tx-ciclo')];
  const oldSvg = row.querySelector('.tx-connector-svg');
  if(oldSvg) oldSvg.remove();
  if(cards.length < 2) return;
  if(window.innerWidth <= 640) return;

  const rowRect = row.getBoundingClientRect();
  const w = row.scrollWidth, h = row.scrollHeight;
  const pts = cards.map(card => {
    const r = card.getBoundingClientRect();
    const head = card.querySelector('.tx-ciclo-head');
    const hr = head ? head.getBoundingClientRect() : r;
    return {
      left: r.left - rowRect.left, right: r.right - rowRect.left,
      top: r.top - rowRect.top, bottom: r.bottom - rowRect.top,
      midY: hr.top - rowRect.top + hr.height/2
    };
  });

  let paths = '';
  for(let i=0; i<pts.length-1; i++){
    const a = pts[i], b = pts[i+1];
    const sameRow = Math.abs(a.midY - b.midY) < 2;
    if(sameRow){
      const x1 = a.right + 6, x2 = b.left - 6;
      if(x2 > x1){ paths += `<path class="tx-conn-path" d="${roundedPath([[x1,a.midY],[x2,a.midY]], 10)}"/>`; }
    } else {
      const startX = a.right + 6, startY = a.midY;
      const midY = (a.bottom + b.top) / 2;
      const endX = b.left + 15, endY = b.top - 8;
      const outX = startX + 14;
      const d = roundedPath([[startX,startY],[outX,startY],[outX,midY],[endX,midY],[endX,endY]], 10);
      paths += `<path class="tx-conn-path" d="${d}"/>`;
    }
  }

  const svg = document.createElementNS('http://www.w3.org/2000/svg','svg');
  svg.setAttribute('class','tx-connector-svg');
  svg.setAttribute('width', w); svg.setAttribute('height', h);
  svg.innerHTML = paths;
  row.insertBefore(svg, row.firstChild);
}

let txResizeTimer = null;
window.addEventListener('resize', () => {
  clearTimeout(txResizeTimer);
  txResizeTimer = setTimeout(() => {
    if(currentMode==='view' && currentTab==='trilhaExtra') layoutTxConnectors();
  }, 150);
});

/* ================= COLABORADORES (cadastro) ================= */
function mcMatchesFilters(c, search, unidadeId, status, lideranca){
  if(search && !(norm(c.nome).includes(search) || norm(c.cargo||'').includes(search) || norm(c.email||'').includes(search))) return false;
  if(unidadeId && c.unidadeId !== unidadeId) return false;
  if(status==='ativo' && c.ativo===false) return false;
  if(status==='inativo' && c.ativo!==false) return false;
  if(lideranca==='sim' && !c.lideranca) return false;
  if(lideranca==='nao' && c.lideranca) return false;
  return true;
}
function mcTrilhaChips(c){
  const ids = c.trilhaIds||[];
  if(!ids.length) return '<span class="mc-dim">Nenhuma</span>';
  const names = ids.map(tid=>trilhaById(tid)?.nome).filter(Boolean);
  const shown = names.slice(0,2);
  let html = shown.map(n=>`<span class="mc-chip-mini">${esc(n)}</span>`).join('');
  if(names.length>shown.length) html += `<span class="mc-chip-mini">+${names.length-shown.length}</span>`;
  return html || '<span class="mc-dim">Nenhuma</span>';
}
function renderManageColabTable(){
  const tbody = document.getElementById('mc-table-body');
  const countEl = document.getElementById('mc-result-count');
  if(dbUnavailable){ tbody.innerHTML = '<tr><td colspan="7" class="mc-empty-cell">Banco de dados indisponível nesta visualização.</td></tr>'; countEl.textContent = ''; return; }
  if(!isLoaded('colaboradores')){ tbody.innerHTML = '<tr><td colspan="7"><div class="loading-row"><span class="spinner"></span>Carregando…</div></td></tr>'; countEl.textContent = ''; return; }
  if(colaboradores.length===0){ tbody.innerHTML = '<tr><td colspan="7" class="mc-empty-cell">Nenhum colaborador cadastrado ainda. Clique em "Novo colaborador" para começar.</td></tr>'; countEl.textContent = ''; return; }
  const search = norm(document.getElementById('mc-search')?.value||'');
  const unidadeId = document.getElementById('mc-filter-unidade')?.value||'';
  const status = document.getElementById('mc-filter-status')?.value||'';
  const lideranca = document.getElementById('mc-filter-lideranca')?.value||'';
  const items = colaboradores.filter(c=>mcMatchesFilters(c,search,unidadeId,status,lideranca)).sort((a,b)=>(a.nome||'').localeCompare(b.nome||''));
  countEl.textContent = items.length===colaboradores.length ? `${colaboradores.length} colaborador(es)` : `${items.length} de ${colaboradores.length} colaboradores`;
  if(items.length===0){ tbody.innerHTML = '<tr><td colspan="7" class="mc-empty-cell">Nenhum resultado para os filtros aplicados.</td></tr>'; return; }
  tbody.innerHTML = items.map(c => {
    const conc = colabConclusao(c);
    const unidadeNome = unidadeById(c.unidadeId)?.nome || '—';
    return `<tr class="${c.ativo===false?'mc-row-inativo':''}" onclick="openColabForm('${c.id}')">
      <td><div class="mc-cell-name">${c.lideranca?'<span class="lead-dot" title="Liderança"></span>':''}<span>${esc(c.nome)}</span></div></td>
      <td>${c.cargo?esc(c.cargo):'—'}</td>
      <td>${esc(unidadeNome)}</td>
      <td><span class="badge ${c.ativo===false?'inativo':'ativo'}">${c.ativo===false?'Inativo':'Ativo'}</span></td>
      <td>${mcTrilhaChips(c)}</td>
      <td>${(c.trilhaIds||[]).length ? `<div class="mc-progress-mini"><div class="progress-bar-outer"><div class="progress-bar-inner" style="width:${conc.pct}%"></div></div><span>${conc.pct}%</span></div>` : '<span class="mc-dim">—</span>'}</td>
      <td class="mc-actions"><button class="btn-ghost-sm" onclick="event.stopPropagation();openColabForm('${c.id}')" title="Editar colaborador">${editSvg()}</button></td>
    </tr>`;
  }).join('');
}

function switchColabFormTab(tab){
  document.querySelectorAll('#colab-form-tabs .form-tab').forEach(b => b.classList.toggle('active', b.dataset.tab===tab));
  document.getElementById('cftab-panel-dados').classList.toggle('active', tab==='dados');
  document.getElementById('cftab-panel-trilhas').classList.toggle('active', tab==='trilhas');
  document.getElementById('cf-actions-dados').style.display = tab==='dados' ? '' : 'none';
  document.getElementById('cf-actions-trilhas').style.display = tab==='trilhas' ? '' : 'none';
  if(tab==='trilhas') renderColabFormTrilhas();
}

function renderColabFormTrilhas(){
  const el = document.getElementById('colab-form-trilhas-content');
  if(!el) return;
  const c = editingColabId ? colaboradores.find(x=>x.id===editingColabId) : null;
  if(!c){ el.innerHTML = '<p class="hint" style="margin:0;">Salve os dados cadastrais para poder atribuir trilhas e acompanhar o progresso.</p>'; return; }
  const conc = colabConclusao(c);
  const assignedIds = c.trilhaIds || [];
  let html = `<div class="section-h">Trilhas atribuídas</div><div class="chip-row">`;
  assignedIds.forEach(tid => {
    const t = trilhaById(tid);
    if(!t) return;
    html += `<span class="chip">${esc(t.nome)}<button onclick="removeColabTrilha('${c.id}','${tid}')" title="Remover">×</button></span>`;
  });
  const available = trilhas.filter(t=>!assignedIds.includes(t.id));
  if(available.length){
    html += `<select class="add-chip-select" onchange="if(this.value){addColabTrilha('${c.id}', this.value); this.value='';}">
      <option value="">+ trilha</option>${available.map(t=>`<option value="${esc(t.id)}">${esc(t.nome)}</option>`).join('')}</select>`;
  }
  html += `</div>`;

  if(assignedIds.length){
    html += `<div class="section-h">Progresso <span style="text-transform:none;font-weight:600;color:var(--ink-dim);">${conc.done}/${conc.total} módulos · ${conc.pct}%</span></div>`;
    html += `<div class="progress-bar-outer"><div class="progress-bar-inner" style="width:${conc.pct}%"></div></div>`;
    assignedIds.forEach(tid => {
      const t = trilhaById(tid); if(!t) return;
      html += `<div class="progress-group"><div class="progress-group-title">${esc(t.nome)}</div>`;
      sortedCiclosOf(tid).forEach(ci => {
        const mods = modulosOf(ci.id);
        if(!mods.length) return;
        html += `<div class="progress-cycle-title">${esc(ci.nome)}</div>`;
        mods.forEach(m => {
          const st = progressoDoc(c.id, m.id)?.status || 'Não iniciado';
          html += `<div class="prog-row"><span class="prog-name">${esc(m.nome)}</span>
            <button class="status-pill ${statusPillClass(st)}" onclick="cycleProgresso('${c.id}','${m.id}')">${esc(st)}</button></div>`;
        });
      });
      html += `</div>`;
    });
  } else {
    html += `<p style="font-size:12.5px;color:var(--ink-dim);">Ainda não está em nenhuma trilha.</p>`;
  }
  el.innerHTML = html;
}

async function addColabTrilha(colabId, trilhaId){
  const c = colaboradores.find(x=>x.id===colabId); if(!c || !db) return;
  const ids = new Set(c.trilhaIds||[]); ids.add(trilhaId);
  await db.doc('colaboradores/'+colabId).update({trilhaIds:[...ids]});
  toast('Trilha adicionada');
}
async function removeColabTrilha(colabId, trilhaId){
  const c = colaboradores.find(x=>x.id===colabId); if(!c || !db) return;
  const ids = (c.trilhaIds||[]).filter(id=>id!==trilhaId);
  await db.doc('colaboradores/'+colabId).update({trilhaIds: ids});
  toast('Trilha removida');
}
async function cycleProgresso(colabId, modId){
  if(!db) return;
  const cur = progressoDoc(colabId, modId)?.status || 'Não iniciado';
  const next = nextStatus(cur);
  await db.doc('progresso/prog-'+colabId+'-'+modId).set({colaboradorId:colabId, moduloId:modId, status:next});
}

function openColabForm(id){
  editingColabId = id;
  const c = id ? colaboradores.find(x=>x.id===id) : null;
  document.getElementById('colab-form-title').textContent = c ? 'Editar colaborador' : 'Novo colaborador';
  document.getElementById('cf-nome').value = c?.nome || '';
  document.getElementById('cf-email').value = c?.email || '';
  document.getElementById('cf-cargo').value = c?.cargo || '';
  document.getElementById('cf-gestor').value = c?.gestor || '';
  document.getElementById('cf-admissao').value = c?.dataAdmissao || '';
  document.getElementById('cf-lideranca').checked = !!c?.lideranca;
  document.getElementById('cf-ativo').checked = c?.ativo !== false;
  document.getElementById('cf-nota').value = c?.nota || '';
  populateSelects();
  if(c?.unidadeId) document.getElementById('cf-unidade').value = c.unidadeId;
  document.getElementById('colab-delete-btn').style.display = c ? '' : 'none';
  switchColabFormTab('dados');
  renderColabFormTrilhas();
  document.getElementById('overlay-colab').classList.add('show');
  document.getElementById('modal-colab').classList.add('show');
}
function closeColabForm(){ document.getElementById('overlay-colab').classList.remove('show'); document.getElementById('modal-colab').classList.remove('show'); }
async function saveColab(){
  const nome = document.getElementById('cf-nome').value.trim();
  if(!nome){ toast('Informe o nome'); return; }
  const data = {
    nome, email: document.getElementById('cf-email').value.trim(),
    cargo: document.getElementById('cf-cargo').value.trim(),
    unidadeId: document.getElementById('cf-unidade').value || '',
    gestor: document.getElementById('cf-gestor').value.trim(),
    dataAdmissao: document.getElementById('cf-admissao').value.trim(),
    lideranca: document.getElementById('cf-lideranca').checked,
    ativo: document.getElementById('cf-ativo').checked,
    nota: document.getElementById('cf-nota').value.trim(),
  };
  if(!db){ toast('Banco indisponível'); return; }
  if(editingColabId){
    await db.doc('colaboradores/'+editingColabId).update(data);
  } else {
    data.trilhaIds = [];
    const ref = await db.collection('colaboradores').add(data);
    colabSelected = ref.id;
  }
  closeColabForm(); toast('Colaborador salvo');
}
function askDeleteColab(){
  if(!editingColabId) return;
  const c = colaboradores.find(x=>x.id===editingColabId);
  openConfirm(`Excluir ${c?.nome||'colaborador'}?`, 'O cadastro e o progresso registrado dele serão removidos.', async () => {
    await db.doc('colaboradores/'+editingColabId).delete();
    for(const p of progresso.filter(p=>p.colaboradorId===editingColabId)){ await db.doc('progresso/'+p.id).delete(); }
    if(colabSelected===editingColabId) colabSelected = null;
    closeColabForm(); toast('Colaborador excluído');
  });
}

/* ================= TRILHAS (cadastro) ================= */
function renderTrilhas(filter){
  const el = document.getElementById('trilhas-list');
  if(dbUnavailable){ el.innerHTML = '<div class="empty-state">Este visualizador não tem acesso ao banco de dados do artifact.</div>'; return; }
  if(!isLoaded('trilhas')){ el.innerHTML = '<div class="loading-row"><span class="spinner"></span>Carregando trilhas…</div>'; return; }
  const f = norm(filter||'');
  const list = sortedTrilhas().filter(t=>norm(t.nome).includes(f));
  if(!list.length){
    el.innerHTML = trilhas.length===0
      ? '<div class="empty-cta"><p class="empty-title">Nenhuma trilha cadastrada ainda</p><p>Clique em "Nova trilha" para começar.</p></div>'
      : `<div class="empty-board"><p>Nenhum resultado para "${esc(filter||'')}".</p></div>`;
    return;
  }
  el.innerHTML = list.map(t => {
    const cyclesHtml = sortedCiclosOf(t.id).map(ci => `
      <div class="cycle-col" data-ciclo-id="${esc(ci.id)}">
        <div class="cycle-head" draggable="true" ondragstart="onCycleDragStart(event,'${ci.id}')" onclick="openCycleModal('${ci.id}')">
          <div class="cycle-icon">${iconSvg(ci.icon)}</div>
          <div class="cycle-titles"><p class="cycle-eyebrow">${esc(ci.tema||'')}</p><p class="cycle-name">${esc(ci.nome)}</p></div>
        </div>
        ${modulosOf(ci.id).map(m => `
          <div class="module-node cat-${catDotClass(m.categoria)}" onclick="openModuleForm('${m.id}')">
            ${m.etapa ? `<p class="module-etapa">${esc(m.etapa)}</p>` : ''}
            <p class="module-name">${esc(m.nome)}</p>
            <div class="module-meta">
              <span class="module-cat-label"><span class="cat-dot ${catDotClass(m.categoria)}"></span>${esc(catLabel(m.categoria))}</span>
              <span class="status-pill ${statusPillClass(m.status)}" style="pointer-events:none;">${esc(m.status||'A iniciar')}</span>
            </div>
          </div>`).join('')}
        <button class="add-tile" onclick="openModuleForm(null,'${ci.id}')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>Módulo</button>
      </div>`).join('');
    return `
      <div class="trilha-card card">
        <div class="trilha-head">
          <div class="trilha-titles"><h3>${esc(t.nome)}</h3>${t.descricao?`<p>${esc(t.descricao)}</p>`:''}</div>
          <div class="trilha-actions">
            <button class="btn-ghost-sm" onclick="openTrilhaForm('${t.id}')" title="Editar trilha">${editSvg()}</button>
          </div>
        </div>
        <div class="board" data-trilha-id="${esc(t.id)}" ondragover="onBoardDragOver(event)" ondragleave="onBoardDragLeave(event)" ondrop="onBoardDrop(event,'${t.id}')">
          ${cyclesHtml || '<div class="empty-board" style="flex:1;">Nenhum ciclo nesta trilha ainda.</div>'}
          <div class="cycle-col add-col"><button class="add-cycle-btn" onclick="openCycleModal(null,'${t.id}')"><span class="plus-circle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></span>Novo ciclo</button></div>
        </div>
      </div>`;
  }).join('');
}

let dragCicloId = null;
function onCycleDragStart(ev, cicloId){ dragCicloId = cicloId; ev.dataTransfer.effectAllowed = 'move'; ev.dataTransfer.setData('text/plain', cicloId); }
function onBoardDragOver(ev){ ev.preventDefault(); ev.currentTarget.classList.add('drag-over'); }
function onBoardDragLeave(ev){ ev.currentTarget.classList.remove('drag-over'); }
async function onBoardDrop(ev, trilhaId){
  ev.preventDefault(); ev.currentTarget.classList.remove('drag-over');
  const cicloId = dragCicloId || ev.dataTransfer.getData('text/plain');
  dragCicloId = null;
  if(!cicloId || !db) return;
  const ci = cicloById(cicloId); if(!ci || ci.trilhaId===trilhaId) return;
  const maxOrdem = Math.max(0, ...sortedCiclosOf(trilhaId).map(c=>c.ordem||0));
  await db.doc('ciclos/'+cicloId).update({trilhaId, ordem: maxOrdem+1});
  toast('Ciclo movido');
}

function openTrilhaForm(id){
  editingTrilhaId = id;
  const t = id ? trilhaById(id) : null;
  document.getElementById('trilha-form-title').textContent = t ? 'Editar trilha' : 'Nova trilha';
  document.getElementById('tf-nome').value = t?.nome || '';
  document.getElementById('tf-desc').value = t?.descricao || '';
  document.getElementById('trilha-delete-btn').style.display = t ? '' : 'none';
  document.getElementById('overlay-trilha').classList.add('show');
  document.getElementById('modal-trilha').classList.add('show');
}
function closeTrilhaForm(){ document.getElementById('overlay-trilha').classList.remove('show'); document.getElementById('modal-trilha').classList.remove('show'); }
async function saveTrilha(){
  const nome = document.getElementById('tf-nome').value.trim();
  if(!nome){ toast('Informe o nome da trilha'); return; }
  const data = {nome, descricao: document.getElementById('tf-desc').value.trim()};
  if(!db){ toast('Banco indisponível'); return; }
  if(editingTrilhaId){ await db.doc('trilhas/'+editingTrilhaId).update(data); }
  else { data.ordem = Math.max(0, ...trilhas.map(t=>t.ordem||0)) + 1; await db.collection('trilhas').add(data); }
  closeTrilhaForm(); toast('Trilha salva');
}
function askDeleteTrilha(){
  if(!editingTrilhaId) return;
  const t = trilhaById(editingTrilhaId);
  if(sortedCiclosOf(editingTrilhaId).length){ toast('Mova ou exclua os ciclos desta trilha antes de excluí-la'); return; }
  const assigned = colaboradores.filter(c=>(c.trilhaIds||[]).includes(editingTrilhaId)).length;
  openConfirm(`Excluir ${t?.nome||'trilha'}?`, assigned ? `${assigned} colaborador(es) serão desvinculados desta trilha.` : 'Esta ação não pode ser desfeita.', async () => {
    for(const c of colaboradores.filter(c=>(c.trilhaIds||[]).includes(editingTrilhaId))){
      await db.doc('colaboradores/'+c.id).update({trilhaIds:(c.trilhaIds||[]).filter(id=>id!==editingTrilhaId)});
    }
    await db.doc('trilhas/'+editingTrilhaId).delete();
    closeTrilhaForm(); toast('Trilha excluída');
  });
}

function renderIconPick(){
  document.getElementById('cf2-icon-pick').innerHTML = ICON_KEYS.map(k=>`<button type="button" class="icon-opt" data-icon="${k}" onclick="pickIcon('${k}')">${iconSvg(k)}</button>`).join('');
}
function pickIcon(key){
  document.querySelectorAll('#cf2-icon-pick .icon-opt').forEach(b=>b.classList.toggle('selected', b.dataset.icon===key));
  document.getElementById('cf2-icon-pick').dataset.value = key;
}
function openCycleModal(id, trilhaIdForNew){
  editingCycleId = id;
  const ci = id ? cicloById(id) : null;
  document.getElementById('cycle-modal-title').textContent = ci ? 'Editar ciclo' : 'Novo ciclo';
  populateSelects();
  document.getElementById('cf2-trilha').value = ci?.trilhaId || trilhaIdForNew || '';
  document.getElementById('cf2-nome').value = ci?.nome || '';
  document.getElementById('cf2-tema').value = ci?.tema || '';
  document.getElementById('cf2-ordem').value = ci?.ordem ?? (sortedCiclosOf(ci?.trilhaId||trilhaIdForNew).length+1);
  renderIconPick();
  pickIcon(ci?.icon || 'layers');
  document.getElementById('cycle-delete-btn').style.display = ci ? '' : 'none';
  document.getElementById('overlay-cycle').classList.add('show');
  document.getElementById('modal-cycle').classList.add('show');
}
function closeCycleModal(){ document.getElementById('overlay-cycle').classList.remove('show'); document.getElementById('modal-cycle').classList.remove('show'); }
async function saveCycle(){
  const nome = document.getElementById('cf2-nome').value.trim();
  const trilhaId = document.getElementById('cf2-trilha').value;
  if(!nome || !trilhaId){ toast('Informe trilha e nome do ciclo'); return; }
  const data = {
    trilhaId, nome, tema: document.getElementById('cf2-tema').value.trim(),
    ordem: parseInt(document.getElementById('cf2-ordem').value)||0,
    icon: document.getElementById('cf2-icon-pick').dataset.value || 'layers'
  };
  if(!db){ toast('Banco indisponível'); return; }
  if(editingCycleId){ await db.doc('ciclos/'+editingCycleId).update(data); }
  else { await db.collection('ciclos').add(data); }
  closeCycleModal(); toast('Ciclo salvo');
}
function askDeleteCycle(){
  if(!editingCycleId) return;
  if(modulosOf(editingCycleId).length){ toast('Mova ou exclua os módulos deste ciclo antes de excluí-lo'); return; }
  const ci = cicloById(editingCycleId);
  openConfirm(`Excluir ${ci?.nome||'ciclo'}?`, 'Esta ação não pode ser desfeita.', async () => {
    await db.doc('ciclos/'+editingCycleId).delete();
    closeCycleModal(); toast('Ciclo excluído');
  });
}

function openModuleForm(id, cicloIdForNew){
  editingModuleId = id;
  const m = id ? moduloById(id) : null;
  document.getElementById('module-drawer-title').textContent = m ? 'Editar módulo' : 'Novo módulo';
  populateSelects();
  document.getElementById('mf-ciclo').value = m?.cicloId || cicloIdForNew || '';
  document.getElementById('mf-etapa').value = m?.etapa || '';
  document.getElementById('mf-ordem').value = m?.ordem ?? (modulosOf(m?.cicloId||cicloIdForNew).length+1);
  document.getElementById('mf-nome').value = m?.nome || '';
  document.getElementById('mf-categoria').value = m?.categoria || 'Liderança';
  document.getElementById('mf-status').value = m?.status || 'A iniciar';
  document.getElementById('mf-desc').value = m?.descricao || '';
  document.getElementById('mf-mentor').value = m?.mentor || '';
  document.getElementById('mf-formato').value = m?.formato || '';
  document.getElementById('mf-publico').value = m?.publicoAlvo || '';
  document.getElementById('mf-carga').value = m?.cargaHoraria || '';
  document.getElementById('mf-inicio').value = m?.inicioPrevisto || '';
  document.getElementById('mf-material').value = m?.linkMaterial || '';
  document.getElementById('module-delete-btn').style.display = m ? '' : 'none';
  document.getElementById('overlay-module').classList.add('show');
  document.getElementById('drawer-module').classList.add('show');
}
function closeModuleDrawer(){ document.getElementById('overlay-module').classList.remove('show'); document.getElementById('drawer-module').classList.remove('show'); }
async function saveModule(){
  const nome = document.getElementById('mf-nome').value.trim();
  const cicloId = document.getElementById('mf-ciclo').value;
  if(!nome || !cicloId){ toast('Informe ciclo e nome do módulo'); return; }
  const data = {
    cicloId, nome, etapa: document.getElementById('mf-etapa').value.trim(),
    ordem: parseInt(document.getElementById('mf-ordem').value)||0,
    categoria: document.getElementById('mf-categoria').value,
    status: document.getElementById('mf-status').value,
    descricao: document.getElementById('mf-desc').value.trim(),
    mentor: document.getElementById('mf-mentor').value.trim(),
    formato: document.getElementById('mf-formato').value.trim(),
    publicoAlvo: document.getElementById('mf-publico').value.trim(),
    cargaHoraria: document.getElementById('mf-carga').value.trim(),
    inicioPrevisto: document.getElementById('mf-inicio').value.trim(),
    linkMaterial: document.getElementById('mf-material').value.trim(),
  };
  if(!db){ toast('Banco indisponível'); return; }
  if(editingModuleId){ await db.doc('modulos/'+editingModuleId).update(data); }
  else { await db.collection('modulos').add(data); }
  closeModuleDrawer(); toast('Módulo salvo');
}
function askDeleteModule(){
  if(!editingModuleId) return;
  const m = moduloById(editingModuleId);
  openConfirm(`Excluir ${m?.nome||'módulo'}?`, 'O progresso dos colaboradores neste módulo também será removido.', async () => {
    await db.doc('modulos/'+editingModuleId).delete();
    for(const p of progresso.filter(p=>p.moduloId===editingModuleId)){ await db.doc('progresso/'+p.id).delete(); }
    closeModuleDrawer(); toast('Módulo excluído');
  });
}

/* ================= CICLOS (cadastro) ================= */
function renderCadastroCiclos(filter){
  const el = document.getElementById('ciclos-reg-list');
  if(dbUnavailable){ el.innerHTML = '<div class="empty-state">Este visualizador não tem acesso ao banco de dados do artifact.</div>'; return; }
  if(!isLoaded('ciclos')){ el.innerHTML = '<div class="loading-row"><span class="spinner"></span>Carregando ciclos…</div>'; return; }
  const f = norm(filter||'');
  const list = [...ciclos].filter(ci => norm(ci.nome).includes(f) || norm(trilhaById(ci.trilhaId)?.nome).includes(f))
    .sort((a,b)=> (trilhaById(a.trilhaId)?.nome||'').localeCompare(trilhaById(b.trilhaId)?.nome||'') || (a.ordem??0)-(b.ordem??0));
  if(!list.length){ el.innerHTML = ciclos.length===0 ? '<p class="colab-empty">Nenhum ciclo cadastrado ainda.</p>' : `<p class="colab-empty">Nenhum resultado para "${esc(filter||'')}".</p>`; return; }
  el.innerHTML = list.map(ci => {
    const nMod = modulosOf(ci.id).length;
    return `
    <div class="reg-row" onclick="openCycleModal('${ci.id}')">
      <div class="cycle-icon" style="width:26px;height:26px;flex-shrink:0;">${iconSvg(ci.icon)}</div>
      <div class="reg-row-main">
        <div class="reg-row-title">${esc(ci.nome)}</div>
        <div class="reg-row-sub">${esc(trilhaById(ci.trilhaId)?.nome || '—')}${ci.tema?' · '+esc(ci.tema):''} · ${nMod} módulo${nMod===1?'':'s'}</div>
      </div>
      <span class="btn-ghost-sm" style="pointer-events:none;">${editSvg()}</span>
    </div>`;
  }).join('');
}

/* ================= MÓDULOS (cadastro) ================= */
function renderCadastroModulos(filter){
  const el = document.getElementById('modulos-reg-list');
  if(dbUnavailable){ el.innerHTML = '<div class="empty-state">Este visualizador não tem acesso ao banco de dados do artifact.</div>'; return; }
  if(!isLoaded('modulos')){ el.innerHTML = '<div class="loading-row"><span class="spinner"></span>Carregando módulos…</div>'; return; }
  const f = norm(filter||'');
  const list = [...modulos].filter(m => norm(m.nome).includes(f) || norm(cicloById(m.cicloId)?.nome).includes(f))
    .sort((a,b)=> (a.ordem??0)-(b.ordem??0));
  if(!list.length){ el.innerHTML = modulos.length===0 ? '<p class="colab-empty">Nenhum módulo cadastrado ainda.</p>' : `<p class="colab-empty">Nenhum resultado para "${esc(filter||'')}".</p>`; return; }
  el.innerHTML = list.map(m => {
    const ci = cicloById(m.cicloId); const t = ci ? trilhaById(ci.trilhaId) : null;
    return `
    <div class="reg-row" onclick="openModuleForm('${m.id}')">
      <span class="cat-dot ${catDotClass(m.categoria)}" style="flex-shrink:0;"></span>
      <div class="reg-row-main">
        <div class="reg-row-title">${esc(m.nome)}</div>
        <div class="reg-row-sub">${esc(t?.nome||'—')} / ${esc(ci?.nome||'—')} · ${esc(m.status||'A iniciar')}</div>
      </div>
      <span class="btn-ghost-sm" style="pointer-events:none;">${editSvg()}</span>
    </div>`;
  }).join('');
}

/* ================= UNIDADES ================= */
function renderUnidades(){
  const el = document.getElementById('unidades-list');
  if(dbUnavailable){ el.innerHTML = '<div class="empty-state">Este visualizador não tem acesso ao banco de dados do artifact.</div>'; return; }
  if(!isLoaded('unidades')){ el.innerHTML = '<div class="loading-row"><span class="spinner"></span>Carregando…</div>'; return; }
  if(!unidades.length){ el.innerHTML = '<p style="font-size:12.5px;color:var(--ink-dim);">Nenhuma unidade cadastrada ainda. Use o campo abaixo para criar a primeira.</p>'; return; }
  el.innerHTML = unidades.map(u => {
    const count = colaboradores.filter(c=>c.unidadeId===u.id).length;
    if(unidadeEditingId===u.id){
      return `<div class="unidade-row">
        <input type="text" id="unidade-edit-input" value="${esc(u.nome)}" style="flex:1;font-family:var(--font);font-size:13.5px;padding:6px 9px;border:1px solid var(--line);border-radius:6px;background:var(--bg);color:var(--ink);">
        <button class="btn-ghost-sm" onclick="saveUnidadeEdit('${u.id}')" title="Salvar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 6 9 17l-5-5"/></svg></button>
        <button class="btn-ghost-sm" onclick="unidadeEditingId=null; renderUnidades();" title="Cancelar">×</button>
      </div>`;
    }
    return `<div class="unidade-row">
      <span class="unidade-name">${esc(u.nome)}</span>
      <span class="unidade-count">${count} colaborador${count===1?'':'es'}</span>
      <button class="btn-ghost-sm" onclick="unidadeEditingId='${u.id}'; renderUnidades();" title="Renomear">${editSvg()}</button>
      <button class="btn-ghost-sm" onclick="deleteUnidade('${u.id}', ${count})" title="Excluir">${trashSvg()}</button>
    </div>`;
  }).join('');
}
async function addUnidade(){
  const input = document.getElementById('unidade-new-nome');
  const nome = input.value.trim();
  if(!nome){ toast('Informe o nome da unidade'); return; }
  if(!db){ toast('Banco indisponível'); return; }
  await db.collection('unidades').add({nome});
  input.value = ''; toast('Unidade adicionada');
}
async function saveUnidadeEdit(id){
  const nome = document.getElementById('unidade-edit-input').value.trim();
  if(!nome) return;
  await db.doc('unidades/'+id).update({nome});
  unidadeEditingId = null; toast('Unidade atualizada');
}
function deleteUnidade(id, count){
  if(count>0){ toast('Mova os colaboradores desta unidade antes de excluí-la'); return; }
  const u = unidadeById(id);
  openConfirm(`Excluir ${u?.nome||'unidade'}?`, 'Esta ação não pode ser desfeita.', async () => {
    await db.doc('unidades/'+id).delete(); toast('Unidade excluída');
  });
}

/* ================= Confirm modal ================= */
function openConfirm(title, text, fn){
  document.getElementById('confirm-title').textContent = title;
  document.getElementById('confirm-text').textContent = text;
  confirmActionFn = fn;
  document.getElementById('overlay-confirm').classList.add('show');
  document.getElementById('modal-confirm').classList.add('show');
}
function closeConfirm(){ document.getElementById('overlay-confirm').classList.remove('show'); document.getElementById('modal-confirm').classList.remove('show'); confirmActionFn = null; }
async function confirmActionRun(){ const fn = confirmActionFn; closeConfirm(); if(fn) await fn(); }

boot();
</script>
<?php endif; ?>
</body>
</html>
