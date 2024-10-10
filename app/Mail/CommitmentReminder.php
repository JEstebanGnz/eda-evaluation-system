<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CommitmentReminder extends Mailable
{
    use Queueable, SerializesModels;

    public $subject = "Recordatorio diligenciamiento de compromiso para Evaluación de Desempeño";
    public $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('commitmentReminder')->with('data', $this->data);
    }
}
