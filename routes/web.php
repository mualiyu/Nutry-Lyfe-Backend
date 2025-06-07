<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // return view('welcome');
    return response()->json([
        'status' => false,
        'message' => "Please Contact Admin"
    ], 401);
});


Route::get('storage/{p}/{filename}', function ($p, $filename)
{
    $path = storage_path('app/public/'.$p.'/' . $filename);

    if (!File::exists($path)) {
        abort(404);
    }

    $file = File::get($path);
    $type = File::mimeType($path);

    $response = Response::make($file, 200);
    $response->header("Content-Type", $type);

    return $response;
});
