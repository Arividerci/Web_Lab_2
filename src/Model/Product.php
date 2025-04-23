<?php

namespace Myshop\Electronics\Model;

use PDO;
use Myshop\Electronics\Core\Database;

class Product
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connect();
    }

    public function getStock(string $brand, string $model): ?int
    {
        $stmt = $this->pdo->prepare("SELECT stock FROM products WHERE brand = ? AND model = ?");
        $stmt->execute([$brand, $model]);
        $row = $stmt->fetch();

        return $row['stock'] ?? null;
    }

    public function findByBrandModel(string $brand, string $model): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, stock FROM products WHERE brand = ? AND model = ?");
        $stmt->execute([$brand, $model]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function decreaseStock(string $brand, string $model, int $qty): void
    {
        $stmt = $this->pdo->prepare("UPDATE products SET stock = stock - ? WHERE brand = ? AND model = ?");
        $stmt->execute([$qty, $brand, $model]);
    }
}
