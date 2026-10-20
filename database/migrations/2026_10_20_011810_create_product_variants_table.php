<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('size_id')->constrained()->restrictOnDelete();
            $table->string('sku')->unique();
            $table->string('color')->default('Original');
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('min_stock')->default(2);
            $table->timestamps();

            $table->unique(['product_id', 'size_id', 'color']);
        });

        DB::statement('ALTER TABLE product_variants ADD CONSTRAINT product_variants_stock_check CHECK (stock >= 0)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_price_check CHECK (base_price >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
