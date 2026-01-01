<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ItemCondition;
use Illuminate\Support\Facades\DB;

class Item extends Model
{
    use HasFactory;

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

    protected $casts = [
        'condition' => ItemCondition::class,
        'processing_expires_at' => 'datetime',
    ];

    public function releaseProcessingIfExpired(): void
    {
        DB::transaction(function () {
            $item = self::whereKey($this->id)->lockForUpdate()->first();

            if (! $item || ! $item->isProcessingExpired()) {
                return;
            }

            $item->update([
                'status' => 'on_sale',
                'processing_expires_at' => null,
            ]);

            $item->order()->delete();
        });
    }
    public function isProcessingExpired(): bool
    {
        return $this->status === 'processing'
            && $this->processing_expires_at
            && $this->processing_expires_at->isPast();
    }

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
