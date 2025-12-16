<?php

namespace App\Http\Controllers;

use App\Helpers\HolidayHelper;
use App\Models\Booking;
use App\Models\PaymentLog;
use App\Services\BookingService;
use App\Services\JazzCashService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private BookingService $bookingService,
        private JazzCashService $jazzCashService
    ) {}

    public function show(Request $request, Booking $booking): View|RedirectResponse
    {
        // Verify booking belongs to authenticated user
        if ($booking->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this booking.');
        }

        if (HolidayHelper::isHoliday($booking->trip->departure_date)) {
            return view('frontend.payment.expired', compact('booking'))
                ->with('error', 'Bookings are closed during the holiday period.');
        }

        // Check if booking is expired
        if ($booking->status === 'expired' || ($booking->reserved_until && now()->gt($booking->reserved_until))) {
            $booking->update(['status' => 'expired']);

            return view('frontend.payment.expired', compact('booking'));
        }

        // Check if already paid
        if ($booking->payment_status === 'paid' && $booking->status === 'confirmed') {
            return redirect()->route('frontend.bookings.success', $booking)->with('success', 'Booking already confirmed!');
        }

        // Ensure reservation window is at least 10 minutes
        if (! $booking->reserved_until || $booking->reserved_until->lt(now()->addMinutes(10))) {
            $booking->update(['reserved_until' => now()->addMinutes(10)]);
            $booking->refresh();
        }

        // Build JazzCash payload using DB amount only
        $payload = $this->jazzCashService->buildRequestPayload($booking);

        // Create / update payment log
        PaymentLog::updateOrCreate(
            ['txn_reference' => $payload['pp_TxnRefNo']],
            [
                'booking_id' => $booking->id,
                'gateway' => 'jazzcash',
                'amount' => (int) $payload['pp_Amount'],
                'status' => 'initiated',
                'response_code' => null,
                'message' => null,
                'request_payload' => $payload,
                'response_payload' => null,
            ]
        );

        $booking->update([
            'payment_gateway' => 'jazzcash',
            'gateway_transaction_reference' => $payload['pp_TxnRefNo'],
        ]);

        return view('frontend.payment.index', [
            'booking' => $booking,
            'jazzcash' => $payload,
            'jazzcashUrl' => $this->jazzCashService->endpoint(),
        ]);
    }

    public function process(Request $request, Booking $booking): RedirectResponse
    {
        abort(404);
    }

    public function success(Booking $booking): View
    {
        // Verify booking belongs to authenticated user
        if ($booking->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this booking.');
        }

        $booking->load(['trip.route', 'trip.bus', 'fromStop.terminal', 'toStop.terminal', 'seats', 'passengers']);

        return view('frontend.payment.success', compact('booking'));
    }

    public function jazzCashCallback(Request $request): View
    {
        $data = $request->all();

        Log::info('JazzCash callback received', $data);

        if (! $this->jazzCashService->validateSecureHash($data)) {
            Log::warning('JazzCash secure hash mismatch', $data);

            $this->markPaymentFailed(null, $data, 'invalid_hash');

            return view('frontend.payment.callback-failed')->with('error', 'Invalid payment signature.');
        }

        $txnRefNo = $data['pp_TxnRefNo'] ?? null;
        $amount = isset($data['pp_Amount']) ? (int) $data['pp_Amount'] : null;
        $respCode = $data['pp_ResponseCode'] ?? null;
        $respMsg = $data['pp_ResponseMessage'] ?? null;

        if (! $txnRefNo || ! $amount) {
            $this->markPaymentFailed(null, $data, 'missing_reference');

            return view('frontend.payment.callback-failed')->with('error', 'Invalid payment data.');
        }

        $log = PaymentLog::where('txn_reference', $txnRefNo)->first();

        if (! $log) {
            $this->markPaymentFailed(null, $data, 'log_not_found');

            return view('frontend.payment.callback-failed')->with('error', 'Payment record not found.');
        }

        if ($log->status === 'success') {
            $booking = $log->booking()->with(['trip.route', 'fromStop.terminal', 'toStop.terminal'])->first();

            return view('frontend.payment.callback-success', compact('booking'));
        }

        $booking = $log->booking()->lockForUpdate()->with('trip')->first();

        if (! $booking) {
            $this->markPaymentFailed($log, $data, 'booking_not_found');

            return view('frontend.payment.callback-failed')->with('error', 'Booking not found.');
        }

        return DB::transaction(function () use ($booking, $log, $amount, $respCode, $respMsg, $data) {
            $expectedAmount = (int) ($booking->final_amount * 100);

            if ($amount !== $expectedAmount) {
                $this->markPaymentFailed($log, $data, 'amount_mismatch');

                return view('frontend.payment.callback-failed')->with('error', 'Amount mismatch.');
            }

            if ($booking->payment_status === 'paid' || $booking->status === 'confirmed') {
                return view('frontend.payment.callback-success', compact('booking'));
            }

            if ($respCode === '000') {
                $log->update([
                    'status' => 'success',
                    'response_code' => $respCode,
                    'message' => $respMsg,
                    'response_payload' => $data,
                ]);

                $this->bookingService->confirmPayment($booking, 'jazzcash', $booking->final_amount);

                $booking->update([
                    'payment_gateway' => 'jazzcash',
                    'gateway_transaction_reference' => $log->txn_reference,
                    'online_transaction_id' => $data['pp_RetreivalReferenceNo'] ?? $log->txn_reference,
                ]);

                app(\App\Services\TicketService::class)->sendTicket($booking);

                return view('frontend.payment.callback-success', compact('booking'));
            }

            $this->markPaymentFailed($log, $data, $respCode ?? 'failed');

            $booking->update([
                'status' => 'expired',
                'payment_status' => 'unpaid',
            ]);
            $booking->delete();

            return view('frontend.payment.callback-failed')->with('error', 'Payment failed or was cancelled.');
        });
    }

    protected function markPaymentFailed(?PaymentLog $log, array $data, string $reason): void
    {
        if ($log) {
            $log->update([
                'status' => 'failed',
                'response_code' => $reason,
                'message' => $data['pp_ResponseMessage'] ?? null,
                'response_payload' => $data,
            ]);
        } else {
            PaymentLog::create([
                'booking_id' => $data['booking_id'] ?? null,
                'gateway' => 'jazzcash',
                'txn_reference' => $data['pp_TxnRefNo'] ?? 'unknown-'.now()->timestamp,
                'amount' => isset($data['pp_Amount']) ? (int) $data['pp_Amount'] : 0,
                'status' => 'failed',
                'response_code' => $reason,
                'message' => $data['pp_ResponseMessage'] ?? null,
                'request_payload' => [],
                'response_payload' => $data,
            ]);
        }
    }
}
