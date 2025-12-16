@extends('frontend.layouts.app')

@section('title', 'Payment Successful')

@section('content')
    <div class="container py-5">
        <div class="alert alert-success">
            <h4 class="alert-heading">Payment Successful</h4>
            <p>Your booking #{{ $booking->booking_number }} has been confirmed.</p>
            <hr>
            <a href="{{ route('frontend.bookings.success', $booking) }}" class="btn btn-primary">
                View Ticket
            </a>
        </div>
    </div>
@endsection


