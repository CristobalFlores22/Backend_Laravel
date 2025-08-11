<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use Faker\Factory as Faker;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('es_MX');

        $categories = [
            'blanco','integral','dulce','artesanal','sin_gluten',
            'regional','enriquecido','de_molde','crujiente',
            'dulce_relleno','salado','festivo','vegano'
        ];

        $bases = [
            'Pan', 'Baguette', 'Concha', 'Cuernito', 'Telera', 'Bolillo',
            'Rosca', 'Panqué', 'Muffin', 'Empanada', 'Churro', 'Dona',
            'Pan de muerto', 'Focaccia', 'Ciabatta', 'Biscuit', 'Scone'
        ];

        $creados = 0;
        $intentos = 0;

        // Genera al menos 200 productos, evitando duplicados (name+category)
        while ($creados < 200 && $intentos < 1000) {
            $intentos++;

            $category = $faker->randomElement($categories);
            $base = $faker->randomElement($bases);

            $name = trim(substr($base . ' ' . ($category === 'blanco' ? 'tradicional' : $category), 0, 100));

            $purchase = $faker->randomFloat(2, 1, 10);
            $sale = $faker->randomFloat(2, $purchase + 1, $purchase + 10);

            // Evita duplicados por (name, category)
            $exists = Product::where('name', $name)->where('category', $category)->exists();
            if ($exists) {
                continue;
            }

            Product::create([
                'name' => $name,
                'description' => substr($faker->sentence(10) . ' Ideal para ' . $category . '.', 0, 1000),
                'purchase_price' => $purchase,
                'sale_price' => $sale,
                'category' => $category,
                'stock' => $faker->numberBetween(5, 50),
            ]);

            $creados++;
        }
    }
}
