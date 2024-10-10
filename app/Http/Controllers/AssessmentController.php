<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentPeriod;
use App\Models\FunctionaryProfile;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Psy\Util\Json;

class AssessmentController extends Controller
{

    public $assessment_period_id;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): JsonResponse
    {
        $activeAssessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;
        $functionary = $request->input('functionary');

        if ($functionary !== null){
            $assessments= Assessment::getUserAssessments($functionary);
        }
        else {
            $assessments = Assessment::all();
        }
        return response()->json($assessments);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Assessment $assessment)
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $activeAssessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;
        //Check that evaluator selected isn't an already an evaluator for the same functionary
        $assessmentAlreadyExists = DB::table('assessments')->where('evaluated_id','=',$request->input('evaluated_id'))
            ->where('evaluator_id','=', $request->input('evaluator_id'))
            ->where('assessment_period_id','=', $activeAssessmentPeriodId)->first();

        if($assessmentAlreadyExists){
            return response()->json(['message' => 'No puedes asignar a una persona más de una vez al mismo funcionario'], 400);
        }

        try{
            Assessment::createAssessment($request);
        }catch (QueryException $e) {
            return response()->json(['message' => 'No se pudo crear la asignación.'], 400);
        }
        return response()->json(['message' => 'Asignación creada correctamente']);
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
    public function update(Request $request, Assessment $assessment)
    {

        $activeAssessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;
        //Check that evaluator selected isn't an already an evaluator for the same functionary
        $assessmentAlreadyExists = DB::table('assessments')->where('evaluated_id','=',$request->input('evaluated_id'))
            ->where('evaluator_id','=', $request->input('evaluator_id'))
            ->where('assessment_period_id','=', $activeAssessmentPeriodId)->first();

        if($assessmentAlreadyExists){
            return response()->json(['message' => 'No puedes asignar a una persona más de una vez al mismo funcionario'], 400);
        }

        if($assessment['pending'] === 0){
            return response()->json(['message' => 'No puedes modificar la asignación si esta ya se realizó'], 400);
        }

        $assessment->update($request->all());
        return response()->json(['message' => 'Asignación actualizada exitosamente']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Assessment $assessment)
    {
        try {
            if($assessment['pending'] === 0){
                return response()->json(['message' => 'No puedes eliminar la asignación si esta ya se realizó'], 400);
            }
            $assessment->delete();
        } catch (QueryException $e) {
            return response()->json(['message' => 'No se ha podido eliminar la asignación...'], 400);
        }
        return response()->json(['message' => 'Asignación eliminada exitosamente']);
    }
}
