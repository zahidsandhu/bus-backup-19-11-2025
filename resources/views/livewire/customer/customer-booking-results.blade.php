<div>
    <div class="mb-3">
        <h5 class="mb-1">
            {{ $fromTerminal?->name }} ({{ $fromTerminal?->code }})
            →
            {{ $toTerminal?->name }} ({{ $toTerminal?->code }})
        </h5>
        <p class="text-muted mb-0">
            Travel Date: {{ \Carbon\Carbon::parse($travelDate)->format('d M Y') }}
        </p>
    </div>

    @if (empty($trips))
        <div class="alert alert-info mb-0">
            No trips found for selected date and route.
        </div>
    @else
        <div class="list-group">
            @foreach ($trips as $trip)
                <button type="button"
                        wire:click="selectTrip({{ $trip['id'] }})"
                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold">
                            {{ $trip['departure_time'] }}
                        </div>
                        <small class="text-muted">
                            Arrival: {{ $trip['arrival_time'] }}
                        </small>
                    </div>
                    <span class="badge bg-primary rounded-pill">
                        Select
                    </span>
                </button>
            @endforeach
        </div>
    @endif
</div>


