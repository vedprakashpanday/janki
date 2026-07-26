<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage; // 🔥 Ye zaroori hai

class GreetingNotification extends Notification
{
    use Queueable;

    public $title;
    public $message;
    public $icon;
    public $colorClass;
    public $url;

    public function __construct($title, $message, $icon = 'fa-solid fa-gift', $colorClass = 'text-success', $url = '#')
    {
        $this->title = $title;
        $this->message = $message;
        $this->icon = $icon;
        $this->colorClass = $colorClass;
        $this->url = $url;
    }

    public function via($notifiable)
    {
        // 🔥 Yahan 'broadcasting' add kiya gaya hai live alert ke liye
        return ['database', 'broadcasting'];
    }

    public function toArray($notifiable)
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'icon' => $this->icon,
            'colorClass' => $this->colorClass,
            'url' => $this->url
        ];
    }

    // 🔥 Ye naya method WebSocket/Pusher par live data bhejega
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