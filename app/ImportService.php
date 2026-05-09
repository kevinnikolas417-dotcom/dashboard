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
