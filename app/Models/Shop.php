<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Shop extends Model
{
    protected $fillable = [
        'owner_id', 'name', 'slug', 'type', 'target_audience', 'description',
        'specialties', 'amenities', 'phone', 'email', 'website', 'instagram',
        'facebook', 'whatsapp', 'logo', 'cover_image', 'address', 'city',
        'province', 'country', 'postal_code', 'latitude', 'longitude', 'currency',
        'status', 'plan', 'plan_expires', 'verified', 'featured', 'suspension_public',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'rating_avg' => 'decimal:2',
        'verified' => 'boolean',
        'featured' => 'boolean',
        'is_shadowbanned' => 'boolean',
        'suspension_public' => 'boolean',
        'suspension_until' => 'datetime',
        'plan_expires' => 'date',
    ];

    // Relaciones
    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    public function services() { return $this->hasMany(Service::class); }
    public function categories() { return $this->hasMany(ServiceCategory::class); }
    public function employees() { return $this->hasMany(Employee::class); }
    public function appointments() { return $this->hasMany(Appointment::class); }
    public function reviews() { return $this->hasMany(Review::class); }
    public function photos() { return $this->hasMany(ShopPhoto::class)->orderBy('sort_order'); }
    public function hours() { return $this->hasMany(ShopHour::class); }
    public function closures() { return $this->hasMany(ShopClosure::class); }
    public function moderationLog() { return $this->hasMany(ShopModerationLog::class); }
    public function reports() { return $this->hasMany(ShopReport::class); }
    public function favoritedBy() { return $this->belongsToMany(User::class, 'favorites', 'shop_id', 'user_id'); }

    // Scope: sólo locales visibles en búsqueda pública
    public function scopePublicVisible(Builder $q): Builder
    {
        return $q->where(function ($w) {
            $w->where('status', 'active')
              ->orWhere(function ($w2) {
                  $w2->where('status', 'suspended')->where('suspension_public', true);
              });
        })->where('is_shadowbanned', false);
    }

    // Labels (idénticos al código original)
    public static function typeLabel(?string $type): string
    {
        return match ($type ?? '') {
            'barbershop' => 'Barbería', 'salon' => 'Salón de Belleza', 'mixed' => 'Mixto / Unisex',
            'nails' => 'Manicura & Uñas', 'spa' => 'Spa & Relajación', 'tattoo' => 'Tatuajes & Piercings',
            'makeup' => 'Maquillaje & Estética', default => 'Otro',
        };
    }

    public static function audienceLabel(?string $aud): string
    {
        return match ($aud ?? '') { 'men' => 'Caballeros', 'women' => 'Damas', default => 'Unisex' };
    }

    public static function typeEmoji(?string $type): string
    {
        return match ($type ?? '') {
            'barbershop' => '💈', 'salon' => '💅', 'nails' => '💅', 'spa' => '🧖',
            'tattoo' => '🎨', 'makeup' => '💄', default => '✂️',
        };
    }

    public function updateRating(): void
    {
        $this->rating_avg = $this->reviews()->where('is_visible', true)->avg('rating') ?? 0;
        $this->rating_count = $this->reviews()->where('is_visible', true)->count();
        $this->saveQuietly();
    }

    public function generateSlug(string $name): string
    {
        $slug = \Illuminate\Support\Str::slug($name);
        $base = $slug;
        $i = 1;
        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }
}
