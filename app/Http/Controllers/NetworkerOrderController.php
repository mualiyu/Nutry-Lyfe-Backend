<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\UserProduct;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class NetworkerOrderController extends Controller
{
    public function place_order(Request $request)
    {
        // Ensure user is authenticated and is a Networker
        $user = $request->user();
        if (!$user || $user->user_type !== 'Networker') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or not a Networker.'
            ], 403);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'stockist_user_id' => 'required|exists:users,id',
            'payment_type' => 'required|in:wallet,paystack',
            'products' => 'required|array|min:1',
            'products.*.user_product_id' => 'required|exists:user_products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $stockistId = $request->stockist_user_id;
        $products = $request->products;
        $total = 0;
        $orderItems = [];

        DB::beginTransaction();
        try {
            foreach ($products as $item) {
                $userProduct = UserProduct::where('id', $item['user_product_id'])
                    ->where('user_id', $stockistId)
                    ->first();
                if (!$userProduct) {
                    DB::rollBack();
                    return response()->json([
                        'status' => false,
                        'message' => 'UserProduct not found for stockist.'
                    ], 404);
                }
                if ($userProduct->quantity < $item['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'status' => false,
                        'message' => 'Insufficient stock for product: ' . $userProduct->product->name,
                    ], 422);
                }
                $product = $userProduct->product;
                $price = $product->price;
                $subtotal = $price * $item['quantity'];
                $total += $subtotal;
                $orderItems[] = [
                    'product_id' => $userProduct->product_id,
                    'qty' => $item['quantity'],
                    'price' => $price
                ];
                // Deduct stock from stockist
                $userProduct->quantity -= $item['quantity'];
                $userProduct->save();
            }

            // Check payment type
            if ($request->payment_type === 'wallet') {
                $wallet = $user->wallet()->first();
                if (!$wallet || $wallet->balance < $total) {
                    DB::rollBack();
                    return response()->json([
                        'status' => false,
                        'message' => 'Insufficient wallet balance.'
                    ], 422);
                }

                // Deduct wallet balance
                $wallet->balance -= $total;
                $wallet->save();

                // Create Order
                $order = Order::create([
                    'user_id' => $user->id,
                    'total' => $total,
                    'orderID' => 'NL-O-' . mt_rand(10000000, 99999999),
                    'status' => 'completed',
                ]);

                // Create OrderItems
                foreach ($orderItems as $orderItem) {
                    $order->items()->create($orderItem);
                }

                // Create Payment
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'amount' => $total,
                    'status' => 'paid',
                    'payment_method' => 'wallet',
                ]);

                // Log transaction
                $user->transactions()->create([
                    'user_id' => $user->id,
                    'type' => 'order_payment',
                    'amount' => $total,
                    'status' => 'completed',
                    'transaction_id' => $payment->id,
                    'description' => 'Order payment via wallet'
                ]);

                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => 'Order placed and paid successfully via wallet.',
                    'data' => [
                        'order' => $order->load('items'),
                        'payment_type' => $request->payment_type,
                        'payment' => [
                            'payment_method' => 'wallet',
                        ]
                    ]
                ], 201);

            } else if ($request->payment_type === 'paystack') {
                // Create Order
                $order = Order::create([
                    'user_id' => $user->id,
                    'total' => $total,
                    'orderID' => 'NL-O-' . mt_rand(10000000, 99999999),
                    'status' => 'pending',
                ]);

                // Create OrderItems
                foreach ($orderItems as $orderItem) {
                    $order->items()->create($orderItem);
                }

                // Create Payment and generate Paystack link
                list($authUrl, $reference, $err) = Payment::generatePaystackLink($user, $order);

                if ($err) {
                    DB::rollBack();
                    return response()->json([
                        'status' => false,
                        'message' => $err
                    ], 500);
                }

                $payment = Payment::create([
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'amount' => $total,
                    'status' => 'pending',
                    'paystack_reference' => $reference,
                    'paystack_url' => $authUrl,
                    'payment_method' => 'paystack',
                ]);

                // Log transaction
                $user->transactions()->create([
                    'user_id' => $user->id,
                    'type' => 'order_payment',
                    'amount' => $total,
                    'status' => 'pending',
                    'transaction_id' => $payment->id,
                    'description' => 'Order payment via Paystack'
                ]);

                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => 'Order placed successfully. Proceed to payment.',
                    'data' => [
                        'order' => $order->load('items'),
                        'payment_type' => $request->payment_type,
                        'payment' => [
                            'paystack_url' => $authUrl,
                            'reference' => $reference,
                        ]
                    ]
                ], 201);
            } else {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid payment type.'
                ], 422);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * POST /api/networker/order/verify-payment
     * Body: { "reference": "paystack_reference" }
     */
    public function verify_payment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $reference = $request->reference;
        $payment = Payment::where('paystack_reference', $reference)->first();
        if (!$payment) {
            return response()->json([
                'status' => false,
                'message' => 'Payment not found.'
            ], 404);
        }

        list($data, $err) = Payment::verifyPaystack($reference);

        if ($err) {
            return response()->json([
                'status' => false,
                'message' => $err
            ], 500);
        }

        if ($data['status'] === 'success') {
            $payment->status = 'paid';
            $payment->data = $data;
            $payment->save();
            $order = $payment->order;
            $order->status = 'completed';
            $order->save();

            $payment->user->transactions()->create([
                'user_id' => $payment->user_id,
                'type' => 'order_payment',
                'amount' => $payment->amount,
                'status' => 'completed',
                'transaction_id' => $payment->id,
                'description' => 'Order payment via Paystack'
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Payment verified and order completed.',
                'data' => [
                    'order' => $order,
                    'payment' => $payment
                ]
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Payment not successful.',
                'data' => $data
            ], 422);
        }
    }

    /**
     * GET /api/networker/order/all
     * Returns all orders for the authenticated networker
     */
    public function get_orders(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->user_type !== 'Networker') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or not a Networker.'
            ], 403);
        }
        $orders = $user->orders()->with(['items.product', 'payment'])->orderBy('created_at', 'desc')->get();
        return response()->json([
            'status' => true,
            'data' => $orders
        ]);
    }

    /**
     * GET /api/networker/orders/{order}
     * Returns a single order for the authenticated networker
     */
    public function get_order(Request $request, $orderId)
    {
        $user = $request->user();
        if (!$user || $user->user_type !== 'Networker') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or not a Networker.'
            ], 403);
        }

        $order = $user->orders()->with(['items.product', 'payment'])->where('orderID', $orderId)->first();
        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Order not found.'
            ], 404);
        }
        return response()->json([
            'status' => true,
            'data' => $order
        ]);
    }
}
