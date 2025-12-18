<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
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

    $response->assertOk()->assertJson([
        'success' => true,
    ]);

    Notification::assertSentTo($user, ResetPassword::class);
});

it('validates the email when requesting a password reset', function () {
    $response = postJson('/api/customer/auth/forgot-password', [
        'email' => 'not-an-email',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['email']);
});
