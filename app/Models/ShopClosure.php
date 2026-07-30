<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopClosure extends Model
{
    public $timestamps = false;
    protected $fillable = ['shop_id', 'date', 'reason'];
    protected $casts = ['date' => 'date'];

    public function shop() { return $this->belongsTo(Shop::class); }
}
