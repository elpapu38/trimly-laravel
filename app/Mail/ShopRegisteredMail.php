<?php

namespace App\Mail;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ShopRegisteredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Shop $shop, public User $owner)
    {
    }

    public function build()
    {
        return $this->subject("Nuevo local pendiente: {$this->shop->name}")
            ->view('emails.shop-registered');
    }
}
