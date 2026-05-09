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
