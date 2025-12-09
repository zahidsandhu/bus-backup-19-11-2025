<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Head Office Report</title>

    <style>
        /* General Settings */
        body {
            font-family: Arial, sans-serif;
            margin: 10px;
            font-size: 11px; /* reduced font size */
        }

        .header {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
        }

        .sub-header {
            text-align: center;
            font-size: 14px;
            font-weight: 700;
        }

        .info-lines div {
            font-size: 10px;
            margin-bottom: 3px;
        }

        .line-between, .line-multi {
            display: flex;
            justify-content: space-between;
            gap: 5px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 2px 4px;
            text-align: left;
        }

        .data-table th {
            font-weight: bold;
        }

        .text-align {
            text-align: right;
        }

        .right-summary {
            width: 200px;
            font-weight: bold;
            font-size: 10px;
        }

        .summary-line {
            display: flex;
            justify-content: space-between;
            padding: 1px 0;
        }

        .summary-line.online {
            border-top: 1px dashed #000;
        }

        .summary-line.balance {
            border-top: 2px solid #000;
        }

        .date-time {
            font-size: 8px;
            font-style: italic;
            text-align: right;
            margin-top: 2px;
        }

        .footer-table-container {
            border: 1px solid #000;
            padding: 2px;
            margin-top: 5px;
            width: 100%;
            page-break-inside: avoid;
            font-size: 10px;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .footer-table th, .footer-table td {
            padding: 2px 3px;
            text-align: right;
            border: 1px solid #000;
            font-weight: bold;
        }

        .footer-table thead th {
            font-weight: bold;
            text-align: center;
        }

        .dest-cell {
            text-align: left !important;
        }

        .total-col-header, .total-col-data {
            width: 15%;
        }

        .final-total td {
            border-bottom: 2px solid #000;
        }

        /* Print-specific adjustments */
        @media print {
            body {
                margin: 5mm;
            }

            .data-table, .footer-table {
                page-break-inside: avoid;
            }
        }
    </style>

    <script>
        window.onload = function () {
            window.print();
        };
    </script>
</head>

<body>
    <div>
        <div class="header">{{ $companyInitials }}</div>
        <div class="sub-header">{{ $companyTagline }}</div>

        <div class="info-lines">
            <div class="line-between">
                <span><strong>Route:</strong> {{ $routeCode }} <span>{{ $departureTime }}</span></span>
                <span><strong>Date:</strong> {{ $departureDate }}</span>
            </div>

            <div class="line-multi">
                <span><strong>Vehicle No:</strong> {{ $vehicleNo }}</span>
                <span><strong>Arrival Time:</strong> {{ $arrivalTime }}</span>
                <span><strong>Departure Time:</strong> {{ $departureTime }}</span>
                <span><strong>Voucher No:</strong> {{ $voucherNo }}</span>
            </div>

            <div class="line-between">
                <span><strong>Driver:</strong> {{ $driverName }}</span>
                <span><strong>Host:</strong> {{ $hostName }}</span>
            </div>
        </div>

        <table class="data-table">
            <tr>
                <th>Seat</th>
                <th>Name</th>
                <th>CNIC</th>
                <th>Cell</th>
                <th>Via</th>
                <th>By</th>
                <th>From</th>
                <th>Desti.</th>
                <th>Fare</th>
            </tr>
            @foreach ($passengers as $passenger)
            <tr>
                <td>{{ $passenger['seat_number'] }}</td>
                <td>{{ $passenger['name'] }}</td>
                <td>{{ $passenger['cnic'] }}</td>
                <td>{{ $passenger['phone'] }}</td>
                <td>{{ $passenger['via'] }}</td>
                <td>{{ $passenger['agent_name'] }}</td>
                <td>{{ $passenger['from_code'] }}</td>
                <td>{{ $passenger['to_code'] }}</td>
                <td>{{ number_format($passenger['fare'], 0) }}</td>
            </tr>
            @endforeach
        </table>

        <div style="font-size:10px; font-weight:bold; border-top:2px solid #000; padding-top:5px; border-bottom:1px solid #000;">
            <table style="width:100%; font-size:10px; font-weight:bold;">
                <tr>
                    <td class="text-align">Printed By:</td>
                    <td>{{ $currentUserName }}</td>
                    <td class="text-align">Total Pax:</td>
                    <td>{{ $totalPassengers }}</td>
                    <td class="text-align">Total Fare:</td>
                    <td>{{ number_format($totalFare, 0) }}</td>
                </tr>
            </table>
            <br>
            <table style="width:100%; font-size:10px; font-weight:bold; border-top:1px solid #000;">
                <tr>
                    <td class="text-align">Other Income:</td>
                    <td>{{ number_format($otherIncome, 0) }}</td>
                </tr>
            </table>
        </div>

        <table style="width:100%; font-size:10px; font-weight:bold; margin:5px 0; border-bottom:1px solid #000;">
            <tr>
                <td class="text-align">Adda:</td>
                <td>{{ number_format($addaExpense, 0) }}</td>
                <td class="text-align">Hakri:</td>
                <td>{{ number_format($hakriExpense, 0) }}</td>
                <td class="text-align">Others:</td>
                <td>{{ number_format($otherExpense, 0) }}</td>
                <td class="text-align">Total Expense:</td>
                <td>{{ number_format($totalExpenses, 0) }}</td>
            </tr>
        </table>

        <div class="right-summary">
            <div class="summary-line">
                <span>Counter Sales:</span>
                <span>{{ number_format($counterSales, 0) }}</span>
            </div>
            <div class="summary-line online">
                <span>Online Sales:</span>
                <span>{{ number_format($onlineSales, 0) }}</span>
            </div>
            <div class="summary-line" style="border-top:1px solid #000;">
                <span>Total Expenses:</span>
                <span>{{ number_format($totalExpenses, 0) }}</span>
            </div>
            <div class="summary-line balance">
                <span>Balance:</span>
                <span>{{ number_format($balance, 0) }}</span>
            </div>
            <div class="date-time">{{ $printDateTime }}</div>
        </div>

        <div class="footer-table-container">
            <table class="footer-table">
                <thead>
                    <tr>
                        <td></td>
                        @foreach ($agents as $agent)
                        <th>{{ $agent }}</th>
                        @endforeach
                        <th class="total-col-header">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($destinations as $dest)
                    <tr>
                        <td class="dest-cell">{{ $dest }}</td>
                        @foreach ($agents as $agent)
                        <td>{{ number_format($agentBreakdown[$agent][$dest] ?? 0, 0) }}</td>
                        @endforeach
                        <td class="total-col-data">{{ number_format($destinationTotals[$dest] ?? 0, 0) }}</td>
                    </tr>
                    @endforeach
                    <tr class="final-total">
                        <td class="dest-cell">Total</td>
                        @foreach ($agents as $agent)
                        <td>{{ number_format($agentTotals[$agent] ?? 0, 0) }}</td>
                        @endforeach
                        <td class="total-col-data">{{ number_format($grandTotal, 0) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
