<?php

namespace App\Http\Controllers;

use App\Mail\VerifyEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProfileController extends Controller
{

    public function index(Request $request)
    {
        if ($request->user()->tokenCan($request->user()->user_type)) {

            return response()->json([
                'status' => true,
                'data' => [
                    'user' => User::where('id', '=', $request->user()->id)->with('wallet')->with('referrals')->with('referrer')->get()[0],
                ],
            ], 200);

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 422);
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'phone' => 'required',
            'acct_name' => 'nullable',
            'acct_number' => 'nullable',
            'acct_type' => 'nullable',
            'bank' => 'nullable',
            'state' => 'nullable',
            'lga' => 'nullable',
            'address' => 'nullable',
            'photo' => 'nullable',
            'description' => 'nullable',

            // User ID that we want to change
            'user_id' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        if ($request->has('user_id') && !$request->user_id==null) {
            $check = User::find($request->user_id);
            if ($check) {
                $idd = $request->user_id;
                // $request->offsetUnset('user_id');
                $request->replace( $request->except('user_id') );

                $user = User::where('id', '=', $idd)->update($request->all());
                if ($user) {
                    return response()->json([
                        'status' => true,
                        'data' => [
                            'user' => User::where('id', '=', $idd)->get()[0],
                        ],
                        'message' => 'User profile update successfull.'
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => "Failed, Try again!"
                    ], 422);
                }
            }else{
                return response()->json([
                    'status' => false,
                    'message' => "Failed, User not found!"
                ], 422);
            }
        }else{
            $user = User::where('id', '=', $request->user()->id)->update($request->all());
            if ($user) {
                return response()->json([
                    'status' => true,
                    'data' => [
                        'user' => User::where('id', '=', $request->user()->id)->get()[0],
                    ],
                    'message' => 'User profile update successfull.'
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => "Failed, Try again!"
                ], 422);
            }
        }

    }

    // upload user picture
    public function upload(Request $request)
    {
            $validator = Validator::make($request->all(), [
                'file' => 'required|max:5000|mimes:jpg,png,jpeg,heic',
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
                $fileNameToStore = $fileName."_".time().".".$fileExt;
                $request->file("file")->storeAs("public/user", $fileNameToStore);

                // $url = url('/storage/user/'.$fileNameToStore);
                $url = '/storage/user/'.$fileNameToStore;
                // $url = Storage::disk('s3')->url("user/".$fileNameToStore);

                return response()->json([
                    'status' => true,
                    'url'=> $url,
                    'message' => "File is successfully uploaded.",
                ]);

            }else{
                return response()->json([
                    'status' => false,
                    'message' => "Error! file upload invalid. Try again."
                ], 422);
            }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        if ($request->user()->tokenCan($request->user()->user_type)) {

            $request->user()->update([
                'status' => 0,
            ]);

            $request->user()->tokens()->delete();

            return response()->json([
                'status' => true,
                'message' => 'Account Deleted.'
            ], 200);

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 422);
        }
    }

    public function hierarchy_l1(Request $request)
    {
        if ($request->user()->tokenCan($request->user()->user_type)) {
            $user = User::find($request->user()->id);

            $l1 = $user->downline()->get();

            if(count($l1)>0){
                return response()->json([
                    'status' => true,
                    'data' => [
                        'downline' => $l1,
                    ],
                ], 200);
            }else{
                return response()->json([
                    'status' => false,
                    'message' => "No refferals",
                ], 422);
            }

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 401);
        }
    }

    public function hierarchy_all_downline(Request $request)
    {
        if ($request->user()->tokenCan($request->user()->user_type)) {
            $user = User::find($request->user()->id);

            $all_d = $user->allDownline()->get();

            if(count($all_d)>0){
                return response()->json([
                    'status' => true,
                    'data' => [
                        'allDownline' => $all_d,
                    ],
                ], 200);
            }else{
                return response()->json([
                    'status' => false,
                    'message' => "No refferals",
                ], 422);
            }

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 401);
        }
    }



    public function networker_register_another(Request $request)
    {

        if ($request->user()->tokenCan("Networker")) {

            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required|email|unique:users,email',
                'phone' => 'required',
                // 'store_name' => 'required|unique:users,store_name',
                // 'ref_id' => 'nullable',
                // 'package_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $request['user_type'] = "Networker";
            // $request['store_id'] = $request->user()->store_id;
            $request['status'] = "1";
            $request['photo'] = "/user/default.png";

            $request['package_id'] = $request->user()->package_id;

            $request['ref_id'] = $request->user()->ref_id;  //the id of the vendor that registered you
            $request['my_ref_id'] = "NL-".rand(100000, 999999);

            $pass = Str::random(8);

            $request['password'] = Hash::make($pass);

            $user = User::create($request->all());

            if ($user) {
                $verify2 =  DB::table('password_reset_tokens')->where([
                    ['email', $request->all()['email']]
                ]);

                if ($verify2->exists()) {
                    $verify2->delete();
                }
                $pin = rand(10000000, 99999999);
                DB::table('password_reset_tokens')
                    ->insert(
                        [
                            'email' => $request->all()['email'],
                            'token' => $pin
                        ]
                    );
            }else{
                return response()->json([
                    'status' => false,
                    'message' => "Failed, Try again!"
                ], 422);
            }

            Mail::to($request->email)->send(new VerifyEmail($pin, $pass, $request->email));

            // $token = $user->createToken('NL-Networker', ['Vendor'])->plainTextToken;

            return response()->json([
                'status' => true,
                'data' => [
                    'user' => User::where('id', '=', $user->id)->with('package')->get()[0],
                    // 'token' =>$token,
                ],
                'message' => 'Registration successfull, an email has been sent for verification.'
            ]);


        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 422);
        }
    }

}
