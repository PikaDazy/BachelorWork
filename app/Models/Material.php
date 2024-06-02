<?php

namespace App\Models;

use App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Material extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $fillable = ['name', 'description', 'url', 'quantity'];

    public function manufacrures(): BelongsToMany
    {
        return $this->belongsToMany(Manufacture::class);
    }
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function storages(): BelongsToMany
    {
        return $this->belongsToMany(Storage::class)->withPivot('storage_quantity');
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class);
    }

    public function fakeDelete(): bool
    {
        $orders = $this->orders()->where('is_finalized', false)->get();
        if ($orders->isEmpty()) {

            $this->manufacrures()->detach();
            $this->products()->detach();
            $this->is_deleted = true;
            $this->name .= '(видален)';
            $this->save();

            return true;
        }

        return false;
    }
}
