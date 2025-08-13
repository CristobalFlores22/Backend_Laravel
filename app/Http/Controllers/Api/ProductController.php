<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

class ProductController extends Controller
{
    /** GET /products?search=&category=&per_page= */
    public function index(Request $request)
    {
        $q = Product::query();

        if ($s = $request->query('search')) {
            $q->where('name', 'like', "%{$s}%");
        }
        if ($c = $request->query('category')) {
            $q->where('category', $c);
        }

        $perPage = min((int)$request->query('per_page', 20), 100);

        return response()->json(
            $q->orderBy('name')->paginate($perPage)
        );
    }

    public function store(Request $request)
    {
        $categories = 'blanco,integral,dulce,artesanal,sin_gluten,regional,enriquecido,de_molde,crujiente,dulce_relleno,salado,festivo,vegano';

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'category' => "required|in:$categories",
            'stock' => 'required|integer|min:0',
        ]);

        // Regla de negocio: venta >= compra
        if ($validated['sale_price'] < $validated['purchase_price']) {
            return response()->json([
                'message' => 'El precio de venta no puede ser menor al precio de compra'
            ], 422);
        }

        // Unicidad por (name, category)
        $exists = Product::where('name', $validated['name'])
            ->where('category', $validated['category'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Ya existe un producto con ese nombre en la misma categoría'
            ], 422);
        }

        $product = Product::create($validated);
        return response()->json($product, 201);
    }

    public function show($id)
    {
        return response()->json(Product::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $categories = 'blanco,integral,dulce,artesanal,sin_gluten,regional,enriquecido,de_molde,crujiente,dulce_relleno,salado,festivo,vegano';

        $rules = [
            'name' => 'sometimes|string|max:100',
            'description' => 'sometimes|nullable|string|max:1000',
            'purchase_price' => 'sometimes|numeric|min:0',
            'sale_price' => 'sometimes|numeric|min:0',
            'category' => "sometimes|in:$categories",
            'stock' => 'sometimes|integer|min:0',
        ];

        $data = $request->validate($rules);

        $product = Product::findOrFail($id);

        // Mezcla valores efectivos para validar reglas de negocio
        $effective = array_merge($product->only([
            'name','description','purchase_price','sale_price','category','stock'
        ]), $data);

        if (isset($effective['sale_price'], $effective['purchase_price'])
            && $effective['sale_price'] < $effective['purchase_price']) {
            return response()->json([
                'message' => 'El precio de venta no puede ser menor al precio de compra'
            ], 422);
        }

        if (isset($effective['name']) || isset($effective['category'])) {
            $exists = Product::where('name', $effective['name'])
                ->where('category', $effective['category'])
                ->where('id', '!=', $product->id)
                ->exists();
            if ($exists) {
                return response()->json([
                    'message' => 'Ya existe un producto con ese nombre en la misma categoría'
                ], 422);
            }
        }

        $product->update($data);
        return response()->json($product);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return response()->json(null, 204);
    }

    /** GET /products/available-for-sales */
    public function availableForSales()
    {
        return response()->json([
            'products' => Product::available()
                ->orderBy('name')
                ->get(['id', 'name', 'sale_price', 'stock', 'category']),
            'categories' => [
                'blanco','integral','dulce','artesanal','sin_gluten','regional',
                'enriquecido','de_molde','crujiente','dulce_relleno','salado','festivo','vegano'
            ]
        ]);
    }

    /** POST /products/stock/check */
    public function checkStock(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        // Traemos los productos involucrados
        $ids = collect($data['items'])->pluck('product_id')->unique()->values();
        $products = Product::whereIn('id', $ids)->get()->keyBy('id');

        // Validar existencias
        $insufficient = [];
        foreach ($data['items'] as $it) {
            $p = $products[$it['product_id']];
            if ($p->stock < $it['quantity']) {
                $insufficient[] = [
                    'product_id' => $p->id,
                    'name'       => $p->name,
                    'requested'  => (int)$it['quantity'],
                    'available'  => (int)$p->stock,
                ];
            }
        }

        if (!empty($insufficient)) {
            return response()->json([
                'message' => 'Stock insuficiente',
                'details' => $insufficient
            ], 422);
        }

        // Estructura esperada por tu frontend (price = sale_price)
        $responseProducts = collect($data['items'])->map(function ($it) use ($products) {
            $p = $products[$it['product_id']];
            return [
                'product_id' => $p->id,
                'name'       => $p->name,
                'price'      => (float)$p->sale_price,
                'quantity'   => (int)$it['quantity'],
            ];
        })->values();

        return response()->json(['products' => $responseProducts], 200);
    }

    /** POST /products/stock/update */
    public function updateStock(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'operation'          => 'required|in:increment,decrement',
        ]);

        // Actualización atómica con bloqueo de fila
        DB::transaction(function () use ($data) {
            foreach ($data['items'] as $it) {
                // Bloqueo de fila para concurrencia segura
                $product = Product::where('id', $it['product_id'])->lockForUpdate()->first();

                if ($data['operation'] === 'decrement') {
                    if ($product->stock < $it['quantity']) {
                        // Si no alcanza, abortamos transacción
                        abort(response()->json([
                            'message' => "Stock insuficiente para {$product->name}",
                            'details' => [
                                'product_id' => $product->id,
                                'requested'  => (int)$it['quantity'],
                                'available'  => (int)$product->stock,
                            ]
                        ], 422));
                    }
                    $product->decrement('stock', $it['quantity']);
                } else {
                    $product->increment('stock', $it['quantity']);
                }
            }
        });

        return response()->json([
            'message' => 'Stock actualizado',
            'items'   => $data['items'],
            'operation' => $data['operation'],
        ], 200);
    }
}

