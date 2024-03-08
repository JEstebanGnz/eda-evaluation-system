<?php

namespace App\Http\Controllers;

use App\Models\AssessmentPeriod;
use App\Models\FormAnswer;
use App\Models\FormAnswers;
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


        if( $role === "administrador"){
            $functionaryUserId = $request->input('functionaryUserId');
            $functionaryName = $request->input('functionaryName');
            $graph = $request->input('graph');
            $grades = json_decode($request->input('grades'));
            $openAnswers = FormAnswer::getFunctionaryOpenAnswers($functionaryUserId, $assessmentPeriodId);
            return view('assessmentReport', compact( 'assessmentPeriodName','labels','functionaryName','grades','graph', 'openAnswers'));
        }


        //TODO Traer el dataset del ideal de respuesta y junto con el del promedio final, ponerlos en la gráfica que se renderiza en blade
        //TODO NO OLVIDAAAAAAAAAAR
        //TODO NO OLVIDAAAAAAAAAAR
        //TODO NO OLVIDAAAAAAAAAAR
        //TODO NO OLVIDAAAAAAAAAAR
        //TODO NO OLVIDAAAAAAAAAAR
        //TODO NO OLVIDAAAAAAAAAAR
        //TODO NO OLVIDAAAAAAAAAAR
        //TODO NO OLVIDAAAAAAAAAAR


        $userId = $user['id'];
        $functionaryName = $user['name'];

        $labels = array_unique(array_column($labels->toArray(), 'name'));


        $grades = DB::table('aggregate_assessment_results')->select(['role' ,'first_competence', 'second_competence','third_competence',
            'fourth_competence' , 'fifth_competence', 'sixth_competence'])->where('assessment_period_id', '=', $assessmentPeriodId)
            ->where('user_id','=',$userId)->get();

        $openAnswers = FormAnswer::getFunctionaryOpenAnswers($userId, $assessmentPeriodId);

        return view('assessmentReport2', compact( 'assessmentPeriodName','labels','functionaryName','grades', 'openAnswers', 'role'));
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
