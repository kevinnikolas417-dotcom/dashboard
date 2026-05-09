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
