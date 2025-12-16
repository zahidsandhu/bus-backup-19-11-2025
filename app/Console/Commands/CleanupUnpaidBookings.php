<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\PaymentLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupUnpaidBookings extends Command
{
    protected $signature = 'bookings:cleanup-unpaid';

    protected $description = 'Unlock seats and delete unpaid online bookings after timeout';

    public function handle(): int
    {
        $cutoff = now()->subMinutes(10);

        Booking::query()
            ->where('channel', 'online')
            ->where('payment_status', 'unpaid')
            ->where('status', 'hold')
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('reserved_until')->orWhere('reserved_until', '<=', $cutoff);
            })
            ->chunkById(100, function ($bookings) {
                foreach ($bookings as $booking) {
                    DB::transaction(function () use ($booking) {
                        PaymentLog::where('booking_id', $booking->id)
                            ->where('status', 'initiated')
                            ->update(['status' => 'expired']);

                        $booking->update([
                            'status' => 'expired',
                            'payment_status' => 'unpaid',
                        ]);

                        $booking->delete();
                    });
                }
            });

        return Command::SUCCESS;
    }
}


