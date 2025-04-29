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
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('age');
            $table->string('phone');
            $table->string('position');
            $table->time('shift_start');
            $table->time('shift_end');
            $table->integer('salary');
            $table->integer('bonus')->default(0);
            $table->integer('current_month_salary');
            $table->boolean('salary_paid')->default(false);
            $table->text('notes')->nullable();
            $table->date('date_joined');
            $table->string('address');
            $table->string('national_id')->unique();
            $table->string('emergency_contact');
            $table->string('photo')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
