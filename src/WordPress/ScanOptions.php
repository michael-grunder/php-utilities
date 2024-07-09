<?php

namespace Mgrunder\Utilities\WordPress;

/* A little helper class to scan multiple databases to try and find
   wordpress options tables.  This is simple enough because they will
   have the fields `option_id', `option_name', and `option_value'.*/
class ScanOptions {
    private \PDO $conn;
    private ?\PDOStatement $stmt = null;

    public static function get(string $host, string $user, string $password): self {
        $conn = new \PDO("mysql:host={$host}", $user, $password);
        $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return new self($conn);
    }

    public function __construct(\PDO $conn) {
        $this->conn = $conn;
    }

    private function hasOptionsFields(array $fields): bool {
        return in_array('option_id', $fields) &&
               in_array('option_name', $fields) &&
               in_array('option_value', $fields);
    }

    public function scan(): array {
        $result = [];

        $dbs = $this->getDatabases();

        foreach ($dbs as $db) {
            $tables = $this->getTables($db);

            foreach ($tables as $table) {
                $stmt = $this->conn->prepare("DESCRIBE {$db}.{$table}");
                $stmt->execute();
                $fields = $stmt->fetchAll(\PDO::FETCH_COLUMN);

                if ( ! $this->hasOptionsFields($fields))
                    continue;

                $result[] = ['db' => $db, 'table' => $table];
            }
        }

        return $result;
    }

    private function getDatabases(): array {
        $stmt = $this->conn->prepare("SHOW DATABASES");
        $stmt->execute();
        $databases = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        return $databases;
    }

    private function getTables(string $db): array {
        $stmt = $this->conn->prepare("SHOW TABLES FROM {$db}");
        $stmt->execute();
        $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        return $tables;
    }
}
