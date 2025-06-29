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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('category');
            $table->integer('quantity');
            $table->string('unit');
            $table->decimal('price_per_unit', 8, 2);
            $table->string('supplier_name');
            $table->date('received_date');
            $table->date('expiry_date')->nullable();
            $table->boolean('low_stock')->default(false);
            $table->integer('low_stock_threshold')->nullable();
            $table->string('photo')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
