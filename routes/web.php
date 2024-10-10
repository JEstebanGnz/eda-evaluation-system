<?php

use App\Models\AssessmentPeriod;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


/* >>>>>>>>>>>>>>>>>>>>>>>  Assessments routes >>>>>>>><<<<<< */
Route::resource('api/assessments', \App\Http\Controllers\AssessmentController::class, [
    'as' => 'api'
])->middleware('auth');


/* >>>>>>>>>>>>>>>>>>>>>>>  Assessment Periods routes >>>>>>>><<<<<< */
Route::inertia('/assessmentPeriods', 'AssessmentPeriods/Index')->middleware(['auth', 'isAdmin'])->name('assessmentPeriods.index.view');
Route::resource('api/assessmentPeriods', \App\Http\Controllers\AssessmentPeriodController::class, [
    'as' => 'api'
])->middleware('auth');
Route::post('/api/assessmentPeriods/{assessmentPeriod}/setActive', [\App\Http\Controllers\AssessmentPeriodController::class, 'setActive'])
    ->middleware(['auth', 'isAdmin'])->name('api.assessmentPeriods.setActive');
Route::post('/api/assessmentPeriods/{assessmentPeriod}/migrateActiveAssessmentPeriod', [\App\Http\Controllers\AssessmentPeriodController::class, 'migrateActiveAssessmentPeriodInfo'])
    ->middleware(['auth', 'isAdmin'])->name('api.assessmentPeriods.migrateActive');



/* >>>>>Certifications routes <<<<<< */
Route::resource('api/certifications', \App\Http\Controllers\CertificationController::class, [
    'as' => 'api'])->middleware('auth');
Route::get('/certifications/{certification}/downloadFile', [\App\Http\Controllers\CertificationController::class, 'downloadFile'])->middleware(['auth'])
    ->name('certifications.downloadFile');


/* >>>>>Comments routes <<<<<< */
Route::resource('api/comments', \App\Http\Controllers\CommentController::class, [
    'as' => 'api'])->middleware('auth');


/* >>>>>Commitments routes <<<<<< */
Route::inertia('/commitments', 'Commitments/Index')->name('commitments.index.view')->middleware('auth');
Route::get('/commitments/landing', [\App\Http\Controllers\CommitmentController::class, 'landing'])->middleware(['auth'])->name('commitments.landing');
Route::resource('api/commitments', \App\Http\Controllers\CommitmentController::class, [
    'as' => 'api'])->middleware('auth');
Route::get('/commitments/{role}', [\App\Http\Controllers\CommitmentController::class, 'indexCommitments'])->middleware(['auth'])->name('commitments.index');
Route::get('/commitments/{commitment}/finish', [\App\Http\Controllers\CommitmentController::class, 'setCommitmentAsDone'])->middleware(['auth', 'isAdmin'])->name('commitments.setDone');

/* >>>>>>>>>>>>>>>>>>>>>>>>>>>> Commitment Reminder routes <<<<<<<<<<<<<<<<<<<<<<<<<<< */
Route::inertia('/commitments/landing/reminders', 'Commitments/Reminders')->middleware(['auth', 'isAdmin'])->name('reminders.index.view');
Route::resource('api/reminders', \App\Http\Controllers\ReminderController::class, [
    'as' => 'api'
])->middleware('auth');


/* >>>>>Competences routes <<<<<< */
Route::inertia('/competences', 'Competences/Index')->middleware(['auth', 'isAdmin'])->name('competences.index.view');
Route::resource('api/competences', \App\Http\Controllers\CompetenceController::class, [
    'as' => 'api'
])->middleware('auth');
Route::post('competences/updateOrder', [\App\Http\Controllers\CompetenceController::class, 'updateOrder'])->middleware(['auth', 'isAdmin'])
    ->name('competences.updateOrder');

/* >>>>>Dependencies routes <<<<<< */
Route::inertia('/dependencies', 'Dependencies/Index')->middleware(['auth', 'isAdmin'])->name('dependencies.index.view');
Route::resource('api/dependencies', \App\Http\Controllers\DependencyController::class, [
    'as' => 'api'])->middleware('auth');
Route::get('/api/dependencies/{dependency}', [\App\Http\Controllers\DependencyController::class, 'edit'])->middleware(['auth', 'isAdmin'])
    ->name('api.dependencies.edit');
Route::get('/api/dependencies/{dependency}/assessmentStatus', [\App\Http\Controllers\DependencyController::class, 'assessmentStatus'])
    ->middleware(['auth', 'isAdminOrDependencyAdmin'])->name('api.dependencies.assessmentStatus');
Route::post('/api/dependencies/sync', [\App\Http\Controllers\DependencyController::class, 'sync'])->middleware(['auth', 'isAdmin'])
    ->name('api.dependencies.sync');
Route::get('/api/dependencies/{dependency}/admins', [\App\Http\Controllers\DependencyController::class, 'getAdmins'])->middleware(['auth'])
    ->name('api.dependencies.admins');
Route::inertia('/dependencies/admin/landing', 'Dependencies/LandingMultipleDependenciesAdmin')->middleware(['auth', 'isAdminOrDependencyAdmin'])
    ->name('dependencies.landing');
Route::inertia('/assessmentStatus', 'Dependencies/AssessmentStatus')->middleware(['auth', 'isAdmin']);

/* >>>>>DependencyAdmin routes <<<<<< */
Route::resource('api/{dependency}/dependencyAdmins', \App\Http\Controllers\DependencyAdminController::class, [
    'as' => 'api'])->middleware(['auth', 'isAdmin']);

/* >>>>>>>>>>>>>>>>>>>>>>>  ExternalClients routes >>>>>>>><<<<<< */
Route::inertia('/externalClients', 'ExternalClients/Index')->middleware(['auth', 'isAdmin'])->name('externalClients.index.view');
Route::resource('api/externalClients', \App\Http\Controllers\ExternalClientController::class, [
    'as' => 'api'
])->middleware('auth');
Route::post('/api/externalClient/updatePassword', [\App\Http\Controllers\ExternalClientController::class, 'updatePassword'])->middleware(['auth', 'isAdmin'])
    ->name('api.externalClients.updatePassword');


/* >>>>>>>>>>>>>>>>>>>>>>>  Files routes >>>>>>>><<<<<< */
Route::post('/file/upload', [\App\Http\Controllers\ExternalClientController::class, 'uploadFile'])->middleware(['auth'])
    ->name('file.upload');

/* >>>>>>>>>>>>>>>>>>>>> Forms routes <<<<<<<<<<<<<<<<<<<< */
Route::get('api/forms/withoutQuestions', [\App\Http\Controllers\FormController::class, 'getWithoutQuestions'])->middleware(['auth', 'isAdmin'])
    ->name('api.forms.withoutQuestions');
Route::get('api/forms/copyFromPeriod/{assessmentPeriod}', [\App\Http\Controllers\FormController::class, 'copyFromPeriod'])->name('api.forms.copyFromPeriod')
    ->middleware(['auth', 'isAdmin']);
Route::inertia('/forms', 'Forms/Index')->middleware(['auth', 'isAdmin'])->name('forms.index.view');
Route::inertia('/forms/{form}', 'Forms/Show')->middleware(['auth', 'isAdmin'])->name('forms.show.view');
Route::resource('api/forms', \App\Http\Controllers\FormController::class, [
    'as' => 'api'
])->middleware('auth');
Route::get('borrarForm/{form}', [\App\Http\Controllers\FormController::class, 'destroy'])->middleware(['auth', 'isAdmin']);
Route::post('api/forms/{form}/copy', [\App\Http\Controllers\FormController::class, 'copy'])->name('api.forms.copy')->middleware(['auth', 'isAdmin']);
Route::patch('api/forms/{form}/formQuestions', [\App\Http\Controllers\FormQuestionController::class, 'storeOrUpdate'])->name('api.forms.questions.store')
    ->middleware(['auth', 'isAdmin']);
Route::get('api/forms/{form}/formQuestions', [\App\Http\Controllers\FormQuestionController::class, 'getByFormId'])->name('api.forms.questions.show')
    ->middleware(['auth']);


/* >>>>>>>>>>>>>>>>>>>>> Forms answers routes <<<<<<<<<<<<<<<<<<<< */
Route::resource('api/answers', \App\Http\Controllers\FormAnswersController::class, [
    'as' => 'api'
])->middleware('auth');
Route::get('answers/aggregateGrades', [\App\Http\Controllers\FormAnswersController::class, 'indexAggregateGrades'])->name('api.answers.aggregateGrades')
    ->middleware(['auth', 'isAdmin']);
Route::get('answers/openAnswers', [\App\Http\Controllers\FormAnswersController::class, 'getOpenAnswers'])->name('api.answers.openAnswers')
    ->middleware(['auth', 'isAdmin']);


/* >>>>>FunctionaryProfile routes <<<<<< */
Route::inertia('/functionaries', 'Functionaries/Index')->middleware(['auth', 'isAdmin'])->name('functionaries.index.view');
Route::post('/api/functionaryProfiles/sync', [\App\Http\Controllers\FunctionaryProfileController::class, 'sync'])->middleware(['auth', 'isAdmin'])
    ->name('api.functionaryProfiles.sync');
Route::post('/api/functionaryProfiles/{functionaryProfile}/changeStatus', [\App\Http\Controllers\FunctionaryProfileController::class, 'changeStatus'])
    ->middleware(['auth', 'isAdmin'])->name('api.functionaryProfiles.changeStatus');
Route::resource('api/functionaries', \App\Http\Controllers\FunctionaryProfileController::class, [
    'as' => 'api'])->middleware('auth');
Route::get('/api/{dependency}/functionaryProfiles/{functionaryProfile}', [\App\Http\Controllers\FunctionaryProfileController::class, 'edit'])
    ->middleware(['auth', 'isAdmin'])->name('api.functionaryProfiles.edit');
Route::get('/functionaries/changes', [\App\Http\Controllers\FunctionaryProfileController::class, 'getPendingChanges'])->middleware(['auth', 'isAdmin'])
    ->name('functionaryProfiles.pendingChanges');
Route::post('/functionaries/changes/{userId}/approve',[\App\Http\Controllers\FunctionaryProfileController::class, 'approveChange'])
    ->middleware(['auth', 'isAdmin'])->name('functionaryProfiles.change.approve');
Route::post('/functionaries/changes/{userId}/delete',[\App\Http\Controllers\FunctionaryProfileController::class, 'declineChange'])
    ->middleware(['auth', 'isAdmin'])->name('functionaryProfiles.change.decline');


/* >>>>>>>>>>>>>>>>>>>>>>>  Positions routes >>>>>>>><<<<<< */
Route::inertia('/positions', 'Positions/Index')->middleware(['auth', 'isAdmin'])->name('positions.index.view');
Route::get('api/positions/ableToAssign/', [\App\Http\Controllers\PositionController::class, 'ableToAssign'])->middleware(['auth', 'isAdmin'])
    ->name('api.positions.ableToAssign');
Route::resource('api/positions', \App\Http\Controllers\PositionController::class, [
    'as' => 'api'
])->middleware('auth');

/* >>>>>>>>>>>>>>>>>>>>>>>  Positions Assignment routes >>>>>>>><<<<<< */
Route::inertia('/positions/assignment', 'PositionAssignment/Index')->middleware(['auth', 'isAdmin'])->name('positions.assignment.index.view');
Route::resource('api/positionsAssignment', \App\Http\Controllers\PositionAssignmentController::class, [
    'as' => 'api'
])->middleware('auth');
Route::post('api/positionsAssignment/create', [\App\Http\Controllers\PositionAssignmentController::class, 'createAssignment'])
    ->middleware(['auth', 'isAdmin'])->name('api.positionsAssignment.create');
Route::post('api/positionsAssignment/destroy', [\App\Http\Controllers\PositionAssignmentController::class, 'deleteAssignment'])
    ->middleware(['auth', 'isAdmin'])->name('api.positionsAssignment.destroy');



/* >>>>>Reports routes<<<<<< */
Route::inertia('/reports/assessments', 'Reports/Assessments')->middleware(['auth', 'isAdmin'])->name('reports.assessments.index');
Route::get('/reports/assessments/available', [\App\Http\Controllers\ReportsController::class, 'hasAssessmentReportAvailable'])->middleware(['auth'])->name('reports.assessment.available');
Route::post('/reports/assessmentPDF', [\App\Http\Controllers\ReportsController::class, 'getAssessmentPDF'])->middleware(['auth'])->name('reports.assessmentPDF');
Route::post('/reports/commitmentPDF', [\App\Http\Controllers\ReportsController::class, 'getCommitmentsStatusPDF'])->middleware(['auth'])->name('reports.commitmentPDF');


/* >>>>>ResponseIdeals routes<<<<<< */
Route::inertia('/responseIdeals', 'ResponseIdeals/Index')->middleware(['auth', 'isAdmin'])->name('responseIdeals.index.view');
Route::resource('api/responseIdeals', \App\Http\Controllers\ResponseIdealController::class, [
    'as' => 'api'
])->middleware('auth');

/* >>>>>Roles routes <<<<<< */
Route::get('/roles', [\App\Http\Controllers\Roles\RoleController::class, 'index'])->middleware(['auth', 'isAdmin'])->name('roles.index');
Route::resource('api/roles', \App\Http\Controllers\Roles\ApiRoleController::class, [
    'as' => 'api'
])->middleware('auth');


/* >>>>>>>>>>>>>>>>>>>>>>>>>>>> Test routes <<<<<<<<<<<<<<<<<<<<<<<<<<< */
Route::get('/tests', [\App\Http\Controllers\TestController::class, 'indexView'])->middleware(['auth'])->name('tests.index.view');
Route::post('/tests/{testId}', [\App\Http\Controllers\TestController::class, 'startTest'])->middleware(['auth'])->name('tests.startTest');
Route::resource('api/tests', \App\Http\Controllers\TestController::class, [
    'as' => 'api'])->middleware('auth');


/* >>>>>>>>>>>>>>>>>>>>>>>>>>>> Training routes <<<<<<<<<<<<<<<<<<<<<<<<<<< */
Route::inertia('/commitments/landing/trainings', 'Commitments/Trainings')->middleware(['auth', 'isAdmin'])->name('trainings.index.view');
Route::resource('api/trainings', \App\Http\Controllers\TrainingController::class, [
    'as' => 'api'
])->middleware('auth');


/* >>>>>User routes <<<<<< */
Route::get('/users', [\App\Http\Controllers\Users\UserController::class, 'index'])->middleware(['auth', 'isAdmin'])->name('users.index');
Route::resource('api/users', \App\Http\Controllers\Users\ApiUserController::class, [
    'as' => 'api'])->middleware('auth');
//Update user role
Route::patch('/api/users/{user}/roles', [\App\Http\Controllers\Users\ApiUserController::class, 'updateUserRoles'])->middleware(['auth', 'isAdmin'])->name('api.users.roles.update');
Route::get('/api/users/{user}/roles', [\App\Http\Controllers\Users\ApiUserController::class, 'getUserRoles'])->middleware('auth')->name('api.users.roles.show');
Route::post('/api/roles/select', [\App\Http\Controllers\Users\ApiUserController::class, 'selectRole'])->middleware('auth')->name('api.roles.selectRole');
Route::post('/users/{userId}/impersonate', [\App\Http\Controllers\Users\UserController::class, 'impersonate'])->middleware(['auth', 'isAdmin'])->name('users.impersonate');


/* >>>>>>>>>>>>>>>>>>>>>>>>>>>> Auth routes <<<<<<<<<<<<<<<<<<<<<<<< */
Route::get('/login', [\App\Http\Controllers\AuthController::class, 'landing'])->name('login');
Route::post('/loginExternal', [\App\Http\Controllers\AuthController::class, 'externalClientLogin'])->name('externalClient.login');
Route::get('/', [\App\Http\Controllers\AuthController::class, 'handleRoleRedirect'])->middleware(['auth'])->name('redirect');
Route::get('/googleLogin', [\App\Http\Controllers\AuthController::class, 'redirectGoogleLogin'])->name('googleLogin');
Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('logout');
Route::get('/google/callback', [\App\Http\Controllers\AuthController::class, 'handleGoogleCallback']);
Route::get('/pickRole', [\App\Http\Controllers\AuthController::class, 'pickRole'])->name('pickRole');

Route::get('realTest', function (){
/*

    $now = Carbon::now();
    $date = $now->toDateString();

    $inRangeOfSuitableDates = DB::table('assessment_periods as ap')->where('active','=',1)
        ->where('assessment_end_date','<=',"2024-05-11")->where('commitment_start_date','>=',"2024-05-11")->first();

    if($inRangeOfSuitableDates){
        $activeAssessmentPeriodId = AssessmentPeriod::getActiveAssessmentPeriod()->id;

        //First retrieve all the form_answers
        $formAnswers = DB::table('form_answers as fa')
            ->select(['fa.user_id','fa.evaluated_id','a.role','fa.first_competence_average','fa.second_competence_average',
                'fa.third_competence_average','fa.fourth_competence_average','fa.fifth_competence_average','fa.sixth_competence_average',])
            ->where('fa.assessment_period_id','=',$activeAssessmentPeriodId)
            ->join('assessments as a', 'fa.id','=','a.form_answer_id')->get()->toArray();

        $evaluatedIds = array_unique(array_column($formAnswers, 'evaluated_id'));
        $weights = DB::table('actors_assessment_weight')->get();

        //Now iterate over every evaluatedId and filter the $formAnswers array and do the calculations
        foreach ($evaluatedIds as $evaluatedId){

//        $userAssignments = DB::table('assessments as a')->where('a.assessment_period_id','=',$activeAssessmentPeriodId)
//          ->where('a.evaluated_id','=',$evaluatedId)->get();

            $userFormAnswers = array_filter($formAnswers, function ($formAnswer) use($evaluatedId){
                return $formAnswer->evaluated_id === $evaluatedId;
            });

            //In order to calculate the final aggregate results, AT LEAST 3 user's actors have to submit the answer
            if(count($userFormAnswers) > 2){

                //First, define the final values
                $final_first_aggregate_competence = 0;
                $final_second_aggregate_competence= 0;
                $final_third_aggregate_competence= 0;
                $final_fourth_aggregate_competence = 0;
                $final_fifth_aggregate_competence= 0;
                $final_sixth_aggregate_competence= 0;

                //Now we will execute the Strategy design pattern, to know what are going to be the assessment weights for every actor that submitted the answer
                $userHasPeerAssessment = \App\Models\Assessment::userHasPeerAssessment($userFormAnswers, 'role','par');
                $peerWeight = $weights->where('name', '=', 'par')->first()->value;

                foreach ($userFormAnswers as $formAnswer) {
                    if ($formAnswer->role === "cliente interno" || $formAnswer->role === "cliente externo") {
                        $formAnswer->role = "cliente";
                    }
                    //Depending on the assessment role, then we get the assessment weight
                    $assessmentWeight = $weights->where('name', '=', $formAnswer->role)->first()->value;

                    if (!$userHasPeerAssessment){
                        //Then the peer perspective weight has to be distributed equally on the boss and internal/external client perspective
                        if ($formAnswer->role === "jefe" || $formAnswer->role === "cliente") {
                            $assessmentWeight = $assessmentWeight + ($peerWeight/2);
                        }
                    }

                    //Now for every $formAnswer, multiply the value for the corresponding $assessmentWeight and add it to the final result on every final_aggregate_competence
                    $final_first_aggregate_competence += $formAnswer->first_competence_average * $assessmentWeight;
                    $final_second_aggregate_competence += $formAnswer->second_competence_average * $assessmentWeight;
                    $final_third_aggregate_competence += $formAnswer->third_competence_average * $assessmentWeight;
                    $final_fourth_aggregate_competence += $formAnswer->fourth_competence_average * $assessmentWeight;
                    $final_fifth_aggregate_competence += $formAnswer->fifth_competence_average * $assessmentWeight;
                    $final_sixth_aggregate_competence += $formAnswer->sixth_competence_average * $assessmentWeight;

                }

                $firstCompetenceTotal = number_format($final_first_aggregate_competence, 1);
                $secondCompetenceTotal = number_format($final_second_aggregate_competence, 1);
                $thirdCompetenceTotal = number_format($final_third_aggregate_competence, 1);
                $fourthCompetenceTotal = number_format($final_fourth_aggregate_competence, 1);
                $fifthCompetenceTotal = number_format($final_fifth_aggregate_competence, 1);
                $sixthCompetenceTotal = number_format($final_sixth_aggregate_competence, 1);

                //Get the dependency of the user
                $dependencyIdentifier = DB::table('assessments as a')->where('evaluated_id','=',$evaluatedId)
                    ->where('assessment_period_id','=',$activeAssessmentPeriodId)->where('pending','=',0)->first()->dependency_identifier;

                //Insert the data
                DB::table('aggregate_assessment_results')->updateOrInsert(
                    ['user_id' => $evaluatedId, 'assessment_period_id' => $activeAssessmentPeriodId],
                    ['first_competence' => $firstCompetenceTotal,
                        'second_competence' => $secondCompetenceTotal,
                        'third_competence' => $thirdCompetenceTotal,
                        'fourth_competence' => $fourthCompetenceTotal,
                        'fifth_competence' => $fifthCompetenceTotal,
                        'sixth_competence' => $sixthCompetenceTotal,
                        'dependency_identifier' => $dependencyIdentifier,
                        'role' => 'promedio final',
                        'created_at' => Carbon::now()->toDateTimeString(),
                        'updated_at' => Carbon::now()->toDateTimeString()]);
            }
        }
    }*/
});



