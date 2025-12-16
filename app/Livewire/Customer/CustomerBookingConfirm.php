<?php

namespace App\Livewire\Customer;

use App\Models\Trip;
use App\Services\BookingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CustomerBookingConfirm extends Component
{
    public $draft;

    public $bookingNumber;

    public $bookingId;

    public function mount(): void
    {
        $this->draft = Session::get('customer_booking_draft');

        if (! $this->draft) {
            throw ValidationException::withMessages([
                'booking' => 'No booking in progress. Please start again.',
            ]);
        }
    }

    public function confirm(): void
    {
        $draft = $this->draft;

        /** @var BookingService $service */
        $service = app(BookingService::class);

        $data = [
            'trip_id' => $draft['trip_id'],
            'from_stop_id' => $draft['from_stop_id'],
            'to_stop_id' => $draft['to_stop_id'],
            'from_trip_stop_id' => $draft['from_trip_stop_id'],
            'to_trip_stop_id' => $draft['to_trip_stop_id'],
            'terminal_id' => $draft['from_terminal_id'],
            'seat_numbers' => $draft['seat_numbers'],
            'seats_data' => collect($draft['seat_numbers'])->map(fn ($sn) => [
                'seat_number' => $sn,
                'gender' => $draft['passengers'][0]['gender'] ?? 'male',
            ])->toArray(),
            'passengers' => $draft['passengers'],
            'channel' => 'online',
            'payment_method' => 'jazzcash',
            'payment_gateway' => 'jazzcash',
            'online_transaction_id' => null,
            'total_fare' => $draft['total_fare'],
            'discount_amount' => $draft['discount_amount'],
            'tax_amount' => 0,
            'final_amount' => $draft['final_amount'],
            'notes' => null,
            'user_id' => $draft['user_id'],
            'payment_received_from_customer' => 0,
            'return_after_deduction_from_customer' => 0,
            'status' => 'hold',
            'payment_status' => 'unpaid',
            'reserved_until' => now()->addMinutes(10),
        ];

        try {
            $booking = $service->createBooking($data, Auth::user());
        } catch (\Throwable $e) {
            report($e);

            throw ValidationException::withMessages([
                'booking' => 'Unable to create booking: '.$e->getMessage(),
            ]);
        }

        Session::forget('customer_booking_draft');

        $this->bookingNumber = $booking->booking_number;
        $this->bookingId = $booking->id;

        $this->redirectRoute('frontend.bookings.payment', $booking);
    }

    public function getTripProperty(): ?Trip
    {
        if (! $this->draft) {
            return null;
        }

        return Trip::with('route', 'bus')->find($this->draft['trip_id']);
    }

    public function render()
    {
        return view('livewire.customer.customer-booking-confirm', [
            'trip' => $this->trip,
        ]);
    }
}


