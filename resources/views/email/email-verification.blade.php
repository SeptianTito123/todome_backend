<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Email Verification</title>

    <style>
        body {
            background: #f5f5f5;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 480px;
            margin: 60px auto;
            background: white;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
            text-align: center;
        }

        h2 {
            color: #333;
            margin-bottom: 10px;
        }

        p {
            color: #666;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .btn {
            display: block;
            width: 100%;
            background: #6a4cfc;
            color: white;
            padding: 12px;
            font-size: 15px;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 15px;
        }

        .btn:hover {
            background: #593be0;
        }

        .footer {
            margin-top: 25px;
            font-size: 13px;
            color: #999;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Email Verification Successful</h2>
    <p>
        Your email has been verified successfully.  
        You can now return to the ToDoMe app and log in.
    </p>

    <a href="todome://login" class="btn">Open ToDoMe App</a>

    <p class="footer">Thank you for using ToDoMe!</p>
</div>

</body>
</html>
