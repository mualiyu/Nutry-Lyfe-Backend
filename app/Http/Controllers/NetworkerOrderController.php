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
        // try {
            foreach ($products as $item) {
                $userProduct = UserProduct::where('id', $item['user_product_id'])
                    ->where('user_id', $stockistId)
                    ->first();
                if (!$userProduct) {
                    // throw new \Exception('UserProduct not found for stockist.');
                    return response()->json([
                        'status' => false,
                        'message' => 'UserProduct not found for stockist.'
                    ], 404);
                }
                if ($userProduct->quantity < $item['quantity']) {
                    // throw new \Exception('Insufficient stock for product ID: ' . $userProduct->product_id);
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

            // Create Order
            $order = Order::create([
                'user_id' => $user->id,
                'total' => $total,
                'orderID' => 'NL-O-' . uniqid(). uniqid(),
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

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Order placed successfully. Proceed to payment.',
                'data' => [
                    'order' => $order->load('items'),
                    'payment' => [
                        'paystack_url' => $authUrl,
                        'reference' => $reference,
                    ]
                ]
            ], 201);
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     return response()->json([
        //         'status' => false,
        //         'message' => $e->getMessage()
        //     ], 422);
        // }
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
