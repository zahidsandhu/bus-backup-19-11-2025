<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'gateway',
        'txn_reference',
        'amount',
        'status',
        'response_code',
        'message',
        'request_payload',
        'response_payload',
    ];

    protected $casts = [
        'amount' => 'integer',
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}


