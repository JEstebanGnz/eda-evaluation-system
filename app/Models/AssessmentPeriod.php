<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AssessmentPeriod extends Model
{

    protected $fillable = [
        'name',
        'assessment_start_date',
        'assessment_end_date',
        'commitment_start_date',
        'commitment_end_date',
    ];

    protected $guarded = ['active'];

    public function positions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function userProfiles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserProfile::class);
    }

    public function commitments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Commitment::class);
    }

    public function dependencies(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Dependency::class);
    }

    public function assessments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function aggregateAssessmentResults(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AggregateAssessmentResult::class);
    }

    public function forms(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Form::class);
    }

    public function formAnswers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FormAnswer::class);
    }

    public function assessmentReminders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AssessmentReminder::class);
    }

    public static function migrateActiveAssessmentPeriodInformation($activeAssessmentPeriod, $destinationAssessmentPeriod)
    {
        //We have to migrate the competences, response ideals, position_user and job_title_positions
        $tablesName = ['forms'];

        foreach ($tablesName as $tableName){
            //First validate that there are no records associated to the desired assessmentPeriod to migrate to on the selected table
            $destinationAssessmentPeriodRecords = DB::table($tableName)->where('assessment_period_id','=',$destinationAssessmentPeriod->id)
                ->get();
            //If, in fact, there are no records in the new assessmentPeriod, then proceed with the insert of the data
            if(count($destinationAssessmentPeriodRecords)  === 0){
                $activeAssessmentPeriodRecords = DB::table($tableName)->where('assessment_period_id','=',$activeAssessmentPeriod->id)
                    ->get()->map(function ($item) {
                        return collect($item)->except(['id','created_at', 'updated_at'])->all();
                    })->toArray();
                foreach ($activeAssessmentPeriodRecords as &$activeAssessmentPeriodRecord){
                    $activeAssessmentPeriodRecord['assessment_period_id'] = $destinationAssessmentPeriod['id'];
                    $activeAssessmentPeriodRecord['created_at'] = Carbon::now()->toDateTimeString();
                    $activeAssessmentPeriodRecord['updated_at'] = Carbon::now()->toDateTimeString();
                    DB::table($tableName)->insert($activeAssessmentPeriodRecord);
                }
            }
            else {
                return false;
            }
        }
        return true;
    }

    public static function getActiveAssessmentPeriod()
    {
        return self::where('active', '=',   1)->firstOrFail();
    }

    public static function importResponseIdeals($assessmentPeriodId){

        $previousResponseIdeals = DB::table('response_ideals')->where('assessment_period_id', '=', self::getActiveAssessmentPeriod()->id)->get();

        foreach ($previousResponseIdeals as $responseIdeal){
            $responseIdeal = ResponseIdeal::find($responseIdeal->id);
            $newResponseIdeal = $responseIdeal->replicate(['assessment_period_id']);
            $newResponseIdeal->assessment_period_id = $assessmentPeriodId;
            $newResponseIdeal->save();
        }

    }


    use HasFactory;
}
