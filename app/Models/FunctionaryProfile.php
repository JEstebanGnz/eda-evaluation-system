<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use phpDocumentor\Reflection\Types\Boolean;

class FunctionaryProfile extends Model
{
    use HasFactory;
    protected $guarded = [];


    public function  user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getFunctionariesAssessments(string $dependencyIdentifier)
    {
        $activeAssessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;
        $dataFromFunctionaries = [];
        $functionariesFromDependency = DB::table('assessments as a')->select(['a.evaluated_id as id', 'fp.id as functionary_profile_id', 'fp.name as name', 'fp.job_title as job_title'])
            ->where('a.assessment_period_id', '=', $activeAssessmentPeriodId)
            ->join('functionary_profiles as fp','a.evaluated_id', '=', 'fp.user_id')
            ->where('fp.assessment_period_id', '=', $activeAssessmentPeriodId)
            ->where('a.dependency_identifier', '=', $dependencyIdentifier)->distinct()->get();

        foreach ($functionariesFromDependency as $functionaryFromDependency){
            $functionaryAssessments = (object) ['id' => $functionaryFromDependency->id, 'functionary_profile_id' => $functionaryFromDependency->functionary_profile_id,
                'name' => $functionaryFromDependency->name, 'job_title' => $functionaryFromDependency->job_title];

            $assessmentsFromFunctionary = DB::table('assessments as a')->select(['u.name as name', 'a.role as role', 'a.pending'])
                ->where('a.evaluated_id', '=', $functionaryFromDependency->id)
                ->where('a.assessment_period_id', '=', $activeAssessmentPeriodId)
                ->join('users as u','a.evaluator_id', '=', 'u.id')->get();

            foreach($assessmentsFromFunctionary as $assessmentFromFunctionary){
                if($assessmentFromFunctionary->role === "jefe"){
                    $functionaryAssessments->boss = $assessmentFromFunctionary->name;
                    $functionaryAssessments->bossPending = $assessmentFromFunctionary->pending;
                }
                if($assessmentFromFunctionary->role === "par"){
                    $functionaryAssessments->peer = $assessmentFromFunctionary->name;
                    $functionaryAssessments->peerPending = $assessmentFromFunctionary->pending;
                }
                if($assessmentFromFunctionary->role === "cliente interno" || $assessmentFromFunctionary->role === "cliente externo"){
                    $functionaryAssessments->client = $assessmentFromFunctionary->name;
                    $functionaryAssessments->clientPending = $assessmentFromFunctionary->pending;
                }
                if($assessmentFromFunctionary->role === "autoevaluación"){
                    $functionaryAssessments->autoPending = $assessmentFromFunctionary->pending;
                }
            }
            $dataFromFunctionaries [] = $functionaryAssessments;
        }

        return $dataFromFunctionaries;
    }

    public static function createOrUpdateFromArray(array $functionaries): void
    {
        $activeAssessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;
        $assessmentPeriodAsString = (string)$activeAssessmentPeriodId;
        $functionaryRoleId = Role::getRoleIdByName('funcionario');


        /**First we check those users who are no longer on the university but still appear on functionary_profiles DB
        and proceed to save the info on functionaries_data_changes */
        self::checkForNoLongerFunctionaries($functionaries, $activeAssessmentPeriodId);

        $finalFunctionaries = [];
        foreach ($functionaries as $functionary){
            /** Here we check if the users that really exist in endpoint have any information changed
             (position, program) and if so, we save the info on functionaries_data_changes and do not proceed to sync directly
             */
            if (self::functionaryHasNoPendingChanges($functionary, $activeAssessmentPeriodId))
            {
                $finalFunctionaries [] = $functionary;
            }
        }

        $jobTitles = array_unique(array_column($finalFunctionaries, 'position'));
        Position::syncJobTitles($jobTitles);

        $errorMessage = '';
        foreach ($finalFunctionaries as $functionary) {
            $user = User::firstOrCreate(['email' => $functionary['email']], ['name' => $functionary['full_name'],
                'password' => 'automatic_generate_password']);

            if ($functionary['dep_code'] === "") {
                continue;
            }

            $dependencyIdentifier = $functionary['dep_code'] . '-' . $assessmentPeriodAsString;
            try {
                self::updateOrCreate(
                    [
                        'identification_number' => $functionary['identification'],
                        'dependency_identifier' => $dependencyIdentifier === '' ? null : $dependencyIdentifier,
                    ],
                    [
                        'name' => $functionary['full_name'],
                        'user_id' => $user->id,
                        'dependency_name' => $functionary['faculty'] === '' ? null : $functionary['faculty'],
                        'job_title' => $functionary['position'] === '' ? null : $functionary['position'],
                        'hire_date' => $functionary['date_admission'] === '' ? null : $functionary['date_admission'],
                        'assessment_period_id' => $activeAssessmentPeriodId
                    ]);

                DB::table('role_user')->updateOrInsert(
                    ['user_id' => $user->id,
                        'role_id' => $functionaryRoleId]
                );
                self::assignFunctionaryToDependency($user->id, $dependencyIdentifier);
            } catch (\Exception $e) {
                $errorMessage .= nl2br("Ha ocurrido el siguiente error mirando al funcionario $functionary[full_name] : {$e->getMessage()}");
            }
        }
        if ($errorMessage !== '') {
            throw new \RuntimeException($errorMessage);
        }

    }

    public static function checkForNoLongerFunctionaries($functionaries, $activeAssessmentPeriodId): void
    {
        //Iterate from every user on funtionary_profiles table and check if exists on the $functionaries endPoint.
        $functionariesOnDB = DB::table('functionary_profiles')->where("assessment_period_id", '=', $activeAssessmentPeriodId)->get();
        $functionaries = collect($functionaries);

        $noLongerFunctionariesArray = [];
        foreach ($functionariesOnDB as $functionary){
                $existsInEndPoint = $functionaries->contains('identification', '=',$functionary->identification_number);
                if(!$existsInEndPoint){
                    $noLongerFunctionariesArray [] = $functionary;
                }
        }

        if(count($noLongerFunctionariesArray)>0){
            foreach ($noLongerFunctionariesArray as $functionaryToDelete){
                $payload = json_encode(["user_id" =>null,"name" => '',
                    "identification_number" => '', "dependency_name" => '',
                    "dependency_identifier" => '',
                    "job_title" => ''], JSON_THROW_ON_ERROR);

                DB::table('functionaries_data_changes')->updateOrInsert(["user_id" => $functionaryToDelete->user_id,
                    "assessment_period_id" => $functionaryToDelete->assessment_period_id],["payload" => $payload]);
            }
        }
    }

    public static function functionaryHasNoPendingChanges($functionary, $activeAssessmentPeriodId): bool
    {
        //First let's check if user is already on table functionary_profiles
        $functionaryDB = self::where('identification_number', '=', $functionary['identification'])->where('assessment_period_id', '=', $activeAssessmentPeriodId)->first();

        //If the person already exists, let's check if there are some changes from the last sync on DB
        if ($functionaryDB) {
            $functionaryEndPointArray = ["position" => $functionary['position'],
                "program" => $functionary['program'],
            ];

            $functionaryDBArray = ["position" => $functionaryDB['job_title'],
                "program" => $functionaryDB['dependency_name'],
            ];

            $changes = array_diff($functionaryEndPointArray, $functionaryDBArray);
            //If, in fact, there are changes, then we proceed and insert that info on the functionaries_info_changes
            if (count($changes) > 0) {
                if (isset($changes["position"]) || isset($changes["program"])) {
                    $payload = json_encode(["user_id" =>$functionaryDB['user_id'],"name" => $functionary["full_name"],
                                            "identification_number" => $functionary["identification"], "dependency_name" => $functionary["program"],
                                            "dependency_identifier" => $functionary["dep_code"].'-'.$activeAssessmentPeriodId,
                                            "job_title" => $functionary["position"], "hire_date" => $functionary["date_admission"]], JSON_THROW_ON_ERROR);

                    DB::table('functionaries_data_changes')->updateOrInsert(["user_id" => $functionaryDB['user_id'],
                        "assessment_period_id" => $functionaryDB['assessment_period_id']],["payload" => $payload]);

                    return false;
                }
            }
        }
        return true;
    }

    public static function assignFunctionaryToDependency($userId, $dependencyIdentifier): void
    {
        $functionaryRoleId = Role::getRoleIdByName('funcionario');
        $activeAssessmentPeriod = AssessmentPeriod::getActiveAssessmentPeriod()->id;

        DB::table('dependency_user')->updateOrInsert(
            ['user_id' => $userId, 'dependency_identifier' => $dependencyIdentifier, 'role_id' => $functionaryRoleId],
            ['user_id' => $userId, 'dependency_identifier' => $dependencyIdentifier, 'role_id' => $functionaryRoleId]
        );

        $user = DB::table('assessments')->where('evaluated_id', $userId)
            ->where('evaluator_id', $userId)->where('assessment_period_id', '=', $activeAssessmentPeriod)->first();

        if (!$user) {
            DB::table('assessments')->updateOrInsert(
                ['evaluated_id' => $userId, 'evaluator_id' => $userId, 'role' => 'autoevaluación'],
                ['pending' => 1, 'dependency_identifier' => $dependencyIdentifier,
                    'assessment_period_id' => $activeAssessmentPeriod,
                    'created_at' => Carbon::now()->toDateTimeString(),
                    'updated_at' => Carbon::now()->toDateTimeString()]);
        }
    }
}
