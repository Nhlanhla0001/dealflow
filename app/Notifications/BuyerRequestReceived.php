<?php

namespace App\Notifications;

use App\Models\Transaction;
use App\Notifications\Concerns\RespectsNotificationPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BuyerRequestReceived extends Notification
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(public Transaction $transaction) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('DealFlow AI — new buyer request')
            ->greeting('New request for your service')
            ->line("A buyer created a request for \"{$this->transaction->title}\".")
            ->line('Review the request, configure your agent, and respond with an offer.')
            ->action('Open the request', route('transactions.show', $this->transaction))
            ->line('The final terms still require your explicit approval.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'buyer_request',
            'title' => 'New buyer request',
            'message' => $this->transaction->title,
            'transaction_id' => $this->transaction->id,
            'url' => route('transactions.show', $this->transaction),
        ];
    }
}
