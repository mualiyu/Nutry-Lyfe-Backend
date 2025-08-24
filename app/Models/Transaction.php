<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',  //order_payment, wallet_funding, pv_conversion, ~~bonus, withdrawal, product purchase, registration etc.
        'amount',
        'status',
        'transaction_id',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
