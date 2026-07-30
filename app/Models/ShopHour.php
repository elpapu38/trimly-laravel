<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopHour extends Model
{
    public $timestamps = false;
    protected $fillable = ['shop_id', 'day_of_week', 'opens_at', 'closes_at', 'break_start', 'break_end'];

    public function shop() { return $this->belongsTo(Shop::class); }
}
