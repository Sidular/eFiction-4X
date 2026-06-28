<?php

declare(strict_types=1);

namespace eFiction\Core;

use PDO;
use PDOException;
use PDOStatement;

/**
 * PDO wrapper with table prefixing, helpers, and transaction support.
 */
class Database
{
    private ?PDO $pdo = null;
    private string $prefix;
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $db = $config->get('db', []);

        $host = $db['host'] ?? 'localhost';
        $database = $db['database'] ?? '';
        $user = $db['user'] ?? '';
        $pass = $db['password'] ?? '';
        $charset = $db['charset'] ?? 'utf8mb4';
        $this->prefix = $db['prefix'] ?? 'fanfiction_';

        $dsn = "mysql:host={$host};dbname={$database};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
            $this->pdo->exec("SET NAMES {$charset} COLLATE {$charset}_unicode_ci");
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function prefix(): string
    {
        return $this->prefix;
    }

    public function table(string $name): string
    {
        return $this->prefix . $name;
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchColumn(string $sql, array $params = []): mixed
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }

    public function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn($c) => ':' . $c, $columns);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table($table),
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        $this->query($sql, $data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = array_map(static fn($c) => "{$c} = :{$c}", array_keys($data));
        $sql = sprintf('UPDATE %s SET %s WHERE %s', $this->table($table), implode(', ', $set), $where);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge($data, $whereParams));
        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = sprintf('DELETE FROM %s WHERE %s', $this->table($table), $where);
        return $this->query($sql, $params)->rowCount();
    }

    public function count(string $table, string $where = '1', array $params = []): int
    {
        return (int) $this->fetchColumn(
            sprintf('SELECT COUNT(*) FROM %s WHERE %s', $this->table($table), $where),
            $params
        );
    }

    public function exists(string $table, string $where, array $params = []): bool
    {
        return $this->count($table, $where, $params) > 0;
    }

    public function begin(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        $this->pdo->rollBack();
    }

    public function exec(string $sql): void
    {
        $this->pdo->exec($sql);
    }

    public function lastId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }
}
