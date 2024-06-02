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
        Schema::create('manufactures', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });

        Schema::create('manufacture_material', function (Blueprint $table){
            $table->foreignId('manufacture_id')->constrained('manufactures');
            $table->foreignId('material_id')->constrained('materials');
            $table->float('price')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manufacture_material');
        Schema::dropIfExists('manufactures');
    }
};
