<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'buyer',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('onboarding.show', absolute: false));
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'buyer',
        ]);
    }

    public function test_registration_requires_a_role_choice()
    {
        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'No Role',
            'email' => 'norole@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'norole@example.com']);
    }

    public function test_new_users_can_register_as_a_seller()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test Seller',
            'email' => 'seller@example.com',
            'role' => 'seller',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('onboarding.show', absolute: false));
        $this->assertDatabaseHas('users', [
            'email' => 'seller@example.com',
            'role' => 'seller',
        ]);
    }
}
