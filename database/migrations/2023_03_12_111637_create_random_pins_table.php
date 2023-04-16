<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
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
                $table->string('pin', 56)->nullable(false);
                $table->tinyInteger('type')->nullable(false);
                $table->boolean('has_been_emitted')->default(0);
                $table->timestamps();
                $table->softDeletes();
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
};
