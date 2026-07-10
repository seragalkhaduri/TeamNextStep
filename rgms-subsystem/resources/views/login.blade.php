<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - نظام المجموعات البحثية الفرعي / Sign In - RGMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            --card-bg: rgba(30, 27, 75, 0.45);
            --border: rgba(255, 255, 255, 0.08);
            --accent: #6366f1;
            --accent-hover: #4f46e5;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --white: #ffffff;
            --error-bg: rgba(244, 63, 94, 0.15);
            --error-text: #f43f5e;
            --error-border: rgba(244, 63, 94, 0.25);
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg-gradient);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            overflow-x: hidden;
            position: relative;
        }

        /* Ambient Glow Background Blobs */
        .glow-1, .glow-2 {
            position: absolute;
            width: 350px;
            height: 350px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.15);
            filter: blur(80px);
            z-index: 1;
        }
        .glow-1 { top: 15%; left: 20%; }
        .glow-2 { bottom: 15%; right: 20%; background: rgba(139, 92, 246, 0.15); }

        .auth-container {
            width: 100%;
            max-width: 420px;
            z-index: 10;
        }

        .auth-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .logo-area {
            margin-bottom: 2rem;
        }
        .logo-area h2 {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: var(--white);
            margin-bottom: 0.25rem;
        }
        .logo-area p {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .form-group {
            margin-bottom: 1.25rem;
            text-align: left;
        }
        .form-group label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }
        .input-wrapper {
            position: relative;
        }
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--white);
            font-size: 0.9rem;
            font-family: inherit;
            transition: all 0.2s ease;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
            background: rgba(15, 23, 42, 0.8);
        }

        .btn-submit {
            width: 100%;
            padding: 0.75rem;
            background: var(--accent);
            color: var(--white);
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 1rem;
        }
        .btn-submit:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
        }
        .btn-submit:active {
            transform: translateY(0);
        }

        .alert-error {
            background: var(--error-bg);
            color: var(--error-text);
            border: 1px solid var(--error-border);
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.25);
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .tab-switcher {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .tab-button {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-secondary);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 0.85rem 1rem;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .tab-button.active {
            background: rgba(99, 102, 241, 0.2);
            color: var(--white);
            border-color: rgba(99, 102, 241, 0.45);
        }

        .panel.hidden {
            display: none;
        }

        /* Demo credentials helper */
        .credentials-helper {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            text-align: left;
        }
        .credentials-helper summary {
            font-size: 0.78rem;
            color: var(--text-secondary);
            cursor: pointer;
            font-weight: 600;
            user-select: none;
        }
        .credentials-helper summary:hover {
            color: var(--white);
        }
        .credentials-list {
            margin-top: 0.75rem;
            font-size: 0.75rem;
            background: rgba(15, 23, 42, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            padding: 0.75rem;
            color: var(--text-secondary);
            list-style: none;
        }
        .credentials-list li {
            margin-bottom: 0.4rem;
            display: flex;
            justify-content: space-between;
        }
        .credentials-list li:last-child {
            margin-bottom: 0;
        }
        .credentials-list code {
            background: rgba(99, 102, 241, 0.15);
            color: #a5b4fc;
            padding: 1px 4px;
            border-radius: 4px;
            font-family: monospace;
        }
    </style>
</head>
<body>

<div class="glow-1"></div>
<div class="glow-2"></div>

<div class="auth-container">
    <div class="auth-card">
        <div class="logo-area">
            <h2 style="font-family:'Cairo', sans-serif; font-size:1.6rem; font-weight:800;">🔬 RGMS Subsystem</h2>
            <p style="font-family:'Cairo', sans-serif; margin-top:0.3rem;">نظام المجموعات البحثية الفرعي</p>
            <p style="font-size:0.75rem; opacity:0.7;">Research Groups Management Subsystem</p>
        </div>

        @if(session('success'))
            <div class="alert-success">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert-error" style="direction:rtl; text-align:right; font-family:'Cairo', sans-serif;">
                ⚠️ {{ $errors->first() }}
            </div>
        @endif

        <form action="/login" method="POST">
            @csrf
            <div class="form-group">
                <label for="username" style="display:flex; justify-content:space-between; font-family:'Cairo', sans-serif; font-size:0.75rem;">
                    <span>اسم المستخدم</span>
                    <span>Username</span>
                </label>
                <div class="input-wrapper">
                    <input type="text" id="username" name="username" class="form-input" placeholder="Enter username / أدخل اسم المستخدم" value="{{ old('username') }}" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label for="password" style="display:flex; justify-content:space-between; font-family:'Cairo', sans-serif; font-size:0.75rem;">
                    <span>كلمة المرور</span>
                    <span>Password</span>
                </label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn-submit" style="font-family:'Cairo', sans-serif; font-weight:700;">تسجيل الدخول / Sign In</button>
        </form>

        <details class="credentials-helper" style="direction:rtl; text-align:right; font-family:'Cairo', sans-serif;">
            <summary style="display:flex; justify-content:space-between; direction:ltr; font-family:'Cairo', sans-serif;">
                <span style="font-size:0.75rem; opacity:0.8;">(Password: password)</span>
                <span>🔑 الباحثون والمدققون المصرح لهم</span>
            </summary>
            <ul class="credentials-list" style="direction:ltr; font-family:inherit;">
                <li><span>System Admin:</span> <code>sysadmin</code></li>
                <li><span>University Admin:</span> <code>uniadmin</code></li>
                <li><span>Auditor Staff:</span> <code>auditor</code></li>
                <li><span>Graduate Researcher:</span> <code>student01</code></li>
            </ul>
        </details>
    </div>
</div>

<script>
    const tabButtons = document.querySelectorAll('.tab-button');
    const panels = document.querySelectorAll('.panel');
    const activePanel = '{{ session('active_panel', 'login') }}';

    function showPanel(panelId) {
        panels.forEach(panel => panel.classList.toggle('hidden', panel.id !== panelId));
        tabButtons.forEach(button => button.classList.toggle('active', button.dataset.panel === panelId));
    }

    tabButtons.forEach(button => {
        button.addEventListener('click', () => showPanel(button.dataset.panel));
    });

    showPanel(activePanel === 'signup' ? 'signup-panel' : 'login-panel');
</script>
</body>
</html>
