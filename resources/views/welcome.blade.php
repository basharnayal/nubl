<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
<meta name="description" content="NUBL connects donors, recipients, and local food providers through dignified, private food support." />
<meta name="theme-color" content="#F5F4F1" />
<title>{{ config('app.name', 'NUBL') }} — Food support with dignity</title>

@vite(['resources/css/fonts-landing.css'])

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

    /* ── Fluid type scale (clamp: min | fluid | max) ── */
    --text-xs:   clamp(0.75rem,  1.5vw,  0.875rem);
    --text-sm:   clamp(0.875rem, 2vw,    1rem);
    --text-base: clamp(1rem,     2.5vw,  1.125rem);
    --text-lg:   clamp(1.125rem, 3vw,    1.25rem);
    --text-xl:   clamp(1.25rem,  3.5vw,  1.5rem);
    --text-2xl:  clamp(1.5rem,   4vw,    2rem);
    --text-3xl:  clamp(2rem,     5vw,    3rem);
    --text-4xl:  clamp(2.5rem,   6vw,    4rem);

    /* ── Spacing scale (8 px base) ── */
    --sp-1:  0.5rem;   /* 8px  */
    --sp-2:  1rem;     /* 16px */
    --sp-3:  1.5rem;   /* 24px */
    --sp-4:  2rem;     /* 32px */
    --sp-6:  3rem;     /* 48px */
    --sp-8:  4rem;     /* 64px */
    --sp-10: 5rem;     /* 80px */
    --sp-12: 6rem;     /* 96px */
    --sp-16: 8rem;     /* 128px */

    /* ── Elevation shadows ── */
    --shadow-sm: 0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.06);
    --shadow-md: 0 4px 12px rgba(0,0,0,.10), 0 2px 4px rgba(0,0,0,.06);
    --shadow-lg: 0 10px 30px rgba(0,0,0,.12), 0 4px 8px rgba(0,0,0,.06);
    --shadow-xl: 0 20px 60px rgba(0,0,0,.15);

    /* ── Transitions ── */
    --t-fast: 0.15s ease;
    --t-base: 0.25s ease;
    --t-slow: 0.4s ease;

    /* ── Border radius ── */
    --r-sm:   6px;
    --r-md:   12px;
    --r-lg:   20px;
    --r-xl:   32px;
    --r-full: 9999px;
  }

  [lang="ar"] {
    --display: 'IBM Plex Sans Arabic', system-ui, sans-serif;
    --sans: 'IBM Plex Sans Arabic', system-ui, sans-serif;
  }
  [lang="ar"] .display { font-weight: 500; }
  [lang="ar"] h1.display, [lang="ar"] h2.display, [lang="ar"] h3 { font-weight: 600; }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  html { scroll-behavior: smooth; }

  /* ── Utility: tabular numbers for financial data ── */
  .tabular-nums { font-variant-numeric: tabular-nums; }

  /* ── Utility: eyebrow/caption mono text ── */
  .mono-caption {
    font-family: var(--mono);
    font-size: 11px;
    letter-spacing: 0;
    text-transform: uppercase;
    color: var(--muted);
  }
  [lang="ar"] .mono-caption {
    font-family: var(--sans);
    text-transform: none;
    font-size: 13px;
  }

  /* ── Utility: section heading top margin ── */
  .mt-section-head { margin-top: 22px; }
  .mt-hero-head    { margin-top: 28px; }
  .mt-flow-head    { margin-top: 20px; }

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
    transition: border-color var(--t-base), box-shadow var(--t-base);
  }
  /* Box-shadow appears after 60 px scroll (added via JS class) */
  nav.site.scrolled {
    border-color: var(--border);
    box-shadow: 0 4px 20px rgba(26,29,43,.08);
  }

  /* ── Hamburger / mobile drawer ── */
  .nav-hamburger {
    display: none;
    flex-direction: column;
    justify-content: center;
    gap: 5px;
    width: 44px; height: 44px;
    padding: 10px;
    border-radius: var(--r-sm);
    background: transparent;
    border: 1px solid var(--border);
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
  }
  .nav-hamburger span {
    display: block;
    width: 100%; height: 1.5px;
    background: var(--charcoal);
    border-radius: 2px;
    transition: transform var(--t-fast), opacity var(--t-fast);
    transform-origin: center;
  }
  @media (max-width: 760px) { .nav-hamburger { display: flex; } }

  /* Drawer: uses hidden checkbox trick — pure CSS, no JS required */
  #nav-drawer-toggle { position: absolute; opacity: 0; pointer-events: none; }
  .nav-drawer {
    display: none;
    position: fixed;
    inset-block-start: 0; inset-inline-end: 0;
    width: min(320px, 85vw);
    height: 100dvh;
    background: var(--white);
    box-shadow: -4px 0 32px rgba(26,29,43,.15);
    z-index: 50;
    flex-direction: column;
    padding: var(--sp-8) var(--sp-4) var(--sp-4);
    gap: var(--sp-2);
    transform: translateX(100%);
    transition: transform var(--t-slow);
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
  }
  [dir="rtl"] .nav-drawer { inset-inline-end: auto; inset-inline-start: 0; transform: translateX(-100%); }
  #nav-drawer-toggle:checked ~ .nav-drawer {
    display: flex;
    transform: translateX(0);
  }
  [dir="rtl"] #nav-drawer-toggle:checked ~ .nav-drawer { transform: translateX(0); }
  .nav-drawer-overlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(26,29,43,.4);
    z-index: 45;
  }
  #nav-drawer-toggle:checked ~ .nav-drawer-overlay { display: block; }
  .nav-drawer a {
    display: block;
    font-size: 1.125rem;
    font-weight: 500;
    color: var(--charcoal);
    text-decoration: none;
    padding: var(--sp-2) 0;
    border-bottom: 1px solid var(--border);
  }
  .nav-drawer a:last-child { border-bottom: 0; }
  .nav-drawer a:hover { color: var(--navy-dk); }
  .nav-drawer-close {
    position: absolute;
    top: var(--sp-3); inset-inline-end: var(--sp-3);
    width: 44px; height: 44px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
    color: var(--muted);
    cursor: pointer;
    border-radius: var(--r-sm);
    border: 1px solid var(--border);
    background: var(--canvas);
  }
  .nav-drawer-close:hover { color: var(--charcoal); }
  .nav-drawer-cta    { margin-top: var(--sp-2) !important; }
  .nav-drawer-cta--sm { margin-top: var(--sp-1) !important; }
  /* Animate hamburger → × when open */
  #nav-drawer-toggle:checked ~ nav.site .nav-hamburger span:nth-child(1) { transform: translateY(6.5px) rotate(45deg); }
  #nav-drawer-toggle:checked ~ nav.site .nav-hamburger span:nth-child(2) { opacity: 0; transform: scaleX(0); }
  #nav-drawer-toggle:checked ~ nav.site .nav-hamburger span:nth-child(3) { transform: translateY(-6.5px) rotate(-45deg); }
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
  /* WCAG fix: --accent-dk (#C98C0A) + --off-white = 2.77:1 FAIL → navy-dk = 5.04:1 PASS */
  .cta.accent:hover { background: var(--accent-dk); color: var(--navy-dk); }
  /* ghost variant: white bg, charcoal text, border */
  .cta.ghost { background: var(--white); color: var(--charcoal); border: 1px solid var(--border); }
  .cta.ghost:hover { background: var(--canvas); color: var(--charcoal); border-color: var(--charcoal); transform: translateY(-1px); }
  /* danger/dashboard variant */
  .cta.danger { background: var(--rose); color: var(--white); border: 1px solid var(--rose); }
  .cta.danger:hover { background: var(--rose-dk); color: var(--white); border-color: var(--rose-dk); }
  /* nav .links a sets charcoal + opacity; needs higher specificity than .cta alone */
  nav .links a.cta {
    color: var(--off-white);
    opacity: 1;
  }
  nav .links a.cta:hover {
    color: var(--off-white);
    opacity: 1;
  }
  nav .links a.cta.accent { color: var(--navy-dk); }
  /* WCAG fix: match base accent hover */
  nav .links a.cta.accent:hover { color: var(--navy-dk); }
  nav .links a.cta.ghost { color: var(--charcoal); }
  nav .links a.cta.danger { color: var(--white); opacity: 1; }
  nav .links a.cta.danger:hover { color: var(--white); opacity: 1; }
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
    font-size: clamp(2.25rem, 2vw + 1.75rem, 4.25rem);
    max-width: 11ch;
    line-height: 1.08;
  }
  /* RTL/Arabic: use clamp so it stays fluid, never overflows on small screens */
  [lang="ar"] .hero h1 { font-size: clamp(2.5rem, 2vw + 2rem, 5.3rem); max-width: 13ch; }
  /* Hero impact card appears slightly after the heading */
  .hero-impact--delayed { transition-delay: 0.15s; }

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
  .agg-big .unit { font-size: 0.3em; color: var(--muted); margin-inline-start: 10px; vertical-align: super; font-family: var(--mono); letter-spacing: 0; }
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
    font-size: clamp(2rem, 1.5rem + 2.5vw, 3.25rem);
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
    font-size: clamp(2.25rem, 1.5rem + 3vw, 3.75rem);
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

  /* Flow connector — logical properties so gradient runs correctly in RTL */
  .flow-connector {
    position: absolute;
    top: 44%; inset-inline: 0;
    height: 1px;
    background: repeating-linear-gradient(
      to inline-end,
      var(--border) 0 6px,
      transparent 6px 12px
    );
    z-index: -1;
  }
  /* Fallback for browsers not supporting inline-end in gradients */
  @supports not (background: linear-gradient(to inline-end, red, blue)) {
    .flow-connector {
      background: repeating-linear-gradient(90deg, var(--border) 0 6px, transparent 6px 12px);
    }
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
    font-size: clamp(2.25rem, 1.5rem + 3vw, 3.75rem);
    max-width: 14ch;
  }
  .privacy h2 em { color: var(--rose-dk); }
  /* opacity: 0.85 on charcoal-on-gold-bg — removed, full opacity safer for contrast */
  .privacy .body { margin-top: 28px; font-size: 17px; color: var(--charcoal); max-width: 50ch; line-height: 1.65; }

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

  /* RTL fix: fixed 110px column breaks Arabic labels — use intrinsic sizing */
  .id-row { display: grid; grid-template-columns: minmax(90px, max-content) 1fr; gap: 16px; padding: 10px 0; border-bottom: 1px dashed var(--border); align-items: baseline; }
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
  /* Replace inline style="color: #2AAE5F" — use semantic CSS variable */
  :root { --color-verified: #2AAE5F; }
  .id-row .v.verified { color: var(--color-verified); font-weight: 500; }
  /* Replace inline style="margin-top: 6px" on first id-row */
  .id-row--first { margin-top: 6px; }

  .arrow-down {
    display: flex; align-items: center; justify-content: center; color: var(--gold-dk);
    font-family: var(--mono); font-size: 11px; letter-spacing: 0; text-transform: uppercase; gap: 8px;
  }
  [lang="ar"] .arrow-down { font-family: var(--sans); text-transform: none; letter-spacing: 0; font-size: 13px; }

  /* ---------- Trust & Transparency ---------- */
  section.trust { padding: clamp(80px, 12vw, 140px) 0; }
  .trust-grid { display: grid; grid-template-columns: 1fr 1fr; gap: clamp(32px, 4vw, 56px); }
  @media (max-width: 900px) { .trust-grid { grid-template-columns: 1fr; } }

  .trust-left h2 { font-size: clamp(2.25rem, 1.5rem + 3vw, 3.75rem); max-width: 14ch; }
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
  .ledger-row .amt { font-family: var(--display); font-size: 22px; color: var(--navy-dk); font-feature-settings: "tnum"; font-variant-numeric: tabular-nums; white-space: nowrap; }
  .ledger-row .amt .u { font-family: var(--mono); font-size: 10px; color: var(--muted); margin-inline-start: 4px; }
  [lang="ar"] .ledger-row .amt .u { font-family: var(--sans); font-size: 12px; }

  .ledger-foot { margin-top: 18px; display: flex; justify-content: space-between; align-items: center; padding-top: 14px; border-top: 1px solid var(--border); }
  .ledger-foot a { font-size: 13px; color: var(--navy-dk); text-decoration: underline; text-underline-offset: 4px; }
  /* Replace inline style on ledger-foot span */
  .ledger-foot-meta { font-family: var(--mono); font-size: 11px; color: var(--muted); letter-spacing: 0; }
  [lang="ar"] .ledger-foot-meta { font-family: var(--sans); font-size: 12px; }

  /* trust-badges: always 2-col when rendered (override repeat(3,1fr)) */
  .trust-badges { margin-top: 28px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  @media (max-width: 640px) { .trust-badges { grid-template-columns: 1fr; } }
  .badge-card {
    border: 1px solid var(--border);
    padding: 18px;
    background: var(--white);
  }
  .badge-card .n { font-family: var(--display); font-size: 30px; color: var(--navy-dk); line-height: 1; font-variant-numeric: tabular-nums; }
  .badge-card .t { font-size: 12px; color: var(--muted); margin-top: 8px; }
  /* Replace inline style="font-size:.6em;color:var(--muted)" on % superscript */
  .badge-pct { font-size: 0.6em; color: var(--muted); }

  /* ---------- Providers ---------- */
  section.providers {
    padding: clamp(80px, 12vw, 140px) 0;
    background: var(--canvas);
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
  }
  .providers-intro { max-width: 780px; margin-bottom: 56px; }
  .providers-intro h2 { font-size: clamp(2.25rem, 1.5rem + 3vw, 3.6rem); }
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
  .stories-head h2 { font-size: clamp(2.25rem, 1.5rem + 3vw, 3.6rem); max-width: 16ch; }

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
    font-size: clamp(2.5rem, 2rem + 3.5vw, 4.75rem);
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
  /* WCAG fix: --rose (#E0305E) + white 14px = 4.38:1 FAIL → rose-dk (#B8244C) + white = 6.21:1 PASS */
  .cta-card.request:hover { background: var(--rose-dk); color: var(--white); border-color: var(--rose-dk); }
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
    padding: clamp(48px, 8vw, 80px) 0 clamp(32px, 5vw, 48px);
    border-top: 1px solid color-mix(in oklab, var(--navy-lt) 20%, transparent);
  }
  footer .rows { display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr; gap: 32px; }
  @media (max-width: 760px) { footer .rows { grid-template-columns: 1fr 1fr; gap: 24px; } }
  @media (max-width: 560px) { footer .rows { grid-template-columns: 1fr; } }
  footer h5 { font-family: var(--mono); font-size: 11px; letter-spacing: 0; text-transform: uppercase; color: var(--navy-lt); margin-bottom: 14px; }
  [lang="ar"] footer h5 { font-family: var(--sans); text-transform: none; letter-spacing: 0; font-size: 13px; }
  /* hover: color shift (not opacity-only) per WCAG */
  footer a { display: block; color: var(--off-white); text-decoration: none; font-size: 14px; margin-bottom: 10px; opacity: .85; transition: color var(--t-fast), opacity var(--t-fast); }
  footer a:hover { opacity: 1; color: var(--gold-lt); }
  footer .brand-col .logo { color: var(--off-white); font-size: 28px; }
  footer .brand-col p { color: var(--navy-lt); margin-top: 16px; font-size: 14px; line-height: 1.6; max-width: 30ch; }

  /* Social icons row */
  footer .social-row { display: flex; gap: var(--sp-2); margin-top: var(--sp-3); flex-wrap: wrap; }
  footer .social-icon {
    display: inline-flex; align-items: center; justify-content: center;
    width: 40px; height: 40px;
    border-radius: var(--r-sm);
    border: 1px solid color-mix(in oklab, var(--navy-lt) 30%, transparent);
    color: var(--navy-lt);
    text-decoration: none;
    transition: border-color var(--t-fast), color var(--t-fast), background var(--t-fast);
    margin-bottom: 0;
    opacity: 1;
  }
  footer .social-icon:hover { color: var(--gold-lt); border-color: var(--gold-lt); background: color-mix(in oklab, var(--gold-lt) 10%, transparent); }
  footer .social-icon svg { width: 18px; height: 18px; }

  /* Legal strip — --navy-lt on --navy-dk = 6.96:1, passes AA ✓ */
  footer .bottom {
    margin-top: var(--sp-8);
    padding-top: var(--sp-4);
    border-top: 1px solid color-mix(in oklab, var(--navy-lt) 20%, transparent);
    display: flex; justify-content: space-between; flex-wrap: wrap; gap: 12px;
    font-size: 0.75rem;
    color: var(--navy-lt);
  }
  footer .bottom a { display: inline; margin-bottom: 0; font-size: inherit; }

  /* ---------- Reveal animations + stagger ---------- */
  .reveal { opacity: 0; transform: translateY(14px); transition: opacity .6s ease-out, transform .6s ease-out; }
  .reveal.on { opacity: 1; transform: none; }

  /* Role cards — stagger sequentially */
  .flow-track .role-card:nth-child(1) { transition-delay: 0s; }
  .flow-track .role-card:nth-child(2) { transition-delay: 0.1s; }
  .flow-track .role-card:nth-child(3) { transition-delay: 0.2s; }

  /* Provider cells */
  .provider-types .provider-cell:nth-child(1) { transition-delay: 0s; }
  .provider-types .provider-cell:nth-child(2) { transition-delay: 0.08s; }
  .provider-types .provider-cell:nth-child(3) { transition-delay: 0.16s; }
  .provider-types .provider-cell:nth-child(4) { transition-delay: 0.24s; }

  /* CTA triad cards */
  .cta-triad .cta-card:nth-child(1) { transition-delay: 0s; }
  .cta-triad .cta-card:nth-child(2) { transition-delay: 0.1s; }
  .cta-triad .cta-card:nth-child(3) { transition-delay: 0.2s; }

  /* Story cards */
  .stories-grid .story:nth-child(1) { transition-delay: 0s; }
  .stories-grid .story:nth-child(2) { transition-delay: 0.1s; }
  .stories-grid .story:nth-child(3) { transition-delay: 0.2s; }

  /* ── CSS scroll-snap for stories on mobile ── */
  @media (max-width: 900px) {
    .stories-grid {
      display: flex;
      overflow-x: auto;
      scroll-snap-type: x mandatory;
      -webkit-overflow-scrolling: touch;
      gap: 16px;
      padding-bottom: var(--sp-2);
      /* Hide scrollbar but keep scrollability */
      scrollbar-width: none;
    }
    .stories-grid::-webkit-scrollbar { display: none; }
    .stories-grid .story {
      flex: 0 0 min(82vw, 360px);
      scroll-snap-align: start;
      min-height: 280px;
    }
  }

  @media (max-width: 900px) {
    .hero { padding: 44px 0 64px; }
    /* h1/h2 sizes now fluid via clamp() — only agg panel needs override */
    .agg-big { font-size: 4rem; }
    .provider-strip .big { font-size: 1.85rem; }
    .story { min-height: auto; }
  }

  @media (max-width: 560px) {
    .wrap { padding-inline: 16px; }
    .hero { padding: 22px 0 32px; }
    /* hero h1 — clamp handles fluid scale; only max-width tweak needed */
    .hero h1, [lang="ar"] .hero h1 { max-width: 12ch; }
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
    .role-card,
    .story,
    .cta-card {
      padding: 24px;
    }
    .story .quote { font-size: 1.1rem; }
    .story-meta { align-items: flex-start; flex-direction: column; gap: 6px; }
    /* cta-band h2 and cta-card h3 now fluid via clamp — no override needed */
  }

  @media (max-width: 420px) {
    /* hero h1 fluid via clamp — only layout tweaks remain */
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
<a class="skip-link" href="#main-content">{{ __('welcome.skip_link') }}</a>

<!-- CSS-only mobile drawer toggle -->
<input type="checkbox" id="nav-drawer-toggle" aria-hidden="true" />
<label class="nav-drawer-overlay" for="nav-drawer-toggle" aria-hidden="true"></label>
<nav class="nav-drawer" aria-label="{{ __('welcome.nav.aria') }}">
  <label class="nav-drawer-close" for="nav-drawer-toggle" aria-label="{{ __('welcome.nav.close_drawer') }}">✕</label>
  <a href="#idea">{{ __('welcome.nav.idea') }}</a>
  <a href="#how">{{ __('welcome.nav.how') }}</a>
  <a href="#trust">{{ __('welcome.nav.trust') }}</a>
  <a href="#providers">{{ __('welcome.nav.providers') }}</a>
  @if (auth()->check())
  <a href="{{ url('/dashboard') }}" class="cta danger nav-drawer-cta">{{ __('welcome.nav.dashboard') }}</a>
  @else
  <a href="{{ route('register', ['type' => 'donor']) }}" class="cta accent nav-drawer-cta">{{ __('welcome.nav.give') }}</a>
  <a href="{{ route('login') }}" class="cta nav-drawer-cta--sm">{{ __('welcome.nav.login') }}</a>
  @endif
</nav>

<!-- ========== NAV ========== -->
<nav class="site" id="nav" aria-label="{{ __('welcome.nav.aria') }}">
  <div class="inner">
    <a href="{{ url('/') }}" class="logo" aria-label="{{ config('app.name', 'NUBL') }}">
      <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name', 'NUBL') }}" class="brand-logo" />
    </a>

    <div class="links">
      <a href="#idea">{{ __('welcome.nav.idea') }}</a>
      <a href="#how">{{ __('welcome.nav.how') }}</a>
      <a href="#trust">{{ __('welcome.nav.trust') }}</a>
      <a href="#providers">{{ __('welcome.nav.providers') }}</a>
      <button class="lang" id="langToggle" type="button" aria-label="{{ __('welcome.nav.lang_aria') }}" data-en-url="{{ route('locale.switch', 'en') }}" data-ar-url="{{ route('locale.switch', 'ar') }}">
        <span id="langLabel">{{ __('welcome.nav.lang_label') }}</span>
      </button>
      @if (auth()->check())
      <a href="{{ url('/dashboard') }}" class="cta danger">
        <span>{{ __('welcome.nav.dashboard') }}</span>
        <svg viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
      @else
      <a href="{{ route('register', ['type' => 'donor']) }}" class="cta accent">
        <span>{{ __('welcome.nav.give') }}</span>
        <svg viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
      <a href="{{ route('login') }}" class="cta">
        <span>{{ __('welcome.nav.login') }}</span>
        <svg viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
      @endif
    </div>

    <!-- Hamburger: visible ≤760 px, opens CSS drawer -->
    <label class="nav-hamburger" for="nav-drawer-toggle" aria-label="{{ __('welcome.nav.open_menu') }}" role="button" tabindex="0">
      <span></span><span></span><span></span>
    </label>
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
      <span class="eyebrow reveal">{{ __('welcome.hero.eyebrow') }}</span>
      <h1 class="display reveal mt-hero-head">
        {{ __('welcome.hero.h1_1') }} <i>{{ __('welcome.hero.h1_2') }}</i> {{ __('welcome.hero.h1_3') }}
      </h1>
      <p class="sub reveal">{!! __('welcome.hero.sub') !!}</p>
      <div class="hero-ctas reveal">
        <a href="{{ route('register', ['type' => 'donor']) }}" class="cta accent">
          <span>{{ __('welcome.hero.cta1') }}</span>
          <svg viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
        <a href="{{ route('register', ['type' => 'recipient']) }}" class="cta ghost">
          <span>{{ __('welcome.hero.cta2') }}</span>
          <svg viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
      </div>
    </div>

    <div class="reveal hero-impact hero-impact--delayed">
      <div class="agg-card">
        <div class="agg-head">
          <span class="stamp"><span class="dot"></span><span>{{ __('welcome.impact.live') }}</span></span>
          <span class="stamp">{{ __('welcome.impact.q') }}</span>
        </div>
        <div class="agg-big" data-counter="{{ $heroStats['totalDelivered'] }}"><span class="agg-value">0</span><span class="unit">{{ __('welcome.impact.amount_unit') }}</span></div>
        <div class="agg-label">{{ __('welcome.impact.amount_label') }}</div>

        <div class="agg-split">
          <div>
            <div class="num" data-counter="{{ $heroStats['familiesSupported'] }}">0</div>
            <div class="tag">{{ __('welcome.impact.families_tag') }}</div>
          </div>
          <div>
            <div class="num" data-counter="{{ $heroStats['localProviders'] }}">0</div>
            <div class="tag">{{ __('welcome.impact.providers_tag') }}</div>
          </div>
        </div>
      </div>

      <!-- aria-live="polite" so screen readers announce new feed items non-intrusively -->
      <div class="feed" aria-live="polite" aria-atomic="false" aria-relevant="additions">
        <div class="feed-head">
          <span class="live"><span class="dot"></span><span>{{ __('welcome.impact.live_feed') }}</span></span>
          <span>{{ __('welcome.impact.anonymized') }}</span>
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
      {{ __('welcome.manifesto.chapter') }}
      <hr/>
      {{ __('welcome.manifesto.chapter_label') }}
    </aside>
    <div>
      <span class="eyebrow">{{ __('welcome.manifesto.eyebrow') }}</span>
      <h2 class="display mt-section-head">
        {{ __('welcome.manifesto.h1') }} <em>{{ __('welcome.manifesto.h2') }}</em> {{ __('welcome.manifesto.h3') }}
      </h2>
      <p class="lede">{{ __('welcome.manifesto.p') }}</p>
    </div>
  </div>
</section>

<!-- ========== HOW IT WORKS ========== -->
<section class="flow" id="how">
  <div class="wrap">
    <div class="flow-head">
      <div>
        <span class="eyebrow">{{ __('welcome.how.chapter') }}</span>
        <h2 class="display mt-flow-head">
          {{ __('welcome.how.h1') }} <i>{{ __('welcome.how.h2') }}</i>
        </h2>
      </div>
      <p>{{ __('welcome.how.p') }}</p>
    </div>

    <div class="flow-diagram">
      <div class="flow-connector" aria-hidden="true"></div>
      <div class="flow-track">

        <article class="role-card supporter reveal">
          <span class="accent-dot"></span>
          <span class="step">{{ __('welcome.how.step1') }}</span>
          <div class="icon">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
              <path d="M14 25S4 18 4 11a6 6 0 0 1 10-4.5A6 6 0 0 1 24 11c0 7-10 14-10 14z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
            </svg>
          </div>
          <h3>{{ __('welcome.how.role1_h') }}</h3>
          <p class="role-body">{{ __('welcome.how.role1_p') }}</p>
          <div class="role-actions">
            <a href="{{ route('register', ['type' => 'donor']) }}"><span>{{ __('welcome.how.role1_cta') }}</span>
              <svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
          </div>
        </article>

        <article class="role-card provider reveal">
          <span class="accent-dot"></span>
          <span class="step">{{ __('welcome.how.step2') }}</span>
          <div class="icon">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
              <path d="M4 10h20l-1.5 12a2 2 0 0 1-2 1.8H7.5a2 2 0 0 1-2-1.8L4 10z" stroke="currentColor" stroke-width="1.6"/>
              <path d="M9 10V7a5 5 0 0 1 10 0v3" stroke="currentColor" stroke-width="1.6"/>
            </svg>
          </div>
          <h3>{{ __('welcome.how.role2_h') }}</h3>
          <p class="role-body">{{ __('welcome.how.role2_p') }}</p>
          <div class="role-actions">
            <a href="{{ route('register.provider') }}"><span>{{ __('welcome.how.role2_cta') }}</span>
              <svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
          </div>
        </article>

        <article class="role-card recipient reveal">
          <span class="accent-dot"></span>
          <span class="step">{{ __('welcome.how.step3') }}</span>
          <div class="icon">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
              <circle cx="14" cy="10" r="4" stroke="currentColor" stroke-width="1.6"/>
              <path d="M5 24c0-4.5 4-8 9-8s9 3.5 9 8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
          </div>
          <h3>{{ __('welcome.how.role3_h') }}</h3>
          <p class="role-body">{{ __('welcome.how.role3_p') }}</p>
          <div class="role-actions">
            <a href="{{ route('register', ['type' => 'recipient']) }}"><span>{{ __('welcome.how.role3_cta') }}</span>
              <svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
          </div>
        </article>

      </div>
    </div>
  </div>
</section>

<!-- ========== PRIVACY ========== -->
<section class="privacy" id="privacy">
  <div class="wrap privacy-grid">
    <div>
      <span class="eyebrow">{{ __('welcome.privacy.eyebrow') }}</span>
      <h2 class="display mt-section-head">
        {{ __('welcome.privacy.h1') }}<br/>
        <em>{{ __('welcome.privacy.h2') }}</em>
      </h2>
      <p class="body">{{ __('welcome.privacy.p') }}</p>
      <div class="privacy-pills">
        <span class="pill">{{ __('welcome.privacy.pill1') }}</span>
        <span class="pill">{{ __('welcome.privacy.pill2') }}</span>
        <span class="pill">{{ __('welcome.privacy.pill3') }}</span>
        <span class="pill">{{ __('welcome.privacy.pill4') }}</span>
      </div>
    </div>

    <div class="card-pair reveal">
      <div class="id-card clear">
        <div class="title-line">
          <span>{{ __('welcome.privacy.id_held') }}</span>
          <span>01 · RAW</span>
        </div>
        <div class="id-row id-row--first">
          <span class="k">{{ __('welcome.privacy.id_name') }}</span>
          <span class="v redacted">Fatima Al-Hashimi</span>
        </div>
        <div class="id-row">
          <span class="k">{{ __('welcome.privacy.id_area') }}</span>
          <span class="v redacted">Ras Beirut — Block 04</span>
        </div>
        <div class="id-row">
          <span class="k">{{ __('welcome.privacy.id_family') }}</span>
          <span class="v redacted">4 adults, 2 children</span>
        </div>
        <div class="id-row">
          <span class="k">{{ __('welcome.privacy.id_status') }}</span>
          <span class="v verified">{{ __('welcome.privacy.id_verified') }}</span>
        </div>
      </div>

      <div class="arrow-down">
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M7 2v10M3 8l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>{{ __('welcome.privacy.tokenized') }}</span>
      </div>

      <div class="id-card protected">
        <div class="title-line">
          <span>{{ __('welcome.privacy.id_shown') }}</span>
          <span>02 · TOKEN</span>
        </div>
        <div class="id-row id-row--first">
          <span class="k">{{ __('welcome.privacy.id_ref') }}</span>
          <span class="v token">NBL-7F3A-QR</span>
        </div>
        <div class="id-row">
          <span class="k">{{ __('welcome.privacy.id_region') }}</span>
          <span class="v">{{ __('welcome.privacy.id_region_v') }}</span>
        </div>
        <div class="id-row">
          <span class="k">{{ __('welcome.privacy.id_household') }}</span>
          <span class="v">{{ __('welcome.privacy.id_household_v') }}</span>
        </div>
        <div class="id-row">
          <span class="k">{{ __('welcome.privacy.id_fulfillment') }}</span>
          <span class="v">{{ __('welcome.privacy.id_fulfill_v') }}</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ========== TRUST / TRANSPARENCY ========== -->
<section class="trust" id="trust">
  <div class="wrap trust-grid">
    <div class="trust-left">
      <span class="eyebrow">{{ __('welcome.trust.eyebrow') }}</span>
      <h2 class="display mt-section-head">
        {{ __('welcome.trust.h1') }} <i>{{ __('welcome.trust.h2') }}</i>
      </h2>
      <p>{{ __('welcome.trust.p') }}</p>

      @if($heroStats['trustBadges'] !== null)
      <div class="trust-badges">
        <div class="badge-card">
          <div class="n">{{ $heroStats['trustBadges']['delivered'] }}<span class="badge-pct">%</span></div>
          <div class="t">{{ __('welcome.trust.badge1') }}</div>
        </div>
        <div class="badge-card">
          <div class="n">{{ $heroStats['trustBadges']['held'] }}<span class="badge-pct">%</span></div>
          <div class="t">{{ __('welcome.trust.badge2') }}</div>
        </div>
      </div>
      @endif
    </div>

    <div class="transparency-ledger" aria-label="Live ledger preview">
      <div class="ledger-head">
        <h4>{{ $heroStats['trustLedger']['is_live'] ? __('welcome.trust.ledger_h') : __('welcome.trust.ledger_h_preview') }}</h4>
        <span class="date" id="ledgerDate">19 APR 2026</span>
      </div>
      @foreach($heroStats['trustLedger']['rows'] as $row)
      <div class="ledger-row">
        <div>
          <div class="desc">{{ $row['desc'] }}</div>
          @if($heroStats['trustLedger']['is_live'])
          <div class="meta">{{ $row['meta'] }}</div>
          @endif
        </div>
        <div class="amt">{{ $row['amount'] }}<span class="u">{{ __('welcome.trust.sar') }}</span></div>
      </div>
      @endforeach

      <div class="ledger-foot">
        <span class="ledger-foot-meta">
          @if($heroStats['trustLedger']['is_live'])
            {{ __('welcome.trust.ledger_count_live', ['shown' => $heroStats['trustLedger']['shown'], 'total' => $heroStats['trustLedger']['total']]) }}
          @else
            {{ __('welcome.trust.ledger_count_preview') }}
          @endif
        </span>
        <a href="#trust">{{ __('welcome.trust.ledger_full') }}</a>
      </div>
    </div>
  </div>
</section>

<!-- ========== PROVIDERS ========== -->
<section class="providers" id="providers">
  <div class="wrap">
    <div class="providers-intro">
      <span class="eyebrow">{{ __('welcome.providers.eyebrow') }}</span>
      <h2 class="display mt-section-head">
        {{ __('welcome.providers.h1') }} <em>{{ __('welcome.providers.h2') }}</em>
      </h2>
      <p>{{ __('welcome.providers.p') }}</p>
    </div>

    <div class="provider-types">
      <div class="provider-cell reveal">
        <div class="glyph">
          <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
            <rect x="5" y="10" width="26" height="20" stroke="currentColor" stroke-width="1.5"/>
            <path d="M5 14h26" stroke="currentColor" stroke-width="1.5"/>
            <path d="M13 10V6h10v4" stroke="currentColor" stroke-width="1.5"/>
            <circle cx="13" cy="22" r="1.5" fill="currentColor"/>
            <circle cx="23" cy="22" r="1.5" fill="currentColor"/>
          </svg>
        </div>
        <h4>{{ __('welcome.providers.prov1_h') }}</h4>
        <p>{{ __('welcome.providers.prov1_p') }}</p>
        <div class="cnt"><b data-counter-inline="{{ $heroStats['providerCounts']['grocery'] }}">{{ $heroStats['providerCounts']['grocery'] }}</b><span>{{ __('welcome.providers.prov1_cnt') }}</span></div>
      </div>

      <div class="provider-cell reveal">
        <div class="glyph">
          <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
            <path d="M10 6v12a4 4 0 0 0 4 4v8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            <path d="M10 6v12M14 6v12M18 6v12a4 4 0 0 1-4 4" stroke="currentColor" stroke-width="1.5"/>
            <path d="M24 6c-2 0-3 4-3 8s1 5 3 5v11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
        </div>
        <h4>{{ __('welcome.providers.prov4_h') }}</h4>
        <p>{{ __('welcome.providers.prov4_p') }}</p>
        <div class="cnt"><b data-counter-inline="{{ $heroStats['providerCounts']['restaurant'] }}">{{ $heroStats['providerCounts']['restaurant'] }}</b><span>{{ __('welcome.providers.prov4_cnt') }}</span></div>
      </div>
      
      <div class="provider-cell reveal">
        <div class="glyph">
          <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
            <path d="M6 28h24M8 28V14c0-4 4-8 10-8s10 4 10 8v14" stroke="currentColor" stroke-width="1.5"/>
            <path d="M13 14h10" stroke="currentColor" stroke-width="1.5"/>
          </svg>
        </div>
        <h4>{{ __('welcome.providers.prov2_h') }}</h4>
        <p>{{ __('welcome.providers.prov2_p') }}</p>
        <div class="cnt"><b data-counter-inline="{{ $heroStats['providerCounts']['catering'] }}">{{ $heroStats['providerCounts']['catering'] }}</b><span>{{ __('welcome.providers.prov2_cnt') }}</span></div>
      </div>


      
      <div class="provider-cell reveal">
        <div class="glyph">
          <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
            <path d="M6 22c0-6 5-10 12-10s12 4 12 10H6z" stroke="currentColor" stroke-width="1.5"/>
            <path d="M4 26h28" stroke="currentColor" stroke-width="1.5"/>
            <path d="M14 8v4M18 6v6M22 8v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
        </div>
        <h4>{{ __('welcome.providers.prov3_h') }}</h4>
        <p>{{ __('welcome.providers.prov3_p') }}</p>
        <div class="cnt"><b data-counter-inline="{{ $heroStats['providerCounts']['bakery'] }}">{{ $heroStats['providerCounts']['bakery'] }}</b><span>{{ __('welcome.providers.prov3_cnt') }}</span></div>
      </div>

   
    </div>

    <div class="provider-strip">
      <div class="big display">
        {{ __('welcome.providers.strip_1') }} <em>{{ __('welcome.providers.strip_2') }}</em>
      </div>
      <a href="{{ route('register.provider') }}" class="cta">
        <span>{{ __('welcome.providers.strip_cta') }}</span>
        <svg viewBox="0 0 16 16" width="14" height="14" fill="none" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
    </div>
  </div>
</section>

<!-- ========== STORIES ========== -->
<section class="stories" id="stories">
  <div class="wrap">
    <div class="stories-head">
      <div>
        <span class="eyebrow">{{ __('welcome.stories.eyebrow') }}</span>
        <h2 class="display mt-flow-head">
          {{ __('welcome.stories.h1') }} <i>{{ __('welcome.stories.h2') }}</i>
        </h2>
      </div>
      <span class="mono-caption">{{ __('welcome.stories.consent') }}</span>
    </div>

    <div class="stories-grid">
      <article class="story feature reveal">
        <p class="quote">{{ __('welcome.stories.story1') }}</p>
        <div class="story-meta">
          <span class="role">{{ __('welcome.stories.story1_role') }}</span>
          <span>{{ __('welcome.stories.story1_loc') }}</span>
        </div>
      </article>

      <article class="story reveal">
        <p class="quote">{{ __('welcome.stories.story2') }}</p>
        <div class="story-meta">
          <span class="role">{{ __('welcome.stories.story2_role') }}</span>
          <span>{{ __('welcome.stories.story2_loc') }}</span>
        </div>
      </article>

      <article class="story feature-2 reveal">
        <p class="quote">{{ __('welcome.stories.story3') }}</p>
        <div class="story-meta">
          <span class="role">{{ __('welcome.stories.story3_role') }}</span>
          <span>{{ __('welcome.stories.story3_loc') }}</span>
        </div>
      </article>
    </div>
  </div>
</section>

<!-- ========== CTA BAND ========== -->
<section class="cta-band" id="cta">
  <div class="wrap">
    <span class="eyebrow">{{ __('welcome.cta.eyebrow') }}</span>
    <h2 class="display">
      {{ __('welcome.cta.h1') }}<br/>
      <em>{{ __('welcome.cta.h2') }}</em>
    </h2>

    <div class="cta-triad">
      <a href="{{ route('register', ['type' => 'donor']) }}" class="cta-card donate reveal">
        <span class="num">01</span>
        <h3>{{ __('welcome.cta.a_h') }}</h3>
        <p>{{ __('welcome.cta.a_p') }}</p>
        <span class="go"><span>{{ __('welcome.cta.a_cta') }}</span>
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
      </a>

      <a href="{{ route('register', ['type' => 'recipient']) }}" class="cta-card request reveal">
        <span class="num">02</span>
        <h3>{{ __('welcome.cta.b_h') }}</h3>
        <p>{{ __('welcome.cta.b_p') }}</p>
        <span class="go"><span>{{ __('welcome.cta.b_cta') }}</span>
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
      </a>

      <a href="{{ route('register.provider') }}" class="cta-card join reveal">
        <span class="num">03</span>
        <h3>{{ __('welcome.cta.c_h') }}</h3>
        <p>{{ __('welcome.cta.c_p') }}</p>
        <span class="go"><span>{{ __('welcome.cta.c_cta') }}</span>
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
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
          <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name', 'NUBL') }}" class="brand-logo" loading="lazy" />
        </a>
        <p>{{ __('welcome.footer.tagline') }}</p>
        <!-- Social icons -->
        <div class="social-row" role="list">
          <a href="#" class="social-icon" aria-label="Twitter / X" role="listitem">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 4l16 16M4 20L20 4"/></svg>
          </a>
          <a href="#" class="social-icon" aria-label="LinkedIn" role="listitem">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="4"/><path d="M7 10v7M7 7v.01M11 17v-4a2 2 0 0 1 4 0v4M11 13v4"/></svg>
          </a>
          <a href="#" class="social-icon" aria-label="Instagram" role="listitem">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
          </a>
        </div>
      </div>
      <div>
        <h5>{{ __('welcome.footer.platform') }}</h5>
        <a href="#how">{{ __('welcome.footer.how') }}</a>
        <a href="#trust">{{ __('welcome.footer.transparency') }}</a>
        <a href="#providers">{{ __('welcome.footer.coverage') }}</a>
      </div>
      <div>
        <h5>{{ __('welcome.footer.join') }}</h5>
        <a href="{{ route('register', ['type' => 'donor']) }}">{{ __('welcome.footer.support') }}</a>
        <a href="{{ route('register', ['type' => 'recipient']) }}">{{ __('welcome.footer.request') }}</a>
        <a href="{{ route('register.provider') }}">{{ __('welcome.footer.partner') }}</a>
      </div>
      <div>
        <h5>{{ __('welcome.footer.org') }}</h5>
        <a href="#idea">{{ __('welcome.footer.about') }}</a>
        <a href="#stories">{{ __('welcome.footer.press') }}</a>
        <a href="#cta">{{ __('welcome.footer.contact') }}</a>
      </div>
    </div>
    <div class="bottom">
      <span>{{ __('welcome.footer.copy') }}</span>
      <span>{{ __('welcome.footer.legal') }}</span>
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
  // -------- Locale (server-side) --------
  const CURRENT_LOCALE = "{{ app()->getLocale() }}";
  const FEED_ITEMS = @json($heroStats['feedItems']);

  // -------- Tweak defaults (editmode block) --------
  const TWEAK_DEFAULTS = /*EDITMODE-BEGIN*/{
    "lang": "{{ app()->getLocale() === 'ar' ? 'ar' : 'en' }}",
    "accent": "gold",
    "density": "default"
  }/*EDITMODE-END*/;

  let TWEAKS = { ...TWEAK_DEFAULTS };
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

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
    if (!list) return;
    list.innerHTML = '';
    FEED_ITEMS.forEach((it, i) => {
      const d = document.createElement('div');
      d.className = 'feed-item' + (i === 0 ? ' active' : '');
      const r1 = document.createElement('div');
      r1.className = 'row1';
      r1.textContent = it.row1;
      const r2 = document.createElement('div');
      r2.className = 'row2';
      r2.textContent = it.row2;
      d.appendChild(r1);
      d.appendChild(r2);
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
    const locale = CURRENT_LOCALE === 'ar' ? 'ar-EG' : 'en-US';
    const valueEl = el.querySelector(':scope > .agg-value');
    const textNode = valueEl ? null : el.childNodes[el.childNodes.length - 1];
    function setFormatted(n) {
      const s = n.toLocaleString(locale);
      if (valueEl) valueEl.textContent = s;
      else textNode.nodeValue = s;
    }
    if (reduceMotion) {
      setFormatted(target);
      return;
    }
    function tick(now) {
      const t = Math.min(1, (now - start) / duration);
      const eased = 1 - Math.pow(1 - t, 3);
      const v = Math.floor(target * eased);
      setFormatted(v);
      if (t < 1) requestAnimationFrame(tick);
      else setFormatted(target);
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

  // Ensure anything already in the viewport on load reveals immediately
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
    /* 60 px threshold per spec — enough to clear the hero eyebrow */
    nav.classList.toggle('scrolled', window.scrollY > 60);
  });

  // -------- Language toggle button in nav --------
  const langToggle = document.getElementById('langToggle');
  if (langToggle) {
    langToggle.addEventListener('click', () => {
      const next = CURRENT_LOCALE === 'en' ? 'ar' : 'en';
      const target = next === 'ar' ? langToggle.dataset.arUrl : langToggle.dataset.enUrl;
      if (target) window.location.href = target;
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
      if (key === 'lang') {
        const url = val === 'ar' ? langToggle?.dataset.arUrl : langToggle?.dataset.enUrl;
        if (url) { window.location.href = url; return; }
      }
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

  // -------- Ledger date --------
  function updateLedgerDate() {
    const el = document.getElementById('ledgerDate');
    if (!el) return;
    const locale = CURRENT_LOCALE === 'ar' ? 'ar-SA-u-ca-gregory' : 'en-GB';
    const formatted = new Intl.DateTimeFormat(locale, {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    }).format(new Date());
    el.textContent = CURRENT_LOCALE === 'ar' ? formatted : formatted.toUpperCase();
  }

  // -------- Init --------
  renderFeed();
  updateLedgerDate();
  applyAccent(TWEAKS.accent);
  applyDensity(TWEAKS.density);
  updateTweakUI();
</script>
</body>
</html>
