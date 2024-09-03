<!DOCTYPE html>
<html>
<head>
    <title>Verify Your Email</title>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Prepare the token
            let token = "{{ request()->query('token') }}";

            // Function to send the POST request for email verification
            function verifyEmail() {
                fetch("{{ url('/api/verify-email') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({ token: token })
                })
                    .then(response => response.json())
                    .then(data => {
                        // Redirect or show a success message
                        if (data.message === 'Email verified successfully') {
                            window.location.href = "/verification-success";
                        } else {
                            window.location.href = "/verification-failed";
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        window.location.href = "/verification-failed";
                    });
            }

            // Automatically verify email on page load
            verifyEmail();

            // If the user clicks the button, verify email manually
            document.getElementById("verifyButton").addEventListener("click", function() {
                verifyEmail();
            });
        });
    </script>
</head>
<body>
<h1>Verify Your Email</h1>
<p>We are verifying your email address. Please wait...</p>
<p>If you are not automatically redirected, click the button below to verify your email manually.</p>
<button id="verifyButton">Verify Email Manually</button>
</body>
</html>
