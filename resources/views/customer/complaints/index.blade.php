@extends('frontend.layouts.app')

@section('title', 'My Complaints')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show small" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                    </div>
                @endif

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="mb-0">My Complaints</h3>
                    <a href="{{ route('customer.complaints.create') }}" class="btn btn-primary btn-sm">
                        New Complaint
                    </a>
                </div>

                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($complaints as $complaint)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $complaint->title }}</div>
                                        </td>
                                        <td>
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
                                        </td>
                                        <td class="text-muted small">
                                            {{ $complaint->created_at->format('d M Y H:i') }}
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('customer.complaints.show', $complaint) }}"
                                               class="btn btn-outline-primary btn-sm">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            You have not submitted any complaints yet.
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if ($complaints->hasPages())
                        <div class="card-footer">
                            <div class="d-flex justify-content-center">
                                {{ $complaints->links() }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

