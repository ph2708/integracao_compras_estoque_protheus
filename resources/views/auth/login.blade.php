<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PlanFacto - PCP & Compras | Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --border-color: #334155;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #6366f1;
            --accent-hover: #4f46e5;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .login-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .brand-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .brand-logo {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .brand-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .brand-subtitle {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: #cbd5e1;
            margin-bottom: 0.5rem;
        }

        .form-control {
            width: 100%;
            background-color: #0f172a;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            color: var(--text-main);
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            border-color: var(--accent);
        }

        .btn-submit {
            width: 100%;
            background-color: var(--accent);
            color: #ffffff;
            border: none;
            border-radius: 0.5rem;
            padding: 0.85rem;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-top: 0.5rem;
        }

        .btn-submit:hover {
            background-color: var(--accent-hover);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
        }

        .remember-flex {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .remember-flex input {
            accent-color: var(--accent);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand-header">
            <div class="brand-logo">⚡</div>
            <div class="brand-title">PlanFacto</div>
            <div class="brand-subtitle">PCP & Compras - Portal de Acesso Corporativo</div>
        </div>

        @if($errors->any())
            <div class="alert-error">
                @foreach($errors->all() as $error)
                    <div>⚠️ {{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if(session('success'))
            <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); color: #6ee7b7; padding: 0.75rem; border-radius: 0.5rem; font-size: 0.85rem; margin-bottom: 1.25rem;">
                ✅ {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">E-mail Corporativo</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="usuario@maquigeral.com.br" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label">Senha</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <div class="form-group remember-flex">
                <input type="checkbox" name="remember" id="remember">
                <label for="remember">Lembrar minhas credenciais</label>
            </div>

            <button type="submit" class="btn-submit">Entrar no Sistema ➔</button>
        </form>
    </div>
</body>
</html>
