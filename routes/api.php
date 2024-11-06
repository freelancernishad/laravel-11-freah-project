<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Server\ServerStatusController;

// Load users and admins route files
if (file_exists($userRoutes = __DIR__.'/users.php')) {
    require $userRoutes;
}

if (file_exists($adminRoutes = __DIR__.'/admins.php')) {
    require $adminRoutes;
}



Route::get('/server-status', [ServerStatusController::class, 'checkStatus']);
