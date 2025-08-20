<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'blanco','integral','dulce','artesanal','sin_gluten','regional',
            'enriquecido','de_molde','crujiente','dulce_relleno','salado','festivo','vegano'
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
    }
}
