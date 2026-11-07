<?php

namespace App\Notifications;

use App\Models\Agreement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TermsReadyForApproval extends Notification
{
    use Queueable;

    public function __construct(public Agreement $agreement) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $transaction = $this->agreement->transaction;

        return (new MailMessage)
            ->subject('DealFlow AI — terms ready for your approval')
            ->greeting('Terms ready for review')
            ->line("The AI negotiation for \"{$transaction->title}\" produced terms ready for your approval.")
            ->line('Price: '.$this->price().' '.$this->agreement->currency)
            ->line("Delivery: {$this->agreement->delivery_days} days")
            ->action('Review and approve', route('transactions.show', $transaction))
            ->line('You can approve or reject the terms. Payment only starts after both parties approve.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $transaction = $this->agreement->transaction;

        return [
            'type' => 'terms_ready',
            'title' => 'Terms ready for your approval',
            'message' => "{$transaction->title} — {$this->price()} {$this->agreement->currency} / {$this->agreement->delivery_days} days",
            'transaction_id' => $transaction->id,
            'agreement_id' => $this->agreement->id,
            'url' => route('transactions.show', $transaction),
        ];
    }

    private function price(): string
    {
        return number_format($this->agreement->price_minor / 100, 2);
    }
}
