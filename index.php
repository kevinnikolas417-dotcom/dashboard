

==================== database.sql ====================

-- ============================================================
-- PAINEL MULTIEMPRESAS META ADS
-- Banco de dados criado do zero
-- ============================================================

CREATE DATABASE IF NOT EXISTS painel_ads
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE painel_ads;

CREATE TABLE IF NOT EXISTS empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(160) NOT NULL,
    slug VARCHAR(160) NOT NULL UNIQUE,
    status ENUM('ativa','inativa') NOT NULL DEFAULT 'ativa',
    observacoes TEXT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contas_anuncio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    plataforma ENUM('meta','google','tiktok','outro') NOT NULL DEFAULT 'meta',
    conta_anuncio_id VARCHAR(80) NOT NULL,
    nome_conta VARCHAR(180) NULL,
    business_id VARCHAR(80) NULL,
    business_nome VARCHAR(180) NULL,
    pagina_id VARCHAR(80) NULL,
    pagina_nome VARCHAR(180) NULL,
    status ENUM('ativa','inativa') NOT NULL DEFAULT 'ativa',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_conta_anuncio (plataforma, conta_anuncio_id),
    INDEX idx_conta_empresa (empresa_id),
    CONSTRAINT fk_contas_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    tipo ENUM('admin','usuario') NOT NULL DEFAULT 'usuario',
    status ENUM('pendente','ativo','bloqueado') NOT NULL DEFAULT 'pendente',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS usuario_empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    empresa_id INT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_usuario_empresa (usuario_id, empresa_id),
    INDEX idx_usuario_empresa_usuario (usuario_id),
    INDEX idx_usuario_empresa_empresa (empresa_id),
    CONSTRAINT fk_usuario_empresas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_usuario_empresas_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS importacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    conta_anuncio_id INT NULL,
    nome_arquivo_original VARCHAR(255) NOT NULL,
    tipo_arquivo ENUM('csv','xlsx','manual','api') NOT NULL DEFAULT 'xlsx',
    periodo_inicio DATE NULL,
    periodo_fim DATE NULL,
    total_linhas INT NOT NULL DEFAULT 0,
    status ENUM('processando','concluida','erro') NOT NULL DEFAULT 'processando',
    mensagem_erro TEXT NULL,
    importado_por INT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_importacoes_empresa (empresa_id),
    INDEX idx_importacoes_conta (conta_anuncio_id),
    INDEX idx_importacoes_periodo (periodo_inicio, periodo_fim),
    CONSTRAINT fk_importacoes_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_importacoes_conta FOREIGN KEY (conta_anuncio_id) REFERENCES contas_anuncio(id) ON DELETE SET NULL,
    CONSTRAINT fk_importacoes_usuario FOREIGN KEY (importado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS campanhas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    conta_anuncio_id INT NULL,
    campanha_meta_id VARCHAR(100) NULL,
    nome VARCHAR(255) NOT NULL,
    objetivo VARCHAR(140) NULL,
    status_atual VARCHAR(90) NULL,
    orcamento_diario DECIMAL(15,2) NOT NULL DEFAULT 0,
    criado_em_meta DATETIME NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_campanha_meta (conta_anuncio_id, campanha_meta_id),
    INDEX idx_campanhas_empresa (empresa_id),
    INDEX idx_campanhas_nome (nome),
    CONSTRAINT fk_campanhas_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_campanhas_conta FOREIGN KEY (conta_anuncio_id) REFERENCES contas_anuncio(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS conjuntos_anuncios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    campanha_id INT NULL,
    conjunto_meta_id VARCHAR(100) NULL,
    nome VARCHAR(255) NOT NULL,
    status_atual VARCHAR(90) NULL,
    orcamento_diario DECIMAL(15,2) NOT NULL DEFAULT 0,
    criado_em_meta DATETIME NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_conjunto_meta (campanha_id, conjunto_meta_id),
    INDEX idx_conjuntos_empresa (empresa_id),
    INDEX idx_conjuntos_campanha (campanha_id),
    CONSTRAINT fk_conjuntos_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_conjuntos_campanha FOREIGN KEY (campanha_id) REFERENCES campanhas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS anuncios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    campanha_id INT NULL,
    conjunto_id INT NULL,
    anuncio_meta_id VARCHAR(100) NULL,
    nome VARCHAR(255) NOT NULL,
    tipo_criativo VARCHAR(90) NULL,
    status_atual VARCHAR(90) NULL,
    plataforma VARCHAR(90) NULL,
    criado_em_meta DATETIME NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_anuncio_meta (conjunto_id, anuncio_meta_id),
    INDEX idx_anuncios_empresa (empresa_id),
    INDEX idx_anuncios_campanha (campanha_id),
    INDEX idx_anuncios_conjunto (conjunto_id),
    CONSTRAINT fk_anuncios_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_anuncios_campanha FOREIGN KEY (campanha_id) REFERENCES campanhas(id) ON DELETE CASCADE,
    CONSTRAINT fk_anuncios_conjunto FOREIGN KEY (conjunto_id) REFERENCES conjuntos_anuncios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS metricas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    importacao_id INT NULL,
    empresa_id INT NOT NULL,
    conta_anuncio_id INT NULL,
    campanha_id INT NULL,
    conjunto_id INT NULL,
    anuncio_id INT NULL,
    nivel ENUM('campanha','conjunto','anuncio') NOT NULL DEFAULT 'campanha',
    data_inicio DATE NULL,
    data_fim DATE NULL,
    status_veiculacao VARCHAR(90) NULL,
    tipo_resultado VARCHAR(180) NULL,
    significado_resultado TEXT NULL,
    resultados DECIMAL(18,4) NOT NULL DEFAULT 0,
    custo_por_resultado DECIMAL(15,2) NOT NULL DEFAULT 0,
    valor_gasto DECIMAL(15,2) NOT NULL DEFAULT 0,
    alcance INT NOT NULL DEFAULT 0,
    impressoes INT NOT NULL DEFAULT 0,
    frequencia DECIMAL(10,4) NOT NULL DEFAULT 0,
    cpm DECIMAL(15,2) NOT NULL DEFAULT 0,
    cliques_link INT NOT NULL DEFAULT 0,
    compras INT NOT NULL DEFAULT 0,
    conversas_iniciadas INT NOT NULL DEFAULT 0,
    thruplays INT NOT NULL DEFAULT 0,
    visitas_perfil INT NOT NULL DEFAULT 0,
    engajamentos INT NOT NULL DEFAULT 0,
    plataforma VARCHAR(90) NULL,
    atribuicao VARCHAR(255) NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_metricas_empresa (empresa_id),
    INDEX idx_metricas_conta (conta_anuncio_id),
    INDEX idx_metricas_importacao (importacao_id),
    INDEX idx_metricas_campanha (campanha_id),
    INDEX idx_metricas_conjunto (conjunto_id),
    INDEX idx_metricas_anuncio (anuncio_id),
    INDEX idx_metricas_periodo (data_inicio, data_fim),
    INDEX idx_metricas_nivel (nivel),
    CONSTRAINT fk_metricas_importacao FOREIGN KEY (importacao_id) REFERENCES importacoes(id) ON DELETE SET NULL,
    CONSTRAINT fk_metricas_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_metricas_conta FOREIGN KEY (conta_anuncio_id) REFERENCES contas_anuncio(id) ON DELETE SET NULL,
    CONSTRAINT fk_metricas_campanha FOREIGN KEY (campanha_id) REFERENCES campanhas(id) ON DELETE SET NULL,
    CONSTRAINT fk_metricas_conjunto FOREIGN KEY (conjunto_id) REFERENCES conjuntos_anuncios(id) ON DELETE SET NULL,
    CONSTRAINT fk_metricas_anuncio FOREIGN KEY (anuncio_id) REFERENCES anuncios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE OR REPLACE VIEW vw_resumo_empresas AS
SELECT
    e.id AS empresa_id,
    e.nome AS empresa,
    e.slug,
    e.status,
    COUNT(DISTINCT ca.id) AS contas,
    COUNT(DISTINCT c.id) AS campanhas,
    COUNT(DISTINCT a.id) AS anuncios,
    COALESCE(SUM(m.valor_gasto), 0) AS gasto,
    COALESCE(SUM(m.alcance), 0) AS alcance,
    COALESCE(SUM(m.impressoes), 0) AS impressoes,
    COALESCE(SUM(m.resultados), 0) AS resultados,
    MAX(m.criado_em) AS ultima_atualizacao
FROM empresas e
LEFT JOIN contas_anuncio ca ON ca.empresa_id = e.id
LEFT JOIN campanhas c ON c.empresa_id = e.id
LEFT JOIN anuncios a ON a.empresa_id = e.id
LEFT JOIN metricas m ON m.empresa_id = e.id
GROUP BY e.id, e.nome, e.slug, e.status;

INSERT INTO empresas (nome, slug, status) VALUES
('PARISVIU', 'parisviu', 'ativa'),
('IDDV', 'iddv', 'ativa'),
('LIBERTY', 'liberty', 'ativa'),
('LEPARQUE', 'leparque', 'ativa')
ON DUPLICATE KEY UPDATE nome = VALUES(nome), status = VALUES(status);

INSERT INTO contas_anuncio (empresa_id, plataforma, conta_anuncio_id, nome_conta, business_id, business_nome, pagina_id, pagina_nome, status)
SELECT id, 'meta', '9729633853761104', 'Paris Viu - Pré-Paga', '663896709441948', 'Óticas Paris Viu LTDA', NULL, NULL, 'ativa'
FROM empresas WHERE slug = 'parisviu'
ON DUPLICATE KEY UPDATE nome_conta = VALUES(nome_conta), status = VALUES(status);

INSERT INTO contas_anuncio (empresa_id, plataforma, conta_anuncio_id, nome_conta, business_id, business_nome, pagina_id, pagina_nome, status)
SELECT id, 'meta', '680017045142657', 'Direito de Ver - Pré-Paga', '1567994961252082', 'Instituto Direito De Ver', '789239390941637', 'Direito De Ver', 'ativa'
FROM empresas WHERE slug = 'iddv'
ON DUPLICATE KEY UPDATE nome_conta = VALUES(nome_conta), status = VALUES(status);

INSERT INTO contas_anuncio (empresa_id, plataforma, conta_anuncio_id, nome_conta, business_id, business_nome, pagina_id, pagina_nome, status)
SELECT id, 'meta', '9235099773177305', 'Liberty Med - Pré-paga', '1315258062806909', 'Liberty Med', NULL, NULL, 'ativa'
FROM empresas WHERE slug = 'liberty'
ON DUPLICATE KEY UPDATE nome_conta = VALUES(nome_conta), status = VALUES(status);

INSERT INTO contas_anuncio (empresa_id, plataforma, conta_anuncio_id, nome_conta, business_id, business_nome, pagina_id, pagina_nome, status)
SELECT id, 'meta', '4292843267600003', 'LEPARQUĒ', '1450191136347321', 'Leparquè • Óculos de Sol e Grau', NULL, NULL, 'ativa'
FROM empresas WHERE slug = 'leparque'
ON DUPLICATE KEY UPDATE nome_conta = VALUES(nome_conta), status = VALUES(status);


==================== config.php ====================

<?php
return [
    'db_host' => 'localhost',
    'db_name' => 'painel_ads',
    'db_user' => 'SEU_USUARIO_MYSQL',
    'db_pass' => 'SUA_SENHA_MYSQL',
    'admin_email' => 'kevinnikolas417@gmail.com',
    'admin_password' => '123456',
];


==================== index.php ====================

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


==================== app/Database.php ====================

<?php
class Database
{
    private mysqli $conn;

    public function __construct(array $config)
    {
        $this->conn = new mysqli(
            $config['db_host'],
            $config['db_user'],
            $config['db_pass'],
            $config['db_name']
        );

        if ($this->conn->connect_error) {
            die('Erro ao conectar ao banco de dados: ' . $this->conn->connect_error);
        }

        $this->conn->set_charset('utf8mb4');
    }

    public function conn(): mysqli
    {
        return $this->conn;
    }

    public function one(string $sql, string $types = '', array $params = []): ?array
    {
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception('Erro SQL: ' . $this->conn->error);
        }

        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        return $row ?: null;
    }

    public function all(string $sql, string $types = '', array $params = []): array
    {
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception('Erro SQL: ' . $this->conn->error);
        }

        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];

        while ($res && $row = $res->fetch_assoc()) {
            $rows[] = $row;
        }

        $stmt->close();

        return $rows;
    }

    public function execute(string $sql, string $types = '', array $params = []): int
    {
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception('Erro SQL: ' . $this->conn->error);
        }

        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $insertId = $stmt->insert_id;
        $stmt->close();

        return (int)$insertId;
    }

    public function begin(): void
    {
        $this->conn->begin_transaction();
    }

    public function commit(): void
    {
        $this->conn->commit();
    }

    public function rollback(): void
    {
        $this->conn->rollback();
    }
}


==================== app/helpers.php ====================

<?php
function esc($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money_br($value): string
{
    return 'R$ ' . number_format((float)$value, 2, ',', '.');
}

function num_br($value): string
{
    return number_format((int)$value, 0, ',', '.');
}

function clean_number($value): float
{
    $value = trim((string)$value);

    if ($value === '' || $value === '-' || $value === '–') {
        return 0.0;
    }

    $value = str_replace(['R$', 'BRL', "\xc2\xa0", ' '], '', $value);

    if (strpos($value, ',') !== false) {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
    }

    return is_numeric($value) ? (float)$value : 0.0;
}

function clean_int($value): int
{
    return (int)round(clean_number($value));
}

function mysql_date($value): ?string
{
    $value = trim((string)$value);

    if ($value === '' || $value === '-' || $value === '–') {
        return null;
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return $value;
    }

    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $m)) {
        return $m[3] . '-' . $m[2] . '-' . $m[1];
    }

    $time = strtotime($value);

    return $time ? date('Y-m-d', $time) : null;
}

function normalize_header($value): string
{
    $value = trim((string)$value);
    return preg_replace('/^\xEF\xBB\xBF/', '', $value);
}

function pick_col(array $row, array $names, $default = '')
{
    foreach ($names as $name) {
        if (array_key_exists($name, $row)) {
            return $row[$name];
        }
    }

    return $default;
}

function result_meaning($type): string
{
    $text = function_exists('mb_strtolower')
        ? mb_strtolower((string)$type, 'UTF-8')
        : strtolower((string)$type);

    if (strpos($text, 'conversas por mensagem') !== false) {
        return 'Quantidade de conversas iniciadas por mensagem a partir da campanha.';
    }

    if (strpos($text, 'alcance') !== false) {
        return 'Quantidade de contas únicas alcançadas pela campanha.';
    }

    if (strpos($text, 'thruplay') !== false) {
        return 'Reproduções qualificadas de vídeo, conforme métrica ThruPlay da Meta.';
    }

    if (strpos($text, 'visitas ao perfil') !== false) {
        return 'Quantidade de visitas ao perfil do Instagram geradas pela campanha.';
    }

    if (strpos($text, 'engajamentos com o post') !== false) {
        return 'Quantidade de engajamentos no post, como reações, comentários e compartilhamentos.';
    }

    if (strpos($text, 'cliques no link') !== false) {
        return 'Quantidade de cliques no link gerados pela campanha.';
    }

    if (strpos($text, 'interações') !== false) {
        return 'Quantidade de interações registradas pela campanha.';
    }

    return 'Resultado principal definido pela Meta conforme o objetivo da campanha.';
}

function slugify(string $text): string
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');

    return $text ?: ('item-' . time());
}


==================== app/Auth.php ====================

<?php
class Auth
{
    private Database $db;
    private array $config;

    public function __construct(Database $db, array $config)
    {
        $this->db = $db;
        $this->config = $config;
    }

    public function ensureInitialAdmin(): void
    {
        $email = $this->config['admin_email'];
        $existing = $this->db->one('SELECT id FROM usuarios WHERE email = ?', 's', [$email]);

        if ($existing) {
            return;
        }

        $hash = password_hash($this->config['admin_password'], PASSWORD_DEFAULT);

        $userId = $this->db->execute(
            "INSERT INTO usuarios (email, senha_hash, tipo, status) VALUES (?, ?, 'admin', 'ativo')",
            'ss',
            [$email, $hash]
        );

        $empresas = $this->db->all('SELECT id FROM empresas');

        foreach ($empresas as $empresa) {
            $this->db->execute(
                'INSERT IGNORE INTO usuario_empresas (usuario_id, empresa_id) VALUES (?, ?)',
                'ii',
                [$userId, $empresa['id']]
            );
        }
    }

    public function login(string $email, string $password): array
    {
        $user = $this->db->one('SELECT * FROM usuarios WHERE email = ?', 's', [$email]);

        if (!$user || !password_verify($password, $user['senha_hash'])) {
            return ['ok' => false, 'message' => 'E-mail ou senha inválidos.'];
        }

        if ($user['status'] !== 'ativo') {
            return ['ok' => false, 'message' => 'Sua conta ainda não foi aprovada pelo admin.'];
        }

        $_SESSION['usuario_id'] = $user['id'];

        return ['ok' => true, 'message' => 'Login realizado.'];
    }

    public function register(string $email, string $password): array
    {
        if ($email === '' || $password === '') {
            return ['ok' => false, 'message' => 'Preencha o e-mail e a senha pessoal.'];
        }

        $exists = $this->db->one('SELECT id FROM usuarios WHERE email = ?', 's', [$email]);

        if ($exists) {
            return ['ok' => false, 'message' => 'Este e-mail já possui uma conta.'];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $this->db->execute(
            "INSERT INTO usuarios (email, senha_hash, tipo, status) VALUES (?, ?, 'usuario', 'pendente')",
            'ss',
            [$email, $hash]
        );

        return ['ok' => true, 'message' => 'Conta criada com sucesso. Aguarde o admin aprovar seu acesso.'];
    }

    public function logout(): void
    {
        session_destroy();
    }

    public function user(): ?array
    {
        if (!isset($_SESSION['usuario_id'])) {
            return null;
        }

        return $this->db->one('SELECT * FROM usuarios WHERE id = ?', 'i', [$_SESSION['usuario_id']]);
    }

    public function isAdmin(?array $user): bool
    {
        return $user && $user['tipo'] === 'admin';
    }
}


==================== app/SpreadsheetReader.php ====================

<?php
class SpreadsheetReader
{
    public function readUploadedFile(string $tmpPath, string $fileName): array
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            return $this->readCsv($tmpPath);
        }

        if ($extension === 'xlsx') {
            return $this->readXlsx($tmpPath);
        }

        throw new Exception('Formato não suportado. Envie uma planilha XLSX ou CSV.');
    }

    private function readCsv(string $path): array
    {
        $firstLine = strtok((string)file_get_contents($path), "\n");
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $handle = fopen($path, 'r');

        if (!$handle) {
            throw new Exception('Não foi possível abrir o CSV.');
        }

        $matrix = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $matrix[] = $row;
        }

        fclose($handle);

        return $this->rowsFromMatrix($matrix);
    }

    private function readXlsx(string $path): array
    {
        if (!class_exists('ZipArchive')) {
            throw new Exception('ZipArchive não está habilitado no servidor. Envie CSV ou habilite ZipArchive para ler XLSX.');
        }

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new Exception('Não foi possível abrir o XLSX.');
        }

        $sharedStrings = $this->sharedStrings($zip);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');

        if (!$sheetXml) {
            $zip->close();
            throw new Exception('Não foi possível ler a primeira aba do XLSX.');
        }

        $xml = simplexml_load_string($sheetXml);

        if (!$xml) {
            $zip->close();
            throw new Exception('Planilha XLSX inválida.');
        }

        $matrix = [];

        foreach ($xml->sheetData->row as $row) {
            $rowArray = [];

            foreach ($row->c as $cell) {
                $attrs = $cell->attributes();
                $ref = isset($attrs['r']) ? (string)$attrs['r'] : '';
                $index = $this->columnIndex($ref);
                $rowArray[$index] = $this->cellValue($cell, $sharedStrings);
            }

            if (!empty($rowArray)) {
                ksort($rowArray);
                $max = max(array_keys($rowArray));
                $full = [];

                for ($i = 0; $i <= $max; $i++) {
                    $full[] = $rowArray[$i] ?? '';
                }

                $matrix[] = $full;
            }
        }

        $zip->close();

        return $this->rowsFromMatrix($matrix);
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        $strings = [];
        $xmlString = $zip->getFromName('xl/sharedStrings.xml');

        if (!$xmlString) {
            return $strings;
        }

        $xml = simplexml_load_string($xmlString);

        if (!$xml) {
            return $strings;
        }

        foreach ($xml->si as $si) {
            $text = '';

            if (isset($si->t)) {
                $text = (string)$si->t;
            } elseif (isset($si->r)) {
                foreach ($si->r as $run) {
                    $text .= (string)$run->t;
                }
            }

            $strings[] = $text;
        }

        return $strings;
    }

    private function cellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $attrs = $cell->attributes();
        $type = isset($attrs['t']) ? (string)$attrs['t'] : '';
        $value = isset($cell->v) ? (string)$cell->v : '';

        if ($type === 's') {
            return $sharedStrings[(int)$value] ?? '';
        }

        if ($type === 'inlineStr') {
            return isset($cell->is->t) ? (string)$cell->is->t : '';
        }

        return $value;
    }

    private function columnIndex(string $cellRef): int
    {
        preg_match('/^([A-Z]+)/', $cellRef, $match);
        $letters = $match[1] ?? 'A';
        $number = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $number = $number * 26 + (ord($letters[$i]) - 64);
        }

        return $number - 1;
    }

    private function rowsFromMatrix(array $matrix): array
    {
        $headerIndex = -1;
        $headers = [];

        foreach ($matrix as $index => $row) {
            $normalized = array_map('normalize_header', $row);
            $joined = implode(' | ', $normalized);

            if (stripos($joined, 'Nome da campanha') !== false || stripos($joined, 'Campaign name') !== false) {
                $headerIndex = $index;
                $headers = $normalized;
                break;
            }
        }

        if ($headerIndex < 0) {
            throw new Exception('Cabeçalho não encontrado. A planilha precisa conter a coluna "Nome da campanha".');
        }

        $rows = [];

        for ($i = $headerIndex + 1; $i < count($matrix); $i++) {
            $assoc = [];
            $empty = true;

            foreach ($headers as $col => $header) {
                if ($header === '') {
                    continue;
                }

                $value = $matrix[$i][$col] ?? '';

                if (trim((string)$value) !== '') {
                    $empty = false;
                }

                $assoc[$header] = $value;
            }

            if (!$empty) {
                $rows[] = $assoc;
            }
        }

        return $rows;
    }
}


==================== app/ImportService.php ====================

<?php
class ImportService
{
    private Database $db;
    private SpreadsheetReader $reader;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->reader = new SpreadsheetReader();
    }

    public function import(int $empresaId, ?int $contaId, int $userId, array $file): int
    {
        if ($empresaId <= 0) {
            throw new Exception('Selecione a empresa.');
        }

        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Envie uma planilha XLSX ou CSV.');
        }

        if (!$contaId) {
            $conta = $this->db->one(
                'SELECT id FROM contas_anuncio WHERE empresa_id = ? ORDER BY id ASC LIMIT 1',
                'i',
                [$empresaId]
            );

            $contaId = $conta ? (int)$conta['id'] : null;
        }

        $rows = $this->reader->readUploadedFile($file['tmp_name'], $file['name']);

        if (count($rows) === 0) {
            throw new Exception('Nenhuma linha encontrada para importar.');
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        $this->db->begin();

        try {
            $importId = $this->db->execute(
                "INSERT INTO importacoes
                (empresa_id, conta_anuncio_id, nome_arquivo_original, tipo_arquivo, status, importado_por)
                VALUES (?, ?, ?, ?, 'processando', ?)",
                'iissi',
                [$empresaId, $contaId, $file['name'], $extension, $userId]
            );

            $periodStart = null;
            $periodEnd = null;
            $total = 0;

            foreach ($rows as $row) {
                $campaignName = trim((string)pick_col($row, ['Nome da campanha', 'Campaign name']));

                if ($campaignName === '') {
                    continue;
                }

                $campaignId = $this->upsertCampaign($empresaId, $contaId, $row);
                $adsetId = $this->upsertAdset($empresaId, $campaignId, $row);
                $adId = $this->upsertAd($empresaId, $campaignId, $adsetId, $row);

                $level = $adId ? 'anuncio' : ($adsetId ? 'conjunto' : 'campanha');

                $typeResult = pick_col($row, ['Tipo de resultado', 'Result type']);
                $startDate = mysql_date(pick_col($row, ['Início dos relatórios', 'Reporting starts']));
                $endDate = mysql_date(pick_col($row, ['Encerramento dos relatórios', 'Reporting ends']));

                if ($startDate && (!$periodStart || $startDate < $periodStart)) {
                    $periodStart = $startDate;
                }

                if ($endDate && (!$periodEnd || $endDate > $periodEnd)) {
                    $periodEnd = $endDate;
                }

                $results = clean_number(pick_col($row, ['Resultados', 'Results']));
                $conversations = stripos($typeResult, 'conversas por mensagem') !== false ? (int)$results : 0;
                $thruplays = stripos($typeResult, 'thruplay') !== false ? (int)$results : 0;
                $profileVisits = stripos($typeResult, 'visitas ao perfil') !== false ? (int)$results : 0;
                $engagements = (stripos($typeResult, 'engajamentos') !== false || stripos($typeResult, 'interações') !== false) ? (int)$results : 0;

                $this->db->execute(
                    "INSERT INTO metricas (
                        importacao_id,
                        empresa_id,
                        conta_anuncio_id,
                        campanha_id,
                        conjunto_id,
                        anuncio_id,
                        nivel,
                        data_inicio,
                        data_fim,
                        status_veiculacao,
                        tipo_resultado,
                        significado_resultado,
                        resultados,
                        custo_por_resultado,
                        valor_gasto,
                        alcance,
                        impressoes,
                        frequencia,
                        cpm,
                        cliques_link,
                        compras,
                        conversas_iniciadas,
                        thruplays,
                        visitas_perfil,
                        engajamentos,
                        plataforma,
                        atribuicao
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    'iiiiiissssssdddiiiddiiiiiss',
                    [
                        $importId,
                        $empresaId,
                        $contaId,
                        $campaignId,
                        $adsetId,
                        $adId,
                        $level,
                        $startDate,
                        $endDate,
                        pick_col($row, ['Status de veiculação', 'Delivery status', 'Status']),
                        $typeResult,
                        result_meaning($typeResult),
                        $results,
                        clean_number(pick_col($row, ['Custo por resultado', 'Cost per result'])),
                        clean_number(pick_col($row, ['Valor usado (BRL)', 'Amount spent (BRL)', 'Amount spent'])),
                        clean_int(pick_col($row, ['Alcance', 'Reach'])),
                        clean_int(pick_col($row, ['Impressões', 'Impressions'])),
                        clean_number(pick_col($row, ['Frequência', 'Frequency'])),
                        clean_number(pick_col($row, ['CPM (custo por 1.000 impressões)', 'CPM'])),
                        clean_int(pick_col($row, ['Cliques no link', 'Link clicks'])),
                        clean_int(pick_col($row, ['Compras', 'Purchases'])),
                        $conversations,
                        $thruplays,
                        $profileVisits,
                        $engagements,
                        pick_col($row, ['Plataforma', 'Publisher platform']),
                        pick_col($row, ['Configuração de atribuição', 'Attribution setting'])
                    ]
                );

                $total++;
            }

            $this->db->execute(
                "UPDATE importacoes
                 SET periodo_inicio = ?, periodo_fim = ?, total_linhas = ?, status = 'concluida'
                 WHERE id = ?",
                'ssii',
                [$periodStart, $periodEnd, $total, $importId]
            );

            $this->db->commit();

            return $total;
        } catch (Exception $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

    private function upsertCampaign(int $empresaId, ?int $contaId, array $row): int
    {
        $name = trim((string)pick_col($row, ['Nome da campanha', 'Campaign name']));
        $metaId = trim((string)pick_col($row, ['ID da campanha', 'Campaign ID']));

        if ($metaId === '') {
            $metaId = 'nome-' . md5($empresaId . '|' . $contaId . '|' . $name);
        }

        $existing = $this->db->one(
            'SELECT id FROM campanhas WHERE conta_anuncio_id <=> ? AND campanha_meta_id = ?',
            'is',
            [$contaId, $metaId]
        );

        $objective = pick_col($row, ['Objetivo', 'Objective']);
        $status = pick_col($row, ['Status de veiculação', 'Delivery status', 'Status']);
        $budget = clean_number(pick_col($row, ['Orçamento diário', 'Daily budget']));

        if ($existing) {
            $this->db->execute(
                'UPDATE campanhas SET nome = ?, objetivo = ?, status_atual = ?, orcamento_diario = ? WHERE id = ?',
                'sssdi',
                [$name, $objective, $status, $budget, $existing['id']]
            );

            return (int)$existing['id'];
        }

        return $this->db->execute(
            "INSERT INTO campanhas
             (empresa_id, conta_anuncio_id, campanha_meta_id, nome, objetivo, status_atual, orcamento_diario)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            'iissssd',
            [$empresaId, $contaId, $metaId, $name, $objective, $status, $budget]
        );
    }

    private function upsertAdset(int $empresaId, int $campaignId, array $row): ?int
    {
        $name = trim((string)pick_col($row, ['Nome do conjunto de anúncios', 'Ad set name']));

        if ($name === '') {
            return null;
        }

        $metaId = trim((string)pick_col($row, ['ID do conjunto de anúncios', 'Ad set ID']));

        if ($metaId === '') {
            $metaId = 'nome-' . md5($campaignId . '|' . $name);
        }

        $existing = $this->db->one(
            'SELECT id FROM conjuntos_anuncios WHERE campanha_id <=> ? AND conjunto_meta_id = ?',
            'is',
            [$campaignId, $metaId]
        );

        $status = pick_col($row, ['Status de veiculação', 'Delivery status', 'Status']);

        if ($existing) {
            $this->db->execute(
                'UPDATE conjuntos_anuncios SET nome = ?, status_atual = ? WHERE id = ?',
                'ssi',
                [$name, $status, $existing['id']]
            );

            return (int)$existing['id'];
        }

        return $this->db->execute(
            "INSERT INTO conjuntos_anuncios
             (empresa_id, campanha_id, conjunto_meta_id, nome, status_atual)
             VALUES (?, ?, ?, ?, ?)",
            'iisss',
            [$empresaId, $campaignId, $metaId, $name, $status]
        );
    }

    private function upsertAd(int $empresaId, int $campaignId, ?int $adsetId, array $row): ?int
    {
        $name = trim((string)pick_col($row, ['Nome do anúncio', 'Ad name']));

        if ($name === '') {
            return null;
        }

        $metaId = trim((string)pick_col($row, ['ID do anúncio', 'Ad ID']));
        $platform = pick_col($row, ['Plataforma', 'Publisher platform']);

        if ($metaId === '') {
            $metaId = 'nome-' . md5($campaignId . '|' . $adsetId . '|' . $name . '|' . $platform);
        }

        $existing = $this->db->one(
            'SELECT id FROM anuncios WHERE conjunto_id <=> ? AND anuncio_meta_id = ?',
            'is',
            [$adsetId, $metaId]
        );

        if ($existing) {
            $this->db->execute(
                'UPDATE anuncios SET nome = ?, plataforma = ? WHERE id = ?',
                'ssi',
                [$name, $platform, $existing['id']]
            );

            return (int)$existing['id'];
        }

        return $this->db->execute(
            "INSERT INTO anuncios
             (empresa_id, campanha_id, conjunto_id, anuncio_meta_id, nome, plataforma)
             VALUES (?, ?, ?, ?, ?, ?)",
            'iiisss',
            [$empresaId, $campaignId, $adsetId, $metaId, $name, $platform]
        );
    }
}


==================== app/Dashboard.php ====================

<?php
class Dashboard
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function companiesForUser(array $user, bool $isAdmin): array
    {
        if ($isAdmin) {
            return $this->db->all("SELECT * FROM empresas WHERE status = 'ativa' ORDER BY nome");
        }

        return $this->db->all(
            "SELECT e.*
             FROM empresas e
             INNER JOIN usuario_empresas ue ON ue.empresa_id = e.id
             WHERE ue.usuario_id = ? AND e.status = 'ativa'
             ORDER BY e.nome",
            'i',
            [$user['id']]
        );
    }

    public function companySummary(int $empresaId): array
    {
        return $this->db->one(
            "SELECT
                e.*,
                COUNT(DISTINCT c.id) AS campanhas,
                COUNT(DISTINCT a.id) AS anuncios,
                COALESCE(SUM(m.valor_gasto), 0) AS gasto,
                COALESCE(SUM(m.alcance), 0) AS alcance,
                COALESCE(SUM(m.impressoes), 0) AS impressoes,
                COALESCE(SUM(m.resultados), 0) AS resultados
             FROM empresas e
             LEFT JOIN campanhas c ON c.empresa_id = e.id
             LEFT JOIN anuncios a ON a.empresa_id = e.id
             LEFT JOIN metricas m ON m.empresa_id = e.id
             WHERE e.id = ?
             GROUP BY e.id",
            'i',
            [$empresaId]
        ) ?: [];
    }

    public function campaignSummary(int $campanhaId): array
    {
        return $this->db->one(
            "SELECT
                c.*,
                COALESCE(SUM(m.valor_gasto), 0) AS gasto,
                COALESCE(SUM(m.alcance), 0) AS alcance,
                COALESCE(SUM(m.impressoes), 0) AS impressoes,
                COALESCE(SUM(m.resultados), 0) AS resultados
             FROM campanhas c
             LEFT JOIN metricas m ON m.campanha_id = c.id
             WHERE c.id = ?
             GROUP BY c.id",
            'i',
            [$campanhaId]
        ) ?: [];
    }

    public function campaignsByCompany(int $empresaId): array
    {
        return $this->db->all(
            "SELECT
                c.*,
                COALESCE(SUM(m.valor_gasto), 0) AS gasto,
                COALESCE(SUM(m.alcance), 0) AS alcance,
                COALESCE(SUM(m.impressoes), 0) AS impressoes,
                COALESCE(SUM(m.resultados), 0) AS resultados,
                MAX(m.tipo_resultado) AS tipo_resultado
             FROM campanhas c
             LEFT JOIN metricas m ON m.campanha_id = c.id
             WHERE c.empresa_id = ?
             GROUP BY c.id
             ORDER BY gasto DESC, c.nome ASC",
            'i',
            [$empresaId]
        );
    }

    public function adsByCampaign(int $campaignId): array
    {
        return $this->db->all(
            "SELECT
                COALESCE(a.nome, 'Resumo da campanha') AS anuncio,
                COALESCE(a.plataforma, m.plataforma, 'Meta') AS plataforma,
                COALESCE(SUM(m.valor_gasto), 0) AS gasto,
                COALESCE(SUM(m.alcance), 0) AS alcance,
                COALESCE(SUM(m.impressoes), 0) AS impressoes,
                COALESCE(SUM(m.resultados), 0) AS resultados,
                MAX(m.tipo_resultado) AS tipo_resultado,
                MAX(m.significado_resultado) AS significado
             FROM metricas m
             LEFT JOIN anuncios a ON a.id = m.anuncio_id
             WHERE m.campanha_id = ?
             GROUP BY a.id, a.nome, a.plataforma
             ORDER BY gasto DESC",
            'i',
            [$campaignId]
        );
    }

    public function accounts(): array
    {
        return $this->db->all(
            "SELECT ca.*, e.nome AS empresa
             FROM contas_anuncio ca
             INNER JOIN empresas e ON e.id = ca.empresa_id
             WHERE ca.status = 'ativa'
             ORDER BY e.nome, ca.nome_conta"
        );
    }

    public function users(): array
    {
        return $this->db->all('SELECT * FROM usuarios ORDER BY criado_em DESC');
    }

    public function userCompanyIds(int $userId): array
    {
        $rows = $this->db->all('SELECT empresa_id FROM usuario_empresas WHERE usuario_id = ?', 'i', [$userId]);

        return array_map(fn($row) => (int)$row['empresa_id'], $rows);
    }

    public function imports(): array
    {
        return $this->db->all(
            "SELECT i.*, e.nome AS empresa, ca.nome_conta
             FROM importacoes i
             INNER JOIN empresas e ON e.id = i.empresa_id
             LEFT JOIN contas_anuncio ca ON ca.id = i.conta_anuncio_id
             ORDER BY i.criado_em DESC
             LIMIT 20"
        );
    }
}


==================== app/bootstrap.php ====================

<?php
session_start();

$config = require __DIR__ . '/../config.php';

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/SpreadsheetReader.php';
require_once __DIR__ . '/ImportService.php';
require_once __DIR__ . '/Dashboard.php';

$db = new Database($config);
$auth = new Auth($db, $config);
$auth->ensureInitialAdmin();

$dashboard = new Dashboard($db);
$importer = new ImportService($db);

$flashSuccess = '';
$flashError = '';


==================== assets/style.css ====================

* {
    box-sizing: border-box;
}

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

.auth-header,
.hero {
    color: #ffffff;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
}

.auth-header {
    padding: 28px;
}

.auth-header h1 {
    margin: 0 0 8px;
    font-size: 2rem;
}

.auth-header p,
.hero p {
    margin: 0;
    color: rgba(255, 255, 255, .88);
    line-height: 1.45;
}

.auth-form {
    padding: 24px;
    display: grid;
    gap: 14px;
}

.auth-form label,
.form-grid label {
    display: grid;
    gap: 7px;
}

.auth-form span,
.form-grid span,
.label-title {
    font-size: .82rem;
    color: #334155;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .06em;
}

input,
select {
    width: 100%;
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 13px 14px;
    font-size: 1rem;
    outline: none;
    background: #ffffff;
}

input:focus,
select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(91, 110, 225, .14);
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

header {
    padding: 18px 0 14px;
}

.hero {
    padding: 22px;
    border-radius: 26px;
    box-shadow: var(--shadow);
    display: grid;
    gap: 18px;
}

.hero h1 {
    margin: 0 0 8px;
    font-size: clamp(1.55rem, 6vw, 3rem);
    line-height: 1.05;
}

.hero-card {
    background: rgba(255, 255, 255, .14);
    border: 1px solid rgba(255, 255, 255, .22);
    border-radius: 20px;
    padding: 14px;
    display: grid;
    gap: 8px;
}

.hero-card span {
    display: block;
    color: rgba(255, 255, 255, .72);
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .06em;
}

.hero-card strong {
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

.panel,
.card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 24px;
    box-shadow: var(--shadow);
    padding: 16px;
}

.panel {
    margin-bottom: 16px;
}

.panel h2,
.card h3 {
    margin: 0 0 8px;
}

.panel p,
.card p {
    margin: 0 0 12px;
    color: var(--muted);
    line-height: 1.45;
}

.metrics {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 16px;
}

.metrics article,
.card-metrics div {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 14px;
    box-shadow: 0 8px 20px rgba(15, 23, 42, .06);
}

.metrics span,
.card-metrics span {
    display: block;
    color: var(--muted);
    font-size: .64rem;
    text-transform: uppercase;
    letter-spacing: .07em;
    font-weight: 900;
    margin-bottom: 5px;
}

.metrics strong {
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
    display: grid;
    gap: 12px;
}

.company-card {
    min-height: 280px;
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

.card-metrics {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

.pill-link {
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

.form-grid {
    display: grid;
    gap: 14px;
}

.chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 8px;
}

.chip {
    border: 1px solid var(--border);
    background: #ffffff;
    border-radius: 999px;
    padding: 9px 12px;
    font-weight: 800;
    color: #334155;
}

.chip input {
    width: auto;
    margin-right: 6px;
}

.notice {
    margin-top: 14px;
    padding: 14px;
    border-radius: 18px;
    color: var(--warning);
    background: var(--warning-bg);
    border: 1px solid #fde68a;
    font-weight: 700;
    line-height: 1.45;
}

.table-wrap {
    overflow-x: auto;
    background: #ffffff;
    border: 1px solid var(--border);
    border-radius: 22px;
    box-shadow: 0 8px 20px rgba(15, 23, 42, .06);
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 820px;
}

th,
td {
    text-align: left;
    padding: 12px;
    border-bottom: 1px solid var(--border);
    font-size: .88rem;
    vertical-align: top;
}

th {
    background: #111827;
    color: #ffffff;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}

small {
    color: var(--muted);
    font-weight: 700;
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

    .grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .card {
        padding: 20px;
    }
}


==================== README.txt ====================

# Site Multiempresas — Criado do Zero

Este projeto foi refeito do zero, em uma estrutura organizada por arquivos.

## O que tem

- Login
- Criar conta
- Admin aprova usuários
- Admin define perfil: admin ou usuário
- Admin libera empresas por usuário
- Banco de dados MySQL multiempresas
- Empresas iniciais:
  - PARISVIU
  - IDDV
  - LIBERTY
  - LEPARQUE
- Admin importa planilha por empresa
- Importa XLSX ou CSV
- Reconhece colunas do relatório da Meta
- Atualiza:
  - campanhas
  - conjuntos de anúncios
  - anúncios/criativos
  - métricas
  - histórico de importações
- Dashboard por empresa
- Dashboard por campanha
- Histórico de importações

## Arquivos principais

- index.php
- config.php
- database.sql
- app/
- assets/style.css

## Instalação na Hostinger

1. Crie um banco MySQL no hPanel.
2. Abra o phpMyAdmin.
3. Importe o arquivo `database.sql`.
4. Edite o arquivo `config.php`:
   - db_host
   - db_name
   - db_user
   - db_pass
5. Suba todos os arquivos para a raiz do site.
6. Acesse o site.

## Login admin inicial

E-mail: kevinnikolas417@gmail.com
Senha: 123456

O sistema cria esse admin automaticamente no primeiro acesso, caso ele ainda não exista no banco.

## Atualização dos dados

1. Entre como admin.
2. Clique em `Importar relatório`.
3. Escolha a empresa.
4. Escolha a conta de anúncio.
5. Envie a planilha XLSX ou CSV exportada da Meta.
6. O banco será atualizado automaticamente.
