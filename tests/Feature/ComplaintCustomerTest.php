<?php

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('allows a customer to create a complaint and view it', function (): void {
    Storage::fake('public');

    $customer = User::factory()->create();
    $this->actingAs($customer);

    $file = UploadedFile::fake()->create('screenshot.png', 100);

    $response = $this->post(route('customer.complaints.store'), [
        'title' => 'Test Complaint',
        'message' => 'Something went wrong',
        'attachment' => $file,
    ]);

    $response->assertRedirect(route('customer.complaints.index'));

    $complaint = Complaint::first();

    expect($complaint)->not()->toBeNull();
    expect($complaint->user_id)->toBe($customer->id);
    expect($complaint->title)->toBe('Test Complaint');

    if ($complaint->attachment) {
        Storage::disk('public')->assertExists($complaint->attachment);
    }

    $show = $this->get(route('customer.complaints.show', $complaint));
    $show->assertOk();
});
