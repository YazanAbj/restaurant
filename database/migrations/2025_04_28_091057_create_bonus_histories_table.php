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
        Schema::create('bonus_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade'); // Linking to staff table
            $table->decimal('bonus_amount', 10, 2);  // Store positive or negative bonus
            $table->text('reason')->nullable(); // Reason for the bonus
            $table->timestamps(); // Created and Updated timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bonus_histories');
    }
};
