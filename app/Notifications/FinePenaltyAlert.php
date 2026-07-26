<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class FinePenaltyAlert extends Notification
{
    use Queueable;

    public $fineId;
    public $amountText;
    public $userType;

    public function __construct($fineId, $amountText, $userType)
    {
        $this->fineId = $fineId;
        $this->amountText = $amountText;
        $this->userType = $userType;
    }

    public function via($notifiable)
    {
        // Database for saving in table, broadcast for live Echo bell alert
        return ['database', 'broadcast']; 
    }

    public function toArray($notifiable)
    {
        // Employee/Member URL routing
        $portalPrefix = strtolower($this->userType) === 'member' ? 'customer' : 'employee';

        return [
            'title' => 'Fine / Penalty Notice',
            'message' => 'A new charge of ' . $this->amountText . ' has been applied to your profile.',
            'url' => '/' . $portalPrefix . '/my-penalties?view_id=' . $this->fineId,
            'icon' => 'fa-file-invoice-dollar',
            'colorClass' => 'text-danger'
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}