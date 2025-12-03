@extends('admin.layouts.app')

@section('content')
    <div class="container-xxl py-4">
        <div class="mb-3">
            <a href="{{ route('admin.complaints.index') }}" class="text-decoration-none small">&larr; Back to complaints</a>
        </div>

        @if (session('success'))
            <div class="alert alert-success small">
                {{ session('success') }}
            </div>
        @endif

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h1 class="h5 mb-0">{{ $complaint->title }}</h1>
                            <span class="badge bg-secondary">
                                {{ ucfirst(str_replace('-', ' ', $complaint->status)) }}
                            </span>
                        </div>
                        <div class="text-muted small mb-3">
                            Submitted {{ $complaint->created_at->format('d M Y H:i') }} by
                            {{ $complaint->user->name }} ({{ $complaint->user->email }})
                        </div>

                        <div class="border-top pt-3 small">
                            <pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;">{{ $complaint->message }}</pre>
                        </div>

                        @if ($complaint->attachment)
                            <div class="border-top pt-3 mt-3 small">
                                <span class="fw-semibold">Attachment:</span>
                                <a href="{{ asset('storage/'.$complaint->attachment) }}" target="_blank" class="ms-1">
                                    View file
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                @if ($complaint->admin_remarks || $complaint->employee_remarks)
                    <div class="card">
                        <div class="card-header py-2">
                            <h2 class="h6 mb-0">Remarks</h2>
                        </div>
                        <div class="card-body small">
                            @if ($complaint->admin_remarks)
                                <div class="mb-3">
                                    <div class="fw-semibold mb-1">Admin Remarks</div>
                                    <pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;">{{ $complaint->admin_remarks }}</pre>
                                </div>
                            @endif
                            @if ($complaint->employee_remarks)
                                <div>
                                    <div class="fw-semibold mb-1">Employee Remarks</div>
                                    <pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;">{{ $complaint->employee_remarks }}</pre>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header py-2">
                        <h2 class="h6 mb-0">Update Complaint</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.complaints.update', $complaint) }}" class="small">
                            @csrf
                            @method('PUT')

                            <div class="mb-2">
                                <label class="form-label form-label-sm">Status</label>
                                <select name="status" class="form-select form-select-sm">
                                    @foreach (['open','in-progress','resolved','closed'] as $status)
                                        <option value="{{ $status }}" @selected($complaint->status === $status)>
                                            {{ ucfirst(str_replace('-', ' ', $status)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-2">
                                <label class="form-label form-label-sm">Assign to Employee</label>
                                <select name="employee_id" class="form-select form-select-sm">
                                    <option value="">Unassigned</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}" @selected($complaint->employee_id === $employee->id)>
                                            {{ $employee->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-2">
                                <label class="form-label form-label-sm">Admin Remarks</label>
                                <textarea name="admin_remarks" rows="3"
                                          class="form-control form-control-sm">{{ old('admin_remarks', $complaint->admin_remarks) }}</textarea>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    Save Changes
                                </button>

                                <form method="POST" action="{{ route('admin.complaints.destroy', $complaint) }}"
                                      onsubmit="return confirm('Delete this complaint?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


