<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{{ config('app.name') }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<style>
/* Global Styles */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    margin: 0 !important;
    padding: 0 !important;
    width: 100%;
    -webkit-text-size-adjust: none;
}

/* Body text style */
body {
    font-family: Arial, sans-serif;
    font-size: 14px;
    line-height: 1.6;
    color: #6B7280;
    background-color: #f9fafb;
}

/* Header text style */
.header {
    padding: 20px;
    text-align: center;
    /* background: #3b82f6; */
    /* color: white; */
    font-size: 14px;
    width: 60%;
    margin: auto;
}

/* Logo style */
.logo {
    width: auto;
    height: 100px;
    margin: auto;
    display: block;
}

/* Inner body style */
.inner-body {
    padding: 30px;
    background: #ffffff;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin: 0 auto;
    width: 60%;
}

/* Footer style */
.footer {
    padding: 20px;
    text-align: center;
    background: #f9fafb;
    color: #6B7280;
    font-size: 14px;
    width: 60%;
    margin: auto;
}

/* Button style */
.button {
    display: inline-block;
    padding: 10px 20px;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 4px;
    text-decoration: none;
    font-size: 16px;
    cursor: pointer;
}

/* Responsive Styles */
@media only screen and (max-width: 600px) {
    .inner-body {
        width: 100% !important;
    }

    .footer {
        width: 100% !important;
    }
}

@media only screen and (max-width: 500px) {
    .button {
        width: 100% !important;
    }
}
</style>
</head>
<body>

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 auto;">
<tr>
<td align="center">
<table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: auto;">

    <tr>
    <td class="header" width="100%" >
    <a href="{{ url('/') }}" style="display: inline-block;">
    <img src="{{url('/')}}pmall-logo.jpeg" class="logo" alt="Pmall Logo">
    </a>
    </td>
    </tr>


<!-- Email Body -->
<tr>
<td class="body" width="100%" cellpadding="0" cellspacing="0" style="border: hidden !important;">
<table class="inner-body" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: auto;">
<!-- Body content -->
<tr>
<td class="content-cell">

@yield('content')

{{-- <table class="subcopy" width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
    <td>
    {{ Illuminate\Mail\Markdown::parse($slot) }}
    </td>
    </tr>
    </table> --}}

</td>
</tr>
</table>
</td>
</tr>

<tr>
    <td>
    <table class="footer" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: auto;">
    <tr>
    <td class="content-cell" align="center">
     © {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')
    </td>
    </tr>
    </table>
    </td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>
