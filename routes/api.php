<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;

Route::get('/', fn () => response()->json(['message' => 'API Productos OK']));

// ✅ RUTAS ESENCIALES PARA EL FRONTEND - MANTENER ACTIVAS
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{id}', [ProductController::class, 'show']);
Route::post('products', [ProductController::class, 'store']);
Route::put('products/{id}', [ProductController::class, 'update']);
Route::delete('products/{id}', [ProductController::class, 'destroy']);

// 🔧 RUTA ADICIONAL NECESARIA PARA LAS CATEGORÍAS
Route::get('categories', function() {
    return response()->json(\App\Models\Category::all(['id', 'name']));
});

// ❌ COMENTADAS - No necesarias para el CRUD básico del frontend
// Route::get('products/available-for-sales', [ProductController::class, 'availableForSales']);

// ❌ COMENTADAS - Rutas de stock (para sistemas de ventas)
// Route::prefix('products')->group(function () {
//     Route::post('check', [ProductController::class, 'checkStock']);           // POST /api/products/check
//     Route::post('update-stock', [ProductController::class, 'updateStock']);   // POST /api/products/update-stock
// });

// ❌ COMENTADO - Middleware de autenticación (para más adelante)
// Route::middleware('auth:api')->group(function () {
//     // Rutas protegidas irían aquí
// });