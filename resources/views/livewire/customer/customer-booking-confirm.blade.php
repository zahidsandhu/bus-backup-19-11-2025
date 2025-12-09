<div>
    @if ($bookingNumber)
        <div class="alert alert-success">
            <h5 class="mb-1">Booking Confirmed</h5>
            <p class="mb-0">
                Your booking number is <strong>{{ $bookingNumber }}</strong>.
            </p>
        </div>
    @else
        <div class="mb-3">
            <h5 class="mb-1">Review & Confirm</h5>
            <p class="text-muted mb-0">
                Trip #{{ $trip?->id }} | Total Amount: {{ number_format($draft['final_amount'], 0) }}
            </p>
        </div>
        <button type="button" class="btn btn-primary" wire:click="confirm">
            Confirm Booking
        </button>
    @endif
</div>


