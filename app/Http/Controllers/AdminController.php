<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    // get all system users
    public function getAllUsers(Request $request)
    {
        if (!$request->user()->tokenCan("Admin")) {
            return response()->json(['status'=> false, 'message' => 'Unauthorized to view users.'], 403);
        }

        $users = User::orderBy('created_at', 'asc')->get();
        $networkers = $users->where('user_type', 'Networker');
        $stockists = User::orderBy('created_at', 'asc')->where('user_type', 'Stockist')->with('userProducts.product')->get();
        $admins = $users->where('user_type', 'Admin');



        return response()->json([
            'status' => true,
            'message' => "Users retrieved successfully.",
            'data' => [
                'stockists' => $stockists,
                'networkers' => $networkers,
                'admins' => $admins,
            ],
        ], 200);
    }
}
