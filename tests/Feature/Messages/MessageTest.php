<?php

namespace Tests\Feature\Messages;

use App\Models\Transaction;
use App\Models\User;
use App\Notifications\MessageReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_participants_can_exchange_messages(): void
    {
        Notification::fake();

        $transaction = Transaction::factory()->create();

        $this->actingAs($transaction->buyer)
            ->postJson(route('transactions.messages.store', $transaction), [
                'body' => 'Could you start on Monday?',
            ])
            ->assertCreated()
            ->assertJsonPath('message.body', 'Could you start on Monday?');

        $this->assertDatabaseHas('messages', [
            'transaction_id' => $transaction->id,
            'user_id' => $transaction->buyer_id,
            'body' => 'Could you start on Monday?',
        ]);

        Notification::assertSentTo($transaction->seller, MessageReceived::class);

        $this->actingAs($transaction->seller)
            ->getJson(route('transactions.messages.index', $transaction))
            ->assertOk()
            ->assertJsonPath('messages.0.body', 'Could you start on Monday?')
            ->assertJsonPath('unread', 1);

        // The second read is clean.
        $this->actingAs($transaction->seller)
            ->getJson(route('transactions.messages.index', $transaction))
            ->assertJsonPath('unread', 0);
    }

    public function test_outsiders_cannot_read_or_post_in_a_thread(): void
    {
        $transaction = Transaction::factory()->create();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->getJson(route('transactions.messages.index', $transaction))
            ->assertForbidden();

        $this->actingAs($outsider)
            ->postJson(route('transactions.messages.store', $transaction), [
                'body' => 'Let me in.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_message_body_is_validated(): void
    {
        $transaction = Transaction::factory()->create();

        $this->actingAs($transaction->buyer)
            ->postJson(route('transactions.messages.store', $transaction), [
                'body' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('body');

        $this->actingAs($transaction->buyer)
            ->postJson(route('transactions.messages.store', $transaction), [
                'body' => str_repeat('a', 3001),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('body');
    }

    public function test_unread_counts_exclude_own_messages_and_reset_when_read(): void
    {
        $transaction = Transaction::factory()->create();

        $this->actingAs($transaction->buyer)
            ->postJson(route('transactions.messages.store', $transaction), [
                'body' => 'First',
            ])
            ->assertCreated();

        $this->actingAs($transaction->buyer)
            ->postJson(route('transactions.messages.store', $transaction), [
                'body' => 'Second',
            ])
            ->assertCreated();

        $this->actingAs($transaction->seller)
            ->getJson(route('transactions.messages.index', $transaction))
            ->assertJsonPath('unread', 2);

        // Reading the thread clears the unread counter.
        $this->actingAs($transaction->seller)
            ->getJson(route('transactions.messages.index', $transaction))
            ->assertJsonPath('unread', 0);

        $this->actingAs($transaction->seller)
            ->postJson(route('transactions.messages.store', $transaction), [
                'body' => 'On it.',
            ])
            ->assertCreated();

        $this->actingAs($transaction->buyer)
            ->getJson(route('transactions.messages.index', $transaction))
            ->assertJsonPath('unread', 1)
            ->assertJsonPath('messages.2.body', 'On it.');
    }
}
