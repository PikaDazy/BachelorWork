<?php

namespace Database\Seeders;

use App\Models\Material;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Material::count() > 0) {
            return;
        }

        Material::insert([
           [
               'name' => 'Картон',
               'description' => 'empty description',
               'url' =>null,
           ],
           [
               'name' => 'Поліетилен',
               'description' => 'empty description',
               'url' =>null,
           ],
           [
               'name' => 'Алюмінієва фольга',
               'description' => 'empty description',
               'url' =>null,
           ],
           [
               'name' => 'Скло',
               'description' => 'empty description',
               'url' =>null,
           ],
           [
               'name' => 'Алюміній',
               'description' => 'empty description',
               'url' =>null,
           ],

        ]);
    }
}
