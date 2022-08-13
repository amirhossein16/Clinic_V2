<?php

require_once __DIR__ . './../config/config.php';
require_once __DIR__ . './../vendor/autoload.php';

use App\Controller\AuthController;
use App\Controller\DashboardController;
use App\Controller\DoctorController;
use App\Controller\PageController;
use Core\Application;

session_start();

$app = new Application;

$app->route->get('/', [PageController::class , 'home']);
$app->route->get('/section', [PageController::class ,  'section']);

$app->route->get('/doctors', [DoctorController::class , 'doctors']);
$app->route->get('/profile', [DoctorController::class , 'profile']);
$app->route->post('/profile', [DoctorController::class , 'getVisitTime']);

$app->route->get('/register', [AuthController::class , 'showRegister']);
$app->route->post('/register', [AuthController::class , 'register']);
$app->route->get('/login', [AuthController::class , 'showLogin']);
$app->route->post('/login', [AuthController::class , 'login']);
$app->route->get('/logout', [AuthController::class , 'logout']);

$app->route->get('/dashboard', [DashboardController::class , 'main']);
$app->route->post('/dashboard', [DashboardController::class , 'addSection']);
$app->route->delete('/dashboard', [DashboardController::class , 'deleteSection']);
$app->route->put('/dashboard', [DashboardController::class , 'editSection']);
$app->route->get('/dashboard/editProfile', [DashboardController::class , 'showEditProfile']);
$app->route->put('/dashboard/editProfile', [DashboardController::class , 'editProfile']);
$app->route->get('/dashboard/requests', [DashboardController::class , 'showRequests']);
$app->route->put('/dashboard/requests', [DashboardController::class , 'requests']);
$app->route->get('/dashboard/appointment', [DashboardController::class , 'appointment']);
$app->route->get('/dashboard/section', [DashboardController::class , 'sectionDetails']);
$app->route->delete('/dashboard/section', [DashboardController::class , 'deleteDoctorFromSection']);
$app->route->post('/dashboard/section', [DashboardController::class , 'addDoctorToSection']);



$app->run();