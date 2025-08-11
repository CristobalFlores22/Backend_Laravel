<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->decimal('purchase_price', 10, 2); // 10,2
            $table->decimal('sale_price', 10, 2);     // 10,2
            $table->string('category', 50);           // NOT NULL y 50
            $table->unsignedInteger('stock')->default(0);
            $table->timestamps();

            // Evitar duplicados por nombre (o por nombre+categoría si prefieres)
            $table->unique(['name', 'category']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
