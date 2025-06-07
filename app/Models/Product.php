<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'ingredients',
        'benefits',
        'price',
        'image',
        'status'
    ];


    // public function orderItems()
    // {
    //     return $this->hasMany(OrderItem::class);
    // }

    // public function userProducts()
    // {
    //     return $this->hasMany(UserProduct::class);
    // }

    public function stockists(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_products')
            ->withPivot('quantity', 'status');
            // ->wherePivot('status', 'active');
    }

}
