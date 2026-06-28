<?php

declare(strict_types=1);

namespace eFiction\Models;

use eFiction\Database;

/**
 * Base model providing common database helpers.
 */
abstract class Model
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    protected function table(string $name): string
    {
        return $this->db->table($name);
    }

    public function count(string $where = '1', array $params = []): int
    {
        return $this->db->count($this->tableName(), $where, $params);
    }

    abstract protected function tableName(): string;
}
