<?php

namespace App\Http\Controllers;

use App\Models\AssessmentPeriod;
use App\Models\ResponseIdeal;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Exception\JsonException;

class ResponseIdealController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $activeAssessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;
        $responseIdeals = DB::table('response_ideals as ri')->select(['ri.id', 'ri.position_id','ri.response', 'ri.assessment_period_id', 'p.name'])->where('ri.assessment_period_id', '=', $activeAssessmentPeriodId)
            ->join('positions as p', 'ri.position_id', '=', 'p.id')->get();
        foreach ($responseIdeals as $responseIdeal){
            $responseIdeal->response = json_decode($responseIdeal->response);
        }
        return response()->json($responseIdeals);
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
        try {
            ResponseIdeal::createResponseIdeal($request->all());
        } catch (JsonException $e){
            return response()->json(['message' => 'Ha ocurrido un error guardando la información']);
        }

        return response()->json(['message' => 'Ideal de respuesta creado exitosamente']);
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
    public function update(Request $request, ResponseIdeal $responseIdeal)
    {
        $responseIdeal->update($request->all());
        return response()->json(['message' => 'Ideal de respuesta actualizado exitosamente']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(ResponseIdeal $responseIdeal)
    {
        try {
            $responseIdeal->delete();
        } catch (QueryException $e) {
                return response()->json(['message' => 'No puedes eliminar el ideal de respuesta, error.'], 400);
        }
        return response()->json(['message' => 'Ideal de respuesta eliminado exitosamente']);
    }
}
