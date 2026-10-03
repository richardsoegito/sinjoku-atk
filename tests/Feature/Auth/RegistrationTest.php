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

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'John Doe',
            'username' => 'john-doe',
            'email' => 'test@example.com',
            'timezone' => 'Asia/Jakarta',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
        $this->assertSame('Asia/Jakarta', auth()->user()->timezone);
        $this->assertSame('john-doe', auth()->user()->username);
    }

    public function test_registration_validation_errors_are_returned(): void
    {
        $response = $this->from(route('register'))
            ->post(route('register.store'), [
                'name' => '',
                'username' => 'invalid name',
                'email' => 'not-an-email',
                'timezone' => 'invalid',
                'password' => 'short',
                'password_confirmation' => 'different',
            ]);

        $response->assertRedirect(route('register', absolute: false))
            ->assertSessionHasErrors(['name', 'username', 'email', 'timezone', 'password']);
    }
}
