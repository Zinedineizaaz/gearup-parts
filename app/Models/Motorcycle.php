<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Motorcycle extends Model
{
    //

    public function parts()
    {
        return $this->belongsToMany(Product::class, 'product_fitments', 'motorcycle_id', 'product_id');
    }
}
