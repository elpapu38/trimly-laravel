<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;
    protected $table = 'audit_log';
    protected $fillable = ['user_id', 'action', 'entity', 'entity_id', 'payload', 'ip'];
    protected $casts = ['payload' => 'array', 'created_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
}
