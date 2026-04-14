<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast; // Ye zaroori hai
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoginApprovedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sessionId;
    public $status;
    public $token;

    public function __construct($sessionId, $status, $token = null)
    {
        $this->sessionId = $sessionId;
        $this->status = $status;
        $this->token = $token;
    }

    // Is channel par user ka browser listen karega
    public function broadcastOn(): array
    {
        return [
            new Channel('login-status.' . $this->sessionId),
        ];
    }

    public function broadcastAs()
    {
        return 'LoginApproved';
    }
}