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
            margin-bottom: 0.25rem;
        }

        .kpi-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .kpi-subtitle {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        /* Tabelas Customizadas Dark Mode */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border-radius: 0.375rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.8rem;
        }

        th {
            background-color: #0f172a;
            color: #a5b4fc;
            padding: 0.6rem 0.75rem;
            font-weight: 600;
            border-bottom: 2px solid var(--border-color);
            white-space: nowrap;
        }

        td {
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        tr:hover td {
            background-color: rgba(255, 255, 255, 0.02);
        }

        /* Linha de Filtros de Colunas */
        tr.filter-row td, tr.filter-row th {
            background-color: #1e293b;
            padding: 0.35rem 0.5rem;
            border-bottom: 2px solid var(--accent);
        }

        .filter-input {
            width: 100%;
            padding: 0.2rem 0.4rem;
            font-size: 0.75rem;
            background-color: #0f172a;
            border: 1px solid var(--border-color);
            border-radius: 0.25rem;
            color: var(--text-main);
        }

        .filter-input:focus {
            outline: none;
            border-color: var(--accent);
        }

        /* Formulários e Inputs */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }

        .form-control, .form-select {
            width: 100%;
            padding: 0.45rem 0.65rem;
            background-color: #0f172a;
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
            color: var(--text-main);
            font-size: 0.8rem;
            transition: border-color 0.2s;
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--accent);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.45rem 0.85rem;
            border-radius: 0.375rem;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.2s;
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

        .badge {
            display: inline-block;
            padding: 0.15rem 0.45rem;
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
            font-weight: 700;
        }

        .pagination-container .page-item.disabled .page-link {
            opacity: 0.4;
            cursor: not-allowed;
        }

        /* Loading Spinner */
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(99, 102, 241, 0.2);
            border-top-color: #6366f1;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Global Loading Overlay Spinner -->
    <div id="globalLoadingOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(4px); z-index: 99999; flex-direction: column; align-items: center; justify-content: center; gap: 1.25rem; color: #f8fafc;">
        <div class="loading-spinner"></div>
        <div id="globalLoadingText" style="font-size: 1.15rem; font-weight: 600; color: #a5b4fc; text-align: center; max-width: 400px; padding: 0 1rem; line-height: 1.5;">
            ⏳ Buscando dados no Protheus... Aguarde...
        </div>
    </div>

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
                <a href="{{ route('importar.index') }}" class="nav-link {{ request()->routeIs('importar.*') ? 'active' : '' }}">
                    📥 Importar Base
                </a>
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

    <script>
        window.mostrarLoading = function(mensagem) {
            const overlay = document.getElementById('globalLoadingOverlay');
            const textEl = document.getElementById('globalLoadingText');
            if (mensagem && textEl) {
                textEl.innerHTML = mensagem;
            }
            if (overlay) {
                overlay.style.display = 'flex';
            }
        };

        window.ocultarLoading = function() {
            const overlay = document.getElementById('globalLoadingOverlay');
            if (overlay) {
                overlay.style.display = 'none';
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    if (this.checkValidity()) {
                        window.mostrarLoading();
                    }
                });
            });
        });
    </script>
</body>
</html>
