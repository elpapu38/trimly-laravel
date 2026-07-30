<?php

namespace App\Mail;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ShopApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Shop $shop, public User $owner)
    {
    }

    public function build()
    {
        return $this->subject('¡Tu local fue aprobado en Trimly!')
            ->view('emails.shop-approved');
    }
}
