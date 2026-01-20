<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Google Login Success</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial; background: #f4f6f8; display:flex; justify-content:center; align-items:center; height:100vh; }
        .card { background: #fff; padding:30px; border-radius:8px; max-width:400px; text-align:center; box-shadow:0 10px 20px rgba(0,0,0,0.1);}
        .card h2 { color: #2e7d32; margin-bottom:10px;}
        .card p { margin:6px 0; }
        .btn { display:inline-block; margin-top:20px; padding:10px 18px; background:#4285F4; color:#fff; text-decoration:none; border-radius:4px;}
    </style>
</head>
<body>

<div class="card">
    <h2>Login Successful 🎉</h2>
    <p>You have signed in using Google</p>

    <p><strong>Name:</strong> {{ $user->fname }}</p>
    <p><strong>Email:</strong> {{ $user->email }}</p>

    <a href="/" class="btn">Go to Home</a>
</div>

</body>
</html>
