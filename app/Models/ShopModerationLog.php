<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopModerationLog extends Model
{
    public $timestamps = false;
    protected $table = 'shop_moderation_log';
    protected $fillable = ['shop_id', 'admin_id', 'action', 'reason', 'duration_days', 'expires_at'];
    protected $casts = ['created_at' => 'datetime', 'expires_at' => 'datetime', 'dismissed_at' => 'datetime'];

    public function shop() { return $this->belongsTo(Shop::class); }
    public function admin() { return $this->belongsTo(User::class, 'admin_id'); }
}
