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
