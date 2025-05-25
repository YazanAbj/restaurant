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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('table_number');
            $table->decimal('total', 10, 2)->default(0);
            $table->enum('discount_type', ['percentage', 'fixed'])->nullable(); // Discount type
            $table->decimal('discount_value', 10, 2)->nullable();               // Value of discount
            $table->decimal('discount_amount', 10, 2)->nullable();              // Amount after calculation
            $table->decimal('final_price', 10, 2)->nullable();                  // Total after discount
            $table->enum('status', ['open', 'paid'])->default('open');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
