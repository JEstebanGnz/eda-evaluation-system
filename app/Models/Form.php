<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    protected $guarded = [];

    public function assessmentPeriod(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->BelongsTo(AssessmentPeriod::class);
    }

    public function formAnswers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FormAnswer::class);
    }

    public static function getCurrentForms()
    {
        $assessmentPeriodId = (int)AssessmentPeriod::getActiveAssessmentPeriod()->id;
        return self::where('creation_assessment_period_id', '=', $assessmentPeriodId)
            ->with(['assessmentPeriod'])->get();
    }

    public static function createForm($request)
    {
        return self::UpdateOrCreate(
            ['id' => $request->input('id')],
            [
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'assessment_period_id' => $request->input('assessment_period_id'),
                'dependency_role' => $request->input('dependency_role'),
                'position' => $request->input('position'),
                'creation_assessment_period_id' => AssessmentPeriod::getActiveAssessmentPeriod()->id
            ]);
    }

    public static function withoutQuestions(int $assessmentPeriodId = null)
    {
        if ($assessmentPeriodId === null) {
            $assessmentPeriodId = (int)AssessmentPeriod::getActiveAssessmentPeriod()->id;
        }

        return self::where('creation_assessment_period_id', '=', $assessmentPeriodId)
            ->where('questions', '=', null)
            ->with(['assessmentPeriod'])
            ->get();
    }

    use HasFactory;
}
