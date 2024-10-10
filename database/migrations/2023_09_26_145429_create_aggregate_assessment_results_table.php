<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAggregateAssessmentResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aggregate_assessment_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users');
            $table->double('first_competence')->nullable();
            $table->double('second_competence')->nullable();
            $table->double('third_competence')->nullable();
            $table->double('fourth_competence')->nullable();
            $table->double('fifth_competence')->nullable();
            $table->double('sixth_competence')->nullable();
            $table->string('dependency_identifier');
            $table->foreign('dependency_identifier')->references('identifier')->on('dependencies');
            $table->string('role');
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
        Schema::dropIfExists('aggregate_assessment_results');
    }
}
