<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ComplaintController extends Controller
{
    public function index()
    {
        $complaints = Complaint::query()
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        return view('customer.complaints.index', compact('complaints'));
    }

    public function create()
    {
        return view('customer.complaints.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'attachment' => ['nullable', 'file', 'max:2048'],
        ]);

        $attachmentPath = null;

        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('complaints', 'public');
        }

        Complaint::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'message' => $validated['message'],
            'attachment' => $attachmentPath,
            'status' => 'open',
        ]);

        return redirect()
            ->route('customer.complaints.index')
            ->with('success', 'Complaint submitted successfully.');
    }

    public function show(Complaint $complaint)
    {
        abort_unless($complaint->user_id === Auth::id(), 403);

        return view('customer.complaints.show', compact('complaint'));
    }
}
