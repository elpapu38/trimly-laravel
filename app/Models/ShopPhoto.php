<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopPhoto extends Model
{
    public $timestamps = false;
    protected $fillable = ['shop_id', 'filename', 'caption', 'sort_order'];
    protected $casts = ['created_at' => 'datetime'];

    public function shop() { return $this->belongsTo(Shop::class); }
}
