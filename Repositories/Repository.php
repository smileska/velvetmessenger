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

    public function fetch(string $table, array $select = ['*'], string $where = '', array $params = []): array
    {
        $columns = implode(', ', $select);
        $query = "SELECT $columns FROM $table";

        if (!empty($where)) {
            $query .= " WHERE $where";
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
