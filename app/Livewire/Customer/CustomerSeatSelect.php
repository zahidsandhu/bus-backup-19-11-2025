<?php

namespace App\Livewire\Customer;

use App\Models\Fare;
use App\Models\RouteStop;
use App\Models\Trip;
use App\Models\TripStop;
use App\Models\BookingPassenger;
use App\Services\SeatAvailabilityService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CustomerSeatSelect extends Component
{
    public $tripId;

    public $fromRouteStopId;

    public $toRouteStopId;

    public $fromTripStopId;

    public $toTripStopId;

    public $fromTerminalId;

    public $toTerminalId;

    public $travelDate;

    public $trip;

    public $seatMap = [];

    public $seatCount = 0;

    public $selectedSeats = [];

    public $lockedSeats = [];

    public $passengers = [];

    public $baseFare = 0;

    public $discountPerSeat = 0;

    public $totalFare = 0;

    public $finalAmount = 0;

    public function mount(
        Trip $trip,
        int $from_stop_id,
        int $to_stop_id,
        int $from_terminal_id,
        int $to_terminal_id,
        string $date
    ): void {
        $this->trip = $trip->load(['bus.busLayout', 'route']);
        $this->tripId = $trip->id;
        $this->fromRouteStopId = $from_stop_id;
        $this->toRouteStopId = $to_stop_id;
        $this->fromTerminalId = $from_terminal_id;
        $this->toTerminalId = $to_terminal_id;
        $this->travelDate = $date;

        $fromRouteStop = RouteStop::findOrFail($this->fromRouteStopId);
        $toRouteStop = RouteStop::findOrFail($this->toRouteStopId);

        if ($fromRouteStop->sequence >= $toRouteStop->sequence) {
            throw ValidationException::withMessages([
                'segment' => 'Invalid segment selection.',
            ]);
        }

        $fromTripStop = TripStop::where('trip_id', $this->tripId)
            ->where('terminal_id', $fromRouteStop->terminal_id)
            ->where('sequence', $fromRouteStop->sequence)
            ->firstOrFail();

        $toTripStop = TripStop::where('trip_id', $this->tripId)
            ->where('terminal_id', $toRouteStop->terminal_id)
            ->where('sequence', $toRouteStop->sequence)
            ->firstOrFail();

        $this->fromTripStopId = $fromTripStop->id;
        $this->toTripStopId = $toTripStop->id;

        $this->loadSeatMap();
        $this->loadFare();

        $this->passengers = [
            [
                'name' => '',
                'age' => '',
                'gender' => '',
                'cnic' => '',
                'phone' => '',
                'email' => '',
                'is_required' => true,
            ],
        ];
    }

    public function loadSeatMap(): void
    {
        /** @var SeatAvailabilityService $service */
        $service = app(SeatAvailabilityService::class);

        $this->seatMap = $service->getAvailableSeats(
            $this->tripId,
            $this->fromTripStopId,
            $this->toTripStopId
        );

        $this->seatCount = count($this->seatMap);
        $this->selectedSeats = [];
    }

    public function loadFare(): void
    {
        $fare = Fare::active()
            ->where('from_terminal_id', $this->fromTerminalId)
            ->where('to_terminal_id', $this->toTerminalId)
            ->first();

        if (! $fare) {
            throw ValidationException::withMessages([
                'fare' => 'No fare defined for selected segment.',
            ]);
        }

        $this->baseFare = (float) $fare->final_fare;
        $this->discountPerSeat = (float) $fare->getDiscountAmount();

        $this->recalculateTotals();
    }

    public function selectSeat(int $seatNumber): void
    {
        if (! isset($this->seatMap[$seatNumber])) {
            return;
        }

        if ($this->seatMap[$seatNumber]['status'] === 'booked') {
            return;
        }

        if (isset($this->selectedSeats[$seatNumber])) {
            unset($this->selectedSeats[$seatNumber]);
        } else {
            if (count($this->selectedSeats) >= $this->seatCount) {
                return;
            }

            $this->selectedSeats[$seatNumber] = [
                'seat_number' => $seatNumber,
            ];
        }

        if (count($this->selectedSeats) === 0) {
            $this->passengers = [
                [
                    'name' => '',
                    'age' => '',
                    'gender' => '',
                    'cnic' => '',
                    'phone' => '',
                    'email' => '',
                    'is_required' => true,
                ],
            ];
        }

        $this->recalculateTotals();
    }

    public function addPassenger(): void
    {
        $selectedSeatCount = count($this->selectedSeats);

        if ($selectedSeatCount === 0) {
            return;
        }

        if (count($this->passengers) >= $selectedSeatCount) {
            return;
        }

        $this->passengers[] = [
            'name' => '',
            'age' => '',
            'gender' => '',
            'cnic' => '',
            'phone' => '',
            'email' => '',
            'is_required' => false,
        ];
    }

    public function removePassenger(int $index): void
    {
        if (! isset($this->passengers[$index])) {
            return;
        }

        if ($this->passengers[$index]['is_required']) {
            return;
        }

        unset($this->passengers[$index]);
        $this->passengers = array_values($this->passengers);

        if (! empty($this->passengers)) {
            $this->passengers[0]['is_required'] = true;
        }
    }

    public function updatedPassengers($value, $key): void
    {
        if (! is_string($key) || ! str_contains($key, '.')) {
            return;
        }

        [$index, $field] = explode('.', $key, 2);

        if ($field !== 'cnic') {
            return;
        }

        $index = (int) $index;

        if (! isset($this->passengers[$index])) {
            return;
        }

        $cnic = trim((string) $value);

        if ($cnic === '') {
            $this->resetErrorBag('passengers.'.$index.'.cnic');

            return;
        }

        if (! preg_match('/^[0-9]{5}-[0-9]{7}-[0-9]{1}$/', $cnic)) {
            $this->addError('passengers.'.$index.'.cnic', 'CNIC format is invalid. Use 12345-9999999-1.');

            return;
        }

        $this->resetErrorBag('passengers.'.$index.'.cnic');

        $existingPassenger = BookingPassenger::query()
            ->where('cnic', $cnic)
            ->latest('id')
            ->first();

        if (! $existingPassenger) {
            return;
        }

        $gender = null;

        if ($existingPassenger->gender) {
            if ($existingPassenger->gender instanceof \App\Enums\GenderEnum) {
                $gender = $existingPassenger->gender->value;
            } elseif (is_string($existingPassenger->gender)) {
                $gender = $existingPassenger->gender;
            }
        }

        $this->passengers[$index]['cnic'] = $cnic;
        $this->passengers[$index]['name'] = $existingPassenger->name ?? '';
        $this->passengers[$index]['age'] = $existingPassenger->age ?? '';
        $this->passengers[$index]['gender'] = $gender ?? '';
        $this->passengers[$index]['phone'] = $existingPassenger->phone ?? '';
        $this->passengers[$index]['email'] = $existingPassenger->email ?? '';
    }

    public function recalculateTotals(): void
    {
        $seatCount = count($this->selectedSeats);

        if ($seatCount === 0) {
            $this->totalFare = 0;
            $this->finalAmount = 0;

            return;
        }

        $this->totalFare = $this->baseFare * $seatCount;
        $totalDiscount = $this->discountPerSeat * $seatCount;

        $this->finalAmount = max(0, $this->totalFare - $totalDiscount);
    }

    public function proceed(): void
    {
        $seatCount = count($this->selectedSeats);

        if ($seatCount === 0) {
            throw ValidationException::withMessages([
                'seats' => 'Please select at least one seat.',
            ]);
        }

        $rules = [
            'passengers' => 'required|array|min:1|max:'.$seatCount,
            'passengers.*.name' => 'required|string|max:100',
            'passengers.*.age' => 'required|integer|min:1|max:120',
            'passengers.*.gender' => 'required|in:male,female',
            'passengers.*.cnic' => 'nullable|string|regex:/^[0-9]{5}-[0-9]{7}-[0-9]{1}$/|max:15',
            'passengers.*.phone' => 'nullable|string|max:20',
            'passengers.*.email' => 'nullable|email|max:100',
        ];

        $this->validate($rules);

        $seatNumbers = array_keys($this->selectedSeats);

        Session::put('customer_booking_draft', [
            'trip_id' => $this->tripId,
            'from_stop_id' => $this->fromRouteStopId,
            'to_stop_id' => $this->toRouteStopId,
            'from_trip_stop_id' => $this->fromTripStopId,
            'to_trip_stop_id' => $this->toTripStopId,
            'from_terminal_id' => $this->fromTerminalId,
            'to_terminal_id' => $this->toTerminalId,
            'travel_date' => $this->travelDate,
            'seat_numbers' => $seatNumbers,
            'passengers' => $this->passengers,
            'total_fare' => $this->totalFare,
            'discount_amount' => $this->discountPerSeat * $seatCount,
            'final_amount' => $this->finalAmount,
            'currency' => $this->trip->route->base_currency ?? 'PKR',
            'user_id' => Auth::id(),
        ]);

        redirect()->route('customer.book.confirm');
    }

    public function render()
    {
        return view('livewire.customer.customer-seat-select', [
            'trip' => $this->trip,
        ]);
    }
}


