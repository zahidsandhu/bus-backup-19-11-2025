@extends('layouts.app')

@section('content')
    <div class="max-w-3xl mx-auto py-8">
        <div class="mb-4">
            <a href="{{ route('customer.complaints.index') }}" class="text-sm text-blue-600 hover:underline">&larr; Back to complaints</a>
        </div>

        <div class="bg-white shadow rounded-md p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold">{{ $complaint->title }}</h1>
                <span class="inline-flex px-3 py-1 rounded-full text-xs
                    @if($complaint->status === 'open') bg-yellow-100 text-yellow-800
                    @elseif($complaint->status === 'in-progress') bg-blue-100 text-blue-800
                    @elseif($complaint->status === 'resolved') bg-green-100 text-green-800
                    @else bg-gray-100 text-gray-800 @endif">
                    {{ ucfirst(str_replace('-', ' ', $complaint->status)) }}
                </span>
            </div>

            <div class="text-sm text-gray-500">
                Submitted on {{ $complaint->created_at->format('d M Y H:i') }}
            </div>

            <div class="border-t pt-4 text-sm text-gray-800 whitespace-pre-line">
                {{ $complaint->message }}
            </div>

            @if ($complaint->attachment)
                <div class="pt-4 border-t text-sm">
                    <span class="font-medium text-gray-700">Attachment:</span>
                    <a href="{{ asset('storage/'.$complaint->attachment) }}" target="_blank"
                       class="ml-2 text-blue-600 hover:underline">
                        View file
                    </a>
                </div>
            @endif

            @if ($complaint->admin_remarks || $complaint->employee_remarks)
                <div class="pt-4 border-t space-y-3 text-sm">
                    @if ($complaint->admin_remarks)
                        <div>
                            <div class="font-medium text-gray-700 mb-1">Admin Remarks</div>
                            <div class="text-gray-800 whitespace-pre-line">
                                {{ $complaint->admin_remarks }}
                            </div>
                        </div>
                    @endif

                    @if ($complaint->employee_remarks)
                        <div>
                            <div class="font-medium text-gray-700 mb-1">Employee Remarks</div>
                            <div class="text-gray-800 whitespace-pre-line">
                                {{ $complaint->employee_remarks }}
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection


