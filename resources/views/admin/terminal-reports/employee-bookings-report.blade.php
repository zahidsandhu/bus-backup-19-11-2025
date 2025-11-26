@extends('admin.layouts.app')

@section('title', 'Employee Bookings Report')

@section('content')
<!--breadcrumb-->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Reports</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.terminal-reports.index') }}">Terminal Reports</a></li>
                <li class="breadcrumb-item active" aria-current="page">Employee Bookings Report</li>
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
                    <i class="bx bx-filter-alt text-primary"></i> Employee Bookings Report Filters
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
                                        <i class="bx bx-building"></i> Terminal
                                    </label>
                                    <select class="form-select form-select-sm" id="terminalSelect">
                                        <option value="">All Terminals</option>
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

                            <div class="col-md-3">
                                <label class="form-label small fw-bold">
                                    <i class="bx bx-user"></i> Employee
                                </label>
                                <select class="form-select form-select-sm" id="filterEmployee">
                                    @if ($canViewAllReports)
                                        <option value="">All Employees</option>
                                    @endif
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}" {{ isset($selectedEmployeeId) && (string) $selectedEmployeeId === (string) $employee->id ? 'selected' : '' }}>
                                            {{ $employee->name }}{{ $employee->email ? ' (' . $employee->email . ')' : '' }}{{ $employee->terminal ? ' - ' . $employee->terminal->name : '' }}
                                        </option>
                                    @endforeach
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
                        </div>
                    </div>

                    <!-- Status & Payment Filters -->
                    <div class="col-lg-12 mb-3">
                        <h6 class="text-muted mb-3 fw-semibold" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="bx bx-filter"></i> Status & Payment Filters
                        </h6>
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Booking Status</label>
                                <select class="form-select form-select-sm" id="filterStatus">
                                    <option value="">All Status</option>
                                    @foreach ($bookingStatuses as $status)
                                        <option value="{{ $status->value }}">{{ $status->getLabel() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Payment Status</label>
                                <select class="form-select form-select-sm" id="filterPaymentStatus">
                                    <option value="">All Payments</option>
                                    @foreach ($paymentStatuses as $status)
                                        <option value="{{ $status->value }}">{{ $status->getLabel() }}</option>
                                    @endforeach
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
                        </div>
                        <div class="row g-2 mt-2">
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
        <p class="mt-2">Generating employee bookings report...</p>
    </div>

    <!-- Report Content -->
    <div id="reportContent" style="display: none;">

        <!-- Employee Summary Cards -->
        <div id="employeeSummaryCards" class="row g-3 mb-4">
            <!-- Cards will be dynamically generated here -->
        </div>

        <!-- Employee Statistics Table -->
        <div class="card shadow-sm mb-4 border-0 border-start border-4 border-primary">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="bx bx-user text-primary"></i> Employee Bookings Summary
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0" id="employeeSummaryTable">
                        <thead class="table-primary">
                            <tr>
                                <th class="text-center">Employee</th>
                                <th class="text-center">Terminal</th>
                                <th class="text-center">Total Bookings</th>
                                <th class="text-center">Total Amount (PKR)</th>
                                <th class="text-center">Daily Average</th>
                                <th class="text-center">Monthly Total</th>
                            </tr>
                        </thead>
                        <tbody id="employeeSummaryTableBody">
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">
                                    No data available. Please generate a report.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Daily & Monthly Breakdown -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="bx bx-calendar text-success"></i> Daily Bookings Breakdown
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0" id="dailyBreakdownTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th class="text-end">Bookings</th>
                                        <th class="text-end">Amount (PKR)</th>
                                    </tr>
                                </thead>
                                <tbody id="dailyBreakdownTableBody">
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">
                                            No data available
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="bx bx-calendar-check text-info"></i> Monthly Bookings Breakdown
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0" id="monthlyBreakdownTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Month</th>
                                        <th class="text-end">Bookings</th>
                                        <th class="text-end">Amount (PKR)</th>
                                    </tr>
                                </thead>
                                <tbody id="monthlyBreakdownTableBody">
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">
                                            No data available
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bookings Table with DataTable -->
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-header bg-white border-bottom py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">
                        <i class="bx bx-list-ul text-primary"></i> Complete Booking Details
                    </h6>
                    <button class="btn btn-success btn-sm" onclick="exportReport()">
                        <i class="bx bx-download"></i> Export Report
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0" id="bookingsTable">
                        <thead class="table-light">
                            <tr>
                                <th><i class="bx bx-ticket"></i> Booking #</th>
                                <th><i class="bx bx-calendar"></i> Date & Time</th>
                                <th><i class="bx bx-route"></i> Route</th>
                                <th><i class="bx bx-map"></i> From → To</th>
                                <th><i class="bx bx-user"></i> Passengers</th>
                                <th><i class="bx bx-chair"></i> Seats</th>
                                <th><i class="bx bx-user-circle"></i> Employee</th>
                                <th><i class="bx bx-check-circle"></i> Status</th>
                                <th><i class="bx bx-check-square"></i> Is Advance</th>
                                <th><i class="bx bx-credit-card"></i> Payment Method</th>
                                <th><i class="bx bx-credit-card"></i> Payment Status</th>
                                <th><i class="bx bx-money"></i> Amount</th>
                            </tr>
                        </thead>
                        <tbody>
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

    #employeeSummaryTable thead th {
        background-color: #0d6efd !important;
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.85rem;
    }
</style>
@endsection

@section('scripts')
<script>
    let bookingsTable = null;

    // Load employees when terminal is selected
    $('#terminalSelect').on('change', function() {
        const terminalId = $(this).val();
        const employeeSelect = $('#filterEmployee');

        employeeSelect.html('<option value="">All Employees</option>');

        if (terminalId) {
            // Filter employees by terminal
            @if ($canViewAllReports)
                @foreach ($employees as $employee)
                    @if ($employee->terminal_id)
                        if ({{ $employee->terminal_id }} == terminalId) {
                            employeeSelect.append(
                                $('<option></option>')
                                    .attr('value', {{ $employee->id }})
                                    .text('{{ $employee->name }}{{ $employee->email ? " (" . $employee->email . ")" : "" }} - {{ $employee->terminal ? $employee->terminal->name : "" }}')
                            );
                        }
                    @endif
                @endforeach
            @endif
        } else {
            // Show all employees
            @foreach ($employees as $employee)
                employeeSelect.append(
                    $('<option></option>')
                        .attr('value', {{ $employee->id }})
                        .text('{{ $employee->name }}{{ $employee->email ? " (" . $employee->email . ")" : "" }}{{ $employee->terminal ? " - " . $employee->terminal->name : "" }}')
                );
            @endforeach
        }
    });

    function loadReport() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;

        if (!startDate || !endDate) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Information',
                text: 'Please select Start Date and End Date.',
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

        // Load summary data
        $.ajax({
            url: "{{ route('admin.terminal-reports.employee-bookings-data') }}",
            type: 'GET',
            data: {
                terminal_id: document.getElementById('terminalSelect').value || null,
                employee_id: document.getElementById('filterEmployee').value || null,
                start_date: startDate,
                end_date: endDate,
                status: document.getElementById('filterStatus').value || null,
                payment_status: document.getElementById('filterPaymentStatus').value || null,
                payment_method: document.getElementById('filterPaymentMethod').value || null,
                channel: document.getElementById('filterChannel').value || null,
                is_advance: document.getElementById('filterAdvance').value || null
            },
            success: function(response) {
                if (response.success) {
                    renderSummaryReport(response);
                    loadBookingsTable();
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

    function renderSummaryReport(data) {
        const employeeStats = data.employee_stats || [];

        // Clear previous data
        $('#employeeSummaryCards').html('');
        $('#employeeSummaryTableBody').html('');
        $('#dailyBreakdownTableBody').html('');
        $('#monthlyBreakdownTableBody').html('');

        if (employeeStats.length === 0) {
            $('#employeeSummaryTableBody').html('<tr><td colspan="6" class="text-center text-muted py-3">No data available for the selected filters.</td></tr>');
            $('#dailyBreakdownTableBody').html('<tr><td colspan="3" class="text-center text-muted py-3">No data available</td></tr>');
            $('#monthlyBreakdownTableBody').html('<tr><td colspan="3" class="text-center text-muted py-3">No data available</td></tr>');
            return;
        }

        // Calculate totals
        let totalBookings = 0;
        let totalAmount = 0;
        const allDailyStats = {};
        const allMonthlyStats = {};

        employeeStats.forEach(function(stat) {
            totalBookings += stat.total_bookings;
            totalAmount += stat.total_amount;

            // Aggregate daily stats
            stat.daily_stats.forEach(function(daily) {
                if (!allDailyStats[daily.date]) {
                    allDailyStats[daily.date] = { count: 0, amount: 0 };
                }
                allDailyStats[daily.date].count += daily.count;
                allDailyStats[daily.date].amount += daily.amount;
            });

            // Aggregate monthly stats
            stat.monthly_stats.forEach(function(monthly) {
                if (!allMonthlyStats[monthly.month]) {
                    allMonthlyStats[monthly.month] = { count: 0, amount: 0 };
                }
                allMonthlyStats[monthly.month].count += monthly.count;
                allMonthlyStats[monthly.month].amount += monthly.amount;
            });
        });

        // Render summary cards
        const cardsHtml = `
            <div class="col-md-3">
                <div class="card shadow-sm border-0 border-start border-4 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary me-3">
                                <i class="bx bx-user"></i>
                            </div>
                            <div>
                                <h4 class="my-1 text-primary" id="totalEmployees">${employeeStats.length}</h4>
                                <p class="mb-0 font-13">Total Employees</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 border-start border-4 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="widgets-icons-2 rounded-circle bg-light-success text-success me-3">
                                <i class="bx bx-ticket"></i>
                            </div>
                            <div>
                                <h4 class="my-1 text-success" id="totalBookings">${totalBookings}</h4>
                                <p class="mb-0 font-13">Total Bookings</p>
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
                                <i class="bx bx-money"></i>
                            </div>
                            <div>
                                <h4 class="my-1 text-info" id="totalAmount">PKR ${totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</h4>
                                <p class="mb-0 font-13">Total Amount</p>
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
                                <i class="bx bx-calculator"></i>
                            </div>
                            <div>
                                <h4 class="my-1 text-warning" id="averagePerEmployee">${employeeStats.length > 0 ? (totalBookings / employeeStats.length).toFixed(1) : 0}</h4>
                                <p class="mb-0 font-13">Avg Bookings/Employee</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        $('#employeeSummaryCards').html(cardsHtml);

        // Render employee summary table
        let summaryTableHtml = '';
        employeeStats.forEach(function(stat) {
            const terminalName = stat.employee.terminal ? stat.employee.terminal.name + (stat.employee.terminal.code ? ' (' + stat.employee.terminal.code + ')' : '') : 'No Terminal';
            const daysDiff = Math.ceil((new Date(data.end_date) - new Date(data.start_date)) / (1000 * 60 * 60 * 24)) + 1;
            const dailyAvg = daysDiff > 0 ? (stat.total_bookings / daysDiff).toFixed(2) : 0;
            const monthlyTotal = stat.monthly_stats.reduce((sum, m) => sum + m.count, 0);

            summaryTableHtml += `
                <tr>
                    <td class="fw-bold">${stat.employee.name}${stat.employee.email ? '<br><small class="text-muted">' + stat.employee.email + '</small>' : ''}</td>
                    <td>${terminalName}</td>
                    <td class="text-center fw-bold">${stat.total_bookings}</td>
                    <td class="text-end fw-bold text-primary">PKR ${stat.total_amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td class="text-center">${dailyAvg}</td>
                    <td class="text-center fw-bold">${monthlyTotal}</td>
                </tr>
            `;
        });
        $('#employeeSummaryTableBody').html(summaryTableHtml);

        // Render daily breakdown
        const dailyDates = Object.keys(allDailyStats).sort();
        let dailyHtml = '';
        if (dailyDates.length > 0) {
            dailyDates.forEach(function(date) {
                const stat = allDailyStats[date];
                dailyHtml += `
                    <tr>
                        <td>${new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</td>
                        <td class="text-end fw-bold">${stat.count}</td>
                        <td class="text-end">PKR ${stat.amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    </tr>
                `;
            });
        } else {
            dailyHtml = '<tr><td colspan="3" class="text-center text-muted py-3">No data available</td></tr>';
        }
        $('#dailyBreakdownTableBody').html(dailyHtml);

        // Render monthly breakdown
        const monthlyMonths = Object.keys(allMonthlyStats).sort();
        let monthlyHtml = '';
        if (monthlyMonths.length > 0) {
            monthlyMonths.forEach(function(month) {
                const stat = allMonthlyStats[month];
                const [year, monthNum] = month.split('-');
                const monthName = new Date(year, monthNum - 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                monthlyHtml += `
                    <tr>
                        <td>${monthName}</td>
                        <td class="text-end fw-bold">${stat.count}</td>
                        <td class="text-end">PKR ${stat.amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    </tr>
                `;
            });
        } else {
            monthlyHtml = '<tr><td colspan="3" class="text-center text-muted py-3">No data available</td></tr>';
        }
        $('#monthlyBreakdownTableBody').html(monthlyHtml);
    }

    function loadBookingsTable() {
        if (bookingsTable) {
            bookingsTable.destroy();
        }

        bookingsTable = $('#bookingsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('admin.terminal-reports.employee-bookings-table-data') }}",
                data: function(d) {
                    d.terminal_id = document.getElementById('terminalSelect').value || null;
                    d.employee_id = document.getElementById('filterEmployee').value || null;
                    d.start_date = document.getElementById('startDate').value;
                    d.end_date = document.getElementById('endDate').value;
                    d.status = document.getElementById('filterStatus').value || null;
                    d.payment_status = document.getElementById('filterPaymentStatus').value || null;
                    d.payment_method = document.getElementById('filterPaymentMethod').value || null;
                    d.channel = document.getElementById('filterChannel').value || null;
                    d.is_advance = document.getElementById('filterAdvance').value || null;
                }
            },
            columns: [
                { data: 'booking_number_formatted', name: 'booking_number' },
                { data: 'date_time', name: 'created_at' },
                { data: 'route_info', name: 'route' },
                { data: 'from_to', name: 'from_to' },
                { data: 'passengers_count', name: 'total_passengers' },
                { data: 'seats_info', name: 'seats' },
                { data: 'employee_info', name: 'booked_by_user_id' },
                { data: 'status_badge', name: 'status' },
                { data: 'is_advance', name: 'is_advance' },
                { data: 'payment_method', name: 'payment_method' },
                { data: 'payment_status_badge', name: 'payment_status' },
                { data: 'amount_formatted', name: 'final_amount' }
            ],
            order: [[1, 'desc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            language: {
                processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
            }
        });
    }

    function resetFilters() {
        document.getElementById('startDate').value = '{{ date('Y-m-d') }}';
        document.getElementById('endDate').value = '{{ date('Y-m-d') }}';
        document.getElementById('filterEmployee').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterPaymentStatus').value = '';
        document.getElementById('filterPaymentMethod').value = '';
        document.getElementById('filterChannel').value = '';
        document.getElementById('filterAdvance').value = '';
        @if ($canSelectTerminal)
            document.getElementById('terminalSelect').value = '';
        @endif
        document.getElementById('reportContent').style.display = 'none';
        if (bookingsTable) {
            bookingsTable.destroy();
            bookingsTable = null;
        }
    }

    function exportReport() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;

        if (!startDate || !endDate) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Information',
                text: 'Please select Start Date and End Date before exporting.',
                confirmButtonColor: '#ffc107'
            });
            return;
        }

        // Create export URL with current filters
        const params = new URLSearchParams({
            terminal_id: document.getElementById('terminalSelect').value || '',
            employee_id: document.getElementById('filterEmployee').value || '',
            start_date: startDate,
            end_date: endDate,
            status: document.getElementById('filterStatus').value || '',
            payment_status: document.getElementById('filterPaymentStatus').value || '',
            payment_method: document.getElementById('filterPaymentMethod').value || '',
            channel: document.getElementById('filterChannel').value || '',
            is_advance: document.getElementById('filterAdvance').value || ''
        });

        window.open("{{ route('admin.terminal-reports.employee-bookings-table-data') }}?" + params.toString() + "&export=1", '_blank');
    }
</script>
@endsection

