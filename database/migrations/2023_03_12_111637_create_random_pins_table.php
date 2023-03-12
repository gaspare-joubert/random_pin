<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRandomPinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('random_pins')) {
            Schema::create('random_pins', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid');
                $table->string('pin', 56)->nullable(false);
                $table->string('permitted_characters', 36)->nullable(false);
                $table->boolean('has_been_emitted')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('random_pins');
    }
}
