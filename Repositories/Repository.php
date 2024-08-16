<?php

namespace Repositories;

use PDO;

class Repository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function executeQuery(string $table, array $select, string $where, array $params)
    {
        $columns = implode(', ', $select);
        $query = "SELECT $columns FROM $table";

        if (!empty($where)) {
            $query .= " WHERE $where";
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);

        return $stmt;
    }

    public function fetch(string $table, array $select = ['*'], string $where = '', array $params = []): array
    {
        $stmt = $this->executeQuery($table, $select, $where, $params);
        return $stmt->fetchAll();
    }

    public function fetchOne(string $table, array $select = ['*'], string $where = '', array $params = []): ?array
    {
        $stmt = $this->executeQuery($table, $select, $where, $params);
        return $stmt->fetch() ?: null;
    }
}

