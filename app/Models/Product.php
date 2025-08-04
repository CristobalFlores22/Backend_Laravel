<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'purchase_price',
        'sale_price',
        'category',
        'stock',
    ];
}
