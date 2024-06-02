<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use PhpParser\Builder;

class Order extends Model
{
    use HasFactory;

    const ORDER_OVERDUE = 1;
    const ORDER_CLOSED = 2;
    const ORDER_NO_WARNINGS = 3;

    protected $fillable = [
        'due_date', 'client_id', 'manufacture_id', 'status', 'total'
    ];


    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class)
            ->as('orderItems')->withPivot('count', 'price', 'total');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->as('orderItems')->withPivot('count', 'price', 'total');
    }

    public function mostOrderedProducts(int $limit = 5)
    {
        return DB::table("orders")
            ->join("order_product", function($join){
                $join->on("orders.id", "=", "order_product.order_id");
            })
            ->join("products", function($join){
                $join->on("order_product.product_id", "=", "products.id");
            })
            ->select(
                "order_product.product_id",
                "products.id",
                "products.name",
                "products.url",
                "products.price",
                DB::raw('sum(`order_product`.`count`) as product_count'),
                DB::raw('sum(`order_product`.`total`) as product_price')
            )
            ->orderBy("product_count","desc")
            ->groupBy("order_product.product_id")
            ->limit($limit)
            ->get();
    }

    public function statusCount(): Collection
    {
        return $this
            ->select(
                'status',
                DB::raw('count(`status`) as status_count')
            )
            ->groupBy('status')
            ->get();
    }

    public function manufacture(): BelongsTo
    {
        return $this->belongsTo(Manufacture::class);
    }

    public function finalizeProducts()
    {
        $products = $this->products;
        $itemsLocation = [];
        foreach ($products as $product) {
            $storages = Storage::whereHas('products', function ($query) use ($product) {
                return $query
                    ->where('id', $product->id);
            })->get();

            $requested = $product->orderItems->count;
            foreach ($storages as $storage) {
                $storageProduct = $storage->products()->where('id', $product->id)->first();
                $itemsLocation[] = [
                    'storage_id' => $storage->id,
                    'product_id' => $product->id,
                    'count' => $storageProduct->pivot->storage_quantity,
                    'requested' => $requested,
                ];

                if ($requested <= $storageProduct->pivot->storage_quantity) {
                    $requested = 0;
                    break;
                } else {
                    $requested -= $storage->quantity;
                }
            }

            if ($requested > 0) {
                return 'Недостатньо продуктів на скаладі. Не можу закрити замовлення';
            }
        }

        foreach ($itemsLocation as $item) {
            if ($item['requested'] < $item['count']) {
                $count = $item['count'] - $item['requested'];
                Storage::where('id', $item['storage_id'])->first()
                    ->products()->updateExistingPivot($item['product_id'], ['storage_quantity' => $count]);
            }
        }

        $this->is_finalized = 1;
        $this->save();

        return '';
    }

    public function finalizeMaterials(): string
    {
        $materials = $this->materials;
        $materialsCount = $materials->sum('orderItems.count');

        $storage = Storage::where(DB::raw('`capacity` - `load`'), '>', $materialsCount)->first();
        $itemsLocation = [];
        if ($storage) {
            foreach ($materials as $material) {
                $existingMaterial = $storage->materials()->where('id', $material->id)->first();
                $storageQuantity = $existingMaterial
                    ? $existingMaterial->pivot->storage_quantity + $material->orderItems->count
                    : $material->orderItems->count;

                $itemsLocation[$material->id] = ['storage_quantity' => $storageQuantity];
            }

            $storage->materials()->syncWithoutDetaching($itemsLocation);
        } else {
            foreach ($materials as $material) {
                $storage = Storage::where(DB::raw('`capacity` - `load`'), '>', $material->orderItems->count)->first();
                if (!$storage) {
                    return 'Недостатньо вільного місця на складах. Не можу закрити замовлення';
                }

                $existingMaterial = $storage->materials()->where('id', $material->id)->first();
                $storageQuantity = $existingMaterial->pivot->storage_quantity
                    ? $existingMaterial->pivot->storage_quantity + $material->orderItems->count
                    : $material->orderItems->count;

                $storage->materials()->syncWithoutDetaching(
                    [$material->id => ['storage_quantity' => $storageQuantity]]
                );
            }
        }

        $this->is_finalized = 1;
        $this->save();

        return '';
    }

    public function client(): BelongsTo
    {
        return $this->BelongsTo(Client::class);
    }

    public function updateOrderTotal(): void
    {
        $orderItems = $this->getOrderItems();

        $total = 0;
        foreach ($orderItems as $orderItem) {
            $total += $orderItem->orderItems->total;
        }

        $this->total = $total;
        $this->save();
    }

    public function getOrderItems()
    {
        if ($this->client_id !== 1) {
            $this->load('products');
            return $this->products;
        } else {
            $this->load('materials');
            return $this->materials;
        }
    }

    public static function warningFilter($warningType)
    {
        return match ($warningType) {
            self::ORDER_OVERDUE => self::query()
                ->where('due_date', '<=', DB::raw('CAST(CURRENT_TIMESTAMP AS DATETIME)'))
                ->where('is_finalized', false),
            self::ORDER_CLOSED => self::query()->where('is_finalized', true),
            self::ORDER_NO_WARNINGS => self::query()->where('due_date', '>', DB::raw('CAST(CURRENT_TIMESTAMP AS DATETIME)')),
            default => self::query(),
        };
    }
}
