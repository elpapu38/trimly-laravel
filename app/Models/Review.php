<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'appointment_id', 'shop_id', 'client_id', 'rating', 'rating_cleanliness',
        'rating_punctuality', 'rating_value', 'comment', 'reply', 'reply_at',
        'is_visible', 'report_count', 'flagged_at', 'helpful_count',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'created_at' => 'datetime',
        'reply_at' => 'datetime',
        'flagged_at' => 'datetime',
    ];

    public function appointment() { return $this->belongsTo(Appointment::class); }
    public function shop() { return $this->belongsTo(Shop::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function reports() { return $this->hasMany(ReviewReport::class); }

    public static function ratingBreakdown(int $shopId): array
    {
        $rows = static::where('shop_id', $shopId)->where('is_visible', true)
            ->selectRaw('rating, count(*) as cnt')->groupBy('rating')->get();

        $breakdown = array_fill(1, 5, 0);
        $total = 0;
        foreach ($rows as $r) {
            $breakdown[(int) $r->rating] = (int) $r->cnt;
            $total += (int) $r->cnt;
        }
        return ['breakdown' => $breakdown, 'total' => $total];
    }
}
