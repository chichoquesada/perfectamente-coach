<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PerfectaMENTE Coach — Sea fiel a su plan</title>
<meta name="description" content="Suba el PDF de su nutricionista. La IA construye su tablero de fidelidad. Usted solo hace check.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600;1,700&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #0a0a0a;
    --bg-elevated: #141414;
    --bg-card: #1a1a1a;
    --gold: #FFD264;
    --gold-soft: rgba(255, 210, 100, 0.12);
    --green: #4ade80;
    --red: #ef4444;
    --text: #f5f5f5;
    --text-dim: #999;
    --text-faint: #555;
    --border: rgba(255,255,255,0.06);
    --border-strong: rgba(255,255,255,0.14);
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }
  html { scroll-behavior: smooth; }

  body {
    background: var(--bg);
    color: var(--text);
    font-family: 'Inter', sans-serif;
    line-height: 1.6;
    overflow-x: hidden;
  }

  /* Grain overlay */
  body::before {
    content: '';
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 1000;
    opacity: 0.025;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
  }

  .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
  }

  /* === NAV === */
  nav {
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 100;
    background: rgba(10,10,10,0.7);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--border);
  }
  .nav-inner {
    max-width: 1200px;
    margin: 0 auto;
    padding: 16px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .nav-brand {
    font-family: 'Lora', serif;
    font-style: italic;
    font-weight: 600;
    font-size: 18px;
    letter-spacing: 0.02em;
  }
  .nav-brand span { color: var(--gold); }
  .nav-cta {
    background: var(--gold);
    color: #000;
    padding: 9px 18px;
    border-radius: 999px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    transition: transform 0.2s;
  }
  .nav-cta:hover { transform: translateY(-1px); }

  /* === HERO === */
  .hero {
    padding: 140px 0 80px;
    position: relative;
    overflow: hidden;
  }

  .hero::before {
    content: '';
    position: absolute;
    top: -200px;
    left: 50%;
    transform: translateX(-50%);
    width: 800px;
    height: 800px;
    background: radial-gradient(circle, rgba(255, 210, 100, 0.08) 0%, transparent 60%);
    pointer-events: none;
  }

  .hero-content {
    position: relative;
    z-index: 2;
    max-width: 820px;
    margin: 0 auto;
    text-align: center;
  }

  .eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--gold-soft);
    color: var(--gold);
    padding: 6px 14px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    margin-bottom: 28px;
    border: 1px solid rgba(255,210,100,0.2);
  }
  .eyebrow-dot {
    width: 6px;
    height: 6px;
    background: var(--gold);
    border-radius: 50%;
    animation: pulse 2s infinite;
  }
  @keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
  }

  .hero h1 {
    font-family: 'Lora', serif;
    font-weight: 600;
    font-size: clamp(36px, 6vw, 64px);
    line-height: 1.05;
    letter-spacing: -0.02em;
    margin-bottom: 24px;
  }
  .hero h1 em {
    font-style: italic;
    color: var(--gold);
    background: linear-gradient(90deg, #FFD264, #ffe9a8);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  .hero-sub {
    font-size: 19px;
    color: var(--text-dim);
    max-width: 580px;
    margin: 0 auto 40px;
    line-height: 1.55;
  }

  .hero-cta {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: var(--gold);
    color: #000;
    padding: 18px 32px;
    border-radius: 999px;
    text-decoration: none;
    font-size: 16px;
    font-weight: 700;
    transition: all 0.25s;
    box-shadow: 0 12px 40px rgba(255, 210, 100, 0.25);
  }
  .hero-cta:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 50px rgba(255, 210, 100, 0.35);
  }
  .hero-cta-arrow { transition: transform 0.25s; }
  .hero-cta:hover .hero-cta-arrow { transform: translateX(4px); }

  .hero-trust {
    margin-top: 28px;
    font-size: 12px;
    color: var(--text-faint);
    letter-spacing: 0.1em;
  }
  .hero-trust span { color: var(--gold); }

  /* === BAJADA: dolor === */
  .pain {
    padding: 80px 0;
    background: var(--bg-elevated);
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
  }
  .pain-inner {
    max-width: 720px;
    margin: 0 auto;
    text-align: center;
  }
  .pain-quote {
    font-family: 'Lora', serif;
    font-style: italic;
    font-size: clamp(24px, 4vw, 36px);
    line-height: 1.3;
    color: var(--text);
    margin-bottom: 16px;
  }
  .pain-quote span { color: var(--gold); }
  .pain-author {
    font-size: 12px;
    letter-spacing: 0.25em;
    color: var(--text-faint);
    text-transform: uppercase;
  }

  /* === HOW IT WORKS === */
  .how {
    padding: 100px 0;
  }

  .section-eyebrow {
    text-align: center;
    font-size: 11px;
    color: var(--gold);
    letter-spacing: 0.3em;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 12px;
  }

  .section-title {
    font-family: 'Lora', serif;
    font-weight: 600;
    font-size: clamp(28px, 4vw, 44px);
    line-height: 1.15;
    text-align: center;
    margin-bottom: 60px;
  }
  .section-title em { color: var(--gold); font-style: italic; }

  .steps {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
    counter-reset: step;
  }
  @media (max-width: 768px) {
    .steps { grid-template-columns: 1fr; }
  }

  .step {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 32px 28px;
    position: relative;
    transition: all 0.3s;
  }
  .step:hover {
    border-color: rgba(255,210,100,0.3);
    transform: translateY(-4px);
  }
  .step-num {
    font-family: 'Lora', serif;
    font-style: italic;
    font-size: 56px;
    color: var(--gold);
    line-height: 1;
    margin-bottom: 16px;
    opacity: 0.4;
  }
  .step-title {
    font-family: 'Lora', serif;
    font-weight: 600;
    font-size: 22px;
    margin-bottom: 12px;
  }
  .step-desc {
    color: var(--text-dim);
    font-size: 15px;
    line-height: 1.6;
  }

  /* === PHONE MOCKUP === */
  .demo-section {
    padding: 100px 0;
    background: linear-gradient(180deg, transparent, rgba(255,210,100,0.03), transparent);
  }

  .demo-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 80px;
    align-items: center;
    max-width: 1100px;
    margin: 0 auto;
  }
  @media (max-width: 900px) {
    .demo-grid { grid-template-columns: 1fr; gap: 50px; text-align: center; }
  }

  .demo-text h2 {
    font-family: 'Lora', serif;
    font-weight: 600;
    font-size: clamp(28px, 4vw, 42px);
    line-height: 1.15;
    margin-bottom: 20px;
  }
  .demo-text h2 em { color: var(--gold); font-style: italic; }
  .demo-text p {
    color: var(--text-dim);
    font-size: 16px;
    line-height: 1.7;
    margin-bottom: 24px;
  }

  .feature-list {
    list-style: none;
  }
  .feature-list li {
    padding: 12px 0;
    font-size: 15px;
    color: var(--text);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: flex-start;
    gap: 12px;
  }
  .feature-list li::before {
    content: '✓';
    color: var(--gold);
    font-weight: 700;
    flex-shrink: 0;
  }

  /* Phone */
  .phone-wrap {
    display: flex;
    justify-content: center;
    perspective: 1500px;
  }

  .phone {
    width: 280px;
    height: 580px;
    background: #000;
    border: 8px solid #1a1a1a;
    border-radius: 44px;
    padding: 16px 12px;
    position: relative;
    box-shadow:
      0 0 0 1px rgba(255,255,255,0.05),
      0 30px 80px rgba(0,0,0,0.6),
      0 0 80px rgba(255,210,100,0.08);
    transform: rotateY(-8deg) rotateX(4deg);
    transition: transform 0.5s;
  }
  .phone:hover { transform: rotateY(0) rotateX(0); }

  .phone-notch {
    position: absolute;
    top: 12px;
    left: 50%;
    transform: translateX(-50%);
    width: 100px;
    height: 22px;
    background: #000;
    border-radius: 12px;
    z-index: 2;
  }

  .phone-screen {
    width: 100%;
    height: 100%;
    background: var(--bg);
    border-radius: 32px;
    padding: 38px 14px 14px;
    overflow: hidden;
    position: relative;
  }

  .phone-time {
    font-size: 10px;
    color: var(--text-dim);
    text-align: center;
    margin-bottom: 12px;
    letter-spacing: 0.1em;
  }

  .phone-header {
    text-align: center;
    margin-bottom: 16px;
  }
  .phone-brand {
    font-family: 'Lora', serif;
    font-style: italic;
    font-size: 14px;
    margin-bottom: 12px;
  }
  .phone-brand span { color: var(--gold); }

  .phone-ring {
    width: 60px;
    height: 60px;
    margin: 0 auto;
    position: relative;
  }
  .phone-ring-text {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Lora', serif;
    font-weight: 600;
    color: var(--gold);
    font-size: 16px;
  }

  .phone-meal {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 10px 12px;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 11px;
    position: relative;
    overflow: hidden;
  }
  .phone-meal.fiel { border-color: rgba(74,222,128,0.3); background: linear-gradient(90deg, rgba(74,222,128,0.1), var(--bg-card)); }
  .phone-meal.fiel::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 2px; background: var(--green); }

  .phone-meal-icon {
    width: 28px;
    height: 28px;
    background: var(--bg-elevated);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
  }
  .phone-meal-body { flex: 1; min-width: 0; }
  .phone-meal-time {
    font-size: 8px;
    color: var(--text-faint);
    letter-spacing: 0.1em;
    text-transform: uppercase;
    font-weight: 600;
  }
  .phone-meal-name {
    font-family: 'Lora', serif;
    font-weight: 600;
    font-size: 12px;
    line-height: 1.2;
  }
  .phone-meal-check {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 1.5px solid var(--border-strong);
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #000;
    font-size: 10px;
    font-weight: 700;
  }
  .phone-meal.fiel .phone-meal-check { background: var(--green); border-color: var(--green); }

  /* === PRICING === */
  .pricing {
    padding: 100px 0;
  }

  .price-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    max-width: 800px;
    margin: 0 auto;
  }
  @media (max-width: 700px) {
    .price-grid { grid-template-columns: 1fr; }
  }

  .price-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 36px 32px;
    transition: all 0.3s;
    position: relative;
  }

  .price-card.featured {
    border-color: var(--gold);
    background: linear-gradient(180deg, rgba(255,210,100,0.05), var(--bg-card));
    transform: scale(1.02);
  }

  .price-badge {
    position: absolute;
    top: -12px;
    right: 24px;
    background: var(--gold);
    color: #000;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.15em;
    text-transform: uppercase;
  }

  .price-name {
    font-family: 'Lora', serif;
    font-weight: 600;
    font-size: 20px;
    margin-bottom: 4px;
  }
  .price-tagline {
    color: var(--text-dim);
    font-size: 13px;
    margin-bottom: 24px;
  }
  .price-amount {
    display: flex;
    align-items: baseline;
    gap: 6px;
    margin-bottom: 28px;
  }
  .price-amount-num {
    font-family: 'Lora', serif;
    font-weight: 600;
    font-size: 56px;
    line-height: 1;
  }
  .price-amount-num.featured-num { color: var(--gold); }
  .price-amount-period {
    color: var(--text-dim);
    font-size: 14px;
  }
  .price-amount-yearly {
    font-size: 12px;
    color: var(--gold);
    margin-top: 4px;
  }

  .price-features {
    list-style: none;
    margin-bottom: 28px;
  }
  .price-features li {
    padding: 8px 0;
    font-size: 14px;
    color: var(--text-dim);
    display: flex;
    align-items: flex-start;
    gap: 10px;
  }
  .price-features li::before {
    content: '·';
    color: var(--gold);
    font-weight: 700;
    font-size: 18px;
    line-height: 1;
  }

  .price-cta {
    display: block;
    text-align: center;
    padding: 14px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s;
    border: 1px solid var(--border-strong);
    color: var(--text);
  }
  .price-cta:hover { background: var(--bg-elevated); }
  .price-cta.featured-cta {
    background: var(--gold);
    color: #000;
    border-color: var(--gold);
  }
  .price-cta.featured-cta:hover {
    background: #ffe9a8;
    transform: translateY(-1px);
  }

  /* === AFFILIATE === */
  .affiliate {
    padding: 100px 0;
    background: var(--bg-elevated);
    border-top: 1px solid var(--border);
  }
  .affiliate-inner {
    max-width: 720px;
    margin: 0 auto;
    text-align: center;
  }
  .affiliate h2 {
    font-family: 'Lora', serif;
    font-weight: 600;
    font-size: clamp(28px, 4vw, 40px);
    margin-bottom: 16px;
    line-height: 1.2;
  }
  .affiliate h2 em { color: var(--gold); font-style: italic; }
  .affiliate p {
    color: var(--text-dim);
    font-size: 16px;
    max-width: 560px;
    margin: 0 auto 32px;
    line-height: 1.6;
  }
  .affiliate-stat {
    display: inline-flex;
    align-items: baseline;
    gap: 10px;
    background: var(--bg-card);
    border: 1px solid var(--border-strong);
    border-radius: 999px;
    padding: 12px 24px;
    margin-bottom: 32px;
  }
  .affiliate-stat-num {
    font-family: 'Lora', serif;
    font-weight: 600;
    font-size: 28px;
    color: var(--gold);
  }
  .affiliate-stat-label {
    color: var(--text-dim);
    font-size: 13px;
  }

  /* === FAQ === */
  .faq {
    padding: 100px 0;
  }
  .faq-list {
    max-width: 720px;
    margin: 0 auto;
  }
  .faq-item {
    border-bottom: 1px solid var(--border);
  }
  .faq-q {
    width: 100%;
    background: none;
    border: none;
    color: var(--text);
    font-family: 'Lora', serif;
    font-weight: 600;
    font-size: 18px;
    text-align: left;
    padding: 24px 0;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
  }
  .faq-q-icon {
    color: var(--gold);
    font-size: 20px;
    flex-shrink: 0;
    transition: transform 0.3s;
  }
  .faq-item.open .faq-q-icon { transform: rotate(45deg); }
  .faq-a {
    color: var(--text-dim);
    font-size: 15px;
    line-height: 1.6;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s, padding 0.3s;
  }
  .faq-item.open .faq-a {
    max-height: 300px;
    padding-bottom: 24px;
  }

  /* === FINAL CTA === */
  .final-cta {
    padding: 120px 0;
    text-align: center;
    position: relative;
    overflow: hidden;
  }
  .final-cta::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at center, rgba(255,210,100,0.08), transparent 60%);
    pointer-events: none;
  }
  .final-cta-content { position: relative; z-index: 2; }
  .final-cta h2 {
    font-family: 'Lora', serif;
    font-weight: 600;
    font-size: clamp(36px, 5vw, 56px);
    line-height: 1.1;
    margin-bottom: 24px;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
  }
  .final-cta h2 em { color: var(--gold); font-style: italic; }
  .final-cta p {
    color: var(--text-dim);
    font-size: 17px;
    margin-bottom: 36px;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
  }

  /* === FOOTER === */
  footer {
    padding: 40px 0 60px;
    border-top: 1px solid var(--border);
    text-align: center;
  }
  .footer-brand {
    font-family: 'Lora', serif;
    font-style: italic;
    font-weight: 600;
    margin-bottom: 12px;
  }
  .footer-brand span { color: var(--gold); }
  .footer-meta {
    font-size: 12px;
    color: var(--text-faint);
    margin-bottom: 8px;
  }
  .footer-meta a { color: var(--text-dim); text-decoration: none; }
  .footer-meta a:hover { color: var(--gold); }

  .footer-quote {
    font-family: 'Lora', serif;
    font-style: italic;
    color: var(--text-faint);
    font-size: 13px;
    margin-top: 16px;
  }

  /* Reveal on scroll */
  .reveal {
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.8s, transform 0.8s;
  }
  .reveal.visible {
    opacity: 1;
    transform: translateY(0);
  }
</style>
</head>
<body>

<nav>
  <div class="nav-inner">
    <div class="nav-brand">Perfecta<span>MENTE</span> Coach</div>
    <div style="display:flex; align-items:center; gap:18px;">
      <a href="{{ route('login') }}" style="color: var(--text-dim); text-decoration:none; font-size:13px; font-weight:500;">Entrar</a>
      <a href="#empezar" class="nav-cta">Empezar gratis</a>
    </div>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="container">
    <div class="hero-content">
      <div class="eyebrow">
        <span class="eyebrow-dot"></span>
        Ahora con IA
      </div>
      <h1>Su nutricionista le dio un plan.<br><em>Nosotros lo hacemos cumplirlo.</em></h1>
      <p class="hero-sub">Suba el PDF de su plan. La IA lo lee, lo organiza y le entrega un tablero diario donde solo tiene que hacer check. Fidelidad sin pensar.</p>
      <a href="#empezar" class="hero-cta">
        Subir mi plan
        <span class="hero-cta-arrow">→</span>
      </a>
      <div class="hero-trust">DISEÑADA POR <span>@CHICHOQV</span> — CROSSFIT GAMES CHAMPION 2024</div>
    </div>
  </div>
</section>

<!-- PAIN -->
<section class="pain">
  <div class="container">
    <div class="pain-inner">
      <p class="pain-quote">"Su plan no falla porque sea malo. Falla porque <span>el PDF se perdió</span> en su WhatsApp hace tres semanas."</p>
      <p class="pain-author">— Chicho Q.</p>
    </div>
  </div>
</section>

<!-- HOW -->
<section class="how">
  <div class="container">
    <p class="section-eyebrow reveal">Tres pasos</p>
    <h2 class="section-title reveal">De PDF olvidado a <em>tablero vivo</em>.</h2>
    <div class="steps">
      <div class="step reveal">
        <div class="step-num">01</div>
        <h3 class="step-title">Suba su plan</h3>
        <p class="step-desc">PDF, foto del documento o texto pegado. Lo que le mandó su nutricionista, su entrenador o su médico.</p>
      </div>
      <div class="step reveal">
        <div class="step-num">02</div>
        <h3 class="step-title">La IA lo organiza</h3>
        <p class="step-desc">Detecta sus comidas, horarios, opciones, suplementos e hidratación. En 30 segundos su tablero está listo.</p>
      </div>
      <div class="step reveal">
        <div class="step-num">03</div>
        <h3 class="step-title">Solo haga check</h3>
        <p class="step-desc">Cada comida, cada vaso de agua, cada suplemento. Fiel, parcial o no fiel. Su racha empieza hoy.</p>
      </div>
    </div>
  </div>
</section>

<!-- DEMO -->
<section class="demo-section">
  <div class="container">
    <div class="demo-grid">
      <div class="demo-text reveal">
        <p class="section-eyebrow" style="text-align:left;">El tablero</p>
        <h2>Su día completo,<br><em>en una pantalla.</em></h2>
        <p>Abra la app. Vea sus comidas con sus horarios. Toque la que acaba de hacer. Vea cómo sube su % de fidelidad en tiempo real. Eso es todo.</p>
        <ul class="feature-list">
          <li>Checks dinámicos extraídos de su plan real</li>
          <li>Tres estados: fiel · parcial · no fiel</li>
          <li>Anillo de fidelidad diario en oro</li>
          <li>Notas opcionales en cada comida</li>
          <li>Racha visual estilo calendario</li>
          <li>Análisis con IA cada semana</li>
        </ul>
      </div>
      <div class="phone-wrap reveal">
        <div class="phone">
          <div class="phone-notch"></div>
          <div class="phone-screen">
            <div class="phone-time">9:41</div>
            <div class="phone-header">
              <div class="phone-brand">Perfecta<span>MENTE</span></div>
              <div class="phone-ring">
                <svg width="60" height="60" style="transform: rotate(-90deg);">
                  <circle cx="30" cy="30" r="25" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="5"/>
                  <circle cx="30" cy="30" r="25" fill="none" stroke="#FFD264" stroke-width="5" stroke-linecap="round" stroke-dasharray="157" stroke-dashoffset="55" style="filter: drop-shadow(0 0 6px rgba(255,210,100,0.4));"/>
                </svg>
                <div class="phone-ring-text">65%</div>
              </div>
            </div>
            <div class="phone-meal fiel">
              <div class="phone-meal-icon">🌅</div>
              <div class="phone-meal-body">
                <div class="phone-meal-time">6:30 AM</div>
                <div class="phone-meal-name">Al despertar</div>
              </div>
              <div class="phone-meal-check">✓</div>
            </div>
            <div class="phone-meal fiel">
              <div class="phone-meal-icon">🍳</div>
              <div class="phone-meal-body">
                <div class="phone-meal-time">8:30 AM</div>
                <div class="phone-meal-name">Desayuno</div>
              </div>
              <div class="phone-meal-check">✓</div>
            </div>
            <div class="phone-meal fiel">
              <div class="phone-meal-icon">🥤</div>
              <div class="phone-meal-body">
                <div class="phone-meal-time">10:00 AM</div>
                <div class="phone-meal-name">Merienda</div>
              </div>
              <div class="phone-meal-check">✓</div>
            </div>
            <div class="phone-meal">
              <div class="phone-meal-icon">🍖</div>
              <div class="phone-meal-body">
                <div class="phone-meal-time">12:00 PM</div>
                <div class="phone-meal-name">Almuerzo</div>
              </div>
              <div class="phone-meal-check"></div>
            </div>
            <div class="phone-meal">
              <div class="phone-meal-icon">🥪</div>
              <div class="phone-meal-body">
                <div class="phone-meal-time">3:00 PM</div>
                <div class="phone-meal-name">Merienda</div>
              </div>
              <div class="phone-meal-check"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- PRICING -->
<section class="pricing" id="empezar">
  <div class="container">
    <p class="section-eyebrow reveal">Precios</p>
    <h2 class="section-title reveal">Empiece gratis. <em>Crezca cuando esté listo.</em></h2>
    <div class="price-grid">
      <div class="price-card reveal">
        <div class="price-name">Free</div>
        <div class="price-tagline">Para probarlo</div>
        <div class="price-amount">
          <span class="price-amount-num">$0</span>
          <span class="price-amount-period">/ siempre</span>
        </div>
        <ul class="price-features">
          <li>1 plan activo</li>
          <li>Checks ilimitados al día</li>
          <li>7 días de historial</li>
          <li>Anillo de fidelidad diario</li>
          <li>Subida de plan con IA</li>
        </ul>
        <a href="{{ route('register') }}" class="price-cta">Empezar gratis</a>
      </div>

      <div class="price-card featured reveal">
        <div class="price-badge">Más popular</div>
        <div class="price-name">Pro</div>
        <div class="price-tagline">Para ser implacable</div>
        <div class="price-amount">
          <span class="price-amount-num featured-num">$7</span>
          <span class="price-amount-period">/ mes</span>
        </div>
        <div class="price-amount-yearly">o $59/año — ahorre $25</div>
        <ul class="price-features">
          <li>Todo lo del Free, sin límites</li>
          <li>Historial e ilimitada racha</li>
          <li>Análisis IA semanal personalizado</li>
          <li>Múltiples planes activos</li>
          <li>Reportes exportables (PDF)</li>
          <li>Acceso a herramientas PerfectaMENTE diarias</li>
          <li>Soporte directo</li>
        </ul>
        <a href="{{ route('register') }}" class="price-cta featured-cta">Empezar 7 días gratis</a>
      </div>
    </div>
  </div>
</section>

<!-- AFFILIATE -->
<section class="affiliate">
  <div class="container">
    <div class="affiliate-inner reveal">
      <p class="section-eyebrow">Para nutricionistas y coaches</p>
      <h2>Sus clientes cumplen más.<br><em>Usted gana más.</em></h2>
      <div class="affiliate-stat">
        <span class="affiliate-stat-num">30%</span>
        <span class="affiliate-stat-label">de comisión recurrente</span>
      </div>
      <p>Recomiende la app a sus clientes con su link único. Cada vez que uno paga Pro, usted gana mes a mes. Ellos cumplen mejor su plan, usted recibe ingresos pasivos. Sin papeleo, sin dashboards complicados.</p>
      <a href="{{ route('register') }}?role=coach" class="hero-cta">Solicitar mi link de coach <span class="hero-cta-arrow">→</span></a>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="faq">
  <div class="container">
    <p class="section-eyebrow reveal">Dudas comunes</p>
    <h2 class="section-title reveal">Lo que toda la gente <em>pregunta</em>.</h2>
    <div class="faq-list">
      <div class="faq-item">
        <button class="faq-q">¿Funciona con cualquier plan? <span class="faq-q-icon">+</span></button>
        <div class="faq-a">Sí. Si su plan está en PDF, foto o texto, la IA lo entiende. Funciona con planes de nutricionistas, médicos deportólogos, entrenadores, dietas keto, ayuno intermitente, planes vegetarianos. Lo único que necesitamos es que su plan tenga al menos comidas con horarios.</div>
      </div>
      <div class="faq-item">
        <button class="faq-q">¿Y si la IA se equivoca al leer mi plan? <span class="faq-q-icon">+</span></button>
        <div class="faq-a">Antes de empezar le mostramos exactamente qué leyó: paciente, comidas, horarios, suplementos. Usted lo revisa y puede editar lo que sea. Solo cuando confirma, empieza el reto.</div>
      </div>
      <div class="faq-item">
        <button class="faq-q">¿Qué pasa con suplementos hormonales o medicamentos? <span class="faq-q-icon">+</span></button>
        <div class="faq-a">Los detectamos automáticamente y los excluimos del tablero. Eso debe manejarlo con su médico, no con una app de tracking. La app es para nutrición, hidratación y suplementación nutricional, no para sustituir prescripciones médicas.</div>
      </div>
      <div class="faq-item">
        <button class="faq-q">¿Puedo cancelar cuando quiera? <span class="faq-q-icon">+</span></button>
        <div class="faq-a">Sí, sin preguntas. Cancela desde la app y mantiene el acceso hasta que termina el período pagado. Sus datos quedan guardados por si decide volver.</div>
      </div>
      <div class="faq-item">
        <button class="faq-q">¿Por qué confiar? <span class="faq-q-icon">+</span></button>
        <div class="faq-a">PerfectaMENTE Coach es de Chicho Quesada — Campeón Mundial CrossFit Games 2024 (40-44) y desarrollador del sistema PerfectaMENTE de entrenamiento mental. La app integra disciplina nutricional con la metodología que él mismo usa para competir al más alto nivel.</div>
      </div>
    </div>
  </div>
</section>

<!-- FINAL CTA -->
<section class="final-cta">
  <div class="container">
    <div class="final-cta-content reveal">
      <h2>Hoy es buen día para <em>empezar a ser fiel</em>.</h2>
      <p>Suba su plan. En 30 segundos su tablero está listo. Su racha empieza hoy.</p>
      <a href="{{ route('register') }}" class="hero-cta">Subir mi plan ahora <span class="hero-cta-arrow">→</span></a>
    </div>
  </div>
</section>

<footer>
  <div class="container">
    <div class="footer-brand">Perfecta<span>MENTE</span> Coach</div>
    <p class="footer-meta">© 2026 · <a href="#">Términos</a> · <a href="#">Privacidad</a> · <a href="#">Contacto</a></p>
    <p class="footer-quote">"La fidelidad diaria es lo que separa a los campeones del resto." — @chichoqv</p>
  </div>
</footer>

<script>
  // FAQ toggle
  document.querySelectorAll('.faq-q').forEach(q => {
    q.addEventListener('click', () => {
      q.parentElement.classList.toggle('open');
    });
  });

  // Reveal on scroll
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('visible');
        observer.unobserve(e.target);
      }
    });
  }, { threshold: 0.1 });
  document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
</script>

</body>
</html>
