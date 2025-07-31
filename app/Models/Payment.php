<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'amount',
        'status',
        'paystack_reference',
        'paystack_url',
        'payment_method',
        'data',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Initialize Paystack payment and return [auth_url, reference]
     */
    public static function generatePaystackLink($user, $order)
    {
        $paystackSecret = env('PAYSTACK_SECRET_KEY');
        $paystackUrl = 'https://api.paystack.co/transaction/initialize';
        $reference = 'NL-P-' . uniqid(). uniqid();
        $callbackUrl = "https://nutrylyfe.netlify.app/call-back/payment-verification";
        // $callbackUrl = url('/api/networker/order/verify-payment');
        $response = Http::withToken($paystackSecret)
            ->post($paystackUrl, [
                'amount' => $order->total * 100, // Paystack expects amount in kobo
                'email' => $user->email,
                'reference' => $reference,
                'callback_url' => $callbackUrl,
                'metadata' => [
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'customer' => $user,
                ],
            ]);
        if (!$response->ok() || !$response['status']) {
            return [null, null, $response];
            return [null, null, 'Failed to initialize payment with Paystack.'];
        }
        $authUrl = $response['data']['authorization_url'];
        return [$authUrl, $reference, null];
    }

    /**
     * Verify Paystack payment by reference
     */
    public static function verifyPaystack($reference)
    {
        $paystackSecret = env('PAYSTACK_SECRET_KEY');
        $verifyUrl = 'https://api.paystack.co/transaction/verify/' . $reference;
        $response = Http::withToken($paystackSecret)->get($verifyUrl);
        if (!$response->ok() || !$response['status']) {
            return [null, 'Failed to verify payment with Paystack.'];
        }
        return [$response['data'], null];
    }
}
