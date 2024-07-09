<?php

namespace Mgrunder\Utilities\WordPress;

class DumpOptions {
    private \PDO $conn;
    private ?\PDOStatement $stmt = null;

    private string $db;
    private string $table;

    public static function get(string $host, string $user, string $password, string $db): self {
        $conn = new \PDO("mysql:host={$host};dbname={$db}", $user, $password);
        $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return new self($conn, $db);
    }

    public function __construct(\PDO $conn, string $db, string $table = 'wp_options') {
        $this->conn = $conn;
        $this->db = $db;
        $this->table = $table;
    }

    /* Use a prepared query to count the number of rows in the table */
    public function count(): int {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM {$this->db}.{$this->table}");
        $stmt->execute();
        $count = $stmt->fetchColumn();

        return $count;
    }

    public function select(string $name = null): void {
        $query = "SELECT option_id, option_name, option_value FROM {$this->db}.{$this->table}";
        if ($name !== null) {
            if (strpos($name, '%') !== false) {
                $query .= " WHERE option_name LIKE :name";
            } else {
                $query .= " WHERE option_name = :name";
            }
        }

        $this->stmt = $this->conn->prepare($query);

        if ($name !== null)
            $this->stmt->bindParam(':name', $name);

        $this->stmt->execute();
    }

    private function unserializeRecursive(&$result, string $input) {
        $result = @unserialize($input);
        if ($result === false) {
            $result = $input;
            return;
        }

        if (is_array($result)) {
            foreach ($result as $key => $value) {
                $this->unserializeRecursive($result[$key], $value);
            }
        }
    }

    public function next(bool $unserialize = false): ?array {
        if ($this->stmt == null) {
            throw new \Exception('Call select() before calling next()');
        }

        $row = $this->stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row === false)
            return null;
        else if ( ! $unserialize)
            return $row['option_value'];

        $result = null;

        $this->unserializeRecursive($result, $row['option_value']);

        return $result;
    }
}
