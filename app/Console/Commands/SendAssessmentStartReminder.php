<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAssessmentStartReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assessmentReminder:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        set_time_limit(10000);

        $assessmentStartDate = DB::table('assessment_periods as ap')->where('active','=',1)->first()->assessment_start_date;
        $now = Carbon::now();
        $date = $now->toDateString();

        if($date === $assessmentStartDate){
            $users = User::all();
            $users = array_unique(array_column($users->toArray(),'email'));

            foreach ($users as $user){
                $email = new \App\Mail\AssessmentReminderMailable();
                Mail::bcc([$user])->send($email);
            }

            $email = new \App\Mail\AssessmentReminderMailable();
            Mail::bcc(['juanes01.gonzalez@gmail.com'])->send($email);
            Log::info("Cronjob correo recordatorio enviado correctamente");
        }

        return 0;
    }
}
