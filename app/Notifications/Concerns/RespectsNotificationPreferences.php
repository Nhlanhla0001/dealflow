<?php

namespace App\Notifications\Concerns;

trait RespectsNotificationPreferences
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable->notification_in_app ?? true) {
            $channels[] = 'database';
        }

        if ($notifiable->notification_email ?? true) {
            $channels[] = 'mail';
        }

        return $channels === [] ? ['database'] : $channels;
    }
}
