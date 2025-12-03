@extends('frontend.layouts.app')

@section('title', 'Complaint Details')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">

                <div class="mb-3">
                    <a href="{{ route('customer.complaints.index') }}" class="btn btn-outline-secondary btn-sm">
                        &larr; Back to Complaints
                    </a>
                </div>

                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">{{ $complaint->title }}</h3>
                        @php
                            $status = $complaint->status;
                            $badgeClass = 'bg-secondary';
                            if ($status === 'open') {
                                $badgeClass = 'bg-warning text-dark';
                            } elseif ($status === 'in-progress') {
                                $badgeClass = 'bg-info text-dark';
                            } elseif ($status === 'resolved') {
                                $badgeClass = 'bg-success';
                            } elseif ($status === 'closed') {
                                $badgeClass = 'bg-dark';
                            }
                        @endphp
                        <span class="badge {{ $badgeClass }}">
                            {{ ucfirst(str_replace('-', ' ', $status)) }}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="text-muted small mb-3">
                            Submitted on {{ $complaint->created_at->format('d M Y H:i') }}
                        </div>

                        <div class="border-top pt-3 small">
                            <p class="mb-0" style="white-space: pre-wrap;">{{ $complaint->message }}</p>
                        </div>

                        @if ($complaint->attachment)
                            <div class="border-top pt-3 mt-3 small">
                                <span class="fw-semibold">Attachment:</span>
                                <a href="{{ asset('storage/'.$complaint->attachment) }}" target="_blank"
                                   class="ms-1">
                                    View file
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                @if ($complaint->admin_remarks || $complaint->employee_remarks)
                    <div class="card">
                        <div class="card-header py-2">
                            <h5 class="mb-0 h6">Remarks</h5>
                        </div>
                        <div class="card-body small">
                            @if ($complaint->admin_remarks)
                                <div class="mb-3">
                                    <div class="fw-semibold mb-1">Admin Remarks</div>
                                    <p class="mb-0" style="white-space: pre-wrap;">
                                        {{ $complaint->admin_remarks }}
                                    </p>
                                </div>
                            @endif

                            @if ($complaint->employee_remarks)
                                <div>
                                    <div class="fw-semibold mb-1">Employee Remarks</div>
                                    <p class="mb-0" style="white-space: pre-wrap;">
                                        {{ $complaint->employee_remarks }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
@endsection

