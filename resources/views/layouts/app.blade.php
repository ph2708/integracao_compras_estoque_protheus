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
            padding: 0.85rem 1.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
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
            padding: 1.25rem 1.75rem;
            max-width: 100%;
            width: 100%;
            margin: 0;
        }

        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            width: 100%;
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
            padding: 0.75rem 0.85rem;
            font-weight: 600;
            border-bottom: 2px solid var(--border-color);
            white-space: nowrap;
        }

        td {
            padding: 0.7rem 0.85rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        tr:hover td {
            background-color: rgba(255, 255, 255, 0.03);
        }

        /* Badges de Colunas Especiais (Produto Pai e Descrição Longa) */
        .badge-produto-pai {
            display: inline-block;
            padding: 0.4rem 0.75rem;
            font-size: 0.78rem;
            font-weight: 500;
            line-height: 1.45;
            color: #d8b4fe;
            background: rgba(168, 85, 247, 0.15);
            border: 1px solid rgba(168, 85, 247, 0.35);
            border-radius: 0.375rem;
            word-break: break-word;
            white-space: normal;
            max-width: 320px;
        }

        .badge-desc-longa {
            display: inline-block;
            padding: 0.4rem 0.75rem;
            font-size: 0.78rem;
            font-weight: 400;
            line-height: 1.45;
            color: #7dd3fc;
            background: rgba(56, 189, 248, 0.12);
            border: 1px solid rgba(56, 189, 248, 0.3);
            border-radius: 0.375rem;
            word-break: break-word;
            white-space: normal;
            max-width: 350px;
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
            background-color: #334155;
            color: var(--text-main);
        }

        .btn-secondary:hover {
            background-color: #475569;
        }

        /* Badges de Status PCP */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.7rem;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
        }

        .badge-falta { background-color: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.4); }
        .badge-separado { background-color: rgba(16, 185, 129, 0.2); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.4); }
        .badge-retirado { background-color: rgba(59, 130, 246, 0.2); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.4); }
        .badge-fabrica { background-color: rgba(245, 158, 11, 0.2); color: #fcd34d; border: 1px solid rgba(245, 158, 11, 0.4); }
        .badge-kanban { background-color: rgba(168, 85, 247, 0.2); color: #d8b4fe; border: 1px solid rgba(168, 85, 247, 0.4); }

        /* Paginação Dark Mode */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0 0 0;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .pagination {
            display: flex;
            gap: 0.25rem;
            list-style: none;
        }

        .pagination li a, .pagination li span {
            padding: 0.3rem 0.6rem;
            border-radius: 0.25rem;
            background-color: #1e293b;
            color: var(--text-main);
            text-decoration: none;
            border: 1px solid var(--border-color);
            font-size: 0.75rem;
        }

        .pagination li.active span {
            background-color: var(--accent);
            border-color: var(--accent);
        }

        /* Overlay e Spinner de Loading */
        #globalLoadingOverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(4px);
            z-index: 99999;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(99, 102, 241, 0.2);
            border-top: 5px solid var(--accent);
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

    <!-- Overlay Global de Carregamento -->
    <div id="globalLoadingOverlay">
        <div class="spinner"></div>
        <p id="globalLoadingText" style="margin-top: 1.25rem; font-size: 0.95rem; font-weight: 500; color: #a5b4fc;">
            Consultando Protheus... Por favor aguarde...
        </p>
    </div>

    <!-- Header / Navbar Principal -->
    <header>
        <a href="{{ route('compras.index') }}" class="brand">
            ⚡ TOTVS Protheus <span>PCP & Compras</span>
        </a>

        @auth
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

            @if(auth()->user()->canCloseOp())
            <a href="{{ route('fechamento-op.index') }}" class="nav-link {{ request()->routeIs('fechamento-op.*') ? 'active' : '' }}" style="color: #c084fc;">
                🔒 Fechamento de OP
            </a>
            @endif

            @if(auth()->user()->isAdmin())
            <a href="{{ route('importar.index') }}" class="nav-link {{ request()->routeIs('importar.*') ? 'active' : '' }}">
                📥 Importar Base
            </a>
            <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                👥 Usuários
            </a>
            @endif
        </nav>

        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="text-align: right; font-size: 0.75rem;">
                <div style="font-weight: 600; color: var(--text-main);">{{ auth()->user()->name }}</div>
                @if(auth()->user()->isAdmin())
                    <span style="font-size: 0.65rem; background: rgba(99, 102, 241, 0.2); color: #a5b4fc; padding: 0.1rem 0.35rem; border-radius: 0.2rem; border: 1px solid rgba(99, 102, 241, 0.4);">ADMIN</span>
                @else
                    <span style="font-size: 0.65rem; color: var(--text-muted);">USUÁRIO</span>
                @endif
            </div>

            <form action="{{ route('logout') }}" method="POST" style="margin: 0;" onsubmit="window.mostrarLoading('🚪 Encerrando sessão...')">
                @csrf
                <button type="submit" class="btn btn-secondary" style="padding: 0.3rem 0.65rem; font-size: 0.75rem; background-color: #991b1b; color: #fecaca; border: 1px solid #7f1d1d;">
                    🚪 Sair
                </button>
            </form>
        </div>
        @endauth
    </header>

    <!-- Conteúdo Principal Flutuante / Full-Width -->
    <main>
        <!-- Mensagens de Alerta Flash -->
        @if(session('success'))
            <div class="card" style="border-color: #059669; background-color: rgba(5, 150, 105, 0.15); color: #6ee7b7; padding: 0.75rem 1rem; margin-bottom: 1rem;">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="card" style="border-color: #dc2626; background-color: rgba(220, 38, 38, 0.15); color: #fca5a5; padding: 0.75rem 1rem; margin-bottom: 1rem;">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    <script>
        window.mostrarLoading = function(mensagem) {
            const overlay = document.getElementById('globalLoadingOverlay');
            const textLabel = document.getElementById('globalLoadingText');
            if (overlay) {
                if (mensagem) {
                    textLabel.innerText = mensagem;
                }
                overlay.style.display = 'flex';
            }
        };

        window.ocultarLoading = function() {
            const overlay = document.getElementById('globalLoadingOverlay');
            if (overlay) {
                overlay.style.display = 'none';
            }
        };
    </script>
</body>
</html>
