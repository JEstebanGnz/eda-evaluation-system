<?php

namespace App\Http\Controllers;

use App\Models\AssessmentPeriod;
use App\Models\FormAnswer;
use App\Models\FormAnswers;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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


    public function getAssessmentPDF(Request $request){

        $assessmentPeriodId = $request->input('assessmentPeriodId');
        if($assessmentPeriodId == null){
            $assessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;
        }
        $assessmentPeriodName = AssessmentPeriod::select(['name'])->where('id', '=',  $assessmentPeriodId)->first()->name;
        $labels = DB::table('competences')->where('assessment_period_id', '=', $assessmentPeriodId)
            ->orderBy('position', 'ASC')->get();


        //Check the user role and based on that generate the report
        $user = auth()->user();
        $role = $user->role()['name'];

        if($role === "administrador"){
            $functionaryUserId = $request->input('functionaryUserId');
            $functionaryName = $request->input('functionaryName');
            $graph = $request->input('graph');
            $grades = json_decode($request->input('grades'));
            $openAnswers = FormAnswer::getFunctionaryOpenAnswers($functionaryUserId, $assessmentPeriodId);

            return view('assessmentReport', compact( 'assessmentPeriodName','labels','functionaryName','grades','graph', 'openAnswers'));
        }

        $userId = $user['id'];
        $functionaryName = $user['name'];

        $labels = array_unique(array_column($labels->toArray(), 'name'));

        $datasets = [];

        //Response Ideal dataset
        $responseIdealGrade = DB::table('position_user as pu')->select(['ri.response', 'p.name'])->where('pu.user_id','=',$user['id'])
            ->join('response_ideals as ri','ri.position_id','=','pu.position_id')
            ->join('positions as p','p.id','=','pu.position_id')
            ->where('ri.assessment_period_id','=',$assessmentPeriodId)->first();

        $mapResponseIdealArray = array_map(function ($responseIdeal){
            return $responseIdeal->value;
        }, json_decode($responseIdealGrade->response));

        $datasets [] = (object)
        [       'label' => "Nivel Esperado ($responseIdealGrade->name)",
                'data' => $mapResponseIdealArray,
                'backgroundColor' => 'orange',
                'borderColor' => 'orange',
                'borderWidth'=> 2,
                'borderDash'=> [5, 5],
        ];

        //AggregateGrade dataset
        $aggregateGrade = DB::table('aggregate_assessment_results as ar')->select(['ar.role' ,'ar.first_competence as c1','ar.second_competence as c2',
            'ar.third_competence as c3','ar.fourth_competence as c4', 'ar.fifth_competence as c5','ar.sixth_competence as c6'])
            ->where('ar.assessment_period_id', '=', $assessmentPeriodId)
            ->where('ar.user_id','=',$userId)->first();


        for ($i = 1; $i < 7; $i++){
            $competence = "c${i}";
            $mapAggregateGradeArray [] = $aggregateGrade->$competence;
        }

//        dd($aggregateGrade);

        $datasets [] = (object)
        [    'label' => ucfirst($aggregateGrade->role),
            'data' => $mapAggregateGradeArray,
            'backgroundColor' => 'black',
            'borderColor' => 'black',
            'borderWidth'=> 7,

        ];

        $openAnswers = FormAnswer::getFunctionaryOpenAnswers($userId, $assessmentPeriodId);

        return view('assessmentReport2', compact( 'assessmentPeriodName','labels','functionaryName', 'datasets', 'openAnswers', 'role'));
    }


    public function getAssessmentPDFDraft(Request $request){

        $assessmentPeriodId = $request->input('assessmentPeriodId');
        if($assessmentPeriodId == null){
            $assessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;
        }
        $assessmentPeriodName = AssessmentPeriod::select(['name'])->where('id', '=',  $assessmentPeriodId)->first()->name;

        //Check the user role and based on that generate the report
        $user = auth()->user();

        if($user->role()['name'] === "administrador"){
            $labels = json_decode($request->input('labels'));
            $functionaryUserId = $request->input('functionaryUserId');
            $functionaryName = $request->input('functionaryName');
            $graph = $request->input('graph');
            $grades = json_decode($request->input('grades'));
            $openAnswers = FormAnswer::getFunctionaryOpenAnswers($functionaryUserId, $assessmentPeriodId);
            return view('assessmentReport', compact( 'assessmentPeriodName','labels','functionaryName','grades','graph', 'openAnswers'));
        }

        $userId = $user['id'];
        $functionaryName = $user['name'];
        $labels = DB::table('competences')->where('assessment_period_id', '=', $assessmentPeriodId)
            ->orderBy('position', 'ASC')->get();
        $grades = DB::table('aggregate_assessment_results')->select(['role' ,'first_competence', 'second_competence','third_competence',
            'fourth_competence' , 'fifth_competence', 'sixth_competence'])->where('assessment_period_id', '=', $assessmentPeriodId)
            ->where('user_id','=',$userId)->get();

//        dd($grades);


        $openAnswers = FormAnswer::getFunctionaryOpenAnswers($userId, $assessmentPeriodId);


        return view('assessmentReport2', compact( 'assessmentPeriodName','labels','functionaryName','grades', 'openAnswers'));
    }

    public function getCommitmentsStatusPDF(Request $request){

        $activeAssessmentPeriod = AssessmentPeriod::getActiveAssessmentPeriod();
        $assessmentPeriodName = $activeAssessmentPeriod->name;
        $user = auth()->user();
        $functionaryName = $user->name;

        $commitments = DB::table('commitments as c')->select(['t.name as Tipo de compromiso', 'c.due_date as Fecha máxima'])
            ->where('assessment_period_id', '=', $activeAssessmentPeriod->id)
            ->where('user_id','=', $user->id)->orderBy('due_date', 'ASC')
            ->where('c.done', '=', '0')
            ->join('users as u', 'c.user_id', '=', 'u.id')
            ->join('trainings as t', 'c.training_id', '=','t.id')->get();

        return view('commitmentReport', compact( 'assessmentPeriodName','functionaryName', 'commitments'));
    }


    public function hasAssessmentReportAvailable(){

        $activeAssessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;
        $user = auth()->user();

        return DB::table('aggregate_assessment_results')->where('assessment_period_id', '=', $activeAssessmentPeriodId)
            ->where('user_id','=',$user['id'])->first();
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
