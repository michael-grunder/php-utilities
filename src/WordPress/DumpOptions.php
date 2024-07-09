<?php
namespace Mgrunder\Utilities\WordPress;

class DumpOptions {
    private \PDO $conn;
    private ?\PDOStatement $stmt = null;

    private string $db;
    private string $table;

    public static function get(string $host, string $user, string $password, string $db,
                               string $table = 'wp_options'): self
    {
        $conn = new \PDO("mysql:host={$host};dbname={$db}", $user, $password);
        $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return new self($conn, $db, $table);
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

    public function dump(string $name = null): \Generator {
        $query = "SELECT option_id, option_name, option_value FROM {$this->db}.{$this->table}";
        if ($name !== null) {
            if (strpos($name, '%') !== false) {
                $query .= " WHERE option_name LIKE :name";
            } else {
                $query .= " WHERE option_name = :name";
            }
        }

        $stmt = $this->conn->prepare($query);

        if ($name !== null) {
            $stmt->bindParam(':name', $name);
        }

        $stmt->execute();

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }
}
