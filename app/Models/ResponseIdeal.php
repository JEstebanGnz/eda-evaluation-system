<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResponseIdeal extends Model
{

    protected $guarded = [];

    public function positions(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public static function createResponseIdeal($request){
        $activeAssessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;
        return self::UpdateOrCreate(
            [   'position_id' => $request['position_id'],
                'assessment_period_id' => $activeAssessmentPeriodId],
            [
                'response' => json_encode($request['response'])
            ]);
    }

    use HasFactory;
}
