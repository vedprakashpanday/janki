<?php

// namespace App\Events;

// use Illuminate\Broadcasting\Channel;
// use Illuminate\Broadcasting\InteractsWithSockets;
// use Illuminate\Broadcasting\PrivateChannel;
// use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
// use Illuminate\Foundation\Events\Dispatchable;
// use Illuminate\Queue\SerializesModels;

// class GlobalUserNotification implements ShouldBroadcastNow
// {
//     // use Dispatchable, InteractsWithSockets, SerializesModels;

//     // public $channelName;
//     // public $taskId;
//     // public $taskTitle;
//     // public $logData;

//     // public function __construct($channelName, $taskId, $taskTitle, $logData)
//     // {
//     //     $this->channelName = $channelName;
//     //     $this->taskId = $taskId;
//     //     $this->taskTitle = $taskTitle;
//     //     $this->logData = $logData;
//     // }

//     // public function broadcastOn(): array
//     // {
//     //     // Ye specific user ke private channel par hi jayega, 50 request nahi aayengi
//     //     return [
//     //         new PrivateChannel($this->channelName),
//     //     ];
//     // }

//     // public function broadcastAs()
//     // {
//     //     // Event name for frontend listening
//     //     return 'notification.received';
//     // }
// }