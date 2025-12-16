@extends('frontend.layouts.app')

@section('title', 'Payment Failed')

@section('content')
    <div class="container py-5">
        <div class="alert alert-danger">
            <h4 class="alert-heading">Payment Failed</h4>
            <p>{{ $error ?? 'Your payment could not be processed.' }}</p>
            <hr>
            <a href="{{ route('home') }}" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>
@endsection


