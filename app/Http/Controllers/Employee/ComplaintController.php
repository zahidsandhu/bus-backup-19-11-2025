<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComplaintController extends Controller
{
    public function index()
    {
        $complaints = Complaint::query()
            ->where('employee_id', Auth::id())
            ->latest()
            ->paginate(15);

        return view('employee.complaints.index', compact('complaints'));
    }

    public function show(Complaint $complaint)
    {
        abort_unless($complaint->employee_id === Auth::id(), 403);

        return view('employee.complaints.show', compact('complaint'));
    }

    public function update(Request $request, Complaint $complaint)
    {
        abort_unless($complaint->employee_id === Auth::id(), 403);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:open,in-progress,resolved,closed'],
            'employee_remarks' => ['nullable', 'string'],
        ]);

        $complaint->update([
            'status' => $validated['status'],
            'employee_remarks' => $validated['employee_remarks'] ?? null,
        ]);

        return redirect()
            ->route('employee.complaints.show', $complaint)
            ->with('success', 'Complaint updated successfully.');
    }
}
