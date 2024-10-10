<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Competence extends Model
{
    protected $guarded = [];
    use HasFactory;

    public static function createCompetence($request)
    {
        $activeAssessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;
        return self::UpdateOrCreate(
            [   'name' => $request['name'],
                'assessment_period_id' => $activeAssessmentPeriodId],
            [
                'position' => $request['position'],
            ]);
    }

    public static function reOrderCompetencesOnDelete($deletedCompetencePosition)
    {
        $activeAssessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;

        //First let's get all the current competences
        $competencesToOrder = DB::table('competences')->where('assessment_period_id', '=', $activeAssessmentPeriodId)
            ->where('position','>',$deletedCompetencePosition)->orderBy('position', 'DESC')->get();

        if(count($competencesToOrder)>0){
            foreach ($competencesToOrder as $competence){
                DB::table('competences')->updateOrInsert(['id' => $competence->id, 'assessment_period_id' => $activeAssessmentPeriodId],['position' => $competence->position-1]);
            }
        }

    }

    public static function reOrderCompetencesOnPositionChange($position,$oldPosition, $newPosition)
    {
        $activeAssessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;

        if ($oldPosition == $newPosition){
            return response()->json(['message' => 'La posición nueva debe ser diferente a la actual'], 500);
        }

        DB::table('competences')->where('assessment_period_id','=', $activeAssessmentPeriodId)
            ->where('position','=', $newPosition)->update(['position' => $oldPosition]);

        DB::table('competences')->where('id', '=', $position['id'])->update(['position' => $newPosition]);

        return response()->json(['message' => 'Posición actualizada correctamente']);


    }


}
