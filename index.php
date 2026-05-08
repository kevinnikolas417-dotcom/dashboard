from pathlib import Path
import zipfile

php = r'''<?php
session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

/*
|--------------------------------------------------------------------------
| PAINEL COM API META ATIVA
|--------------------------------------------------------------------------
| Admin inicial:
| E-mail: kevinnikolas417@gmail.com
| Senha: 123456
|
| IMPORTANTE:
| Para a API funcionar em tempo real, configure:
|
| 1. $META_ACCESS_TOKEN
| 2. $META_AD_ACCOUNT_ID
|
| O token precisa ter permissão para ler campanhas e insights da conta de anúncios.
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| CONFIGURAÇÃO DA API META
|--------------------------------------------------------------------------
*/

$META_API_VERSION = 'v25.0';
$META_AD_ACCOUNT_ID = '9729633853761104';

/*
  RECOMENDADO:
  Configure o token como variável de ambiente na hospedagem:
  META_ACCESS_TOKEN=seu_token

  ALTERNATIVA:
  Cole o token abaixo, entre as aspas.
*/
$META_ACCESS_TOKEN = getenv('META_ACCESS_TOKEN');

if (!$META_ACCESS_TOKEN) {
    $META_ACCESS_TOKEN = '';
}

/*
|--------------------------------------------------------------------------
| CONFIGURAÇÃO LOCAL
|--------------------------------------------------------------------------
*/

$DATA_DIR = __DIR__ . '/data';
$USERS_FILE = $DATA_DIR . '/usuarios.json';

if (!is_dir($DATA_DIR)) {
    mkdir($DATA_DIR, 0755, true);
}

$periodos = array(
    '7' => array('nome' => 'Últimos 7 dias', 'dias' => 7),
    '15' => array('nome' => 'Últimos 15 dias', 'dias' => 15),
    '30' => array('nome' => 'Últimos 30 dias', 'dias' => 30),
    '90' => array('nome' => 'Últimos 90 dias', 'dias' => 90)
);

$empresas_base = array(
    array(
        'slug' => 'parisviu',
        'nome' => 'PARISVIU',
        'status' => 'Ativa',
        'descricao' => 'Empresa conectada à conta de anúncios Meta.',
        'ad_account_id' => $META_AD_ACCOUNT_ID
    )
);

/*
|--------------------------------------------------------------------------
| FUNÇÕES BÁSICAS
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

function plataforma_nome($valor) {
    if ($valor == 'facebook') {
        return 'Facebook';
    }

    if ($valor == 'instagram') {
        return 'Instagram';
    }

    if ($valor == 'audience_network') {
        return 'Audience Network';
    }

    if ($valor == 'messenger') {
        return 'Messenger';
    }

    if ($valor == '' || $valor == null) {
        return 'Meta';
    }

    return ucfirst(str_replace('_', ' ', $valor));
}

function cents_para_reais($valor) {
    if ($valor === null || $valor === '') {
        return 0;
    }

    return ((float)$valor) / 100;
}

function periodo_range($dias) {
    $fim = date('Y-m-d');
    $inicio = date('Y-m-d', strtotime('-' . ((int)$dias - 1) . ' days'));

    return array(
        'since' => $inicio,
        'until' => $fim
    );
}

/*
|--------------------------------------------------------------------------
| FUNÇÕES DE USUÁRIOS
|--------------------------------------------------------------------------
*/

function carregar_usuarios($arquivo) {
    if (!file_exists($arquivo)) {
        return array();
    }

    $conteudo = file_get_contents($arquivo);
    $dados = json_decode($conteudo, true);

    if (!is_array($dados)) {
        return array();
    }

    return $dados;
}

function salvar_usuarios($arquivo, $usuarios) {
    file_put_contents($arquivo, json_encode($usuarios, JSON_PRETTY_PRINT));
}

function buscar_usuario_email($usuarios, $email) {
    foreach ($usuarios as $indice => $usuario) {
        if (strtolower($usuario['email']) == strtolower($email)) {
            return array('indice' => $indice, 'usuario' => $usuario);
        }
    }

    return null;
}

function buscar_usuario_id($usuarios, $id) {
    foreach ($usuarios as $indice => $usuario) {
        if ((string)$usuario['id'] == (string)$id) {
            return array('indice' => $indice, 'usuario' => $usuario);
        }
    }

    return null;
}

function criar_admin_inicial($arquivo) {
    $usuarios = carregar_usuarios($arquivo);
    $existe = false;

    foreach ($usuarios as $usuario) {
        if (strtolower($usuario['email']) == 'kevinnikolas417@gmail.com') {
            $existe = true;
            break;
        }
    }

    if (!$existe) {
        $usuarios[] = array(
            'id' => uniqid('user_'),
            'email' => 'kevinnikolas417@gmail.com',
            'senha_hash' => password_hash('123456', PASSWORD_DEFAULT),
            'tipo' => 'admin',
            'status' => 'ativo',
            'empresas' => array('parisviu'),
            'criado_em' => date('Y-m-d H:i:s')
        );

        salvar_usuarios($arquivo, $usuarios);
    }
}

/*
|--------------------------------------------------------------------------
| API META
|--------------------------------------------------------------------------
*/

function meta_request($path, $params, $token, $version) {
    if (!$token) {
        return array(
            'ok' => false,
            'error' => 'Token da Meta não configurado.',
            'data' => array()
        );
    }

    $base_url = 'https://graph.facebook.com/' . $version . '/' . ltrim($path, '/');

    $params['access_token'] = $token;
    $url = $base_url . '?' . http_build_query($params);

    $response = false;
    $http_code = 0;

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $erro = curl_error($ch);
            curl_close($ch);

            return array(
                'ok' => false,
                'error' => 'Erro cURL: ' . $erro,
                'data' => array()
            );
        }

        curl_close($ch);
    } else {
        $response = @file_get_contents($url);
        $http_code = $response ? 200 : 0;
    }

    if (!$response) {
        return array(
            'ok' => false,
            'error' => 'Não foi possível conectar à API da Meta.',
            'data' => array()
        );
    }

    $json = json_decode($response, true);

    if (!is_array($json)) {
        return array(
            'ok' => false,
            'error' => 'Resposta inválida da API da Meta.',
            'data' => array()
        );
    }

    if (isset($json['error'])) {
        $msg = isset($json['error']['message']) ? $json['error']['message'] : 'Erro desconhecido da Meta.';

        return array(
            'ok' => false,
            'error' => $msg,
            'data' => array()
        );
    }

    if ($http_code >= 400) {
        return array(
            'ok' => false,
            'error' => 'Erro HTTP ' . $http_code . ' na API da Meta.',
            'data' => array()
        );
    }

    return array(
        'ok' => true,
        'error' => '',
        'data' => isset($json['data']) ? $json['data'] : array(),
        'raw' => $json
    );
}

function meta_paginated_request($path, $params, $token, $version) {
    $primeira = meta_request($path, $params, $token, $version);

    if (!$primeira['ok']) {
        return $primeira;
    }

    $dados = $primeira['data'];
    $raw = isset($primeira['raw']) ? $primeira['raw'] : array();

    $next = isset($raw['paging']['next']) ? $raw['paging']['next'] : '';

    while ($next) {
        $response = false;

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $next);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            $response = curl_exec($ch);
            curl_close($ch);
        } else {
            $response = @file_get_contents($next);
        }

        if (!$response) {
            break;
        }

        $json = json_decode($response, true);

        if (!is_array($json) || isset($json['error'])) {
            break;
        }

        if (isset($json['data']) && is_array($json['data'])) {
            foreach ($json['data'] as $item) {
                $dados[] = $item;
            }
        }

        $next = isset($json['paging']['next']) ? $json['paging']['next'] : '';
    }

    return array(
        'ok' => true,
        'error' => '',
        'data' => $dados
    );
}

function meta_dashboard_empresa($empresa_base, $periodo, $token, $version) {
    $ad_account_id = $empresa_base['ad_account_id'];
    $account_path = 'act_' . $ad_account_id;

    $range = periodo_range($periodo['dias']);

    $retorno = array(
        'ok' => false,
        'error' => '',
        'empresa' => $empresa_base,
        'campanhas' => array(),
        'total' => array(
            'gasto' => 0,
            'alcance' => 0,
            'impressoes' => 0,
            'campanhas' => 0,
            'anuncios' => 0
        ),
        'range' => $range
    );

    if (!$token) {
        $retorno['error'] = 'Token da Meta não configurado. Configure META_ACCESS_TOKEN na hospedagem ou no topo do index.php.';
        return $retorno;
    }

    $campanhas_res = meta_paginated_request(
        $account_path . '/campaigns',
        array(
            'fields' => 'id,name,status,effective_status,daily_budget',
            'limit' => 100
        ),
        $token,
        $version
    );

    if (!$campanhas_res['ok']) {
        $retorno['error'] = $campanhas_res['error'];
        return $retorno;
    }

    $campanhas_meta = array();

    foreach ($campanhas_res['data'] as $campanha) {
        $status = isset($campanha['effective_status']) ? $campanha['effective_status'] : '';

        if ($status == 'ACTIVE') {
            $campanhas_meta[$campanha['id']] = array(
                'id' => $campanha['id'],
                'nome' => isset($campanha['name']) ? $campanha['name'] : 'Campanha sem nome',
                'status' => $status,
                'orcamento_diario' => isset($campanha['daily_budget']) ? cents_para_reais($campanha['daily_budget']) : 0,
                'anuncios' => array()
            );
        }
    }

    $ads_res = meta_paginated_request(
        $account_path . '/ads',
        array(
            'fields' => 'id,name,effective_status,created_time,campaign{id,name},adset{id,name}',
            'limit' => 200
        ),
        $token,
        $version
    );

    $ads_info = array();

    if ($ads_res['ok']) {
        foreach ($ads_res['data'] as $ad) {
            $ads_info[$ad['id']] = $ad;
        }
    }

    $insights_res = meta_paginated_request(
        $account_path . '/insights',
        array(
            'fields' => 'campaign_id,campaign_name,adset_id,adset_name,ad_id,ad_name,spend,reach,impressions,frequency,date_start,date_stop',
            'level' => 'ad',
            'breakdowns' => 'publisher_platform',
            'time_range' => json_encode($range),
            'limit' => 500
        ),
        $token,
        $version
    );

    if (!$insights_res['ok']) {
        $retorno['error'] = $insights_res['error'];
        return $retorno;
    }

    foreach ($insights_res['data'] as $linha) {
        $campaign_id = isset($linha['campaign_id']) ? $linha['campaign_id'] : '';

        if ($campaign_id == '') {
            continue;
        }

        if (!isset($campanhas_meta[$campaign_id])) {
            continue;
        }

        $ad_id = isset($linha['ad_id']) ? $linha['ad_id'] : '';
        $plataforma = isset($linha['publisher_platform']) ? $linha['publisher_platform'] : 'meta';
        $ad_key = $ad_id . '_' . $plataforma;

        $criado_em = '';

        if (isset($ads_info[$ad_id]) && isset($ads_info[$ad_id]['created_time'])) {
            $criado_em = $ads_info[$ad_id]['created_time'];
        }

        $campanhas_meta[$campaign_id]['anuncios'][$ad_key] = array(
            'id' => $ad_id,
            'nome' => isset($linha['ad_name']) ? $linha['ad_name'] : 'Anúncio sem nome',
            'conjunto' => isset($linha['adset_name']) ? $linha['adset_name'] : '',
            'plataforma' => $plataforma,
            'gasto' => isset($linha['spend']) ? (float)$linha['spend'] : 0,
            'alcance' => isset($linha['reach']) ? (int)$linha['reach'] : 0,
            'impressoes' => isset($linha['impressions']) ? (int)$linha['impressions'] : 0,
            'frequencia' => isset($linha['frequency']) ? (float)$linha['frequency'] : 0,
            'criado_em' => $criado_em
        );
    }

    foreach ($campanhas_meta as $id => $campanha) {
        $campanha_total = array(
            'gasto' => 0,
            'alcance' => 0,
            'impressoes' => 0,
            'anuncios' => 0
        );

        $anuncios_array = array();

        foreach ($campanha['anuncios'] as $anuncio) {
            $campanha_total['gasto'] += $anuncio['gasto'];
            $campanha_total['alcance'] += $anuncio['alcance'];
            $campanha_total['impressoes'] += $anuncio['impressoes'];
            $campanha_total['anuncios']++;

            $anuncios_array[] = $anuncio;
        }

        $campanha['anuncios'] = $anuncios_array;
        $campanha['total'] = $campanha_total;

        $retorno['campanhas'][] = $campanha;
        $retorno['total']['gasto'] += $campanha_total['gasto'];
        $retorno['total']['alcance'] += $campanha_total['alcance'];
        $retorno['total']['impressoes'] += $campanha_total['impressoes'];
        $retorno['total']['anuncios'] += $campanha_total['anuncios'];
    }

    $retorno['total']['campanhas'] = count($retorno['campanhas']);
    $retorno['ok'] = true;

    return $retorno;
}

/*
|--------------------------------------------------------------------------
| USUÁRIOS E SESSÃO
|--------------------------------------------------------------------------
*/

criar_admin_inicial($USERS_FILE);
$usuarios = carregar_usuarios($USERS_FILE);

$erro = '';
$mensagem = '';

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'criar_conta') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $senha = isset($_POST['senha']) ? trim($_POST['senha']) : '';

    if ($email == '' || $senha == '') {
        $erro = 'Preencha o e-mail e a senha pessoal.';
    } elseif (buscar_usuario_email($usuarios, $email)) {
        $erro = 'Este e-mail já possui uma conta.';
    } else {
        $usuarios[] = array(
            'id' => uniqid('user_'),
            'email' => $email,
            'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
            'tipo' => 'usuario',
            'status' => 'pendente',
            'empresas' => array(),
            'criado_em' => date('Y-m-d H:i:s')
        );

        salvar_usuarios($USERS_FILE, $usuarios);
        $usuarios = carregar_usuarios($USERS_FILE);
        $mensagem = 'Conta criada com sucesso. Aguarde o admin aprovar seu acesso.';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'login') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $senha = isset($_POST['senha']) ? trim($_POST['senha']) : '';

    $busca = buscar_usuario_email($usuarios, $email);

    if (!$busca) {
        $erro = 'E-mail ou senha inválidos.';
    } else {
        $usuario = $busca['usuario'];

        if (!password_verify($senha, $usuario['senha_hash'])) {
            $erro = 'E-mail ou senha inválidos.';
        } elseif ($usuario['status'] != 'ativo') {
            $erro = 'Sua conta ainda não foi aprovada pelo admin.';
        } else {
            $_SESSION['usuario_id'] = $usuario['id'];
            header('Location: index.php');
            exit;
        }
    }
}

$logado = isset($_SESSION['usuario_id']);
$usuario_logado = null;
$is_admin = false;

if ($logado) {
    $busca_logado = buscar_usuario_id($usuarios, $_SESSION['usuario_id']);

    if (!$busca_logado) {
        session_destroy();
        header('Location: index.php');
        exit;
    }

    $usuario_logado = $busca_logado['usuario'];
    $is_admin = ($usuario_logado['tipo'] == 'admin');
}

if ($logado && $is_admin && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'salvar_usuario') {
    $id_usuario = isset($_POST['id_usuario']) ? $_POST['id_usuario'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'pendente';
    $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : 'usuario';
    $empresas_usuario = isset($_POST['empresas']) && is_array($_POST['empresas']) ? $_POST['empresas'] : array();

    $busca = buscar_usuario_id($usuarios, $id_usuario);

    if ($busca) {
        $indice = $busca['indice'];
        $usuarios[$indice]['status'] = $status;
        $usuarios[$indice]['tipo'] = $tipo;
        $usuarios[$indice]['empresas'] = $empresas_usuario;

        salvar_usuarios($USERS_FILE, $usuarios);
        $usuarios = carregar_usuarios($USERS_FILE);
        $mensagem = 'Usuário atualizado com sucesso.';
    }
}

/*
|--------------------------------------------------------------------------
| ROTAS
|--------------------------------------------------------------------------
*/

$pagina = isset($_GET['pagina']) ? $_GET['pagina'] : 'login';

$periodo_selecionado = isset($_GET['periodo']) ? $_GET['periodo'] : '7';

if (!isset($periodos[$periodo_selecionado])) {
    $periodo_selecionado = '7';
}

$periodo_atual = $periodos[$periodo_selecionado];

$empresa_slug = isset($_GET['empresa']) ? $_GET['empresa'] : '';
$campanha_id = isset($_GET['campanha']) ? $_GET['campanha'] : '';

$empresas_liberadas = array();

if ($logado && $is_admin) {
    $empresas_liberadas = $empresas_base;
} elseif ($logado && $usuario_logado) {
    foreach ($empresas_base as $empresa) {
        if (in_array($empresa['slug'], $usuario_logado['empresas'])) {
            $empresas_liberadas[] = $empresa;
        }
    }
}

$empresa_base_selecionada = null;

if ($empresa_slug != '') {
    $empresa_base_selecionada = buscar_empresa($empresas_liberadas, $empresa_slug);
}

$dashboard = null;
$campanha_selecionada = null;

if ($empresa_base_selecionada) {
    $dashboard = meta_dashboard_empresa($empresa_base_selecionada, $periodo_atual, $META_ACCESS_TOKEN, $META_API_VERSION);

    if ($dashboard['ok'] && $campanha_id != '') {
        foreach ($dashboard['campanhas'] as $campanha) {
            if ((string)$campanha['id'] == (string)$campanha_id) {
                $campanha_selecionada = $campanha;
                break;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel Meta Ads</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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
            --warning: #92400e;
            --warning-bg: #fffbeb;
            --shadow: 0 14px 34px rgba(15, 23, 42, .10);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(91, 110, 225, .18), transparent 28rem),
                linear-gradient(135deg, #f8fbff 0%, #edf2fb 100%);
        }

        a { color: inherit; }

        .shell {
            width: min(1160px, calc(100% - 28px));
            margin: 0 auto;
        }

        .auth-page {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 22px 14px;
        }

        .auth-card {
            width: min(480px, 100%);
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 28px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .auth-header {
            color: #ffffff;
            padding: 28px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }

        .auth-header h1 {
            margin: 0 0 8px;
            font-size: 2rem;
        }

        .auth-header p {
            margin: 0;
            color: rgba(255, 255, 255, .86);
            line-height: 1.45;
        }

        .auth-form {
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

        .field input,
        .field select {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 13px 14px;
            font-size: 1rem;
            outline: none;
            background: #ffffff;
        }

        .button {
            border: none;
            cursor: pointer;
            color: #ffffff;
            border-radius: 16px;
            padding: 14px;
            font-size: 1rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            text-decoration: none;
            display: inline-flex;
            justify-content: center;
            align-items: center;
        }

        .auth-link {
            text-align: center;
            color: var(--muted);
            font-size: .92rem;
            font-weight: 700;
        }

        .auth-link a {
            color: #3730a3;
            font-weight: 900;
            text-decoration: none;
        }

        .alert {
            border-radius: 14px;
            padding: 12px 14px;
            font-weight: 800;
            margin-bottom: 14px;
        }

        .alert.error {
            color: var(--danger);
            background: var(--danger-bg);
            border: 1px solid #fecaca;
        }

        .alert.success {
            color: var(--green);
            background: var(--green-bg);
            border: 1px solid #bbf7d0;
        }

        .alert.warning {
            color: var(--warning);
            background: var(--warning-bg);
            border: 1px solid #fde68a;
        }

        header { padding: 18px 0 14px; }

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

        main { padding: 8px 0 90px; }

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

        .card,
        .user-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: var(--shadow);
            padding: 16px;
            display: grid;
            gap: 14px;
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
        }

        .card h2,
        .user-card h3 {
            margin: 0;
            font-size: 1rem;
            line-height: 1.28;
        }

        .card p,
        .heading p,
        .ad-head p,
        .user-card p {
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

        .status-badge.warning {
            color: var(--warning);
            background: var(--warning-bg);
            border-color: #fde68a;
        }

        .heading {
            background: #ffffff;
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
        }

        .ad-visual.instagram {
            background: linear-gradient(135deg, #be185d, #f97316);
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

        .admin-form {
            display: grid;
            gap: 12px;
        }

        .checkbox-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .checkbox-pill {
            border: 1px solid var(--border);
            background: #ffffff;
            border-radius: 999px;
            padding: 9px 12px;
            font-weight: 800;
            color: #334155;
        }

        .checkbox-pill input {
            margin-right: 6px;
        }

        @media (min-width: 680px) {
            header { padding-top: 30px; }

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

    <?php if ($pagina == 'criar-conta'): ?>

        <section class="auth-page">
            <div class="auth-card">
                <div class="auth-header">
                    <h1>Criar conta</h1>
                    <p>Informe seu e-mail e sua senha pessoal. O admin precisará aprovar seu acesso.</p>
                </div>

                <form class="auth-form" method="post" action="index.php?pagina=criar-conta">
                    <?php if ($erro): ?>
                        <div class="alert error"><?php echo esc($erro); ?></div>
                    <?php endif; ?>

                    <?php if ($mensagem): ?>
                        <div class="alert success"><?php echo esc($mensagem); ?></div>
                    <?php endif; ?>

                    <input type="hidden" name="acao" value="criar_conta">

                    <div class="field">
                        <label for="email_cadastro">E-mail</label>
                        <input type="email" id="email_cadastro" name="email" placeholder="seuemail@exemplo.com" required>
                    </div>

                    <div class="field">
                        <label for="senha_cadastro">Senha pessoal</label>
                        <input type="password" id="senha_cadastro" name="senha" placeholder="Crie sua senha pessoal" required>
                    </div>

                    <button class="button" type="submit">Criar conta</button>

                    <div class="auth-link">
                        Já tem uma conta? <a href="index.php">Entrar</a>
                    </div>
                </form>
            </div>
        </section>

    <?php else: ?>

        <section class="auth-page">
            <div class="auth-card">
                <div class="auth-header">
                    <h1>Entrar</h1>
                    <p>Acesse sua conta para visualizar as empresas liberadas para você.</p>
                </div>

                <form class="auth-form" method="post" action="index.php">
                    <?php if ($erro): ?>
                        <div class="alert error"><?php echo esc($erro); ?></div>
                    <?php endif; ?>

                    <?php if ($mensagem): ?>
                        <div class="alert success"><?php echo esc($mensagem); ?></div>
                    <?php endif; ?>

                    <input type="hidden" name="acao" value="login">

                    <div class="field">
                        <label for="email_login">E-mail</label>
                        <input type="email" id="email_login" name="email" placeholder="seuemail@exemplo.com" required>
                    </div>

                    <div class="field">
                        <label for="senha_login">Senha</label>
                        <input type="password" id="senha_login" name="senha" placeholder="Digite sua senha" required>
                    </div>

                    <button class="button" type="submit">Entrar</button>

                    <div class="auth-link">
                        Ainda não tem uma conta? <a href="index.php?pagina=criar-conta">Crie a sua</a>
                    </div>
                </form>
            </div>
        </section>

    <?php endif; ?>

<?php else: ?>

    <header>
        <div class="shell">
            <section class="hero">
                <div>
                    <h1>Painel Meta Ads</h1>
                    <p>
                        <?php if ($is_admin): ?>
                            Admin conectado. A API da Meta é consultada a cada carregamento da página.
                        <?php else: ?>
                            Usuário conectado. Você visualiza somente as empresas liberadas pelo admin.
                        <?php endif; ?>
                    </p>
                </div>

                <div class="hero-meta">
                    <div>
                        <span>E-mail</span>
                        <strong><?php echo esc($usuario_logado['email']); ?></strong>
                    </div>

                    <div>
                        <span>Perfil</span>
                        <strong><?php echo esc($usuario_logado['tipo']); ?></strong>
                    </div>

                    <div>
                        <span>API</span>
                        <strong><?php echo $META_ACCESS_TOKEN ? 'Meta ativa' : 'Token pendente'; ?></strong>
                    </div>
                </div>
            </section>
        </div>
    </header>

    <main>
        <div class="shell">
            <nav class="nav">
                <a href="index.php?periodo=<?php echo esc($periodo_selecionado); ?>">Empresas</a>

                <?php if ($is_admin): ?>
                    <a href="index.php?pagina=usuarios&periodo=<?php echo esc($periodo_selecionado); ?>">Usuários</a>
                <?php endif; ?>

                <?php foreach ($empresas_liberadas as $empresa_nav): ?>
                    <a href="index.php?periodo=<?php echo esc($periodo_selecionado); ?>&empresa=<?php echo esc($empresa_nav['slug']); ?>">
                        <?php echo esc($empresa_nav['nome']); ?>
                    </a>
                <?php endforeach; ?>

                <a class="logout" href="index.php?logout=1">Sair</a>
            </nav>

            <?php if ($mensagem): ?>
                <div class="alert success"><?php echo esc($mensagem); ?></div>
            <?php endif; ?>

            <?php if ($erro): ?>
                <div class="alert error"><?php echo esc($erro); ?></div>
            <?php endif; ?>

            <?php if ($pagina == 'usuarios' && $is_admin): ?>

                <section class="heading">
                    <div>
                        <a class="back-link" href="index.php?periodo=<?php echo esc($periodo_selecionado); ?>">← Voltar</a>
                        <h2>Gerenciar usuários</h2>
                        <p>Aprove contas, defina admin ou usuário e escolha as empresas responsáveis.</p>
                    </div>
                </section>

                <section class="grid">
                    <?php foreach ($usuarios as $usuario_item): ?>
                        <article class="user-card">
                            <div>
                                <h3><?php echo esc($usuario_item['email']); ?></h3>
                                <p>Criado em: <?php echo esc($usuario_item['criado_em']); ?></p>

                                <?php if ($usuario_item['status'] == 'ativo'): ?>
                                    <span class="status-badge">Ativo</span>
                                <?php else: ?>
                                    <span class="status-badge warning">Pendente</span>
                                <?php endif; ?>
                            </div>

                            <form class="admin-form" method="post" action="index.php?pagina=usuarios&periodo=<?php echo esc($periodo_selecionado); ?>">
                                <input type="hidden" name="acao" value="salvar_usuario">
                                <input type="hidden" name="id_usuario" value="<?php echo esc($usuario_item['id']); ?>">

                                <div class="field">
                                    <label>Status</label>
                                    <select name="status">
                                        <option value="pendente" <?php echo $usuario_item['status'] == 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                        <option value="ativo" <?php echo $usuario_item['status'] == 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                    </select>
                                </div>

                                <div class="field">
                                    <label>Tipo de acesso</label>
                                    <select name="tipo">
                                        <option value="usuario" <?php echo $usuario_item['tipo'] == 'usuario' ? 'selected' : ''; ?>>Usuário</option>
                                        <option value="admin" <?php echo $usuario_item['tipo'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>

                                <div class="field">
                                    <label>Empresas responsáveis</label>
                                    <div class="checkbox-list">
                                        <?php foreach ($empresas_base as $empresa_opcao): ?>
                                            <?php $checked = in_array($empresa_opcao['slug'], $usuario_item['empresas']) ? 'checked' : ''; ?>

                                            <label class="checkbox-pill">
                                                <input type="checkbox" name="empresas[]" value="<?php echo esc($empresa_opcao['slug']); ?>" <?php echo $checked; ?>>
                                                <?php echo esc($empresa_opcao['nome']); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <button class="button" type="submit">Salvar usuário</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </section>

            <?php else: ?>

                <section class="date-filter">
                    <div class="date-filter-title">Filtrar período</div>

                    <div class="date-filter-buttons">
                        <?php foreach ($periodos as $chave_periodo => $periodo): ?>
                            <?php
                                $url_periodo = 'index.php?periodo=' . urlencode($chave_periodo);

                                if ($empresa_base_selecionada) {
                                    $url_periodo .= '&empresa=' . urlencode($empresa_base_selecionada['slug']);
                                }

                                if ($campanha_selecionada) {
                                    $url_periodo .= '&campanha=' . urlencode($campanha_selecionada['id']);
                                }

                                $classe = ($chave_periodo == $periodo_selecionado) ? 'active' : '';
                            ?>

                            <a class="<?php echo esc($classe); ?>" href="<?php echo esc($url_periodo); ?>">
                                <?php echo esc($periodo['nome']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <?php if (count($empresas_liberadas) == 0): ?>

                    <div class="alert error">
                        Nenhuma empresa foi liberada para sua conta. Aguarde o admin designar uma empresa.
                    </div>

                <?php elseif (!$empresa_base_selecionada): ?>

                    <section class="metrics">
                        <article class="metric">
                            <span>Empresas</span>
                            <strong><?php echo count($empresas_liberadas); ?></strong>
                        </article>

                        <article class="metric">
                            <span>API</span>
                            <strong><?php echo $META_ACCESS_TOKEN ? 'Ativa' : 'Pendente'; ?></strong>
                        </article>

                        <article class="metric">
                            <span>Conta Meta</span>
                            <strong><?php echo esc($META_AD_ACCOUNT_ID); ?></strong>
                        </article>

                        <article class="metric">
                            <span>Período</span>
                            <strong><?php echo esc($periodo_atual['nome']); ?></strong>
                        </article>
                    </section>

                    <?php if (!$META_ACCESS_TOKEN): ?>
                        <div class="alert warning">
                            Configure o token da Meta no topo do arquivo ou como variável de ambiente META_ACCESS_TOKEN para ativar dados em tempo real.
                        </div>
                    <?php endif; ?>

                    <section class="grid">
                        <?php foreach ($empresas_liberadas as $empresa): ?>
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
                                            <span>Conta Meta</span>
                                            <strong><?php echo esc($empresa['ad_account_id']); ?></strong>
                                        </div>

                                        <div class="mini-box">
                                            <span>Atualização</span>
                                            <strong>Tempo real</strong>
                                        </div>
                                    </div>

                                    <div class="open-label">Abrir empresa</div>
                                </article>
                            </a>
                        <?php endforeach; ?>
                    </section>

                <?php elseif ($empresa_base_selecionada && !$campanha_selecionada): ?>

                    <section class="heading">
                        <div>
                            <a class="back-link" href="index.php?periodo=<?php echo esc($periodo_selecionado); ?>">← Voltar para empresas</a>
                            <h2><?php echo esc($empresa_base_selecionada['nome']); ?></h2>
                            <p>Dados puxados diretamente da API da Meta no carregamento da página.</p>
                        </div>

                        <div class="mini-box">
                            <span>Conta Meta</span>
                            <strong><?php echo esc($empresa_base_selecionada['ad_account_id']); ?></strong>
                        </div>
                    </section>

                    <?php if (!$dashboard || !$dashboard['ok']): ?>
                        <div class="alert error">
                            <?php echo esc($dashboard ? $dashboard['error'] : 'Não foi possível carregar os dados da Meta.'); ?>
                        </div>
                    <?php else: ?>

                        <div class="period-chip">
                            <?php echo esc($periodo_atual['nome']); ?>:
                            <?php echo esc($dashboard['range']['since']); ?> a <?php echo esc($dashboard['range']['until']); ?>
                        </div>

                        <section class="metrics">
                            <article class="metric">
                                <span>Gasto</span>
                                <strong><?php echo dinheiro($dashboard['total']['gasto']); ?></strong>
                            </article>

                            <article class="metric">
                                <span>Alcance</span>
                                <strong><?php echo numero($dashboard['total']['alcance']); ?></strong>
                            </article>

                            <article class="metric">
                                <span>Campanhas ativas</span>
                                <strong><?php echo numero($dashboard['total']['campanhas']); ?></strong>
                            </article>

                            <article class="metric">
                                <span>Anúncios</span>
                                <strong><?php echo numero($dashboard['total']['anuncios']); ?></strong>
                            </article>
                        </section>

                        <?php if (count($dashboard['campanhas']) == 0): ?>
                            <div class="alert warning">
                                Nenhuma campanha ativa encontrada na API da Meta para este período.
                            </div>
                        <?php endif; ?>

                        <section class="grid">
                            <?php foreach ($dashboard['campanhas'] as $campanha): ?>
                                <a class="card-link" href="index.php?periodo=<?php echo esc($periodo_selecionado); ?>&empresa=<?php echo esc($empresa_base_selecionada['slug']); ?>&campanha=<?php echo esc($campanha['id']); ?>">
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
                                                <strong><?php echo dinheiro($campanha['total']['gasto']); ?></strong>
                                            </div>

                                            <div class="mini-box">
                                                <span>Alcance</span>
                                                <strong><?php echo numero($campanha['total']['alcance']); ?></strong>
                                            </div>

                                            <div class="mini-box">
                                                <span>Anúncios</span>
                                                <strong><?php echo numero($campanha['total']['anuncios']); ?></strong>
                                            </div>
                                        </div>

                                        <div class="open-label">Abrir anúncios</div>
                                    </article>
                                </a>
                            <?php endforeach; ?>
                        </section>

                    <?php endif; ?>

                <?php elseif ($empresa_base_selecionada && $campanha_selecionada): ?>

                    <section class="heading">
                        <div>
                            <a class="back-link" href="index.php?periodo=<?php echo esc($periodo_selecionado); ?>&empresa=<?php echo esc($empresa_base_selecionada['slug']); ?>">← Voltar para campanhas</a>
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
                            <span>Gasto</span>
                            <strong><?php echo dinheiro($campanha_selecionada['total']['gasto']); ?></strong>
                        </article>

                        <article class="metric">
                            <span>Alcance</span>
                            <strong><?php echo numero($campanha_selecionada['total']['alcance']); ?></strong>
                        </article>

                        <article class="metric">
                            <span>Impressões</span>
                            <strong><?php echo numero($campanha_selecionada['total']['impressoes']); ?></strong>
                        </article>

                        <article class="metric">
                            <span>Anúncios</span>
                            <strong><?php echo numero($campanha_selecionada['total']['anuncios']); ?></strong>
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
                                $custo_mil = $anuncio['alcance'] > 0 ? ($anuncio['gasto'] / $anuncio['alcance']) * 1000 : 0;
                                $largura_alcance = max(($anuncio['alcance'] / max($campanha_selecionada['total']['alcance'], 1)) * 100, 3);
                                $largura_gasto = max(($anuncio['gasto'] / max($campanha_selecionada['total']['gasto'], 1)) * 100, 3);
                            ?>

                            <article class="ad-card">
                                <div class="ad-visual <?php echo esc($anuncio['plataforma']); ?>">
                                    <span><?php echo esc(tipo_criativo($anuncio['nome'])); ?></span>
                                    <strong><?php echo esc(plataforma_nome($anuncio['plataforma'])); ?></strong>
                                </div>

                                <div class="ad-content">
                                    <div class="ad-head">
                                        <div>
                                            <h3><?php echo esc($anuncio['nome']); ?></h3>
                                            <p>Criativo ID: <?php echo esc($anuncio['id']); ?></p>
                                            <p>Conjunto: <?php echo esc($anuncio['conjunto']); ?></p>
                                            <p>Criado em: <?php echo esc($anuncio['criado_em']); ?></p>
                                        </div>

                                        <span class="rank">
                                            Plataforma: <?php echo esc(plataforma_nome($anuncio['plataforma'])); ?>
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
                                            <strong><?php echo dinheiro($custo_mil); ?></strong>
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
                                            <strong><?php echo esc(number_format($anuncio['frequencia'], 2, ',', '.')); ?>x</strong>
                                        </div>

                                        <div class="ad-insight">
                                            <span>Plataforma</span>
                                            <strong><?php echo esc(plataforma_nome($anuncio['plataforma'])); ?></strong>
                                        </div>
                                    </div>

                                    <div class="note">
                                        * Dados carregados diretamente da API da Meta no momento da abertura da página.
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </section>

                <?php endif; ?>

            <?php endif; ?>
        </div>
    </main>

<?php endif; ?>

</body>
</html>
'''

# Validate
for marker in ["from pathlib import Path", "import zipfile", "shutil", "python_user_visible", "php = r'''"]:
    if marker in php:
        raise ValueError(f"Marcador proibido encontrado: {marker}")

if not php.startswith("<?php"):
    raise ValueError("Arquivo não começa com <?php")

out = Path("/mnt/data/index_meta_api_tempo_real.php")
out.write_text(php, encoding="utf-8")

zip_path = Path("/mnt/data/index_meta_api_tempo_real.zip")
if zip_path.exists():
    zip_path.unlink()

with zipfile.ZipFile(zip_path, "w", zipfile.ZIP_DEFLATED) as z:
    z.write(out, arcname="index.php")
    z.writestr("README.txt", """PAINEL META API TEMPO REAL

1. Suba o index.php para a raiz do GitHub.
2. Configure o token da Meta:
   - Preferencial: variável de ambiente META_ACCESS_TOKEN
   - Alternativa: cole o token na variável $META_ACCESS_TOKEN no topo do arquivo.
3. Conta de anúncios configurada:
   9729633853761104

Login admin inicial:
E-mail: kevinnikolas417@gmail.com
Senha: 123456

O sistema consulta a API da Meta a cada carregamento da página.
""")

print("Arquivo PHP criado:", out)
print("ZIP criado:", zip_path)
print("Linhas:", len(php.splitlines()))
print("Começa com:", php[:5])
