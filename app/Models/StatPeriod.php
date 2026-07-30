<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatPeriod extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'date_from', 'date_to', 'created_by'];
    protected $casts = ['date_from' => 'date', 'date_to' => 'date', 'created_at' => 'datetime'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
