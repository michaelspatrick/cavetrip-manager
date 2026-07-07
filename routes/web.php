<?php

declare(strict_types=1);

use CaveTrip\Controllers\AuthController;
use CaveTrip\Controllers\CaveController;
use CaveTrip\Controllers\DashboardController;
use CaveTrip\Controllers\GrottoSettingsController;
use CaveTrip\Controllers\LandownerController;
use CaveTrip\Controllers\TripController;
use CaveTrip\Controllers\TripParticipantController;
use CaveTrip\Controllers\UserController;
use CaveTrip\Core\Application;
use CaveTrip\Core\Router;
use CaveTrip\Core\View;

$router = new Router();

$router->get('/', static fn (Application $app): string => View::render($app, 'pages/home', [
    'title' => 'CaveTrip Manager',
]));

$router->get('/login', [new AuthController(), 'showLogin']);
$router->post('/login', [new AuthController(), 'login']);
$router->post('/logout', [new AuthController(), 'logout']);

$router->get('/dashboard', [new DashboardController(), 'index']);

$router->get('/admin/grotto/settings', [new GrottoSettingsController(), 'edit']);
$router->post('/admin/grotto/settings', [new GrottoSettingsController(), 'update']);

$router->get('/users', [new UserController(), 'index']);
$router->get('/users/create', [new UserController(), 'create']);
$router->post('/users', [new UserController(), 'store']);


$router->get('/trips', [new TripController(), 'index']);
$router->get('/trips/create', [new TripController(), 'create']);
$router->post('/trips', [new TripController(), 'store']);
$router->get('/trips/show', [new TripController(), 'show']);
$router->get('/trips/edit', [new TripController(), 'edit']);
$router->post('/trips/update', [new TripController(), 'update']);
$router->post('/trips/cancel', [new TripController(), 'cancel']);
$router->post('/trips/participants/add', [new TripParticipantController(), 'add']);
$router->post('/trips/participants/remove', [new TripParticipantController(), 'remove']);
$router->get('/trip/signup', [new TripParticipantController(), 'publicSignup']);
$router->post('/trip/signup', [new TripParticipantController(), 'publicSignupStore']);

$router->get('/landowners', [new LandownerController(), 'index']);
$router->get('/landowners/create', [new LandownerController(), 'create']);
$router->post('/landowners', [new LandownerController(), 'store']);
$router->get('/landowners/edit', [new LandownerController(), 'edit']);
$router->post('/landowners/update', [new LandownerController(), 'update']);

$router->get('/caves', [new CaveController(), 'index']);
$router->get('/caves/create', [new CaveController(), 'create']);
$router->post('/caves', [new CaveController(), 'store']);
$router->get('/caves/edit', [new CaveController(), 'edit']);
$router->post('/caves/update', [new CaveController(), 'update']);

$router->get('/health', static function (Application $app): string {
    $dbStatus = 'not checked';
    try {
        $app->db()->query('SELECT 1');
        $dbStatus = 'ok';
    } catch (Throwable $e) {
        http_response_code(500);
        $dbStatus = 'database error: ' . $e->getMessage();
    }

    header('Content-Type: application/json');
    return json_encode([
        'app' => 'CaveTrip Manager',
        'status' => http_response_code() === 500 ? 'error' : 'ok',
        'database' => $dbStatus,
        'time' => date(DATE_ATOM),
    ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
});

return $router;
