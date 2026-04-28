<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminActionMail extends Mailable
{
    use Queueable, SerializesModels;
 
    public $actionName;
    public $userEmail;
    public $approveUrl;
    public $rejectUrl;

    public function __construct($actionName, $userEmail, $approveUrl, $rejectUrl)
    {
        $this->actionName = $actionName;
        $this->userEmail = $userEmail;
        $this->approveUrl = $approveUrl;
        $this->rejectUrl = $rejectUrl;
    }

    public function build()
    {
        return $this->subject('Action Required: ' . $this->actionName)
                    ->view('emails.admin_action');
    }
}