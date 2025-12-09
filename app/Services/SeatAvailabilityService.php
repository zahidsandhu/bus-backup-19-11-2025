<?php

namespace App\Services;

use App\Models\Trip;

class SeatAvailabilityService
{
    public function __construct(
        public AvailabilityService $availabilityService
    ) {
    }

    /**
     * Build a simple seat map for a given trip segment.
     *
     * @param  int  $tripId
     * @param  int  $fromTripStopId  TripStop ID
     * @param  int  $toTripStopId  TripStop ID
     * @return array<int, array{number:int,status:string}>
     */
    public function getAvailableSeats(int $tripId, int $fromTripStopId, int $toTripStopId): array
    {
        $trip = Trip::with('bus')->findOrFail($tripId);

        $seatCount = $this->availabilityService->seatCount($trip);
        $freeSeats = $this->availabilityService->availableSeats($tripId, $fromTripStopId, $toTripStopId);
        $freeSet = array_flip($freeSeats);

        $seatMap = [];

        for ($i = 1; $i <= $seatCount; $i++) {
            $seatMap[$i] = [
                'number' => $i,
                'status' => isset($freeSet[$i]) ? 'available' : 'booked',
            ];
        }

        return $seatMap;
    }
}


