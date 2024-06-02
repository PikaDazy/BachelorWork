<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->dateTime('due_date');
            $table->foreignId('client_id')->nullable()->constrained('clients');
            $table->foreignId('manufacture_id')->nullable()->constrained('manufactures');
            $table->enum(
                'status',
                [
                    'placed',
                    'paid',
                    'produced',
                    'shipped',
                    'delivered'
                ]
            )->default('placed');
            $table->float('total');
            $table->float('is_finalized');
            $table->timestamps();
        });

        Schema::create('material_order', function (Blueprint $table) {
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('material_id')->constrained('materials');
            $table->integer('count');
            $table->float('price');
            $table->float('total');
            $table->foreignId('storage_id')->nullable()->constrained('storages');
        });

        Schema::create('order_product', function (Blueprint $table) {
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('product_id')->constrained('products');
            $table->integer('count');
            $table->float('price');
            $table->float('total');
            $table->foreignId('storage_id')->nullable()->constrained('storages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_order');
        Schema::dropIfExists('order_product');
        Schema::dropIfExists('orders');
    }
};
