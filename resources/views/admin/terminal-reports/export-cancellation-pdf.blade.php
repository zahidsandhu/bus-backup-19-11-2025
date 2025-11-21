<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancellation Report - {{ $terminal->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 15px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #dc3545;
            padding-bottom: 15px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #dc3545;
            margin-bottom: 5px;
        }

        .report-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .report-info {
            font-size: 10px;
            color: #666;
        }

        .summary-cards {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 15px;
        }

        .summary-card {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            text-align: center;
            background: #f8f9fa;
        }

        .summary-card.danger {
            border-color: #dc3545;
            background: #f8d7da;
        }

        .summary-card.warning {
            border-color: #ffc107;
            background: #fff3cd;
        }

        .summary-card.info {
            border-color: #0dcaf0;
            background: #d1ecf1;
        }

        .summary-card.secondary {
            border-color: #6c757d;
            background: #e2e3e5;
        }

        .summary-value {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .summary-label {
            font-size: 9px;
            color: #666;
            text-transform: uppercase;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
            padding: 5px 10px;
            background: #dc3545;
            color: white;
            border-radius: 3px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 8px;
            font-weight: bold;
            border-radius: 3px;
            color: white;
        }

        .badge-danger {
            background-color: #dc3545;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #000;
        }

        .badge-success {
            background-color: #198754;
        }

        .badge-info {
            background-color: #0dcaf0;
            color: #000;
        }

        .badge-secondary {
            background-color: #6c757d;
        }

        .badge-primary {
            background-color: #0d6efd;
        }

        .breakdown-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }

        .breakdown-item:last-child {
            border-bottom: none;
        }

        .breakdown-label {
            font-weight: bold;
        }

        .breakdown-value {
            color: #dc3545;
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .filters-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 9px;
        }

        .filters-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .reason-cell {
            max-width: 200px;
            word-wrap: break-word;
        }

        @page {
            margin: 1cm;
            size: A4 landscape;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-name">{{ $generalSettings->company_name ?? 'Bus Management System' }}</div>
        <div class="report-title">Cancellation Report - {{ $terminal->name }} ({{ $terminal->code }})</div>
        <div class="report-info">
            Period: {{ $startDate->format('d M Y') }} to {{ $endDate->format('d M Y') }} |
            Generated on: {{ now()->format('d M Y, H:i') }}
        </div>
    </div>

    <!-- Applied Filters -->
    @if(array_filter($filters))
    <div class="filters-info">
        <div class="filters-title">Applied Filters:</div>
        @if(!empty($filters['route_id']))
            Route: {{ $cancelledBookings->first()?->trip?->route?->name ?? 'N/A' }} |
        @endif
        @if(!empty($filters['cancelled_by_user_id']))
            Cancelled By: {{ $cancelledBookings->first()?->cancelledByUser?->name ?? 'N/A' }} |
        @endif
        @if(!empty($filters['cancelled_by_type']))
            Type: {{ ucfirst($filters['cancelled_by_type']) }} |
        @endif
        @if(!empty($filters['payment_method']))
            Payment: {{ ucfirst($filters['payment_method']) }} |
        @endif
        @if(!empty($filters['channel']))
            Channel: {{ ucfirst($filters['channel']) }} |
        @endif
        @if(!empty($filters['is_advance']))
            Booking Type: {{ $filters['is_advance'] === '1' ? 'Advance' : 'Regular' }}
        @endif
    </div>
    @endif

    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card danger">
            <div class="summary-value">{{ $stats['total_cancellations'] }}</div>
            <div class="summary-label">Total Cancellations</div>
        </div>
        <div class="summary-card warning">
            <div class="summary-value">PKR {{ number_format($stats['total_refund_amount'], 0) }}</div>
            <div class="summary-label">Total Refund Amount</div>
        </div>
        <div class="summary-card info">
            <div class="summary-value">{{ $stats['total_cancelled_seats'] }}</div>
            <div class="summary-label">Cancelled Seats</div>
        </div>
        <div class="summary-card secondary">
            <div class="summary-value">{{ $stats['total_cancelled_passengers'] }}</div>
            <div class="summary-label">Cancelled Passengers</div>
        </div>
    </div>

    <!-- Cancelled Bookings Table -->
    <div class="section">
        <div class="section-title">Cancelled Bookings Details</div>
        <table>
            <thead>
                <tr>
                    <th>Booking #</th>
                    <th>Booking Date</th>
                    <th>Cancelled Date</th>
                    <th>Route</th>
                    <th>Passengers</th>
                    <th>Seats</th>
                    <th>Channel</th>
                    <th>Payment</th>
                    <th>Amount</th>
                    <th>Cancelled By</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cancelledBookings as $booking)
                <tr>
                    <td>
                        <span class="badge badge-danger">#{{ $booking->booking_number }}</span>
                    </td>
                    <td>{{ $booking->created_at->format('d M Y, H:i') }}</td>
                    <td>{{ $booking->cancelled_at ? $booking->cancelled_at->format('d M Y, H:i') : '-' }}</td>
                    <td>
                        <strong>{{ $booking->fromStop?->terminal?->code ?? 'N/A' }} → {{ $booking->toStop?->terminal?->code ?? 'N/A' }}</strong>
                    </td>
                    <td>{{ $booking->passengers->pluck('name')->join(', ') ?: 'No passengers' }}</td>
                    <td>{{ $booking->seats->pluck('seat_number')->join(', ') }}</td>
                    <td>
                        @php
                            try {
                                $channel = \App\Enums\ChannelEnum::from($booking->channel ?? '');
                                $channelClass = match($channel->value) {
                                    'counter' => 'badge-primary',
                                    'online' => 'badge-success',
                                    'phone' => 'badge-info',
                                    default => 'badge-secondary'
                                };
                                echo '<span class="badge ' . $channelClass . '">' . $channel->getLabel() . '</span>';
                            } catch (\ValueError $e) {
                                echo '<span class="badge badge-secondary">' . ($booking->channel ?? 'N/A') . '</span>';
                            }
                        @endphp
                    </td>
                    <td>
                        @php
                            try {
                                $method = \App\Enums\PaymentMethodEnum::from($booking->payment_method ?? '');
                                $methodClass = match($method->value) {
                                    'cash' => 'badge-success',
                                    'card' => 'badge-info',
                                    'mobile_wallet' => 'badge-warning',
                                    default => 'badge-secondary'
                                };
                                echo '<span class="badge ' . $methodClass . '">' . $method->getLabel() . '</span>';
                            } catch (\ValueError $e) {
                                echo '<span class="badge badge-secondary">' . ucfirst($booking->payment_method ?? 'Unknown') . '</span>';
                            }
                        @endphp
                    </td>
                    <td class="text-right"><strong>PKR {{ number_format($booking->final_amount, 0) }}</strong></td>
                    <td>
                        <strong>{{ $booking->cancelledByUser?->name ?? 'System' }}</strong><br>
                        <span class="badge badge-warning">{{ ucfirst($booking->cancelled_by_type ?? 'unknown') }}</span>
                    </td>
                    <td class="reason-cell">{{ $booking->cancellation_reason ?? 'No reason provided' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center">No cancelled bookings found for this period.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Breakdown by Cancelled Type -->
    @if(count($stats['by_cancelled_type']) > 0)
    <div class="section">
        <div class="section-title">Cancellations by User Type</div>
        <table style="width: 50%; float: left; margin-right: 2%;">
            <thead>
                <tr>
                    <th>User Type</th>
                    <th>Count</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stats['by_cancelled_type'] as $type => $data)
                <tr>
                    <td><strong>{{ ucfirst($type) }}</strong></td>
                    <td class="text-center">{{ $data['count'] }}</td>
                    <td class="text-right">PKR {{ number_format($data['amount'], 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Breakdown by Payment Method -->
    @if(count($stats['by_payment_method']) > 0)
    <div class="section">
        <div class="section-title">Cancellations by Payment Method</div>
        <table style="width: 48%; float: right;">
            <thead>
                <tr>
                    <th>Payment Method</th>
                    <th>Count</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stats['by_payment_method'] as $method => $data)
                <tr>
                    <td><strong>{{ ucfirst(str_replace('_', ' ', $method)) }}</strong></td>
                    <td class="text-center">{{ $data['count'] }}</td>
                    <td class="text-right">PKR {{ number_format($data['amount'], 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div style="clear: both;"></div>

    <!-- Top Cancellation Reasons -->
    @if(count($cancellationReasons) > 0)
    <div class="section">
        <div class="section-title">Top Cancellation Reasons</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 70%;">Cancellation Reason</th>
                    <th style="width: 15%;">Count</th>
                    <th style="width: 15%;">Percentage</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalReasons = $cancellationReasons->sum('count');
                @endphp
                @foreach($cancellationReasons as $reason)
                <tr>
                    <td>{{ $reason['reason'] ?: 'No reason provided' }}</td>
                    <td class="text-center">{{ $reason['count'] }}</td>
                    <td class="text-center">{{ $totalReasons > 0 ? number_format(($reason['count'] / $totalReasons) * 100, 1) : 0 }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>This report was generated automatically by {{ $generalSettings->company_name ?? 'Bus Management System' }} on {{ now()->format('d M Y \a\t H:i') }}</p>
        <p>For any queries regarding this report, please contact the administration.</p>
    </div>
</body>
</html>
