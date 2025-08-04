<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;

Route::get('/', function () {
    return response()->json(['message' => 'API funcionando correctamente']);
});

// Definir rutas RESTful para el recurso "products"
Route::resource('products', ProductController::class)
    ->only(['index', 'store', 'show', 'update', 'destroy'])
    ->names('api.products'); // Aquí el nombre debe coincidir con el recurso 'products'
