<?php

namespace App\Mail;

use App\Models\Appointment;
use App\Models\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Appointment $appointment, public Shop $shop)
    {
    }

    public function build()
    {
        return $this->subject("Turno confirmado — {$this->shop->name}")
            ->view('emails.booking-confirmation');
    }
}
