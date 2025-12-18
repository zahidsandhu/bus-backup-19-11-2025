<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('streams a CSV file for employee bookings export', function () {
    Permission::firstOrCreate([
        'name' => 'access admin panel',
        'guard_name' => 'web',
    ]);

    Permission::firstOrCreate([
        'name' => 'view terminal reports',
        'guard_name' => 'web',
    ]);

    /** @var \App\Models\User $user */
    $user = User::factory()->create();
    $user->givePermissionTo(['access admin panel', 'view terminal reports']);

    actingAs($user);

    $response = get(route('admin.terminal-reports.employee-bookings-export', [
        'start_date' => now()->format('Y-m-d'),
        'end_date' => now()->format('Y-m-d'),
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv');
    $response->assertHeader('Content-Disposition');

    $content = $response->streamedContent();

    expect($content)->toContain('Booking Number');
    expect($content)->toContain('Currency');
});


