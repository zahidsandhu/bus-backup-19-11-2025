<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

it('requires authentication to create a booking', function () {
    postJson('/api/customer/bookings', [])
        ->assertUnauthorized();
});

it('validates booking payload for authenticated customers', function () {
    Sanctum::actingAs(User::factory()->create());

    postJson('/api/customer/bookings', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'trip_id',
            'from_terminal_id',
            'to_terminal_id',
            'seat_numbers',
            'seats_data',
            'passengers',
            'total_fare',
            'final_amount',
        ]);
});

it('validates required filters when searching trips', function () {
    getJson('/api/customer/trips')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['from_terminal_id', 'to_terminal_id', 'date']);
});

it('validates trip details input', function () {
    getJson('/api/customer/trips/details')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['trip_id', 'from_terminal_id', 'to_terminal_id']);
});

