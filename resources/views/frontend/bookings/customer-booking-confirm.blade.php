@extends('frontend.layouts.app')

@section('title', 'Confirm Booking')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center mb-0">Confirm Booking</h3>
                    </div>
                    <div class="card-body">
                        @livewire('customer.customer-booking-confirm')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


