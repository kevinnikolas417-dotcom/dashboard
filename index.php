<?php
require_once __DIR__ . '/app/bootstrap.php';

if (isset($_GET['logout'])) {
    $auth->logout();
    header('Location: index.php');
    exit;
}

$page = $_GET['page'] ?? 'home';
$user = $auth->user();
$isLogged = $user !== null;
$isAdmin = $auth->isAdmin($user);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $result = $auth->login(trim($_POST['email'] ?? ''), trim($_POST['password'] ?? ''));

        if ($result['ok']) {
            header('Location: index.php');
            exit;
        }

        $flashError = $result['message'];
    }

    if ($action === 'register') {
        $result = $auth->register(trim($_POST['email'] ?? ''), trim($_POST['password'] ?? ''));
        $flashSuccess = $result['ok'] ? $result['message'] : '';
        $flashError = $result['ok'] ? '' : $result['message'];
    }

    if ($isLogged && $isAdmin && $action === 'save_user') {
        $userId = (int)$_POST['user_id'];
        $status = $_POST['status'] ?? 'pendente';
        $type = $_POST['type'] ?? 'usuario';
        $companies = isset($_POST['companies']) && is_array($_POST['companies']) ? $_POST['companies'] : [];

        $db->execute('UPDATE usuarios SET status = ?, tipo = ? WHERE id = ?', 'ssi', [$status, $type, $userId]);
        $db->execute('DELETE FROM usuario_empresas WHERE usuario_id = ?', 'i', [$userId]);

        foreach ($companies as $companyId) {
            $db->execute('INSERT IGNORE INTO usuario_empresas (usuario_id, empresa_id) VALUES (?, ?)', 'ii', [$userId, (int)$companyId]);
        }

        $flashSuccess = 'Usuário atualizado com sucesso.';
    }

    if ($isLogged && $isAdmin && $action === 'import_report') {
        try {
            $total = $importer->import(
                (int)($_POST['empresa_id'] ?? 0),
                !empty($_POST['conta_anuncio_id']) ? (int)$_POST['conta_anuncio_id'] : null,
                (int)$user['id'],
                $_FILES['file'] ?? []
            );

            $flashSuccess = 'Relatório importado com sucesso. Linhas processadas: ' . $total . '.';
            $page = 'import';
        } catch (Exception $exception) {
            $flashError = $exception->getMessage();
            $page = 'import';
        }
    }
}

$user = $auth->user();
$isLogged = $user !== null;
$isAdmin = $auth->isAdmin($user);

function user_can_access_company(array $companies, int $companyId, bool $isAdmin): bool
{
    if ($isAdmin) {
        return true;
    }

    foreach ($companies as $company) {
        if ((int)$company['id'] === $companyId) {
            return true;
        }
    }

    return false;
}

$companies = $isLogged ? $dashboard->companiesForUser($user, $isAdmin) : [];
$companyId = isset($_GET['company']) ? (int)$_GET['company'] : 0;
$campaignId = isset($_GET['campaign']) ? (int)$_GET['campaign'] : 0;

if ($companyId && !user_can_access_company($companies, $companyId, $isAdmin)) {
    $companyId = 0;
    $campaignId = 0;
}

?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel Multiempresas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php if (!$isLogged): ?>
    <section class="auth-page">
        <div class="auth-card">
            <?php if ($page === 'register'): ?>
                <div class="auth-header">
                    <h1>Criar conta</h1>
                    <p>Informe seu e-mail e sua senha pessoal. O admin aprovará seu acesso.</p>
                </div>

                <form class="auth-form" method="post">
                    <?php if ($flashError): ?><div class="alert error"><?php echo esc($flashError); ?></div><?php endif; ?>
                    <?php if ($flashSuccess): ?><div class="alert success"><?php echo esc($flashSuccess); ?></div><?php endif; ?>

                    <input type="hidden" name="action" value="register">

                    <label>
                        <span>E-mail</span>
                        <input type="email" name="email" required>
                    </label>

                    <label>
                        <span>Senha pessoal</span>
                        <input type="password" name="password" required>
                    </label>

                    <button class="button" type="submit">Criar conta</button>

                    <p class="auth-link">Já tem uma conta? <a href="index.php">Entrar</a></p>
                </form>
            <?php else: ?>
                <div class="auth-header">
                    <h1>Entrar</h1>
                    <p>Acesse empresas, campanhas e relatórios liberados para sua conta.</p>
                </div>

                <form class="auth-form" method="post">
                    <?php if ($flashError): ?><div class="alert error"><?php echo esc($flashError); ?></div><?php endif; ?>
                    <?php if ($flashSuccess): ?><div class="alert success"><?php echo esc($flashSuccess); ?></div><?php endif; ?>

                    <input type="hidden" name="action" value="login">

                    <label>
                        <span>E-mail</span>
                        <input type="email" name="email" required>
                    </label>

                    <label>
                        <span>Senha</span>
                        <input type="password" name="password" required>
                    </label>

                    <button class="button" type="submit">Entrar</button>

                    <p class="auth-link">Ainda não tem uma conta? <a href="index.php?page=register">Crie a sua</a></p>
                </form>
            <?php endif; ?>
        </div>
    </section>
<?php else: ?>
    <header>
        <div class="shell">
            <section class="hero">
                <div>
                    <h1>Painel Multiempresas</h1>
                    <p>
                        <?php echo $isAdmin
                            ? 'Admin conectado. Envie planilhas por empresa e atualize o banco de dados.'
                            : 'Usuário conectado. Você visualiza somente empresas liberadas pelo admin.'; ?>
                    </p>
                </div>

                <div class="hero-card">
                    <span>E-mail</span>
                    <strong><?php echo esc($user['email']); ?></strong>

                    <span>Perfil</span>
                    <strong><?php echo esc($user['tipo']); ?></strong>

                    <span>Fonte dos dados</span>
                    <strong>Banco MySQL</strong>
                </div>
            </section>
        </div>
    </header>

    <main>
        <div class="shell">
            <nav class="nav">
                <a href="index.php">Empresas</a>

                <?php if ($isAdmin): ?>
                    <a href="index.php?page=import">Importar relatório</a>
                    <a href="index.php?page=users">Usuários</a>
                    <a href="index.php?page=imports">Importações</a>
                <?php endif; ?>

                <a class="logout" href="index.php?logout=1">Sair</a>
            </nav>

            <?php if ($flashSuccess): ?><div class="alert success"><?php echo esc($flashSuccess); ?></div><?php endif; ?>
            <?php if ($flashError): ?><div class="alert error"><?php echo esc($flashError); ?></div><?php endif; ?>

            <?php if ($page === 'import' && $isAdmin): ?>
                <section class="panel">
                    <div>
                        <h2>Importar relatório</h2>
                        <p>Escolha a empresa, a conta de anúncio e envie o XLSX ou CSV exportado da Meta.</p>
                    </div>

                    <form class="form-grid" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="import_report">

                        <label>
                            <span>Empresa</span>
                            <select name="empresa_id" required>
                                <option value="">Selecione</option>
                                <?php foreach ($dashboard->companiesForUser($user, true) as $company): ?>
                                    <option value="<?php echo esc($company['id']); ?>"><?php echo esc($company['nome']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label>
                            <span>Conta de anúncio</span>
                            <select name="conta_anuncio_id">
                                <option value="">Usar primeira conta da empresa</option>
                                <?php foreach ($dashboard->accounts() as $account): ?>
                                    <option value="<?php echo esc($account['id']); ?>">
                                        <?php echo esc($account['empresa'] . ' — ' . $account['nome_conta'] . ' (' . $account['conta_anuncio_id'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label>
                            <span>Planilha XLSX ou CSV</span>
                            <input type="file" name="file" accept=".xlsx,.csv" required>
                        </label>

                        <button class="button" type="submit">Enviar e atualizar banco</button>
                    </form>

                    <div class="notice">
                        O importador reconhece colunas como: Nome da campanha, ID da campanha, Status de veiculação,
                        Tipo de resultado, Resultados, Valor usado, Alcance, Impressões, Conjunto, Anúncio e Plataforma.
                    </div>
                </section>

            <?php elseif ($page === 'users' && $isAdmin): ?>
                <section class="panel">
                    <h2>Gerenciar usuários</h2>
                    <p>Aprove usuários, defina perfil e vincule empresas.</p>
                </section>

                <section class="grid">
                    <?php foreach ($dashboard->users() as $listedUser): ?>
                        <?php $selectedCompanies = $dashboard->userCompanyIds((int)$listedUser['id']); ?>

                        <article class="card">
                            <h3><?php echo esc($listedUser['email']); ?></h3>
                            <p>Criado em: <?php echo esc($listedUser['criado_em']); ?></p>

                            <form class="form-grid" method="post">
                                <input type="hidden" name="action" value="save_user">
                                <input type="hidden" name="user_id" value="<?php echo esc($listedUser['id']); ?>">

                                <label>
                                    <span>Status</span>
                                    <select name="status">
                                        <option value="pendente" <?php echo $listedUser['status'] === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                        <option value="ativo" <?php echo $listedUser['status'] === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                        <option value="bloqueado" <?php echo $listedUser['status'] === 'bloqueado' ? 'selected' : ''; ?>>Bloqueado</option>
                                    </select>
                                </label>

                                <label>
                                    <span>Tipo</span>
                                    <select name="type">
                                        <option value="usuario" <?php echo $listedUser['tipo'] === 'usuario' ? 'selected' : ''; ?>>Usuário</option>
                                        <option value="admin" <?php echo $listedUser['tipo'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </label>

                                <div>
                                    <span class="label-title">Empresas liberadas</span>
                                    <div class="chips">
                                        <?php foreach ($dashboard->companiesForUser($user, true) as $company): ?>
                                            <label class="chip">
                                                <input
                                                    type="checkbox"
                                                    name="companies[]"
                                                    value="<?php echo esc($company['id']); ?>"
                                                    <?php echo in_array((int)$company['id'], $selectedCompanies, true) ? 'checked' : ''; ?>
                                                >
                                                <?php echo esc($company['nome']); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <button class="button" type="submit">Salvar usuário</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </section>

            <?php elseif ($page === 'imports' && $isAdmin): ?>
                <section class="panel">
                    <h2>Últimas importações</h2>
                    <p>Histórico dos relatórios enviados ao banco de dados.</p>
                </section>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Empresa</th>
                                <th>Conta</th>
                                <th>Arquivo</th>
                                <th>Status</th>
                                <th>Linhas</th>
                                <th>Período</th>
                                <th>Importado em</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dashboard->imports() as $import): ?>
                                <tr>
                                    <td><?php echo esc($import['empresa']); ?></td>
                                    <td><?php echo esc($import['nome_conta']); ?></td>
                                    <td><?php echo esc($import['nome_arquivo_original']); ?></td>
                                    <td><?php echo esc($import['status']); ?></td>
                                    <td><?php echo num_br($import['total_linhas']); ?></td>
                                    <td><?php echo esc($import['periodo_inicio'] . ' a ' . $import['periodo_fim']); ?></td>
                                    <td><?php echo esc($import['criado_em']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($companyId && $campaignId): ?>
                <?php
                    $campaign = $dashboard->campaignSummary($campaignId);
                    $ads = $dashboard->adsByCampaign($campaignId);
                ?>

                <section class="panel">
                    <a class="pill-link" href="index.php?company=<?php echo esc($companyId); ?>">← Voltar para campanhas</a>
                    <h2><?php echo esc($campaign['nome'] ?? 'Campanha'); ?></h2>
                    <p>ID: <?php echo esc($campaign['campanha_meta_id'] ?? ''); ?></p>
                </section>

                <section class="metrics">
                    <article><span>Gasto</span><strong><?php echo money_br($campaign['gasto'] ?? 0); ?></strong></article>
                    <article><span>Alcance</span><strong><?php echo num_br($campaign['alcance'] ?? 0); ?></strong></article>
                    <article><span>Impressões</span><strong><?php echo num_br($campaign['impressoes'] ?? 0); ?></strong></article>
                    <article><span>Resultados</span><strong><?php echo num_br($campaign['resultados'] ?? 0); ?></strong></article>
                </section>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Anúncio / Criativo</th>
                                <th>Plataforma</th>
                                <th>Gasto</th>
                                <th>Alcance</th>
                                <th>Impressões</th>
                                <th>Resultados</th>
                                <th>Significado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ads as $ad): ?>
                                <tr>
                                    <td><?php echo esc($ad['anuncio']); ?></td>
                                    <td><?php echo esc($ad['plataforma']); ?></td>
                                    <td><?php echo money_br($ad['gasto']); ?></td>
                                    <td><?php echo num_br($ad['alcance']); ?></td>
                                    <td><?php echo num_br($ad['impressoes']); ?></td>
                                    <td>
                                        <?php echo num_br($ad['resultados']); ?>
                                        <br><small><?php echo esc($ad['tipo_resultado']); ?></small>
                                    </td>
                                    <td><?php echo esc($ad['significado']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($companyId): ?>
                <?php
                    $summary = $dashboard->companySummary($companyId);
                    $campaigns = $dashboard->campaignsByCompany($companyId);
                ?>

                <section class="panel">
                    <a class="pill-link" href="index.php">← Voltar para empresas</a>
                    <h2><?php echo esc($summary['nome'] ?? 'Empresa'); ?></h2>
                    <p>Dados salvos no banco de dados a partir dos relatórios importados.</p>
                </section>

                <section class="metrics">
                    <article><span>Gasto</span><strong><?php echo money_br($summary['gasto'] ?? 0); ?></strong></article>
                    <article><span>Alcance</span><strong><?php echo num_br($summary['alcance'] ?? 0); ?></strong></article>
                    <article><span>Impressões</span><strong><?php echo num_br($summary['impressoes'] ?? 0); ?></strong></article>
                    <article><span>Campanhas</span><strong><?php echo num_br(count($campaigns)); ?></strong></article>
                </section>

                <?php if (!$campaigns): ?>
                    <div class="alert warning">Ainda não existem campanhas importadas para esta empresa.</div>
                <?php endif; ?>

                <section class="grid">
                    <?php foreach ($campaigns as $campaign): ?>
                        <a class="card-link" href="index.php?company=<?php echo esc($companyId); ?>&campaign=<?php echo esc($campaign['id']); ?>">
                            <article class="card">
                                <h3><?php echo esc($campaign['nome']); ?></h3>
                                <p>Status: <?php echo esc($campaign['status_atual']); ?></p>
                                <p>Resultado: <?php echo esc($campaign['tipo_resultado']); ?></p>

                                <div class="card-metrics">
                                    <div><span>Gasto</span><strong><?php echo money_br($campaign['gasto']); ?></strong></div>
                                    <div><span>Alcance</span><strong><?php echo num_br($campaign['alcance']); ?></strong></div>
                                    <div><span>Impressões</span><strong><?php echo num_br($campaign['impressoes']); ?></strong></div>
                                    <div><span>Resultados</span><strong><?php echo num_br($campaign['resultados']); ?></strong></div>
                                </div>

                                <span class="pill-link">Abrir campanha</span>
                            </article>
                        </a>
                    <?php endforeach; ?>
                </section>

            <?php else: ?>
                <section class="metrics">
                    <article><span>Empresas liberadas</span><strong><?php echo num_br(count($companies)); ?></strong></article>
                    <article><span>Perfil</span><strong><?php echo esc($user['tipo']); ?></strong></article>
                    <article><span>Atualização</span><strong>Planilha</strong></article>
                    <article><span>Banco</span><strong>MySQL</strong></article>
                </section>

                <?php if (!$companies): ?>
                    <div class="alert warning">Nenhuma empresa foi liberada para sua conta.</div>
                <?php endif; ?>

                <section class="grid">
                    <?php foreach ($companies as $company): ?>
                        <?php $summary = $dashboard->companySummary((int)$company['id']); ?>

                        <a class="card-link" href="index.php?company=<?php echo esc($company['id']); ?>">
                            <article class="card company-card">
                                <div class="avatar"><?php echo esc(substr($company['nome'], 0, 4)); ?></div>

                                <h3><?php echo esc($company['nome']); ?></h3>
                                <p>Empresa cadastrada no banco multiempresas.</p>

                                <div class="card-metrics">
                                    <div><span>Campanhas</span><strong><?php echo num_br($summary['campanhas'] ?? 0); ?></strong></div>
                                    <div><span>Anúncios</span><strong><?php echo num_br($summary['anuncios'] ?? 0); ?></strong></div>
                                    <div><span>Gasto</span><strong><?php echo money_br($summary['gasto'] ?? 0); ?></strong></div>
                                    <div><span>Alcance</span><strong><?php echo num_br($summary['alcance'] ?? 0); ?></strong></div>
                                </div>

                                <span class="pill-link">Abrir empresa</span>
                            </article>
                        </a>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </div>
    </main>
<?php endif; ?>
</body>
</html>
