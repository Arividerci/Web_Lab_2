<?php

namespace Myshop\Electronics\Controller;

use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use Myshop\Electronics\Model\Order;
use Myshop\Electronics\Model\Product;

class FormController {
    private Environment $twig;

    private array $brandModels = [
        'Samsung' => [
            'Galaxy S23', 'Galaxy S22 Ultra', 'Galaxy Z Fold5',
            'Galaxy Z Flip4', 'Galaxy A54', 'Galaxy M33'
        ],
        'Apple' => [
            'iPhone 15 Pro Max', 'iPhone 15', 'iPhone 14 Pro',
            'iPhone 13', 'iPhone SE (2022)', 'iPhone 12 Mini'
        ],
        'Xiaomi' => [
            'Redmi Note 13 Pro+', 'Poco F5 Pro', 'Mi 11 Ultra',
            'Redmi 12', 'Xiaomi 13T', 'Poco X5'
        ],
        'LG' => [
            'LG Velvet', 'LG Wing', 'LG G8X ThinQ'
        ],
        'Sony' => [
            'Xperia 1 V', 'Xperia 5 IV', 'Xperia 10 III'
        ],
        'Asus' => [
            'ROG Phone 7', 'Zenfone 10', 'ROG Phone 6D Ultimate'
        ],
        'Honor' => [
            'Honor 90', 'Honor Magic 5 Pro', 'Honor X9b'
        ],
        'Google' => [
            'Pixel 8 Pro', 'Pixel 7a', 'Pixel Fold'
        ],
        'OnePlus' => [
            'OnePlus 11', 'OnePlus Nord 3', 'OnePlus Ace 2V'
        ],
        'Huawei' => [
            'P60 Pro', 'Mate X3', 'Nova 11i'
        ]
    ];
    
    private function requireAdmin() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /');
            exit;
        }
    }    

    public function __construct() {
        $loader = new FilesystemLoader(__DIR__ . '/../views');
        $this->twig = new Environment($loader);

        $this->twig->addGlobal('session', $_SESSION);
    }

    public function orders() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /');
            exit;
        }

        $orderModel = new Order();
        $orders = $orderModel->all();

        echo $this->twig->render('orders.twig', [
            'title' => 'Список заказов',
            'orders' => $orders
        ]);
    }

    
    
    public function delete() {
        $id = $_POST['id'] ?? null;
    
        if ($id) {
            $orderModel = new \Myshop\Electronics\Model\Order();
            $orderModel->deleteById((int)$id);
        }
    
        header('Location: /orders');
        exit;
    }
    
    public function exportToCsv() {
        $orderModel = new \Myshop\Electronics\Model\Order();
        $orders = $orderModel->all();
    
        $file = __DIR__ . '/../../orders.csv';
        $handle = fopen($file, 'w');
    
        foreach ($orders as $order) {
            fputcsv($handle, [
                $order['created_at'],
                $order['name'],
                $order['email'],
                $order['phone'],
                $order['brand'],
                $order['model'],
                $order['quantity']
            ], ';');
        }
    
        fclose($handle);
    
        header('Location: /orders');
        exit;
    }

    
    public function importCsv() {
        $file = __DIR__ . '/../../orders.csv';
    
        if (file_exists($file) && ($handle = fopen($file, "r")) !== false) {
            $orderModel = new \Myshop\Electronics\Model\Order();
    
            while (($data = fgetcsv($handle, 1000, ";")) !== false) {
                if (count($data) < 7) continue;
    
                list($created_at, $name, $email, $phone, $brand, $model, $quantity) = $data;
                $orderModel->createCompact($created_at, $name, $email, $phone, $brand, $model, $quantity);
            }
    
            fclose($handle);
        }
    
        header('Location: /orders');
        exit;
    }

    public function deleteAll() {
        $orderModel = new \Myshop\Electronics\Model\Order();
        $orderModel->deleteAll();
    
        header('Location: /orders');
        exit;
    }
    
    
    public function index() {
        
        $message = $_SESSION['success'] ?? null;
        unset($_SESSION['success']);
    
        echo $this->twig->render('index.twig', [
            'title' => 'Магазин электроники',
            'message' => $message ?? null,
            'errors' => $errors ?? [],
            'old' => $data ?? [],
            'brands' => array_keys($this->brandModels),
            'models' => $this->brandModels
        ]);
    }
    
    
    public function submit() {
        $data = [
            'email' => $_SESSION['user']['email'] ?? '',
            'name' => $_SESSION['user']['name'] ?? '',
            'phone' => $_SESSION['user']['phone'] ?? '',
            'brand' => trim($_POST['brand'] ?? ''),
            'model' => trim($_POST['model'] ?? ''),
            'quantity' => (int) ($_POST['quantity'] ?? 1)
        ];
    
        $errors = [];
    
        // Валидация
        if (!$data['name']) $errors[] = "ФИО обязательно.";
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "Неверный email.";
        if (!preg_match("/^\+?\d{10,13}$/", $data['phone'])) $errors[] = "Телефон некорректный.";
        if (!$data['brand']) $errors[] = "Укажите бренд.";
        if (!$data['model']) $errors[] = "Укажите модель.";
        if ($data['quantity'] < 1) $errors[] = "Количество должно быть больше 0.";
    
        // Поиск ID продукта
        $productModel = new \Myshop\Electronics\Model\Product();
        $product = $productModel->findByBrandModel($data['brand'], $data['model']);
    
        if (!$product) {
            $errors[] = "Товар не найден.";
        } elseif ($data['quantity'] > $product['stock']) {
            $errors[] = "Недостаточно товара на складе. В наличии: {$product['stock']}.";
        }
    
        if (!isset($_SESSION['user'])) {
            $errors[] = "Вы должны войти в систему, чтобы оформить заказ.";
        }
    
        if (!empty($errors)) {
            echo $this->twig->render('index.twig', [
                'title' => 'Магазин электроники',
                'errors' => $errors,
                'old' => $data,
                'brands' => array_keys($this->brandModels),
                'models' => $this->brandModels
            ]);
            return;
        }
    
        $orderModel = new \Myshop\Electronics\Model\Order();
        $orderModel->create($_SESSION['user']['id'], $product['id'], $data['quantity']);
    
        $productModel->decreaseStock($data['brand'], $data['model'], $data['quantity']);
    
        $_SESSION['success'] = "Спасибо, {$data['name']}! Заказ оформлен.";
        header('Location: /');
        exit;
    }
    
    
}
