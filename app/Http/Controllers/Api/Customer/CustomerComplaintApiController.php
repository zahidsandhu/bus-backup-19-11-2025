<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerComplaintApiController extends Controller
{
    /**
     * Get all complaints of logged-in customer
     */
    public function index(Request $request): JsonResponse
    {
        $complaints = Complaint::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $complaints,
        ]);
    }

    /**
     * Create a new complaint
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'     => 'required|string|max:255',
            'message'   => 'required|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
        ]);

        // Upload file if exists
        $filePath = null;
        if ($request->hasFile('attachment')) {
            $filePath = $request->file('attachment')->store('complaints', 'public');
        }

        $complaint = Complaint::create([
            'user_id'   => $request->user()->id,
            'title'     => $request->title,
            'message'   => $request->message,
            'attachment'=> $filePath,
            'status'    => 'Pending',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'complaint' => $complaint,
            ],
        ]);
    }

    /**
     * Show a single complaint of logged-in customer
     */
    public function show(Request $request, $id): JsonResponse
    {
        $complaint = Complaint::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'complaint' => $complaint,
            ],
        ]);
    }
}
