<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'PCP & Compras') }} - TOTVS Protheus</title>
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
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
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
            flex-direction: column;
        }

        header {
            background-color: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 0.85rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 700;
            font-size: 1.15rem;
            color: var(--text-main);
            text-decoration: none;
        }

        .brand span {
            color: var(--accent);
        }

        nav {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .nav-link {
            color: var(--text-muted);
            text-decoration: none;
            padding: 0.4rem 0.85rem;
            border-radius: 0.375rem;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--text-main);
            background-color: rgba(99, 102, 241, 0.15);
        }

        main {
            flex: 1;
            padding: 1.5rem;
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
        }

        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
            text-align: left;
        }

        th, td {
            padding: 0.65rem 0.75rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        th {
            background-color: rgba(15, 23, 42, 0.6);
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.7rem;
            white-space: nowrap;
        }

        tr:hover td {
            background-color: rgba(255, 255, 255, 0.02);
        }

        .filter-row th {
            padding: 0.35rem;
            background-color: rgba(30, 41, 59, 0.9);
        }

        .filter-input {
            width: 100%;
            background-color: #0f172a;
            border: 1px solid var(--border-color);
            border-radius: 0.3rem;
            padding: 0.25rem 0.4rem;
            color: var(--text-main);
            font-size: 0.725rem;
            outline: none;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-falta { background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); }
        .badge-separado { background: rgba(245, 158, 11, 0.2); color: #fcd34d; border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge-retirado { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-fabrica { background: rgba(99, 102, 241, 0.2); color: #a5b4fc; border: 1px solid rgba(99, 102, 241, 0.3); }
        .badge-kanban { background: rgba(168, 85, 247, 0.2); color: #d8b4fe; border: 1px solid rgba(168, 85, 247, 0.3); }

        .badge-pendente { background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); }
        .badge-antecipado { background: rgba(245, 158, 11, 0.2); color: #fcd34d; border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge-faturado { background: rgba(59, 130, 246, 0.2); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.3); }
        .badge-pago { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.3); }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.85rem;
            border-radius: 0.375rem;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-primary { background-color: var(--accent); color: white; }
        .btn-primary:hover { background-color: var(--accent-hover); }
        .btn-secondary { background-color: var(--border-color); color: var(--text-main); }
        .btn-secondary:hover { background-color: #475569; }

        .form-group { margin-bottom: 0.85rem; }
        .form-label { display: block; font-size: 0.75rem; font-weight: 500; color: var(--text-muted); margin-bottom: 0.25rem; }
        .form-control, .form-select {
            width: 100%;
            background-color: #0f172a;
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
            padding: 0.4rem 0.6rem;
            color: var(--text-main);
            font-size: 0.8rem;
            outline: none;
        }

        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--border-color);
            font-size: 0.8rem;
            color: var(--text-muted);
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .alert-toast {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            padding: 0.85rem 1.25rem;
            border-radius: 0.5rem;
            font-size: 0.85rem;
            font-weight: 500;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .alert-toast-success { background-color: #065f46; color: #a7f3d0; border: 1px solid #059669; }
        .alert-toast-error { background-color: #991b1b; color: #fecaca; border: 1px solid #dc2626; }
    </style>
</head>
<body>
    <header>
        <a href="{{ route('dashboard') }}" class="brand">
            ⚡ TOTVS Protheus <span>PCP & Compras</span>
        </a>
        <nav>
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                📊 Dashboard
            </a>
            @if(auth()->user() && auth()->user()->isEstoque())
                <a href="{{ route('estoque.index') }}" class="nav-link {{ request()->routeIs('estoque.*') ? 'active' : '' }}">
                    📦 Estoque PCP
                </a>
            @endif
            @if(auth()->user() && auth()->user()->isCompras())
                <a href="{{ route('compras.index') }}" class="nav-link {{ request()->routeIs('compras.*') ? 'active' : '' }}">
                    🛒 Compras
                </a>
            @endif
            @if(auth()->user() && auth()->user()->isAdmin())
                <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    👥 Usuários
                </a>
            @endif
        </nav>

        @auth
            <div style="display: flex; align-items: center; gap: 0.75rem; border-left: 1px solid var(--border-color); padding-left: 1rem;">
                <div style="text-align: right; font-size: 0.75rem;">
                    <div style="font-weight: 600; color: #f8fafc;">{{ auth()->user()->name }}</div>
                    @php
                        $userBadge = match(auth()->user()->role) {
                            'ADMIN' => 'badge-faturado',
                            'COMPRAS' => 'badge-antecipado',
                            'ESTOQUE' => 'badge-separado',
                            default => 'badge-pendente'
                        };
                    @endphp
                    <span class="badge {{ $userBadge }}">{{ auth()->user()->role }}</span>
                </div>
                <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; color: #fca5a5;">
                        🚪 Sair
                    </button>
                </form>
            </div>
        @endauth
    </header>

    <main>
        @if(session('success'))
            <div class="alert-toast alert-toast-success" id="toastSuccess">
                ✅ {{ session('success') }}
            </div>
            <script>setTimeout(() => { document.getElementById('toastSuccess')?.remove(); }, 4000);</script>
        @endif

        @if(session('error'))
            <div class="alert-toast alert-toast-error" id="toastError">
                ❌ {{ session('error') }}
            </div>
            <script>setTimeout(() => { document.getElementById('toastError')?.remove(); }, 4000);</script>
        @endif

        @yield('content')
    </main>
</body>
</html>
