<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewReport extends Model
{
    public $timestamps = false;
    protected $fillable = ['review_id', 'user_id', 'reason', 'note'];
    protected $casts = ['created_at' => 'datetime'];

    public function review() { return $this->belongsTo(Review::class); }
    public function user() { return $this->belongsTo(User::class); }
}
