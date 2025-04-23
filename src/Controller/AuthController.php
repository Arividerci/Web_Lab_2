<?php

namespace Myshop\Electronics\Controller;

use Myshop\Electronics\Core\Database;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use PDO;

class AuthController
{
    private Environment $twig;
    private PDO $pdo;

    public function __construct()
    {
        $loader = new FilesystemLoader(__DIR__ . '/../views');
        $this->twig = new Environment($loader);
        $this->twig->addGlobal('session', $_SESSION);
        $this->pdo = Database::connect();
    }

    public function showRegister() {
        echo $this->twig->render('register.twig', ['title' => 'Регистрация']);
    }
    
    public function showLogin()
    {
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['error']);

        echo $this->twig->render('login.twig', [
            'title' => 'Вход',
            'error' => $error
        ]);
    }

    public function login()
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'phone' => $user['phone'],
                'role' => $user['role'] ?? 'user'  // роль по умолчанию
            ];
            header('Location: /cabinet');
            exit;
        } else {
            $_SESSION['error'] = 'Неверный email или пароль';
            header('Location: /login');
            exit;
        }
    }
    public function logout() {
        session_destroy();
        header('Location: /');
        exit;
    }
    public function cabinet() {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }
    
        $user = $_SESSION['user'];
        $orderModel = new \Myshop\Electronics\Model\Order();
        $orders = $orderModel->getByUserId($user['id']);
    
        echo $this->twig->render('cabinet.twig', [
            'title' => 'Личный кабинет',
            'user' => $user,
            'orders' => $orders
        ]);
    }
    
    public function getByUserId($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function register() {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $errors = [];

        if (!$name) $errors[] = "Имя обязательно";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Некорректный email";
        if (strlen($password) < 6) $errors[] = "Пароль должен быть от 6 символов";
        if (!preg_match("/^\+?\d{10,13}$/", $phone)) $errors[] = "Некорректный номер телефона";

        $check = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = "Email уже зарегистрирован";
        }

        if ($errors) {
            echo $this->twig->render('register.twig', [
                'title' => 'Регистрация',
                'errors' => $errors,
                'old' => compact('name', 'email')
            ]);
            return;
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hashed, $phone]);

        $_SESSION['success'] = "Вы успешно зарегистрированы!";
        header('Location: /login');
        exit;
    }
}
