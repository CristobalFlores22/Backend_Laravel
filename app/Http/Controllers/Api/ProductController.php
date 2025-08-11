<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json(Product::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'category' => 'required|in:blanco,integral,dulce,artesanal,sin_gluten,regional,enriquecido,de_molde,crujiente,dulce_relleno,salado,festivo,vegano',
            'stock' => 'required|integer|min:0',
        ]);

        $product = Product::create($validated);
        return response()->json($product, 201);
    }

    public function show($id)
    {
        return response()->json(Product::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string|max:1000',
            'purchase_price' => 'sometimes|numeric|min:0',
            'sale_price' => 'sometimes|numeric|min:0',
            'category' => 'sometimes|in:blanco,integral,dulce,artesanal,sin_gluten,regional,enriquecido,de_molde,crujiente,dulce_relleno,salado,festivo,vegano',
            'stock' => 'sometimes|integer|min:0',
        ]);

        $product = Product::findOrFail($id);
        $product->update($validated);
        return response()->json($product);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return response()->json(null, 204);
    }

    public function availableForSales()
    {
        return response()->json([
            'products' => Product::available()
                ->orderBy('name')
                ->get(['id', 'name', 'sale_price', 'stock', 'category']),
            'categories' => [
                'blanco', 'integral', 'dulce', 'artesanal', 'sin_gluten',
                'regional', 'enriquecido', 'de_molde', 'crujiente',
                'dulce_relleno', 'salado', 'festivo', 'vegano'
            ]
        ]);
    }

    // En ProductController

    public function checkStock(Request $request)
    {
        $items = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1'
        ]);

        $products = Product::whereIn('id', collect($items['items'])->pluck('product_id'))->get();

        $responseProducts = [];
        foreach ($items['items'] as $item) {
            $product = $products->firstWhere('id', $item['product_id']);
            if (!$product || $product->stock < $item['quantity']) {
                return response()->json([
                    'message' => 'Stock insuficiente para el producto: ' . ($product->name ?? 'Desconocido')
                ], 422);
            }
            $responseProducts[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->sale_price,
                'quantity' => $item['quantity']
            ];
        }

        return response()->json(['products' => $responseProducts]);
    }

    public function updateStock(Request $request)
        {
            $data = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|integer|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'operation' => 'required|in:increment,decrement'
            ]);

            DB::beginTransaction();
            try {
                foreach ($data['items'] as $item) {
                    $id = $item['product_id'];
                    $qty = (int) $item['quantity'];

                    if ($data['operation'] === 'decrement') {
                        // UPDATE atómico: solo descuenta si hay stock suficiente
                        $updated = DB::update(
                            "UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?",
                            [$qty, $id, $qty]
                        );
                        if ($updated === 0) {
                            DB::rollBack();
                            $name = optional(Product::find($id))->name ?? 'Desconocido';
                            return response()->json(['message' => "Stock insuficiente para $name"], 422);
                        }
                    } else {
                        DB::update("UPDATE products SET stock = stock + ? WHERE id = ?", [$qty, $id]);
                    }
                }

                DB::commit();
                return response()->json(['message' => 'Stock actualizado correctamente']);
            } catch (\Throwable $e) {
                DB::rollBack();
                return response()->json(['message' => 'Error al actualizar stock'], 500);
            }
        }

}