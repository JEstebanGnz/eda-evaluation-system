<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Context extends Model
{

    private $strategy;

    protected $guarded = [];


    public function setStrategy($param){
        $this->strategy = $param;
    }

    public function execute($param){

        //Here goes the logic for the kind of assessment.
    }



    use HasFactory;
}
