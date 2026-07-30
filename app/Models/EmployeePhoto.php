<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeePhoto extends Model
{
    public $timestamps = false;
    protected $fillable = ['employee_id', 'filename', 'caption'];
    protected $casts = ['created_at' => 'datetime'];

    public function employee() { return $this->belongsTo(Employee::class); }
}
