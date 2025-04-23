<?php

namespace Myshop\Electronics\Model;

use PDO;
use Myshop\Electronics\Core\Database;

class Order
{
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::connect(); 
    }

    public function deleteById(int $id): void {
        $stmt = $this->pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function createCompact($created, $name, $email, $phone, $brand, $model, $qty): void {
        $stmt = $this->pdo->prepare("INSERT INTO orders (created_at, name, email, phone, brand, model, quantity)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$created, $name, $email, $phone, $brand, $model, $qty]);
    }
    
    public function create(array $data): bool {
        $stmt = $this->pdo->prepare("INSERT INTO orders 
            (created_at, name, email, phone, brand, model, quantity)
            VALUES (NOW(), ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['brand'],
            $data['model'],
            $data['quantity']
        ]);
    }

    public function all(): array {
        $stmt = $this->pdo->query("SELECT * FROM orders ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }
    
    public function deleteAll(): void {
        $this->pdo->exec("DELETE FROM orders");
        $this->pdo->exec("ALTER TABLE orders AUTO_INCREMENT = 1");
    }
    
}
