<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Str;

class JazzCashService
{
    public function buildRequestPayload(Booking $booking): array
    {
        $amountPaisa = (int) ($booking->final_amount * 100);

        $now = now();
        $txnRefNo = 'JC-'.$booking->id.'-'.Str::upper(Str::random(8));

        $data = [
            'pp_Version' => config('jazzcash.version'),
            'pp_TxnType' => 'MWALLET',
            'pp_Language' => config('jazzcash.language'),
            'pp_MerchantID' => config('jazzcash.merchant_id'),
            'pp_Password' => config('jazzcash.password'),
            'pp_TxnRefNo' => $txnRefNo,
            'pp_Amount' => (string) $amountPaisa,
            'pp_TxnCurrency' => config('jazzcash.currency'),
            'pp_TxnDateTime' => $now->format('YmdHis'),
            'pp_BillReference' => (string) $booking->booking_number,
            'pp_Description' => 'Booking #'.$booking->booking_number,
            'pp_ReturnURL' => route('payments.jazzcash.callback'),
            'pp_SecureHash' => '',
        ];

        $data['pp_SecureHash'] = $this->generateSecureHash($data);

        return $data;
    }

    public function endpoint(): string
    {
        return config('jazzcash.endpoint');
    }

    public function generateSecureHash(array $data): string
    {
        $integritySalt = config('jazzcash.hash_key');

        ksort($data);

        $str = '';
        foreach ($data as $key => $value) {
            if ($key === 'pp_SecureHash' || $value === null || $value === '') {
                continue;
            }

            $str .= '&'.$value;
        }

        $str = $integritySalt.$str;

        return strtoupper(hash_hmac('sha256', $str, $integritySalt));
    }

    public function validateSecureHash(array $data): bool
    {
        if (empty($data['pp_SecureHash'])) {
            return false;
        }

        $received = $data['pp_SecureHash'];
        unset($data['pp_SecureHash']);

        $calculated = $this->generateSecureHash($data);

        return hash_equals($received, $calculated);
    }
}


