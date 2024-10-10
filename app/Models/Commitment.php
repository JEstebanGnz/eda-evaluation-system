<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class Commitment extends Model
{


    protected $guarded = [];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function training(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public static function createCommitment($request)
    {
        $activeAssessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;

        //Create the commitment
        $commitment = self::UpdateOrCreate(
            [
                'user_id' => $request['user_id'],
                'training_id' => $request['training_id'],
                'due_date' => $request['due_date'],
                'done' => $request['done'],
                'assessment_period_id' => $activeAssessmentPeriodId
            ]);

        //Now get the name and the training for the commitment
        $commitment = DB::table('commitments as c')->select(['c.id', 'u.name as user_name', 'u.email as email','t.name as training_name','c.due_date'])
            ->where('c.id','=', $commitment['id'])
            ->where('c.assessment_period_id','=',$activeAssessmentPeriodId)
            ->join('users as u', 'c.user_id', '=', 'u.id')
            ->join('trainings as t', 'c.training_id', '=','t.id')->first();

        //Now send the email to the functionary on the commitment
        $data = ['user_name' => $commitment->user_name,
            'training_name'=> $commitment->training_name,
            'due_date' => (date("d/m/Y", strtotime($commitment->due_date)))];

        $email = new \App\Mail\CommitmentCreated($data);
        Mail::bcc([$commitment->email])->send($email);
    }

    public static function isDone($commitment){
        return $commitment['done'] === 1;
    }

    public static function inRangeOfDates($dueDate){
     return AssessmentPeriod::where('commitment_start_date', '<=', $dueDate)->where('commitment_end_date', '>=', $dueDate)->where('active','=', 1)->first();
    }

    public static function getBossCommitments($commitment){
        return $commitment['done'] === 1;
    }

    public function assessmentPeriod(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Comment::class);
    }

    use HasFactory;
}
