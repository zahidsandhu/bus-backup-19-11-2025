@extends('admin.layouts.app')

@section('title', 'Cancellation Reports')

@section('content')
<!--breadcrumb-->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Reports</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.terminal-reports.index') }}">Terminal Reports</a></li>
                <li class="breadcrumb-item active" aria-current="page">Cancellation Reports</li>
            </ol>
        </nav>
    </div>
</div>
<!--end breadcrumb-->

<div class="container-fluid">
    <!-- Filter Card -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">
                    <i class="bx bx-filter-alt text-primary"></i> Cancellation Report Filters
                </h6>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="true">
                    <i class="bx bx-chevron-down"></i> Toggle Filters
                </button>
            </div>
        </div>
        <div class="collapse show" id="filterCollapse">
            <div class="card-body">
                <div class="row g-3">
                    <!-- Date Range & Terminal Section -->
                    <div class="col-lg-12 mb-3">
                        <h6 class="text-muted mb-3 fw-semibold" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="bx bx-calendar"></i> Date Range & Terminal
                        </h6>
                        <div class="row g-2">
                            @if ($canSelectTerminal)
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">
                                        <i class="bx bx-building"></i> Terminal <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select form-select-sm" id="terminalSelect" required>
                                        <option value="">-- Select Terminal --</option>
                                        @foreach ($terminals as $terminal)
                                            <option value="{{ $terminal->id }}">{{ $terminal->name }}{{ $terminal->code ? ' (' . $terminal->code . ')' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">
                                        <i class="bx bx-building"></i> Terminal
                                    </label>
                                    @php
                                        $terminal = $terminals->first();
                                    @endphp
                                    <input type="text" class="form-control form-control-sm"
                                        value="{{ ($terminal ? $terminal->name . ($terminal->code ? ' (' . $terminal->code . ')' : '') : 'N/A') }}"
                                        readonly>
                                    <input type="hidden" id="terminalSelect" value="{{ $terminal->id ?? '' }}">
                                </div>
                            @endif

                            <div class="col-md-2">
                                <label class="form-label small fw-bold">
                                    <i class="bx bx-route"></i> Route
                                </label>
                                <select class="form-select form-select-sm" id="filterRoute">
                                    <option value="">All Routes</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold">
                                    <i class="bx bx-calendar"></i> Start Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control form-control-sm" id="startDate"
                                    value="{{ date('Y-m-d') }}" required>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold">
                                    <i class="bx bx-calendar"></i> End Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control form-control-sm" id="endDate" value="{{ date('Y-m-d') }}" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold">
                                    <i class="bx bx-user"></i> Cancelled By User
                                </label>
                                <select class="form-select form-select-sm" id="filterCancelledBy">
                                    @if ($canViewAllReports)
                                        <option value="">All Users</option>
                                    @endif
                                    @foreach ($users as $reportUser)
                                        <option value="{{ $reportUser->id }}" {{ isset($selectedUserId) && (string) $selectedUserId === (string) $reportUser->id ? 'selected' : '' }}>
                                            {{ $reportUser->name }}{{ $reportUser->email ? ' (' . $reportUser->email . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Cancellation Filters -->
                    <div class="col-lg-12 mb-3">
                        <h6 class="text-muted mb-3 fw-semibold" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="bx bx-x-circle"></i> Cancellation Filters
                        </h6>
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Cancelled By Type</label>
                                <select class="form-select form-select-sm" id="filterCancelledByType">
                                    <option value="">All Types</option>
                                    <option value="admin">Admin</option>
                                    <option value="employee">Employee</option>
                                    <option value="customer">Customer</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Payment Method</label>
                                <select class="form-select form-select-sm" id="filterPaymentMethod">
                                    <option value="">All Methods</option>
                                    @foreach ($paymentMethods as $method)
                                        @if($method['value'] !== 'other')
                                            <option value="{{ $method['value'] }}">{{ $method['label'] }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Channel</label>
                                <select class="form-select form-select-sm" id="filterChannel">
                                    <option value="">All Channels</option>
                                    @foreach ($channels as $channel)
                                        <option value="{{ $channel->value }}">{{ $channel->getLabel() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Booking Type</label>
                                <select class="form-select form-select-sm" id="filterAdvance">
                                    <option value="">All Bookings</option>
                                    <option value="1">Advance Bookings Only</option>
                                    <option value="0">Regular Bookings Only</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-lg-12 mt-3 pt-3 border-top">
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary btn-sm" onclick="loadReport()">
                                <i class="bx bx-search"></i> Generate Report
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="resetFilters()">
                                <i class="bx bx-refresh"></i> Reset Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div id="loadingIndicator" class="text-center py-5" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2">Generating cancellation report...</p>
    </div>

    <!-- Report Content -->
    <div id="reportContent" style="display: none;">

        <!-- Cancellation Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm border-0 border-start border-4 border-danger">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="widgets-icons-2 rounded-circle bg-light-danger text-danger me-3">
                                <i class="bx bx-x-circle"></i>
                            </div>
                            <div>
                                <h4 class="my-1 text-danger" id="totalCancellations">0</h4>
                                <p class="mb-0 font-13">Total Cancellations</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 border-start border-4 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="widgets-icons-2 rounded-circle bg-light-warning text-warning me-3">
                                <i class="bx bx-money"></i>
                            </div>
                            <div>
                                <h4 class="my-1 text-warning" id="totalRefundAmount">PKR 0</h4>
                                <p class="mb-0 font-13">Total Refund Amount</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 border-start border-4 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="widgets-icons-2 rounded-circle bg-light-info text-info me-3">
                                <i class="bx bx-chair"></i>
                            </div>
                            <div>
                                <h4 class="my-1 text-info" id="totalCancelledSeats">0</h4>
                                <p class="mb-0 font-13">Cancelled Seats</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 border-start border-4 border-secondary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="widgets-icons-2 rounded-circle bg-light-secondary text-secondary me-3">
                                <i class="bx bx-user"></i>
                            </div>
                            <div>
                                <h4 class="my-1 text-secondary" id="totalCancelledPassengers">0</h4>
                                <p class="mb-0 font-13">Cancelled Passengers</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancelled Bookings Table with DataTable -->
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-header bg-white border-bottom py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">
                        <i class="bx bx-x-circle text-danger"></i> Cancelled Bookings from This Terminal
                    </h6>
                    <button class="btn btn-success btn-sm" onclick="exportReport()">
                        <i class="bx bx-download"></i> Export Report
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0" id="cancellationsTable">
                        <thead class="table-light">
                            <tr>
                                <th><i class="bx bx-ticket"></i> Booking #</th>
                                <th><i class="bx bx-calendar"></i> Booking Date</th>
                                <th><i class="bx bx-x-circle"></i> Cancelled Date</th>
                                <th><i class="bx bx-route"></i> Route</th>
                                <th><i class="bx bx-user"></i> Passengers</th>
                                <th><i class="bx bx-chair"></i> Seats</th>
                                <th><i class="bx bx-store"></i> Channel</th>
                                <th><i class="bx bx-check-square"></i> Is Advance</th>
                                <th><i class="bx bx-credit-card"></i> Payment Method</th>
                                <th><i class="bx bx-money"></i> Amount</th>
                                <th><i class="bx bx-user-x"></i> Cancelled By</th>
                                <th><i class="bx bx-message-detail"></i> Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Cancellation Breakdown -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="bx bx-user-x text-danger"></i> Cancellations by User Type
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="cancellationsByTypeBreakdown">
                            <p class="text-muted text-center mb-0 small">No data available</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="bx bx-credit-card text-warning"></i> Cancellations by Payment Method
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="cancellationsByPaymentBreakdown">
                            <p class="text-muted text-center mb-0 small">No data available</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Cancellation Reasons -->
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="bx bx-message-detail text-info"></i> Top Cancellation Reasons
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0" id="cancellationReasonsTable">
                        <thead class="table-info">
                            <tr>
                                <th class="text-center" style="width: 60%;">Cancellation Reason</th>
                                <th class="text-center" style="width: 20%;">Count</th>
                                <th class="text-center" style="width: 20%;">Percentage</th>
                            </tr>
                        </thead>
                        <tbody id="cancellationReasonsTableBody">
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">
                                    No data available. Please generate a report.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .radius-10 {
        border-radius: 10px;
    }

    .border-start {
        border-left-width: 4px !important;
    }

    .bg-light-primary {
        background-color: rgba(13, 110, 253, 0.1);
    }

    .bg-light-success {
        background-color: rgba(25, 135, 84, 0.1);
    }

    .bg-light-warning {
        background-color: rgba(255, 193, 7, 0.1);
    }

    .bg-light-info {
        background-color: rgba(13, 202, 240, 0.1);
    }

    .bg-light-danger {
        background-color: rgba(220, 53, 69, 0.1);
    }

    .bg-light-secondary {
        background-color: rgba(108, 117, 125, 0.1);
    }

    .text-primary {
        color: #0d6efd !important;
    }

    .text-success {
        color: #198754 !important;
    }

    .text-info {
        color: #0dcaf0 !important;
    }

    .text-warning {
        color: #ffc107 !important;
    }

    .text-danger {
        color: #dc3545 !important;
    }

    .text-secondary {
        color: #6c757d !important;
    }

    .widgets-icons-2 {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .card {
        border-radius: 8px;
    }

    .table thead th {
        font-weight: 600;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Print Styles */
    @media print {
        .no-print {
            display: none !important;
        }

        body {
            font-size: 12px;
        }

        .card {
            border: 1px solid #ddd;
            page-break-inside: avoid;
        }

        .table {
            font-size: 11px;
        }

        .table th,
        .table td {
            padding: 4px 8px;
        }

        .bg-primary,
        .bg-success,
        .bg-warning,
        .bg-info,
        .bg-danger,
        .bg-dark {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        @page {
            margin: 1cm;
            size: A4 landscape;
        }
    }

    /* Table enhancements */
    .table-sm th,
    .table-sm td {
        padding: 0.5rem;
        font-size: 0.875rem;
    }

    .sticky-top {
        position: sticky;
        top: 0;
        z-index: 10;
    }
</style>
@endsection

@section('scripts')
    <script>
        // Load routes when terminal is selected
        $('#terminalSelect').on('change', function() {
            const terminalId = $(this).val();
            const routeSelect = $('#filterRoute');

            routeSelect.html('<option value="">All Routes</option>');

            if (terminalId) {
                $.ajax({
                    url: "{{ route('admin.terminal-reports.routes') }}",
                    type: 'GET',
                    data: { terminal_id: terminalId },
                    success: function(response) {
                        if (response.success && response.routes.length > 0) {
                            response.routes.forEach(function(route) {
                                routeSelect.append(
                                    $('<option></option>')
                                        .attr('value', route.id)
                                        .text(route.name + (route.code ? ' (' + route.code + ')' : ''))
                                );
                            });
                        }
                    },
                    error: function() {
                        console.error('Failed to load routes');
                    }
                });
            }
        });

        // Auto-load routes if terminal is pre-selected
        @if (!$canSelectTerminal && $terminals->isNotEmpty())
            $(document).ready(function() {
                const terminalId = $('#terminalSelect').val();
                if (terminalId) {
                    $('#terminalSelect').trigger('change');
                }
            });
        @endif

        function loadReport() {
            const terminalId = document.getElementById('terminalSelect').value;
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            if (!terminalId || !startDate || !endDate) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Information',
                    text: 'Please select all required fields: Terminal, Start Date, and End Date.',
                    confirmButtonColor: '#ffc107'
                });
                return;
            }

            if (new Date(startDate) > new Date(endDate)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Date Range',
                    text: 'Start date cannot be greater than end date.',
                    confirmButtonColor: '#d33'
                });
                return;
            }

            // Show loading indicator
            document.getElementById('loadingIndicator').style.display = 'block';
            document.getElementById('reportContent').style.display = 'none';

            $.ajax({
                url: "{{ route('admin.terminal-reports.cancellation-data') }}",
                type: 'GET',
                data: {
                    terminal_id: terminalId,
                    start_date: startDate,
                    end_date: endDate,
                    route_id: document.getElementById('filterRoute').value || null,
                    cancelled_by_user_id: document.getElementById('filterCancelledBy').value || null
                },
                success: function(response) {
                    if (response.success) {
                        renderReport(response);
                        document.getElementById('reportContent').style.display = 'block';
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed to Load Report',
                            text: response.error || 'Unable to generate report. Please try again.',
                            confirmButtonColor: '#d33'
                        });
                    }
                },
                error: function(error) {
                    const message = error.responseJSON?.error ||
                        'Unable to generate report. Please check your connection and try again.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed to Load Report',
                        text: message,
                        confirmButtonColor: '#d33'
                    });
                },
                complete: function() {
                    document.getElementById('loadingIndicator').style.display = 'none';
                }
            });
        }

        let cancellationsTable = null;

        function renderReport(data) {
            const stats = data.stats;

            // Update summary cards
            document.getElementById('totalCancellations').textContent = stats.total_cancellations || 0;
            document.getElementById('totalRefundAmount').textContent = 'PKR ' + (stats.total_refund_amount || 0).toLocaleString('en-US', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
            document.getElementById('totalCancelledSeats').textContent = stats.total_cancelled_seats || 0;
            document.getElementById('totalCancelledPassengers').textContent = stats.total_cancelled_passengers || 0;

            // Initialize DataTable for cancellations
            initializeCancellationsTable();

            // Render cancellation reasons table
            const reasonsBody = document.getElementById('cancellationReasonsTableBody');
            const reasons = data.cancellation_reasons || [];

            if (reasons.length === 0) {
                reasonsBody.innerHTML = `
                    <tr>
                        <td colspan="3" class="text-center text-muted py-3">No cancellation reasons found.</td>
                    </tr>
                `;
            } else {
                let reasonsHtml = '';
                const totalReasons = reasons.reduce((sum, reason) => sum + reason.count, 0);

                reasons.forEach(reason => {
                    const percentage = totalReasons > 0 ? ((reason.count / totalReasons) * 100).toFixed(1) : 0;
                    reasonsHtml += `
                        <tr>
                            <td class="fw-semibold">${reason.reason || 'No reason provided'}</td>
                            <td class="text-center">${reason.count}</td>
                            <td class="text-center">${percentage}%</td>
                        </tr>
                    `;
                });
                reasonsBody.innerHTML = reasonsHtml;
            }

            // Render cancellations by type breakdown
            const typeDiv = document.getElementById('cancellationsByTypeBreakdown');
            if (Object.keys(stats.by_cancelled_type || {}).length === 0) {
                typeDiv.innerHTML = '<p class="text-muted text-center mb-0 small">No data available</p>';
            } else {
                let typeHtml = '';
                Object.entries(stats.by_cancelled_type).forEach(([type, data]) => {
                    const typeLabel = type.charAt(0).toUpperCase() + type.slice(1);
                    typeHtml += `
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <div>
                                <strong>${typeLabel}</strong>
                                <small class="d-block text-muted">${data.count} cancellations</small>
                            </div>
                            <div class="text-end">
                                <strong class="text-danger">PKR ${parseFloat(data.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0})}</strong>
                            </div>
                        </div>
                    `;
                });
                typeDiv.innerHTML = typeHtml;
            }

            // Render cancellations by payment method breakdown
            const paymentDiv = document.getElementById('cancellationsByPaymentBreakdown');
            if (Object.keys(stats.by_payment_method || {}).length === 0) {
                paymentDiv.innerHTML = '<p class="text-muted text-center mb-0 small">No data available</p>';
            } else {
                let paymentHtml = '';
                Object.entries(stats.by_payment_method).forEach(([method, data]) => {
                    const methodLabel = method.charAt(0).toUpperCase() + method.slice(1).replace('_', ' ');
                    paymentHtml += `
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <div>
                                <strong>${methodLabel}</strong>
                                <small class="d-block text-muted">${data.count} cancellations</small>
                            </div>
                            <div class="text-end">
                                <strong class="text-warning">PKR ${parseFloat(data.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0})}</strong>
                            </div>
                        </div>
                    `;
                });
                paymentDiv.innerHTML = paymentHtml;
            }
        }

        function initializeCancellationsTable() {
            // Destroy existing table if it exists
            if (cancellationsTable) {
                cancellationsTable.destroy();
                cancellationsTable = null;
            }

            const terminalId = document.getElementById('terminalSelect').value;
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            if (!terminalId || !startDate || !endDate) {
                return;
            }

            cancellationsTable = $('#cancellationsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.terminal-reports.cancellation-bookings-data') }}',
                    data: function(d) {
                        d.terminal_id = terminalId;
                        d.start_date = startDate;
                        d.end_date = endDate;
                        d.route_id = document.getElementById('filterRoute').value;
                        d.cancelled_by_user_id = document.getElementById('filterCancelledBy').value;
                        d.cancelled_by_type = document.getElementById('filterCancelledByType').value;
                        d.payment_method = document.getElementById('filterPaymentMethod').value;
                        d.channel = document.getElementById('filterChannel').value;
                        d.is_advance = document.getElementById('filterAdvance').value;
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTable AJAX Error:', error, thrown);
                        Swal.fire({
                            icon: 'error',
                            title: 'Loading Error',
                            text: 'Failed to load cancellations data. Please try again.',
                            confirmButtonColor: '#d33'
                        });
                    }
                },
                columns: [
                    { data: 'booking_number', name: 'booking_number' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'cancelled_at', name: 'cancelled_at' },
                    { data: 'route', name: 'route' },
                    { data: 'passengers', name: 'passengers', orderable: false, searchable: false },
                    { data: 'seats', name: 'seats', orderable: false, searchable: false },
                    { data: 'channel', name: 'channel' },
                    { data: 'is_advance', name: 'is_advance', orderable: false, searchable: false },
                    { data: 'payment_method', name: 'payment_method' },
                    { data: 'amount', name: 'final_amount' },
                    { data: 'cancelled_by', name: 'cancelled_by', orderable: false, searchable: false },
                    { data: 'cancellation_reason', name: 'cancellation_reason', orderable: false, searchable: false }
                ],
                order: [[2, 'desc']],
                pageLength: 25,
                language: {
                    search: '_INPUT_',
                    searchPlaceholder: 'Search cancellations...',
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                    emptyTable: 'No cancelled bookings found',
                    zeroRecords: 'No matching cancelled bookings found'
                },
                dom: 'lfrtip',
            });

            // Add event listeners for filter changes
            $('#filterRoute, #filterCancelledByType, #filterPaymentMethod, #filterChannel, #filterAdvance').on('change', function() {
                if (cancellationsTable) {
                    cancellationsTable.ajax.reload(null, false);
                }
            });

            // Reload full report when cancelled by user filter changes
            $('#filterCancelledBy').on('change', function() {
                if (cancellationsTable) {
                    cancellationsTable.ajax.reload(null, false);
                    // Also reload summary data
                    loadReport();
                }
            });
        }

        function resetFilters() {
            document.getElementById('filterRoute').value = '';
            document.getElementById('filterCancelledBy').value = '';
            document.getElementById('filterCancelledByType').value = '';
            document.getElementById('filterPaymentMethod').value = '';
            document.getElementById('filterChannel').value = '';
            document.getElementById('filterAdvance').value = '';

            if (cancellationsTable) {
                cancellationsTable.ajax.reload(null, false);
            }
        }

        function exportReport() {
            const terminalId = document.getElementById('terminalSelect').value;
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            if (!terminalId || !startDate || !endDate) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Information',
                    text: 'Please select all required fields: Terminal, Start Date, and End Date.',
                    confirmButtonColor: '#ffc107'
                });
                return;
            }

            if (new Date(startDate) > new Date(endDate)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Date Range',
                    text: 'Start date cannot be greater than end date.',
                    confirmButtonColor: '#d33'
                });
                return;
            }

            // Build query string with all filters
            const params = new URLSearchParams({
                terminal_id: terminalId,
                start_date: startDate,
                end_date: endDate,
                route_id: document.getElementById('filterRoute').value || '',
                cancelled_by_user_id: document.getElementById('filterCancelledBy').value || '',
                cancelled_by_type: document.getElementById('filterCancelledByType').value || '',
                payment_method: document.getElementById('filterPaymentMethod').value || '',
                channel: document.getElementById('filterChannel').value || '',
                is_advance: document.getElementById('filterAdvance').value || ''
            });

            // Open export in new window
            window.open("{{ route('admin.terminal-reports.cancellation-export') }}?" + params.toString(), '_blank');
        }

        // Auto-load report for users with assigned terminal (their terminal)
        @if (!$canSelectTerminal && $terminals->isNotEmpty())
            document.addEventListener('DOMContentLoaded', function() {
                loadReport();
            });
        @endif
    </script>
@endsection
