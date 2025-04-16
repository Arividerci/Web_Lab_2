<?php
require_once 'db.php';

$csvFile = 'orders.csv';
$message = "";

$imported = false;
$orders = [];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_all'])) {
    $pdo->exec("DELETE FROM orders");
    $pdo->exec("ALTER TABLE orders AUTO_INCREMENT = 1;");
    $message = "Все заказы удалены.";
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['import_csv'] ?? '')) {
    if (file_exists($csvFile) && ($handle = fopen($csvFile, "r")) !== false) {
        while (($data = fgetcsv($handle, 1000, ";")) !== false) {
            if (count($data) < 7) continue;

            list($created_at, $name, $email, $phone, $brand, $model, $quantity) = $data;

            // Защита от дубликатов
            $check = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE created_at = ? AND email = ?");
            $check->execute([$created_at, $email]);

            if ($check->fetchColumn() == 0) {
                $stmt = $pdo->prepare("INSERT INTO orders 
                    (created_at, name, email, phone, brand, model, quantity)
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$created_at, $name, $email, $phone, $brand, $model, $quantity]);
            }
        }
        fclose($handle);
        $message = "CSV импортирован!";
        $imported = true;
    } else {
        $message = "CSV-файл не найден или не читается.";
    }
}

// Всегда показываем таблицу (если есть данные)
$orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Список заказов</title>
    
    <link rel="stylesheet" href="Styles/style_db.css">

    
</head>
<body>

<h2>Список заказов</h2>

<form method="post" class="button-form">
    <button type="submit" name="import_csv" value="1">Импортировать из CSV</button>
</form>

<form method="post" class="button-form">
    <button type="submit" name="delete_all" value="1">Удалить все заказы</button>
</form>


<?php if (!empty($message)): ?>
    <p class="message"><?= $message ?></p>
<?php endif; ?>

<?php if (!empty($orders)): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Дата</th><th>ФИО</th><th>Email</th><th>Телефон</th>
                <th>Бренд</th><th>Модель</th><th>Кол-во</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= $order['id'] ?></td>
                <td><?= $order['created_at'] ?></td>
                <td><?= $order['name'] ?></td>
                <td><?= $order['email'] ?></td>
                <td><?= $order['phone'] ?></td>
                <td><?= $order['brand'] ?></td>
                <td><?= $order['model'] ?></td>
                <td><?= $order['quantity'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Пока заказов нет.</p>
<?php endif; ?>

</body>
</html>
