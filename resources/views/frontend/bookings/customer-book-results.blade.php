@extends('frontend.layouts.app')

@section('title', 'Select Trip')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center mb-0">Select Trip</h3>
                    </div>
                    <div class="card-body">
                        @livewire('customer.customer-booking-results', [
                            'fromTerminalId' => request('from_terminal_id'),
                            'toTerminalId' => request('to_terminal_id'),
                            'date' => request('date'),
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


