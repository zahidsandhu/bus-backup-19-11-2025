<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerComplaintApiController extends Controller
{
    /**
     * Get all complaints of logged-in customer
     */
    public function index(Request $request): JsonResponse
    {
        $complaints = Complaint::query()
            ->with('attachments')
            ->where('user_id', $request->user()->id)
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
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            // Backwards compatible single attachment
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf'],
            // New multiple attachments support
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf'],
        ]);

        $userId = $request->user()->id;

        $primaryAttachmentPath = null;
        $storedAttachments = [];

        // Backwards compatible single file upload (if client still sends `attachment`)
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $primaryAttachmentPath = $file->store('complaints', 'public');

            $storedAttachments[] = [
                'path' => $primaryAttachmentPath,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ];
        }

        // New multiple files upload via `attachments[]`
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('complaints', 'public');

                if ($primaryAttachmentPath === null) {
                    $primaryAttachmentPath = $path;
                }

                $storedAttachments[] = [
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ];
            }
        }

        $complaint = Complaint::create([
            'user_id' => $userId,
            'title' => $validated['title'],
            'message' => $validated['message'],
            // keep first attachment path for existing web views / logic
            'attachment' => $primaryAttachmentPath,
            'status' => 'Pending',
        ]);

        if ($storedAttachments !== []) {
            foreach ($storedAttachments as $attachmentData) {
                ComplaintAttachment::create([
                    'complaint_id' => $complaint->id,
                    'path' => $attachmentData['path'],
                    'original_name' => $attachmentData['original_name'],
                    'mime_type' => $attachmentData['mime_type'],
                    'size' => $attachmentData['size'],
                ]);
            }
        }

        $complaint->load('attachments');

        return response()->json([
            'success' => true,
            'data' => [
                'complaint' => $complaint,
            ],
        ], 201);
    }

    /**
     * Show a single complaint of logged-in customer
     */
    public function show(Request $request, $id): JsonResponse
    {
        $complaint = Complaint::query()
            ->with('attachments')
            ->where('id', $id)
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
