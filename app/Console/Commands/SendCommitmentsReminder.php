<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendCommitmentsReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commitmentReminder:send';

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
        $today = Carbon::today();
        $todayAsString = $today->toDateString();

        //First, check if today is in the range of dates for commitments
        $inRangeOfDates = DB::table('assessment_periods as ap')->where('ap.commitment_start_date', '<=', $todayAsString)
            ->where('ap.commitment_end_date', '>=', $todayAsString)->where('ap.active','=',1)->first();

        if($inRangeOfDates){
            //get all the commitments
            $commitments = DB::table('commitments as c')
                ->select(['c.id', 'u.name as user_name', 'u.email as email','t.name as training_name','c.due_date'])
                ->where('c.assessment_period_id','=', 1)
                ->where('c.done','=', 0)
                ->join('users as u', 'c.user_id', '=', 'u.id')
                ->join('trainings as t', 'c.training_id', '=','t.id')->get();

            //get the days prior to due date in which the reminder has to be sent
            $reminder = DB::table('reminders as r')->where('r.reminder_type','=', 'commitment')
                ->where('r.send_before','=', 'finish')->first();

            if($reminder){
                //Now iterate over all the commitments and check if today is the date when the reminder has to be sent
                foreach ($commitments as $commitment){
                    $sendDate = $today->addDays($reminder->days)->toDateString();
                    if($sendDate === $commitment->due_date){

                        //Si hoy es el día, entonces lanzamos un correo
                        $data = ['user_name' => $commitment->user_name,
                            'training_name'=> $commitment->training_name,
                            'due_date' => (date("d/m/Y", strtotime($commitment->due_date)))];

                        $email = new \App\Mail\CommitmentReminder($data);
                        Mail::bcc([$commitment->email])->send($email);
                    }
                }
                Log::info("Commitment reminders correctly sent");
            }
        }

        return 0;
    }
}
