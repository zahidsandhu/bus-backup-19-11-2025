<?php

use App\Models\Complaint;
use App\Models\User;
use Spatie\Permission\Models\Role;

it('allows admin to view and update complaints', function (): void {
    $adminRole = Role::firstOrCreate(['name' => 'Admin']);

    $admin = User::factory()->create();
    $admin->assignRole($adminRole);

    $customer = User::factory()->create();
    $employee = User::factory()->create();

    $complaint = Complaint::create([
        'user_id' => $customer->id,
        'title' => 'Test',
        'message' => 'Issue',
        'status' => 'open',
    ]);

    $this->actingAs($admin);

    $index = $this->get(route('admin.complaints.index'));
    $index->assertOk();

    $show = $this->get(route('admin.complaints.show', $complaint));
    $show->assertOk();

    $update = $this->put(route('admin.complaints.update', $complaint), [
        'status' => 'in-progress',
        'admin_remarks' => 'We are checking this',
        'employee_id' => $employee->id,
    ]);

    $update->assertRedirect(route('admin.complaints.show', $complaint));

    $complaint->refresh();

    expect($complaint->status)->toBe('in-progress');
    expect($complaint->admin_remarks)->toBe('We are checking this');
    expect($complaint->employee_id)->toBe($employee->id);
});
