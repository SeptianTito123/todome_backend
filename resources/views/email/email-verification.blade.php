<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification Success</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #333;
        }

        .container {
            text-align: center;
            width: 100%;
            max-width: 420px;
            padding: 40px;
            /* Border tipis seperti di gambar */
            border: 1px solid #eaeaea; 
            border-radius: 8px;
            background-color: #fff;
            /* Box shadow opsional agar sedikit pop-up, bisa dihapus jika ingin flat 100% */
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        /* Lingkaran Hijau */
        .success-icon-circle {
            width: 70px;
            height: 70px;
            background-color: #50E3C2; /* Warna mint/hijau cerah mirip gambar */
            background-color: #4cd964; /* Atau hijau sukses standar */
            border-radius: 50%;
            margin: 0 auto 25px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        /* Ikon Centang CSS murni */
        .checkmark {
            display: inline-block;
            width: 22px;
            height: 11px;
            border-bottom: 4px solid #fff;
            border-left: 4px solid #fff;
            transform: rotate(-45deg);
            margin-bottom: 6px; /* Sedikit penyesuaian posisi */
        }

        h1 {
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 10px 0;
            color: #2c3e50;
        }

        p.subtitle {
            font-size: 15px;
            color: #7f8c8d;
            margin: 0 0 30px 0;
            line-height: 1.5;
        }

        /* Footer khusus permintaan Anda */
        .footer {
            font-size: 13px;
            color: #bdc3c7; /* Abu-abu muda */
            margin-top: 20px;
            font-weight: 500;
        }

        /* Responsif untuk layar kecil */
        @media (max-width: 480px) {
            .container {
                border: none;
                box-shadow: none;
                padding: 20px;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <!-- Ikon Centang Hijau -->
        <div class="success-icon-circle">
            <div class="checkmark"></div>
        </div>

        <!-- Judul & Subjudul -->
        <h1>Email successfully verified!</h1>
        <p class="subtitle">
            Your email is now active. You can verify your login in the app.
        </p>

        <!-- Footer yang direquest -->
        <p class="footer">Thank you for using ToDoMe!</p>
    </div>

</body>
</html>