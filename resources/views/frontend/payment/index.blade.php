@extends('frontend.layouts.app')

@section('title', 'Complete Payment')

@section('content')
    <section class="py-5 bg-light" style="min-height: calc(100vh - 300px);">
        <div class="container py-5">
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3 class="mb-2 fw-bold">
                                <i class="bi bi-receipt-cutoff me-2"></i>Complete Payment
                            </h3>
                            <div class="d-flex align-items-center gap-4 flex-wrap">
                                <div>
                                    <i class="bi bi-ticket-perforated me-2"></i>
                                    <strong>Booking #{{ $booking->booking_number }}</strong>
                                </div>
                                <div>
                                    <i class="bi bi-currency-exchange me-2"></i>
                                    <strong>{{ $booking->currency }} {{ number_format($booking->final_amount, 2) }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <div class="h4 mb-0 text-warning">
                                <i class="bi bi-clock me-2"></i>
                                <span>
                                    {{ optional($booking->reserved_until)->diffForHumans(null, true) ?? '10 minutes' }}
                                </span>
                            </div>
                            <small class="opacity-75">Time remaining to complete payment</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-lg border-0 mb-4">
                        <div class="card-header bg-white border-0">
                            <h4 class="mb-0 fw-bold">
                                <i class="bi bi-credit-card me-2 text-primary"></i>JazzCash Payment
                            </h4>
                        </div>
                        <div class="card-body">
                            <p class="mb-3">
                                You are paying
                                <strong>{{ $booking->currency }} {{ number_format($booking->final_amount, 2) }}</strong>
                                for booking <strong>#{{ $booking->booking_number }}</strong>.
                            </p>

                            <form id="jazzcash-payment-form" method="POST" action="{{ $jazzcashUrl }}">
                                @foreach($jazzcash as $key => $value)
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endforeach

                                <button type="submit" class="btn btn-lg btn-primary fw-bold">
                                    <i class="bi bi-credit-card me-2"></i>Pay with JazzCash
                                </button>
                            </form>

                            <small class="text-muted d-block mt-2">
                                You will be redirected to JazzCash to complete the payment securely.
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Booking Summary Sidebar -->
                <div class="col-lg-4">
                    <div class="card shadow-lg border-0" style="position: sticky; top: 100px;">
                        <div class="card-header bg-primary text-white border-0">
                            <h4 class="mb-0 fw-bold">
                                <i class="bi bi-receipt-cutoff me-2"></i>Booking Summary
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                    <span class="text-muted">Booking Number:</span>
                                    <strong class="text-dark">{{ $booking->booking_number }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                    <span class="text-muted">Seats:</span>
                                    <strong class="text-dark">{{ $booking->total_passengers }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                    <span class="text-muted">Subtotal:</span>
                                    <strong class="text-dark">{{ $booking->currency }} {{ number_format($booking->total_fare, 2) }}</strong>
                                </div>
                                @if($booking->discount_amount > 0)
                                <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                    <span class="text-muted">Discount:</span>
                                    <strong class="text-success">-{{ $booking->currency }} {{ number_format($booking->discount_amount, 2) }}</strong>
                                </div>
                                @endif
                                <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                    <span class="text-muted">Tax:</span>
                                    <strong class="text-dark">{{ $booking->currency }} {{ number_format($booking->tax_amount, 2) }}</strong>
                                </div>
                                <hr class="my-3">
                                <div class="d-flex justify-content-between mb-3">
                                    <h4 class="mb-0 fw-bold">Total:</h4>
                                    <h4 class="mb-0 fw-bold text-primary">
                                        {{ $booking->currency }} {{ number_format($booking->final_amount, 2) }}
                                    </h4>
                                </div>
                            </div>

                            <div class="alert alert-warning mb-0">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <small><strong>Important:</strong> Complete payment within 15 minutes or your booking will expire.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

