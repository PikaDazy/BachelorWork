<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Users will be redirected to this route if not logged in
Volt::route('/login', 'login')->name('login');
Volt::route('/register', 'register');

// Define the logout
Route::get('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/');
});

// Protected routes here
Route::middleware('auth')->group(function () {
    // Users
    Volt::route('/', 'index');
    Volt::route('/users', 'users.index');
    Volt::route('/users/create', 'users.create');
    Volt::route('/users/{user}/edit', 'users.edit');

    // Clients
    Volt::route('/clients', 'clients.list');
    Volt::route('/clients/create', 'clients.create');
    Volt::route('/clients/{client}/edit', 'clients.edit');

    // Materials
    Volt::route('/materials/list', 'materials.list');
    Volt::route('/materials/create', 'materials.create');
    Volt::route('/materials/{material}/edit', 'materials.edit');

    //Products
    Volt::route('/products/list', 'products.list');
    Volt::route('/products/create', 'products.create');
    Volt::route('/products/{product}/edit', 'products.edit');

    //Manufacture
    Volt::route('/manufacture/list', 'manufacture.list');
    Volt::route('/manufacture/create', 'manufacture.create');
    Volt::route('/manufacture/{manufacture}/edit', 'manufacture.edit');

    //Storage
    Volt::route('/storage/list', 'storage.list');
    Volt::route('/storage/create', 'storage.create');
    Volt::route('/storage/{storage}/edit', 'storage.edit');

    //Orders
    Volt::route('/orders/list', 'orders.list');
    Volt::route('/orders/create', 'orders.create');
    Volt::route('/orders/{order}/edit', 'orders.edit');
    Volt::route('/orders/{order}/view', 'orders.view');
    // ... more
});
