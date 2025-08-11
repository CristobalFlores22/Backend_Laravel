<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;

Route::get('/', fn () => response()->json(['message' => 'API Productos OK']));

// Público (si el profe lo permite)
Route::get('products', [ProductController::class, 'index']);
Route::get('products/available-for-sales', [ProductController::class, 'availableForSales']);
Route::get('products/{id}', [ProductController::class, 'show']);

// Protegido (si ya usan JWT, descomenta el middleware)
// Route::middleware('auth:api')->group(function () {
    Route::post('products', [ProductController::class, 'store']);
    Route::put('products/{id}', [ProductController::class, 'update']);
    Route::delete('products/{id}', [ProductController::class, 'destroy']);

    // Stock helpers para Ventas API
    Route::post('products/stock/check', [ProductController::class, 'checkStock']);
    Route::post('products/stock/update', [ProductController::class, 'updateStock']);
// });
