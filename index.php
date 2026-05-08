<?php
// Dashboard Paris Viu - PHP + HTML
// Arquivo único. Basta subir este index.php em uma hospedagem com PHP habilitado.

$campaigns = [
    [
        "slug" => "campanha-0010-nova-parisviu",
        "id" => "120244842239960764",
        "name" => "[CAMPANHA0010] [NOVA PARISVIU] [RECONHECIMENTO]",
        "dailyBudget" => 20.00,
        "creatives" => [
            ["id" => "120244842311700764", "name" => "[ADD001] [CARROSSEL]", "platform" => "facebook", "spend" => 1.81, "reach" => 1182, "impressions" => 1242],
            ["id" => "120244842239980764", "name" => "[ADD002] [CARROSSEL]", "platform" => "instagram", "spend" => 1.85, "reach" => 1456, "impressions" => 1456],
        ],
    ],
    [
        "slug" => "campanha-0009-historia-parisviu",
        "id" => "120244828429190764",
        "name" => "[CAMPANHA0009] [HISTORIA PARISVIU] [RECONHECIMENTO]",
        "dailyBudget" => 20.00,
        "creatives" => [
            ["id" => "120244841964610764", "name" => "[ADD001] [VÍDEO]", "platform" => "facebook", "spend" => 1.87, "reach" => 1053, "impressions" => 1163],
            ["id" => "120244841917760764", "name" => "[ADD001] [VÍDEO]", "platform" => "instagram", "spend" => 2.13, "reach" => 1567, "impressions" => 1579],
        ],
    ],
    [
        "slug" => "campanha-0008-ocul-prts-na-hora",
        "id" => "120244828207010764",
        "name" => "[CAMPANHA0008] [ÓCUL PRTS NA HORA]",
        "dailyBudget" => 20.00,
        "creatives" => [
            ["id" => "120244841775000764", "name" => "[ADD002] [VÍDEO]", "platform" => "facebook", "spend" => 2.18, "reach" => 1436, "impressions" => 1512],
            ["id" => "120244841168570764", "name" => "[ADD002] [VÍDEO]", "platform" => "instagram", "spend" => 2.17, "reach" => 1593, "impressions" => 1593],
        ],
    ],
    [
        "slug" => "campanha-0007-oculos-9990-micro",
        "id" => "120244828194760764",
        "name" => "[CAMP0007] [ÓCULOS 99,90] [MICRO]",
        "dailyBudget" => 40.00,
        "creatives" => [
            ["id" => "120244842110540764", "name" => "[ADD002] [VÍDEO]", "platform" => "facebook", "spend" => 2.15, "reach" => 1448, "impressions" => 1500],
            ["id" => "120244842071230764", "name" => "[ADD004] [CARROSSEL]", "platform" => "facebook", "spend" => 0.43, "reach" => 16, "impressions" => 18],
            ["id" => "120244842031390764", "name" => "[ADD003] [CARROSSEL]", "platform" => "instagram", "spend" => 0.20, "reach" => 6, "impressions" => 6],
            ["id" => "120244828194770764", "name" => "[ADD001] [VÍDEO]", "platform" => "instagram", "spend" => 2.40, "reach" => 1770, "impressions" => 1820],
        ],
    ],
];

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function br_money($value) {
    return 'R$ ' . number_format((float)$value, 2, ',', '.');
}

function br_int($value) {
    return number_format((int)$value, 0, ',', '.');
}

function platform_label($platform) {
    if ($platform === 'facebook') return 'Facebook';
    if ($platform === 'instagram') return 'Instagram';
    return ucfirst((string)$platform);
}

function creative_type($name) {
    $upper = mb_strtoupper((string)$name, 'UTF-8');
    if (str_contains($upper, 'CARROSSEL')) return 'CARROSSEL';
    if (str_contains($upper, 'VÍDEO') || str_contains($upper, 'VIDEO')) return 'VÍDEO';
    return 'CRIATIVO';
}

function campaign_totals($campaign) {
    $spend = 0;
    $reach = 0;
    $impressions = 0;

    foreach ($campaign['creatives'] as $creative) {
        $spend += $creative['spend'];
        $reach += $creative['reach'];
        $impressions += $creative['impressions'];
    }

    return [
        'spend' => $spend,
        'reach' => $reach,
        'impressions' => $impressions,
    ];
}

$allCreatives = [];
foreach ($campaigns as $campaign) {
    foreach ($campaign['creatives'] as $creative) {
        $allCreatives[] = $creative;
    }
}

$totalSpend = array_sum(array_column($allCreatives, 'spend'));
$totalReach = array_sum(array_column($allCreatives, 'reach'));
$totalImpressions = array_sum(array_column($allCreatives, 'impressions'));

$maxReach = max(array_column($allCreatives, 'reach'));
$maxSpend = max(array_column($allCreatives, 'spend'));

$rankedCreatives = $allCreatives;
usort($rankedCreatives, function ($a, $b) {
    return $b['reach'] <=> $a['reach'];
});

$ranks = [];
foreach ($rankedCreatives as $index => $creative) {
    $ranks[$creative['id']] = $index + 1;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Paris Viu - PHP + HTML</title>
  <style>
    :root {
      --bg:#f4f7fb;
      --card:#ffffff;
      --text:#111827;
      --muted:#64748b;
      --primary:#5b6ee1;
      --primary-2:#7c3aed;
      --border:#e5e7eb;
      --soft:#f8fafc;
      --success:#047857;
      --success-bg:#ecfdf5;
      --shadow:0 14px 34px rgba(15,23,42,.10);
      --radius:22px;
    }
    * { box-sizing:border-box; }
    html { scroll-behavior:smooth; }
    body {
      margin:0;
      font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
      color:var(--text);
      background:
        radial-gradient(circle at top left,rgba(91,110,225,.18),transparent 28rem),
        linear-gradient(135deg,#f8fbff 0%,#edf2fb 100%);
      min-height:100vh;
      -webkit-font-smoothing:antialiased;
    }
    .shell {
      width:min(1160px,calc(100% - 28px));
      margin:0 auto;
    }
    header { padding:18px 0 14px; }
    .hero {
      background:linear-gradient(135deg,var(--primary) 0%,var(--primary-2) 100%);
      color:#fff;
      border-radius:26px;
      padding:22px;
      box-shadow:var(--shadow);
      display:grid;
      gap:18px;
    }
    .hero h1 {
      margin:0 0 8px;
      font-size:clamp(1.55rem,6vw,3rem);
      line-height:1.05;
      letter-spacing:-.03em;
    }
    .hero p {
      margin:0;
      color:rgba(255,255,255,.88);
      line-height:1.45;
      font-size:.98rem;
    }
    .hero-meta {
      background:rgba(255,255,255,.14);
      border:1px solid rgba(255,255,255,.22);
      border-radius:20px;
      padding:14px;
      display:grid;
      grid-template-columns:1fr;
      gap:10px;
    }
    .hero-meta span {
      display:block;
      font-size:.72rem;
      color:rgba(255,255,255,.72);
      margin-bottom:3px;
      text-transform:uppercase;
      letter-spacing:.06em;
      font-weight:800;
    }
    .hero-meta strong {
      display:block;
      font-size:.96rem;
      line-height:1.2;
    }
    main { padding:8px 0 90px; }
    .mobile-nav {
      position:sticky;
      top:0;
      z-index:20;
      margin:0 -14px 16px;
      padding:10px 14px;
      background:rgba(248,251,255,.88);
      backdrop-filter:blur(14px);
      border-bottom:1px solid rgba(226,232,240,.85);
      display:flex;
      gap:8px;
      overflow-x:auto;
      scrollbar-width:none;
    }
    .mobile-nav::-webkit-scrollbar { display:none; }
    .mobile-nav a {
      flex:0 0 auto;
      text-decoration:none;
      background:#fff;
      color:#3730a3;
      border:1px solid #c7d2fe;
      border-radius:999px;
      padding:10px 13px;
      font-weight:900;
      font-size:.78rem;
      box-shadow:0 6px 16px rgba(15,23,42,.06);
    }
    .period-chip {
      display:inline-flex;
      align-items:center;
      background:var(--success-bg);
      color:var(--success);
      border:1px solid #bbf7d0;
      border-radius:999px;
      padding:9px 12px;
      font-weight:900;
      font-size:.78rem;
      margin-bottom:14px;
      line-height:1.25;
    }
    .top-metrics {
      display:grid;
      grid-template-columns:repeat(2,minmax(0,1fr));
      gap:10px;
      margin-bottom:16px;
    }
    .top-metrics article {
      background:var(--card);
      border:1px solid var(--border);
      border-radius:20px;
      padding:14px;
      box-shadow:0 8px 20px rgba(15,23,42,.06);
    }
    .top-metrics.compact article { padding:13px; }
    .top-metrics span,
    .campaign-card-metrics span,
    .ad-dashboard span,
    .ad-insights span,
    .detail-budget span {
      display:block;
      color:var(--muted);
      font-size:.64rem;
      text-transform:uppercase;
      letter-spacing:.07em;
      font-weight:900;
      margin-bottom:5px;
      line-height:1.25;
    }
    .top-metrics strong {
      font-size:clamp(1.18rem,5vw,1.8rem);
      font-weight:950;
      line-height:1.05;
      letter-spacing:-.02em;
    }
    .campaign-grid {
      display:grid;
      grid-template-columns:1fr;
      gap:14px;
      align-items:start;
    }
    .campaign-link {
      text-decoration:none;
      color:inherit;
    }
    .campaign-card {
      background:var(--card);
      border:1px solid var(--border);
      border-radius:24px;
      box-shadow:var(--shadow);
      overflow:hidden;
      min-height:0;
      padding:16px;
      display:grid;
      gap:14px;
      transition:.2s ease;
      background:
        radial-gradient(circle at top right,rgba(91,110,225,.13),transparent 12rem),
        linear-gradient(135deg,#ffffff 0%,#f7f9ff 100%);
    }
    .campaign-card:active { transform:scale(.99); }
    .campaign-card-top {
      display:grid;
      grid-template-columns:auto 1fr;
      gap:12px;
      align-items:flex-start;
    }
    .campaign-icon {
      width:48px;
      height:48px;
      border-radius:17px;
      display:grid;
      place-items:center;
      background:linear-gradient(135deg,var(--primary),#8b5cf6);
      color:#fff;
      font-weight:950;
      flex:0 0 auto;
      box-shadow:0 10px 22px rgba(91,110,225,.24);
    }
    .campaign-card h2 {
      margin:0;
      font-size:1rem;
      line-height:1.28;
      letter-spacing:-.01em;
    }
    .campaign-card p,
    .section-heading p {
      margin:6px 0 0;
      color:var(--muted);
      font-size:.76rem;
      font-weight:750;
      overflow-wrap:anywhere;
    }
    .campaign-card-metrics {
      display:grid;
      grid-template-columns:repeat(2,minmax(0,1fr));
      gap:8px;
    }
    .campaign-card-metrics div,
    .detail-budget {
      background:#fff;
      border:1px solid var(--border);
      border-radius:16px;
      padding:10px;
    }
    .campaign-card-metrics strong,
    .detail-budget strong {
      font-weight:950;
      font-size:.96rem;
      line-height:1.1;
    }
    .open-label {
      align-self:end;
      justify-self:start;
      background:#eef2ff;
      color:#3730a3;
      border:1px solid #c7d2fe;
      border-radius:999px;
      padding:9px 12px;
      font-weight:950;
      font-size:.78rem;
    }
    .campaign-detail {
      margin-top:28px;
      padding-top:10px;
      scroll-margin-top:74px;
    }
    .section-heading {
      background:linear-gradient(135deg,#fff 0%,#f7f9ff 100%);
      border:1px solid var(--border);
      border-radius:24px;
      padding:16px;
      box-shadow:var(--shadow);
      display:grid;
      gap:14px;
      align-items:center;
      margin-bottom:14px;
    }
    .section-heading h2 {
      margin:10px 0 0;
      font-size:1.18rem;
      line-height:1.25;
      letter-spacing:-.02em;
    }
    .back-link {
      display:inline-flex;
      align-items:center;
      gap:8px;
      text-decoration:none;
      background:#fff;
      color:#3730a3;
      border:1px solid #c7d2fe;
      border-radius:999px;
      padding:10px 12px;
      font-weight:950;
      font-size:.8rem;
    }
    .ads-title {
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      gap:10px;
      margin:18px 0 12px;
      flex-wrap:wrap;
    }
    .ads-title h3 {
      margin:0;
      font-size:1.08rem;
    }
    .ads-title span {
      color:var(--muted);
      font-weight:850;
      font-size:.86rem;
    }
    .ads-grid {
      display:grid;
      grid-template-columns:1fr;
      gap:14px;
    }
    .ad-card {
      border:1px solid var(--border);
      border-radius:24px;
      overflow:hidden;
      background:#fff;
      box-shadow:0 10px 26px rgba(15,23,42,.07);
    }
    .ad-visual {
      min-height:108px;
      padding:16px;
      display:flex;
      flex-direction:column;
      justify-content:space-between;
      color:#fff;
      background:linear-gradient(135deg,#1d4ed8,#60a5fa);
      position:relative;
      overflow:hidden;
    }
    .ad-visual.instagram {
      background:linear-gradient(135deg,#be185d,#f97316);
    }
    .ad-visual::after {
      content:"";
      position:absolute;
      width:150px;
      height:150px;
      right:-44px;
      top:-48px;
      border-radius:999px;
      background:rgba(255,255,255,.18);
    }
    .ad-visual span,
    .ad-visual strong {
      position:relative;
      z-index:1;
    }
    .ad-visual span {
      font-size:1.18rem;
      font-weight:950;
      letter-spacing:.04em;
    }
    .ad-visual strong {
      align-self:flex-start;
      border:1px solid rgba(255,255,255,.4);
      background:rgba(255,255,255,.18);
      border-radius:999px;
      padding:7px 11px;
      font-size:.78rem;
    }
    .ad-content {
      padding:14px;
      display:grid;
      gap:12px;
    }
    .ad-head {
      display:grid;
      gap:10px;
      align-items:start;
    }
    .ad-head h3 {
      margin:0;
      font-size:.98rem;
      line-height:1.28;
    }
    .ad-head p {
      margin:5px 0 0;
      color:var(--muted);
      font-size:.76rem;
      font-weight:750;
      overflow-wrap:anywhere;
    }
    .ad-head b {
      background:#fef3c7;
      border:1px solid #fde68a;
      color:#92400e;
      border-radius:999px;
      padding:6px 9px;
      font-size:.72rem;
      white-space:nowrap;
      justify-self:start;
    }
    .ad-dashboard {
      display:grid;
      grid-template-columns:repeat(2,minmax(0,1fr));
      gap:8px;
    }
    .ad-dashboard div,
    .ad-insights div {
      background:var(--soft);
      border:1px solid var(--border);
      border-radius:16px;
      padding:10px;
    }
    .ad-dashboard strong,
    .ad-insights strong {
      font-weight:950;
      line-height:1.2;
      font-size:.9rem;
    }
    .ad-bars {
      display:grid;
      gap:10px;
    }
    .bar-line {
      display:grid;
      grid-template-columns:1fr;
      gap:6px;
      align-items:center;
      font-size:.78rem;
      color:var(--muted);
      font-weight:850;
    }
    .bar-line div {
      background:#eef2ff;
      border-radius:999px;
      height:12px;
      overflow:hidden;
    }
    .bar-line i {
      display:block;
      height:100%;
      border-radius:999px;
      background:linear-gradient(90deg,var(--primary),#8b5cf6);
    }
    .bar-line i.spend {
      background:linear-gradient(90deg,#16a34a,#22c55e);
    }
    .bar-line b {
      color:#334155;
      text-align:left;
      white-space:nowrap;
    }
    .ad-insights {
      display:grid;
      grid-template-columns:1fr;
      gap:8px;
    }
    .ad-content small {
      color:var(--muted);
      font-weight:700;
      line-height:1.35;
      font-size:.76rem;
    }
    .floating-top {
      position:fixed;
      right:14px;
      bottom:14px;
      z-index:30;
      width:48px;
      height:48px;
      border-radius:999px;
      display:grid;
      place-items:center;
      background:linear-gradient(135deg,var(--primary),var(--primary-2));
      color:#fff;
      text-decoration:none;
      font-weight:950;
      box-shadow:0 12px 28px rgba(91,110,225,.35);
    }
    @media (min-width:680px) {
      header { padding-top:30px; }
      .hero { grid-template-columns:1.25fr .75fr; padding:30px; }
      .top-metrics { grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; margin-bottom:22px; }
      .campaign-grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:20px; }
      .campaign-card { min-height:270px; padding:20px; }
      .section-heading { grid-template-columns:1fr auto; padding:20px; }
      .ads-grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px; }
      .ad-head { grid-template-columns:1fr auto; }
      .ad-insights { grid-template-columns:repeat(3,minmax(0,1fr)); }
      .bar-line { grid-template-columns:78px 1fr 80px; }
      .bar-line b { text-align:right; }
      .mobile-nav { margin-left:0; margin-right:0; border-radius:0 0 20px 20px; }
    }
    @media (min-width:1080px) {
      .shell { width:min(1240px,calc(100% - 32px)); }
    }
  </style>
</head>
<body>
  <header>
    <div class="shell">
      <section class="hero">
        <div>
          <h1>Dashboard de Campanhas</h1>
          <p>Visual otimizado para celular, com leitura em cards, atalhos fixos e navegação interna para os anúncios.</p>
        </div>
        <div class="hero-meta">
          <div>
            <span>Conta de anúncios</span>
            <strong>9729633853761104</strong>
          </div>
          <div>
            <span>Conta</span>
            <strong>Paris Viu - Pré-Paga</strong>
          </div>
          <div>
            <span>Estrutura</span>
            <strong>PHP + HTML em arquivo único</strong>
          </div>
        </div>
      </section>
    </div>
  </header>

  <main>
    <div class="shell">
      <nav class="mobile-nav" aria-label="Atalhos">
        <a href="#campanhas">Campanhas</a>
        <?php foreach ($campaigns as $campaign): ?>
          <a href="#<?= e($campaign['slug']) ?>"><?= e(substr($campaign['name'], 1, 12)) ?></a>
        <?php endforeach; ?>
      </nav>

      <section id="campanhas">
        <div class="period-chip">Dados revisados: 7, 15 e 30 dias conforme retorno da API</div>

        <section class="top-metrics">
          <article>
            <span>Gasto total</span>
            <strong><?= br_money($totalSpend) ?></strong>
          </article>
          <article>
            <span>Alcance total</span>
            <strong><?= br_int($totalReach) ?></strong>
          </article>
          <article>
            <span>Campanhas</span>
            <strong><?= count($campaigns) ?></strong>
          </article>
          <article>
            <span>Criativos</span>
            <strong><?= count($allCreatives) ?></strong>
          </article>
        </section>

        <section class="campaign-grid">
          <?php foreach ($campaigns as $campaign): ?>
            <?php $campaignTotals = campaign_totals($campaign); ?>
            <a class="campaign-link" href="#<?= e($campaign['slug']) ?>">
              <article class="campaign-card">
                <div class="campaign-card-top">
                  <div class="campaign-icon">PV</div>
                  <div>
                    <h2><?= e($campaign['name']) ?></h2>
                    <p>Campanha ID: <?= e($campaign['id']) ?></p>
                  </div>
                </div>

                <div class="campaign-card-metrics">
                  <div>
                    <span>Orçamento diário</span>
                    <strong><?= br_money($campaign['dailyBudget']) ?></strong>
                  </div>
                  <div>
                    <span>Gasto</span>
                    <strong><?= br_money($campaignTotals['spend']) ?></strong>
                  </div>
                  <div>
                    <span>Alcance</span>
                    <strong><?= br_int($campaignTotals['reach']) ?></strong>
                  </div>
                  <div>
                    <span>Anúncios</span>
                    <strong><?= count($campaign['creatives']) ?></strong>
                  </div>
                </div>

                <div class="open-label">Abrir anúncios da campanha</div>
              </article>
            </a>
          <?php endforeach; ?>
        </section>
      </section>

      <?php foreach ($campaigns as $campaign): ?>
        <?php $campaignTotals = campaign_totals($campaign); ?>
        <section id="<?= e($campaign['slug']) ?>" class="campaign-detail">
          <div class="section-heading">
            <div>
              <a class="back-link" href="#campanhas">← Voltar para campanhas</a>
              <h2><?= e($campaign['name']) ?></h2>
              <p>Campanha ID: <?= e($campaign['id']) ?></p>
            </div>

            <div class="detail-budget">
              <span>Orçamento diário</span>
              <strong><?= br_money($campaign['dailyBudget']) ?></strong>
            </div>
          </div>

          <section class="top-metrics compact">
            <article>
              <span>Gasto da campanha</span>
              <strong><?= br_money($campaignTotals['spend']) ?></strong>
            </article>
            <article>
              <span>Alcance</span>
              <strong><?= br_int($campaignTotals['reach']) ?></strong>
            </article>
            <article>
              <span>Impressões</span>
              <strong><?= br_int($campaignTotals['impressions']) ?></strong>
            </article>
            <article>
              <span>Anúncios</span>
              <strong><?= count($campaign['creatives']) ?></strong>
            </article>
          </section>

          <div class="ads-title">
            <h3>Anúncios / Criativos</h3>
            <span><?= count($campaign['creatives']) ?> anúncio(s) nesta campanha</span>
          </div>

          <section class="ads-grid">
            <?php foreach ($campaign['creatives'] as $creative): ?>
              <?php
                $efficiency = $creative['spend'] > 0 ? round($creative['reach'] / $creative['spend']) : 0;
                $costPerThousandReach = $creative['reach'] > 0 ? ($creative['spend'] / $creative['reach']) * 1000 : 0;
                $frequency = $creative['reach'] > 0 ? $creative['impressions'] / $creative['reach'] : 0;
                $reachWidth = max(($creative['reach'] / $maxReach) * 100, 3);
                $spendWidth = max(($creative['spend'] / $maxSpend) * 100, 3);
              ?>
              <article class="ad-card">
                <div class="ad-visual <?= e($creative['platform']) ?>">
                  <span><?= e(creative_type($creative['name'])) ?></span>
                  <strong><?= e(platform_label($creative['platform'])) ?></strong>
                </div>

                <div class="ad-content">
                  <div class="ad-head">
                    <div>
                      <h3><?= e($creative['name']) ?></h3>
                      <p>Criativo ID: <?= e($creative['id']) ?></p>
                      <p>Tempo de veiculação: conforme período selecionado</p>
                    </div>
                    <b>#<?= e($ranks[$creative['id']] ?? '-') ?> alcance</b>
                  </div>

                  <div class="ad-dashboard">
                    <div>
                      <span>Gasto</span>
                      <strong><?= br_money($creative['spend']) ?></strong>
                    </div>
                    <div>
                      <span>Alcance</span>
                      <strong><?= br_int($creative['reach']) ?></strong>
                    </div>
                    <div>
                      <span>Impressões</span>
                      <strong><?= br_int($creative['impressions']) ?></strong>
                    </div>
                    <div>
                      <span>Custo/1.000 alcance</span>
                      <strong><?= br_money($costPerThousandReach) ?></strong>
                    </div>
                  </div>

                  <div class="ad-bars">
                    <div class="bar-line">
                      <span>Alcance</span>
                      <div><i style="width:<?= e(number_format($reachWidth, 2, '.', '')) ?>%"></i></div>
                      <b><?= br_int($creative['reach']) ?></b>
                    </div>
                    <div class="bar-line">
                      <span>Gasto</span>
                      <div><i class="spend" style="width:<?= e(number_format($spendWidth, 2, '.', '')) ?>%"></i></div>
                      <b><?= br_money($creative['spend']) ?></b>
                    </div>
                  </div>

                  <div class="ad-insights">
                    <div>
                      <span>Eficiência*</span>
                      <strong><?= br_int($efficiency) ?> alcances por real</strong>
                    </div>
                    <div>
                      <span>Frequência</span>
                      <strong><?= e(number_format($frequency, 2, ',', '.')) ?>x</strong>
                    </div>
                    <div>
                      <span>Plataforma</span>
                      <strong><?= e(platform_label($creative['platform'])) ?></strong>
                    </div>
                  </div>

                  <small>* Quantidade de contas alcançadas para cada R$ 1,00 investido.</small>
                </div>
              </article>
            <?php endforeach; ?>
          </section>
        </section>
      <?php endforeach; ?>
    </div>
  </main>

  <a class="floating-top" href="#campanhas" aria-label="Voltar ao topo">↑</a>
</body>
</html>
