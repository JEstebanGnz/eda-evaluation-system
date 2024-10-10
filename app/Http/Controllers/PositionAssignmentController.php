<?php

namespace App\Http\Controllers;

use App\Models\AssessmentPeriod;
use App\Models\JobTitlePosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Exception\JsonException;

class PositionAssignmentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $activeAssessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;
        $assignments = DB::table('job_title_positions as jtp')->select(['jtp.job_title', 'jtp.position_id', 'p.name as name'])->where('jtp.assessment_period_id', '=', $activeAssessmentPeriodId)->
        leftJoin('positions as p', 'p.id', '=', 'jtp.position_id')->get();
        return response()->json($assignments);
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
    public function update(Request $request, JobTitlePosition $assignment)
    {
    }

    public function createAssignment(Request $request)
    {
        $activeAssessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;
        $assignment = $request->input('data');
        $upsertData = [];
        try {
            //First we do the assignment of the job_title_positions table
            DB::table('job_title_positions')->updateOrInsert(['job_title' => $assignment['job_title'] , 'assessment_period_id' => $activeAssessmentPeriodId],
                ['position_id' => $assignment['position_id']]);
            //Now we have to assign this position to all the functionaries with that job_title
            $functionaries = DB::table('functionary_profiles')->where('job_title','=', $assignment['job_title'])
                ->where('assessment_period_id', '=', $activeAssessmentPeriodId)->get();

            //Now let's insert the data in the array
            foreach ($functionaries as $functionary){
                DB::table('position_user')->updateOrInsert(['user_id' =>$functionary->user_id, 'assessment_period_id' => $activeAssessmentPeriodId],
                    ['position_id' => $assignment['position_id']]);
            }

        } catch (JsonException $e) {
            return response()->json(['message' => 'Ha ocurrido un error con la fuente de datos']);
        }
        return response()->json(['message' => 'Asignación actualizada exitosamente']);
    }


    public function deleteAssignment(Request $request)
    {
        $activeAssessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;
        $assignment = $request->input('data');
        $functionariesArray = [];

        try {
            //First we remove the assignment of the job_title_positions table
            DB::table('job_title_positions')->updateOrInsert(['job_title' => $assignment['job_title'], 'assessment_period_id' => $activeAssessmentPeriodId],
                ['position_id' => null]);
            //Now we have to remove the assign of this position to all the functionaries with that job_title
            $functionaries = DB::table('functionary_profiles')->where('job_title', '=', $assignment['job_title'])
                ->where('assessment_period_id', '=', $activeAssessmentPeriodId)->get();

            //Now let's insert the data in the array
            foreach ($functionaries as $functionary) {
                $functionariesArray [] = $functionary->user_id;
            }

            //Now let's upsert the data
            $users = DB::table('position_user')->whereIn('user_id', $functionariesArray)->where('assessment_period_id', '=', $activeAssessmentPeriodId)->delete();

        } catch (JsonException $e) {
            return response()->json(['message' => 'Ha ocurrido un error con la fuente de datos']);
        }

        return response()->json(['message' => 'Asignación borrada exitosamente']);

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
