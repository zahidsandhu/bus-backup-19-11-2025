@extends('admin.layouts.app')

@section('title', 'Create Holiday')

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-sm-0">
                                <i class="bx bx-calendar-plus me-2"></i>Create Holiday
                            </h4>
                        </div>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('admin.holidays.index') }}">Holidays</a></li>
                                <li class="breadcrumb-item active">Create</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-8 mx-auto">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Holiday Details</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('admin.holidays.store') }}" method="POST">
                                @csrf

                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                    <input type="date"
                                           id="start_date"
                                           name="start_date"
                                           class="form-control @error('start_date') is-invalid @enderror"
                                           value="{{ old('start_date') }}"
                                           required>
                                    @error('start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                    <input type="date"
                                           id="end_date"
                                           name="end_date"
                                           class="form-control @error('end_date') is-invalid @enderror"
                                           value="{{ old('end_date') }}"
                                           required>
                                    @error('end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Bookings will be blocked for all dates inclusive in this range.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="reason" class="form-label">Reason (optional)</label>
                                    <input type="text"
                                           id="reason"
                                           name="reason"
                                           class="form-control @error('reason') is-invalid @enderror"
                                           value="{{ old('reason') }}"
                                           placeholder="e.g., Eid Holidays, Maintenance Shutdown">
                                    @error('reason')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="is_active"
                                           name="is_active"
                                           value="1"
                                           {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('admin.holidays.index') }}" class="btn btn-outline-secondary">
                                        <i class="bx bx-arrow-back me-1"></i>Back
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save me-1"></i>Save Holiday
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


