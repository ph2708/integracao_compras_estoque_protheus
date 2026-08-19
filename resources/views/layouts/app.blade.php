<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCP & Compras | TOTVS Protheus</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Styles -->
    <style>
        :root {
            --bg-dark: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --border-color: rgba(255, 255, 255, 0.1);
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --accent: #06b6d4;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            padding-bottom: 3rem;
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(6, 182, 212, 0.15) 0px, transparent 50%);
            background-attachment: fixed;
        }

        header {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 50;
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
            font-size: 1.2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #a5b4fc, #38bdf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-links {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .nav-link {
            color: var(--text-muted);
            text-decoration: none;
            padding: 0.45rem 0.85rem;
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--text-main);
            background: rgba(255, 255, 255, 0.08);
        }

        .nav-link.active {
            border: 1px solid rgba(99, 102, 241, 0.3);
            background: rgba(99, 102, 241, 0.15);
        }

        .container {
            width: 100%;
            max-width: 1600px;
            margin: 1.5rem auto;
            padding: 0 1rem;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 0.85rem;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .btn {
            padding: 0.45rem 0.9rem;
            border-radius: 0.4rem;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.775rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-main);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.775rem;
            table-layout: auto;
        }

        th {
            background: rgba(15, 23, 42, 0.8);
            color: var(--text-muted);
            padding: 0.5rem;
            font-weight: 600;
            border-bottom: 1px solid var(--border-color);
            text-transform: uppercase;
            letter-spacing: 0.03em;
            font-size: 0.7rem;
            white-space: nowrap;
        }

        td {
            padding: 0.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: var(--text-main);
            vertical-align: middle;
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .filter-row th {
            background: rgba(15, 23, 42, 0.95);
            padding: 0.3rem 0.5rem;
            text-transform: none;
        }

        .filter-input {
            width: 100%;
            padding: 0.25rem 0.4rem;
            background: rgba(30, 41, 59, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 0.3rem;
            color: var(--text-main);
            font-size: 0.725rem;
            outline: none;
        }

        .filter-input:focus {
            border-color: var(--primary);
        }

        .badge {
            padding: 0.2rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.675rem;
            font-weight: 600;
            display: inline-block;
            white-space: nowrap;
        }

        .badge-falta { background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); }
        .badge-separado { background: rgba(245, 158, 11, 0.2); color: #fcd34d; border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge-retirado { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-fabrica { background: rgba(59, 130, 246, 0.2); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.3); }
        .badge-kanban { background: rgba(168, 85, 247, 0.2); color: #d8b4fe; border: 1px solid rgba(168, 85, 247, 0.3); }

        .badge-antecipado { background: rgba(245, 158, 11, 0.2); color: #fcd34d; }
        .badge-faturado { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }
        .badge-pago { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; }
        .badge-pendente { background: rgba(100, 116, 139, 0.2); color: #cbd5e1; }

        .form-group {
            margin-bottom: 0.85rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.3rem;
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .form-control, .form-select {
            width: 100%;
            padding: 0.35rem 0.5rem;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid var(--border-color);
            border-radius: 0.4rem;
            color: var(--text-main);
            font-size: 0.75rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
            padding: 0.6rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.8rem;
        }

        /* Modern Pagination Styling Fix */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
            flex-wrap: wrap;
            gap: 0.5rem;
            font-size: 0.775rem;
        }

        /* Corrige os ícones SVG gigantes da paginação nativa do Laravel */
        nav[role="navigation"] svg {
            width: 1rem !important;
            height: 1rem !important;
            max-width: 1rem !important;
            max-height: 1rem !important;
            display: inline-block !important;
            vertical-align: middle !important;
        }

        nav[role="navigation"] {
            display: flex !important;
            align-items: center !important;
            gap: 0.25rem !important;
        }

        nav[role="navigation"] > div:first-child {
            display: none !important; /* Esconde o texto nativo do Tailwind */
        }

        nav[role="navigation"] span,
        nav[role="navigation"] a {
            padding: 0.3rem 0.55rem !important;
            border-radius: 0.35rem !important;
            background: rgba(15, 23, 42, 0.8) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-main) !important;
            text-decoration: none !important;
            font-size: 0.75rem !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            line-height: 1 !important;
        }

        nav[role="navigation"] a:hover {
            background: rgba(99, 102, 241, 0.3) !important;
            border-color: var(--primary) !important;
        }

        nav[role="navigation"] span[aria-current="page"] span,
        nav[role="navigation"] span[aria-current="page"] {
            background: var(--primary) !important;
            color: white !important;
            font-weight: 700 !important;
            border-color: var(--primary) !important;
        }

        /* KPI Card Styles */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .kpi-card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid var(--border-color);
            border-radius: 0.85rem;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            position: relative;
            overflow: hidden;
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary);
        }

        .kpi-title {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-weight: 600;
        }

        .kpi-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .kpi-subtitle {
            font-size: 0.7rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <header>
        <div class="brand">
            ⚡ PCP & Compras <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">| TOTVS Protheus</span>
        </div>
        <nav class="nav-links">
            <a href="{{ route('dashboard.index') }}" class="nav-link {{ request()->routeIs('dashboard.*') ? 'active' : '' }}">
                📊 Dashboard
            </a>
            <a href="{{ route('estoque.index') }}" class="nav-link {{ request()->routeIs('estoque.*') ? 'active' : '' }}">
                📦 Estoque PCP
            </a>
            <a href="{{ route('compras.index') }}" class="nav-link {{ request()->routeIs('compras.*') ? 'active' : '' }}">
                🛒 Compras
            </a>
        </nav>
    </header>

    <div class="container">
        @if(session('success'))
            <div class="alert-success">
                {{ session('success') }}
            </div>
        @endif

        @yield('content')
    </div>
</body>
</html>
