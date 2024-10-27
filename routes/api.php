<?php

use Illuminate\Support\Facades\Route;

// Load users and admins route files
if (file_exists($userRoutes = __DIR__.'/users.php')) {
    require $userRoutes;
}

if (file_exists($adminRoutes = __DIR__.'/admins.php')) {
    require $adminRoutes;
}

// Other route definitions
