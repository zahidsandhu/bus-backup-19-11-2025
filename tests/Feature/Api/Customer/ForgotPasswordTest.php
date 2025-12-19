<?php

use App\Models\User;
use App\Models\PasswordResetOtp;
use App\Notifications\ForgotPasswordOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Hash;

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

it('resets the password with a valid otp', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);

    $otp = '123456';

    PasswordResetOtp::create([
        'user_id' => $user->id,
        'otp' => $otp,
    ]);

    $response = postJson('/api/customer/auth/reset-password', [
        'otp' => $otp,
        'password' => 'new-password-123',
    ]);

    $response->assertOk()->assertJson([
        'success' => true,
    ]);

    expect(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue();
});

it('returns an error when the otp is invalid', function () {
    $user = User::factory()->create();

    $response = postJson('/api/customer/auth/reset-password', [
        'otp' => '000000',
        'password' => 'new-password-123',
    ]);

    $response->assertStatus(422)->assertJson([
        'success' => false,
    ]);
});
