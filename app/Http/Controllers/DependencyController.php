<?php

namespace App\Http\Controllers;

use App\Helpers\AtlanteProvider;
use App\Http\Requests\DestroyDependencyRequest;
use App\Http\Requests\StoreDependencyRequest;
use App\Models\AssessmentPeriod;
use App\Models\Dependency;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DependencyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): JsonResponse
    {
        $assessmentPeriodId = $request->input('assessmentPeriodId');
        return response()->json(Dependency::getDependencies($assessmentPeriodId));
    }


    public function sync(): JsonResponse
    {
        $data = [];
        try {
            $dependencies = AtlanteProvider::get('units', $data);
            Dependency::createOrUpdateFromArray($dependencies);
        } catch (\JsonException $e) {
            return response()->json(['message' => 'Ha ocurrido un error con la fuente de datos']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Ha ocurrido el siguiente error: ' . $e->getMessage()], 500);
        }
        return response()->json(['message' => 'Las dependencias se han sincronizado exitosamente']);
    }


    public function getAdmins(Dependency $dependency): JsonResponse
    {
        return response()->json(Dependency::getDependencyAdmins($dependency));
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
    public function store(StoreDependencyRequest $request): JsonResponse
    {
        $assessmentPeriodAsString = (string)AssessmentPeriod::getActiveAssessmentPeriod()->id;
        $code = 'custom-' . $request->input('name');
        Dependency::create([
            'name' => $request->input('name'),
            'code' => $code,
            'is_custom' => 1,
            'identifier' => $code . '-' . $assessmentPeriodAsString,
            'assessment_period_id' => AssessmentPeriod::getActiveAssessmentPeriod()->id
        ]);

        return response()->json(['message' => 'Dependencia creada exitosamente']);
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
    public function edit(Dependency $dependency)
    {
        return Inertia::render('Dependencies/ManageDependency', ['dependency' => $dependency]);
    }

    public function assessmentStatus(Dependency $dependency)
    {
        return Inertia::render('Dependencies/AssessmentStatus', ['dependency' => $dependency]);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateDependencyRequest $request, Dependency $dependency)
    {
        $dependency->update($request->all());
        return response()->json(['message' => 'Dependencia actualizada correctamente']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(DestroyDependencyRequest $request, Dependency $dependency)
    {
        if($dependency->users->count()>0){
            return response()->json(['message' => 'No puedes eliminar una dependencia con usuarios adentro'], 400);
        }

        if ($dependency->is_custom === 1) {
            $dependency->delete();
            return response()->json(['message' => 'Dependencia eliminada exitosamente']);
        }
        return response()->json(['message' => 'No se ha podido eliminar, la dependencia no es personalizada'], 400);
    }
}
