@php($booking = $booking ?? null)

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Your Ticket #{{ $booking->booking_number }}</title>
</head>
<body>
    <h2>Your Ticket #{{ $booking->booking_number }}</h2>
    <p>Dear {{ $booking->user?->name ?? 'Customer' }},</p>

    <p>Your booking has been confirmed. Below are your trip details:</p>

    <ul>
        <li>
            Route:
            {{ $booking->fromStop?->terminal?->name }}
            →
            {{ $booking->toStop?->terminal?->name }}
        </li>
        <li>
            Departure:
            {{ optional($booking->trip?->departure_datetime)->format('d M Y H:i') }}
        </li>
        <li>
            Seats:
            {{ $booking->seats->pluck('seat_number')->implode(', ') }}
        </li>
        <li>
            Total Paid:
            {{ $booking->currency }} {{ number_format($booking->final_amount, 2) }}
        </li>
    </ul>

    <p>Thank you for traveling with us.</p>
</body>
</html>


