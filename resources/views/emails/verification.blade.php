<!DOCTYPE html>
<html>
<head>
    <title>Verify Your Email</title>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const token = "{{ $token }}"; // Ambil token dari server-side rendering

            function verifyEmail() {
                fetch("{{ url('/api/verify-email') }}/" + token, {
                    method: "GET", // Menggunakan GET untuk kesederhanaan
                    headers: {
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    }
                })
                    .then(response => response.json())
                    .then(data => {
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

            // Verifikasi otomatis saat halaman dimuat
            verifyEmail();

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
