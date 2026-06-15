<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: Arial, sans-serif;
            background: #f3f6fb;
            color: #1f2937;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
        }

        .login-header {
            padding: 26px 26px 18px;
            border-bottom: 1px solid #e2e8f0;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 22px;
        }

        .brand-mark {
            display: grid;
            width: 42px;
            height: 42px;
            place-items: center;
            border-radius: 8px;
            background: #1d4ed8;
            color: #fff;
            font-weight: 800;
        }

        h1 {
            margin: 0;
            font-size: 26px;
            line-height: 1.2;
        }

        p {
            margin: 6px 0 0;
            color: #64748b;
        }

        form {
            padding: 26px;
        }

        .field {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 800;
        }

        input {
            width: 100%;
            min-height: 44px;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 15px;
        }

        input:focus {
            border-color: #1d4ed8;
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.14);
        }

        .error {
            margin-top: 6px;
            color: #b91c1c;
            font-size: 14px;
        }

        button {
            width: 100%;
            min-height: 44px;
            border: 0;
            border-radius: 8px;
            background: #1d4ed8;
            color: #fff;
            cursor: pointer;
            font-size: 15px;
            font-weight: 800;
        }

        .hint {
            margin-top: 16px;
            color: #64748b;
            font-size: 13px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <main class="login-card">
        <div class="login-header">
            <div class="brand">
                <div class="brand-mark">A</div>
                <strong>Attendance Admin</strong>
            </div>
            <h1>Admin Login</h1>
            <p>Sign in to manage employees and attendance data.</p>
        </div>

        <form method="POST" action="{{ route('admin.login.store') }}">
            @csrf

            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required>
                @error('password')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit">Login</button>

            <!-- <div class="hint">
                Default login: admin@example.com / admin123456
            </div> -->
        </form>
    </main>
</body>
</html>
