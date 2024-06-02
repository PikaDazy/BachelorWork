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
        Schema::create('storages', function (Blueprint $table) {
            $table->id();
            $table->text('address');
            $table->float('height');
            $table->float('square');
            $table->integer('load')->nullable()->default(0);
            $table->integer('capacity')->default(0);
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });

        Schema::create('material_storage', function (Blueprint $table) {
            $table->foreignId('storage_id')->constrained('storages');
            $table->foreignId('material_id')->constrained('materials');
            $table->integer('storage_quantity')->default(0);
        });

        Schema::create('product_storage', function (Blueprint $table) {
            $table->foreignId('storage_id')->constrained('storages');
            $table->foreignId('product_id')->constrained('products');
            $table->integer('storage_quantity')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_storage');
        Schema::dropIfExists('product_storage');
        Schema::dropIfExists('storages');
    }
};
