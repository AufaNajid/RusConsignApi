<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite('resources/css/app.css')
    <style scoped>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

        .full-screen-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: url('{{ asset('images/bg-image.png') }}') center/cover no-repeat;
        }
        body {
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f0f0f0;
        }
        .wrapper {
            width: 90%;
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            box-sizing: border-box;
            border-radius: 15px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        .header img {
            margin-right: 15px;
            width: 150px;
            height: auto;
        }
        .content {
            text-align: center;
            margin-bottom: 20px;
        }
        .content img {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
        }
        .content p {
            margin-bottom: 20px;
        }
        .content button {
            transition: transform 0.3s ease-in-out;
            background: linear-gradient(to right, #38BDF8, #1E3A8A);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 18px;
            font-weight: bold;
            padding: 15px 30px;
            cursor: pointer;
        }
        .content button:hover {
            transform: scale(1.05);
        }
        .footer {
            text-align: center;
        }
        .bottom-text {
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .wrapper {
                padding: 15px;
            }
            .content img {
                width: 60px;
                height: 60px;
            }
            .content button {
                padding: 10px 20px;
                font-size: 16px;
            }
            .header img {
                width: 120px;
            }
        }

        @media (max-width: 480px) {
            .content p {
                font-size: 14px;
            }
            .content button {
                padding: 8px 16px;
                font-size: 14px;
            }
            .header img {
                width: 100px;
            }
        }
    </style>
    <title>Selamat</title>
</head>
<body>
<div class="full-screen-bg"></div>
<div class="wrapper">
    <div class="header">
        <img src="{{ asset('images/nearus.png') }}" alt="Logo Nearus" />
    </div>
    <div class="content">
        <img src="{{ asset('images/icon-mail.png') }}" alt="Mail Icon" />
        <div class="text-center text-black text-2xl font-semibold font-montserrat mb-4">
            Akun Sudah Diverifikasi
        </div>
        <p class="text-black text-lg font-medium font-montserrat leading-snug mb-10">
            Terima Kasih anda telah melakukan verifikasi akun anda.
        </p>
        <p class="text-black text-sm font-light font-montserrat leading-none bottom-text">
            Anda bisa menutup email ini setelah anda selesai melakukan verifikasi email
        </p>
            <button>
                Login
            </button>
        </a>

    </div>
</div>
</body>
</html><!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite('resources/css/app.css')
    <style scoped>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

        .full-screen-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: url('{{ asset('images/bg-image.png') }}') center/cover no-repeat;
        }
        body {
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f0f0f0;
        }
        .wrapper {
            width: 90%;
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            box-sizing: border-box;
            border-radius: 15px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        .header img {
            margin-right: 15px;
            width: 150px;
            height: auto;
        }
        .content {
            text-align: center;
            margin-bottom: 20px;
        }
        .content img {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
        }
        .content p {
            margin-bottom: 20px;
        }
        .content button {
            transition: transform 0.3s ease-in-out;
            background: linear-gradient(to right, #38BDF8, #1E3A8A);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 18px;
            font-weight: bold;
            padding: 15px 30px;
            cursor: pointer;
        }
        .content button:hover {
            transform: scale(1.05);
        }
        .footer {
            text-align: center;
        }
        .bottom-text {
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .wrapper {
                padding: 15px;
            }
            .content img {
                width: 60px;
                height: 60px;
            }
            .content button {
                padding: 10px 20px;
                font-size: 16px;
            }
            .header img {
                width: 120px;
            }
        }

        @media (max-width: 480px) {
            .content p {
                font-size: 14px;
            }
            .content button {
                padding: 8px 16px;
                font-size: 14px;
            }
            .header img {
                width: 100px;
            }
        }
    </style>
    <title>Selamat</title>
</head>
<body>
<div class="full-screen-bg"></div>
<div class="wrapper">
    <div class="header">
        <img src="{{ asset('images/nearus.png') }}" alt="Logo Nearus" />
    </div>
    <div class="content">
        <img src="{{ asset('images/icon-mail.png') }}" alt="Mail Icon" />
        <div class="text-center text-black text-2xl font-semibold font-montserrat mb-4">
            Akun Sudah Diverifikasi
        </div>
        <p class="text-black text-lg font-medium font-montserrat leading-snug mb-10">
            Terima Kasih anda telah melakukan verifikasi akun anda.
        </p>
        <p class="text-black text-sm font-light font-montserrat leading-none bottom-text">
            Anda bisa menutup email ini setelah anda selesai melakukan verifikasi email
        </p> <a href="https://nearus.id/login">
            <button>
                Login
            </button>
        </a>

    </div>
</div>
</body>
</html>
