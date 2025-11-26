<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing([
            'trip.route',
            'trip.bus',
            'fromStop.terminal',
            'toStop.terminal',
            'seats',
            'passengers',
        ]);

        return [
            'id' => $this->id,
            'booking_number' => $this->booking_number,
            'status' => $this->status,
            'channel' => $this->channel,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'total_fare' => (float) $this->total_fare,
            'discount_amount' => (float) $this->discount_amount,
            'tax_amount' => (float) $this->tax_amount,
            'final_amount' => (float) $this->final_amount,
            'currency' => $this->currency,
            'total_passengers' => $this->total_passengers,
            'reserved_until' => $this->reserved_until?->toDateTimeString(),
            'confirmed_at' => $this->confirmed_at?->toDateTimeString(),
            'trip' => $this->whenLoaded('trip', function () {
                return [
                    'id' => $this->trip->id,
                    'route_name' => $this->trip->route?->name,
                    'bus_name' => $this->trip->bus?->name,
                    'departure_date' => $this->trip->departure_date?->format('Y-m-d'),
                    'departure_datetime' => $this->trip->departure_datetime?->toDateTimeString(),
                    'estimated_arrival_datetime' => $this->trip->estimated_arrival_datetime?->toDateTimeString(),
                ];
            }),
            'from_stop' => $this->whenLoaded('fromStop', function () {
                return [
                    'id' => $this->fromStop->id,
                    'terminal_name' => $this->fromStop->terminal?->name,
                    'terminal_code' => $this->fromStop->terminal?->code,
                    'sequence' => $this->fromStop->sequence,
                ];
            }),
            'to_stop' => $this->whenLoaded('toStop', function () {
                return [
                    'id' => $this->toStop->id,
                    'terminal_name' => $this->toStop->terminal?->name,
                    'terminal_code' => $this->toStop->terminal?->code,
                    'sequence' => $this->toStop->sequence,
                ];
            }),
            'seats' => $this->whenLoaded('seats', function () {
                return $this->seats->map(function ($seat) {
                    return [
                        'id' => $seat->id,
                        'seat_number' => $seat->seat_number,
                        'gender' => $seat->gender,
                        'fare' => (float) $seat->fare,
                        'tax_amount' => (float) $seat->tax_amount,
                        'final_amount' => (float) $seat->final_amount,
                    ];
                });
            }),
            'passengers' => $this->whenLoaded('passengers', function () {
                return $this->passengers->map(function ($passenger) {
                    return [
                        'id' => $passenger->id,
                        'name' => $passenger->name,
                        'age' => $passenger->age,
                        'gender' => $passenger->gender,
                        'cnic' => $passenger->cnic,
                        'phone' => $passenger->phone,
                        'email' => $passenger->email,
                        'status' => $passenger->status,
                    ];
                });
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
