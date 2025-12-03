<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $complaints = Complaint::query()
            ->with(['user', 'employee'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('customer'), function ($query) use ($request) {
                $search = $request->string('customer');

                $query->whereHas('user', function ($userQuery) use ($search): void {
                    $userQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->orderBy(
                $request->get('sort', 'created_at'),
                $request->get('direction', 'desc')
            )
            ->paginate(15)
            ->withQueryString();

        $employees = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'Employee'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.complaints.index', compact('complaints', 'employees'));
    }

    public function show(Complaint $complaint)
    {
        $complaint->load(['user', 'employee']);

        $employees = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'Employee'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.complaints.show', compact('complaint', 'employees'));
    }

    public function update(Request $request, Complaint $complaint)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:open,in-progress,resolved,closed'],
            'admin_remarks' => ['nullable', 'string'],
            'employee_id' => ['nullable', 'exists:users,id'],
        ]);

        $complaint->update([
            'status' => $validated['status'],
            'admin_remarks' => $validated['admin_remarks'] ?? null,
            'employee_id' => $validated['employee_id'] ?? null,
        ]);

        return redirect()
            ->route('admin.complaints.show', $complaint)
            ->with('success', 'Complaint updated successfully.');
    }

    public function destroy(Complaint $complaint)
    {
        $complaint->delete();

        return redirect()
            ->route('admin.complaints.index')
            ->with('success', 'Complaint deleted successfully.');
    }
}
