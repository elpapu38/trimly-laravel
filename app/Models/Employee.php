<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'shop_id', 'user_id', 'name', 'bio', 'avatar', 'specialty',
        'instagram', 'status', 'sort_order',
    ];

    public function shop() { return $this->belongsTo(Shop::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function services() { return $this->belongsToMany(Service::class, 'employee_services')->withPivot('custom_price', 'custom_duration'); }
    public function hours() { return $this->hasMany(EmployeeHour::class)->orderBy('day_of_week'); }
    public function photos() { return $this->hasMany(EmployeePhoto::class); }
    public function appointments() { return $this->hasMany(Appointment::class); }

    public function scopeActive($q) { return $q->where('status', 'active'); }

    public static function availableForService(int $shopId, int $serviceId)
    {
        return static::where('shop_id', $shopId)
            ->whereHas('services', fn ($q) => $q->where('services.id', $serviceId))
            ->active()->orderBy('sort_order')->orderBy('name')->get();
    }
}
