<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use Myshop\Electronics\Core\Router;
use Myshop\Electronics\Controller\FormController;

$router = new Router();
$form = new FormController();

$router->get('/', [$form, 'index']);
$router->post('/submit', [$form, 'submit']);
$router->get('/orders', [$form, 'orders']);
$router->post('/orders/delete', [$form, 'delete']);
$router->post('/orders/import', [$form, 'importCsv']);
$router->post('/orders/delete-all', [$form, 'deleteAll']);
$router->post('/orders/export-csv', [$form, 'exportToCsv']);



$router->resolve();
