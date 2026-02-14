<?php

namespace Tests\Feature;

use App\Livewire\Auth\ForgotPinPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ForgotPinTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_pin_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-pin');

        $response->assertStatus(200);
    }

    public function test_existing_phone_generates_pin_reset_link(): void
    {
        $user = User::factory()->create([
            'phone' => '3312345678',
        ]);

        Livewire::test(ForgotPinPage::class)
            ->set('phone', $user->phone)
            ->call('sendResetLink')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('user_pin_setup_tokens', [
            'user_id' => $user->id,
        ]);
    }

    public function test_unknown_phone_returns_validation_error(): void
    {
        Livewire::test(ForgotPinPage::class)
            ->set('phone', '3310001112')
            ->call('sendResetLink')
            ->assertHasErrors(['phone']);

        $this->assertDatabaseCount('user_pin_setup_tokens', 0);
    }
}
