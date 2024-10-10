<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('dependency_role', ['jefe', 'par', 'autoevaluación', 'cliente interno', 'cliente externo'])->nullable();
            $table->string('position')->nullable();
            $table->json('questions')->nullable();
            $table->foreignId('assessment_period_id')->nullable()->constrained();
            $table->foreignId('creation_assessment_period_id')->references('id')->on('assessment_periods');
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
        Schema::dropIfExists('forms');
    }
}
