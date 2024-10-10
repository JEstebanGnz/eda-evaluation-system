<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormAnswer extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assessmentPeriod(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AssessmentPeriod::class);
    }

    public function form(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function assessment(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public static function createFormFromRequest(Request $request, Form $form): void
    {
        $activeAssessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;
        $competencesAverage = self::getCompetencesAverage(json_decode(json_encode($request->input('answers'), JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR));

        $formAnswer = self::create([
            'user_id' => auth()->user()->id,
            'form_id' => $form->id,
            'answers' => json_encode($request->input('answers')),
            'submitted_at' => Carbon::now()->toDateTimeString(),
            'evaluated_id' => $request->input('evaluatedId'),
            'first_competence_average' => $competencesAverage['C1'] ?? null,
            'second_competence_average' => $competencesAverage['C2'] ?? null,
            'third_competence_average' => $competencesAverage['C3'] ?? null,
            'fourth_competence_average' => $competencesAverage['C4'] ?? null,
            'fifth_competence_average' => $competencesAverage['C5'] ?? null,
            'sixth_competence_average' => $competencesAverage['C6'] ?? null,
            'assessment_period_id' => $activeAssessmentPeriodId,
        ]);

        self::updateResponseStatusToAnswered($request->input('evaluatedId'), $request->input('role'), $formAnswer);
    }

    public static function getCompetencesAverage($answers): array
    {
        $competences = self::getCompetencesFromFormAnswer($answers);
        return self::getAveragesFromCompetences($competences);
    }

    private static function getCompetencesFromFormAnswer($formAnswers): array
    {
        $competences = [];
        try{
            foreach ($formAnswers as $answer) {
                if (isset($competences[$answer->competence]) === true) {
                    $competences[$answer->competence]['totalAnswers']++;
                } else {
                    $competences[$answer->competence]['totalAnswers'] = 1;
                }

                // the competence already exist at this point
                if (isset($competences[$answer->competence]['accumulatedValue']) === true) {
                    $competences[$answer->competence]['accumulatedValue'] += (double)$answer->answer;
                } else {
                    $competences[$answer->competence]['accumulatedValue'] = (double)$answer->answer;
                }
            }
        }
        catch (\Exception $exception) {
            $message = 'Debes contestar todas las preguntas para poder enviar el formulario';
            throw new \RuntimeException($message);
        }
        return $competences;
    }

    private static function getAveragesFromCompetences($competences): array
    {
        $averages = [];
        foreach ($competences as $competence => $attributes) {
            $averages[$competence] = $attributes['accumulatedValue'] / $attributes['totalAnswers'];
        }
        return $averages;
    }

    public static function updateResponseStatusToAnswered($evaluatedId, $role, $formAnswer): void
    {
        $evaluatorId = auth()->user()->id;
        $activeAssessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;
        Assessment::where('evaluated_id','=', $evaluatedId)->where('role', $role)->where('evaluator_id', $evaluatorId)
            ->where('assessment_period_id', $activeAssessmentPeriodId)->update([
                'pending' => 0 , 'form_answer_id' => $formAnswer->id
            ]);
    }

    public static function getFunctionaryOpenAnswers($functionaryUserId, $assessmentPeriodId)
    {
        if ($assessmentPeriodId === null){
            $assessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;
        }

        $formAnswers = DB::table('form_answers as fa')->select(['fa.answers', 'a.dependency_identifier','a.role', 'u.name as functionary_name'])
            ->where('fa.evaluated_id', '=', $functionaryUserId)
            ->join('forms as f', 'f.id', '=', 'fa.form_id')
            ->join('users as u', 'u.id', '=', 'fa.user_id')
            ->join('assessments as a', 'a.form_answer_id','=','fa.id')
            ->where('f.assessment_period_id', '=', $assessmentPeriodId)
            ->where('fa.assessment_period_id', '=', $assessmentPeriodId)->get();


        $user = auth()->user();

        return self::mapOpenAnswersToArray($formAnswers, $user->role()['name']);

    }

    public static function mapOpenAnswersToArray($formAnswers, $role)
    {
        $finalOpenAnswers = [];
        $openQuestionsNames = [];
        foreach ($formAnswers as $formAnswer){
            $questions = json_decode($formAnswer->answers);
            foreach ($questions as $question){
                if($question->type === "abierta"){
                    if(!in_array($question->name, $openQuestionsNames, true)){
                        $openQuestionsNames [] = $question->name;

                        if($role === 'administrador'){
                            $finalOpenAnswers [] = (object)[
                                'name' => $question->name,
                                'answers' => [ (object)['answer' => $question->answer, 'role' => $formAnswer->role,
                                    'name' => $formAnswer->functionary_name]
                                ]
                            ];
                        }

                        else{
                            $finalOpenAnswers [] = (object)[
                                'name' => $question->name,
                                'answers' => [ (object)['answer' => $question->answer]
                                ]
                            ];
                        }

                    }
                    else{

                        if($role === 'administrador'){
                            foreach ($finalOpenAnswers as $element){
                                if($element->name === $question->name){
                                    $element->answers [] = (object)['answer' => $question->answer, 'role' => $formAnswer->role,
                                        'name' => $formAnswer->functionary_name];
                                }
                            }
                        }
                        else{
                            foreach ($finalOpenAnswers as $element){
                                if($element->name === $question->name){
                                    $element->answers [] = (object)['answer' => $question->answer];
                                }
                            }
                        }
                    }
                }
            }
        }
        return $finalOpenAnswers;
    }
}
