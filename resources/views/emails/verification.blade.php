<!DOCTYPE html>
<html>
<head>
    <title>Email Verification</title>
</head>
<body>
<h1>Hello, {{ $user->name }}!</h1>
<p>Please click the link below to verify your email address:</p>
<a href="{{ url('/verify-email?token=' . $token) }}" target="_blank">Verify Email</a>
<p>Thank you for registering with us!</p>
</body>
</html>
