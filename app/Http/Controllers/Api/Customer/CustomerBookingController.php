<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Helpers\HolidayHelper;
use App\Enums\PlatformEnum;
use App\Http\Resources\Customer\BookingResource;
use App\Models\Booking;
use App\Models\Fare;
use App\Models\RouteStop;
use App\Models\TimetableStop;
use App\Models\Trip;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\DiscountService;
use App\Services\TripFactoryService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CustomerBookingController extends Controller
{
    public function __construct(
        private BookingService $bookingService,
        private AvailabilityService $availabilityService,
        private TripFactoryService $tripFactoryService,
        private DiscountService $discountService
    ) {
    }

    /**
     * Search available trips for the given criteria.
     */
    public function trips(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_terminal_id' => ['required', 'exists:terminals,id'],
            'to_terminal_id' => ['required', 'exists:terminals,id'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);

        try {
            if (HolidayHelper::isHoliday($validated['date'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bookings are closed during the holiday period.',
                ], 400);
            }

            if ($validated['from_terminal_id'] === $validated['to_terminal_id']) {
                throw new \Exception('From and To terminals must be different');
            }

            $selectedDate = $validated['date'];
            $now = now();
            $trips = [];

            $timetableStops = TimetableStop::where('terminal_id', $validated['from_terminal_id'])
                ->where('is_active', true)
                ->where('online_time_table', true)
                ->with('timetable.route')
                ->get();

            foreach ($timetableStops as $timetableStop) {
                if (! $timetableStop->timetable || ! $timetableStop->timetable->route) {
                    continue;
                }

                $routeStops = RouteStop::where('route_id', $timetableStop->timetable->route->id)
                    ->orderBy('sequence')
                    ->get();

                $fromStop = $routeStops->firstWhere('terminal_id', $validated['from_terminal_id']);
                $toStop = $routeStops->firstWhere('terminal_id', $validated['to_terminal_id']);

                if (! $fromStop || ! $toStop || $fromStop->sequence >= $toStop->sequence) {
                    continue;
                }

                if (! $timetableStop->departure_time) {
                    continue;
                }

                $fullDeparture = Carbon::parse($selectedDate.' '.$timetableStop->departure_time);
                $minimumBookingTime = $now->copy()->addHours(2);

                if ($fullDeparture->lt($minimumBookingTime)) {
                    continue;
                }

                $trip = Trip::where('timetable_id', $timetableStop->timetable_id)
                    ->whereDate('departure_date', $selectedDate)
                    ->first();

                if (! $trip) {
                    $trip = $this->tripFactoryService->createFromTimetable($timetableStop->timetable_id, $selectedDate);
                }

                $fare = Fare::active()
                    ->where('from_terminal_id', $validated['from_terminal_id'])
                    ->where('to_terminal_id', $validated['to_terminal_id'])
                    ->first();

                $trip->load('stops');
                $tripFromStop = $trip->stops->firstWhere('terminal_id', $validated['from_terminal_id']);
                $tripToStop = $trip->stops->firstWhere('terminal_id', $validated['to_terminal_id']);

                $availableSeats = 0;
                if ($tripFromStop && $tripToStop) {
                    $availableSeats = count($this->availabilityService->availableSeats(
                        $trip->id,
                        $tripFromStop->id,
                        $tripToStop->id
                    ));
                }

                $trips[] = [
                    'trip_id' => $trip->id,
                    'timetable_id' => $timetableStop->timetable_id,
                    'route_id' => $timetableStop->timetable->route->id,
                    'route_name' => $timetableStop->timetable->route->name,
                    'departure_time' => $timetableStop->departure_time,
                    'arrival_time' => $timetableStop->arrival_time,
                    'departure_datetime' => $fullDeparture->toDateTimeString(),
                    'fare' => $fare ? [
                        'final_fare' => (float) $fare->final_fare,
                        'currency' => $fare->currency,
                    ] : null,
                    'available_seats' => $availableSeats,
                    'bus_name' => $trip->bus?->name ?? 'TBA',
                ];
            }

            $sortedTrips = collect($trips)->sortBy('departure_datetime')->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'trips' => $sortedTrips,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get trip details, fare summary, and seat map.
     */
    public function tripDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'trip_id' => ['required', 'exists:trips,id'],
            'from_terminal_id' => ['required', 'exists:terminals,id'],
            'to_terminal_id' => ['required', 'exists:terminals,id'],
            'platform' => ['nullable', 'in:android,ios,web,counter'],
        ]);

        try {
            $platform = $validated['platform'] ?? PlatformEnum::ANDROID->value;

            $trip = Trip::with(['stops.terminal', 'route', 'bus'])->findOrFail($validated['trip_id']);
            $tripFromStop = $trip->stops->firstWhere('terminal_id', $validated['from_terminal_id']);
            $tripToStop = $trip->stops->firstWhere('terminal_id', $validated['to_terminal_id']);

            if (! $tripFromStop || ! $tripToStop) {
                throw new \Exception('Selected terminals not found in trip');
            }

            if ($tripFromStop->sequence >= $tripToStop->sequence) {
                throw new \Exception('Invalid segment selection');
            }

            if (HolidayHelper::isHoliday($trip->departure_date)) {
                throw new \Exception('Bookings are closed during the holiday period.');
            }

            if ($tripFromStop->departure_at) {
                $minimumBookingTime = now()->addHours(2);

                if ($tripFromStop->departure_at->lt($minimumBookingTime)) {
                    throw new \Exception('Online bookings must be made at least 2 hours before departure. This trip departs too soon.');
                }
            }

            $fare = Fare::active()
                ->where('from_terminal_id', $validated['from_terminal_id'])
                ->where('to_terminal_id', $validated['to_terminal_id'])
                ->first();

            if (! $fare) {
                throw new \Exception('No fare found for this route segment');
            }

            $baseFarePerSeat = (float) $fare->final_fare;
            $bookingDate = $trip->departure_date;
            $bookingTime = $tripFromStop->departure_at;

            $applicableDiscount = $this->discountService->findApplicableDiscounts(
                $trip->route,
                $platform,
                $bookingDate,
                $bookingTime
            )->first();

            $discountInfo = [
                'type' => null,
                'value' => null,
                'amount' => 0,
                'has_discount' => false,
            ];

            if ($applicableDiscount) {
                $discountInfo = [
                    'type' => $applicableDiscount->discount_type,
                    'value' => (float) $applicableDiscount->value,
                    'amount' => 0,
                    'has_discount' => true,
                ];
            }

            $seatCount = $this->availabilityService->seatCount($trip);
            $availableSeats = $this->availabilityService->availableSeats(
                $trip->id,
                $tripFromStop->id,
                $tripToStop->id
            );

            $seatMap = $this->buildSeatMap($trip, $tripFromStop, $tripToStop, $seatCount, $availableSeats);

            return response()->json([
                'success' => true,
                'data' => [
                    'trip' => [
                        'id' => $trip->id,
                        'route_name' => $trip->route?->name,
                        'bus_name' => $trip->bus?->name ?? 'TBA',
                        'departure_date' => $trip->departure_date?->format('Y-m-d'),
                        'departure_datetime' => $trip->departure_datetime?->format('Y-m-d H:i:s'),
                    ],
                    'from_stop' => [
                        'terminal_name' => $tripFromStop->terminal?->name,
                        'terminal_code' => $tripFromStop->terminal?->code,
                        'departure_at' => $tripFromStop->departure_at?->format('Y-m-d H:i:s'),
                    ],
                    'to_stop' => [
                        'terminal_name' => $tripToStop->terminal?->name,
                        'terminal_code' => $tripToStop->terminal?->code,
                        'arrival_at' => $tripToStop->arrival_at?->format('Y-m-d H:i:s'),
                    ],
                    'fare' => [
                        'base_fare' => $baseFarePerSeat,
                        'final_fare' => $baseFarePerSeat,
                        'currency' => $fare->currency,
                        'discount_type' => $discountInfo['type'],
                        'discount_value' => $discountInfo['value'],
                        'discount_amount' => $discountInfo['amount'],
                        'has_discount' => $discountInfo['has_discount'],
                    ],
                    'seat_map' => $seatMap,
                    'available_count' => count($availableSeats),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Create a new booking for the authenticated customer.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'trip_id' => ['required', 'exists:trips,id'],
            'from_terminal_id' => ['required', 'exists:terminals,id'],
            'to_terminal_id' => ['required', 'exists:terminals,id'],
            'seat_numbers' => ['required', 'array', 'min:1'],
            'seat_numbers.*' => ['integer'],
            'seats_data' => ['required', 'array', 'min:1'],
            'seats_data.*.seat_number' => ['required', 'integer'],
            'seats_data.*.gender' => ['required', 'string'],
            'passengers' => ['required', 'array', 'min:1'],
            'passengers.*.name' => ['required', 'string'],
            'passengers.*.gender' => ['nullable', 'string'],
            'passengers.*.cnic' => ['nullable', 'string'],
            'passengers.*.phone' => ['nullable', 'string'],
            'passengers.*.email' => ['nullable', 'string'],
            'total_fare' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'final_amount' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $trip = Trip::with('route')->findOrFail($validated['trip_id']);
            $fromTerminalId = $validated['from_terminal_id'];
            $toTerminalId = $validated['to_terminal_id'];

            $tripFromStop = $trip->stops()->where('terminal_id', $fromTerminalId)->firstOrFail();
            $tripToStop = $trip->stops()->where('terminal_id', $toTerminalId)->firstOrFail();

            if ($tripFromStop->sequence >= $tripToStop->sequence) {
                throw new \Exception('Invalid segment selection');
            }

            if (HolidayHelper::isHoliday($trip->departure_date)) {
                throw ValidationException::withMessages([
                    'date' => 'Bookings are closed during the holiday period.',
                ]);
            }

            // Check 2-hour booking restriction for online customers
            if ($tripFromStop->departure_at) {
                $departureTime = $tripFromStop->departure_at;
                $minimumBookingTime = now()->addHours(2);

                if ($departureTime->lt($minimumBookingTime)) {
                    throw ValidationException::withMessages([
                        'departure_time' => 'Online bookings must be made at least 2 hours before departure. This trip departs too soon to book online.',
                    ]);
                }
            }

            // Find RouteStop IDs
            $fromRouteStop = RouteStop::where('route_id', $trip->route_id)
                ->where('terminal_id', $fromTerminalId)
                ->where('sequence', $tripFromStop->sequence)
                ->firstOrFail();

            $toRouteStop = RouteStop::where('route_id', $trip->route_id)
                ->where('terminal_id', $toTerminalId)
                ->where('sequence', $tripToStop->sequence)
                ->firstOrFail();

            $data = [
                'trip_id' => $validated['trip_id'],
                'from_stop_id' => $fromRouteStop->id,
                'to_stop_id' => $toRouteStop->id,
                'from_trip_stop_id' => $tripFromStop->id,
                'to_trip_stop_id' => $tripToStop->id,
                'terminal_id' => $fromTerminalId,
                'seat_numbers' => $validated['seat_numbers'],
                'seats_data' => $validated['seats_data'],
                'passengers' => $validated['passengers'],
                'channel' => 'online',
                'payment_method' => 'mobile_wallet',
                'online_transaction_id' => null,
                'total_fare' => $validated['total_fare'],
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'final_amount' => $validated['final_amount'],
                'notes' => null,
                'user_id' => $user->id,
                'payment_received_from_customer' => 0,
                'return_after_deduction_from_customer' => 0,
                'status' => 'hold',
                'reserved_until' => now()->addMinutes(15),
                'payment_status' => 'unpaid',
            ];

            $booking = $this->bookingService->create($data, $user);

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully.',
                'data' => [
                    'booking' => new BookingResource($booking),
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get booking history for the authenticated customer.
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('profile');

        $userCnic = $user->profile?->cnic;

        $query = Booking::with([
            'trip.route',
            'trip.bus',
            'fromStop.terminal',
            'toStop.terminal',
            'seats',
            'passengers',
        ])->where(function ($q) use ($user, $userCnic) {
            $q->where('user_id', $user->id);

            if ($userCnic) {
                $q->orWhereHas('passengers', function ($qq) use ($userCnic) {
                    $qq->where('cnic', $userCnic);
                });
            }
        })->orderByDesc('created_at');

        $bookings = $query->paginate(15);

        return BookingResource::collection($bookings)
            ->additional([
                'success' => true,
            ])
            ->response();
    }

    /**
     * Confirm payment for a held booking.
     */
    public function pay(Request $request, Booking $booking): JsonResponse
    {
        if ($booking->user_id !== $request->user()?->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this booking.',
            ], 403);
        }

        if ($booking->status === 'expired' || ($booking->reserved_until && now()->gt($booking->reserved_until))) {
            $booking->update(['status' => 'expired']);

            return response()->json([
                'success' => false,
                'message' => 'Booking has expired. Please create a new booking.',
            ], 400);
        }

        if (HolidayHelper::isHoliday($booking->trip->departure_date)) {
            return response()->json([
                'success' => false,
                'message' => 'Bookings are closed during the holiday period.',
            ], 400);
        }

        if ($booking->payment_status === 'paid' && $booking->status === 'confirmed') {
            return response()->json([
                'success' => true,
                'message' => 'Booking already confirmed.',
                'data' => [
                    'booking' => new BookingResource($booking->loadMissing([
                        'trip.route',
                        'trip.bus',
                        'fromStop.terminal',
                        'toStop.terminal',
                        'seats',
                        'passengers',
                    ])),
                ],
            ]);
        }

        $validated = $request->validate([
            'payment_method' => ['required', 'in:easypaisa,jazzcash'],
            'transaction_id' => ['required', 'string', 'max:100'],
        ]);

        try {
            $paymentMethod = $validated['payment_method'];
            $transactionId = $validated['transaction_id'];

            $paymentVerified = $this->verifyPayment($paymentMethod, $transactionId, (float) $booking->final_amount);

            if (! $paymentVerified) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment verification failed. Please try again or contact support.',
                ], 422);
            }

            $this->bookingService->confirmPayment($booking, $paymentMethod, (float) $booking->final_amount);

            $booking->update([
                'online_transaction_id' => $transactionId,
            ]);

            $booking->loadMissing([
                'trip.route',
                'trip.bus',
                'fromStop.terminal',
                'toStop.terminal',
                'seats',
                'passengers',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment successful! Your booking has been confirmed.',
                'data' => [
                    'booking' => new BookingResource($booking),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API payment processing error: '.$e->getMessage(), [
                'booking_id' => $booking->id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your payment. Please try again.',
            ], 500);
        }
    }

    private function buildSeatMap(Trip $trip, $fromStop, $toStop, int $total, array $available): array
    {
        $seatMap = [];
        $bookedSeats = $this->getBookedSeats($trip, $fromStop, $toStop);

        for ($i = 1; $i <= $total; $i++) {
            $seatMap[$i] = [
                'number' => $i,
                'status' => 'available',
            ];

            if (isset($bookedSeats[$i])) {
                $seatMap[$i]['status'] = $bookedSeats[$i]['status'];
                $seatMap[$i]['gender'] = $bookedSeats[$i]['gender'];
            } elseif (! in_array($i, $available)) {
                $seatMap[$i]['status'] = 'held';
            }
        }

        return $seatMap;
    }

    private function getBookedSeats(Trip $trip, $fromStop, $toStop): array
    {
        $bookings = Booking::with(['seats', 'passengers', 'fromStop', 'toStop'])
            ->where('trip_id', $trip->id)
            ->whereIn('status', ['confirmed', 'checked_in', 'boarded'])
            ->get();

        $bookedSeats = [];

        $queryFrom = $fromStop->sequence ?? null;
        $queryTo = $toStop->sequence ?? null;

        if ($queryFrom === null || $queryTo === null) {
            return $bookedSeats;
        }

        foreach ($bookings as $booking) {
            if (! $booking->fromStop || ! $booking->toStop) {
                continue;
            }

            $bookingFrom = $booking->fromStop->sequence ?? null;
            $bookingTo = $booking->toStop->sequence ?? null;

            if ($bookingFrom === null || $bookingTo === null) {
                continue;
            }

            if ($bookingFrom < $queryTo && $queryFrom < $bookingTo) {
                foreach ($booking->seats as $seat) {
                    $gender = $seat->gender;

                    if (! $gender && $booking->passengers->isNotEmpty()) {
                        $passenger = $booking->passengers->first();
                        $gender = $passenger?->gender;
                    }

                    $bookedSeats[$seat->seat_number] = [
                        'status' => 'booked',
                        'gender' => $gender,
                    ];
                }
            }
        }

        return $bookedSeats;
    }

    /**
     * Mock payment verification - mirrors web PaymentController logic.
     */
    private function verifyPayment(string $gateway, string $transactionId, float $amount): bool
    {
        Log::info('API payment verification requested', [
            'gateway' => $gateway,
            'transaction_id' => $transactionId,
            'amount' => $amount,
        ]);

        return ! empty($transactionId);
    }
}
