<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Assessment extends Model
{

    protected $guarded = [];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->BelongsTo(User::class);
    }

    public function assessmentPeriod(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->BelongsTo(AssessmentPeriod::class);
    }

    public function formAnswer(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(FormAnswer::class);
    }

    public static function getUserAssessments($user = null){

        $activeAssessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;
        $now = Carbon::now();
        $date = $now->toDateString();

        if ($user !== null){
            return  DB::table('assessments as a')->select(['a.id', 'a.pending','u.name', 'a.role', 'a.evaluator_id', 'a.evaluated_id', 'a.assessment_period_id'])
                ->where('evaluated_id','=', $user['user_id'])->where('assessment_period_id','=', $activeAssessmentPeriodId)
                ->join('users as u', 'u.id', '=', 'a.evaluator_id')->get();
        }

        $user = auth()->user();

        if($user->role()->name == "funcionario" || $user->role()->name == "cliente externo") {
            $validDate = DB::table('assessment_periods as ap')->where('ap.active','=',1)
                ->where('ap.assessment_start_date','<=',$date)->where('ap.assessment_end_date','>=',$date)->first();

            if($validDate){
            return  DB::table('assessments as a')
                ->select(['a.id', 'a.pending','u.name', 'a.role', 'a.evaluator_id', 'a.evaluated_id', 'a.assessment_period_id', 'fp.dependency_name'])
                ->where('evaluator_id','=', $user['id'])->where('a.assessment_period_id','=', $activeAssessmentPeriodId)
                ->join('users as u', 'u.id', '=', 'a.evaluated_id')
                ->join('functionary_profiles as fp', 'fp.user_id', '=', 'u.id')
                ->where('fp.assessment_period_id','=', $activeAssessmentPeriodId)->get();
            }
            }
    }

    public static function createAssessment($request){

        $activeAssessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;

        return self::UpdateOrCreate(
            [
                'evaluated_id' => $request->input('evaluated_id'),
                'role' => $request->input('role'),
                'assessment_period_id' => $activeAssessmentPeriodId,
            ],
            [
                'evaluator_id' => $request->input('evaluator_id'),
                'pending' => $request->input('pending'),
                'dependency_identifier' => $request->input('dependency_identifier')
            ]);
    }

    public static function userHasPeerAssessment($userFormAnswers, $key, $value): bool
    {
        foreach ($userFormAnswers as $userFormAnswer){
            if (isset($userFormAnswer->$key) && $userFormAnswer->$key === $value) {
                return true; // The user has a peer assessment, therefore it is a regular type of assessment (the assessment weights are ok)
            }
        }
        return false;
    }

    use HasFactory;
}
