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
    <!-- Chart.js para Gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--text-main);
            text-decoration: none;
        }

        .brand span {
            color: var(--accent);
        }

        nav {
            display: flex;
            gap: 0.5rem;
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

        .nav-link.active {
            color: #a5b4fc;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }

        main {
            flex: 1;
            padding: 1.5rem;
            max-width: 1600px;
            width: 100%;
            margin: 0 auto;
        }

        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* Responsive Grid de KPIs */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .kpi-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--accent);
            border-radius: 0.5rem;
            padding: 1rem;
        }

        .kpi-title {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.35rem;
        }

        .kpi-value {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .kpi-subtitle {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.825rem;
        }

        th {
            background-color: #0f172a;
            color: var(--text-muted);
            font-weight: 600;
            padding: 0.65rem 0.85rem;
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
        }

        td {
            padding: 0.65rem 0.85rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        tr:hover td {
            background-color: rgba(255, 255, 255, 0.02);
        }

        /* Estilo da Linha de Filtros na Tabela */
        .filter-row th {
            padding: 0.35rem 0.5rem !important;
            background-color: #1e293b !important;
        }

        .filter-input {
            width: 100%;
            background-color: #0f172a;
            border: 1px solid var(--border-color);
            color: var(--text-main);
            border-radius: 0.25rem;
            padding: 0.25rem 0.4rem;
            font-size: 0.75rem;
        }

        .filter-input:focus {
            border-color: var(--accent);
            outline: none;
        }

        .form-control, .form-select {
            width: 100%;
            background-color: #0f172a;
            border: 1px solid var(--border-color);
            color: var(--text-main);
            border-radius: 0.375rem;
            padding: 0.45rem 0.75rem;
            font-size: 0.825rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent);
            outline: none;
        }

        .form-group {
            margin-bottom: 0.85rem;
        }

        .form-label {
            display: block;
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 0.35rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.45rem 0.85rem;
            border-radius: 0.375rem;
            font-size: 0.825rem;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-primary {
            background-color: var(--accent);
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: var(--accent-hover);
        }

        .btn-secondary {
            background-color: var(--border-color);
            color: var(--text-main);
        }

        .btn-secondary:hover {
            background-color: #475569;
        }

        .btn-danger {
            background-color: var(--danger);
            color: #ffffff;
        }

        .badge {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-falta { background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.4); }
        .badge-separado { background: rgba(245, 158, 11, 0.2); color: #fcd34d; border: 1px solid rgba(245, 158, 11, 0.4); }
        .badge-retirado { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.4); }
        .badge-fabrica { background: rgba(59, 130, 246, 0.2); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.4); }
        .badge-kanban { background: rgba(168, 85, 247, 0.2); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.4); }

        .badge-pendente { background: rgba(245, 158, 11, 0.2); color: #fcd34d; }
        .badge-faturado { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }
        .badge-pago { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1.25rem;
            font-size: 0.85rem;
        }
        .alert-success { background-color: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #6ee7b7; }
        .alert-danger { background-color: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5; }

        /* Custom Dark Mode Pagination Styles */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
            flex-wrap: wrap;
            gap: 0.5rem;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .pagination-container ul.pagination {
            display: flex;
            list-style: none;
            gap: 0.25rem;
            margin: 0;
            padding: 0;
        }

        .pagination-container .page-item .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.35rem 0.7rem;
            border-radius: 0.375rem;
            background-color: #0f172a;
            border: 1px solid var(--border-color);
            color: var(--text-main);
            text-decoration: none;
            font-size: 0.8rem;
            transition: all 0.2s;
        }

        .pagination-container .page-item.active .page-link {
            background-color: var(--accent);
            border-color: var(--accent);
            color: #ffffff;
            font-weight: 600;
        }

        .pagination-container .page-item.disabled .page-link {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .pagination-container .page-item .page-link:hover:not(.disabled) {
            background-color: var(--border-color);
        }

        .pagination-container svg {
            width: 14px !important;
            height: 14px !important;
            max-width: 14px !important;
            max-height: 14px !important;
            fill: currentColor;
            display: inline-block;
        }
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
            <a href="{{ route('estoque.index') }}" class="nav-link {{ request()->routeIs('estoque.*') ? 'active' : '' }}">
                📦 Estoque PCP
            </a>
            <a href="{{ route('compras.index') }}" class="nav-link {{ request()->routeIs('compras.*') ? 'active' : '' }}">
                🛒 Compras
            </a>
            @if(auth()->check() && auth()->user()->isAdmin())
                <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    👥 Usuários
                </a>
            @endif
        </nav>
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            @auth
                <div style="text-align: right; font-size: 0.75rem;">
                    <div style="font-weight: 600; color: var(--text-main);">{{ auth()->user()->name }}</div>
                    <span class="badge badge-fabrica" style="font-size: 0.6rem; padding: 0.1rem 0.3rem;">{{ auth()->user()->role }}</span>
                </div>
                <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; background-color: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3);">
                        🚪 Sair
                    </button>
                </form>
            @endauth
        </div>
    </header>

    <main>
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
