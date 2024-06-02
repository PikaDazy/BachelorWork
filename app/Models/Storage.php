<?php

namespace App\Models;

use App\Models\Product;
use App\Models\Material;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\DB;

class Storage extends Model
{
    use HasFactory;

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('storage_quantity');
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class)->withPivot('storage_quantity');
    }

    public function items(): BelongsToMany
    {
        $fixQuery = [
            'product_storage.storage_id as pivot_storage_id',
            'product_storage.product_id as pivot_material_id',
            'product_storage.storage_quantity as pivot_storage_quantity'
        ];
        $products = $this->products()->select('id', 'name', 'url', 'storage_quantity',
            DB::raw("'Продукт' as type"), ...$fixQuery
        );

        $materials = $this->materials()->select('id', 'name', 'url', 'storage_quantity',
            DB::raw("'Матеріал' as type"));

        return $materials->union($products)->orderBy('id');
    }
}
