<?php

namespace App\Notifications;

use App\Models\Message;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\Concerns\RespectsNotificationPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class MessageReceived extends Notification
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        public Transaction $transaction,
        public User $sender,
        public Message $message,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('DealFlow - new message')
            ->greeting("New message from {$this->sender->name}")
            ->line(Str::limit($this->message->body, 300))
            ->action('Reply', route('transactions.show', $this->transaction))
            ->line('You can reply from the transaction page.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'message',
            'title' => 'New message from '.$this->sender->name,
            'message' => Str::limit($this->message->body, 120),
            'transaction_id' => $this->transaction->id,
            'url' => route('transactions.show', $this->transaction),
        ];
    }
}
