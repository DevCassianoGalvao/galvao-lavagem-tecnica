<?php
require_once __DIR__ . '/../../core/bootstrap.php';
$landingSettings = [];
try {
    $landingSettings = (new MvpService(Connection::get($config)))->settings();
} catch (Throwable) {
    $landingSettings = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Galvão Lavagem Técnica · Revitalização Visual de Áreas Externas em Nova Friburgo</title>
<meta name="description" content="Lavagem cuidadosa de áreas externas em Nova Friburgo, com equipamentos profissionais, manutenção preventiva e revitalização visual de pisos, muros, fachadas, decks e bordas de piscina.">
<meta name="keywords" content="lavagem técnica Nova Friburgo, revitalização visual de áreas externas, lavagem de alta pressão, remoção de lodo e musgo, manutenção preventiva, limpeza cuidadosa de pisos externos">
<meta name="robots" content="index, follow, max-image-preview:large">
<meta name="theme-color" content="#090909">
<meta property="og:type" content="website">
<meta property="og:locale" content="pt_BR">
<meta property="og:title" content="Galvão Lavagem Técnica · Revitalização Visual em Nova Friburgo">
<meta property="og:description" content="Lavagem cuidadosa para conservar, valorizar e melhorar a aparência de áreas externas em Nova Friburgo e região.">
<meta property="og:image" content="../assets/images/logo-galvao.png">
<link rel="canonical" href="http://localhost/public/landing/">
<link rel="icon" type="image/png" href="../assets/images/logo-galvao.png">
<link rel="shortcut icon" type="image/png" href="../assets/images/logo-galvao.png">
<link rel="apple-touch-icon" href="../assets/images/logo-galvao.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,700;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "Galvão Lavagem Técnica",
  "description": "Lavagem cuidadosa de áreas externas em Nova Friburgo, com equipamentos profissionais, manutenção preventiva e revitalização visual de pisos, muros, fachadas, decks e bordas de piscina.",
  "areaServed": {
    "@type": "City",
    "name": "Nova Friburgo"
  },
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "Nova Friburgo",
    "addressRegion": "RJ",
    "addressCountry": "BR"
  },
  "knowsAbout": [
    "lavagem técnica",
    "lavagem de alta pressão",
    "revitalização de áreas externas",
    "remoção de lodo",
    "remoção de musgo"
  ]
}
</script>
<?= $landingSettings['meta_pixel'] ?? ''; ?>
<?= $landingSettings['google_analytics'] ?? ''; ?>
<?= $landingSettings['gtm'] ?? ''; ?>
<?= $landingSettings['custom_head'] ?? ''; ?>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --gold: #C9A84C;
  --gold-light: #E8C96A;
  --gold-dim: rgba(201,168,76,0.12);
  --gold-border: rgba(201,168,76,0.25);
  --black: #090909;
  --surface: #111111;
  --surface2: #181818;
  --surface3: #222222;
  --border: rgba(255,250,240,0.11);
  --text: #FFFAF0;
  --text-muted: #D5CEC2;
  --text-dim: #9B9286;
  --radius: 14px;
  --container: 1100px;
  --ease: cubic-bezier(0.4,0,0.2,1);
}

html { scroll-behavior: smooth; font-size: 16px; }

body {
  background: var(--black);
  color: var(--text);
  font-family: 'DM Sans', sans-serif;
  font-size: 16px;
  line-height: 1.55;
  overflow-x: hidden;
  -webkit-font-smoothing: antialiased;
}

/* Noise overlay */
body::after {
  content: '';
  position: fixed;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
  pointer-events: none;
  z-index: 9999;
  opacity: 0.5;
}

/* ─── NAV ─────────────────────────────────── */
nav {
  position: fixed;
  top: 0; left: 0; right: 0;
  z-index: 100;
  padding: 16px max(24px, calc((100vw - var(--container)) / 2));
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: linear-gradient(to bottom, rgba(9,9,9,0.95) 0%, transparent 100%);
  backdrop-filter: blur(0px);
  transition: backdrop-filter 0.3s var(--ease), background 0.3s var(--ease);
}

nav.scrolled {
  background: rgba(9,9,9,0.92);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--border);
}

.nav-brand {
  display: flex;
  align-items: center;
  text-decoration: none;
}

.nav-mark {
  width: 76px; height: 56px;
  border: 0;
  border-radius: 0;
  display: flex; align-items: center; justify-content: center;
  overflow: visible;
}

.nav-mark svg { width: 15px; height: 15px; }

.nav-logo {
  width: 100%;
  height: 100%;
  object-fit: contain;
  padding: 0;
  filter: drop-shadow(0 6px 12px rgba(201,168,76,0.18));
}

.nav-name {
  display: none;
}

.nav-links {
  display: flex;
  align-items: center;
  gap: 48px;
  list-style: none;
  margin-left: auto;
}

.nav-links a {
  color: var(--text-muted);
  font-size: 16px;
  text-decoration: none;
  letter-spacing: 0.04em;
  transition: color 0.2s;
}

.nav-links a:hover { color: var(--text); }

.nav-toggle {
  display: none;
  width: 42px;
  height: 42px;
  border: 1px solid rgba(255,250,240,0.12);
  border-radius: 50%;
  background: rgba(255,255,255,0.035);
  color: var(--text);
  cursor: pointer;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 5px;
  margin-left: auto;
}

.nav-toggle span {
  width: 17px;
  height: 1.5px;
  border-radius: 99px;
  background: currentColor;
  transition: transform 0.2s var(--ease), opacity 0.2s var(--ease);
}

.nav-toggle[aria-expanded="true"] span:nth-child(1) { transform: translateY(6.5px) rotate(45deg); }
.nav-toggle[aria-expanded="true"] span:nth-child(2) { opacity: 0; }
.nav-toggle[aria-expanded="true"] span:nth-child(3) { transform: translateY(-6.5px) rotate(-45deg); }

/* ─── HERO ─────────────────────────────────── */
.hero {
  min-height: 100svh;
  display: flex;
  align-items: center;
  padding: 144px 40px 108px;
  position: relative;
  overflow: hidden;
}

.hero-bg {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse 60% 50% at 70% 50%, rgba(201,168,76,0.06) 0%, transparent 70%),
    radial-gradient(ellipse 40% 60% at 20% 80%, rgba(201,168,76,0.04) 0%, transparent 60%);
}

.hero-grid-line {
  position: absolute;
  top: 0; bottom: 0;
  width: 1px;
  background: rgba(255,250,240,0.014);
}

.hero-grid-line:nth-child(1) { left: 20%; }
.hero-grid-line:nth-child(2) { left: 40%; }
.hero-grid-line:nth-child(3) { left: 60%; }
.hero-grid-line:nth-child(4) { left: 80%; }

.hero-inner {
  max-width: var(--container);
  margin: 0 auto;
  width: 100%;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 80px;
  align-items: center;
  position: relative;
  z-index: 1;
}

.hero-tag {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 500;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--gold);
  margin-bottom: 24px;
}

.hero-tag::before {
  content: '';
  width: 24px; height: 1px;
  background: var(--gold);
  opacity: 0.6;
}

.hero-h1 {
  font-family: 'Playfair Display', serif;
  font-size: clamp(34px, 4vw, 48px);
  font-weight: 500;
  line-height: 1.1;
  color: var(--text);
  margin-bottom: 20px;
}

.hero-h1 em {
  font-style: italic;
  color: var(--gold);
}

.hero-sub {
  font-size: 16px;
  color: var(--text-muted);
  line-height: 1.7;
  margin-bottom: 40px;
  max-width: 420px;
  font-weight: 300;
}

.hero-actions {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
  margin-bottom: 0;
}

.btn-primary {
  background: var(--gold);
  border: none;
  border-radius: 99px;
  color: var(--black);
  cursor: pointer;
  font-family: 'DM Sans', sans-serif;
  font-size: 16px;
  font-weight: 500;
  padding: 16px 32px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: background 0.2s, transform 0.15s;
  text-decoration: none;
}

.btn-primary:hover { background: var(--gold-light); }
.btn-primary:active { transform: scale(0.98); }
.btn-primary:disabled {
  cursor: wait;
  opacity: 0.78;
  transform: none;
}

.btn-ghost {
  background: transparent;
  border: 1px solid var(--border);
  border-radius: 99px;
  color: var(--text-muted);
  cursor: pointer;
  font-family: 'DM Sans', sans-serif;
  font-size: 16px;
  padding: 15px 28px;
  transition: border-color 0.2s, color 0.2s;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.btn-ghost:hover { border-color: rgba(255,255,255,0.2); color: var(--text); }

/* Hero before/after carousel */
.hero-visual {
  position: relative;
  z-index: 0;
  transform: translateX(8px);
}

.hero-carousel {
  background:
    linear-gradient(180deg, rgba(255,255,255,0.055), rgba(255,255,255,0.018)),
    var(--surface);
  border: 1px solid rgba(255,250,240,0.12);
  border-radius: 24px;
  overflow: hidden;
  position: relative;
  box-shadow: 0 32px 90px rgba(0,0,0,0.30);
}

.hero-carousel-track {
  display: flex;
  transition: transform 0.75s var(--ease);
}

.hero-slide {
  min-width: 100%;
  display: grid;
  grid-template-columns: 1fr 1fr;
  aspect-ratio: 1.68 / 1;
}

.hero-compare-pane {
  position: relative;
  overflow: hidden;
}

.hero-compare-pane + .hero-compare-pane {
  border-left: 1px solid var(--border);
}

.hero-compare-pane img {
  width: 100%;
  height: 110%;
  display: block;
  object-fit: cover;
  object-position: center 42%;
  transform: translateY(-3%);
  filter: saturate(0.96) contrast(1.03);
}

.hero-compare-pane::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(0,0,0,0.10), transparent 36%, rgba(0,0,0,0.20));
  pointer-events: none;
}

.hero-compare-label {
  position: absolute;
  top: 16px;
  left: 16px;
  z-index: 2;
  padding: 6px 11px;
  border-radius: 99px;
  background: linear-gradient(135deg, var(--gold), var(--gold-light));
  border: 1px solid rgba(255,255,255,0.18);
  backdrop-filter: blur(10px);
  color: var(--black);
  font-size: 14px;
  font-weight: 500;
  letter-spacing: 0.10em;
  text-transform: uppercase;
}

.hero-compare-label.after {
  background: linear-gradient(135deg, var(--gold), var(--gold-light));
  border-color: rgba(255,255,255,0.18);
  color: var(--black);
}

.hero-carousel-dots {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 14px;
  z-index: 3;
  display: flex;
  justify-content: center;
  gap: 7px;
}

.hero-carousel-arrow {
  position: absolute;
  top: 50%;
  z-index: 4;
  width: 42px;
  height: 42px;
  border: 1px solid rgba(255,255,255,0.18);
  border-radius: 50%;
  background: linear-gradient(135deg, var(--gold), var(--gold-light));
  color: var(--black);
  display: grid;
  place-items: center;
  cursor: pointer;
  transform: translateY(-50%);
  box-shadow: 0 18px 40px rgba(0,0,0,0.32);
  transition: background 0.2s var(--ease), box-shadow 0.2s var(--ease);
}

.hero-carousel-arrow:hover {
  background: var(--gold-light);
  box-shadow: 0 18px 40px rgba(0,0,0,0.32);
}

.hero-carousel-arrow.prev { left: 14px; }
.hero-carousel-arrow.next { right: 14px; }

.hero-carousel-arrow svg {
  width: 19px;
  height: 19px;
  fill: none;
  stroke: currentColor;
  stroke-width: 2.4;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.hero-carousel-dot {
  width: 6px;
  height: 6px;
  border: 0;
  border-radius: 50%;
  background: rgba(255,250,240,0.28);
  padding: 0;
  cursor: pointer;
  transition: width 0.25s var(--ease), background 0.25s var(--ease);
}

.hero-carousel-dot.active {
  width: 22px;
  border-radius: 999px;
  background: var(--gold);
}

.hero-visual-meta {
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  flex-direction: column;
  text-align: center;
}

.hero-region-pill {
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 999px;
  padding: 9px 13px;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 8px;
  white-space: nowrap;
  color: var(--text-muted);
}

.hero-ai-note {
  margin-top: 10px;
  color: var(--text-dim);
  font-size: 14px;
  line-height: 1.5;
  font-weight: 300;
  text-align: center;
}

/* ─── SECTION BASE ───────────────────────── */
section { padding: 128px 40px; }

.inner { max-width: var(--container); margin: 0 auto; }

.section-label {
  font-size: 16px;
  font-weight: 500;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--gold);
  margin-bottom: 14px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.section-label::before {
  content: '';
  width: 20px; height: 1px;
  background: var(--gold);
  opacity: 0.5;
}

.section-title {
  font-family: 'Playfair Display', serif;
  font-size: clamp(28px, 3.5vw, 44px);
  font-weight: 500;
  line-height: 1.15;
  color: var(--text);
  margin-bottom: 16px;
}

.section-sub {
  font-size: 16px;
  color: var(--text-muted);
  line-height: 1.62;
  font-weight: 300;
  max-width: 900px;
}

#processo .section-sub {
  max-width: 900px;
}

.icon-outline {
  width: 28px;
  height: 28px;
  color: var(--gold);
}

.icon-outline svg {
  width: 100%;
  height: 100%;
  fill: none;
  stroke: currentColor;
  stroke-width: 1.75;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.ba-surface-icon.icon-outline {
  width: 58px;
  height: 58px;
  opacity: 0.22;
}

.safety-box {
  grid-column: 1 / -1;
  width: 100%;
  margin-top: 12px;
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 22px;
  align-items: flex-start;
  background: linear-gradient(135deg, rgba(201,168,76,0.12), rgba(255,255,255,0.025));
  border: 1px solid var(--gold-border);
  border-radius: 20px;
  padding: clamp(26px, 4vw, 38px);
  box-shadow: 0 24px 70px rgba(0,0,0,0.24);
}

.safety-box .icon-outline {
  width: 40px;
  height: 40px;
}

.safety-title {
  font-family: 'Playfair Display', serif;
  font-size: clamp(18px, 2vw, 24px);
  font-weight: 500;
  color: var(--text);
  margin-bottom: 8px;
}

.safety-text {
  color: var(--text-muted);
  font-size: 16px;
  line-height: 1.75;
  font-weight: 300;
  max-width: 860px;
}

/* ─── PROBLEMA ───────────────────────────── */
.problema { background: var(--surface); }

.problema-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 60px;
  align-items: center;
  margin-top: 60px;
}

.problema-cards {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.problema-card {
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 20px;
}

.problema-card-icon { margin-bottom: 10px; }

.problema-card-title {
  font-size: 16px;
  font-weight: 500;
  color: var(--text);
  margin-bottom: 6px;
}

.problema-card-body {
  font-size: 16px;
  color: var(--text-muted);
  line-height: 1.6;
  font-weight: 300;
}

.problema-text .section-sub { max-width: 100%; }

.alert-box {
  background: var(--gold-dim);
  border: 1px solid var(--gold-border);
  border-radius: var(--radius);
  padding: 20px;
  margin-top: 28px;
}

.alert-box p {
  font-size: 16px;
  color: var(--text-muted);
  line-height: 1.6;
}

.alert-box strong { color: var(--gold); font-weight: 500; }

/* ─── PROCESSO ───────────────────────────── */
.processo-steps {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 2px;
  margin-top: 60px;
  background: var(--border);
  border-radius: var(--radius);
  overflow: hidden;
}

.pstep {
  background: var(--surface);
  padding: 32px 24px;
  position: relative;
  transition: background 0.3s var(--ease);
}

.pstep:hover { background: var(--surface2); }

.pstep-num {
  font-family: 'Playfair Display', serif;
  font-size: 36px;
  color: var(--text-dim);
  line-height: 1;
  margin-bottom: 20px;
}

.pstep-title {
  font-size: 16px;
  font-weight: 500;
  color: var(--text);
  margin-bottom: 8px;
}

.pstep-body {
  font-size: 16px;
  color: var(--text-muted);
  line-height: 1.6;
  font-weight: 300;
}

.pstep-accent {
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 2px;
  background: var(--gold);
  opacity: 0;
  transition: opacity 0.3s;
}

.pstep:hover .pstep-accent { opacity: 1; }

/* ─── SUPERFÍCIES ────────────────────────── */
.surfaces { background: var(--surface); }

.surfaces-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 14px;
  margin-top: 60px;
}

.surface-item {
  min-height: 172px;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 24px 20px;
  transition: border-color 0.2s, background 0.2s;
}

.surface-item:hover {
  border-color: var(--gold-border);
  background: var(--surface3);
}

.surface-icon { margin-bottom: 12px; }

.surface-name {
  font-size: 16px;
  font-weight: 500;
  color: var(--text);
  margin-bottom: 4px;
}

.surface-note {
  font-size: 16px;
  color: var(--text-muted);
  font-weight: 300;
}

/* ─── DIFERENCIAIS ──────────────────────── */
.diff-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-top: 60px;
}

.diff-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 28px;
  display: flex;
  gap: 18px;
  align-items: flex-start;
  transition: border-color 0.2s;
}

.diff-card:hover { border-color: var(--gold-border); }

.diff-icon {
  width: 30px; height: 30px;
  background: transparent;
  border: 0;
  border-radius: 0;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  margin-top: 2px;
}

.diff-text-title {
  font-size: 16px;
  font-weight: 500;
  color: var(--text);
  margin-bottom: 6px;
}

.diff-text-body {
  font-size: 16px;
  color: var(--text-muted);
  line-height: 1.6;
  font-weight: 300;
}

/* ─── SIMULAÇÃO IA ──────────────────────── */
.ia-section { background: var(--surface); }

.ia-inner {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 80px;
  align-items: center;
}

.ia-upload-card {
  background:
    radial-gradient(circle at 50% 0%, rgba(201,168,76,0.10), transparent 42%),
    linear-gradient(180deg, rgba(255,255,255,0.045), rgba(255,255,255,0.018)),
    var(--surface2);
  border: 1px solid rgba(201,168,76,0.18);
  border-radius: 22px;
  overflow: hidden;
  box-shadow: 0 30px 90px rgba(0,0,0,0.28);
}

.ia-card-header {
  padding: 16px 20px;
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  gap: 8px;
}

.ia-card-title {
  font-size: 14px;
  font-weight: 500;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--text-muted);
}

.ia-dots {
  display: flex;
  gap: 5px;
  margin-left: auto;
}

.ia-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  background: var(--text-dim);
}

.ia-dot.active { background: var(--gold); }

.ia-upload-zone {
  min-height: 250px;
  padding: 34px 28px;
  text-align: center;
  cursor: pointer;
  transition: background 0.2s, border-color 0.2s, transform 0.2s;
  border: 1.5px dashed rgba(201,168,76,0.32);
  margin: 22px;
  border-radius: 18px;
  display: grid;
  place-items: center;
  align-content: center;
  gap: 4px;
  position: relative;
  background: linear-gradient(145deg, rgba(201,168,76,0.085), rgba(255,255,255,0.025));
}

.ia-upload-zone:hover,
.ia-upload-zone.has-file {
  background: var(--surface3);
  border-color: var(--gold-border);
  transform: translateY(-2px);
}

.ia-upload-zone.is-hidden { display: none; }

.ia-file-input {
  position: absolute;
  width: 1px;
  height: 1px;
  opacity: 0;
  pointer-events: none;
}

.ia-upload-icon {
  width: 62px;
  height: 62px;
  margin: 0 auto 16px;
  color: #15110a;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--gold), var(--gold-light));
  border: 1px solid rgba(255,255,255,0.18);
  box-shadow: 0 18px 42px rgba(201,168,76,0.22);
}

.ia-upload-icon .icon-outline {
  width: 29px;
  height: 29px;
  color: currentColor;
  stroke-width: 1.75;
}

.ia-upload-icon-img {
  width: 31px;
  height: 31px;
  display: block;
  filter: brightness(0) saturate(100%);
}

.ia-upload-primary {
  font-size: 16px;
  color: var(--text);
  font-weight: 500;
  margin-bottom: 6px;
}

.ia-upload-secondary {
  font-size: 16px;
  color: var(--text-muted);
  font-weight: 300;
}

.ia-upload-note {
  margin-top: 10px;
  padding: 7px 12px;
  border: 1px solid rgba(201,168,76,0.22);
  border-radius: 999px;
  background: rgba(201,168,76,0.08);
  color: var(--text-muted);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  max-width: 100%;
  color: var(--text-muted);
  font-size: 14px;
  line-height: 1.4;
  font-weight: 400;
}

.ia-upload-note-secondary {
  margin-top: 8px;
  color: var(--text-dim);
  font-size: 14px;
  line-height: 1.5;
  font-weight: 300;
}

.ia-preview {
  display: none;
  margin: 0 20px 20px;
  border: 1px solid var(--border);
  border-radius: 14px;
  overflow: hidden;
  background: rgba(255,255,255,0.025);
}

.ia-preview.visible { display: block; }

.ia-preview img {
  width: 100%;
  aspect-ratio: 16 / 9;
  object-fit: cover;
  display: block;
}

.ia-preview-meta {
  padding: 12px 14px;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  color: var(--text-muted);
  font-size: 14px;
}

.ia-state {
  display: none;
  margin: 0 20px 20px;
  min-height: 220px;
  padding: 28px;
  border: 1px solid rgba(201,168,76,0.35);
  border-radius: 18px;
  color: var(--text);
  font-size: 16px;
  line-height: 1.6;
  text-align: center;
  background:
    radial-gradient(circle at 50% 28%, rgba(201,168,76,0.20), transparent 40%),
    linear-gradient(145deg, rgba(201,168,76,0.12), rgba(255,255,255,0.026));
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.05), 0 22px 60px rgba(0,0,0,0.22);
}

.ia-state.visible {
  display: grid;
  align-items: center;
  justify-items: center;
  align-content: center;
  gap: 18px;
  min-height: 260px;
  margin: 0 20px 20px;
  padding: 26px 20px;
  border: 1px solid rgba(201,168,76,0.20);
  border-radius: 16px;
  background:
    radial-gradient(circle at 50% 20%, rgba(201,168,76,0.16), transparent 44%),
    linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.015));
}

.ia-spinner {
  position: relative;
  width: 66px;
  height: 66px;
  border: 2px solid rgba(201,168,76,0.16);
  border-top-color: var(--gold-light);
  border-right-color: var(--gold);
  border-radius: 50%;
  animation: spin 0.85s linear infinite;
  flex-shrink: 0;
  box-shadow: 0 0 34px rgba(201,168,76,0.22);
}

.ia-spinner::after {
  content: '';
  position: absolute;
  inset: 18px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(201,168,76,0.60), rgba(201,168,76,0.08));
  box-shadow: 0 0 20px rgba(201,168,76,0.34);
}

.ia-loading-copy {
  display: grid;
  gap: 8px;
  text-align: center;
  max-width: 340px;
}

.ia-loading-copy strong {
  color: var(--text);
  font-size: 16px;
  font-weight: 500;
}

.ia-loading-copy span {
  color: var(--text-muted);
  font-size: 16px;
  font-weight: 300;
}

.ia-simulation-result {
  display: none;
  margin: 0 20px 20px;
  padding: 16px;
  border: 1px solid var(--gold-border);
  border-radius: 14px;
  background: rgba(201,168,76,0.08);
  color: var(--text-muted);
  font-size: 16px;
  line-height: 1.6;
}

.ia-simulation-result.visible { display: block; }

.ia-simulation-result strong {
  display: block;
  color: var(--text);
  font-size: 16px;
  font-weight: 500;
  margin-bottom: 4px;
}

.ia-result-image {
  display: none;
  width: 100%;
  margin-top: 12px;
  border: 1px solid var(--border);
  border-radius: 12px;
  aspect-ratio: 16 / 9;
  object-fit: cover;
}

.ia-result-image.visible { display: block; }

.ia-action {
  margin: 0 20px 20px;
  width: calc(100% - 40px);
  justify-content: center;
  text-align: center;
}

.ia-text .section-sub { margin-bottom: 24px; }

.ia-steps-mini {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 28px;
}

.ia-step-mini {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.ia-step-num {
  width: 26px; height: 26px;
  background: var(--gold-dim);
  border: 1px solid var(--gold-border);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 14px;
  font-weight: 500;
  color: var(--gold);
  flex-shrink: 0;
  margin-top: 2px;
}

.ia-step-text {
  font-size: 16px;
  color: var(--text-muted);
  line-height: 1.5;
  font-weight: 300;
}

/* ─── CTA FINAL ──────────────────────────── */
.cta-section {
  background: var(--black);
  padding: 120px 40px;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.cta-glow {
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  width: 600px; height: 300px;
  background: radial-gradient(ellipse, rgba(201,168,76,0.08) 0%, transparent 70%);
  pointer-events: none;
}

.cta-section .section-label {
  justify-content: center;
}

.cta-section .section-label::before { display: none; }

.cta-section .section-title {
  max-width: 560px;
  margin: 0 auto 16px;
}

.cta-section .section-sub {
  max-width: 440px;
  margin: 0 auto 40px;
}

.cta-actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 14px;
  flex-wrap: wrap;
  margin-bottom: 40px;
}

.cta-note {
  font-size: 16px;
  color: var(--text-dim);
}

/* ─── FOOTER ─────────────────────────────── */
footer {
  border-top: 1px solid var(--border);
  padding: 32px max(24px, calc((100vw - var(--container)) / 2));
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px;
}

.footer-brand { font-size: 16px; color: var(--text-dim); }
.footer-brand strong { color: var(--text-muted); font-weight: 500; }
.footer-region {
  font-size: 16px;
  color: var(--text-dim);
  display: inline-flex;
  align-items: center;
  gap: 8px;
  line-height: 1;
}

.footer-region .icon-outline {
  width: 12px !important;
  height: 12px !important;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  line-height: 0;
  margin: 0 !important;
  flex-shrink: 0;
}

.footer-region .icon-outline svg {
  display: block;
}

/* ─── ANIMATIONS ─────────────────────────── */
.reveal {
  opacity: 0;
  transform: translateY(24px);
  transition: opacity 0.7s var(--ease), transform 0.7s var(--ease);
}

.reveal.visible {
  opacity: 1;
  transform: none;
}

.reveal-delay-1 { transition-delay: 0.1s; }
.reveal-delay-2 { transition-delay: 0.2s; }
.reveal-delay-3 { transition-delay: 0.3s; }
.reveal-delay-4 { transition-delay: 0.4s; }

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* ─── RESPONSIVE ─────────────────────────── */
@media (max-width: 900px) {
  nav {
    padding: 12px 24px;
    flex-wrap: nowrap;
  }
  .nav-mark { width: 66px; height: 50px; }
  .nav-toggle { display: inline-flex; }
  .nav-links {
    position: absolute;
    top: calc(100% + 8px);
    right: 24px;
    width: min(260px, calc(100vw - 48px));
    display: grid;
    gap: 4px;
    padding: 10px;
    border: 1px solid rgba(255,250,240,0.12);
    border-radius: 18px;
    background: rgba(12,12,12,0.96);
    box-shadow: 0 24px 70px rgba(0,0,0,0.35);
    backdrop-filter: blur(16px);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-8px);
    pointer-events: none;
    transition: opacity 0.2s var(--ease), transform 0.2s var(--ease), visibility 0.2s;
  }
  nav.menu-open .nav-links {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
    pointer-events: auto;
  }
  .nav-links a {
    display: block;
    padding: 12px 14px;
    border-radius: 12px;
    font-size: 16px;
  }
  .nav-links a:hover { background: rgba(255,255,255,0.045); }

  .hero { padding: 122px 24px 88px; }
  .hero-inner { grid-template-columns: 1fr; gap: 48px; }
  .hero-visual {
    display: block;
    transform: none;
    max-width: 520px;
  }
  .hero-visual-meta {
    align-items: center;
  }

  section { padding: 96px 24px; }

  .problema-grid { grid-template-columns: 1fr; gap: 36px; }
  .problema-cards { grid-template-columns: 1fr 1fr; }

  .processo-steps { grid-template-columns: 1fr 1fr; }
  .surfaces-grid { grid-template-columns: 1fr 1fr; }

  .diff-grid { grid-template-columns: 1fr; }

  .ia-inner { grid-template-columns: 1fr; gap: 40px; }

  footer { flex-direction: column; text-align: center; }
}

@media (max-width: 560px) {
  html { font-size: 16px; }
  body { font-size: 16px; }
  nav { padding: 10px 20px; }
  .nav-links {
    right: 20px;
    width: min(260px, calc(100vw - 40px));
  }
  .hero { padding: 118px 20px 80px; }
  .hero-actions { align-items: stretch; }
  .btn-primary,
  .btn-ghost {
    width: 100%;
    justify-content: center;
  }
  .hero-slide {
    grid-template-columns: 1fr;
    aspect-ratio: auto;
  }
  .hero-slide .hero-compare-pane:first-child {
    display: none;
  }
  .hero-compare-pane {
    min-height: 300px;
  }
  .hero-compare-pane + .hero-compare-pane {
    border-left: 0;
    border-top: 0;
  }
  .hero-compare-label {
    top: 12px;
    left: 12px;
  }
  .hero-ai-note { font-size: 14px; }
  .problema-cards { grid-template-columns: 1fr; }
  .processo-steps { grid-template-columns: 1fr; }
  .surfaces-grid { grid-template-columns: 1fr; }
  .safety-box { grid-template-columns: 1fr; }
  .ia-action {
    width: calc(100% - 40px);
    margin-inline: 20px;
    text-align: center;
  }
  section { padding: 88px 20px; }
}
</style>
</head>
<body>
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
  <symbol id="i-bricks" viewBox="0 0 24 24"><path d="M3 7h18"/><path d="M3 12h18"/><path d="M3 17h18"/><path d="M8 7v5"/><path d="M16 12v5"/></symbol>
  <symbol id="i-spark" viewBox="0 0 24 24"><path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z"/><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8L19 15Z"/></symbol>
  <symbol id="i-map" viewBox="0 0 24 24"><path d="M12 21s7-4.7 7-11a7 7 0 0 0-14 0c0 6.3 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></symbol>
  <symbol id="i-droplet" viewBox="0 0 24 24"><path d="M12 3s6 6.2 6 11a6 6 0 0 1-12 0c0-4.8 6-11 6-11Z"/><path d="M9.5 14.5c.7 1.6 2 2.5 4 2.5"/></symbol>
  <symbol id="i-eye" viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></symbol>
  <symbol id="i-alert" viewBox="0 0 24 24"><path d="M12 3 2.8 20h18.4L12 3Z"/><path d="M12 9v5"/><path d="M12 17h.01"/></symbol>
  <symbol id="i-car" viewBox="0 0 24 24"><path d="M3 13h18"/><path d="m5 13 2-5h10l2 5"/><path d="M7 17h.01"/><path d="M17 17h.01"/><path d="M5 13v5h14v-5"/></symbol>
  <symbol id="i-stone" viewBox="0 0 24 24"><path d="M5 17 9 7l4 10"/><path d="M13 17 16 9l3 8"/><path d="M3 17h18"/></symbol>
  <symbol id="i-wood" viewBox="0 0 24 24"><path d="M4 8h16"/><path d="M4 12h16"/><path d="M4 16h16"/><path d="M7 6v12"/><path d="M17 6v12"/></symbol>
  <symbol id="i-pool" viewBox="0 0 24 24"><path d="M4 14c2-2 4-2 6 0s4 2 6 0 4-2 6 0"/><path d="M4 18c2-2 4-2 6 0s4 2 6 0 4-2 6 0"/><path d="M9 10V5h6v5"/></symbol>
  <symbol id="i-gourmet" viewBox="0 0 24 24"><path d="M4 12h16"/><path d="M6 8h12"/><path d="M8 4h8"/><path d="M6 16h12"/><path d="M8 20h8"/></symbol>
  <symbol id="i-roof" viewBox="0 0 24 24"><path d="m3 12 9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></symbol>
  <symbol id="i-steps" viewBox="0 0 24 24"><path d="M4 20h16"/><path d="M6 16h12"/><path d="M8 12h8"/><path d="M10 8h4"/><path d="M12 4v4"/></symbol>
  <symbol id="i-flask" viewBox="0 0 24 24"><path d="M9 3h6"/><path d="M10 3v6l-5 8a3 3 0 0 0 2.6 4.5h8.8A3 3 0 0 0 19 17l-5-8V3"/><path d="M8 15h8"/></symbol>
  <symbol id="i-shield" viewBox="0 0 24 24"><path d="M12 3 4 6v6c0 5 3.4 8 8 9 4.6-1 8-4 8-9V6l-8-3Z"/><path d="M9 12l2 2 4-5"/></symbol>
  <symbol id="i-camera" viewBox="0 0 24 24"><path d="M4 8h3l2-3h6l2 3h3v11H4V8Z"/><circle cx="12" cy="14" r="3"/></symbol>
  <symbol id="i-repeat" viewBox="0 0 24 24"><path d="M17 2l4 4-4 4"/><path d="M3 11V9a3 3 0 0 1 3-3h15"/><path d="M7 22l-4-4 4-4"/><path d="M21 13v2a3 3 0 0 1-3 3H3"/></symbol>
  <symbol id="i-upload" viewBox="0 0 24 24"><path d="M4 16.5V19a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2.5"/><path d="M12 3v13"/><path d="m7 8 5-5 5 5"/></symbol>
</svg>

<!-- NAV -->
<nav id="nav">
  <a href="#" class="nav-brand">
    <div class="nav-mark">
      <img class="nav-logo" src="../assets/images/logo-galvao.png" alt="Galvão Lavagem Técnica">
    </div>
  </a>

  <ul class="nav-links" id="nav-links">
    <li><a href="#processo">Como funciona</a></li>
    <li><a href="#superficies">Superfícies</a></li>
    <li><a href="#simulacao">Simulação IA</a></li>
  </ul>

  <button class="nav-toggle" type="button" aria-label="Abrir menu" aria-controls="nav-links" aria-expanded="false">
    <span></span>
    <span></span>
    <span></span>
  </button>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-bg">
    <div class="hero-grid-line"></div>
    <div class="hero-grid-line"></div>
    <div class="hero-grid-line"></div>
    <div class="hero-grid-line"></div>
  </div>

  <div class="hero-inner">
    <div class="hero-content">
      <p class="hero-tag reveal">Revitalização visual de áreas externas</p>
      <h1 class="hero-h1 reveal reveal-delay-1">Superfícies externas tratadas com <em>precisão</em> e resultado visível.</h1>
      <p class="hero-sub reveal reveal-delay-2">Lavagem cuidadosa e controlada com equipamentos profissionais para recuperar a aparência de pisos, muros, fachadas, decks e bordas de piscina em Nova Friburgo e região.</p>

      <div class="hero-actions reveal reveal-delay-3">
        <a href="galvao-quiz.php" class="btn-primary">
          Solicitar orçamento
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
        <a href="#processo" class="btn-ghost">Como funciona</a>
      </div>

    </div>

    <div class="hero-visual reveal reveal-delay-2">
      <div class="hero-visual-meta">
        <span class="hero-region-pill">
          <span class="icon-outline" style="width:15px;height:15px;margin:0"><svg><use href="#i-map"/></svg></span>
          Nova Friburgo e região
        </span>
      </div>
      <div class="hero-carousel" data-hero-carousel>
        <div class="hero-carousel-track" data-hero-carousel-track>
          <article class="hero-slide">
            <div class="hero-compare-pane">
              <img src="../assets/images/hero-before-after/project-1-before.png" alt="Muro de pedra antes da lavagem técnica">
              <span class="hero-compare-label">Antes</span>
            </div>
            <div class="hero-compare-pane">
              <img src="../assets/images/hero-before-after/project-1-after.png" alt="Muro de pedra depois da lavagem técnica">
              <span class="hero-compare-label after">Depois</span>
            </div>
          </article>
          <article class="hero-slide">
            <div class="hero-compare-pane">
              <img src="../assets/images/hero-before-after/project-3-before.jpg" alt="Borda de piscina antes da lavagem técnica">
              <span class="hero-compare-label">Antes</span>
            </div>
            <div class="hero-compare-pane">
              <img src="../assets/images/hero-before-after/project-3-after.png" alt="Borda de piscina depois da lavagem técnica">
              <span class="hero-compare-label after">Depois</span>
            </div>
          </article>
          <article class="hero-slide">
            <div class="hero-compare-pane">
              <img src="../assets/images/hero-before-after/project-4-before.webp" alt="Muro de contenção antes da lavagem técnica">
              <span class="hero-compare-label">Antes</span>
            </div>
            <div class="hero-compare-pane">
              <img src="../assets/images/hero-before-after/project-4-after.png" alt="Muro de contenção depois da lavagem técnica">
              <span class="hero-compare-label after">Depois</span>
            </div>
          </article>
        </div>
        <div class="hero-carousel-dots" aria-label="Projetos de antes e depois">
          <button class="hero-carousel-dot active" type="button" aria-label="Ver projeto 1" data-hero-slide="0"></button>
          <button class="hero-carousel-dot" type="button" aria-label="Ver projeto 2" data-hero-slide="1"></button>
          <button class="hero-carousel-dot" type="button" aria-label="Ver projeto 3" data-hero-slide="2"></button>
        </div>
        <button class="hero-carousel-arrow prev" type="button" aria-label="Projeto anterior" data-hero-prev>
          <svg viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
        </button>
        <button class="hero-carousel-arrow next" type="button" aria-label="Próximo projeto" data-hero-next>
          <svg viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>
        </button>
      </div>
      <p class="hero-ai-note">Análise visual por imagem com inteligência artificial</p>
    </div>
    <div class="safety-box reveal">
      <span class="icon-outline"><svg><use href="#i-shield"/></svg></span>
      <div>
        <p class="safety-title">Segurança preventiva para áreas de circulação</p>
        <p class="safety-text">Além do impacto visual, o acúmulo de lodo e musgo pode deixar pisos e escadas mais escorregadios, especialmente em áreas molhadas ou usadas por idosos. A limpeza preventiva ajuda a manter o ambiente mais bonito, cuidado e seguro.</p>
      </div>
    </div>
  </div>
</section>

<!-- PROBLEMA -->
<section class="problema" id="problema">
  <div class="inner">
    <div class="problema-grid">
      <div class="problema-text reveal">
        <p class="section-label">Manutenção preventiva</p>
        <h2 class="section-title">Lodo, musgo e umidade mudam a aparência e o uso do espaço.</h2>
        <p class="section-sub">Com o tempo, áreas externas acumulam sujeira, perdem uniformidade e deixam de transmitir cuidado. A manutenção no momento certo preserva a aparência, melhora a circulação e evita desgaste desnecessário.</p>

        <div class="alert-box">
          <p>Cuidar com regularidade ajuda a preservar o acabamento, manter o imóvel mais agradável e evitar intervenções maiores no futuro.</p>
        </div>
      </div>

      <div class="problema-cards">
        <div class="problema-card reveal reveal-delay-1">
          <div class="problema-card-icon icon-outline"><svg><use href="#i-bricks"/></svg></div>
          <p class="problema-card-title">Lodo e musgo</p>
          <p class="problema-card-body">Acúmulos que escurecem a superfície, reduzem o contraste natural e podem deixar áreas de passagem menos seguras.</p>
        </div>
        <div class="problema-card reveal reveal-delay-2">
          <div class="problema-card-icon icon-outline"><svg><use href="#i-droplet"/></svg></div>
          <p class="problema-card-title">Umidade aderida</p>
          <p class="problema-card-body">Marcas que tiram a uniformidade de pedras, pisos, rejuntes e muros, mesmo quando a estrutura está bem conservada.</p>
        </div>
        <div class="problema-card reveal reveal-delay-3">
          <div class="problema-card-icon icon-outline"><svg><use href="#i-eye"/></svg></div>
          <p class="problema-card-title">Desgaste visual</p>
          <p class="problema-card-body">Sem manutenção visual, superfícies boas podem parecer envelhecidas antes da hora.</p>
        </div>
        <div class="problema-card reveal reveal-delay-4">
          <div class="problema-card-icon icon-outline"><svg><use href="#i-alert"/></svg></div>
          <p class="problema-card-title">Risco de queda</p>
          <p class="problema-card-body">Em rampas, calçadas e bordas, uma limpeza preventiva ajuda a reduzir pontos escorregadios.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- PROCESSO -->
<section id="processo">
  <div class="inner">
    <div class="reveal">
      <p class="section-label">Como funciona</p>
      <h2 class="section-title">Um processo cuidadoso, visual e bem organizado.</h2>
      <p class="section-sub">A avaliação considera o tipo de superfície, o estado atual do ambiente e o resultado esperado. Cada serviço recebe uma abordagem adequada, sem improviso e sem excesso.</p>
    </div>

    <div class="processo-steps">
      <div class="pstep reveal reveal-delay-1">
        <div class="pstep-accent"></div>
        <p class="pstep-num">01</p>
        <p class="pstep-title">Avaliação inicial</p>
        <p class="pstep-body">Entendimento do ambiente, do tipo de superfície, da exposição ao tempo e das áreas que exigem mais cuidado.</p>
      </div>
      <div class="pstep reveal reveal-delay-2">
        <div class="pstep-accent"></div>
        <p class="pstep-num">02</p>
        <p class="pstep-title">Abordagem adequada</p>
        <p class="pstep-body">Definição da melhor forma de limpeza para cada material, respeitando acabamento, uso e conservação.</p>
      </div>
      <div class="pstep reveal reveal-delay-3">
        <div class="pstep-accent"></div>
        <p class="pstep-num">03</p>
        <p class="pstep-title">Lavagem controlada</p>
        <p class="pstep-body">Uso de equipamentos profissionais com cuidado na pressão e na proximidade da superfície.</p>
      </div>
      <div class="pstep reveal reveal-delay-4">
        <div class="pstep-accent"></div>
        <p class="pstep-num">04</p>
        <p class="pstep-title">Acabamento e registro</p>
        <p class="pstep-body">Revisão visual, limpeza final e registro do resultado para acompanhar a transformação do espaço.</p>
      </div>
    </div>
  </div>
</section>

<!-- SUPERFÍCIES -->
<section class="surfaces" id="superficies">
  <div class="inner">
    <div class="reveal">
      <p class="section-label">Superfícies atendidas</p>
      <h2 class="section-title">Cada material pede um cuidado diferente.</h2>
      <p class="section-sub">Não existe uma única forma de limpar tudo. A pressão, o ritmo e a abordagem mudam conforme o material, o uso do espaço e o nível de sujeira acumulada.</p>
    </div>

    <div class="surfaces-grid">
      <div class="surface-item reveal reveal-delay-1">
        <div class="surface-icon icon-outline"><svg><use href="#i-car"/></svg></div>
        <p class="surface-name">Garagem e pisos</p>
        <p class="surface-note">Lavagem controlada para recuperar contraste e aparência de uso diário.</p>
      </div>
      <div class="surface-item reveal reveal-delay-2">
        <div class="surface-icon icon-outline"><svg><use href="#i-bricks"/></svg></div>
        <p class="surface-name">Muros e fachadas</p>
        <p class="surface-note">Cuidado visual para remover marcas do tempo sem perder a leitura do material.</p>
      </div>
      <div class="surface-item reveal reveal-delay-3">
        <div class="surface-icon icon-outline"><svg><use href="#i-stone"/></svg></div>
        <p class="surface-name">Pedra São Tomé</p>
        <p class="surface-note">Atenção ao acabamento natural da pedra e às variações da superfície.</p>
      </div>
      <div class="surface-item reveal reveal-delay-4">
        <div class="surface-icon icon-outline"><svg><use href="#i-wood"/></svg></div>
        <p class="surface-name">Decks de madeira</p>
        <p class="surface-note">Mais controle, menos agressividade e atenção ao estado da madeira.</p>
      </div>
      <div class="surface-item reveal">
        <div class="surface-icon icon-outline"><svg><use href="#i-pool"/></svg></div>
        <p class="surface-name">Bordas de piscina</p>
        <p class="surface-note">Limpeza cuidadosa em áreas molhadas, com foco em aparência e segurança.</p>
      </div>
      <div class="surface-item reveal reveal-delay-1">
        <div class="surface-icon icon-outline"><svg><use href="#i-gourmet"/></svg></div>
        <p class="surface-name">Áreas gourmet</p>
        <p class="surface-note">Cuidado com marcas de uso, umidade e resíduos do dia a dia.</p>
      </div>
      <div class="surface-item reveal reveal-delay-2">
        <div class="surface-icon icon-outline"><svg><use href="#i-roof"/></svg></div>
        <p class="surface-name">Telhado (altura média)</p>
        <p class="surface-note">Atenção ao acúmulo de lodo e musgo com abordagem compatível.</p>
      </div>
      <div class="surface-item reveal reveal-delay-3">
        <div class="surface-icon icon-outline"><svg><use href="#i-steps"/></svg></div>
        <p class="surface-name">Calçadas e acessos</p>
        <p class="surface-note">Segurança e estética nos pontos de passagem.</p>
      </div>
    </div>
  </div>
</section>

<!-- DIFERENCIAIS -->
<section>
  <div class="inner">
    <div class="reveal">
      <p class="section-label">Por que escolher a Galvão Lavagem Técnica</p>
      <h2 class="section-title">Cuidado profissional com atenção ao resultado visual.</h2>
      <p class="section-sub">A diferença está na avaliação cuidadosa, na organização do atendimento, no uso correto dos equipamentos e na atenção aos detalhes. Não é uma limpeza apressada: é cuidado com a aparência e conservação do seu espaço.</p>
    </div>

    <div class="diff-grid">
      <div class="diff-card reveal reveal-delay-1">
        <div class="diff-icon icon-outline"><svg><use href="#i-flask"/></svg></div>
        <div>
          <p class="diff-text-title">Avaliação antes da execução</p>
          <p class="diff-text-body">Entendimento do material, do acesso, do nível de sujeira e do resultado esperado antes de iniciar.</p>
        </div>
      </div>
      <div class="diff-card reveal reveal-delay-2">
        <div class="diff-icon icon-outline"><svg><use href="#i-shield"/></svg></div>
        <div>
          <p class="diff-text-title">Lavagem segura e controlada</p>
          <p class="diff-text-body">Execução organizada, com atenção a pisos escorregadios, escadas, áreas molhadas e circulação de pessoas.</p>
        </div>
      </div>
      <div class="diff-card reveal reveal-delay-3">
        <div class="diff-icon icon-outline"><svg><use href="#i-camera"/></svg></div>
        <div>
          <p class="diff-text-title">Apoio visual no orçamento</p>
          <p class="diff-text-body">Fotos e análise visual ajudam a entender melhor o ambiente e alinhar expectativas antes do serviço.</p>
        </div>
      </div>
      <div class="diff-card reveal reveal-delay-4">
        <div class="diff-icon icon-outline"><svg><use href="#i-repeat"/></svg></div>
        <div>
          <p class="diff-text-title">Manutenção preventiva</p>
          <p class="diff-text-body">Cuidado periódico para preservar aparência, segurança e percepção de valor do imóvel.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- SIMULAÇÃO IA -->
<section class="ia-section" id="simulacao">
  <div class="inner">
    <div class="ia-inner">
      <div class="ia-text reveal">
        <p class="section-label">Simulação visual</p>
        <h2 class="section-title">Uma prévia para entender melhor o potencial do espaço.</h2>
        <p class="section-sub">Envie uma foto da área externa e receba uma referência visual simples para apoiar a avaliação. A imagem ajuda a tornar o orçamento mais claro e a conversa mais objetiva.</p>

        <div class="ia-steps-mini">
          <div class="ia-step-mini">
            <span class="ia-step-num">1</span>
            <p class="ia-step-text">Você envia uma foto da área pelo formulário.</p>
          </div>
          <div class="ia-step-mini">
            <span class="ia-step-num">2</span>
            <p class="ia-step-text">A imagem ajuda a observar o tipo de superfície e o estado geral do ambiente.</p>
          </div>
          <div class="ia-step-mini">
            <span class="ia-step-num">3</span>
            <p class="ia-step-text">Você recebe uma prévia visual para facilitar a avaliação e o orçamento.</p>
          </div>
        </div>

        <div style="margin-top:32px">
          <a href="galvao-quiz.php" class="btn-primary">
            Iniciar avaliação por imagem
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </a>
        </div>
      </div>

      <div class="reveal reveal-delay-2">
        <div class="ia-upload-card" data-ia-card data-endpoint="ai-simulation.php" data-csrf="<?= e(csrf_token()); ?>">
          <div class="ia-card-header">
            <span class="ia-card-title">Simulação IA</span>
            <div class="ia-dots">
              <div class="ia-dot"></div>
              <div class="ia-dot active"></div>
              <div class="ia-dot active"></div>
            </div>
          </div>

          <label class="ia-upload-zone" for="ia-image-input" data-ia-dropzone>
            <input class="ia-file-input" id="ia-image-input" type="file" accept="image/*" capture="environment" data-ia-input>
            <div class="ia-upload-icon"><img class="ia-upload-icon-img" src="../assets/images/photo-camera-svgrepo-com.svg" alt="" aria-hidden="true"></div>
            <p class="ia-upload-primary">Envie uma foto ou tire uma agora</p>
            <p class="ia-upload-secondary" data-ia-upload-label>Use a câmera do celular ou selecione da galeria</p>
            <p class="ia-upload-note">PNG, JPG ou WEBP</p>
          </label>

          <div class="ia-preview" data-ia-preview>
            <img src="" alt="Prévia da imagem enviada" data-ia-preview-image>
            <div class="ia-preview-meta">
              <span>Prévia local</span>
            </div>
          </div>

          <div class="ia-state" data-ia-loading>
            <span class="ia-spinner"></span>
            <span class="ia-loading-copy">
              <strong>Gerando prévia visual</strong>
              <span>Preparando uma referência para apoiar a avaliação.</span>
            </span>
          </div>

          <div class="ia-simulation-result" data-ia-result>
            <strong data-ia-result-title>Prévia visual gerada</strong>
            <span data-ia-result-text>Imagem pronta para apoiar a avaliação do orçamento.</span>
            <img class="ia-result-image" src="" alt="Simulação visual gerada por IA" data-ia-result-image>
          </div>

          <button class="btn-primary ia-action" type="button" data-ia-action>
            Enviar imagem para análise
          </button>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CTA FINAL -->
<section class="cta-section">
  <div class="cta-glow"></div>
  <div style="position:relative;z-index:1">
    <p class="section-label reveal">Avaliação gratuita</p>
    <h2 class="section-title reveal reveal-delay-1">Vamos cuidar da aparência do seu espaço?</h2>
    <p class="section-sub reveal reveal-delay-2">Envie algumas informações e receba um orçamento claro, organizado e sem compromisso.</p>

    <div class="cta-actions reveal reveal-delay-3">
      <a href="galvao-quiz.php" class="btn-primary">
        Solicitar orçamento grátis
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </a>
    </div>

    <p class="cta-note reveal reveal-delay-4">Atendimento em Nova Friburgo e região · Avaliação cuidadosa antes da execução</p>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <p class="footer-brand"><strong>Galvão Lavagem Técnica</strong> · Revitalização de áreas externas</p>
  <p class="footer-region"><span class="icon-outline"><svg><use href="#i-map"/></svg></span> Nova Friburgo e região · RJ</p>
</footer>

<script>
  // Nav scroll
  const nav = document.getElementById('nav');
  window.addEventListener('scroll', () => {
    nav.classList.toggle('scrolled', window.scrollY > 40);
  });

  const navToggle = document.querySelector('.nav-toggle');
  const navLinks = document.querySelectorAll('.nav-links a');

  navToggle?.addEventListener('click', () => {
    const isOpen = nav.classList.toggle('menu-open');
    navToggle.setAttribute('aria-expanded', String(isOpen));
    navToggle.setAttribute('aria-label', isOpen ? 'Fechar menu' : 'Abrir menu');
  });

  navLinks.forEach(link => {
    link.addEventListener('click', () => {
      nav.classList.remove('menu-open');
      navToggle?.setAttribute('aria-expanded', 'false');
      navToggle?.setAttribute('aria-label', 'Abrir menu');
    });
  });

  const heroTrack = document.querySelector('[data-hero-carousel-track]');
  const heroDots = document.querySelectorAll('[data-hero-slide]');
  const heroPrev = document.querySelector('[data-hero-prev]');
  const heroNext = document.querySelector('[data-hero-next]');
  const heroSlideCount = heroDots.length;
  let heroSlideIndex = 0;
  let heroCarouselTimer = null;

  function setHeroSlide(index) {
    if (!heroTrack || heroSlideCount === 0) return;
    heroSlideIndex = (index + heroSlideCount) % heroSlideCount;
    heroTrack.style.transform = `translateX(-${heroSlideIndex * 100}%)`;
    heroDots.forEach((dot, dotIndex) => {
      dot.classList.toggle('active', dotIndex === heroSlideIndex);
    });
  }

  function startHeroCarousel() {
    if (heroSlideCount <= 1 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    window.clearInterval(heroCarouselTimer);
    heroCarouselTimer = window.setInterval(() => {
      setHeroSlide(heroSlideIndex + 1);
    }, 7000);
  }

  function restartHeroCarouselAt(index) {
    window.clearInterval(heroCarouselTimer);
    setHeroSlide(index);
    startHeroCarousel();
  }

  heroDots.forEach(dot => {
    dot.addEventListener('click', () => {
      restartHeroCarouselAt(Number(dot.dataset.heroSlide || 0));
    });
  });

  heroPrev?.addEventListener('click', () => restartHeroCarouselAt(heroSlideIndex - 1));
  heroNext?.addEventListener('click', () => restartHeroCarouselAt(heroSlideIndex + 1));

  startHeroCarousel();

  // Reveal on scroll
  const reveals = document.querySelectorAll('.reveal');
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

  reveals.forEach(el => observer.observe(el));

  // Simulação visual conectada ao endpoint PHP. A chave OpenAI fica apenas no backend.
  const iaCard = document.querySelector('[data-ia-card]');
  const iaInput = document.querySelector('[data-ia-input]');
  const iaDropzone = document.querySelector('[data-ia-dropzone]');
  const iaUploadLabel = document.querySelector('[data-ia-upload-label]');
  const iaPreview = document.querySelector('[data-ia-preview]');
  const iaPreviewImage = document.querySelector('[data-ia-preview-image]');
  const iaLoading = document.querySelector('[data-ia-loading]');
  const iaResult = document.querySelector('[data-ia-result]');
  const iaResultTitle = document.querySelector('[data-ia-result-title]');
  const iaResultText = document.querySelector('[data-ia-result-text]');
  const iaResultImage = document.querySelector('[data-ia-result-image]');
  const iaAction = document.querySelector('[data-ia-action]');
  let iaSelectedFile = null;
  let iaPreviewUrl = null;
  let iaReadyForQuiz = false;
  let iaIsGenerating = false;
  const iaCompression = {
    maxWidth: 1280,
    quality: 0.76,
    mimeType: 'image/jpeg',
  };

  function canvasToBlob(canvas, mimeType, quality) {
    return new Promise(resolve => {
      canvas.toBlob(blob => resolve(blob), mimeType, quality);
    });
  }

  function loadImageFromFile(file) {
    return new Promise((resolve, reject) => {
      const image = new Image();
      const url = URL.createObjectURL(file);

      image.onload = () => {
        URL.revokeObjectURL(url);
        resolve(image);
      };
      image.onerror = () => {
        URL.revokeObjectURL(url);
        reject(new Error('Não foi possível preparar a imagem.'));
      };
      image.src = url;
    });
  }

  async function optimizeIaImage(file) {
    const image = await loadImageFromFile(file);
    const scale = Math.min(1, iaCompression.maxWidth / Math.max(image.naturalWidth, image.naturalHeight));
    const width = Math.max(1, Math.round(image.naturalWidth * scale));
    const height = Math.max(1, Math.round(image.naturalHeight * scale));
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;

    const context = canvas.getContext('2d', { alpha: false });
    context.fillStyle = '#111111';
    context.fillRect(0, 0, width, height);
    context.drawImage(image, 0, 0, width, height);

    const blob = await canvasToBlob(canvas, iaCompression.mimeType, iaCompression.quality);

    if (!blob) {
      throw new Error('Não foi possível comprimir a imagem.');
    }

    return new File([blob], 'galvao-simulacao-otimizada.jpg', {
      type: iaCompression.mimeType,
      lastModified: Date.now(),
    });
  }

  if (iaInput && iaAction) {
    const setCleanIaFile = async (file) => {
      if (!file || !file.type.startsWith('image/')) return;

      iaSelectedFile = null;
      iaReadyForQuiz = false;
      iaIsGenerating = false;
      iaAction.disabled = true;
      iaAction.textContent = 'Preparando imagem...';

      if (iaPreviewUrl) URL.revokeObjectURL(iaPreviewUrl);

      try {
        iaSelectedFile = await optimizeIaImage(file);
      } catch (error) {
        iaSelectedFile = file;
      }

      iaPreviewUrl = URL.createObjectURL(iaSelectedFile);
      iaPreviewImage.src = iaPreviewUrl;
      iaUploadLabel.textContent = 'Imagem otimizada e pronta para análise visual';
      iaDropzone.classList.add('has-file', 'is-hidden');
      iaPreview.classList.add('visible');
      iaLoading.classList.remove('visible');
      iaResult.classList.remove('visible');
      iaResultImage.classList.remove('visible');
      iaResultImage.removeAttribute('src');
      iaAction.textContent = 'Enviar imagem para análise';
      iaAction.disabled = false;
    };

    iaInput.addEventListener('change', event => {
      setCleanIaFile(event.target.files[0]);
    });

    iaDropzone.addEventListener('dragover', event => {
      event.preventDefault();
      iaDropzone.classList.add('has-file');
    });

    iaDropzone.addEventListener('dragleave', () => {
      if (!iaSelectedFile) {
        iaDropzone.classList.remove('has-file', 'is-hidden');
      }
    });

    iaDropzone.addEventListener('drop', event => {
      event.preventDefault();
      setCleanIaFile(event.dataTransfer.files[0]);
    });

    iaAction.addEventListener('click', async () => {
      if (iaIsGenerating) return;

      if (iaReadyForQuiz) {
        window.location.href = 'galvao-quiz.php';
        return;
      }

      if (!iaSelectedFile) {
        iaInput.click();
        return;
      }

      iaIsGenerating = true;
      iaLoading.classList.add('visible');
      iaResult.classList.remove('visible');
      iaResultImage.classList.remove('visible');
      iaAction.disabled = true;
      iaAction.textContent = 'Gerando prévia...';

      try {
        const formData = new FormData();
        formData.append('environment_image', iaSelectedFile);
        formData.append('_csrf_token', iaCard.dataset.csrf || '');

        const response = await fetch(iaCard.dataset.endpoint || 'ai-simulation.php', {
          method: 'POST',
          body: formData,
        });
        const payload = await response.json();

        if (payload.soft_block) {
          iaResultTitle.textContent = 'Continue para o orçamento';
          iaResultText.textContent = payload.message || 'Envie as fotos pelo quiz para uma avaliação manual organizada.';
        } else {
          if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Não foi possível gerar a prévia agora.');
          }

          if (payload.simulation?.result_data_url) {
            iaResultImage.src = payload.simulation.result_data_url;
            iaResultImage.classList.add('visible');
          }

          iaResultTitle.textContent = payload.cached ? 'Prévia recuperada' : 'Prévia visual gerada';
          iaResultText.textContent = payload.message || 'Resultado visual pronto para orientar o diagnóstico técnico.';
        }
      } catch (error) {
        iaResultTitle.textContent = 'Não foi possível gerar agora';
        iaResultText.textContent = error.message || 'Você ainda pode seguir para o orçamento e enviar a imagem pelo quiz.';
      } finally {
        iaLoading.classList.remove('visible');
        iaResult.classList.add('visible');
        iaAction.disabled = false;
        iaAction.textContent = 'Continuar para o orçamento';
        iaReadyForQuiz = true;
        iaIsGenerating = false;
      }
    });
  }
</script>

<?= $landingSettings['custom_body'] ?? ''; ?>
</body>
</html>
