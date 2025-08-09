<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Models\UserProduct;
use Illuminate\Http\Request;

class NetworkerProductController extends Controller
{
    public function get_all_stockist(Request $request)
    {
        if ($request->user()->tokenCan($request->user()->user_type)) {
            return response()->json([
                'status' => true,
                'data' => User::where('user_type', 'Stockist')->orderBy("created_at", "desc")->with('userProducts.product')->paginate(15),
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => "Unauthorized Access"
            ], 401);
        }
    }

    public function get_all_products_by_stockist(Request $request, User $user)
    {
        if ($request->user()->tokenCan($request->user()->user_type)) {

            $stockistProducts = $user->userProducts();

            $stockistProducts = $stockistProducts->get()->map(function ($userProduct) {
                return [
                    'id' => $userProduct->id,
                    'product_id' => $userProduct->product_id,
                    'quantity' => $userProduct->quantity,
                    'status' => $userProduct->status,
                    'product' => $userProduct->product, // Eager load product details
                ];
            });

            return response()->json([
                'status' => true,
                'data' => $stockistProducts
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => "Unauthorized Access"
            ], 422);
        }
    }

    public function show_single_product_from_stockist(UserProduct $userProduct)
    {
        $userProduct->user;
        $userProduct->product;

        if ($userProduct) {
            return response()->json([
                'status' => true,
                'data' => [
                    'stockistProduct' =>  $userProduct,
                ],
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => "Product not found"
            ], 422);
        }

    }

    public function show_single_product(Product $product)
    {
        // $product = Product::where('id', '=', $request->product_id)->get();
        $product->stockists;
        if ($product) {
            return response()->json([
                'status' => true,
                'data' => [
                    'product' =>  $product,
                ],
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => "Product not found"
            ], 422);
        }

    }

}
