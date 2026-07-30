<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    public $timestamps = false;
    protected $fillable = ['user_id', 'type', 'title', 'body', 'action_url', 'data', 'read_at'];
    protected $casts = ['data' => 'array', 'read_at' => 'datetime', 'created_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
}
