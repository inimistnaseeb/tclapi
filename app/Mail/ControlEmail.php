<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ControlEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $controlData;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($controlData)
    {
        $this->controlData = $controlData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.control')
                    ->with([
                        'control' => $this->controlData,
                    ])
                    ->subject('Control Notification');
    }
}
