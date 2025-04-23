<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use Myshop\Electronics\Core\Router;
use Myshop\Electronics\Controller\FormController;
use Myshop\Electronics\Controller\AuthController;

$auth = new AuthController();
$router = new Router();
$form = new FormController();

$router->get('/submit', [$form, 'index']);
$router->post('/submit', [$form, 'submit']); 
$router->get('/orders', [$form, 'orders']);
$router->post('/orders/delete', [$form, 'delete']);
$router->post('/orders/import', [$form, 'importCsv']);
$router->post('/orders/delete-all', [$form, 'deleteAll']);
$router->post('/orders/export-csv', [$form, 'exportToCsv']);
$router->get('/register', [$auth, 'showRegister']);
$router->post('/register', [$auth, 'register']);
$router->get('/cart', [$form, 'index']);
$router->get('/login', [$auth, 'showLogin']); 
$router->post('/login', [$auth, 'login']);    
$router->get('/logout', [$auth, 'logout']);
$router->get('/cabinet', [$auth, 'cabinet']);

$router->get('/', function () {
    $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../src/views');
    $twig = new \Twig\Environment($loader);
    $twig->addGlobal('session', $_SESSION);
    echo $twig->render('home.twig', [
        'title' => 'Главная',
    ]);
});






$router->resolve();
