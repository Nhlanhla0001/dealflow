<?php

namespace App\Notifications;

use App\Models\Agreement;
use App\Models\User;
use App\Notifications\Concerns\RespectsNotificationPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CounterpartyApprovedTerms extends Notification
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        public Agreement $agreement,
        public User $approver,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $transaction = $this->agreement->transaction;

        return (new MailMessage)
            ->subject('DealFlow AI — '.$this->role().' approved the terms')
            ->greeting('Your approval is still needed')
            ->line("{$this->role()} approved the agreement for \"{$transaction->title}\".")
            ->line('Price: '.$this->price().' '.$this->agreement->currency)
            ->line("Delivery: {$this->agreement->delivery_days} days")
            ->action('Review and approve', route('transactions.show', $transaction))
            ->line('Payment starts only after both parties approve the same version.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $transaction = $this->agreement->transaction;

        return [
            'type' => 'counterparty_approved',
            'title' => $this->role().' approved the terms',
            'message' => "{$transaction->title} — your approval is still needed.",
            'transaction_id' => $transaction->id,
            'agreement_id' => $this->agreement->id,
            'url' => route('transactions.show', $transaction),
        ];
    }

    private function role(): string
    {
        return $this->approver->id === $this->agreement->transaction->buyer_id
            ? 'Buyer'
            : 'Seller';
    }

    private function price(): string
    {
        return number_format($this->agreement->price_minor / 100, 2);
    }
}
