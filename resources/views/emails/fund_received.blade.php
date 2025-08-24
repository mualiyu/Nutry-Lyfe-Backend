@extends('layouts.mail')

@section('content')

@if ($name)
    <p>Dear {{ $name }},</p>
@else
    <p>Dear Valued Customer,</p>
@endif

    <p>You have successfully received ₦{{ number_format($amount, 2) }} from {{$senderEmail}}.</p>

    <p>If you did not authorize this transaction, please contact support immediately at <a href="mailto:support@nutrylyfe.com">support@nutrylyfe.com</a>.</p>

Thank you for using <a href="https://nutrylyfe.com">Nutrylyfe</a>.<br>
{{ config('app.name') }}

@endsection


