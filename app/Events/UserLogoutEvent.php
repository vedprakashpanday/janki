<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLogoutEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $tokenId; 

    public function __construct($userId, $tokenId = null)
    {
        $this->userId = $userId;
        $this->tokenId = $tokenId;
    }

    public function broadcastOn(): array
    {
        // NAYA: PrivateChannel ki jagah normal 'Channel' use kiya gaya hai (No Auth Error)
        return [
            new Channel('admin.logout.' . $this->userId),
        ];
    }

    public function broadcastAs()
    {
        return 'user.logged.out';
    }
}