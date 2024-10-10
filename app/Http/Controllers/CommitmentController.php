<?php

namespace App\Http\Controllers;

use App\Models\AssessmentPeriod;
use App\Models\Commitment;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CommitmentController extends Controller
{

    public function landing()
    {
        $userRole = auth()->user()->role();
        return Inertia::render('Commitments/Index', ['role' => $userRole]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Support\Collection
     */
    public function indexCommitments(Role $role)
    {
        $user = auth()->user();
        $activeAssessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;

        if($role["name"] === "funcionario") {

            //            if(count($commitments) === 0){
//                return DB::table('commitments as c')->select(['c.id', 'u.name as user_name', 'u.id as user_id', 't.name as training_name', 't.id as training_id','c.due_date','c.done'])
//                    ->where('c.assessment_period_id','=', $activeAssessmentPeriodId)->where('c.user_id','=', $user['id'])
//                    ->join('users as u', 'c.user_id', '=','u.id')
//                    ->join('trainings as t', 'c.training_id', '=','t.id')->get();
//            }
            $now = Carbon::now();
            $date = $now->toDateString();

            $validDate = DB::table('assessment_periods as ap')->where('ap.active','=',1)
                ->where('ap.commitment_start_date','<=',$date)->where('ap.commitment_end_date','>=',$date)->first();

            if($validDate){

                $commitments = DB::table('commitments as c')->select(['c.id', 'u.name as user_name', 'u.id as user_id', 't.name as training_name',
                    't.id as training_id','c.due_date','c.done', 'c.done_date'])
                    ->where('c.user_id', '=', $user['id'])
                    ->where('c.assessment_period_id','=', $activeAssessmentPeriodId)
                    ->join('users as u', 'c.user_id', '=','u.id')
                    ->join('trainings as t', 'c.training_id', '=','t.id')->get();

                return $commitments;

//                return DB::table('commitments as c')->select(['c.id', 'u.name as user_name', 'u.id as user_id', 't.name as training_name',
//                    't.id as training_id','c.due_date','c.done', 'c.done_date'])
//                    ->where('a.evaluator_id', '=', $user['id'])
//                    ->where('a.role','=', 'jefe')->orWhere('c.user_id','=', $user['id'])
//                    ->join('assessments as a', 'c.user_id','=','a.evaluated_id')
//                    ->join('users as u', 'c.user_id', '=','u.id')
//                    ->join('trainings as t', 'c.training_id', '=','t.id')
//                    ->orderBy('u.name','ASC')->get()->unique();
            }

            return response()->json([]);
        }

        if($role["name"] === "administrador") {
            return DB::table('commitments as c')->select(['c.id', 'u.name as user_name', 'u.id as user_id', 't.name as training_name',
                't.id as training_id','c.due_date','c.done', 'c.done_date'])
                ->where('c.assessment_period_id','=', $activeAssessmentPeriodId)
                ->join('users as u', 'c.user_id', '=','u.id')
                ->join('trainings as t', 'c.training_id', '=','t.id')->orderBy('u.name','ASC')->get();
        }

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

        //First check that the date selected is in the commitments range of dates
        if (!Commitment::inRangeOfDates($request->input('due_date'))){
            return response()->json(['message' => 'La fecha seleccionada se encuentra fuera del periodo definido para registrar y realizar compromisos'],400);
        }

        Commitment::createCommitment($request->all());
        return response()->json(['message' => 'Compromiso creado exitosamente, se ha notificado al usuario mediante correo electrónico']);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Commitment  $commitment
     * @return \Illuminate\Http\Response
     */
    public function show(Commitment $commitment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Commitment  $commitment
     * @return \Illuminate\Http\Response
     */
    public function edit(Commitment $commitment)
    {
        $activeAssessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;
        $role = auth()->user()->role();
        $user = auth()->user();

        if($role['name'] === 'funcionario'){
            $userCommitments = DB::table('commitments as c')->
            select(['c.id', 'u.name as user_name', 'u.id as user_id', 't.name as training_name',
                't.id as training_id','c.due_date','c.done', 'c.done_date'])
                ->where('c.user_id', '=', $user['id'])
                ->where('c.assessment_period_id','=', $activeAssessmentPeriodId)
                ->join('users as u', 'c.user_id', '=','u.id')
                ->join('trainings as t', 'c.training_id', '=','t.id')->get();
            if(!$userCommitments->contains('id','=',$commitment['id'])){
                return response('No tienes permisos suficientes para realizar esta acción', 403);
            }
        }

        $commitment = DB::table('commitments as c')->select(['c.id', 'u.name as user_name', 'u.id as user_id', 't.name as training_name', 't.id as training_id','c.due_date','c.done'])
            ->where('c.id','=', $commitment['id'])
            ->join('users as u', 'c.user_id', '=','u.id')
            ->join('trainings as t', 'c.training_id', '=','t.id')->first();

        return Inertia::render('Commitments/Show', ['commitment' => $commitment, 'role' => $role]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Commitment  $commitment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Commitment $commitment)
    {
        //First check if the commitment is already done
        $isDone = Commitment::isDone($request->all());

        if($isDone){
            return response()->json(['message' => 'No puedes cambiar la información del compromiso si este ya se encuentra realizado'], 400);
        }

        $commitment->update($request->all());
        return response()->json(['message' => 'Compromiso actualizado exitosamente']);
    }

    public function setCommitmentAsDone(Commitment $commitment)
    {

        if ($commitment === null) {
            return response()->json(['error' => 'El compromiso seleccionado no existe', 404]);
        }

        $commitment->done = 1;
        $commitment->done_date = Carbon::now();
        $commitment->save();

        return response()->json(['message' => 'Compromiso marcado como finalizado, ya no se podrá editar ni borrar información del mismo']);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Commitment  $commitment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Commitment $commitment)
    {
        //First check if the commitment is already done
        $isDone = Commitment::isDone($commitment);

        if($isDone){
            return response()->json(['message' => 'No puedes eliminar el compromiso si este ya se encuentra realizado'], 400);
        }

        $commitment->delete();
        return response()->json(['message' => 'Compromiso eliminado exitosamente']);
    }
}
