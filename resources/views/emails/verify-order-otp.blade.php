@extends('layouts.mail')

@section('content')

<h2>Verify Your Order OTP</h2>

Your OTP for order verification is: <strong>{{ $otp }}</strong>

Please use this OTP to verify your order. If you have any questions or concerns, feel free to reach out to us.

Thank you for shopping with {{ config('app.name') }}.

@endsection


