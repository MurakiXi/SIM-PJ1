<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ItemCondition;


class Item extends Model
{
    use HasFactory;

    protected $casts = [
        'condition' => ItemCondition::class,
    ];

    protected $fillable = [
        'seller_id',
        'name',
        'brand',
        'description',
        'price',
        'image_path',
        'status',
        'condition',
        'processing_expires_at',
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_item')->withTimestamps();
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function likedUsers()
    {
        return $this->belongsToMany(User::class, 'likes')->withTimestamps();
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function order()
    {
        return $this->hasOne(Order::class);
    }
}
