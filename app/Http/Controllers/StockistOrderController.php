<?php

namespace App\Http\Controllers;

use App\Mail\VerifyOrderOtp;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class StockistOrderController extends Controller
{
    /**
     * GET /api/stockist/orders/all
     * Returns all orders that include products from the authenticated stockist
     */
    public function get_orders(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->user_type !== 'Stockist') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or not a Stockist.'
            ], 403);
        }
        // Get all product_ids the stockist owns
        $userProductIds = $user->userProducts()->pluck('product_id')->toArray();
        // Get all orders that have at least one item with those product_ids
        $orders = Order::whereHas('items', function ($q) use ($userProductIds) {
            $q->whereIn('product_id', $userProductIds);
        })
            ->with(['items.product', 'payment', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json([
            'status' => true,
            'data' => $orders
        ]);
    }

    /**
     * GET /api/stockist/orders/{order}
     * Returns a single order if it contains a product from the authenticated stockist
     */
    public function get_order(Request $request, $orderID)
    {
        $user = $request->user();
        if (!$user || $user->user_type !== 'Stockist') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or not a Stockist.'
            ], 403);
        }
        $userProductIds = $user->userProducts()->pluck('product_id')->toArray();
        $order = Order::where('orderID', $orderID)
            ->whereHas('items', function ($q) use ($userProductIds) {
                $q->whereIn('product_id', $userProductIds);
            })
            ->with(['items.product', 'payment', 'user'])
            ->first();
        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Order not found or does not belong to your store.'
            ], 404);
        }
        return response()->json([
            'status' => true,
            'data' => $order
        ]);
    }



    /**
     * POST /api/stockist/orders/{order}/verify
     * Verify a networker's order by sending an OTP to their email
     */
    public function verifyOrder(Request $request, $orderId)
    {
        if (!$request->user()->tokenCan('Stockist')) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or not a Stockist.'
            ], 403);
        }

        $user = $request->user();
        if (!$user || $user->user_type != 'Stockist') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or not a Stockist.'
            ], 403);
        }

        $order = Order::where('orderID', $orderId)->first();
        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Order not found.'
            ], 404);
        }

        $networker = $order->user;
        $orderDetails = $order->load(['items.product', 'payment']);

        // Generate OTP
        $otp = rand(100000, 999999);
        // Store OTP in session or a temporary storage for verification
        // session()->forget('order_verification_otp');
        Cache::put('order_verification_otp', $otp, now()->addMinutes(10));

        // Send OTP to the networker's email
        Mail::to($networker->email)->send(new VerifyOrderOtp($otp));

        return response()->json([
            'status' => true,
            'message' => 'OTP sent to your email. Please verify your order.',
            'data' => [
                // 'order' => $orderDetails,
                'networker' => $networker
            ]
        ]);
    }

    /**
     * POST /api/stockist/orders/{order}/verify-otp
     * Verify a networker's order by verifying the OTP
     */
    public function verifyOrderOtp(Request $request, $orderId)
    {
        $user = $request->user();
        if (!$user || $user->user_type !== 'Stockist') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or not a Stockist.'
            ], 403);
        }

        $order = Order::where('orderID', $orderId)->first();
        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Order not found.'
            ], 404);
        }

        $networker = $order->user;
        $orderDetails = $order->load(['items.product', 'payment']);

        // Verify OTP
        $otp = $request->otp;
        $storedOtp = Cache::get('order_verification_otp');
        if ($otp != $storedOtp) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP, Please Try Again.'
            ], 422);
        }

        // Clear OTP from cache
        Cache::forget('order_verification_otp');

        return response()->json([
            'status' => true,
            'message' => 'Order verified successfully.',
            'data' => [
                'order' => $orderDetails,
                'networker' => $networker
            ]
        ]);
    }



    /**
     * POST /api/stockist/orders/{order}/close
     * Close an order
     */
    public function closeOrder(Request $request, $orderId)
    {
        if ($request->user()->tokenCan("Stockist")) {
            $user = $request->user();
            if (!$user || $user->user_type !== 'Stockist') {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized or not a Stockist.'
                ], 403);
            }

            $order = Order::where('orderID', $orderId)->first();
            if (!$order) {
                return response()->json([
                    'status' => false,
                    'message' => 'Order not found.'
                ], 404);
            }

            // $stockistProducts = $user->userProducts()->pluck('product_id');
            // $orderItems = $order->items()->pluck('product_id');
            // $missingProducts = $orderItems->diff($stockistProducts);

            // if ($missingProducts->isNotEmpty()) {
            //     return response()->json([
            //         'status' => false,
            //         'message' => 'Order contains products not belonging to the Stockist.'
            //     ], 422);
            // }

            $order->status = 'closed';
            $order->save();

            return response()->json([
                'status' => true,
                'message' => 'Order closed successfully.',
                'data' => $order
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or not a Stockist.'
            ], 403);
        }
    }
}
