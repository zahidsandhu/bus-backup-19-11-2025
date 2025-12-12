<?php

namespace App\Livewire\Customer;

use App\Helpers\HolidayHelper;
use App\Livewire\Admin\BookingConsole as AdminBookingConsole;
use App\Models\RouteStop;
use App\Models\Route;
use App\Models\TimetableStop;
use App\Models\Trip;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingConsole extends AdminBookingConsole
{
    public function loadToTerminals(): void
    {
        dd($this->fromTerminalId);
        if (! $this->fromTerminalId) {
            $this->toTerminals = [];

            return;
        }

        $fromTerminalId = $this->fromTerminalId;

        $routes = Route::whereHas('routeStops', function ($q) use ($fromTerminalId) {
            $q->where('terminal_id', $fromTerminalId);
        })
            ->where('status', 'active')
            ->get();

        $terminals = collect();

        foreach ($routes as $route) {
            $stops = RouteStop::where('route_id', $route->id)
                ->where('online_booking_allowed', 1)
                ->where('online_time_table', 1)
                ->with('terminal:id,name,code')
                ->orderBy('sequence')
                ->get();

            $origin = $stops->firstWhere('terminal_id', $fromTerminalId);

            if (! $origin) {
                continue;
            }

            $filtered = $stops->filter(function ($stop) use ($origin) {
                return $stop->sequence > $origin->sequence;
            });

            foreach ($filtered as $stop) {
                $terminals->push([
                    'terminal_id' => $stop->terminal_id,
                    'name' => $stop->terminal->name,
                    'code' => $stop->terminal->code,
                    'sequence' => $stop->sequence,
                    'route_id' => $route->id,
                ]);
            }
        }

        $this->toTerminals = $terminals->unique('terminal_id')->values()->toArray();
    }

    public function confirmBooking(): void
    {
        $selectedSeatCount = count($this->selectedSeats);

        if ($selectedSeatCount === 0) {
            $this->dispatch('show-error', message: 'Please select at least one seat.');

            return;
        }

        if (count($this->passengers) === 0) {
            $this->updatePassengerForms();
            if (count($this->passengers) === 0) {
                $this->dispatch('show-error', message: 'Please provide at least one passenger information.');

                return;
            }
        }

        foreach ($this->selectedSeats as $seatNumber => $seatData) {
            if (empty($seatData['gender'])) {
                $this->dispatch('show-error', message: "Please select gender for seat {$seatNumber}.");

                return;
            }
        }

        $rules = [
            'passengers' => 'required|array|min:1|max:'.$selectedSeatCount,
            'passengers.*.name' => 'required|string|max:100',
            'passengers.*.age' => 'required|integer|min:1|max:120',
            'passengers.*.gender' => 'required|in:male,female',
            'passengers.*.cnic' => 'nullable|string|regex:/^[0-9]{5}-[0-9]{7}-[0-9]{1}$/|max:15',
            'passengers.*.phone' => 'nullable|string|max:20',
            'passengers.*.email' => 'nullable|email|max:100',
        ];

        $messages = [
            'passengers.min' => 'Please provide at least one passenger information.',
            'passengers.max' => 'You can add up to '.$selectedSeatCount.' passenger(s) for '.$selectedSeatCount.' selected seat(s).',
            'passengers.*.name.required' => 'Passenger name is required.',
            'passengers.*.age.required' => 'Passenger age is required.',
            'passengers.*.gender.required' => 'Passenger gender is required.',
        ];

        $this->validate($rules, $messages);

        try {
            DB::beginTransaction();

            $trip = Trip::with('route', 'stops')->findOrFail($this->tripId);

            $tripFromStop = $trip->stops->firstWhere('terminal_id', $this->fromTerminalId);
            $tripToStop = $trip->stops->firstWhere('terminal_id', $this->toTerminalId);

            if (! $tripFromStop || ! $tripToStop) {
                throw new \Exception('Selected terminals not found in trip.');
            }

            if ($tripFromStop->sequence >= $tripToStop->sequence) {
                throw new \Exception('Invalid segment selection.');
            }

            if (HolidayHelper::isHoliday($trip->departure_date)) {
                throw ValidationException::withMessages([
                    'date' => 'Bookings are closed during the holiday period.',
                ]);
            }

            if ($tripFromStop->departure_at) {
                $departureTime = $tripFromStop->departure_at;
                $minimumBookingTime = now()->addHours(2);

                if ($departureTime->lt($minimumBookingTime)) {
                    throw ValidationException::withMessages([
                        'departure_time' => 'Online bookings must be made at least 2 hours before departure. This trip departs too soon to book online.',
                    ]);
                }
            }

            $fromRouteStop = RouteStop::where('route_id', $trip->route_id)
                ->where('terminal_id', $this->fromTerminalId)
                ->where('sequence', $tripFromStop->sequence)
                ->firstOrFail();

            $toRouteStop = RouteStop::where('route_id', $trip->route_id)
                ->where('terminal_id', $this->toTerminalId)
                ->where('sequence', $tripToStop->sequence)
                ->firstOrFail();

            $seatNumbers = array_keys($this->selectedSeats);
            $seatsData = [];

            foreach ($this->selectedSeats as $seatNumber => $seatData) {
                $seatsData[] = [
                    'seat_number' => $seatNumber,
                    'gender' => $seatData['gender'],
                ];
            }

            $this->calculateFinal();

            $data = [
                'trip_id' => $trip->id,
                'from_stop_id' => $fromRouteStop->id,
                'to_stop_id' => $toRouteStop->id,
                'from_trip_stop_id' => $tripFromStop->id,
                'to_trip_stop_id' => $tripToStop->id,
                'terminal_id' => $this->fromTerminalId,
                'seat_numbers' => $seatNumbers,
                'seats_data' => $seatsData,
                'passengers' => $this->passengers,
                'channel' => 'online',
                'payment_method' => 'mobile_wallet',
                'online_transaction_id' => null,
                'total_fare' => $this->totalFare,
                'discount_amount' => $this->discountAmount * $selectedSeatCount,
                'tax_amount' => $this->taxAmount,
                'final_amount' => $this->finalAmount,
                'notes' => null,
                'user_id' => Auth::id(),
                'payment_received_from_customer' => 0,
                'return_after_deduction_from_customer' => 0,
                'status' => 'hold',
                'reserved_until' => Carbon::now()->addMinutes(15),
                'payment_status' => 'unpaid',
            ];

            /** @var BookingService $bookingService */
            $bookingService = app(BookingService::class);
            $booking = $bookingService->create($data, Auth::user());

            DB::commit();

            $this->resetBookingForm();
            $this->loadTrip();

            $this->dispatch('booking-success', [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'final_amount' => $booking->final_amount,
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('show-error', message: $e->getMessage());
        }
    }

    public function render()
    {
        $generalSettings = \App\Models\GeneralSetting::first();
        $minDate = Carbon::today();
        $maxDate = $generalSettings && $generalSettings->advance_booking_enable
            ? Carbon::today()->addDays($generalSettings->advance_booking_days ?? 7)
            : Carbon::today();

        return view('livewire.customer.booking-console', [
            'minDate' => $minDate->format('Y-m-d'),
            'maxDate' => $maxDate->format('Y-m-d'),
        ]);
    }
}


