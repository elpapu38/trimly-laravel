<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'role', 'avatar',
        'email_verified', 'verify_token', 'status',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified' => 'boolean',
        'last_login' => 'datetime',
        'suspended_until' => 'datetime',
        'password' => 'hashed', // Laravel 10+/11/12 hashea automáticamente al asignar
    ];

    // Relaciones
    public function shops()
    {
        return $this->hasMany(Shop::class, 'owner_id');
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id');
    }

    public function favorites()
    {
        return $this->belongsToMany(Shop::class, 'favorites', 'user_id', 'shop_id')->withPivot('created_at');
    }

    public function notifications_own()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    // Helpers de rol (equivalentes al sistema anterior)
    public function isSuperadmin(): bool { return $this->role === 'superadmin'; }
    public function isShopOwner(): bool { return $this->role === 'shop_owner'; }
    public function isEmployee(): bool { return $this->role === 'employee'; }
    public function isClient(): bool { return $this->role === 'client'; }

    public function isBanned(): bool { return $this->status === 'banned'; }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended'
            && $this->suspended_until
            && $this->suspended_until->isFuture();
    }
}
