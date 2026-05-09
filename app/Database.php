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
