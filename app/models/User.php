<?php

require_once __DIR__ . '/../core/Database.php';

class User {
    private $conn;

    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }

    public function create($email) {
        $stmt = $this->conn->prepare("INSERT INTO users (email) VALUES (?)");
        $stmt->bind_param("s", $email);
        return $stmt->execute();
    }
}
