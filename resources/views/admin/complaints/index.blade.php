@extends('admin.layouts.app')

@section('content')
    <div class="container-xxl py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Complaints</h1>
        </div>

        @if (session('success'))
            <div class="alert alert-success small">
                {{ session('success') }}
            </div>
        @endif

        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3">
                <input type="text" name="customer" value="{{ request('customer') }}" class="form-control form-control-sm"
                       placeholder="Search customer name or email">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    @foreach (['open','in-progress','resolved','closed'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>
                            {{ ucfirst(str_replace('-', ' ', $status)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-sm btn-primary" type="submit">Filter</button>
                <a href="{{ route('admin.complaints.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Customer</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Assigned To</th>
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
                            <td>
                                {{ $complaint->employee?->name ?? 'Unassigned' }}
                            </td>
                            <td class="text-muted small">
                                {{ $complaint->created_at->format('d M Y H:i') }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.complaints.show', $complaint) }}"
                                   class="btn btn-xs btn-outline-primary">
                                    View / Manage
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No complaints found.
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


