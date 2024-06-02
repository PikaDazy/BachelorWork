<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\Product;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Product::count() > 0) {
            return;
        }

        Product::insert([
            [
                'name' => 'Тетра',
                'description' => 'empty description',
                'url' => null,
                'price' => 300,
            ],
            [
                'name' => 'Пластикова бутилка',
                'description' => 'empty description',
                'url' => null,
                'price' => 200,
            ],
            [
                'name' => 'Скляна бутилка',
                'description' => 'empty description',
                'url' => null,
                'price' => 100,
            ],
            [
                'name' => 'Алюмінієва банка',
                'description' => 'empty description',
                'url' => null,
                'price' => 30.3,
            ],
        ]);

        $dbProducts = Product::all();

        foreach ($dbProducts as $product) {
            $materials = Material::inRandomOrder()->limit(rand(1, 3))->pluck('id')->toArray();
            $product->materials()->sync($materials);
        }
    }
}
