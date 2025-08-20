<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use Faker\Factory as Faker;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('es_MX');

        $categoryNames = [
            'blanco','integral','dulce','artesanal','sin_gluten',
            'regional','enriquecido','de_molde','crujiente',
            'dulce_relleno','salado','festivo','vegano'
        ];

        $bases = [
            'Pan', 'Baguette', 'Concha', 'Cuernito', 'Telera', 'Bolillo',
            'Rosca', 'Panqué', 'Muffin', 'Empanada', 'Churro', 'Dona',
            'Pan de muerto', 'Focaccia', 'Ciabatta', 'Biscuit', 'Scone'
        ];

        $categories = Category::whereIn('name', $categoryNames)->get()->keyBy('name');

        $creados = 0;
        $intentos = 0;

        while ($creados < 200 && $intentos < 1000) {
            $intentos++;

            $nameBase = $faker->randomElement($bases);
            $catName = $faker->randomElement($categoryNames);
            $category = $categories[$catName] ?? null;

            if (!$category) continue;

            $name = trim(substr($nameBase . ' ' . ($catName === 'blanco' ? 'tradicional' : $catName), 0, 100));
            $purchase = $faker->randomFloat(2, 1, 10);
            $sale = $faker->randomFloat(2, $purchase + 1, $purchase + 10);

            // Evitar duplicados por (name, category_id)
            $exists = Product::where('name', $name)->where('category_id', $category->id)->exists();
            if ($exists) continue;

            Product::create([
                'name' => $name,
                'description' => substr($faker->sentence(10) . ' Ideal para ' . $catName . '.', 0, 1000),
                'purchase_price' => $purchase,
                'sale_price' => $sale,
                'stock' => $faker->numberBetween(5, 50),
                'category_id' => $category->id,
                'iva' => 16.00,
            ]);

            $creados++;
        }
    }
}
