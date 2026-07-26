<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class PromotionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $title;
    public $message;
    public $icon;
    public $colorClass;
    public $url;
    public $effectiveDate; // 🔥 Naya variable

    // 🔥 Constructor me $effectiveDate add kiya (default null)
    public function __construct($title, $message, $icon = 'fa-solid fa-trophy', $colorClass = 'text-warning', $url = '#', $effectiveDate = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->icon = $icon;
        $this->colorClass = $colorClass;
        $this->url = $url;
        $this->effectiveDate = $effectiveDate; // 🔥 Assign kiya
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable)
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'icon' => $this->icon,
            'colorClass' => $this->colorClass,
            'url' => $this->url,
            'effective_date' => $this->effectiveDate // 🔥 Data me save hoga
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'title' => $this->title,
            'message' => $this->message,
            'icon' => $this->icon,
            'colorClass' => $this->colorClass,
            'url' => $this->url
        ]);
    }
}