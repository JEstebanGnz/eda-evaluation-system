<?php

namespace App\Http\Controllers;

use App\Models\AssessmentPeriod;
use App\Models\Competence;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompetenceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $activeAssessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;
        return response()->json(Competence::where('assessment_period_id','=',$activeAssessmentPeriodId)->orderBy('position')->get());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        Competence::createCompetence($request->all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Competence::createCompetence($request->all());
        $competence = Competence::whereName($request->input('name'))->first();
        return response()->json(['message' => 'Competencia creada exitosamente']);
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
    public function update(Request $request, Competence $competence)
    {
        $competence->update($request->all());
        return response()->json(['message' => 'Competencia actualizada exitosamente']);
    }


    public function updateOrder(Request $request)
    {
        $data = $request->input('data');
        return Competence::reOrderCompetencesOnPositionChange($data['position'],$data['oldPosition'], $data['newPosition']);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Competence $competence)
    {
        try {
            $deletedCompetencePosition = $competence['position'];
            $competence->delete();
            Competence::reOrderCompetencesOnDelete($deletedCompetencePosition);

        } catch (QueryException $e) {
            if ($e->getCode() === "23000") {
                return response()->json(['message' => 'No se pudo eliminar la competencia seleccionada'], 400);
            }
        }
        return response()->json(['message' => 'Competencia eliminada exitosamente']);
    }
}
