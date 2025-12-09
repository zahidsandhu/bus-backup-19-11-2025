@extends('frontend.layouts.app')

@section('title', 'Customer Booking Console')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-11">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center mb-0">Book Your Ticket</h3>
                    </div>
                    <div class="card-body">
                        @livewire('customer.booking-console')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


