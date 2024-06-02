<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Manufacture extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'address'
    ];

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class)->withPivot('price');
    }
}
