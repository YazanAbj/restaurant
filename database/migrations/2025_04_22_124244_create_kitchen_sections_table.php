<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKitchenSectionsTable extends Migration
{
    public function up()
    {
        Schema::create('kitchen_sections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('categories');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kitchen_sections');
    }
}
