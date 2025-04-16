<?php
session_start();
require_once 'db.php';

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function isValidPhone($phone) {
    return preg_match("/^\+?\d{10,13}$/", $phone);
}

function isValidName($name) {
    return preg_match("/^[А-Яа-яЁёA-Za-z\s]{2,500}+$/u", $name);
}

$title = "Интернет-магазин электроники";
$brands = ["Samsung", "Apple", "Xiaomi", "Sony", "LG", "Asus", "HP"];
$file = "orders.csv";
$message = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars(trim($_POST["name"] ?? ''));
    $email = htmlspecialchars(trim($_POST["email"] ?? ''));
    $phone = htmlspecialchars(trim($_POST["phone"] ?? ''));
    $brand = htmlspecialchars(trim($_POST["brand"] ?? ''));
    $model = htmlspecialchars(trim($_POST["model"] ?? ''));
    $quantity = htmlspecialchars($_POST["quantity"] ?? 1);
    $date = date("Y-m-d H:i:s");

    if (empty($name)) {
        $errors[] = "ФИО обязательно для заполнения.";
    } elseif (!isValidName($name)) {
        $errors[] = "ФИО должно содержать только буквы и пробелы от 2х символов.";
    }

    if (empty($email)) {
        $errors[] = "Email обязателен.";
    } elseif (!isValidEmail($email)) {
        $errors[] = "Некорректный email.";
    }

    if (empty($phone)) {
        $errors[] = "Телефон обязателен.";
    } elseif (!isValidPhone($phone)) {
        $errors[] = "Телефон должен быть в формате +79991234567.";
    }

    if (empty($brand) || !in_array($brand, $brands)) {
        $errors[] = "Выберите корректный бренд.";
    }

    if (empty($model)) {
        $errors[] = "Укажите модель товара.";
    }

    if (!is_numeric($quantity) || $quantity < 1) {
        $errors[] = "Количество должно быть числом больше 0.";
    }

    if (empty($errors)) {
        
        $order = [$date, $name, $email, $phone, $brand, $model, $quantity];
        $file_handle = fopen($file, "a");
        fputcsv($file_handle, $order, ";");
        fclose($file_handle);

        $message = "Спасибо, $name! Ваш заказ на $quantity шт. $brand $model успешно оформлен.";

    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="Styles/style.css">
    
</head>
<body>

<header><?= $title ?></header>

<div class="container">
    <h3>Оформление заказа</h3>

    <?php if (!empty($message)): ?>
        <p class="message"><?= $message ?></p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <p class="error-message"><?= implode($errors) ?></p>
    <?php endif; ?>

    <form method="post">

        <label>ФИО:</label>
        <input type="text" name="name" value="<?= $_POST['name'] ?>">

        <label>Email:</label>
        <input type="email" name="email" value="<?= $_POST['email'] ?>">

        <label>Телефон:</label>
        <input type="tel" name="phone" value="<?= $_POST['phone'] ?>">

        <label>Выберите бренд:</label>
        <select name="brand">
            <?php foreach ($brands as $brandOption): ?>
                <option value="<?= $brandOption ?>">
                    <?= $brandOption ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Модель товара:</label>
        <input type="text" name="model" value="<?= $_POST['model'] ?>">

        <label>Количество:</label>
        <input type="number" name="quantity" min="1" value="<?= $_POST['quantity'] ?>">

        <button type="submit">Оформить заказ</button>
    </form>
    <br>
    <form method="post">
        <a href="orders_list.php"><button type="button">Просмотр заказов</button></a>
    </form>

</div>

</body>
</html>
