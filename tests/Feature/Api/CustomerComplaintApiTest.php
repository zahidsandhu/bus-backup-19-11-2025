<?php

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

it('allows a customer to create a complaint with multiple attachments via api', function (): void {
    Storage::fake('public');

    $customer = User::factory()->create();

    actingAs($customer, 'sanctum');

    $fileOne = UploadedFile::fake()->image('one.jpg');
    $fileTwo = UploadedFile::fake()->image('two.png');

    $response = postJson('/api/customer/complaints', [
        'title' => 'API Complaint',
        'message' => 'Something went wrong via API',
        'attachments' => [
            $fileOne,
            $fileTwo,
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'complaint' => [
                    'id',
                    'title',
                    'message',
                    'attachments',
                ],
            ],
        ]);

    /** @var Complaint $complaint */
    $complaint = Complaint::with('attachments')->first();

    expect($complaint)->not()->toBeNull();
    expect($complaint->attachments)->toHaveCount(2);

    foreach ($complaint->attachments as $attachment) {
        Storage::disk('public')->assertExists($attachment->path);
    }
});

it('returns complaints with attachments for the logged in customer', function (): void {
    Storage::fake('public');

    $customer = User::factory()->create();

    actingAs($customer, 'sanctum');

    $file = UploadedFile::fake()->image('one.jpg');

    postJson('/api/customer/complaints', [
        'title' => 'API Complaint 2',
        'message' => 'Another issue',
        'attachments' => [$file],
    ]);

    $response = getJson('/api/customer/complaints');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'title',
                    'attachments',
                ],
            ],
        ]);

    $complaints = $response->json('data');

    expect($complaints)->toBeArray();
    expect($complaints[0]['attachments'])->not()->toBeEmpty();
});
