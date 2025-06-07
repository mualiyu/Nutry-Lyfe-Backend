<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\UserProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->tokenCan($request->user()->user_type)) {
            return response()->json([
                'status' => true,
                'data' => Product::orderBy("created_at", "desc")->paginate(15),
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 422);
        }
    }

    public function show(Request $request)
    {
        $product = Product::where('id', '=', $request->product_id)->get();

        if (count($product) > 0) {
            return response()->json([
                'status' => true,
                'data' => [
                    'product' =>  $product[0],
                ],
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => "Product not found"
            ], 422);
        }
    }

    public function upload(Request $request)
    {
        // if ($request->user()->tokenCan('Customer')) {

        $validator = Validator::make($request->all(), [
            'file' => 'required|max:5000|mimes:jpg,png,jpeg',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        if ($request->hasFile("file")) {
            $fileNameWExt = $request->file("file")->getClientOriginalName();
            $fileName = pathinfo($fileNameWExt, PATHINFO_FILENAME);
            $fileExt = $request->file("file")->getClientOriginalExtension();
            $fileNameToStore = $fileName . "_" . time() . "." . $fileExt;
            $request->file("file")->storeAs("public/productImages", $fileNameToStore);

            $url = '/storage/productImages/' . $fileNameToStore;
            // $url = url('/storage/productImages/' . $fileNameToStore);
            // $url = Storage::disk('s3')->url("user/".$fileNameToStore);

            return response()->json([
                'status' => true,
                'message' => "File successfully uploaded.",
                'url' => $url,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => "Error! file upload invalid. Try again."
            ], 422);
        }

        // }else{
        //     return response()->json([
        //         'status' => false,
        //         'message' => trans('auth.failed')
        //     ], 422);
        // }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function create(Request $request)
    {
        if ($request->user()->tokenCan('Admin')) {

            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'description' => 'nullable|string',
                'ingredients' => 'nullable|string',
                'benefits' => 'nullable|string',
                'price' => 'required|numeric',
                'image' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $product = Product::create($request->all());

            if ($product) {
                return response()->json([
                    'status' => true,
                    'message' => "Product has been created successfully!",
                    'data' => $product,
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => "Failed to create product. Please try again.",
                ], 422);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 422);
        }
    }

    public function update(Request $request, Product $product)
    {
        if ($request->user()->tokenCan('Admin')) {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'description' => 'nullable|string',
                'ingredients' => 'nullable|string',
                'benefits' => 'nullable|string',
                'price' => 'required|numeric',
                'image' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $updated = $product->update($request->all());

            if ($updated) {
                return response()->json([
                    'status' => true,
                    'message' => "Product has been updated successfully!",
                    'data' => $product->fresh(),
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => "Failed to update product. Please try again.",
                ], 422);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 422);
        }
    }

    // update product status
    public function adminUpdateStatus(Request $request)
    {
        if (!$request->user()->tokenCan('Admin')) {
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 422);
        }
        $validator = Validator::make($request->all(), [
            'product_id' => 'required',
            'status' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $product = Product::find($request->product_id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found.'
            ], 422);
        }

        $product->status = $request->status;
        $product->save();

        return response()->json([
            'status' => true,
            'message' => 'Product status updated successfully.',
            'data' => $product,
        ], 200);
    }

    public function destroy(Request $request)
    {
        if ($request->user()->tokenCan("Admin")) {

            $product = Product::where('id', '=', $request->product_id)->delete();

            if ($product) {
                return response()->json([
                    'status' => true,
                    'message' => 'Product Deleted.'
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => "Failed to delete product, Try again later."
                ], 422);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 422);
        }
    }

    // stockist_update_quantity
    public function stockist_update_quantity(Request $request, Product $product)
    {
        if ($request->user()->tokenCan('Stockist')) {
            $validator = Validator::make($request->all(), [
                'quantity' => 'required|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            if ($request->quantity <= 0) {
                return response()->json([
                    'status' => false,
                    'message' => "Quantity must be positive."
                ], 422);
            }

            $userProduct = UserProduct::where('user_id', $request->user()->id)
                ->where('product_id', $product->id)
                ->first();

            if (!$userProduct) {
                // If no UserProduct exists, create a new one
                $userProduct = new UserProduct();
                $userProduct->user_id = $request->user()->id;
                $userProduct->product_id = $product->id;
                $userProduct->quantity = $request->quantity;
                $userProduct->status = 1; // Assuming status is active by default
                $userProduct->save();
            } else {

                $userProduct->quantity += $request->quantity;
                $userProduct->save();
            }

            return response()->json([
                'status' => true,
                'message' => "Product quantity updated successfully!",
                'data' => $userProduct->fresh()->load('product'),
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 422);
        }
    }

    // stockist_show
    public function stockist_show(Request $request)
    {
        if ($request->user()->tokenCan('Stockist')) {

            $userProduct = $request->user()->userProducts()
                ->where('product_id', $request->product_id)->with('product')
                ->first();

            if ($userProduct) {
                return response()->json([
                    'status' => true,
                    'data' => $userProduct
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => "Product not found in your inventory"
                ], 422);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 422);
        }
    }

    // stockist_all_products
    public function stockist_all_products(Request $request)
    {
        if ($request->user()->tokenCan('Stockist')) {

            $userProducts = $request->user()->userProducts();
                // ->with('product');

            $userProducts = $userProducts->get()->map(function ($userProduct) {
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
                'data' => $userProducts
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 422);
        }
    }
}
