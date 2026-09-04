<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login | Shreeyash Group</title>
    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            --brand: #1d4ed8;
            --brand-dark: #173f9c;
            --ink: #0f172a;
            --muted: #64748b;
            --line: #dbe5f3;
            --surface: #ffffff;
            --soft: #eef5ff;
        }

        body {
            margin: 0;
            min-height: 100vh;
            padding: 32px;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(29, 78, 216, 0.16), transparent 34%),
                linear-gradient(135deg, #f8fbff 0%, #eef4fb 45%, #f6f8fb 100%);
            color: var(--ink);
        }

        .login-shell {
            width: min(100%, 980px);
            min-height: calc(100vh - 64px);
            margin: 0 auto;
            display: grid;
            place-items: center;
        }

        .login-card {
            width: 100%;
            display: grid;
            grid-template-columns: 0.95fr 1.05fr;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.32);
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.16);
            backdrop-filter: blur(18px);
        }

        .brand-panel {
            position: relative;
            display: flex;
            min-height: 540px;
            flex-direction: column;
            justify-content: space-between;
            padding: 38px;
            background:
                linear-gradient(150deg, rgba(15, 23, 42, 0.92), rgba(30, 64, 175, 0.88)),
                url("{{ asset('images/logo.png') }}") center 38% / 210px auto no-repeat;
            color: #fff;
        }

        .brand-panel::after {
            position: absolute;
            inset: 0;
            content: "";
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.18), rgba(15, 23, 42, 0.72));
        }

        .brand-panel > * {
            position: relative;
            z-index: 1;
        }

        .brand-kicker {
            width: fit-content;
            padding: 8px 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .brand-copy h2 {
            max-width: 360px;
            margin: 0 0 14px;
            font-size: 34px;
            line-height: 1.08;
        }

        .brand-copy p {
            max-width: 340px;
            margin: 0;
            color: rgba(255, 255, 255, 0.78);
            font-size: 15px;
            line-height: 1.7;
        }

        .brand-stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-top: 28px;
        }

        .brand-stat {
            padding: 14px;
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
        }

        .brand-stat strong {
            display: block;
            font-size: 20px;
        }

        .brand-stat span {
            display: block;
            margin-top: 4px;
            color: rgba(255, 255, 255, 0.72);
            font-size: 12px;
        }

        .form-panel {
            display: flex;
            min-height: 540px;
            flex-direction: column;
            justify-content: center;
            padding: 48px;
            background: var(--surface);
        }

        .login-header {
            margin-bottom: 30px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
        }

        .brand-mark {
            display: grid;
            width: 52px;
            height: 52px;
            place-items: center;
            overflow: hidden;
            border-radius: 14px;
            background: var(--brand);
            color: #fff;
            font-weight: 800;
            box-shadow: 0 12px 24px rgba(29, 78, 216, 0.22);
        }

        .brand-mark img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .brand strong {
            display: block;
            font-size: 17px;
        }

        .brand span {
            display: block;
            margin-top: 2px;
            color: var(--muted);
            font-size: 13px;
        }

        h1 {
            margin: 0;
            font-size: 32px;
            line-height: 1.2;
        }

        p {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 16px;
        }

        form {
            padding: 0;
        }

        .field {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 800;
            color: #172033;
        }

        input {
            width: 100%;
            min-height: 52px;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #f8fbff;
            color: var(--ink);
            font-size: 16px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        input:focus {
            border-color: var(--brand);
            background: #fff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.14);
        }

        .password-wrap {
            position: relative;
        }

        .password-wrap input {
            padding-right: 82px;
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 8px;
            width: auto;
            min-height: 36px;
            padding: 0 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
            color: #1e293b;
            font-size: 13px;
            font-weight: 800;
            transform: translateY(-50%);
        }

        .password-toggle:hover {
            background: var(--soft);
        }

        .error {
            margin-top: 6px;
            color: #b91c1c;
            font-size: 14px;
        }

        .login-submit {
            width: 100%;
            min-height: 52px;
            border: 0;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--brand), #2563eb);
            color: #fff;
            cursor: pointer;
            font-size: 16px;
            font-weight: 800;
            box-shadow: 0 14px 26px rgba(29, 78, 216, 0.22);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .login-submit:hover {
            box-shadow: 0 18px 32px rgba(29, 78, 216, 0.28);
            transform: translateY(-1px);
        }

        .login-submit:active {
            transform: translateY(0);
        }

        .hint {
            margin-top: 16px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.5;
        }

        @media (max-width: 820px) {
            body {
                padding: 18px;
            }

            .login-shell {
                min-height: calc(100vh - 36px);
            }

            .login-card {
                max-width: 480px;
                grid-template-columns: 1fr;
                border-radius: 14px;
            }

            .brand-panel {
                min-height: auto;
                padding: 26px;
                background:
                    linear-gradient(150deg, rgba(15, 23, 42, 0.94), rgba(30, 64, 175, 0.9)),
                    url("{{ asset('images/logo.png') }}") right 24px center / 92px auto no-repeat;
            }

            .brand-copy h2 {
                margin-top: 40px;
                font-size: 26px;
            }

            .brand-copy p {
                font-size: 14px;
            }

            .brand-stats {
                grid-template-columns: 1fr;
            }

            .form-panel {
                min-height: auto;
                padding: 30px 24px;
            }

            h1 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <main class="login-shell">
        <section class="login-card" aria-label="Admin login">
            <aside class="brand-panel">
                <div class="brand-kicker">Shreeyash Group</div>

                <div class="brand-copy">
                    <h2>Manage site attendance with confidence.</h2>
                    <p>Secure access for company admins to review employees, attendance, project data, and daily operations.</p>

                    <div class="brand-stats" aria-label="Admin features">
                        <div class="brand-stat">
                            <strong>Live</strong>
                            <span>Attendance overview</span>
                        </div>
                        <div class="brand-stat">
                            <strong>Secure</strong>
                            <span>Admin access</span>
                        </div>
                    </div>
                </div>
            </aside>

            <div class="form-panel">
                <div class="login-header">
                    <div class="brand">
                        <div class="brand-mark">
                            <img src="{{ asset('images/logo.png') }}" alt="Shreeyash Group">
                        </div>
                        <div>
                            <strong>Attendance Admin</strong>
                            <span>Control panel</span>
                        </div>
                    </div>
                    <h1>Welcome back</h1>
                    <p>Sign in to manage employees and attendance data.</p>
                </div>

                <form method="POST" action="{{ route('admin.login.store') }}">
                    @csrf

                    <div class="field">
                        <label for="email">Email address</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                        @error('email')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <div class="password-wrap">
                            <input id="password" name="password" type="password" autocomplete="current-password" required>
                            <button class="password-toggle" type="button" data-password-toggle aria-controls="password" aria-label="Show password">Show</button>
                        </div>
                        @error('password')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>

                    <button class="login-submit" type="submit">Login</button>

                    <!-- <div class="hint">
                        Default login: admin@example.com / admin123456
                    </div> -->
                </form>
            </div>
        </section>
    </main>
    <script>
        document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.getElementById(button.getAttribute('aria-controls'));
                var shouldShow = input.type === 'password';

                input.type = shouldShow ? 'text' : 'password';
                button.textContent = shouldShow ? 'Hide' : 'Show';
                button.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
            });
        });
    </script>
</body>
</html>
