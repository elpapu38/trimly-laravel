<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'shop_id', 'category_id', 'name', 'description', 'duration_min',
        'price', 'deposit_pct', 'image', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function shop() { return $this->belongsTo(Shop::class); }
    public function category() { return $this->belongsTo(ServiceCategory::class, 'category_id'); }
    public function employees() { return $this->belongsToMany(Employee::class, 'employee_services'); }
    public function appointments() { return $this->hasMany(Appointment::class); }

    public function scopeActive($q) { return $q->where('is_active', true); }

    public static function getGroupedByCategory(int $shopId): array
    {
        $services = static::with('category')
            ->where('shop_id', $shopId)->active()
            ->orderBy('sort_order')->orderBy('name')->get();

        return $services->groupBy(fn ($s) => $s->category->name ?? 'Sin categoría')->toArray();
    }
}
