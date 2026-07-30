<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceCategory extends Model
{
    public $timestamps = false;
    protected $fillable = ['shop_id', 'name', 'sort_order'];

    public function shop() { return $this->belongsTo(Shop::class); }
    public function services() { return $this->hasMany(Service::class, 'category_id'); }
}
