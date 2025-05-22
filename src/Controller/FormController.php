<?php

namespace Myshop\Electronics\Controller;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Myshop\Electronics\Model\Order;
use Myshop\Electronics\Model\Product;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

class FormController {
    private Environment $twig;

    private array $brandModels = [
        'Samsung' => ['Galaxy S23', 'Galaxy S22 Ultra', 'Galaxy Z Fold5', 'Galaxy Z Flip4', 'Galaxy A54', 'Galaxy M33'],
        'Apple' => ['iPhone 15 Pro Max', 'iPhone 15', 'iPhone 14 Pro', 'iPhone 13', 'iPhone SE (2022)', 'iPhone 12 Mini'],
        'Xiaomi' => ['Redmi Note 13 Pro+', 'Poco F5 Pro', 'Mi 11 Ultra', 'Redmi 12', 'Xiaomi 13T', 'Poco X5'],
        'LG' => ['LG Velvet', 'LG Wing', 'LG G8X ThinQ'],
        'Sony' => ['Xperia 1 V', 'Xperia 5 IV', 'Xperia 10 III'],
        'Asus' => ['ROG Phone 7', 'Zenfone 10', 'ROG Phone 6D Ultimate'],
        'Honor' => ['Honor 90', 'Honor Magic 5 Pro', 'Honor X9b'],
        'Google' => ['Pixel 8 Pro', 'Pixel 7a', 'Pixel Fold'],
        'OnePlus' => ['OnePlus 11', 'OnePlus Nord 3', 'OnePlus Ace 2V'],
        'Huawei' => ['P60 Pro', 'Mate X3', 'Nova 11i']
    ];

    public function __construct() {
        $loader = new FilesystemLoader(__DIR__ . '/../views');
        $this->twig = new Environment($loader);
        $this->twig->addGlobal('session', $_SESSION);
    }

    private function requireAdmin() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /');
            exit;
        }
    }

    public function index() {
        $message = $_SESSION['success'] ?? null;
        unset($_SESSION['success']);

        echo $this->twig->render('index.twig', [
            'title' => 'Магазин электроники',
            'message' => $message ?? null,
            'errors' => [],
            'old' => [],
            'brands' => array_keys($this->brandModels),
            'models' => $this->brandModels
        ]);
    }

    public function submit() {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'brand' => trim($_POST['brand'] ?? ''),
            'model' => trim($_POST['model'] ?? ''),
            'quantity' => (int) ($_POST['quantity'] ?? 1)
        ];

        $errors = [];

        if (!$data['brand']) $errors[] = "Укажите бренд.";
        if (!$data['model']) $errors[] = "Укажите модель.";
        if ($data['quantity'] < 1) $errors[] = "Количество должно быть больше 0.";

        $productModel = new Product();
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

        (new Order())->create($_SESSION['user']['id'], $product['id'], $data['quantity']);
        $productModel->decreaseStock($data['brand'], $data['model'], $data['quantity']);

        $_SESSION['success'] = "Спасибо, {$data['name']}! Заказ оформлен.";
        header('Location: /');
        exit;
    }

    public function orders() {
        $this->requireAdmin();
        $orders = (new Order())->all();
        echo $this->twig->render('orders.twig', [
            'title' => 'Список заказов',
            'orders' => $orders
        ]);
    }

    public function delete() {
        $id = $_POST['id'] ?? null;
        if ($id) {
            (new Order())->deleteById((int)$id);
        }
        header('Location: /orders');
        exit;
    }

    public function deleteAll() {
        (new Order())->deleteAll();
        header('Location: /orders');
        exit;
    }

    public function importCsv() {
        $file = __DIR__ . '/../../orders.csv';

        if (!file_exists($file)) {
            $_SESSION['success'] = "Файл orders.csv не найден.";
            header('Location: /orders');
            exit;
        }

        $handle = fopen($file, 'r');
        $orderModel = new \Myshop\Electronics\Model\Order();

        $isHeader = true;

        while (($data = fgetcsv($handle, 1000, ";")) !== false) {
            if ($isHeader) {
                $isHeader = false;
                continue;
            }

            if (count($data) < 7) continue;

            [$created_at, $user_name, $email, $phone, $brand, $model, $quantity] = $data;

            $user = $orderModel->findUserByEmail($email);
            if (!$user) continue;

            $product = $orderModel->findProductByBrandModel($brand, $model);
            if (!$product) continue;

            $orderModel->insertOrder($user['id'], $product['id'], (int)$quantity, $created_at);
        }

        fclose($handle);

        $_SESSION['success'] = "Импорт завершён.";
        header('Location: /orders');
        exit;
    }


    public function exportCsv() {
        $orders = (new \Myshop\Electronics\Model\Order())->all();
        $file = __DIR__ . '/../../orders.csv';
        $handle = fopen($file, 'w');

        fwrite($handle, "created_at;user_name;user_email;user_phone;brand;model;quantity\n");

        foreach ($orders as $order) {
            fwrite($handle, implode(';', [
                $order['created_at'],
                $order['user_name'],
                $order['email'],
                $order['phone'],
                $order['brand'],
                $order['model'],
                $order['quantity']
            ]) . "\n");
        }

        fclose($handle);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="orders.csv"');
        readfile($file);
        exit;
    }

    

    public function exportToExcel() {
        $orders = (new Order())->allWithDetails();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Дата');
        $sheet->setCellValue('B1', 'ФИО');
        $sheet->setCellValue('C1', 'Email');
        $sheet->setCellValue('D1', 'Телефон');
        $sheet->setCellValue('E1', 'Бренд');
        $sheet->setCellValue('F1', 'Модель');
        $sheet->setCellValue('G1', 'Кол-во');

        $row = 2;
        foreach ($orders as $order) {
            $sheet->setCellValue("A{$row}", $order['created_at']);
            $sheet->setCellValue("B{$row}", $order['user_name']);
            $sheet->setCellValue("C{$row}", $order['email']);
            $sheet->setCellValue("D{$row}", $order['phone']);
            $sheet->setCellValue("E{$row}", $order['brand']);
            $sheet->setCellValue("F{$row}", $order['model']);
            $sheet->setCellValue("G{$row}", $order['quantity']);
            $row++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="orders.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function exportToPdf() {
        $orders = (new Order())->allWithDetails();
        $html = '<h2 style="text-align:center;">Отчёт по заказам</h2>';
        $html .= '<table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">';
        $html .= '
            <thead>
                <tr>
                    <th><strong>Дата</strong></th>
                    <th><strong>ФИО</strong></th>
                    <th><strong>Email</strong></th>
                    <th><strong>Телефон</strong></th>
                    <th><strong>Бренд</strong></th>
                    <th><strong>Модель</strong></th>
                    <th><strong>Кол-во</strong></th>
                </tr>
            </thead><tbody>';

        foreach ($orders as $order) {
            $html .= "<tr>
                <td>{$order['created_at']}</td>
                <td>{$order['user_name']}</td>
                <td>{$order['email']}</td>
                <td>{$order['phone']}</td>
                <td>{$order['brand']}</td>
                <td>{$order['model']}</td>
                <td>{$order['quantity']}</td>
            </tr>";
        }

        $html .= '</tbody></table>';

        $mpdf = new Mpdf();
        $mpdf->WriteHTML($html);
        $mpdf->Output('orders_report.pdf', \Mpdf\Output\Destination::DOWNLOAD);
    }
}
