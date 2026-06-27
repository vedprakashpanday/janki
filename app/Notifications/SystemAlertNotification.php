<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;

// 🔥 FIX 1: implements ShouldBroadcastNow hata diya gaya hai. Notifications ko iski zaroorat nahi hoti.
class SystemAlertNotification extends Notification
{
    use Queueable;

    public $title;
    public $message;
    public $url;
    public $icon;
    public $colorClass;

    // 🔥 FIX 2: User ID ko dynamically save karne ke liye ek naya variable
    protected $targetId;

    public function __construct($title, $message, $url, $icon = 'fa-bell', $colorClass = 'text-primary')
    {
        $this->title = $title;
        $this->message = $message;
        $this->url = $url;
        $this->icon = $icon;
        $this->colorClass = $colorClass;
    }

    public function via(object $notifiable): array
    {
        // 🔥 SMART TRICK: via() ke andar se user ki ID pakad kar class me save kar li
        $this->targetId = $notifiable->id; 

        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'icon' => $this->icon,
            'colorClass' => $this->colorClass,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'icon' => $this->icon,
            'colorClass' => $this->colorClass,
            'created_at' => now()->toDateTimeString()
        ]);
    }

    public function broadcastAs(): string
    {
        return 'notification.received';
    }

    // 🔥 FIX 3: Ab yahan koi parameter (object $notifiable) nahi hai, isliye Intelephense khush rahega!
    public function broadcastOn(): array
    {
        // Yahan par humne wahi $targetId use kiya hai jo via() me save kiya tha
        return [
            new PrivateChannel("global.user.admin.{$this->targetId}"),
            new PrivateChannel("global.user.employee.{$this->targetId}")
        ];
    }
}