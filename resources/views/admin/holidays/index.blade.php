@extends('admin.layouts.app')

@section('title', 'Holiday Management')

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-sm-0">
                                <i class="bx bx-calendar-x me-2"></i>Holidays
                            </h4>
                            <p class="text-muted mb-0 mt-1">Manage holiday periods when online bookings are closed.</p>
                        </div>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Holidays</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-calendar-exclamation me-2"></i>Holiday Periods
                            </h5>
                            <a href="{{ route('admin.holidays.create') }}" class="btn btn-primary btn-sm">
                                <i class="bx bx-plus me-1"></i>Add Holiday
                            </a>
                        </div>
                        <div class="card-body">
                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            @if ($holidays->isEmpty())
                                <div class="text-center py-5 text-muted">
                                    <i class="bx bx-calendar-x display-4 mb-3"></i>
                                    <p class="mb-0">No holidays configured yet.</p>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                                <th>Reason</th>
                                                <th>Status</th>
                                                <th>Created At</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($holidays as $holiday)
                                                <tr>
                                                    <td>{{ $loop->iteration + ($holidays->currentPage() - 1) * $holidays->perPage() }}</td>
                                                    <td>{{ $holiday->start_date?->format('Y-m-d') }}</td>
                                                    <td>{{ $holiday->end_date?->format('Y-m-d') }}</td>
                                                    <td>{{ $holiday->reason ?? '-' }}</td>
                                                    <td>
                                                        @if ($holiday->is_active)
                                                            <span class="badge bg-success">Active</span>
                                                        @else
                                                            <span class="badge bg-secondary">Inactive</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $holiday->created_at?->format('Y-m-d H:i') }}</td>
                                                    <td class="text-end">
                                                        <a href="{{ route('admin.holidays.edit', $holiday) }}"
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="bx bx-edit-alt"></i>
                                                        </a>
                                                        <form action="{{ route('admin.holidays.destroy', $holiday) }}"
                                                              method="POST"
                                                              class="d-inline-block"
                                                              onsubmit="return confirm('Are you sure you want to delete this holiday?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                <i class="bx bx-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-3">
                                    {{ $holidays->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


