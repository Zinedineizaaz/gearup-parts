<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    //

    public function fitments()
    {
        // Relasi Many-to-Many menggunakan tabel pivot 'product_fitments'
        return $this->belongsToMany(Motorcycle::class, 'product_fitments', 'product_id', 'motorcycle_id');
    }
}
