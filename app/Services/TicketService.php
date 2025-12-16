<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TicketService
{
    public function sendTicket(Booking $booking): void
    {
        try {
            $booking->loadMissing([
                'user',
                'trip.route',
                'fromStop.terminal',
                'toStop.terminal',
                'seats',
                'passengers',
            ]);

            if ($booking->user && $booking->user->email) {
                Mail::to($booking->user->email)->queue(new \App\Mail\BookingTicketMail($booking));
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send ticket', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}


