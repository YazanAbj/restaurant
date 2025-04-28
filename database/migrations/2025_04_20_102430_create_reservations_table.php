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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // still useful to know which manager created it
            $table->foreignId('table_id')->constrained('tables')->onDelete('cascade');
            $table->date('reservation_date');
            $table->time('reservation_start_time');
            $table->integer('guest_number');
            $table->string('guest_name');
            $table->string('guest_phone')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['confirmed', 'cancelled'])->default('confirmed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
