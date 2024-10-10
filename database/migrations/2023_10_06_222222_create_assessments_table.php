<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssessmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluated_id');
            $table->foreign('evaluated_id')->references('id')->on('users');
            $table->foreignId('evaluator_id');
            $table->foreign('evaluator_id')->references('id')->on('users');
            $table->enum('role',['jefe','par','cliente interno', 'cliente externo', 'autoevaluación']);
            $table->boolean('pending');
            $table->foreignId('assessment_period_id')->constrained();
            $table->string('dependency_identifier');
            $table->foreign('dependency_identifier')->references('identifier')->on('dependencies');
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
        Schema::dropIfExists('assessments');
    }
}
