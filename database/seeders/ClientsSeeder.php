<?php

namespace Database\Seeders;

use App\Models\Client;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClientsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Client::count() > 0) {
            return;
        }

        Client::insert([
            'name' => 'Me',
            'phone' => fake()->numerify('##########'),
            'email' => fake()->unique()->email(),
            'address' => fake()->address(),
        ]);
    }
}
