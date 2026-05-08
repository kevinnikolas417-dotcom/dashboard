from pathlib import Path
import zipfile, shutil

base = Path("/mnt/data/admin_dashboard_refeito_do_zero")
if base.exists():
    shutil.rmtree(base)
base.mkdir()

php = r'''<?php
session_start();

/*
|--------------------------------------------------------------------------
| CONFIGURAÇÃO DO ADMIN
|--------------------------------------------------------------------------
| E-mail: kevinnikolas417@gmail.com
| Senha: 123456
|--------------------------------------------------------------------------
*/

$ADMIN_EMAIL = 'kevinnikolas417@gmail.com';
$ADMIN_PASSWORD = '123456';

/*
|--------------------------------------------------------------------------
| DADOS DO DASHBOARD
|--------------------------------------------------------------------------
| Edite os dados abaixo quando quiser atualizar empresas, campanhas ou anúncios.
|--------------------------------------------------------------------------
*/

$periodos = array(
    '7' => array(
        'nome' => 'Últimos 7 dias',
        'inicio' => '1 de maio de 2026',
        'fim' => '7 de maio de 2026'
    ),
    '15' => array(
        'nome' => 'Últimos 15 dias',
        'inicio' => '23 de abril de 2026',
        'fim' => '7 de maio de 2026'
    ),
    '30' => array(
        'nome' => 'Últimos 30 dias',
        'inicio' => '8 de abril de 2026',
        'fim' => '7 de maio de 2026'
    )
);

$empresas = array(
    array(
        'slug' => 'parisviu',
        'nome' => 'PARISVIU',
        'status' => 'Ativa',
        'descricao' => 'Empresa ativa com campanhas de reconhecimento e anúncios em veiculação.',
        'campanhas' => array(
            array(
                'slug' => 'campanha-0010-nova-parisviu',
                'id' => '120244842239960764',
                'nome' => '[CAMPANHA0010] [NOVA PARISVIU] [RECONHECIMENTO]',
                'orcamento_diario' => 20.00,
                'anuncios' => array(
                    array(
                        'id' => '120244842311700764',
                        'nome' => '[ADD001] [CARROSSEL]',
                        'plataforma' => 'facebook',
                        'gasto' => 1.81,
                        'alcance' => 1182,
                        'impressoes' => 1242
                    ),
                    array(
                        'id' => '120244842239980764',
                        'nome' => '[ADD002] [CARROSSEL]',
                        'plataforma' => 'instagram',
                        'gasto' => 1.85,
                        'alcance' => 1456,
                        'impressoes' => 1456
                    )
                )
            ),
            array(
                'slug' => 'campanha-0009-historia-parisviu',
                'id' => '120244828429190764',
                'nome' => '[CAMPANHA0009] [HISTORIA PARISVIU] [RECONHECIMENTO]',
                'orcamento_diario' => 20.00,
                'anuncios' => array(
                    array(
                        'id' => '120244841964610764',
                        'nome' => '[ADD001] [VÍDEO]',
                        'plataforma' => 'facebook',
                        'gasto' => 1.87,
                        'alcance' => 1053,
                        'impressoes' => 1163
                    ),
                    array(
                        'id' => '120244841917760764',
                        'nome' => '[ADD001] [VÍDEO]',
                        'plataforma' => 'instagram',
                        'gasto' => 2.13,
                        'alcance' => 1567,
                        'impressoes' => 1579
                    )
                )
            ),
            array(
                'slug' => 'campanha-0008-ocul-prts-na-hora',
                'id' => '120244828207010764',
                'nome' => '[CAMPANHA0008] [ÓCUL PRTS NA HORA]',
                'orcamento_diario' => 20.00,
                'anuncios' => array(
                    array(
                        'id' => '120244841775000764',
                        'nome' => '[ADD002] [VÍDEO]',
                        'plataforma' => 'facebook',
                        'gasto' => 2.18,
                        'alcance' => 1436,
                        'impressoes' => 1512
                    ),
                    array(
                        'id' => '120244841168570764',
                        'nome' => '[ADD002] [VÍDEO]',
                        'plataforma' => 'instagram',
                        'gasto' => 2.17,
                        'alcance' => 1593,
                        'impressoes' => 1593
                    )
                )
            ),
            array(
                'slug' => 'campanha-0007-oculos-9990-micro',
                'id' => '120244828194760764',
                'nome' => '[CAMP0007] [ÓCULOS 99,90] [MICRO]',
                'orcamento_diario' => 40.00,
                'anuncios' => array(
                    array(
                        'id' => '120244842110540764',
                        'nome' => '[ADD002] [VÍDEO]',
                        'plataforma' => 'facebook',
                        'gasto' => 2.15,
                        'alcance' => 1448,
                        'impressoes' => 1500
                    ),
                    array(
                        'id' => '120244842071230764',
                        'nome' => '[ADD004] [CARROSSEL]',
                        'plataforma' => 'facebook',
                        'gasto' => 0.43,
                        'alcance' => 16,
                        'impressoes' => 18
                    ),
                    array(
                        'id' => '120244842031390764',
                        'nome' => '[ADD003] [CARROSSEL]',
                        'plataforma' => 'instagram',
                        'gasto' => 0.20,
                        'alcance' => 6,
                        'impressoes' => 6
                    ),
                    array(
                        'id' => '120244828194770764',
                        'nome' => '[ADD001] [VÍDEO]',
                        'plataforma' => 'instagram',
                        'gasto' => 2.40,
                        'alcance' => 1770,
                        'impressoes' => 1820
                    )
                )
            )
        )
    )
);

/*
|--------------------------------------------------------------------------
| FUNÇÕES AUXILIARES
|--------------------------------------------------------------------------
*/

function esc($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function dinheiro($valor) {
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}

function numero($valor) {
    return number_format((int)$valor, 0, ',', '.');
}

function nome_plataforma($plataforma) {
    if ($plataforma == 'facebook') {
        return 'Facebook';
    }

    if ($plataforma == 'instagram') {
        return 'Instagram';
    }

    return ucfirst($plataforma);
}

function tipo_anuncio($nome) {
    $texto = strtoupper($nome);

    if (strpos($texto, 'CARROSSEL') !== false) {
        return 'CARROSSEL';
    }

    if (strpos($texto, 'VIDEO') !== false || strpos($texto, 'VÍDEO') !== false) {
        return 'VÍDEO';
    }

    return 'CRIATIVO';
}

function buscar_empresa($empresas, $slug) {
    foreach ($empresas as $empresa) {
        if ($empresa['slug'] == $slug) {
            return $empresa;
        }
    }

    return null;
}

function buscar_campanha($campanhas, $slug) {
    foreach ($campanhas as $campanha) {
        if ($campanha['slug'] == $slug) {
            return $campanha;
        }
    }

    return null;
}

function totais_campanha($campanha) {
    $total = array(
        'gasto' => 0,
        'alcance' => 0,
        'impressoes' => 0,
        'anuncios' => 0
    );

    foreach ($campanha['anuncios'] as $anuncio) {
        $total['gasto'] += $anuncio['gasto'];
        $total['alcance'] += $anuncio['alcance'];
        $total['impressoes'] += $anuncio['impressoes'];
        $total['anuncios']++;
    }

    return $total;
}

function totais_empresa($empresa) {
    $total = array(
        'gasto' => 0,
        'alcance' => 0,
        'impressoes' => 0,
        'campanhas' => 0,
        'anuncios' => 0
    );

    foreach ($empresa['campanhas'] as $campanha) {
        $tc = totais_campanha($campanha);
        $total['gasto'] += $tc['gasto'];
        $total['alcance'] += $tc['alcance'];
        $total['impressoes'] += $tc['impressoes'];
        $total['anuncios'] += $tc['anuncios'];
        $total['campanhas']++;
    }

    return $total;
}

function todos_anuncios($empresas) {
    $lista = array();

    foreach ($empresas as $empresa) {
        foreach ($empresa['campanhas'] as $campanha) {
            foreach ($campanha['anuncios'] as $anuncio) {
                $lista[] = $anuncio;
            }
        }
    }

    return $lista;
}

function total_campanhas_geral($empresas) {
    $total = 0;

    foreach ($empresas as $empresa) {
        $total += count($empresa['campanhas']);
    }

    return $total;
}

/*
|--------------------------------------------------------------------------
| LOGIN / LOGOUT
|--------------------------------------------------------------------------
*/

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$erro_login = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $senha = isset($_POST['senha']) ? trim($_POST['senha']) : '';

    if ($email == $ADMIN_EMAIL && $senha == $ADMIN_PASSWORD) {
        $_SESSION['admin_logado'] = true;
        $_SESSION['admin_email'] = $email;
        header('Location: index.php');
        exit;
    } else {
        $erro_login = 'E-mail ou senha inválidos.';
    }
}

$logado = isset($_SESSION['admin_logado']) && $_SESSION['admin_logado'] == true;

/*
|--------------------------------------------------------------------------
| ROTAS / FILTROS
|--------------------------------------------------------------------------
*/

$periodo_selecionado = isset($_GET['periodo']) ? $_GET['periodo'] : '7';

if (!isset($periodos[$periodo_selecionado])) {
    $periodo_selecionado = '7';
}

$periodo_atual = $periodos[$periodo_selecionado];

$empresa_slug = isset($_GET['empresa']) ? $_GET['empresa'] : '';
$campanha_slug = isset($_GET['campanha']) ? $_GET['campanha'] : '';

$empresa_selecionada = $empresa_slug ? buscar_empresa($empresas, $empresa_slug) : null;
$campanha_selecionada = null;

if ($empresa_selecionada && $campanha_slug) {
    $campanha_selecionada = buscar_campanha($empresa_selecionada['campanhas'], $campanha_slug);
}

$anuncios_geral = todos_anuncios($empresas);

$max_alcance = 1;
$max_gasto = 1;

foreach ($anuncios_geral as $anuncio) {
    if ($anuncio['alcance'] > $max_alcance) {
        $max_alcance = $anuncio['alcance'];
    }

    if ($anuncio['gasto'] > $max_gasto) {
        $max_gasto = $anuncio['gasto'];
    }
}

$ranks = $anuncios_geral;
usort($ranks, function($a, $b) {
    if ($a['alcance'] == $b['alcance']) {
        return 0;
    }

    return ($a['alcance'] < $b['alcance']) ? 1 : -1;
});

$ranking = array();
$posicao = 1;

foreach ($ranks as $anuncio) {
    $ranking[$anuncio['id']] = $posicao;
    $posicao++;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>

    <style>
        :root {
            --bg: #f3f6fb;
            --card: #ffffff;
            --text: #111827;
            --muted: #64748b;
            --primary: #5b6ee1;
            --secondary: #7c3aed;
            --border: #e5e7eb;
            --soft: #f8fafc;
            --green: #047857;
            --green-bg: #ecfdf5;
            --danger: #b91c1c;
            --danger-bg: #fef2f2;
            --shadow: 0 14px 34px rgba(15, 23, 42, .10);
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(91, 110, 225, .18), transparent 28rem),
                linear-gradient(135deg, #f8fbff 0%, #edf2fb 100%);
        }

        a {
            color: inherit;
        }

        .shell {
            width: min(1160px, calc(100% - 28px));
            margin: 0 auto;
        }

        .login-page {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 22px 14px;
        }

        .login-card {
            width: min(460px, 100%);
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 28px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .login-header {
            color: #ffffff;
            padding: 28px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }

        .login-header h1 {
            margin: 0 0 8px;
            font-size: 2rem;
        }

        .login-header p {
            margin: 0;
            color: rgba(255, 255, 255, .86);
            line-height: 1.45;
        }

        .login-form {
            padding: 24px;
            display: grid;
            gap: 14px;
        }

        .field {
            display: grid;
            gap: 7px;
        }

        .field label {
            font-size: .82rem;
            color: #334155;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .field input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 13px 14px;
            font-size: 1rem;
            outline: none;
        }

        .field input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(91, 110, 225, .14);
        }

        .login-button {
            border: none;
            cursor: pointer;
            color: #ffffff;
            border-radius: 16px;
            padding: 14px;
            font-size: 1rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }

        .login-error {
            color: var(--danger);
            background: var(--danger-bg);
            border: 1px solid #fecaca;
            border-radius: 14px;
            padding: 10px 12px;
            font-weight: 800;
        }

        header {
            padding: 18px 0 14px;
        }

        .hero {
            color: #ffffff;
            padding: 22px;
            border-radius: 26px;
            box-shadow: var(--shadow);
            display: grid;
            gap: 18px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }

        .hero h1 {
            margin: 0 0 8px;
            font-size: clamp(1.55rem, 6vw, 3rem);
            line-height: 1.05;
        }

        .hero p {
            margin: 0;
            color: rgba(255, 255, 255, .88);
            line-height: 1.45;
        }

        .hero-meta {
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 20px;
            padding: 14px;
            display: grid;
            gap: 10px;
        }

        .hero-meta span {
            display: block;
            color: rgba(255, 255, 255, .72);
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 3px;
        }

        .hero-meta strong {
            display: block;
            font-size: .96rem;
        }

        main {
            padding: 8px 0 90px;
        }

        .nav {
            position: sticky;
            top: 0;
            z-index: 10;
            margin: 0 -14px 16px;
            padding: 10px 14px;
            background: rgba(248, 251, 255, .9);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(226, 232, 240, .9);
            display: flex;
            gap: 8px;
            overflow-x: auto;
        }

        .nav a {
            flex: 0 0 auto;
            text-decoration: none;
            background: #ffffff;
            color: #3730a3;
            border: 1px solid #c7d2fe;
            border-radius: 999px;
            padding: 10px 13px;
            font-weight: 900;
            font-size: .78rem;
            box-shadow: 0 6px 16px rgba(15, 23, 42, .06);
        }

        .nav a.logout {
            color: var(--danger);
            border-color: #fecaca;
            background: #fff7f7;
        }

        .date-filter {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .06);
            margin-bottom: 16px;
        }

        .date-filter-title {
            color: var(--muted);
            font-weight: 900;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .07em;
            margin-bottom: 9px;
        }

        .date-filter-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .date-filter-buttons a {
            text-decoration: none;
            color: var(--text);
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 10px 14px;
            font-weight: 900;
            font-size: .82rem;
        }

        .date-filter-buttons a.active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 8px 18px rgba(91, 110, 225, .28);
        }

        .period-chip {
            display: inline-flex;
            align-items: center;
            background: var(--green-bg);
            color: var(--green);
            border: 1px solid #bbf7d0;
            border-radius: 999px;
            padding: 9px 12px;
            font-weight: 900;
            font-size: .78rem;
            margin-bottom: 14px;
            line-height: 1.25;
        }

        .metrics {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 16px;
        }

        .metric,
        .mini-box {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .06);
        }

        .metric span,
        .mini-box span,
        .ad-kpi span,
        .ad-insight span {
            display: block;
            color: var(--muted);
            font-size: .64rem;
            text-transform: uppercase;
            letter-spacing: .07em;
            font-weight: 900;
            margin-bottom: 5px;
        }

        .metric strong {
            font-size: clamp(1.18rem, 5vw, 1.8rem);
            font-weight: 900;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
        }

        .card-link {
            text-decoration: none;
            color: inherit;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: var(--shadow);
            padding: 16px;
            display: grid;
            gap: 14px;
            background:
                radial-gradient(circle at top right, rgba(91, 110, 225, .13), transparent 12rem),
                linear-gradient(135deg, #ffffff 0%, #f7f9ff 100%);
        }

        .card-top {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 12px;
            align-items: flex-start;
        }

        .avatar {
            width: 48px;
            height: 48px;
            border-radius: 17px;
            display: grid;
            place-items: center;
            color: #ffffff;
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            box-shadow: 0 10px 22px rgba(91, 110, 225, .24);
        }

        .card h2 {
            margin: 0;
            font-size: 1rem;
            line-height: 1.28;
        }

        .card p,
        .heading p,
        .ad-head p {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: .76rem;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .card-metrics {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .open-label,
        .back-link,
        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            background: #eef2ff;
            color: #3730a3;
            border: 1px solid #c7d2fe;
            border-radius: 999px;
            padding: 9px 12px;
            font-weight: 900;
            font-size: .78rem;
            justify-self: start;
        }

        .status-badge {
            color: var(--green);
            background: var(--green-bg);
            border-color: #bbf7d0;
            margin-top: 8px;
        }

        .heading {
            background: linear-gradient(135deg, #ffffff 0%, #f7f9ff 100%);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 16px;
            box-shadow: var(--shadow);
            display: grid;
            gap: 14px;
            margin-bottom: 14px;
        }

        .heading h2 {
            margin: 10px 0 0;
            font-size: 1.18rem;
        }

        .ads-title {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            margin: 18px 0 12px;
            flex-wrap: wrap;
        }

        .ads-title h3 {
            margin: 0;
            font-size: 1.08rem;
        }

        .ads-title span {
            color: var(--muted);
            font-weight: 800;
            font-size: .86rem;
        }

        .ads-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
        }

        .ad-card {
            border: 1px solid var(--border);
            border-radius: 24px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .07);
        }

        .ad-visual {
            min-height: 108px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: #ffffff;
            background: linear-gradient(135deg, #1d4ed8, #60a5fa);
            position: relative;
            overflow: hidden;
        }

        .ad-visual.instagram {
            background: linear-gradient(135deg, #be185d, #f97316);
        }

        .ad-visual:after {
            content: "";
            position: absolute;
            width: 150px;
            height: 150px;
            right: -44px;
            top: -48px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .18);
        }

        .ad-visual span,
        .ad-visual strong {
            position: relative;
            z-index: 1;
        }

        .ad-visual span {
            font-size: 1.18rem;
            font-weight: 900;
            letter-spacing: .04em;
        }

        .ad-visual strong {
            align-self: flex-start;
            border: 1px solid rgba(255, 255, 255, .4);
            background: rgba(255, 255, 255, .18);
            border-radius: 999px;
            padding: 7px 11px;
            font-size: .78rem;
        }

        .ad-content {
            padding: 14px;
            display: grid;
            gap: 12px;
        }

        .ad-head {
            display: grid;
            gap: 10px;
        }

        .ad-head h3 {
            margin: 0;
            font-size: .98rem;
        }

        .rank {
            background: #fef3c7;
            border: 1px solid #fde68a;
            color: #92400e;
            border-radius: 999px;
            padding: 6px 9px;
            font-size: .72rem;
            font-weight: 900;
            justify-self: start;
        }

        .ad-kpis,
        .ad-insights {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .ad-kpi,
        .ad-insight {
            background: var(--soft);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 10px;
        }

        .bar-list {
            display: grid;
            gap: 10px;
        }

        .bar-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 6px;
            color: var(--muted);
            font-size: .78rem;
            font-weight: 800;
        }

        .bar-track {
            background: #eef2ff;
            border-radius: 999px;
            height: 12px;
            overflow: hidden;
        }

        .bar-fill {
            display: block;
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .bar-fill.spend {
            background: linear-gradient(90deg, #16a34a, #22c55e);
        }

        .note {
            color: var(--muted);
            font-weight: 700;
            line-height: 1.35;
            font-size: .76rem;
        }

        .floating-top {
            position: fixed;
            right: 14px;
            bottom: 14px;
            z-index: 30;
            width: 48px;
            height: 48px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #ffffff;
            text-decoration: none;
            font-weight: 900;
            box-shadow: 0 12px 28px rgba(91, 110, 225, .35);
        }

        @media (min-width: 680px) {
            header {
                padding-top: 30px;
            }

            .hero {
                grid-template-columns: 1.25fr .75fr;
                padding: 30px;
            }

            .metrics {
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 16px;
            }

            .grid,
            .ads-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 20px;
            }

            .card {
                min-height: 270px;
                padding: 20px;
            }

            .heading {
                grid-template-columns: 1fr auto;
                padding: 20px;
            }

            .ad-head {
                grid-template-columns: 1fr auto;
            }

            .ad-insights {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .bar-row {
                grid-template-columns: 78px 1fr 80px;
                align-items: center;
            }
        }
    </style>
</head>
<body>
<?php if (!$logado): ?>

    <section class="login-page">
        <div class="login-card">
            <div class="login-header">
                <h1>Admin</h1>
                <p>Acesse para visualizar empresas ativas, campanhas e anúncios.</p>
            </div>

            <form class="login-form" method="post" action="index.php">
                <?php if ($erro_login): ?>
                    <div class="login-error"><?php echo esc($erro_login); ?></div>
                <?php endif; ?>

                <div class="field">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="<?php echo esc($ADMIN_EMAIL); ?>" required>
                </div>

                <div class="field">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required>
                </div>

                <button type="submit" class="login-button">Entrar</button>
            </form>
        </div>
    </section>

<?php else: ?>

    <header>
        <div class="shell">
            <section class="hero">
                <div>
                    <h1>Admin Dashboard</h1>
                    <p>Visualize empresas ativas, campanhas e anúncios em uma área protegida.</p>
                </div>

                <div class="hero-meta">
                    <div>
                        <span>Usuário</span>
                        <strong><?php echo esc($_SESSION['admin_email']); ?></strong>
                    </div>

                    <div>
                        <span>Período ativo</span>
                        <strong><?php echo esc($periodo_atual['nome']); ?></strong>
                    </div>

                    <div>
                        <span>Status</span>
                        <strong>Admin conectado</strong>
                    </div>
                </div>
            </section>
        </div>
    </header>

    <main>
        <div class="shell">
            <nav class="nav">
                <a href="index.php?periodo=<?php echo esc($periodo_selecionado); ?>">Empresas</a>

                <?php foreach ($empresas as $empresa_nav): ?>
                    <a href="index.php?periodo=<?php echo esc($periodo_selecionado); ?>&empresa=<?php echo esc($empresa_nav['slug']); ?>">
                        <?php echo esc($empresa_nav['nome']); ?>
                    </a>
                <?php endforeach; ?>

                <a class="logout" href="index.php?logout=1">Sair</a>
            </nav>

            <section class="date-filter">
                <div class="date-filter-title">Filtrar período</div>

                <div class="date-filter-buttons">
                    <?php foreach ($periodos as $chave_periodo => $periodo): ?>
                        <?php
                            $url_periodo = 'index.php?periodo=' . urlencode($chave_periodo);

                            if ($empresa_selecionada) {
                                $url_periodo .= '&empresa=' . urlencode($empresa_selecionada['slug']);
                            }

                            if ($campanha_selecionada) {
                                $url_periodo .= '&campanha=' . urlencode($campanha_selecionada['slug']);
                            }

                            $classe_periodo = ($chave_periodo == $periodo_selecionado) ? 'active' : '';
                        ?>

                        <a class="<?php echo esc($classe_periodo); ?>" href="<?php echo esc($url_periodo); ?>">
                            <?php echo esc($periodo['nome']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <div class="period-chip">
                <?php echo esc($periodo_atual['nome']); ?>:
                <?php echo esc($periodo_atual['inicio']); ?> a
                <?php echo esc($periodo_atual['fim']); ?>
            </div>

            <?php if (!$empresa_selecionada): ?>

                <section class="metrics">
                    <article class="metric">
                        <span>Empresas ativas</span>
                        <strong><?php echo count($empresas); ?></strong>
                    </article>

                    <article class="metric">
                        <span>Campanhas</span>
                        <strong><?php echo total_campanhas_geral($empresas); ?></strong>
                    </article>

                    <article class="metric">
                        <span>Criativos</span>
                        <strong><?php echo count($anuncios_geral); ?></strong>
                    </article>

                    <article class="metric">
                        <span>Alcance total</span>
                        <strong>
                            <?php
                                $alcance_total_geral = 0;
                                foreach ($anuncios_geral as $a) {
                                    $alcance_total_geral += $a['alcance'];
                                }
                                echo numero($alcance_total_geral);
                            ?>
                        </strong>
                    </article>
                </section>

                <section class="grid">
                    <?php foreach ($empresas as $empresa): ?>
                        <?php $te = totais_empresa($empresa); ?>

                        <a class="card-link" href="index.php?periodo=<?php echo esc($periodo_selecionado); ?>&empresa=<?php echo esc($empresa['slug']); ?>">
                            <article class="card">
                                <div class="card-top">
                                    <div class="avatar">PV</div>

                                    <div>
                                        <h2><?php echo esc($empresa['nome']); ?></h2>
                                        <p><?php echo esc($empresa['descricao']); ?></p>
                                        <span class="status-badge"><?php echo esc($empresa['status']); ?></span>
                                    </div>
                                </div>

                                <div class="card-metrics">
                                    <div class="mini-box">
                                        <span>Campanhas</span>
                                        <strong><?php echo esc($te['campanhas']); ?></strong>
                                    </div>

                                    <div class="mini-box">
                                        <span>Anúncios</span>
                                        <strong><?php echo esc($te['anuncios']); ?></strong>
                                    </div>

                                    <div class="mini-box">
                                        <span>Gasto</span>
                                        <strong><?php echo dinheiro($te['gasto']); ?></strong>
                                    </div>

                                    <div class="mini-box">
                                        <span>Alcance</span>
                                        <strong><?php echo numero($te['alcance']); ?></strong>
                                    </div>
                                </div>

                                <div class="open-label">Abrir empresa</div>
                            </article>
                        </a>
                    <?php endforeach; ?>
                </section>

            <?php elseif ($empresa_selecionada && !$campanha_selecionada): ?>

                <?php $te = totais_empresa($empresa_selecionada); ?>

                <section class="heading">
                    <div>
                        <a class="back-link" href="index.php?periodo=<?php echo esc($periodo_selecionado); ?>">
                            ← Voltar para empresas
                        </a>

                        <h2><?php echo esc($empresa_selecionada['nome']); ?></h2>
                        <p><?php echo esc($empresa_selecionada['descricao']); ?></p>
                    </div>

                    <div class="mini-box">
                        <span>Status</span>
                        <strong><?php echo esc($empresa_selecionada['status']); ?></strong>
                    </div>
                </section>

                <section class="metrics">
                    <article class="metric">
                        <span>Gasto</span>
                        <strong><?php echo dinheiro($te['gasto']); ?></strong>
                    </article>

                    <article class="metric">
                        <span>Alcance</span>
                        <strong><?php echo numero($te['alcance']); ?></strong>
                    </article>

                    <article class="metric">
                        <span>Campanhas</span>
                        <strong><?php echo esc($te['campanhas']); ?></strong>
                    </article>

                    <article class="metric">
                        <span>Anúncios</span>
                        <strong><?php echo esc($te['anuncios']); ?></strong>
                    </article>
                </section>

                <section class="grid">
                    <?php foreach ($empresa_selecionada['campanhas'] as $campanha): ?>
                        <?php $tc = totais_campanha($campanha); ?>

                        <a class="card-link" href="index.php?periodo=<?php echo esc($periodo_selecionado); ?>&empresa=<?php echo esc($empresa_selecionada['slug']); ?>&campanha=<?php echo esc($campanha['slug']); ?>">
                            <article class="card">
                                <div class="card-top">
                                    <div class="avatar">PV</div>

                                    <div>
                                        <h2><?php echo esc($campanha['nome']); ?></h2>
                                        <p>Campanha ID: <?php echo esc($campanha['id']); ?></p>
                                    </div>
                                </div>

                                <div class="card-metrics">
                                    <div class="mini-box">
                                        <span>Orçamento diário</span>
                                        <strong><?php echo dinheiro($campanha['orcamento_diario']); ?></strong>
                                    </div>

                                    <div class="mini-box">
                                        <span>Gasto</span>
                                        <strong><?php echo dinheiro($tc['gasto']); ?></strong>
                                    </div>

                                    <div class="mini-box">
                                        <span>Alcance</span>
                                        <strong><?php echo numero($tc['alcance']); ?></strong>
                                    </div>

                                    <div class="mini-box">
                                        <span>Anúncios</span>
                                        <strong><?php echo esc($tc['anuncios']); ?></strong>
                                    </div>
                                </div>

                                <div class="open-label">Abrir anúncios da campanha</div>
                            </article>
                        </a>
                    <?php endforeach; ?>
                </section>

            <?php elseif ($empresa_selecionada && $campanha_selecionada): ?>

                <?php $tc = totais_campanha($campanha_selecionada); ?>

                <section class="heading">
                    <div>
                        <a class="back-link" href="index.php?periodo=<?php echo esc($periodo_selecionado); ?>&empresa=<?php echo esc($empresa_selecionada['slug']); ?>">
                            ← Voltar para campanhas
                        </a>

                        <h2><?php echo esc($campanha_selecionada['nome']); ?></h2>
                        <p>Campanha ID: <?php echo esc($campanha_selecionada['id']); ?></p>
                    </div>

                    <div class="mini-box">
                        <span>Orçamento diário</span>
                        <strong><?php echo dinheiro($campanha_selecionada['orcamento_diario']); ?></strong>
                    </div>
                </section>

                <section class="metrics">
                    <article class="metric">
                        <span>Gasto da campanha</span>
                        <strong><?php echo dinheiro($tc['gasto']); ?></strong>
                    </article>

                    <article class="metric">
                        <span>Alcance</span>
                        <strong><?php echo numero($tc['alcance']); ?></strong>
                    </article>

                    <article class="metric">
                        <span>Impressões</span>
                        <strong><?php echo numero($tc['impressoes']); ?></strong>
                    </article>

                    <article class="metric">
                        <span>Anúncios</span>
                        <strong><?php echo esc($tc['anuncios']); ?></strong>
                    </article>
                </section>

                <div class="ads-title">
                    <h3>Anúncios / Criativos</h3>
                    <span><?php echo count($campanha_selecionada['anuncios']); ?> anúncio(s) nesta campanha</span>
                </div>

                <section class="ads-grid">
                    <?php foreach ($campanha_selecionada['anuncios'] as $anuncio): ?>
                        <?php
                            $eficiencia = $anuncio['gasto'] > 0 ? round($anuncio['alcance'] / $anuncio['gasto']) : 0;
                            $custo_mil_alcance = $anuncio['alcance'] > 0 ? ($anuncio['gasto'] / $anuncio['alcance']) * 1000 : 0;
                            $frequencia = $anuncio['alcance'] > 0 ? $anuncio['impressoes'] / $anuncio['alcance'] : 0;
                            $largura_alcance = max(($anuncio['alcance'] / $max_alcance) * 100, 3);
                            $largura_gasto = max(($anuncio['gasto'] / $max_gasto) * 100, 3);
                        ?>

                        <article class="ad-card">
                            <div class="ad-visual <?php echo esc($anuncio['plataforma']); ?>">
                                <span><?php echo esc(tipo_anuncio($anuncio['nome'])); ?></span>
                                <strong><?php echo esc(nome_plataforma($anuncio['plataforma'])); ?></strong>
                            </div>

                            <div class="ad-content">
                                <div class="ad-head">
                                    <div>
                                        <h3><?php echo esc($anuncio['nome']); ?></h3>
                                        <p>Criativo ID: <?php echo esc($anuncio['id']); ?></p>
                                        <p>Tempo de veiculação: conforme período selecionado</p>
                                    </div>

                                    <span class="rank">
                                        #<?php echo isset($ranking[$anuncio['id']]) ? esc($ranking[$anuncio['id']]) : '-'; ?> alcance
                                    </span>
                                </div>

                                <div class="ad-kpis">
                                    <div class="ad-kpi">
                                        <span>Gasto</span>
                                        <strong><?php echo dinheiro($anuncio['gasto']); ?></strong>
                                    </div>

                                    <div class="ad-kpi">
                                        <span>Alcance</span>
                                        <strong><?php echo numero($anuncio['alcance']); ?></strong>
                                    </div>

                                    <div class="ad-kpi">
                                        <span>Impressões</span>
                                        <strong><?php echo numero($anuncio['impressoes']); ?></strong>
                                    </div>

                                    <div class="ad-kpi">
                                        <span>Custo/1.000 alcance</span>
                                        <strong><?php echo dinheiro($custo_mil_alcance); ?></strong>
                                    </div>
                                </div>

                                <div class="bar-list">
                                    <div class="bar-row">
                                        <span>Alcance</span>
                                        <div class="bar-track">
                                            <i class="bar-fill" style="width: <?php echo esc(number_format($largura_alcance, 2, '.', '')); ?>%;"></i>
                                        </div>
                                        <strong><?php echo numero($anuncio['alcance']); ?></strong>
                                    </div>

                                    <div class="bar-row">
                                        <span>Gasto</span>
                                        <div class="bar-track">
                                            <i class="bar-fill spend" style="width: <?php echo esc(number_format($largura_gasto, 2, '.', '')); ?>%;"></i>
                                        </div>
                                        <strong><?php echo dinheiro($anuncio['gasto']); ?></strong>
                                    </div>
                                </div>

                                <div class="ad-insights">
                                    <div class="ad-insight">
                                        <span>Eficiência*</span>
                                        <strong><?php echo numero($eficiencia); ?> alcances por real</strong>
                                    </div>

                                    <div class="ad-insight">
                                        <span>Frequência</span>
                                        <strong><?php echo esc(number_format($frequencia, 2, ',', '.')); ?>x</strong>
                                    </div>

                                    <div class="ad-insight">
                                        <span>Plataforma</span>
                                        <strong><?php echo esc(nome_plataforma($anuncio['plataforma'])); ?></strong>
                                    </div>
                                </div>

                                <div class="note">
                                    * Quantidade de contas alcançadas para cada R$ 1,00 investido.
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>

            <?php endif; ?>
        </div>
    </main>

    <a class="floating-top" href="index.php?periodo=<?php echo esc($periodo_selecionado); ?>" aria-label="Voltar para empresas">↑</a>

<?php endif; ?>
</body>
</html>
'''

(base / "index.php").write_text(php, encoding="utf-8")

readme = """# Admin Dashboard - Código refeito do zero

Este pacote contém um `index.php` completo e limpo, refeito do zero em PHP + HTML.

## Login

E-mail: kevinnikolas417@gmail.com
Senha: 123456

## Estrutura

Login -> Empresas Ativas -> PARISVIU -> Campanhas -> Anúncios/Dashboards

## Como instalar

1. Extraia este ZIP.
2. Envie somente o arquivo `index.php` para a raiz do repositório GitHub.
3. Faça commit na branch `main`.
4. Aguarde o deploy da Hostinger.

## Observações

- Não use o código Python exibido pelo ChatGPT.
- Não envie o ZIP como arquivo final do site.
- Suba apenas o `index.php`.
- Este arquivo evita funções modernas do PHP para reduzir risco de erro 500.
"""

(base / "README.md").write_text(readme, encoding="utf-8")

zip_path = Path("/mnt/data/admin_dashboard_refeito_do_zero.zip")
if zip_path.exists():
    zip_path.unlink()

with zipfile.ZipFile(zip_path, "w", zipfile.ZIP_DEFLATED) as z:
    z.write(base / "index.php", arcname="index.php")
    z.write(base / "README.md", arcname="README.md")

print(f"Arquivo criado: {zip_path}")
print(f"index.php: {base / 'index.php'}")
print("Código refeito do zero e salvo apenas como PHP/HTML.")
