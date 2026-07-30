<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleBlock extends Model
{
    public $timestamps = false;
    protected $fillable = ['shop_id', 'employee_id', 'date', 'start_time', 'end_time', 'reason'];
    protected $casts = ['date' => 'date'];

    public function shop() { return $this->belongsTo(Shop::class); }
    public function employee() { return $this->belongsTo(Employee::class); }
}
