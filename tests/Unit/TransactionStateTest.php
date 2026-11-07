<?php

namespace Tests\Unit;

use App\Enums\TransactionState;
use App\Models\Transaction;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TransactionStateTest extends TestCase
{
    public function test_draft_can_move_to_negotiating(): void
    {
        $this->assertTrue(
            TransactionState::Draft->canTransitionTo(TransactionState::Negotiating)
        );
    }

    public function test_draft_cannot_jump_to_paid(): void
    {
        $this->assertFalse(
            TransactionState::Draft->canTransitionTo(TransactionState::Paid)
        );
    }

    public function test_terminal_states_have_no_transitions(): void
    {
        $this->assertTrue(TransactionState::Paid->isTerminal());
        $this->assertSame([], TransactionState::Paid->allowedTransitions());
    }

    public function test_an_invalid_transition_throws_and_preserves_state(): void
    {
        $transaction = new Transaction(['state' => TransactionState::Draft]);

        try {
            $transaction->transitionTo(TransactionState::Paid);
            $this->fail('Expected an invalid state transition exception.');
        } catch (InvalidArgumentException) {
            // Expected.
        }

        $this->assertSame(TransactionState::Draft, $transaction->state);
    }
}
