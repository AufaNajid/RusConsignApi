<h1>Verify Your Email Address</h1>

<p>Before proceeding, please check your email for a verification link.</p>
<p>If you did not receive the email, <a href="{{ route('verification.se  nd') }}">click here to request another</a>.</p>

@if (session('status') == 'verification-link-sent')
    <p>A new verification link has been sent to your email address.</p>
@endif
