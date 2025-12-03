@extends('admin.layouts.app')

@section('content')
    <div class="container-xxl py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Assigned Complaints</h1>
        </div>

        @if (session('success'))
            <div class="alert alert-success small">
                {{ session('success') }}
            </div>
        @endif

        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Customer</th>
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
                                <div class="fw-semibold">{{ $complaint->user->name }}</div>
                                <div class="text-muted small">{{ $complaint->user->email }}</div>
                            </td>
                            <td>{{ $complaint->title }}</td>
                            <td>
                                <span class="badge bg-light text-dark">
                                    {{ ucfirst(str_replace('-', ' ', $complaint->status)) }}
                                </span>
                            </td>
                            <td class="text-muted small">
                                {{ $complaint->created_at->format('d M Y H:i') }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('employee.complaints.show', $complaint) }}"
                                   class="btn btn-xs btn-outline-primary">
                                    View / Update
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No complaints assigned to you.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($complaints->hasPages())
                <div class="card-footer">
                    {{ $complaints->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection


