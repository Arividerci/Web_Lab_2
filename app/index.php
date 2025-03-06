<?php
session_start();

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

$title = "Интернет-магазин электроники";
$brands = ["Samsung", "Apple", "Xiaomi", "Sony", "LG", "Asus", "HP"];
$file = "orders.csv";
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars($_POST["name"] ?? '');
    $email = filter_var($_POST["email"] ?? '', FILTER_VALIDATE_EMAIL);
    $phone = preg_replace("/[^0-9+]/", "", $_POST["phone"] ?? '');
    $brand = htmlspecialchars($_POST["brand"] ?? '');
    $model = htmlspecialchars($_POST["model"] ?? '');
    $quantity = intval($_POST["quantity"] ?? 1);
    $date = date("Y-m-d H:i:s");

    if (empty($name) || empty($email) || empty($phone) || empty($brand) || empty($model) || $quantity < 1) {
        $message = "Ошибка: Все поля должны быть заполнены!";
    } elseif (!isValidEmail($email)) {
        $message = "Ошибка: Некорректный email!";
    } else {
        $order = [$date, $name, $email, $phone, $brand, $model, $quantity];
        $file_handle = fopen($file, "a");
        fputcsv($file_handle, $order, ";");
        fclose($file_handle);

        $message = "Спасибо, $name! Ваш заказ на $quantity $brand $model успешно оформлен.";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f4f4;
}

header {
    background: #4c0d4c;
    color: white;
    padding: 15px;
    text-align: center;
}

.container {
    background: white;
    padding: 20px;
    max-width: 400px;
    margin: 50px auto;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    text-align: center;
}

form {
    display: flex;
    flex-direction: column;
}

label {
    text-align: left;
    font-weight: bold;
    margin-top: 10px;
}

input, select {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    margin-top: 5px;
    font-size: 16px;
    width: 100%;
}

button {
    background: #da70d6;
    color: white;
    border: none;
    padding: 10px;
    font-size: 16px;
    cursor: pointer;
    border-radius: 5px;
    margin-top: 10px;
    width: 100%;
}

button:hover {
    background: #0056b3;
}
    </style>
</head>
<body>

<header>
    <h1><?= $title ?></h1>
</header>

<div class="container">
    <h3>Оформление заказа</h3>
    
    <?php if (!empty($message)): ?>
        <p class="message"><?= $message ?></p>
    <?php endif; ?>
    
    <form method="post">

        <label>ФИО:</label>
        <input type="text" name="name" required pattern="[А-Яа-яЁёA-Za-z\s]+" title="Только буквы и пробелы">
        
        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Телефон:</label>
        <input type="tel" name="phone" required pattern="\+?\d{10,13}" title="Введите номер в формате +79991234567">

        <label>Выберите бренд:</label>
        <select name="brand" required>
            <option value="">-- Выберите бренд --</option>
            <?php foreach ($brands as $brand): ?>
                <option value="<?= $brand ?>"><?= $brand ?></option>
            <?php endforeach; ?>
        </select>

        <label>Модель товара:</label>
        <input type="text" name="model" required>

        <label>Количество:</label>
        <input type="number" name="quantity" min="1" required>

        <button type="submit">Оформить заказ</button>
    </form>
</div>

</body>
</html>
