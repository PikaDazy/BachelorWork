<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\Roles;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (User::where('role', Roles::admin->name)->count() > 0) {
            return;
        }

        User::insert([
            [
                'name' => 'admin',
                'role' => Roles::admin->name,
                'bio' => fake()->paragraph(),
                'email' => 'admin@email.com',
                'email_verified_at' => now(),
                'password' => Hash::make('admin'),
                'remember_token' => Str::random(10),
            ],
            [
                'name' => 'notadmin',
                'role' => Roles::user->name,
                'bio' => fake()->paragraph(),
                'email' => 'notadmin@email.com',
                'email_verified_at' => now(),
                'password' => Hash::make('notadmin'),
                'remember_token' => Str::random(10),
            ],

        ]);
    }
}
