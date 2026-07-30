<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopReport extends Model
{
    public $timestamps = false;
    protected $fillable = ['shop_id', 'user_id', 'reason', 'note'];
    protected $casts = ['created_at' => 'datetime'];

    public function shop() { return $this->belongsTo(Shop::class); }
    public function user() { return $this->belongsTo(User::class); }
}
