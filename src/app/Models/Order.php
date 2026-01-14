<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'buyer_id',
        'payment_method',
        'stripe_session_id',
        'ship_postal_code',
        'ship_address',
        'ship_building',
        'price_at_purchase',
        'payment_status',
        'reserved_until',
        'paid_at',
        'canceled_at',
        'expired_at',
    ];


    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
