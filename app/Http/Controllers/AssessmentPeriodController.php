<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyAssessmentPeriodRequest;
use App\Http\Requests\SetActiveAssessmentPeriodRequest;
use App\Http\Requests\StoreAssessmentPeriodRequest;
use App\Http\Requests\UpdateAssessmentPeriodRequest;
use App\Models\AssessmentPeriod;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssessmentPeriodController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(AssessmentPeriod::all());
    }

    public function migrateActiveAssessmentPeriodInfo(AssessmentPeriod $assessmentPeriod): JsonResponse
    {
        //Get the activeAssessmentPeriod
        $active = AssessmentPeriod::getActiveAssessmentPeriod();
        $destinationAssessmentPeriod = $assessmentPeriod;
        try{
            $done = AssessmentPeriod::migrateActiveAssessmentPeriodInformation($active, $destinationAssessmentPeriod);
            if ($done){
                return response()->json(['message' => 'Información migrada de manera exitosa']);
            }
            return response()->json(['message' => 'Ya existe información asociada al periodo al que intentas migrar, no puedes proceder con la migración'], 500);
        } catch (\Exception $exception) {
            return response()->json(['message' => 'Ocurrió un error durante el proceso de migrar la información, por favor contacta con el administrador'], 400);
        }
    }

    public function setActive(SetActiveAssessmentPeriodRequest $request, AssessmentPeriod $assessmentPeriod): JsonResponse
    {
        //Detect previous assessment period
        try {
            $active = AssessmentPeriod::getActiveAssessmentPeriod();
            $active->active = false;
            $active->timestamps = false;
            $active->save();
        } catch (\Exception $e) {
        } finally {
            $assessmentPeriod->active = true;
            $assessmentPeriod->save();
        }
        return response()->json(['message' => 'Se ha seleccionado el periodo de evaluación como el nuevo periodo de evaluación activo']);
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
    public function store(StoreAssessmentPeriodRequest $request): JsonResponse
    {
        AssessmentPeriod::create($request->all());
        $assessmentPeriod = AssessmentPeriod::whereName($request->input('name'))->first();
        return response()->json(['message' => 'Periodo de evaluación creado exitosamente']);
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
    public function update(UpdateAssessmentPeriodRequest $request, AssessmentPeriod $assessmentPeriod): JsonResponse
    {
        $assessmentPeriod->update($request->all());
        return response()->json(['message' => 'Periodo de evaluación actualizado exitosamente']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(DestroyAssessmentPeriodRequest $request, AssessmentPeriod $assessmentPeriod): JsonResponse
    {
        if ($assessmentPeriod->active === 1) {
            return response()->json(['message' => 'No se puede eliminar un periodo de evaluación activo'], 400);
        }
        try {
            $assessmentPeriod->delete();
        } catch (QueryException $e) {
            if ($e->getCode() === "23000") {
                return response()->json(['message' => 'No puedes eliminar un periodo de evaluación si este tiene compromisos creados y asociados'], 400);
            }
        }
        return response()->json(['message' => 'Periodo de evaluación eliminado exitosamente']);

    }
}
