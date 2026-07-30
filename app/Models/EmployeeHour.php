<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeHour extends Model
{
    public $timestamps = false;
    protected $fillable = ['employee_id', 'day_of_week', 'opens_at', 'closes_at'];

    public function employee() { return $this->belongsTo(Employee::class); }
}
