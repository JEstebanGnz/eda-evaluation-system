<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFunctionariesDataChangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('functionaries_data_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->json('payload');
            $table->foreignId('assessment_period_id')->references('id')->on('assessment_periods');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('functionaries_data_changes');
    }
}
