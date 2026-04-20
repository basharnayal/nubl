<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
<meta name="description" content="NUBL connects donors, recipients, and local food providers through dignified, private food support." />
<meta name="theme-color" content="#F5F4F1" />
<title>{{ config('app.name', 'NUBL') }} — Food support with dignity</title>

<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Instrument+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Amiri:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">

<style>
  :root {
    --gold: #F0AA1F;
    --gold-dk: #C98C0A;
    --gold-lt: #FBE8AD;
    --gold-bg: #FEF9EC;

    --rose: #E0305E;
    --rose-dk: #B8244C;
    --rose-lt: #F7B8CB;
    --rose-bg: #FEF0F4;

    --navy: #293765;
    --navy-dk: #1C2747;
    --navy-md: #3E4F80;
    --navy-lt: #A8B3D0;
    --navy-bg: #F0F3FA;

    --white: #FFFFFF;
    --off-white: #FAFAF9;
    --canvas: #F5F4F1;
    --border: #E3E2DC;
    --muted: #6B6E7A;
    --charcoal: #1A1D2B;

    --accent: var(--gold);
    --accent-dk: var(--gold-dk);
    --accent-lt: var(--gold-lt);
    --accent-bg: var(--gold-bg);

    --display: 'Instrument Serif', 'Amiri', Georgia, serif;
    --sans: 'Instrument Sans', 'IBM Plex Sans Arabic', system-ui, sans-serif;
    --mono: 'JetBrains Mono', ui-monospace, monospace;

    --density: 1;
    --max: 1280px;
  }

  [lang="ar"] {
    --display: 'IBM Plex Sans Arabic', system-ui, sans-serif;
    --sans: 'IBM Plex Sans Arabic', system-ui, sans-serif;
  }
  [lang="ar"] .display { font-weight: 500; }
  [lang="ar"] h1.display, [lang="ar"] h2.display, [lang="ar"] h3 { font-weight: 600; }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  html { scroll-behavior: smooth; }

  html, body {
    background: var(--canvas);
    color: var(--charcoal);
    font-family: var(--sans);
    font-size: 17px;
    line-height: 1.55;
    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
  }

  [lang="ar"] body { font-size: 18px; }

  a { color: inherit; }
  button { font-family: inherit; cursor: pointer; border: 0; background: none; color: inherit; }
  main { display: block; }

  .skip-link {
    position: fixed;
    top: 12px;
    inset-inline-start: 12px;
    z-index: 100;
    transform: translateY(-140%);
    padding: 10px 14px;
    border-radius: 8px;
    background: var(--navy-dk);
    color: var(--off-white);
    font-size: 14px;
    text-decoration: none;
    transition: transform .2s ease;
  }
  .skip-link:focus { transform: translateY(0); }
  :focus-visible {
    outline: 3px solid color-mix(in oklab, var(--accent) 80%, var(--white));
    outline-offset: 4px;
  }

  .wrap { max-width: var(--max); margin: 0 auto; padding: 0 clamp(20px, 4vw, 48px); }
  .eyebrow {
    font-family: var(--mono);
    font-size: 11px;
    letter-spacing: 0;
    text-transform: uppercase;
    color: var(--muted);
    display: inline-flex;
    gap: 10px;
    align-items: center;
  }
  [lang="ar"] .eyebrow { font-family: var(--sans); letter-spacing: 0; font-weight: 500; font-size: 13px; text-transform: none; }
  .eyebrow::before {
    content: '';
    width: 24px; height: 1px; background: var(--muted);
  }
  [lang="ar"] .eyebrow::before { width: 20px; }

  .display {
    font-family: var(--display);
    font-weight: 400;
    line-height: 1.02;
    letter-spacing: 0;
  }
  [lang="ar"] .display { line-height: 1.25; letter-spacing: 0; }

  .display i, .display em { font-style: italic; color: var(--navy); }
  [lang="ar"] .display i, [lang="ar"] .display em { font-style: normal; color: var(--navy); font-weight: 700; }

  /* ---------- Navigation ---------- */
  nav.site {
    position: sticky; top: 0; z-index: 40;
    background: color-mix(in oklab, var(--canvas) 88%, transparent);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid transparent;
    transition: border-color .3s;
  }
  nav.site.scrolled { border-color: var(--border); }
  nav.site .inner {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px clamp(20px, 4vw, 48px);
    max-width: var(--max); margin: 0 auto;
  }
  .logo {
    display: flex; align-items: center; gap: 10px;
    font-family: var(--display);
    font-size: 24px;
    letter-spacing: 0;
    color: var(--navy-dk);
  }
  .logo-mark {
    width: 28px; height: 28px;
    position: relative;
    display: inline-block;
  }
  .logo-mark svg { width: 100%; height: 100%; }
  .logo img.brand-logo {
    display: block;
    width: auto;
    height: 52px;
    object-fit: contain;
  }
  footer .brand-col .logo img.brand-logo {
    height: 58px;
    padding: 6px 8px;
    background: var(--off-white);
    border-radius: 8px;
  }
  @media (max-width: 760px) { .logo img.brand-logo { height: 44px; } }
  nav .links { display: flex; gap: clamp(12px, 2.2vw, 28px); align-items: center; }
  nav .links a { font-size: 14px; color: var(--charcoal); text-decoration: none; opacity: .78; }
  nav .links a:hover { opacity: 1; }
  @media (max-width: 760px) { nav .links a:not(.cta):not(.lang) { display: none; } }

  .lang {
    font-family: var(--mono);
    font-size: 11px;
    letter-spacing: 0;
    text-transform: uppercase;
    min-height: 44px;
    padding: 8px 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--muted);
    display: inline-flex; align-items: center; gap: 6px;
    background: var(--off-white);
  }
  [lang="ar"] .lang { font-family: var(--sans); }
  .lang:hover { color: var(--charcoal); border-color: var(--charcoal); }

  .cta {
    min-height: 44px;
    padding: 10px 18px;
    background: var(--navy-dk);
    color: var(--off-white);
    border-radius: 8px;
    font-size: 14px;
    display: inline-flex; align-items: center; gap: 8px;
    text-decoration: none;
    transition: transform .2s, background .2s;
  }
  .cta:hover { transform: translateY(-1px); background: var(--charcoal); }
  .cta.accent { background: var(--accent); color: var(--navy-dk); }
  .cta.accent:hover { background: var(--accent-dk); color: var(--off-white); }
  .cta svg { width: 14px; height: 14px; }
  [dir="rtl"] .cta svg { transform: scaleX(-1); }
  @media (max-width: 560px) {
    nav.site .inner { padding: 12px 16px; gap: 12px; }
    nav .links { gap: 8px; }
    .logo img.brand-logo { height: 38px; }
    .lang { min-height: 40px; padding: 7px 10px; font-size: 11px; }
    .cta { min-height: 40px; padding: 8px 12px; font-size: 13px; }
  }

  /* ---------- Hero ---------- */
  .hero { padding: 56px 0 80px; position: relative; overflow: hidden; }

  .hero-grid {
    display: grid;
    grid-template-columns: 1.35fr 1fr;
    gap: clamp(32px, 5vw, 80px);
    align-items: end;
  }
  @media (max-width: 900px) { .hero-grid { grid-template-columns: 1fr; align-items: start; } }

  .hero h1 {
    font-size: 5.75rem;
    max-width: 11ch;
  }
  [lang="ar"] .hero h1 { font-size: 5.3rem; max-width: 13ch; }

  .hero .sub {
    margin-top: clamp(28px, 4vw, 48px);
    font-size: 1.125rem;
    color: var(--muted);
    max-width: 44ch;
    line-height: 1.6;
  }
  .hero .sub strong { color: var(--charcoal); font-weight: 500; }

  .hero-ctas {
    display: flex; flex-wrap: wrap; gap: 12px; margin-top: 36px;
  }
  .hero-ctas .cta { padding: 14px 22px; font-size: 15px; }

  /* Aggregated panel */
  .agg-card {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 2px;
    padding: 28px;
    position: relative;
    box-shadow: 0 1px 0 rgba(26,29,43,0.02), 0 20px 40px -30px rgba(26,29,43,0.2);
  }
  .agg-head {
    display: flex; justify-content: space-between; align-items: center;
    padding-bottom: 18px;
    border-bottom: 1px solid var(--border);
  }
  .agg-head .stamp {
    display: inline-flex; align-items: center; gap: 8px;
    font-family: var(--mono); font-size: 10px;
    text-transform: uppercase; letter-spacing: 0;
    color: var(--muted);
  }
  [lang="ar"] .agg-head .stamp { font-family: var(--sans); text-transform: none; letter-spacing: 0; font-size: 12px; }
  .stamp .dot { width: 7px; height: 7px; background: #2AAE5F; border-radius: 99px; box-shadow: 0 0 0 4px color-mix(in oklab, #2AAE5F 16%, transparent); }

  .agg-big {
    margin-top: 22px;
    font-family: var(--display);
    font-size: 4.9rem;
    line-height: 1;
    color: var(--navy-dk);
    letter-spacing: 0;
    font-feature-settings: "tnum";
  }
  .agg-big .unit { font-size: 0.3em; color: var(--muted); margin-inline-end: 10px; vertical-align: super; font-family: var(--mono); letter-spacing: 0; }
  [lang="ar"] .agg-big .unit { font-family: var(--sans); }

  .agg-label { font-size: 14px; color: var(--muted); margin-top: 6px; }

  .agg-split { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 24px; padding-top: 22px; border-top: 1px dashed var(--border); }
  .agg-split .num { font-family: var(--display); font-size: 32px; color: var(--navy); line-height: 1; font-feature-settings: "tnum"; }
  .agg-split .tag { font-size: 12px; color: var(--muted); margin-top: 6px; font-family: var(--mono); letter-spacing: 0; text-transform: uppercase;}
  [lang="ar"] .agg-split .tag { font-family: var(--sans); text-transform: none; letter-spacing: 0; font-size: 13px; }

  /* Live feed */
  .feed {
    margin-top: 20px;
    background: var(--navy-dk);
    color: var(--off-white);
    border-radius: 2px;
    padding: 20px 22px;
    overflow: hidden;
    position: relative;
  }
  .feed-head {
    display: flex; align-items: center; justify-content: space-between;
    font-family: var(--mono); font-size: 10px; text-transform: uppercase; letter-spacing: 0;
    color: var(--navy-lt);
    padding-bottom: 10px;
    border-bottom: 1px solid color-mix(in oklab, var(--navy-lt) 30%, transparent);
  }
  [lang="ar"] .feed-head { font-family: var(--sans); text-transform: none; letter-spacing: 0; font-size: 12px; }
  .feed-head .live { display: flex; align-items: center; gap: 8px; color: var(--gold-lt); }
  .feed-head .live .dot { width: 6px; height: 6px; border-radius: 99px; background: var(--gold); animation: pulse 2s infinite; }
  @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.3; } }

  .feed-list { position: relative; height: 84px; margin-top: 10px; }
  .feed-item {
    position: absolute; inset: 0;
    display: flex; flex-direction: column; justify-content: center;
    opacity: 0; transform: translateY(12px);
    transition: opacity .5s, transform .5s;
  }
  .feed-item.active { opacity: 1; transform: translateY(0); }
  .feed-item .row1 { font-family: var(--display); font-size: 20px; letter-spacing: 0; line-height: 1.3; }
  .feed-item .row2 { font-family: var(--mono); font-size: 11px; color: var(--navy-lt); margin-top: 8px; letter-spacing: 0; }
  [lang="ar"] .feed-item .row2 { font-family: var(--sans); letter-spacing: 0; font-size: 13px; }

  /* Sigil grid behind hero */
  .sigil {
    position: absolute;
    top: 0; inset-inline-end: 0;
    width: 45%;
    height: 100%;
    pointer-events: none;
    opacity: 0.35;
    mask-image: linear-gradient(to bottom left, black, transparent 70%);
    -webkit-mask-image: linear-gradient(to bottom left, black, transparent 70%);
  }
  .sigil svg { width: 100%; height: 100%; }

  /* ---------- Manifesto ---------- */
  section.manifesto {
    background: var(--navy-dk);
    color: var(--off-white);
    padding: clamp(80px, 12vw, 160px) 0;
    position: relative;
    overflow: hidden;
  }
  .manifesto-grid {
    display: grid; grid-template-columns: 0.6fr 1.4fr; gap: clamp(40px, 6vw, 100px);
    align-items: start;
  }
  @media (max-width: 900px) { .manifesto-grid { grid-template-columns: 1fr; } }
  .manifesto .eyebrow { color: var(--navy-lt); }
  .manifesto .eyebrow::before { background: var(--navy-lt); }

  .manifesto h2 {
    font-size: 3.25rem;
    max-width: 22ch;
    font-weight: 400;
  }
  .manifesto h2 em { color: var(--gold-lt); font-style: italic; }
  [lang="ar"] .manifesto h2 em { font-style: normal; color: var(--gold-lt); font-weight: 700; }

  .manifesto p.lede {
    margin-top: 32px;
    font-size: 1.15rem;
    color: var(--navy-lt);
    max-width: 58ch; line-height: 1.65;
  }

  .manifesto-aside {
    padding-top: 10px;
    font-family: var(--mono);
    font-size: 11px; letter-spacing: 0; text-transform: uppercase;
    color: var(--navy-lt);
  }
  [lang="ar"] .manifesto-aside { font-family: var(--sans); text-transform: none; letter-spacing: 0; font-size: 13px; }

  .manifesto-aside hr { border: 0; border-top: 1px solid color-mix(in oklab, var(--navy-lt) 50%, transparent); margin: 16px 0; }

  /* Bleed texture */
  .manifesto::before {
    content: ''; position: absolute; inset: 0;
    background-image:
      radial-gradient(circle at 20% 30%, color-mix(in oklab, var(--gold) 20%, transparent) 0, transparent 40%),
      radial-gradient(circle at 80% 70%, color-mix(in oklab, var(--rose) 18%, transparent) 0, transparent 50%);
    opacity: 0.6; pointer-events: none;
  }

  /* ---------- How it works ---------- */
  section.flow { padding: clamp(80px, 12vw, 140px) 0; }
  .flow-head {
    display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: end;
    margin-bottom: clamp(48px, 6vw, 80px);
  }
  @media (max-width: 900px) { .flow-head { grid-template-columns: 1fr; gap: 24px; } }

  .flow-head h2 {
    font-size: 3.75rem;
    max-width: 16ch;
  }
  .flow-head p {
    color: var(--muted);
    font-size: 17px;
    max-width: 46ch;
  }

  .flow-diagram {
    position: relative;
    padding: 40px 0;
  }

  .flow-track {
    display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px;
    position: relative;
  }
  @media (max-width: 900px) { .flow-track { grid-template-columns: 1fr; } }

  .role-card {
    background: var(--white);
    border: 1px solid var(--border);
    padding: 32px 28px 28px;
    position: relative;
    display: flex; flex-direction: column;
    min-height: 360px;
    transition: transform .3s, box-shadow .3s;
  }
  .role-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px -24px rgba(26,29,43,.18); }

  .role-card .step {
    font-family: var(--mono); font-size: 11px; letter-spacing: 0; color: var(--muted);
    text-transform: uppercase;
  }
  [lang="ar"] .role-card .step { font-family: var(--sans); text-transform: none; letter-spacing: 0; font-size: 13px; }

  .role-card .icon {
    width: 56px; height: 56px;
    margin-top: 18px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 2px;
  }
  .role-card.supporter .icon { background: var(--gold-bg); color: var(--gold-dk); }
  .role-card.provider .icon { background: var(--navy-bg); color: var(--navy-dk); }
  .role-card.recipient .icon { background: var(--rose-bg); color: var(--rose-dk); }

  .role-card h3 {
    margin-top: 20px;
    font-family: var(--display); font-size: 32px; font-weight: 400;
    letter-spacing: 0; line-height: 1.1;
  }
  [lang="ar"] .role-card h3 { line-height: 1.3; }

  .role-card .role-body { color: var(--muted); font-size: 15px; margin-top: 12px; line-height: 1.6; flex: 1; }

  .role-actions { margin-top: 22px; padding-top: 18px; border-top: 1px solid var(--border); }
  .role-actions a { font-size: 14px; color: var(--charcoal); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-weight: 500; }
  .role-actions a:hover { color: var(--accent-dk); }
  [dir="rtl"] .role-actions a svg { transform: scaleX(-1); }

  .role-card .accent-dot {
    position: absolute; top: 28px; inset-inline-end: 28px;
    width: 10px; height: 10px; border-radius: 99px;
  }
  .role-card.supporter .accent-dot { background: var(--gold); }
  .role-card.provider .accent-dot { background: var(--navy); }
  .role-card.recipient .accent-dot { background: var(--rose); }

  /* Flow connector */
  .flow-connector {
    position: absolute;
    top: 50%; left: 0; right: 0;
    height: 1px;
    background: repeating-linear-gradient(90deg, var(--border) 0 6px, transparent 6px 12px);
    z-index: -1;
  }
  @media (max-width: 900px) { .flow-connector { display: none; } }

  /* ---------- Privacy ---------- */
  section.privacy {
    background: var(--gold-bg);
    padding: clamp(80px, 12vw, 140px) 0;
    position: relative;
    overflow: hidden;
  }

  .privacy-grid {
    display: grid; grid-template-columns: 1.1fr 0.9fr;
    gap: clamp(48px, 6vw, 100px); align-items: center;
  }
  @media (max-width: 900px) { .privacy-grid { grid-template-columns: 1fr; } }

  .privacy h2 {
    font-size: 3.75rem;
    max-width: 14ch;
  }
  .privacy h2 em { color: var(--rose-dk); }
  .privacy .body { margin-top: 28px; font-size: 17px; color: var(--charcoal); max-width: 50ch; line-height: 1.65; opacity: 0.85; }

  .privacy-pills { margin-top: 32px; display: flex; flex-wrap: wrap; gap: 8px; }
  .pill {
    padding: 8px 14px;
    border: 1px solid color-mix(in oklab, var(--gold-dk) 30%, transparent);
    border-radius: 999px;
    font-size: 13px;
    background: color-mix(in oklab, var(--white) 60%, transparent);
    color: var(--charcoal);
  }

  /* Before/after identity card */
  .card-pair {
    display: grid; gap: 16px; position: relative;
  }
  .id-card {
    background: var(--white);
    border: 1px solid var(--border);
    padding: 22px;
    position: relative;
    border-radius: 2px;
  }
  .id-card .title-line {
    font-family: var(--mono); font-size: 10px; letter-spacing: 0; text-transform: uppercase;
    color: var(--muted); display: flex; justify-content: space-between;
  }
  [lang="ar"] .id-card .title-line { font-family: var(--sans); text-transform: none; letter-spacing: 0; font-size: 12px; }
  .id-card.clear .title-line { color: var(--rose-dk); }
  .id-card.protected .title-line { color: var(--gold-dk); }

  .id-row { display: grid; grid-template-columns: 110px 1fr; gap: 16px; padding: 10px 0; border-bottom: 1px dashed var(--border); align-items: baseline; }
  .id-row:last-child { border: 0; }
  .id-row .k { font-family: var(--mono); font-size: 11px; letter-spacing: 0; color: var(--muted); text-transform: uppercase; }
  [lang="ar"] .id-row .k { font-family: var(--sans); text-transform: none; letter-spacing: 0; font-size: 13px; }
  .id-row .v { font-size: 15px; color: var(--charcoal); }
  .id-row .v.redacted {
    display: inline-block;
    background: var(--navy-dk);
    color: var(--navy-dk);
    border-radius: 2px;
    user-select: none;
    padding: 2px 0;
  }
  .id-row .v.token {
    font-family: var(--mono); background: var(--navy-bg); padding: 3px 8px; border-radius: 2px; color: var(--navy-dk);
  }

  .arrow-down {
    display: flex; align-items: center; justify-content: center; color: var(--gold-dk);
    font-family: var(--mono); font-size: 11px; letter-spacing: 0; text-transform: uppercase; gap: 8px;
  }
  [lang="ar"] .arrow-down { font-family: var(--sans); text-transform: none; letter-spacing: 0; font-size: 13px; }

  /* ---------- Trust & Transparency ---------- */
  section.trust { padding: clamp(80px, 12vw, 140px) 0; }
  .trust-grid { display: grid; grid-template-columns: 1fr 1fr; gap: clamp(32px, 4vw, 56px); }
  @media (max-width: 900px) { .trust-grid { grid-template-columns: 1fr; } }

  .trust-left h2 { font-size: 3.75rem; max-width: 14ch; }
  .trust-left p { color: var(--muted); margin-top: 24px; font-size: 17px; max-width: 42ch; line-height: 1.65; }

  .transparency-ledger {
    background: var(--off-white);
    border: 1px solid var(--border);
    padding: 28px;
  }
  .ledger-head {
    display: flex; justify-content: space-between; align-items: baseline;
    padding-bottom: 14px; border-bottom: 1px solid var(--border); margin-bottom: 18px;
  }
  .ledger-head h4 { font-family: var(--display); font-size: 22px; font-weight: 400; }
  .ledger-head .date { font-family: var(--mono); font-size: 11px; color: var(--muted); }

  .ledger-row {
    display: grid; grid-template-columns: 1fr auto;
    padding: 12px 0; border-bottom: 1px dashed var(--border); align-items: center;
    gap: 14px;
  }
  .ledger-row:last-child { border: 0; }
  .ledger-row .desc { font-size: 14.5px; color: var(--charcoal); }
  .ledger-row .meta { font-family: var(--mono); font-size: 11px; color: var(--muted); margin-top: 3px; }
  [lang="ar"] .ledger-row .meta { font-family: var(--sans); font-size: 12px; }
  .ledger-row .amt { font-family: var(--display); font-size: 22px; color: var(--navy-dk); font-feature-settings: "tnum"; white-space: nowrap; }
  .ledger-row .amt .u { font-family: var(--mono); font-size: 10px; color: var(--muted); margin-inline-start: 4px; }
  [lang="ar"] .ledger-row .amt .u { font-family: var(--sans); font-size: 12px; }

  .ledger-foot { margin-top: 18px; display: flex; justify-content: space-between; align-items: center; padding-top: 14px; border-top: 1px solid var(--border); }
  .ledger-foot a { font-size: 13px; color: var(--navy-dk); text-decoration: underline; text-underline-offset: 4px; }

  .trust-badges { margin-top: 28px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
  @media (max-width: 640px) { .trust-badges { grid-template-columns: 1fr; } }
  .badge-card {
    border: 1px solid var(--border);
    padding: 18px;
    background: var(--white);
  }
  .badge-card .n { font-family: var(--display); font-size: 30px; color: var(--navy-dk); line-height: 1; }
  .badge-card .t { font-size: 12px; color: var(--muted); margin-top: 8px; }

  /* ---------- Providers ---------- */
  section.providers {
    padding: clamp(80px, 12vw, 140px) 0;
    background: var(--canvas);
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
  }
  .providers-intro { max-width: 780px; margin-bottom: 56px; }
  .providers-intro h2 { font-size: 3.6rem; }
  .providers-intro h2 em { color: var(--gold-dk); }
  .providers-intro p { color: var(--muted); margin-top: 22px; font-size: 17px; line-height: 1.65; max-width: 56ch; }

  .provider-types { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0; border: 1px solid var(--border); background: var(--white); }
  @media (max-width: 900px) { .provider-types { grid-template-columns: 1fr 1fr; } }
  @media (max-width: 560px) { .provider-types { grid-template-columns: 1fr; } }

  .provider-cell {
    padding: 28px 24px;
    border-inline-end: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
    display: flex; flex-direction: column;
    min-height: 200px;
  }
  .provider-cell:last-child { border-inline-end: 0; }
  @media (max-width: 900px) {
    .provider-cell:nth-child(2n) { border-inline-end: 0; }
  }

  .provider-cell .glyph {
    width: 44px; height: 44px;
    display: flex; align-items: center; justify-content: center;
    color: var(--navy-dk);
  }
  .provider-cell h4 { font-family: var(--display); font-size: 26px; font-weight: 400; margin-top: 18px; line-height: 1.15; }
  .provider-cell p { color: var(--muted); font-size: 14px; margin-top: 10px; line-height: 1.6; flex: 1; }
  .provider-cell .cnt { margin-top: 16px; font-family: var(--mono); font-size: 11px; color: var(--navy-md); letter-spacing: 0; text-transform: uppercase; }
  [lang="ar"] .provider-cell .cnt { font-family: var(--sans); text-transform: none; letter-spacing: 0; font-size: 13px; font-weight: 500; }
  .provider-cell .cnt b { color: var(--navy-dk); font-weight: 600; font-family: var(--display); font-size: 18px; margin-inline-end: 6px; }
  [lang="ar"] .provider-cell .cnt b { font-family: var(--sans); }

  /* Provider strip */
  .provider-strip {
    margin-top: 40px;
    padding: 28px;
    background: var(--navy-bg);
    display: flex; align-items: center; justify-content: space-between; gap: 32px; flex-wrap: wrap;
  }
  .provider-strip .big {
    font-family: var(--display); font-size: 2.25rem; line-height: 1.2; max-width: 30ch;
  }

  /* ---------- Stories ---------- */
  section.stories { padding: clamp(80px, 12vw, 140px) 0; }
  .stories-head { display: flex; justify-content: space-between; align-items: end; margin-bottom: 48px; flex-wrap: wrap; gap: 20px; }
  .stories-head h2 { font-size: 3.6rem; max-width: 16ch; }

  .stories-grid { display: grid; grid-template-columns: 1.2fr 1fr 1fr; gap: 20px; }
  @media (max-width: 900px) { .stories-grid { grid-template-columns: 1fr; } }

  .story {
    border: 1px solid var(--border);
    background: var(--white);
    padding: 32px;
    display: flex; flex-direction: column;
    min-height: 340px;
    position: relative;
    overflow: hidden;
  }
  .story.feature { background: var(--rose-bg); border-color: color-mix(in oklab, var(--rose) 30%, var(--border)); }
  .story.feature-2 { background: var(--navy-dk); color: var(--off-white); border-color: var(--navy-dk); }
  .story.feature-2 .story-meta { color: var(--navy-lt); }

  .story .quote {
    font-family: var(--display);
    font-size: 1.3rem;
    line-height: 1.55; letter-spacing: 0;
    flex: 1;
  }
  [lang="ar"] .story .quote { line-height: 1.65; }
  .story .quote::before { content: '“'; font-size: 2em; line-height: 0.5; vertical-align: -0.3em; margin-inline-end: 4px; color: var(--rose); }
  [lang="ar"] .story .quote::before { content: '"'; font-size: 1.3em; }
  .story.feature-2 .quote::before { color: var(--gold); }

  .story-meta {
    margin-top: 28px; padding-top: 18px; border-top: 1px solid color-mix(in oklab, currentColor 15%, transparent);
    display: flex; justify-content: space-between; align-items: baseline; gap: 16px;
    font-family: var(--mono); font-size: 11px; letter-spacing: 0; text-transform: uppercase; color: var(--muted);
  }
  [lang="ar"] .story-meta { font-family: var(--sans); text-transform: none; letter-spacing: 0; font-size: 12px; }
  .story-meta .role { color: var(--charcoal); font-weight: 500; }
  .story.feature-2 .story-meta .role { color: var(--gold-lt); }

  /* ---------- CTA triad ---------- */
  section.cta-band {
    background: var(--navy-dk);
    color: var(--off-white);
    padding: clamp(80px, 12vw, 140px) 0 clamp(60px, 10vw, 100px);
    position: relative;
    overflow: hidden;
  }
  .cta-band .eyebrow { color: var(--gold-lt); }
  .cta-band .eyebrow::before { background: var(--gold-lt); }
  .cta-band h2 {
    font-size: 4.75rem;
    max-width: 16ch; margin-top: 20px;
    color: var(--off-white);
  }
  .cta-band h2 em { color: var(--gold-lt); }

  .cta-triad { margin-top: clamp(48px, 6vw, 72px); display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
  @media (max-width: 900px) { .cta-triad { grid-template-columns: 1fr; } }

  .cta-card {
    background: color-mix(in oklab, var(--off-white) 6%, var(--navy-dk));
    border: 1px solid color-mix(in oklab, var(--navy-lt) 30%, transparent);
    padding: 32px 28px;
    display: flex; flex-direction: column;
    min-height: 260px;
    position: relative;
    transition: background .3s, transform .3s, border-color .3s;
    text-decoration: none; color: inherit;
  }
  .cta-card:hover { transform: translateY(-4px); }
  .cta-card.donate:hover { background: var(--gold); color: var(--navy-dk); border-color: var(--gold); }
  .cta-card.request:hover { background: var(--rose); color: var(--white); border-color: var(--rose); }
  .cta-card.join:hover { background: var(--off-white); color: var(--navy-dk); border-color: var(--off-white); }

  .cta-card .num { font-family: var(--mono); font-size: 11px; color: var(--navy-lt); letter-spacing: 0; }
  [lang="ar"] .cta-card .num { font-family: var(--sans); letter-spacing: 0; font-size: 13px; }
  .cta-card h3 { font-family: var(--display); font-size: 36px; font-weight: 400; margin-top: 40px; line-height: 1.1; }
  [lang="ar"] .cta-card h3 { line-height: 1.3; }
  .cta-card p { color: var(--navy-lt); font-size: 14.5px; margin-top: 10px; flex: 1; line-height: 1.6; }
  .cta-card:hover p { color: inherit; opacity: 0.9; }
  .cta-card .go {
    margin-top: 28px; display: inline-flex; align-items: center; gap: 8px;
    font-size: 14px; font-weight: 500;
  }
  [dir="rtl"] .cta-card .go svg { transform: scaleX(-1); }

  /* ---------- Footer ---------- */
  footer {
    background: var(--navy-dk);
    color: var(--navy-lt);
    padding: 60px 0 40px;
    border-top: 1px solid color-mix(in oklab, var(--navy-lt) 20%, transparent);
  }
  footer .rows { display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr; gap: 32px; }
  @media (max-width: 760px) { footer .rows { grid-template-columns: 1fr 1fr; gap: 24px; } }
  @media (max-width: 560px) { footer .rows { grid-template-columns: 1fr; } }
  footer h5 { font-family: var(--mono); font-size: 11px; letter-spacing: 0; text-transform: uppercase; color: var(--navy-lt); margin-bottom: 14px; }
  [lang="ar"] footer h5 { font-family: var(--sans); text-transform: none; letter-spacing: 0; font-size: 13px; }
  footer a { display: block; color: var(--off-white); text-decoration: none; font-size: 14px; margin-bottom: 10px; opacity: .85; }
  footer a:hover { opacity: 1; }
  footer .brand-col .logo { color: var(--off-white); font-size: 28px; }
  footer .brand-col p { color: var(--navy-lt); margin-top: 16px; font-size: 14px; line-height: 1.6; max-width: 30ch; }
  footer .bottom { margin-top: 48px; padding-top: 24px; border-top: 1px solid color-mix(in oklab, var(--navy-lt) 20%, transparent); display: flex; justify-content: space-between; flex-wrap: wrap; gap: 12px; font-size: 12px; }

  /* ---------- Reveal anim ---------- */
  .reveal { opacity: 0; transform: translateY(14px); transition: opacity .7s ease-out, transform .7s ease-out; }
  .reveal.on { opacity: 1; transform: none; }

  @media (max-width: 900px) {
    .hero { padding: 44px 0 64px; }
    .hero h1, [lang="ar"] .hero h1 { font-size: 4rem; }
    .agg-big { font-size: 4rem; }
    .manifesto h2,
    .flow-head h2,
    .privacy h2,
    .trust-left h2,
    .providers-intro h2,
    .stories-head h2 {
      font-size: 2.75rem;
    }
    .cta-band h2 { font-size: 3.25rem; }
    .provider-strip .big { font-size: 1.85rem; }
    .story { min-height: auto; }
  }

  @media (max-width: 560px) {
    .wrap { padding-inline: 16px; }
    .hero { padding: 22px 0 32px; }
    .hero h1, [lang="ar"] .hero h1 { font-size: 2.75rem; max-width: 12ch; }
    .hero .sub,
    .manifesto p.lede,
    .flow-head p,
    .privacy .body,
    .trust-left p,
    .providers-intro p {
      font-size: 1rem;
    }
    .hero-ctas { gap: 10px; }
    .hero-ctas .cta { width: 100%; justify-content: center; }
    .agg-card { padding: 18px; }
    .agg-head { padding-bottom: 12px; }
    .agg-big { font-size: 2.8rem; margin-top: 16px; }
    .agg-split,
    .feed {
      display: none;
    }
    .manifesto h2,
    .flow-head h2,
    .privacy h2,
    .trust-left h2,
    .providers-intro h2,
    .stories-head h2 {
      font-size: 2.25rem;
    }
    .role-card,
    .story,
    .cta-card {
      padding: 24px;
    }
    .story .quote { font-size: 1.1rem; }
    .story-meta { align-items: flex-start; flex-direction: column; gap: 6px; }
    .cta-band h2 { font-size: 2.5rem; }
    .cta-card h3 { font-size: 1.75rem; margin-top: 28px; }
  }

  @media (max-width: 420px) {
    .hero h1, [lang="ar"] .hero h1 { font-size: 2.35rem; }
    .hero .sub { margin-top: 18px; }
    .hero-ctas { margin-top: 22px; }
    .hero-impact { display: none; }
  }

  @media (prefers-reduced-motion: reduce) {
    html { scroll-behavior: auto; }
    *, *::before, *::after {
      animation-duration: 0.01ms !important;
      animation-iteration-count: 1 !important;
      transition-duration: 0.01ms !important;
    }
    .reveal { opacity: 1; transform: none; }
    .feed-item { transform: none; }
  }

  /* ---------- Tweaks panel ---------- */
  #tweaks-root { position: fixed; bottom: 20px; inset-inline-end: 20px; z-index: 80; display: none; }
  #tweaks-root.on { display: block; }
  .tweaks-window {
    background: var(--charcoal);
    color: var(--off-white);
    width: 300px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 20px 40px -10px rgba(0,0,0,.35);
    font-family: var(--sans);
  }
  .tweaks-head {
    padding: 12px 16px; display: flex; align-items: center; justify-content: space-between;
    font-family: var(--mono); font-size: 11px; letter-spacing: 0; text-transform: uppercase;
    background: #000;
  }
  .tweaks-body { padding: 14px 16px; display: flex; flex-direction: column; gap: 14px; }
  .tweak-row label { font-size: 11px; text-transform: uppercase; letter-spacing: 0; color: #9a9ca5; display: block; margin-bottom: 6px; }
  .tweak-row .opts { display: flex; gap: 6px; flex-wrap: wrap; }
  .tweak-row .opts button {
    padding: 8px 10px; background: #222633; color: #ddd; border-radius: 4px; font-size: 12px; flex: 1;
  }
  .tweak-row .opts button.active { background: var(--accent); color: var(--navy-dk); }

  /* Density */
  html[data-density="compact"] { --max: 1200px; }
  html[data-density="compact"] .hero { padding: 32px 0 60px; }
  html[data-density="compact"] section { padding-top: 60px !important; padding-bottom: 60px !important; }
  html[data-density="airy"] section { padding-top: 160px !important; padding-bottom: 160px !important; }

</style>
</head>
<body>
<a class="skip-link" href="#main-content">{{ app()->getLocale() === 'ar' ? 'تجاوز إلى المحتوى' : 'Skip to content' }}</a>

<!-- ========== NAV ========== -->
<nav class="site" id="nav" aria-label="{{ app()->getLocale() === 'ar' ? 'التنقل الرئيسي' : 'Primary navigation' }}">
  <div class="inner">
    <a href="{{ url('/') }}" class="logo" aria-label="{{ config('app.name', 'NUBL') }}">
      <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name', 'NUBL') }}" class="brand-logo" />
    </a>

    <div class="links">
      <a href="#idea" data-i18n="nav_idea">The idea</a>
      <a href="#how" data-i18n="nav_how">How it works</a>
      <a href="#trust" data-i18n="nav_trust">Trust</a>
      <a href="#providers" data-i18n="nav_providers">Providers</a>
      <button class="lang" id="langToggle" type="button" aria-label="{{ app()->getLocale() === 'ar' ? 'التبديل إلى الإنجليزية' : 'Switch to Arabic' }}" data-en-url="{{ route('locale.switch', 'en') }}" data-ar-url="{{ route('locale.switch', 'ar') }}">
        <span id="langLabel">{{ app()->getLocale() === 'ar' ? 'English' : 'العربية' }}</span>
      </button>
      <a href="{{ route('register', ['type' => 'donor']) }}" class="cta accent">
        <span data-i18n="nav_give">Give support</span>
        <svg viewBox="0 0 16 16" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
      <a href="{{ url('/dashboard') }}" class="cta accent">
        <span data-i18n="nav_give">Dashboard</span>
        <svg viewBox="0 0 16 16" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
    </div>
  </div>
</nav>

<main id="main-content">
<!-- ========== HERO ========== -->
<header class="hero" id="top">
  <div class="sigil" aria-hidden="true">
    <svg viewBox="0 0 400 500" preserveAspectRatio="xMidYMid slice">
      <defs>
        <pattern id="dots" x="0" y="0" width="24" height="24" patternUnits="userSpaceOnUse">
          <circle cx="1" cy="1" r="1" fill="#C98C0A" opacity="0.35"/>
        </pattern>
      </defs>
      <rect width="400" height="500" fill="url(#dots)"/>
      <circle cx="280" cy="200" r="140" fill="none" stroke="#F0AA1F" stroke-width="1" opacity="0.4"/>
      <circle cx="280" cy="200" r="90" fill="none" stroke="#E0305E" stroke-width="1" opacity="0.3"/>
      <circle cx="280" cy="200" r="40" fill="#F0AA1F" opacity="0.15"/>
    </svg>
  </div>

  <div class="wrap hero-grid">
    <div>
      <span class="eyebrow reveal" data-i18n="hero_eyebrow">A platform for dignified food support</span>
      <h1 class="display reveal" style="margin-top: 28px;">
        <span data-i18n="hero_h1_1">Support,</span> <i data-i18n="hero_h1_2">delivered quietly</i> <span data-i18n="hero_h1_3">by people who already care.</span>
      </h1>
      <p class="sub reveal" data-i18n="hero_sub">NUBL routes food support through <strong>trusted local providers</strong> already in your city — so help arrives with dignity, not a label.</p>
      <div class="hero-ctas reveal">
        <a href="{{ route('register', ['type' => 'donor']) }}" class="cta accent">
          <span data-i18n="hero_cta1">Support a family</span>
          <svg viewBox="0 0 16 16" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
        <a href="{{ route('register', ['type' => 'recipient']) }}" class="cta" style="background: var(--white); color: var(--charcoal); border: 1px solid var(--border);">
          <span data-i18n="hero_cta2">Request support</span>
          <svg viewBox="0 0 16 16" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
      </div>
    </div>

    <div class="reveal hero-impact" style="transition-delay: .15s;">
      <div class="agg-card">
        <div class="agg-head">
          <span class="stamp"><span class="dot"></span><span data-i18n="impact_live">Impact · updated live</span></span>
          <span class="stamp" style="font-family: var(--mono);" data-i18n="impact_q">Q2 · 2026</span>
        </div>
        <div class="agg-big" data-counter="1842360"><span class="unit" data-i18n="amount_unit">SAR</span>0</div>
        <div class="agg-label" data-i18n="amount_label">delivered this quarter across the network</div>

        <div class="agg-split">
          <div>
            <div class="num" data-counter="2184">0</div>
            <div class="tag" data-i18n="families_tag">families supported</div>
          </div>
          <div>
            <div class="num" data-counter="347">0</div>
            <div class="tag" data-i18n="providers_tag">local providers</div>
          </div>
        </div>
      </div>

      <div class="feed" aria-live="off">
        <div class="feed-head">
          <span class="live"><span class="dot"></span><span data-i18n="live">Live ledger</span></span>
          <span data-i18n="anonymized">anonymized · verified</span>
        </div>
        <div class="feed-list" id="feedList"></div>
      </div>
    </div>
  </div>
</header>

<!-- ========== MANIFESTO / IDEA ========== -->
<section class="manifesto" id="idea">
  <div class="wrap manifesto-grid">
    <aside class="manifesto-aside">
      <span data-i18n="chapter1">Chapter 01</span>
      <hr/>
      <span data-i18n="chapter1_label">The idea</span>
    </aside>
    <div>
      <span class="eyebrow" data-i18n="manifesto_eyebrow">Why we built NUBL</span>
      <h2 class="display" style="margin-top: 24px;">
        <span data-i18n="manifesto_h_1">Need is private.</span> <em data-i18n="manifesto_h_2">So is dignity.</em> <span data-i18n="manifesto_h_3">Support should honor both.</span>
      </h2>
      <p class="lede" data-i18n="manifesto_p">
        Asking for help shouldn't cost you your privacy. NUBL routes everyday grocery credit to eligible households through the shops they already use — quietly, documented, and accountable to everyone.
      </p>
    </div>
  </div>
</section>

<!-- ========== HOW IT WORKS ========== -->
<section class="flow" id="how">
  <div class="wrap">
    <div class="flow-head">
      <div>
        <span class="eyebrow" data-i18n="chapter2">Chapter 02 · How it works</span>
        <h2 class="display" style="margin-top: 20px;">
          <span data-i18n="how_h_1">Three people.</span> <i data-i18n="how_h_2">One quiet circuit.</i>
        </h2>
      </div>
      <p data-i18n="how_p">
        Every transaction on NUBL travels through a structured path. Supporters contribute, providers fulfill, recipients receive — each role protected, each step documented.
      </p>
    </div>

    <div class="flow-diagram">
      <div class="flow-connector" aria-hidden="true"></div>
      <div class="flow-track">

        <article class="role-card supporter">
          <span class="accent-dot"></span>
          <span class="step" data-i18n="step1">Step 01 · Supporter</span>
          <div class="icon">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
              <path d="M14 25S4 18 4 11a6 6 0 0 1 10-4.5A6 6 0 0 1 24 11c0 7-10 14-10 14z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
            </svg>
          </div>
          <h3 data-i18n="role1_h">Choose how to give.</h3>
          <p class="role-body" data-i18n="role1_p">One-time, monthly, or directed to a category. Every contribution shows up in your impact summary.</p>
          <div class="role-actions">
            <a href="{{ route('register', ['type' => 'donor']) }}"><span data-i18n="role1_cta">Become a supporter</span>
              <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
          </div>
        </article>

        <article class="role-card provider">
          <span class="accent-dot"></span>
          <span class="step" data-i18n="step2">Step 02 · Provider</span>
          <div class="icon">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
              <path d="M4 10h20l-1.5 12a2 2 0 0 1-2 1.8H7.5a2 2 0 0 1-2-1.8L4 10z" stroke="currentColor" stroke-width="1.6"/>
              <path d="M9 10V7a5 5 0 0 1 10 0v3" stroke="currentColor" stroke-width="1.6"/>
            </svg>
          </div>
          <h3 data-i18n="role2_h">Fulfill in-store, quietly.</h3>
          <p class="role-body" data-i18n="role2_p">Verified providers receive credit directly. Recipients shop normally, at the same till, with the same dignity as any other customer.</p>
          <div class="role-actions">
            <a href="{{ route('register.provider') }}"><span data-i18n="role2_cta">Join as a provider</span>
              <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
          </div>
        </article>

        <article class="role-card recipient">
          <span class="accent-dot"></span>
          <span class="step" data-i18n="step3">Step 03 · Recipient</span>
          <div class="icon">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
              <circle cx="14" cy="10" r="4" stroke="currentColor" stroke-width="1.6"/>
              <path d="M5 24c0-4.5 4-8 9-8s9 3.5 9 8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
          </div>
          <h3 data-i18n="role3_h">Shop with dignity.</h3>
          <p class="role-body" data-i18n="role3_p">Eligible households use a simple code at any partner provider. No labels. No lines. Just the week's shopping, covered.</p>
          <div class="role-actions">
            <a href="{{ route('register', ['type' => 'recipient']) }}"><span data-i18n="role3_cta">Request support</span>
              <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
          </div>
        </article>

      </div>
    </div>
  </div>
</section>

<!-- ========== PRIVACY ========== -->
<section class="privacy">
  <div class="wrap privacy-grid">
    <div>
      <span class="eyebrow" data-i18n="privacy_eyebrow">Chapter 03 · Privacy & dignity</span>
      <h2 class="display" style="margin-top: 22px;">
        <span data-i18n="privacy_h_1">What the supporter sees</span><br/>
        <em data-i18n="privacy_h_2">is never who the recipient is.</em>
      </h2>
      <p class="body" data-i18n="privacy_p">Every recipient record is split in two. Identifying details are held only for eligibility and are never shown to supporters. You see the impact, never the person.</p>
      <div class="privacy-pills">
        <span class="pill" data-i18n="pill1">Split-storage architecture</span>
        <span class="pill" data-i18n="pill2">Tokenized recipient IDs</span>
        <span class="pill" data-i18n="pill3">Zero supporter-side PII</span>
        <span class="pill" data-i18n="pill4">Third-party annual audit</span>
      </div>
    </div>

    <div class="card-pair reveal">
      <div class="id-card clear">
        <div class="title-line">
          <span data-i18n="id_held">Held securely</span>
          <span>01 · RAW</span>
        </div>
        <div class="id-row" style="margin-top: 6px;">
          <span class="k" data-i18n="id_name">Name</span>
          <span class="v redacted">Fatima Al-Hashimi</span>
        </div>
        <div class="id-row">
          <span class="k" data-i18n="id_area">Area</span>
          <span class="v redacted">Ras Beirut — Block 04</span>
        </div>
        <div class="id-row">
          <span class="k" data-i18n="id_family">Family</span>
          <span class="v redacted">4 adults, 2 children</span>
        </div>
        <div class="id-row">
          <span class="k" data-i18n="id_status">Status</span>
          <span class="v" style="color: #2AAE5F;" data-i18n="id_verified">Verified eligible</span>
        </div>
      </div>

      <div class="arrow-down">
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M7 2v10M3 8l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span data-i18n="tokenized">What supporters see — tokenized</span>
      </div>

      <div class="id-card protected">
        <div class="title-line">
          <span data-i18n="id_shown">Shown to supporters</span>
          <span>02 · TOKEN</span>
        </div>
        <div class="id-row" style="margin-top: 6px;">
          <span class="k" data-i18n="id_ref">Reference</span>
          <span class="v token">NBL-7F3A-QR</span>
        </div>
        <div class="id-row">
          <span class="k" data-i18n="id_region">Region</span>
          <span class="v" data-i18n="id_region_v">Beirut (metro)</span>
        </div>
        <div class="id-row">
          <span class="k" data-i18n="id_household">Household</span>
          <span class="v" data-i18n="id_household_v">Family of 6</span>
        </div>
        <div class="id-row">
          <span class="k" data-i18n="id_fulfillment">Fulfillment</span>
          <span class="v" data-i18n="id_fulfillment_v">Weekly staples basket</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ========== TRUST / TRANSPARENCY ========== -->
<section class="trust" id="trust">
  <div class="wrap trust-grid">
    <div class="trust-left">
      <span class="eyebrow" data-i18n="trust_eyebrow">Chapter 04 · Trust & transparency</span>
      <h2 class="display" style="margin-top: 22px;">
        <span data-i18n="trust_h_1">Every halala,</span> <i data-i18n="trust_h_2">accounted for.</i>
      </h2>
      <p data-i18n="trust_p">NUBL publishes a live ledger of every transaction across the network — without ever exposing who received support.</p>

      <div class="trust-badges" style="grid-template-columns: 1fr 1fr;">
        <div class="badge-card">
          <div class="n">97.9<span style="font-size:.6em;color:var(--muted);">%</span></div>
          <div class="t" data-i18n="badge1">of funds reach recipients directly</div>
        </div>
        <div class="badge-card">
          <div class="n">2.1<span style="font-size:.6em;color:var(--muted);">%</span></div>
          <div class="t" data-i18n="badge2">currently held in the system</div>
        </div>
      </div>
    </div>

    <div class="transparency-ledger" aria-label="Live ledger preview">
      <div class="ledger-head">
        <h4 data-i18n="ledger_h">Live ledger — last 24 hours</h4>
        <span class="date" id="ledgerDate">19 APR 2026</span>
      </div>
      <div class="ledger-row">
        <div>
          <div class="desc" data-i18n="ledger_1">Weekly staples basket · Riyadh</div>
          <div class="meta">08:42 · NBL-7F3A-QR · Tamimi Markets</div>
        </div>
        <div class="amt">158<span class="u" data-i18n="usd">SAR</span></div>
      </div>
      <div class="ledger-row">
        <div>
          <div class="desc" data-i18n="ledger_2">Bakery allowance · Jeddah</div>
          <div class="meta">09:17 · NBL-2K8H-LP · Al Rahmaniah Bakery</div>
        </div>
        <div class="amt">68<span class="u" data-i18n="usd2">SAR</span></div>
      </div>
      <div class="ledger-row">
        <div>
          <div class="desc" data-i18n="ledger_3">Family meal fund · Dammam</div>
          <div class="meta">10:03 · NBL-9T1B-MX · Najd Village</div>
        </div>
        <div class="amt">105<span class="u" data-i18n="usd3">SAR</span></div>
      </div>
      <div class="ledger-row">
        <div>
          <div class="desc" data-i18n="ledger_4">Weekly produce · Makkah</div>
          <div class="meta">11:22 · NBL-4E6D-ZS · Panda Retail</div>
        </div>
        <div class="amt">132<span class="u" data-i18n="usd4">SAR</span></div>
      </div>
      <div class="ledger-row">
        <div>
          <div class="desc" data-i18n="ledger_5">Weekly staples basket · Madinah</div>
          <div class="meta">12:51 · NBL-8C2A-WV · Nahdi Markets</div>
        </div>
        <div class="amt">172<span class="u" data-i18n="usd5">SAR</span></div>
      </div>

      <div class="ledger-foot">
        <span style="font-family: var(--mono); font-size: 11px; color: var(--muted);" data-i18n="ledger_count">Showing 5 of 1,284 today</span>
        <a href="#trust" data-i18n="ledger_full">View full transparency report →</a>
      </div>
    </div>
  </div>
</section>

<!-- ========== PROVIDERS ========== -->
<section class="providers" id="providers">
  <div class="wrap">
    <div class="providers-intro">
      <span class="eyebrow" data-i18n="providers_eyebrow">Chapter 05 · Local providers</span>
      <h2 class="display" style="margin-top: 22px;">
        <span data-i18n="providers_h_1">The shops already</span> <em data-i18n="providers_h_2">serving your city.</em>
      </h2>
      <p data-i18n="providers_p">NUBL doesn't build warehouses or fleets. We partner with trusted shops and restaurants already serving each community — so the money stays local and the shopping feels normal.</p>
    </div>

    <div class="provider-types">
      <div class="provider-cell">
        <div class="glyph">
          <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
            <rect x="5" y="10" width="26" height="20" stroke="currentColor" stroke-width="1.5"/>
            <path d="M5 14h26" stroke="currentColor" stroke-width="1.5"/>
            <path d="M13 10V6h10v4" stroke="currentColor" stroke-width="1.5"/>
            <circle cx="13" cy="22" r="1.5" fill="currentColor"/>
            <circle cx="23" cy="22" r="1.5" fill="currentColor"/>
          </svg>
        </div>
        <h4 data-i18n="prov1_h">Supermarkets</h4>
        <p data-i18n="prov1_p">Full weekly baskets, staple goods, fresh produce.</p>
        <div class="cnt"><b data-counter-inline="142">142</b><span data-i18n="prov1_cnt">partners</span></div>
      </div>

      <div class="provider-cell">
        <div class="glyph">
          <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
            <path d="M6 28h24M8 28V14c0-4 4-8 10-8s10 4 10 8v14" stroke="currentColor" stroke-width="1.5"/>
            <path d="M13 14h10" stroke="currentColor" stroke-width="1.5"/>
          </svg>
        </div>
        <h4 data-i18n="prov2_h">Grocers</h4>
        <p data-i18n="prov2_p">Neighborhood shops offering familiar brands and friendly faces.</p>
        <div class="cnt"><b data-counter-inline="118">118</b><span data-i18n="prov2_cnt">partners</span></div>
      </div>

      <div class="provider-cell">
        <div class="glyph">
          <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
            <path d="M6 22c0-6 5-10 12-10s12 4 12 10H6z" stroke="currentColor" stroke-width="1.5"/>
            <path d="M4 26h28" stroke="currentColor" stroke-width="1.5"/>
            <path d="M14 8v4M18 6v6M22 8v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
        </div>
        <h4 data-i18n="prov3_h">Bakeries</h4>
        <p data-i18n="prov3_p">Daily bread, pastries, and the rituals that make a home feel whole.</p>
        <div class="cnt"><b data-counter-inline="54">54</b><span data-i18n="prov3_cnt">partners</span></div>
      </div>

      <div class="provider-cell">
        <div class="glyph">
          <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
            <path d="M10 6v12a4 4 0 0 0 4 4v8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            <path d="M10 6v12M14 6v12M18 6v12a4 4 0 0 1-4 4" stroke="currentColor" stroke-width="1.5"/>
            <path d="M24 6c-2 0-3 4-3 8s1 5 3 5v11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
        </div>
        <h4 data-i18n="prov4_h">Restaurants</h4>
        <p data-i18n="prov4_p">Cooked meals for families without a kitchen, or for the days a warm plate matters most.</p>
        <div class="cnt"><b data-counter-inline="33">33</b><span data-i18n="prov4_cnt">partners</span></div>
      </div>
    </div>

    <div class="provider-strip">
      <div class="big display">
        <span data-i18n="strip_1">Own a shop or restaurant?</span> <em data-i18n="strip_2">Join the NUBL provider network.</em>
      </div>
      <a href="{{ route('register.provider') }}" class="cta" style="background: var(--navy-dk); color: var(--off-white);">
        <span data-i18n="strip_cta">Apply as a provider</span>
        <svg viewBox="0 0 16 16" width="14" height="14" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
    </div>
  </div>
</section>

<!-- ========== STORIES ========== -->
<section class="stories" id="stories">
  <div class="wrap">
    <div class="stories-head">
      <div>
        <span class="eyebrow" data-i18n="stories_eyebrow">Chapter 06 · From the network</span>
        <h2 class="display" style="margin-top: 20px;">
          <span data-i18n="stories_h_1">Recipient, donor,</span> <i data-i18n="stories_h_2">and provider voices.</i>
        </h2>
      </div>
      <span style="font-family: var(--mono); font-size: 11px; color: var(--muted); letter-spacing: 0; text-transform: uppercase;" data-i18n="stories_consent">All stories shared with consent. Identifying details redacted.</span>
    </div>

    <div class="stories-grid">
      <article class="story feature">
        <p class="quote" data-i18n="story1">What relieved me the most was that I did not have to explain my situation to anyone or ask directly. The support reached me with dignity, and I used it when I truly needed to buy basic household essentials. Knowing that someone is standing with you without even knowing who you are… it means a lot.</p>
        <div class="story-meta">
          <span class="role" data-i18n="story1_role">Recipient / Beneficiary Voice</span>
          <span data-i18n="story1_loc">Beneficiary — Madinah</span>
        </div>
      </article>

      <article class="story">
        <p class="quote" data-i18n="story2">I always used to wonder: did my donation really reach someone who needed it? With NUBL, things felt clearer. I do not know the people, to protect their privacy, but I can see the impact in a reassuring way. That made me donate with peace of mind.</p>
        <div class="story-meta">
          <span class="role" data-i18n="story2_role">Donor Voice</span>
          <span data-i18n="story2_loc">Donor — Riyadh</span>
        </div>
      </article>

      <article class="story feature-2">
        <p class="quote" data-i18n="story3">Sometimes someone comes in and you can tell they are in need, but they do not want anyone to notice. With NUBL, the process became respectful and simple; they receive what they need without embarrassment, and we serve them just like any other customer.</p>
        <div class="story-meta">
          <span class="role" data-i18n="story3_role">Provider Voice</span>
          <span data-i18n="story3_loc">Food Provider — Jeddah</span>
        </div>
      </article>
    </div>
  </div>
</section>

<!-- ========== CTA BAND ========== -->
<section class="cta-band" id="cta">
  <div class="wrap">
    <span class="eyebrow" data-i18n="cta_eyebrow">Chapter 07 · Your part</span>
    <h2 class="display">
      <span data-i18n="cta_h_1">Three ways in.</span><br/>
      <em data-i18n="cta_h_2">One quiet circuit of care.</em>
    </h2>

    <div class="cta-triad">
      <a href="{{ route('register', ['type' => 'donor']) }}" class="cta-card donate">
        <span class="num">01</span>
        <h3 data-i18n="ctaA_h">Give support</h3>
        <p data-i18n="ctaA_p">One-time, monthly, or directed. Every contribution is traceable to impact, invisible to the person you help.</p>
        <span class="go"><span data-i18n="ctaA_cta">Become a supporter</span>
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
      </a>

      <a href="{{ route('register', ['type' => 'recipient']) }}" class="cta-card request">
        <span class="num">02</span>
        <h3 data-i18n="ctaB_h">Request support</h3>
        <p data-i18n="ctaB_p">A short, confidential form. Reviewed within 7 days. No one in your community sees you asking.</p>
        <span class="go"><span data-i18n="ctaB_cta">Apply privately</span>
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
      </a>

      <a href="{{ route('register.provider') }}" class="cta-card join">
        <span class="num">03</span>
        <h3 data-i18n="ctaC_h">Join as a provider</h3>
        <p data-i18n="ctaC_p">Shops and restaurants — keep serving your community, get paid directly by the platform.</p>
        <span class="go"><span data-i18n="ctaC_cta">Apply to partner</span>
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
      </a>
    </div>
  </div>
</section>

</main>

<!-- ========== FOOTER ========== -->
<footer>
  <div class="wrap">
    <div class="rows">
      <div class="brand-col">
        <a href="{{ url('/') }}" class="logo" aria-label="{{ config('app.name', 'NUBL') }}">
          <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name', 'NUBL') }}" class="brand-logo" />
        </a>
        <p data-i18n="foot_tag">A digital platform for dignified food support, connecting supporters, recipients and local providers.</p>
      </div>
      <div>
        <h5 data-i18n="foot_platform">Platform</h5>
        <a href="#how" data-i18n="foot_how">How it works</a>
        <a href="#trust" data-i18n="foot_transparency">Transparency</a>
        <a href="#providers" data-i18n="foot_coverage">Coverage areas</a>
      </div>
      <div>
        <h5 data-i18n="foot_join">Join</h5>
        <a href="{{ route('register', ['type' => 'donor']) }}" data-i18n="foot_support">Support a family</a>
        <a href="{{ route('register', ['type' => 'recipient']) }}" data-i18n="foot_request">Request support</a>
        <a href="{{ route('register.provider') }}" data-i18n="foot_partner">Become a partner</a>
      </div>
      <div>
        <h5 data-i18n="foot_org">Organization</h5>
        <a href="#idea" data-i18n="foot_about">About</a>
        <a href="#stories" data-i18n="foot_press">Press</a>
        <a href="#cta" data-i18n="foot_contact">Contact</a>
      </div>
    </div>
    <div class="bottom">
      <span data-i18n="foot_copy">© 2026 NUBL Platform · All rights reserved</span>
      <span data-i18n="foot_legal">Privacy · Terms · Audited annually by Baker Tilly MENA</span>
    </div>
  </div>
</footer>

<!-- ========== TWEAKS ========== -->
<div id="tweaks-root">
  <div class="tweaks-window">
    <div class="tweaks-head">
      <span>Tweaks</span>
      <span id="tweakStatus">·</span>
    </div>
    <div class="tweaks-body">
      <div class="tweak-row">
        <label>Language</label>
        <div class="opts" data-tweak="lang">
          <button data-val="en" class="active">English</button>
          <button data-val="ar">العربية</button>
        </div>
      </div>
      <div class="tweak-row">
        <label>Primary accent</label>
        <div class="opts" data-tweak="accent">
          <button data-val="gold" class="active">Gold</button>
          <button data-val="rose">Rose</button>
          <button data-val="navy">Navy</button>
        </div>
      </div>
      <div class="tweak-row">
        <label>Density</label>
        <div class="opts" data-tweak="density">
          <button data-val="compact">Compact</button>
          <button data-val="default" class="active">Balanced</button>
          <button data-val="airy">Airy</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // -------- Tweak defaults (editmode block) --------
  const TWEAK_DEFAULTS = /*EDITMODE-BEGIN*/{
    "lang": "{{ app()->getLocale() === 'ar' ? 'ar' : 'en' }}",
    "accent": "gold",
    "density": "default"
  }/*EDITMODE-END*/;

  let TWEAKS = { ...TWEAK_DEFAULTS };
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // -------- i18n strings --------
  const I18N = {
    en: {
      brand: "NUBL",
      nav_idea: "The idea", nav_how: "How it works", nav_trust: "Trust", nav_providers: "Providers", nav_give: "Give support",
      hero_eyebrow: "A platform for dignified food support",
      hero_h1_1: "Nourishment,", hero_h1_2: "delivered quietly", hero_h1_3: "through people who already care.",
      hero_sub: 'NUBL connects supporters with eligible recipients through <strong>trusted local providers</strong> — the supermarkets, grocers, bakeries and restaurants already rooted in your neighborhood. No packages on doorsteps. No identities exposed. Just a quiet line of care.',
      hero_cta1: "Support a family", hero_cta2: "Request support",
      impact_live: "Impact · updated live", impact_q: "Q2 · 2026",
      meals_unit: "SAR", meals_label: "delivered this quarter across the network",
      amount_unit: "SAR", amount_label: "delivered this quarter across the network",
      families_tag: "families supported", providers_tag: "local providers",
      live: "Live ledger", anonymized: "anonymized · verified",
      chapter1: "Chapter 01", chapter1_label: "The idea",
      manifesto_eyebrow: "Why we built NUBL",
      manifesto_h_1: "Hunger is private.", manifesto_h_2: "So is dignity.", manifesto_h_3: "Support should honor both.",
      manifesto_p: "Asking for help shouldn't cost you your privacy. NUBL routes everyday grocery credit to eligible households through the shops they already use — quietly, documented, and accountable to everyone.",
      chapter2: "Chapter 02 · How it works",
      how_h_1: "Three people.", how_h_2: "One quiet circuit.",
      how_p: "Every transaction on NUBL travels through a structured path. Supporters contribute, providers fulfill, recipients receive — each role protected, each step documented.",
      step1: "Step 01 · Supporter", step2: "Step 02 · Provider", step3: "Step 03 · Recipient",
      role1_h: "Choose how to give.",
      role1_p: "One-time, monthly, or directed to a category. Every contribution shows up in your impact summary.",
      role1_cta: "Become a supporter",
      role2_h: "Fulfill in-store, quietly.",
      role2_p: "Verified providers receive credit directly. Recipients shop normally, at the same till, with the same dignity as any other customer.",
      role2_cta: "Join as a provider",
      role3_h: "Shop with dignity.",
      role3_p: "Eligible households use a simple code at any partner provider. No labels. No lines. Just the week's shopping, covered.",
      role3_cta: "Request support",
      privacy_eyebrow: "Chapter 03 · Privacy & dignity",
      privacy_h_1: "What the supporter sees", privacy_h_2: "is never who the recipient is.",
      privacy_p: "Every recipient record is split in two. Identifying details are held only for eligibility and are never shown to supporters. You see the impact, never the person.",
      pill1: "Split-storage architecture", pill2: "Tokenized recipient IDs", pill3: "Zero supporter-side PII", pill4: "Third-party annual audit",
      id_held: "Held securely", id_name: "Name", id_area: "Area", id_family: "Family", id_status: "Status", id_verified: "Verified eligible",
      tokenized: "What supporters see — tokenized",
      id_shown: "Shown to supporters", id_ref: "Reference", id_region: "Region", id_region_v: "Beirut (metro)",
      id_household: "Household", id_household_v: "Family of 6", id_fulfillment: "Fulfillment", id_fulfillment_v: "Weekly staples basket",
      trust_eyebrow: "Chapter 04 · Trust & transparency",
      trust_h_1: "Every halala,", trust_h_2: "accounted for.",
      trust_p: "NUBL publishes a live ledger of every transaction across the network — without ever exposing who received support.",
      badge1: "of funds reach recipients directly", badge2: "platform & verification costs", badge3: "marketing overhead",
      ledger_h: "Live ledger — last 24 hours",
      ledger_1: "Weekly staples basket · Riyadh",
      ledger_2: "Bakery allowance · Jeddah",
      ledger_3: "Family meal fund · Dammam",
      ledger_4: "Weekly produce · Makkah",
      ledger_5: "Weekly staples basket · Madinah",
      usd: "SAR", usd2: "SAR", usd3: "SAR", usd4: "SAR", usd5: "SAR",
      ledger_count: "Showing 5 of 1,284 today", ledger_full: "View full transparency report →",
      providers_eyebrow: "Chapter 05 · Local providers",
      providers_h_1: "The shops already", providers_h_2: "serving your city.",
      providers_p: "NUBL doesn't build warehouses or fleets. We partner with trusted shops and restaurants already serving each community — so the money stays local and the shopping feels normal.",
      prov1_h: "Supermarkets", prov1_p: "Full weekly baskets, staple goods, fresh produce.", prov1_cnt: "partners",
      prov2_h: "Grocers", prov2_p: "Neighborhood shops offering familiar brands and friendly faces.", prov2_cnt: "partners",
      prov3_h: "Bakeries", prov3_p: "Daily bread, pastries, and the rituals that make a home feel whole.", prov3_cnt: "partners",
      prov4_h: "Restaurants", prov4_p: "Cooked meals for families without a kitchen, or for the days a warm plate matters most.", prov4_cnt: "partners",
      strip_1: "Own a shop or restaurant?", strip_2: "Join the NUBL provider network.", strip_cta: "Apply as a provider",
      stories_eyebrow: "Chapter 06 · From the network",
      stories_h_1: "Recipient, donor,", stories_h_2: "and provider voices.",
      stories_consent: "All stories shared with consent. Identifying details redacted.",
      story1: "What relieved me the most was that I did not have to explain my situation to anyone or ask directly. The support reached me with dignity, and I used it when I truly needed to buy basic household essentials. Knowing that someone is standing with you without even knowing who you are… it means a lot.",
      story1_role: "Recipient / Beneficiary Voice", story1_loc: "Beneficiary — Madinah",
      story2: "I always used to wonder: did my donation really reach someone who needed it? With NUBL, things felt clearer. I do not know the people, to protect their privacy, but I can see the impact in a reassuring way. That made me donate with peace of mind.",
      story2_role: "Donor Voice", story2_loc: "Donor — Riyadh",
      story3: "Sometimes someone comes in and you can tell they are in need, but they do not want anyone to notice. With NUBL, the process became respectful and simple; they receive what they need without embarrassment, and we serve them just like any other customer.",
      story3_role: "Provider Voice", story3_loc: "Food Provider — Jeddah",
      cta_eyebrow: "Chapter 07 · Your part",
      cta_h_1: "Three ways in.", cta_h_2: "One quiet circuit of care.",
      ctaA_h: "Give support", ctaA_p: "One-time, monthly, or directed. Every contribution is traceable to impact, invisible to the person you help.", ctaA_cta: "Become a supporter",
      ctaB_h: "Request support", ctaB_p: "A short, confidential form. Reviewed within 7 days. No one in your community sees you asking.", ctaB_cta: "Apply privately",
      ctaC_h: "Join as a provider", ctaC_p: "Shops and restaurants — keep serving your community, get paid directly by the platform.", ctaC_cta: "Apply to partner",
      foot_tag: "A digital platform for dignified food support, connecting supporters, recipients and local providers.",
      foot_platform: "Platform", foot_how: "How it works", foot_transparency: "Transparency", foot_coverage: "Coverage areas",
      foot_join: "Join", foot_support: "Support a family", foot_request: "Request support", foot_partner: "Become a partner",
      foot_org: "Organization", foot_about: "About", foot_press: "Press", foot_contact: "Contact",
      foot_copy: "© 2026 NUBL Platform · All rights reserved",
      foot_legal: "Privacy · Terms · Audited annually by Baker Tilly MENA",
      feed: [
        { row1: "A family of 5 received a weekly staples basket in Riyadh.", row2: "NBL-A2F9-KR · Tamimi Markets · 2 min ago" },
        { row1: "A household received the week's bread allowance in Jeddah.", row2: "NBL-7T3L-WQ · Al Rahmaniah Bakery · 4 min ago" },
        { row1: "A household of 6 picked up fresh produce in Dammam.", row2: "NBL-D8H2-MX · Panda Retail · 6 min ago" },
        { row1: "A family received a warm Friday meal in Makkah.", row2: "NBL-3K1S-BN · Najd Village · 8 min ago" },
        { row1: "A household received the month's staples in Madinah.", row2: "NBL-9E4C-TV · Nahdi Markets · 11 min ago" }
      ]
    },
    ar: {
      brand: "نبل",
      nav_idea: "الفكرة", nav_how: "كيف تعمل", nav_trust: "الشفافية", nav_providers: "المزودون", nav_give: "قدم الدعم",
      hero_eyebrow: "منصة للدعم ",
      hero_h1_1: "الدعم", hero_h1_2: "يصل بكرامة", hero_h1_3: "عبر من يهتمون بك",
      hero_sub: 'توصل <strong>نبل</strong> الدعم الغذائي عبر <strong>مزودين محليين موثوقين</strong> في مدينتك — عندما يكون العون بكرامة، لا بشعار.',
      hero_cta1: "ادعم عائلة", hero_cta2: "اطلب الدعم",
      impact_live: "الأثر · يحدث مباشرة", impact_q: "الربع الثاني · 2026",
      meals_unit: "ريال", meals_label: "قدمت هذا الربع عبر الشبكة",
      amount_unit: "ريال", amount_label: "وصلت هذا الربع عبر الشبكة",
      families_tag: "عائلة مدعومة", providers_tag: "مزود محلي",
      live: "السجل المباشر", anonymized: "مجهول الهوية · موثق",
      chapter1: "الفصل الأول", chapter1_label: "الفكرة",
      manifesto_eyebrow: "لماذا أنشأنا نبل",
      manifesto_h_1: "الحاجة شأن خاص.", manifesto_h_2: "والكرامة كذلك.", manifesto_h_3: "فليكن الدعم محترما للاثنين.",
      manifesto_p: "طلب المساعدة لا ينبغي أن يكلفك خصوصيتك. توصل نبل رصيد التسوق اليومي إلى الأسر المؤهلة عبر المحلات التي يرتادونها أصلا — بهدوء، وموثق، ومحاسب عليه أمام الجميع.",
      chapter2: "الفصل الثاني · كيف تعمل",
      how_h_1: "ثلاثة أشخاص.", how_h_2: "دائرة واحدة هادئة.",
      how_p: "كل عملية في نبل تسير عبر مسار منظم. الداعم يسهم، والمزود ينفذ، والمستفيد يتلقى — كل دور محمي، وكل خطوة موثقة.",
      step1: "الخطوة 01 · الداعم", step2: "الخطوة 02 · المزود", step3: "الخطوة 03 · المستفيد",
      role1_h: "اختر طريقتك في العطاء.",
      role1_p: "مرة واحدة، أو شهريا، أو موجها لفئة. كل مساهمة تظهر في ملخص أثرك.",
      role1_cta: "كن داعما",
      role2_h: "نفذ بهدوء داخل المحل.",
      role2_p: "يتلقى المزودون المعتمدون الرصيد مباشرة. يتسوق المستفيدون كأي زبون، عند نفس الصندوق، بنفس الكرامة.",
      role2_cta: "انضم كمزود",
      role3_h: "تسوق بكرامة.",
      role3_p: "الأسر المؤهلة تستخدم رمزا بسيطا عند أي مزود شريك. لا ملصقات. لا طوابير. فقط تسوق الأسبوع، مغطى.",
      role3_cta: "اطلب الدعم",
      privacy_eyebrow: "الفصل الثالث · الخصوصية والكرامة",
      privacy_h_1: "ما يراه الداعم", privacy_h_2: "ليس هوية المستفيد.",
      privacy_p: "كل ملف مستفيد مقسوم إلى شطرين. البيانات الشخصية محفوظة فقط للتحقق من الأهلية، ولا تظهر للداعمين أبدا. ترى الأثر، لا الشخص.",
      pill1: "تخزين مجزأ", pill2: "معرفات مستفيدين مرمزة", pill3: "لا بيانات شخصية للداعمين", pill4: "تدقيق سنوي خارجي",
      id_held: "محفوظ بأمان", id_name: "الاسم", id_area: "المنطقة", id_family: "العائلة", id_status: "الحالة", id_verified: "مؤهل مؤكد",
      tokenized: "ما يراه الداعمون — مرمز",
      id_shown: "يعرض للداعمين", id_ref: "المرجع", id_region: "المدينة", id_region_v: "الرياض",
      id_household: "حجم الأسرة", id_household_v: "عائلة من 6", id_fulfillment: "نوع الدعم", id_fulfillment_v: "سلة مؤن أسبوعية",
      trust_eyebrow: "الفصل الرابع · الثقة والشفافية",
      trust_h_1: "كل هللة،", trust_h_2: "محسوبة ومعلنة.",
      trust_p: "تنشر نبل سجلا حيا لكل عملية في الشبكة — دون الكشف أبدا عن هوية من تلقى الدعم.",
      badge1: "من التبرعات تصل للمستفيدين مباشرة", badge2: "موجودة حاليا في النظام",
      ledger_h: "السجل المباشر — آخر 24 ساعة",
      ledger_1: "سلة مؤن أسبوعية · الرياض",
      ledger_2: "مخصص مخبز · جدة",
      ledger_3: "وجبة عائلية · الدمام",
      ledger_4: "خضار أسبوعية · مكة",
      ledger_5: "سلة مؤن أسبوعية · المدينة",
      usd: "ريال", usd2: "ريال", usd3: "ريال", usd4: "ريال", usd5: "ريال",
      ledger_count: "5 من 1٬284 اليوم", ledger_full: "عرض تقرير الشفافية الكامل ←",
      providers_eyebrow: "الفصل الخامس · المزودون المحليون",
      providers_h_1: "المحلات التي تخدم", providers_h_2: "مدينتك من قبل.",
      providers_p: "لا تبني نبل مستودعات ولا أساطيل نقل. نتشارك مع المحلات والمطاعم الموثوقة التي تخدم كل مجتمع أصلا — فيبقى المال محليا والتسوق طبيعيا.",
      prov1_h: "السوبرماركت", prov1_p: "سلال مؤن أسبوعية، ومواد أساسية، وخضار طازجة.", prov1_cnt: "شريك",
      prov2_h: "البقالات", prov2_p: "محلات المجتمع بعلاماتها المألوفة ووجوهها الأليفة.", prov2_cnt: "شريك",
      prov3_h: "المخابز", prov3_p: "خبز كل يوم، ومعجنات، وطقوس تشعرك أن البيت بيت.", prov3_cnt: "شريك",
      prov4_h: "المطاعم", prov4_p: "وجبات مطبوخة للأسر، أو لأيام يحتاج فيها الصحن الدافئ أكثر.", prov4_cnt: "شريك",
      strip_1: "تملك محلا أو مطعما؟", strip_2: "انضم إلى شبكة مزودي نبل.", strip_cta: "تقدم كمزود",
      stories_eyebrow: "الفصل السادس · من داخل الشبكة",
      stories_h_1: "أصوات المستفيدين،", stories_h_2: "الداعمين والمزودين.",
      stories_consent: "كل القصص مشاركة بموافقة مكتوبة. التفاصيل التعريفية مخفاة.",
      story1: "أكثر شيء ريّحني أني ما اضطرّيت أشرح ظروفي لأحد أو أطلب بشكل مباشر. وصلني الدعم بكرامة، واستخدمته وقت ما كنت فعلًا محتاج أشتري أساسيات البيت. الشعور إن فيه أحد واقف معك بدون ما يعرفك… يفرق كثير.",
      story1_role: "صوت المحتاج / المستفيد", story1_loc: "مستفيد — المدينة المنورة",
      story2: "كنت دائمًا أتردد: هل تبرعي وصل فعلًا لمن يحتاج؟ في نوبل حسّيت أن الموضوع أوضح. ما أعرف الأشخاص حفاظًا على خصوصيتهم، لكن أشوف الأثر بشكل مطمئن. هذا خلاني أتبرع وأنا مرتاح.",
      story2_role: "صوت الداعم / المتبرع", story2_loc: "داعم — الرياض",
      story3: "أحيانًا يجيك شخص ويبان عليه أنه محتاج، لكن ما يبي أحد يحس فيه. مع نوبل صارت العملية محترمة وسهلة؛ يستلم احتياجه بدون إحراج، ونحن نخدمه مثل أي عميل ثاني.",
      story3_role: "صوت المزود / المتجر", story3_loc: "مزود غذائي — جدة",
      cta_eyebrow: "الفصل السابع · دورك",
      cta_h_1: "ثلاثة أبواب.", cta_h_2: "دائرة هادئة واحدة من العناية.",
      ctaA_h: "قدم الدعم", ctaA_p: "مرة، أو شهريا، أو موجها. كل مساهمة يمكن تتبع أثرها، دون أن يراك من تساعده.", ctaA_cta: "كن داعما",
      ctaB_h: "اطلب الدعم", ctaB_p: "استمارة قصيرة وسرية. تراجع خلال 7 أيام. لن يراك أحد في مجتمعك وأنت تطلب.", ctaB_cta: "قدم طلبا خاصا",
      ctaC_h: "انضم كمزود", ctaC_p: "المحلات والمطاعم — أكمل خدمة مجتمعك واستلم مستحقاتك مباشرة من المنصة.", ctaC_cta: "تقدم للشراكة",
      foot_tag: "منصة رقمية للدعم الغذائي بكرامة، تربط الداعمين بالمستفيدين والمزودين المحليين.",
      foot_platform: "المنصة", foot_how: "كيف تعمل", foot_transparency: "الشفافية", foot_coverage: "مناطق التغطية",
      foot_join: "انضم", foot_support: "ادعم عائلة", foot_request: "اطلب الدعم", foot_partner: "كن شريكا",
      foot_org: "المؤسسة", foot_about: "عن نبل", foot_press: "الصحافة", foot_contact: "تواصل",
      foot_copy: "© 2026 منصة نبل · جميع الحقوق محفوظة",
      foot_legal: "الخصوصية · الشروط · تدقيق سنوي خارجي",
      feed: [
        { row1: "عائلة من 5 أفراد تلقت سلة مؤن أسبوعية في الرياض.", row2: "NBL-A2F9-KR · أسواق التميمي · قبل دقيقتين" },
        { row1: "أسرة تلقت مخصص الخبز لهذا الأسبوع في جدة.", row2: "NBL-7T3L-WQ · مخبز الرحمانية · قبل 4 دقائق" },
        { row1: "أسرة من 6 أفراد استلمت خضارا طازجة في الدمام.", row2: "NBL-D8H2-MX · بنده · قبل 6 دقائق" },
        { row1: "عائلة تلقت وجبة جمعة دافئة في مكة.", row2: "NBL-3K1S-BN · قرية نجد · قبل 8 دقائق" },
        { row1: "أسرة تلقت مؤن الشهر في المدينة.", row2: "NBL-9E4C-TV · أسواق النهدي · قبل 11 دقيقة" }
      ]
    }
  };

  // -------- Apply language --------
  function applyLang(lang) {
    const html = document.documentElement;
    html.lang = lang;
    html.dir = lang === 'ar' ? 'rtl' : 'ltr';
    const dict = I18N[lang];
    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key = el.dataset.i18n;
      if (dict[key] != null) {
        if (dict[key].includes('<')) el.innerHTML = dict[key];
        else el.textContent = dict[key];
      }
    });
    // feed
    renderFeed();
    // lang toggle label shows the OTHER language
    const lbl = document.getElementById('langLabel');
    if (lbl) lbl.textContent = lang === 'en' ? 'العربية' : 'English';
    if (langToggle) {
      langToggle.setAttribute('aria-label', lang === 'en' ? 'Switch to Arabic' : 'التبديل إلى الإنجليزية');
    }
    updateLedgerDate(lang);
  }

  // -------- Apply accent --------
  function applyAccent(name) {
    const map = {
      gold:  { a: '#F0AA1F', adk: '#C98C0A', alt: '#FBE8AD', abg: '#FEF9EC' },
      rose:  { a: '#E0305E', adk: '#B8244C', alt: '#F7B8CB', abg: '#FEF0F4' },
      navy:  { a: '#293765', adk: '#1C2747', alt: '#A8B3D0', abg: '#F0F3FA' }
    };
    const c = map[name] || map.gold;
    const r = document.documentElement.style;
    r.setProperty('--accent', c.a);
    r.setProperty('--accent-dk', c.adk);
    r.setProperty('--accent-lt', c.alt);
    r.setProperty('--accent-bg', c.abg);
  }

  // -------- Apply density --------
  function applyDensity(d) {
    document.documentElement.setAttribute('data-density', d);
  }

  // -------- Live feed cycling --------
  let feedIdx = 0;
  let feedTimer = null;
  function renderFeed() {
    const list = document.getElementById('feedList');
    const items = (I18N[TWEAKS.lang] || I18N.en).feed;
    list.innerHTML = '';
    items.forEach((it, i) => {
      const d = document.createElement('div');
      d.className = 'feed-item' + (i === 0 ? ' active' : '');
      d.innerHTML = `<div class="row1">${it.row1}</div><div class="row2">${it.row2}</div>`;
      list.appendChild(d);
    });
    feedIdx = 0;
    if (feedTimer) clearInterval(feedTimer);
    if (reduceMotion) return;
    feedTimer = setInterval(() => {
      const all = list.querySelectorAll('.feed-item');
      if (!all.length) return;
      all[feedIdx].classList.remove('active');
      feedIdx = (feedIdx + 1) % all.length;
      all[feedIdx].classList.add('active');
    }, 3500);
  }

  // -------- Counter animation --------
  function animateCounter(el, target) {
    const duration = 1800;
    const start = performance.now();
    // find the text node (last child) to animate; keeps leading <span class="unit"> intact
    const textNode = el.childNodes[el.childNodes.length - 1];
    if (reduceMotion) {
      textNode.nodeValue = target.toLocaleString(TWEAKS.lang === 'ar' ? 'ar-EG' : 'en-US');
      return;
    }
    function tick(now) {
      const t = Math.min(1, (now - start) / duration);
      const eased = 1 - Math.pow(1 - t, 3);
      const v = Math.floor(target * eased);
      textNode.nodeValue = v.toLocaleString(TWEAKS.lang === 'ar' ? 'ar-EG' : 'en-US');
      if (t < 1) requestAnimationFrame(tick);
      else textNode.nodeValue = target.toLocaleString(TWEAKS.lang === 'ar' ? 'ar-EG' : 'en-US');
    }
    requestAnimationFrame(tick);
  }

  // -------- Intersection reveal + counters --------
  const io = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('on');
        if (e.target.dataset.counter) {
          animateCounter(e.target, parseInt(e.target.dataset.counter, 10));
        }
        io.unobserve(e.target);
      }
    });
  }, { threshold: 0.15 });

  document.querySelectorAll('.reveal, [data-counter]').forEach(el => io.observe(el));

  // Ensure anything already in the viewport on load reveals immediately (IO fires asynchronously)
  requestAnimationFrame(() => {
    const vh = window.innerHeight;
    document.querySelectorAll('.reveal, [data-counter]').forEach(el => {
      const r = el.getBoundingClientRect();
      if (r.top < vh && r.bottom > 0) {
        el.classList.add('on');
        if (el.dataset.counter) {
          animateCounter(el, parseInt(el.dataset.counter, 10));
        }
        io.unobserve(el);
      }
    });
  });

  // -------- Nav scrolled state --------
  const nav = document.getElementById('nav');
  window.addEventListener('scroll', () => {
    nav.classList.toggle('scrolled', window.scrollY > 8);
  });

  // -------- Language toggle button in nav --------
  const langToggle = document.getElementById('langToggle');
  if (langToggle) {
    langToggle.addEventListener('click', () => {
      const next = TWEAKS.lang === 'en' ? 'ar' : 'en';
      const target = next === 'ar' ? langToggle.dataset.arUrl : langToggle.dataset.enUrl;
      if (target) {
        window.location.href = target;
        return;
      }
      TWEAKS.lang = next;
      applyLang(next);
      updateTweakUI();
      persist({ lang: next });
    });
  }

  // -------- Tweaks plumbing --------
  const tweaksRoot = document.getElementById('tweaks-root');
  function updateTweakUI() {
    document.querySelectorAll('.tweak-row .opts').forEach(opts => {
      const key = opts.dataset.tweak;
      opts.querySelectorAll('button').forEach(b => {
        b.classList.toggle('active', b.dataset.val === TWEAKS[key]);
      });
    });
  }

  document.querySelectorAll('.tweak-row .opts button').forEach(btn => {
    btn.addEventListener('click', () => {
      const key = btn.parentElement.dataset.tweak;
      const val = btn.dataset.val;
      TWEAKS[key] = val;
      if (key === 'lang') applyLang(val);
      if (key === 'accent') applyAccent(val);
      if (key === 'density') applyDensity(val);
      updateTweakUI();
      persist({ [key]: val });
    });
  });

  function persist(edits) {
    try {
      window.parent.postMessage({ type: '__edit_mode_set_keys', edits }, '*');
    } catch (e) {}
  }

  // Tweaks host protocol
  window.addEventListener('message', (e) => {
    const d = e.data || {};
    if (d.type === '__activate_edit_mode') tweaksRoot.classList.add('on');
    if (d.type === '__deactivate_edit_mode') tweaksRoot.classList.remove('on');
  });
  window.parent.postMessage({ type: '__edit_mode_available' }, '*');

  function updateLedgerDate(lang = TWEAKS.lang) {
    const el = document.getElementById('ledgerDate');
    if (!el) return;
    const locale = lang === 'ar' ? 'ar-SA-u-ca-gregory' : 'en-GB';
    const formatted = new Intl.DateTimeFormat(locale, {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    }).format(new Date());
    el.textContent = lang === 'ar' ? formatted : formatted.toUpperCase();
  }

  // -------- Init --------
  // Use Laravel locale first, then fall back to the browser.
  const browserLang = (navigator.language || '').toLowerCase().startsWith('ar') ? 'ar' : 'en';
  TWEAKS.lang = TWEAK_DEFAULTS.lang || browserLang;
  applyLang(TWEAKS.lang);
  applyAccent(TWEAKS.accent);
  applyDensity(TWEAKS.density);
  updateTweakUI();
</script>
</body>
</html>
