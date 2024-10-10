<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Test extends Model
{
    protected $table = 'forms';
    use HasFactory;

    public static function getUserTests()
    {
        $userAssessments = Assessment::getUserAssessments();

       if (!$userAssessments){
           return [];
       }

        foreach ($userAssessments as $assessment) {
            $assessment->test = self::getTestFromAssessment($assessment);
        }
        return $userAssessments;
    }

    public static function getTestFromAssessment($assessment)
    {
        $activeAssessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;

        $evaluatedPosition = DB::table('functionary_profiles as fp')->select(['p.name'])->where('fp.user_id', '=', $assessment->evaluated_id)
            ->where('fp.assessment_period_id', '=', $activeAssessmentPeriodId)
            ->join('job_title_positions as jtp', 'fp.job_title', '=', 'jtp.job_title')
            ->where('jtp.assessment_period_id', '=', $activeAssessmentPeriodId)
            ->join('positions as p', 'jtp.position_id', '=', 'p.id')->first();

        if(!$evaluatedPosition){
            $evaluatedPosition = null;
        }

        else{
            $evaluatedPosition = $evaluatedPosition->name;
        }

        //All params
        $form = DB::table('forms')
            ->where('position', '=', $evaluatedPosition)
            ->where('dependency_role', '=', $assessment->role)
            ->where('assessment_period_id', '=', $activeAssessmentPeriodId)
            ->latest()->first();

//        if($evaluatedPosition !== "Asistente"){
//            dd($assessment, $evaluatedPosition, $form);
//        }

        if ($form !== null) {
            return $form;
        }
        //Only first param
        $form = DB::table('forms')
            ->where('position', '=', null)
            ->where('dependency_role', '=', $assessment->role)
            ->where('assessment_period_id', '=', $activeAssessmentPeriodId)
            ->latest()->first();

        if ($form !== null) {
            return $form;
        }

        //Any params
        $form = DB::table('forms')
            ->where('position', '=', null)
            ->where('dependency_role', '=', null)
            ->where('assessment_period_id', '=', $activeAssessmentPeriodId)
            ->latest()->first();


        if ($form !== null) {
            return $form;
        }

        $form = DB::table('forms')
            ->where('position', '=', null)
            ->where('dependency_role', '=', null)
            ->where('assessment_period_id', '=', null)
            ->latest()->first();

        return $form ?? null;
    }

}
