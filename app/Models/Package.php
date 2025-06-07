<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Yabacon\Paystack;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'pv',               // Point Value
        'eligible_roles',   // JSON or comma-separated roles
        'bonuses_json',     // JSON structure defining bonuses per package
    ];
public function users(): HasMany
    {
        return $this->hasMany(User::class, 'package_id');
    }

    public function init_payment($user)
    {
        try {
            $paystack = new Paystack(env('PAYSTACK_SECRET_KEY'));
            $tranx = $paystack->transaction->initialize([
                'amount' => $this->price * 100, // Amount in kobo (or cents)
                'email' => $user->email ? $user->email : $user->phone,
                'callback_url' => env('FRONT_URL').'/package/payment/verification',
                // 'callback_url' => url('/api/v1/customer/paystack/verify-callback'),
                'metadata' => [
                    'package_id' => $this->id, // Custom metadata
                    'user_id' => $user->id, // Custom metadata
                ],
            ]);

            return [
                'status' => true,
                'authorization_url' => $tranx->data->authorization_url,
                'reference' => $tranx->data->reference,
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
