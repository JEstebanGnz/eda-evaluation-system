<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

//
//$table->string('email');
//$table->foreignId('assessment_period_id')->references('id')->on('assessment_periods');
//$table->json('email_parameters');
//$table->enum('status', ['Not Started', 'In Progress', 'Done']);
//$table->enum('before_start_or_finish_assessment', ['Start', 'Finish']);
//
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('positions');
    }
}
