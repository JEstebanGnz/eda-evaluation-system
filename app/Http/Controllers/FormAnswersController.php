<?php

namespace App\Http\Controllers;

use App\Models\AssessmentPeriod;
use App\Models\FormAnswer;
use App\Models\FunctionaryProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormAnswersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Support\Collection
     */
    public function index(Request $request)
    {
        $assessmentPeriodId = $request->input('assessmentPeriodId');

        if($assessmentPeriodId === null){
            $assessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;
        }

        return DB::table('form_answers as fa')
            ->select(['fa.id', 'fa.submitted_at', 'u.name', 'a.role', 'a.dependency_identifier', 'u.id as user_id', 'd.name as dependency_name', 'p.name as position_name',
                'fa.first_competence_average as c1','fa.second_competence_average as c2','fa.third_competence_average as c3','fa.fourth_competence_average as c4',
                'fa.fifth_competence_average as c5','fa.sixth_competence_average as c6'])
            ->join('forms as f', 'fa.form_id', '=', 'f.id')
            ->join('users as u', 'fa.evaluated_id', '=', 'u.id')
            ->join('assessments as a','fa.id','=','a.form_answer_id')
            ->join('dependencies as d', 'a.dependency_identifier','=','d.identifier')
            ->join('position_user as pu','u.id','=','pu.user_id')
            ->join('positions as p','p.id','=','pu.position_id')
            ->where('f.creation_assessment_period_id', '=', $assessmentPeriodId)
            ->where('pu.assessment_period_id','=',$assessmentPeriodId)
            ->get();


//        'fa.first_competence_average','fa.second_competence_average','fa.third_competence_average','fa.fourth_competence_average',
//                'fa.fifth_competence_average','fa.sixth_competence_average'

    }


    public function indexAggregateGrades(Request $request)
    {
        $assessmentPeriodId = $request->input('assessmentPeriodId');
        if($assessmentPeriodId === null){
            $assessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;
        }
        return DB::table('aggregate_assessment_results as ar')
            ->select(['ar.updated_at as submitted_at', 'ar.role', 'ar.dependency_identifier', 'u.id as user_id','u.name' , 'd.name as dependency_name', 'p.name as position_name',
                'ar.first_competence as c1','ar.second_competence as c2','ar.third_competence as c3','ar.fourth_competence as c4', 'ar.fifth_competence as c5',
                'ar.sixth_competence as c6'])
            ->join('users as u', 'ar.user_id', '=', 'u.id')
            ->join('dependencies as d', 'ar.dependency_identifier','=','d.identifier')
            ->join('position_user as pu','u.id','=','pu.user_id')
            ->join('positions as p','p.id','=','pu.position_id')
            ->where('ar.assessment_period_id', '=', $assessmentPeriodId)->get();
    }

    public function getOpenAnswers (Request $request)
    {
        $assessmentPeriodId = $request->input('assessmentPeriodId');
        $functionaryUserId = $request->input('functionaryUserId');
        return response()->json(FormAnswer::getFunctionaryOpenAnswers($functionaryUserId, $assessmentPeriodId));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
