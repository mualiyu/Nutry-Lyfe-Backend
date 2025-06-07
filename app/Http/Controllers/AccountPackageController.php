<?php

namespace App\Http\Controllers;

use App\Models\AccountPackage;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AccountPackageController extends Controller
{
    // Store package
    public function store(Request $request)
    {
        // check if user is auth and can Admin
        if (!$request->user() || !$request->user()->can('Admin')) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'price' => 'required|numeric',
            'pv' => 'required|numeric',
            'eligible_roles' => 'nullable|string',
            'bonuses_json' => 'nullable|json',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $package = Package::create($request->all());

        if ($package) {
            return response()->json([
                'status' => true,
                'data' => [
                    'package' => $package,
                ],
                'message' => 'Package created successfully.'
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Failed to create package',
        ], 500);
    }

    // get all package
    public function get_all()
    {
        return response()->json([
            'status' => true,
            'data' => [
                'packages' => Package::all(),
            ]
        ]);
    }

    // delete package
    public function destroy($id, Request $request)
    {

        if (!$request->user() || !$request->user()->tokenCan('Admin')) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        $package = Package::find($id);
        if (!$package) {
            return response()->json([
                'status' => false,
                'message' => 'Package not found',
            ], 422);
        }
        $package->delete();
        return response()->json([
            'status' => true,
            'message' => 'Package deleted successfully',
        ]);
    }


}
