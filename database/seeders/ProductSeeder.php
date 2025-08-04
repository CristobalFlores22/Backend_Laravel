<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use Faker\Factory as Faker;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $categories = [
            'blanco', 'integral', 'dulce', 'artesanal', 'sin_gluten',
            'regional', 'enriquecido', 'de_molde', 'crujiente',
            'dulce_relleno', 'salado', 'festivo', 'vegano'
        ];

        $productNames = [
            'Pan de', 'Baguette', 'Concha', 'Cuernito', 'Telera', 'Bolillo',
            'Rosca', 'Panqué', 'Muffin', 'Empanada', 'Churro', 'Dona',
            'Pan de muerto', 'Focaccia', 'Ciabatta', 'Biscuit', 'Scone'
        ];

        for ($i = 1; $i <= 200; $i++) {
            $category = $faker->randomElement($categories);
            $baseName = $faker->randomElement($productNames);
            $purchasePrice = $faker->randomFloat(2, 1, 10); // Compra entre 1.00 y 10.00
            $salePrice = $faker->randomFloat(2, $purchasePrice + 1, $purchasePrice + 10); // Venta con margen

            Product::create([
                'name' => $baseName . ' ' . ($category === 'blanco' ? 'tradicional' : strtolower($category)),
                'description' => $faker->sentence(10) . ' Ideal para ' . strtolower($category) . '.',
                'purchase_price' => $purchasePrice,
                'sale_price' => $salePrice,
                'category' => $category,
                'stock' => $faker->numberBetween(5, 50)
            ]);
        }
    }
}
