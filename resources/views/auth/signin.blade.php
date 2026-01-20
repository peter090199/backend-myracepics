<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign In</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f2f4f7;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .card {
            background: #ffffff;
            padding: 35px;
            border-radius: 8px;
            width: 100%;
            max-width: 380px;
            box-shadow: 0 12px 30px rgba(0,0,0,.12);
            text-align: center;
        }
        .card h2 {
            margin-bottom: 10px;
        }
        .card p {
            color: #666;
            font-size: 14px;
            margin-bottom: 25px;
        }
        .google-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: #4285F4;
            color: #fff;
            padding: 12px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
        }
        .google-btn img {
            width: 20px;
            height: 20px;
            background: #fff;
            padding: 3px;
            border-radius: 2px;
        }
    </style>
</head>
<body>

<div class="card">
    <h2>Sign In</h2>
    <p>Login using your Google account</p>

    <a href="{{ route('redirect.google') }}" class="google-btn">
        <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google">
        Sign in with Google
    </a>
</div>

</body>
</html>
