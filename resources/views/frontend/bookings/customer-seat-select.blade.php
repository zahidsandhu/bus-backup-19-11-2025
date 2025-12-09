@extends('frontend.layouts.app')

@section('title', 'Select Seats')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-11">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center mb-0">Select Seats</h3>
                    </div>
                    <div class="card-body">
                        @livewire('customer.customer-seat-select', [
                            'trip' => $trip,
                            'from_stop_id' => request('from_stop_id'),
                            'to_stop_id' => request('to_stop_id'),
                            'from_terminal_id' => request('from_terminal_id'),
                            'to_terminal_id' => request('to_terminal_id'),
                            'date' => request('date'),
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


