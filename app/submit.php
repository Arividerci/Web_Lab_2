<?php 

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');


    if (!empty($name) and !empty($message)){
        $stmt = $pdo->prepare('INSERT INTO messages (name, email) VALUES (?, ?)');
            $stmt->execute([$name, $email]);
    }
}

header('Location: index.php');
exit;