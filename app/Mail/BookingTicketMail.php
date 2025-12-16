<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingTicketMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking)
    {
    }

    public function build()
    {
        return $this->subject('Your Ticket #'.$this->booking->booking_number)
            ->view('emails.booking-ticket');
    }
}


