<!DOCTYPE html>
<html>
<head>
    <title>Verify Your Email</title>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Prepare the data
            let token = "{{ request()->query('token') }}";

            // Send POST request automatically
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
        });
    </script>
</head>
<body>
<h1>Verifying Your Email...</h1>
<p>Please wait while we verify your email address.</p>
</body>
</html>
