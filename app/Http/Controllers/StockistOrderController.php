<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

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
        $orders = Order::whereHas('items', function($q) use ($userProductIds) {
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
            ->whereHas('items', function($q) use ($userProductIds) {
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
}
