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

    public function getByUserId(int $userId): array {
        $stmt = $this->pdo->prepare("
            SELECT o.created_at, o.quantity, p.brand, p.model
            FROM orders o
            JOIN products p ON o.product_id = p.id
            WHERE o.user_id = ?
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    

    public function deleteById(int $id): void {
        $stmt = $this->pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$id]);
    }
    
    public function create(int $userId, int $productId, int $quantity): bool {
        $stmt = $this->pdo->prepare("
            INSERT INTO orders (user_id, product_id, quantity, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        return $stmt->execute([$userId, $productId, $quantity]);
    }


    public function all(): array {
        $stmt = $this->pdo->query("
            SELECT 
                o.id, o.created_at, o.quantity,
                u.name AS user_name, u.email, u.phone,
                p.brand, p.model
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN products p ON o.product_id = p.id
            ORDER BY o.created_at DESC
        ");
        return $stmt->fetchAll();
    }
    
    
    public function deleteAll(): void {
        $this->pdo->exec("DELETE FROM orders");
        $this->pdo->exec("ALTER TABLE orders AUTO_INCREMENT = 1");
    }
    
}
