<?php

use App\Models\Complaint;
use App\Models\User;
use Spatie\Permission\Models\Role;

it('allows employee to see and update assigned complaints', function (): void {
    $employeeRole = Role::firstOrCreate(['name' => 'Employee']);

    $employee = User::factory()->create();
    $employee->assignRole($employeeRole);

    $customer = User::factory()->create();

    $complaint = Complaint::create([
        'user_id' => $customer->id,
        'employee_id' => $employee->id,
        'title' => 'Customer issue',
        'message' => 'Details',
        'status' => 'open',
    ]);

    $this->actingAs($employee);

    $index = $this->get(route('employee.complaints.index'));
    $index->assertOk();

    $show = $this->get(route('employee.complaints.show', $complaint));
    $show->assertOk();

    $update = $this->put(route('employee.complaints.update', $complaint), [
        'status' => 'resolved',
        'employee_remarks' => 'Fixed',
    ]);

    $update->assertRedirect(route('employee.complaints.show', $complaint));

    $complaint->refresh();

    expect($complaint->status)->toBe('resolved');
    expect($complaint->employee_remarks)->toBe('Fixed');
});
