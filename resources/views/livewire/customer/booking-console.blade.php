<div>
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0">Customer Booking Console</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small">Origin</label>
                    <select class="form-select form-select-sm" wire:model="fromTerminalId">
                        <option value="">Select Origin</option>
                        @foreach ($terminals as $terminal)
                            <option value="{{ $terminal->id }}">
                                {{ $terminal->name }} ({{ $terminal->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label small">Destination</label>
                    <select class="form-select form-select-sm" wire:model="toTerminalId">
                        <option value="">Select Destination</option>
                        @foreach ($toTerminals as $terminal)
                            <option value="{{ $terminal['terminal_id'] }}">
                                {{ $terminal['name'] }} ({{ $terminal['code'] }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label small">Travel Date</label>
                    <input type="date"
                           class="form-control form-control-sm"
                           wire:model="travelDate"
                           min="{{ $minDate }}"
                           max="{{ $maxDate }}">
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <label class="form-label small">Departure Time / Trip</label>
                    <select class="form-select form-select-sm" wire:model="departureTimeId">
                        <option value="">Select Time</option>
                        @foreach ($departureTimes as $time)
                            <option value="{{ $time['id'] }}">
                                {{ $time['departure_at'] }} - {{ $time['route_name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small">Arrival Time</label>
                    <input type="text"
                           class="form-control form-control-sm"
                           value="{{ $arrivalTime }}"
                           readonly>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-primary btn-sm w-100" wire:click="loadTrip">
                        Load Trip & Seats
                    </button>
                </div>
            </div>

            @if ($fareError)
                <div class="alert alert-danger alert-dismissible fade show small mt-3" role="alert">
                    <strong>Fare Error:</strong> {{ $fareError }}
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"
                            wire:click="$set('fareError', null)"></button>
                </div>
            @endif
        </div>
    </div>

    @if ($showTripContent && $tripLoaded)
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0 fw-semibold text-dark">
                            @if ($tripData?->bus_id && $tripData?->bus)
                                {{ $tripData?->bus_name }} ({{ $tripData?->bus_registration_number }})
                            @else
                                <i class="fas fa-chair"></i> Seat Map
                            @endif
                        </h6>
                    </div>
                    <div class="card-body p-2">
                        <div class="seat-map-container">
                            <h6 class="text-center mb-2" style="color:#334155;font-weight:600;font-size:0.875rem;">
                                Select Your Seat
                            </h6>
                            <div class="seat-grid">
                                @php
                                    $totalSeats = $seatCount ?? (count($seatMap) > 0 ? max(array_keys($seatMap)) : 44);
                                    $lastRowCount = 5;
                                    $startLastRow = $totalSeats - ($lastRowCount - 1);
                                    $remaining = $totalSeats - $lastRowCount;
                                    $seatsPerRow = 4;
                                    $fullRows = floor($remaining / $seatsPerRow);
                                    $leftover = $remaining % $seatsPerRow;
                                    $current = 1;
                                @endphp

                                @for ($row = 1; $row <= $fullRows; $row++)
                                    @php
                                        $seats = [
                                            $current + 3,
                                            $current + 2,
                                            $current + 1,
                                            $current
                                        ];
                                        $current += 4;
                                    @endphp
                                    <div class="seat-row-container">
                                        <div class="seat-pair-right">
                                            @foreach ([$seats[0], $seats[1]] as $seat)
                                                @include('components.seat-button', ['seat' => $seat])
                                            @endforeach
                                        </div>
                                        <div class="seat-aisle">{{ $row }}</div>
                                        <div class="seat-pair-left">
                                            @foreach ([$seats[2], $seats[3]] as $seat)
                                                @include('components.seat-button', ['seat' => $seat])
                                            @endforeach
                                        </div>
                                    </div>
                                @endfor

                                @if ($leftover > 0)
                                    <div class="seat-row-container" style="justify-content:center;gap:0.5rem;">
                                        @for ($i = 0; $i < $leftover; $i++)
                                            @include('components.seat-button', ['seat' => $current + $i])
                                        @endfor
                                    </div>
                                    @php $current += $leftover; @endphp
                                @endif

                                <div class="seat-row-container last-row-5" style="display:flex;justify-content:center;gap:0.5rem">
                                    @for ($seat = $startLastRow; $seat <= $totalSeats; $seat++)
                                        @include('components.seat-button', ['seat' => $seat])
                                    @endfor
                                </div>
                            </div>

                            <div class="seat-legend mt-3">
                                <div class="seat-legend-item">
                                    <div class="seat-legend-indicator available"></div>
                                    <span>Available</span>
                                </div>
                                <div class="seat-legend-item">
                                    <div class="seat-legend-indicator selected"></div>
                                    <span>Selected</span>
                                </div>
                                <div class="seat-legend-item">
                                    <div class="seat-legend-indicator booked-male"></div>
                                    <span>Male</span>
                                </div>
                                <div class="seat-legend-item">
                                    <div class="seat-legend-indicator booked-female"></div>
                                    <span>Female</span>
                                </div>
                                <div class="seat-legend-item">
                                    <div class="seat-legend-indicator held"></div>
                                    <span>Held</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-clipboard-list"></i> Booking Summary
                        </h6>
                    </div>
                    <div class="card-body" style="padding:1rem;">
                        <div class="mb-3 p-2 bg-light rounded border border-secondary-subtle">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold mb-0 small">
                                    <i class="fas fa-users"></i> Passengers
                                </h6>
                                <div class="d-flex gap-2 align-items-center">
                                    <span class="badge bg-info">{{ count($selectedSeats) }} seat(s)</span>
                                    @if (count($selectedSeats) > 0 && count($passengers) < count($selectedSeats))
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                wire:click="addPassenger">
                                            <i class="fas fa-plus-circle"></i> Add Passenger
                                        </button>
                                    @endif
                                </div>
                            </div>
                            <p class="text-muted small mb-2">
                                <strong>Required:</strong> At least 1 passenger information required.
                                @if (count($selectedSeats) > 0)
                                    You can add up to {{ count($selectedSeats) }} passenger(s) for
                                    {{ count($selectedSeats) }} selected seat(s).
                                @endif
                            </p>
                            <div style="max-height: 260px; overflow-y: auto;">
                                @foreach ($passengers as $index => $passenger)
                                    <div class="card mb-3 border-2 {{ $passenger['is_required'] ? '' : 'border-warning' }}">
                                        <div class="card-header {{ $passenger['is_required'] ? '' : 'bg-warning-subtle' }}">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0">
                                                    @if ($passenger['is_required'])
                                                        <i class="fas fa-user"></i> Passenger {{ $index + 1 }}
                                                        <span class="badge bg-danger ms-2">Required</span>
                                                    @else
                                                        <i class="fas fa-user-plus"></i> Passenger {{ $index + 1 }}
                                                    @endif
                                                </h6>
                                                @if (! $passenger['is_required'])
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1"
                                                            wire:click="removePassenger({{ $index }})">
                                                        <i class="bx bx-trash" style="font-size:1rem;"></i>
                                                        <span>Remove</span>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-2">
                                                <div class="col-lg-4 col-md-12">
                                                    <label class="form-label small">CNIC</label>
                                                    <input type="text"
                                                           class="form-control form-control-sm @error("passengers.{$index}.cnic") is-invalid @enderror"
                                                           wire:model.blur="passengers.{{ $index }}.cnic"
                                                           placeholder="12345-9999999-1"
                                                           maxlength="15">
                                                    @error("passengers.{$index}.cnic")
                                                    <small class="text-danger d-block">{{ $message }}</small>
                                                    @enderror
                                                </div>
                                                <div class="col-lg-6 col-md-12">
                                                    <label class="form-label small">Name <span class="text-danger">*</span></label>
                                                    <input type="text"
                                                           class="form-control form-control-sm @error("passengers.{$index}.name") is-invalid @enderror"
                                                           wire:model="passengers.{{ $index }}.name"
                                                           placeholder="Full Name">
                                                    @error("passengers.{$index}.name")
                                                    <small class="text-danger d-block">{{ $message }}</small>
                                                    @enderror
                                                </div>
                                                <div class="col-lg-3 col-md-6">
                                                    <label class="form-label small">Age <span class="text-danger">*</span></label>
                                                    <input type="number"
                                                           class="form-control form-control-sm @error("passengers.{$index}.age") is-invalid @enderror"
                                                           wire:model="passengers.{{ $index }}.age"
                                                           min="1" max="120"
                                                           placeholder="Age">
                                                    @error("passengers.{$index}.age")
                                                    <small class="text-danger d-block">{{ $message }}</small>
                                                    @enderror
                                                </div>
                                                <div class="col-lg-3 col-md-6">
                                                    <label class="form-label small">Gender <span class="text-danger">*</span></label>
                                                    <select
                                                        class="form-select form-select-sm @error("passengers.{$index}.gender") is-invalid @enderror"
                                                        wire:model="passengers.{{ $index }}.gender">
                                                        <option value="">Select</option>
                                                        <option value="male">Male</option>
                                                        <option value="female">Female</option>
                                                    </select>
                                                    @error("passengers.{$index}.gender")
                                                    <small class="text-danger d-block">{{ $message }}</small>
                                                    @enderror
                                                </div>
                                                <div class="col-lg-4 col-md-6">
                                                    <label class="form-label small">Phone</label>
                                                    <input type="tel"
                                                           class="form-control form-control-sm @error("passengers.{$index}.phone") is-invalid @enderror"
                                                           wire:model.blur="passengers.{{ $index }}.phone"
                                                           placeholder="03XX-XXXXXXX">
                                                    @error("passengers.{$index}.phone")
                                                    <small class="text-danger d-block">{{ $message }}</small>
                                                    @enderror
                                                </div>
                                                <div class="col-lg-8 col-md-6">
                                                    <label class="form-label small">Email</label>
                                                    <input type="email"
                                                           class="form-control form-control-sm @error("passengers.{$index}.email") is-invalid @enderror"
                                                           wire:model.blur="passengers.{{ $index }}.email"
                                                           placeholder="email@example.com">
                                                    @error("passengers.{$index}.email")
                                                    <small class="text-danger d-block">{{ $message }}</small>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="row g-2 align-items-center">
                                <div class="col-md-4">
                                    <div class="small text-muted">Base Fare (per seat)</div>
                                    <div class="fw-semibold">
                                        {{ number_format($baseFare, 0) }}
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="small text-muted">Total Seats</div>
                                    <div class="fw-semibold">{{ count($selectedSeats) }}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="small text-muted">Total Amount</div>
                                    <div class="fw-bold text-success">
                                        {{ number_format($finalAmount, 0) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 d-flex justify-content-end">
                            <button type="button"
                                    class="btn btn-primary btn-sm"
                                    wire:click="confirmBooking">
                                Confirm Booking
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>


