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
    public $otp; // 🔥 NAYA: OTP Variable Add Kiya

    // Constructor mein $otp = null rakha hai taaki purane emails crash na hon
    public function __construct($actionName, $userEmail, $approveUrl, $rejectUrl, $otp = null)
    {
        $this->actionName = $actionName;
        $this->userEmail = $userEmail;
        $this->approveUrl = $approveUrl;
        $this->rejectUrl = $rejectUrl;
        $this->otp = $otp; // 🔥 NAYA: OTP assign kiya
    }

    public function build()
    {
        return $this->subject('Action Required: ' . $this->actionName)
                    ->view('emails.admin_action');
    }
}