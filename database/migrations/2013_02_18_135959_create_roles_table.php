<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->smallInteger('customId');
            $table->decimal('assessment_weight',4,2)->nullable()->default(00.00);
            $table->timestamps();
        });


        //Create the default roles (admin and functionary)
        \Illuminate\Support\Facades\DB::table('roles')->insert(
            [
                ['name' => 'administrador', 'customId' => 10],
                ['name' => 'funcionario', 'customId' => 3]
            ]
        );

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('roles');
    }
}
