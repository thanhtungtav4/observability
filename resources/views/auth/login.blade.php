<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin Login</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,700|ibm-plex-mono:400,500" rel="stylesheet" />
        <style>
            :root {
                --bg: #f3efe7;
                --ink: #1a1b1f;
                --muted: #646468;
                --panel: rgba(255, 250, 244, 0.82);
                --line: rgba(36, 28, 21, 0.12);
                --orange: #ff6f2c;
                --shadow: 0 20px 40px rgba(31, 24, 18, 0.08);
            }

            * {
                box-sizing: border-box;
            }

            body {
                min-height: 100vh;
                margin: 0;
                display: grid;
                place-items: center;
                padding: 28px;
                font-family: "Space Grotesk", sans-serif;
                color: var(--ink);
                background:
                    radial-gradient(circle at top left, rgba(255, 167, 77, 0.35), transparent 32%),
                    radial-gradient(circle at bottom right, rgba(15, 143, 132, 0.12), transparent 28%),
                    linear-gradient(180deg, #faf7f2 0%, var(--bg) 44%, #efe7da 100%);
            }

            .login-shell {
                width: min(980px, 100%);
                display: grid;
                grid-template-columns: minmax(0, 1.1fr) minmax(320px, 420px);
                gap: 22px;
            }

            .panel {
                background: var(--panel);
                border: 1px solid var(--line);
                border-radius: 28px;
                box-shadow: var(--shadow);
                padding: 30px;
            }

            .hero h1 {
                margin: 0;
                font-size: clamp(42px, 6vw, 72px);
                line-height: 0.95;
                max-width: 9ch;
            }

            .eyebrow,
            .mono {
                font-family: "IBM Plex Mono", monospace;
            }

            .eyebrow {
                margin: 0 0 14px;
                color: #7f3200;
                font-size: 12px;
                font-weight: 700;
                letter-spacing: 0.12em;
                text-transform: uppercase;
            }

            .copy,
            .helper {
                color: var(--muted);
                line-height: 1.7;
            }

            .field {
                display: grid;
                gap: 8px;
                margin-bottom: 16px;
            }

            .field label {
                font-size: 12px;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: rgba(26, 27, 31, 0.54);
            }

            .field input {
                width: 100%;
                padding: 13px 14px;
                border-radius: 14px;
                border: 1px solid rgba(26, 27, 31, 0.12);
                background: rgba(255, 255, 255, 0.84);
                font: inherit;
                color: inherit;
            }

            .checkbox {
                display: flex;
                gap: 10px;
                align-items: center;
                color: var(--muted);
                font-size: 14px;
                margin: 18px 0 0;
            }

            .checkbox input {
                width: 18px;
                height: 18px;
            }

            .button {
                width: 100%;
                min-height: 48px;
                margin-top: 18px;
                border: 0;
                border-radius: 14px;
                background: linear-gradient(135deg, var(--orange), #ff9c52);
                color: white;
                font: inherit;
                font-weight: 700;
                cursor: pointer;
            }

            .error-list {
                margin: 0 0 18px;
                padding: 14px 16px;
                border-radius: 18px;
                background: rgba(203, 75, 60, 0.1);
                color: #8a2418;
            }

            .error-list ul {
                margin: 0;
                padding-left: 18px;
            }

            @media (max-width: 900px) {
                .login-shell {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>
    <body>
        <div class="login-shell">
            <section class="panel hero">
                <p class="eyebrow">Performance Hub</p>
                <h1>Admin access for release triage.</h1>
                <p class="copy">
                    Sign in to inspect portfolio health, compare deployments, and trace regressions across field and synthetic signals.
                </p>
                <p class="helper mono">Use `php artisan performance-hub:create-admin` if you still need your first admin account.</p>
            </section>

            <section class="panel">
                <p class="eyebrow">Admin Login</p>

                @if ($errors->any())
                    <div class="error-list">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}">
                    @csrf

                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required>
                    </div>

                    <label class="checkbox" for="remember">
                        <input id="remember" name="remember" type="checkbox" value="1" @checked(old('remember'))>
                        Keep this browser signed in
                    </label>

                    <button class="button" type="submit">Sign In</button>
                </form>
            </section>
        </div>
    </body>
</html>
