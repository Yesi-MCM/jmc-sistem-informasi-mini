<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api/documentation', function () {
    return view('swagger');
});

Route::get('/debug-info', function () {
    return response()->json([
        'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? null,
        'request_path' => request()->path(),
        'base_url' => request()->getBaseUrl(),
        'base_path' => base_path(),
        'routes' => collect(Route::getRoutes())->map(function ($route) {
            return [
                'uri' => $route->uri(),
                'methods' => $route->methods(),
            ];
        })->values(),
    ]);
});
