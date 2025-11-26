<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\postJson;
use function Pest\Laravel\assertDatabaseHas;

it('registers a new customer and returns token', function () {
    $payload = [
        'name' => 'Test User',
        'email' => 'customer@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ];

    $response = postJson('/api/customer/auth/signup', $payload);

    $response->assertCreated()
        ->assertJson([
            'success' => true,
            'data' => [
                'user' => [
                    'email' => 'customer@example.com',
                ],
            ],
        ])
        ->assertJsonStructure([
            'data' => [
                'token',
                'user' => ['id', 'name', 'email'],
            ],
        ]);

    assertDatabaseHas('users', [
        'email' => 'customer@example.com',
    ]);
});

it('logs in existing customer and issues token', function () {
    $user = User::factory()->create([
        'password' => Hash::make('Password123!'),
    ]);

    $response = postJson('/api/customer/auth/login', [
        'email' => $user->email,
        'password' => 'Password123!',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'data' => [
                'token',
                'user' => ['id', 'email'],
            ],
        ]);

    assertAuthenticated();
});


