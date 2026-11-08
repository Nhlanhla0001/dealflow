<?php

namespace App\Notifications;

use App\Models\Agreement;
use App\Models\User;
use App\Notifications\Concerns\RespectsNotificationPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TermsRejected extends Notification
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        public Agreement $agreement,
        public User $rejector,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $transaction = $this->agreement->transaction;

        return (new MailMessage)
            ->subject('DealFlow — '.$this->role().' rejected the terms')
            ->greeting('The deal was cancelled')
            ->line("{$this->role()} rejected the agreement for \"{$transaction->title}\".")
            ->line('No payment will be created for this transaction.')
            ->action('View transaction', route('transactions.show', $transaction));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $transaction = $this->agreement->transaction;

        return [
            'type' => 'terms_rejected',
            'title' => $this->role().' rejected the terms',
            'message' => "{$transaction->title} was cancelled. No payment will be created.",
            'transaction_id' => $transaction->id,
            'agreement_id' => $this->agreement->id,
            'url' => route('transactions.show', $transaction),
        ];
    }

    private function role(): string
    {
        return $this->rejector->id === $this->agreement->transaction->buyer_id
            ? 'Buyer'
            : 'Seller';
    }
}
