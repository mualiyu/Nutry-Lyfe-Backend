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
        return response()->json(['message' => 'Unauthorized to view users.'], 403);
    }
    $users = User::orderBy('created_at', 'asc')->get();
    $networkers = $users->where('user_type', 'Networker');
    $stockists = $users->where('user_type', 'Stockist');
    $admins = $users->where('user_type', 'Admin');

    return response()->json([
        'status' => true,
        'message' => "Users retrieved successfully.",
        'data' => [
            'networkers' => $networkers,
            'stockists' => $stockists,
            'admins' => $admins,
        ],
    ], 200);
}

}
