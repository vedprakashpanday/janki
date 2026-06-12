<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// 🔥 ShouldBroadcastNow lagana zaroori hai taaki instant message jaye (queue na ho)
class TaskProgressUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $taskId;
    public $logData;

    public function __construct($taskId, $logData)
    {
        $this->taskId = $taskId;
        $this->logData = $logData;
    }

    public function broadcastOn(): array
    {
        // Private channel task ki ID ke basis par banega
        return [
            new PrivateChannel('task.' . $this->taskId),
        ];
    }

    public function broadcastAs()
    {
        // Ye wo naam hai jisko hum frontend par listen karenge
        return 'message.sent';
    }
}