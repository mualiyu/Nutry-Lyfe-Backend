<?php

namespace App\Http\Controllers;

use App\Mail\FundReceivedMail;
use App\Mail\FundTransferMail;
use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class FundWalletController extends Controller
{
    public function initiateFunding(Request $request)
    {
        // $request->validate([
        //     'amount' => 'required|numeric|min:500'
        // ]);
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $user = $request->user();
        list($authUrl, $reference, $error) = Payment::generateWalletFundingLink($user, $request->amount);

        if ($error) {
            return response()->json([
                'status' => false,
                'message' => $error
            ], 422);
        }

        // Create payment record
        Payment::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'status' => 'pending',
            'paystack_reference' => $reference,
            'paystack_url' => $authUrl,
            'payment_method' => 'paystack',
            'data' => json_encode(['purpose' => 'wallet_funding'])
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Payment initiated successfully',
            'data' => [
                'authorization_url' => $authUrl,
                'reference' => $reference
            ]
        ]);
    }

    public function verifyFunding(Request $request)
    {
          $validator = Validator::make($request->all(), [
              'reference' => 'required|string'
          ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        list($paystackData, $error) = Payment::verifyPaystack($request->reference);


        if ($error) {
            return response()->json([
                'status' => false,
                'message' => $error
            ], 400);
        }

        // Find payment record
        $payment = Payment::where('paystack_reference', $request->reference)->first();

        if (!$payment) {
            return response()->json([
                'status' => false,
                'message' => 'Payment record not found'
            ], 404);
        }

        if ($paystackData['status'] === 'success') {
            // Update payment status
            $payment->status = 'completed';
            $payment->save();

            // Update wallet balance
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $payment->user_id],
                ['balance' => 0, 'pv' => 0]
            );

            $wallet->balance += $payment->amount;
            $wallet->save();

            // Create transaction record
            $request->user()->transactions()->create([
                'user_id' => $payment->user_id,
                'type' => 'wallet_funding',
                'amount' => $payment->amount,
                'status' => 'completed',
                'transaction_id' => $payment->id,
                'description' => 'Wallet funding via Paystack'
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Wallet funded successfully',
                'data' => [
                    'new_balance' => $wallet->balance
                ]
            ]);
        }

        $payment->status = 'failed';
        $payment->save();

        return response()->json([
            'status' => false,
            'message' => 'Payment verification failed'
        ], 400);
    }



    //convert pv to naira 1 pv == 500 naira
    public function convertPvToNaira(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pv' => 'required|numeric|min:2|max:1000'
        ]);
        // ->after(function ($validator) use ($request) {
        //     if ($request->pv % 2 !== 0) {
        //         $validator->errors()->add('pv', 'PV must be in multiples of 2');
        //     }
        // });

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $naira = $request->pv * 500;

        if ($naira < 500) {
            return response()->json([
                'status' => false,
                'message' => 'Minimum conversion amount is 2PV = 1000 Naira'
            ], 422);
        }

        //check if he sah the required pv
        $wallet = Wallet::where('user_id', $request->user()->id)->first();
        if ($wallet->pv < $request->pv) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have enough PV to convert'
            ], 422);
        }

        // Update wallet balance
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['balance' => 0, 'pv' => 0]
        );

        $wallet->pv -= $request->pv;
        $wallet->save();
        $wallet->balance += $naira;
        $wallet->save();

        // Create transaction record
        $request->user()->transactions()->create([
            'user_id' => $request->user()->id,
            'type' => 'pv_conversion',
            'amount' => $naira,
            'status' => 'completed',
            'transaction_id' => uniqid('pv_conversion_'),
            'description' => 'PV to Naira conversion'
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Conversion successful',
            'data' => [
                'converted_amount' => $naira,
                'new_balance' => $wallet->balance,
                'new_pv' => $wallet->pv
            ]
        ]);
    }


    public function transferFunds(Request $request)
    {
         $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:500|max:1000000',
            'email' => 'required|email|exists:users,email'
        ]);


        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }


        $recipient = User::where('email', $request->email)->first();

        if (!$recipient) {
            return response()->json([
                'status' => false,
                'message' => 'Recipient not found'
            ], 422);
        }

        $wallet = Wallet::where('user_id', $request->user()->id)->first();

        if ($wallet->balance < $request->amount) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient funds'
            ], 422);
        }

        // Deduct amount from sender's wallet
        $wallet->balance -= $request->amount;
        $wallet->save();

        // Add amount to recipient's wallet
        $recipientWallet = Wallet::where('user_id', $recipient->id)->first();
        if (!$recipientWallet) {
            $recipientWallet = Wallet::create([
                'user_id' => $recipient->id,
                'balance' => 0,
                'pv' => 0
            ]);
        }
        $recipientWallet->balance += $request->amount;
        $recipientWallet->save();

        // send mail to both users
        Mail::to($recipient->email)->send(new FundReceivedMail($request->amount, $recipient->name, $request->user()->email));

        Mail::to($request->user()->email)->send(new FundTransferMail($request->amount, $request->user()->name, $recipient->email));

        // Create transaction record
        $request->user()->transactions()->create([
            'user_id' => $request->user()->id,
            'type' => 'fund_transfer',
            'amount' => $request->amount,
            'status' => 'completed',
            'transaction_id' => uniqid('fund_transfer_'),
            'description' => 'Funds transfer to ' . $recipient->email
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Funds transferred successfully',
            'data' => [
                'new_balance' => $wallet->balance - $request->amount
            ]
        ]);
    }

    // transactionHistory
    public function transactionHistory(Request $request)
    {
        $transactions = $request->user()->transactions()->get();

        if ($transactions->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No transactions found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $transactions
        ]);
    }

}
