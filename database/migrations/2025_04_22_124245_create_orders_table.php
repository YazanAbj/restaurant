<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->string('customer_name');
        $table->foreignId('bill_id')->nullable()->constrained()->onDelete('cascade');
        $table->foreignId('table_id')->constrained()->onDelete('cascade');
        $table->decimal('total_price', 8, 2)->default(0);
        $table->enum('status', ['pending', 'served', 'cancelled'])->default('pending');
        $table->timestamp('ordered_at')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
