<?php

use App\Models\User;
use App\Notifications\ForgotPasswordOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

it('sends a password reset link to an existing customer', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'customer@example.com',
    ]);

    $response = postJson('/api/customer/auth/forgot-password', [
        'email' => $user->email,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'data' => [
                'otp',
                'email_sent',
            ],
        ]);

    Notification::assertSentTo(
        $user,
        ForgotPasswordOtp::class,
        function (ForgotPasswordOtp $notification) {
            expect($notification->otp)->toMatch('/^[0-9]{6}$/');

            return true;
        }
    );

    $this->assertDatabaseHas('password_reset_otps', [
        'user_id' => $user->id,
    ]);
});

it('validates the email when requesting a password reset', function () {
    $response = postJson('/api/customer/auth/forgot-password', [
        'email' => 'not-an-email',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['email']);
});

it('returns an error when the email does not exist', function () {
    $response = postJson('/api/customer/auth/forgot-password', [
        'email' => 'missing@example.com',
    ]);

    $response->assertStatus(422)->assertJson([
        'success' => false,
    ]);
});
