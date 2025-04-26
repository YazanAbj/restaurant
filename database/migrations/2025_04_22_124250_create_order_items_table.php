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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('table_number');
            $table->foreignId('menu_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('kitchen_section_id')->nullable()->constrained('kitchen_sections')->onDelete('set null');
            $table->integer('quantity');
            $table->decimal('price', 8, 2)->default(0);
            $table->text('notes')->nullable();
            $table->enum('status', ['preparing', 'finished', 'canceled'])->default('preparing');
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
