<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\User;
use App\Notifications\TermsReadyForApproval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_disable_email_notifications(): void
    {
        $user = User::factory()->create([
            'notification_email' => false,
            'notification_in_app' => true,
        ]);

        $channels = (new TermsReadyForApproval(new Agreement))->via($user);

        $this->assertSame(['database'], $channels);
    }

    public function test_users_can_disable_in_app_notifications(): void
    {
        $user = User::factory()->create([
            'notification_email' => true,
            'notification_in_app' => false,
        ]);

        $channels = (new TermsReadyForApproval(new Agreement))->via($user);

        $this->assertSame(['mail'], $channels);
    }
}
