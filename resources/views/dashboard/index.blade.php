@extends('layouts.app')

@section('content')
<!-- Plugin ChartDataLabels CDN -->
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700;">📊 Dashboard de Indicadores & Gráficos</h1>
        <p style="color: var(--text-muted); font-size: 0.8rem;">Visão gerencial em tempo real com montantes financeiros (R$) e demandas de estoque.</p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="{{ route('estoque.index') }}" class="btn btn-secondary">📦 Ir para Estoque</a>
        <a href="{{ route('compras.index') }}" class="btn btn-primary">🛒 Ir para Compras</a>
    </div>
</div>

<!-- Filtros no Dashboard (Pedido de Venda + Status PCP + Status Pagamento) -->
<div class="card" style="border-color: rgba(99, 102, 241, 0.4); margin-bottom: 1.25rem;">
    <form action="{{ route('dashboard') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 0.85rem; align-items: flex-end;">
        <div class="form-group" style="margin-bottom: 0; min-width: 180px; flex: 2;">
            <label class="form-label">Pedido de Venda</label>
            <select name="pedido" class="form-select" onchange="this.form.submit()">
                <option value="">-- Todos os Pedidos --</option>
                @foreach($pedidosDisponiveis as $p)
                    <option value="{{ $p }}" {{ $searchPedido == $p ? 'selected' : '' }}>Pedido: {{ $p }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0; min-width: 180px; flex: 2;">
            <label class="form-label">Status PCP / Almoxarifado</label>
            <select name="status_pcp" class="form-select" onchange="this.form.submit()">
                <option value="">-- Todos Status PCP --</option>
                <option value="FALTA" {{ $searchStatusPcp == 'FALTA' ? 'selected' : '' }}>FALTA</option>
                <option value="SEPARADO" {{ $searchStatusPcp == 'SEPARADO' ? 'selected' : '' }}>SEPARADO</option>
                <option value="RETIRADO" {{ $searchStatusPcp == 'RETIRADO' ? 'selected' : '' }}>RETIRADO</option>
                <option value="FABRICA" {{ $searchStatusPcp == 'FABRICA' ? 'selected' : '' }}>FABRICA</option>
                <option value="FABRICAR INTERNO KANBAN" {{ $searchStatusPcp == 'FABRICAR INTERNO KANBAN' ? 'selected' : '' }}>KANBAN</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0; min-width: 180px; flex: 2;">
            <label class="form-label">Status de Pagamento / Compras</label>
            <select name="status_pagamento" class="form-select" onchange="this.form.submit()">
                <option value="">-- Todos Status Pagamento --</option>
                <option value="PENDENTE" {{ $searchStatusPagamento == 'PENDENTE' ? 'selected' : '' }}>PENDENTE</option>
                <option value="PAGAMENTO ANTECIPADO" {{ $searchStatusPagamento == 'PAGAMENTO ANTECIPADO' ? 'selected' : '' }}>PAGAMENTO ANTECIPADO</option>
                <option value="FATURADO" {{ $searchStatusPagamento == 'FATURADO' ? 'selected' : '' }}>FATURADO</option>
                <option value="PAGO" {{ $searchStatusPagamento == 'PAGO' ? 'selected' : '' }}>PAGO</option>
            </select>
        </div>

        <div style="min-width: 120px; flex: 1; display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center;">
                🔍 Filtrar
            </button>
            @if($searchPedido || $searchStatusPcp || $searchStatusPagamento)
                <a href="{{ route('dashboard') }}" class="btn btn-secondary" style="padding: 0.45rem 0.65rem;">
                    ✕ Limpar
                </a>
            @endif
        </div>
    </form>
</div>

<!-- Grid de Indicadores Quantitativos -->
<div class="kpi-grid" style="margin-bottom: 1rem;">
    <div class="kpi-card" style="border-left-color: #6366f1;">
        <div class="kpi-title">Total de Itens no Estoque</div>
        <div class="kpi-value">{{ $totalEstoque }}</div>
        <div class="kpi-subtitle">Itens sob acompanhamento</div>
    </div>

    <div class="kpi-card" style="border-left-color: #ef4444;">
        <div class="kpi-title" style="color: #fca5a5;">Status FALTA * (Ruptura)</div>
        <div class="kpi-value" style="color: #ef4444;">{{ $totalFalta }}</div>
        <div class="kpi-subtitle">Necessidades pendentes</div>
    </div>

    <div class="kpi-card" style="border-left-color: #3b82f6;">
        <div class="kpi-title" style="color: #93c5fd;">Em Produção / Kanban</div>
        <div class="kpi-value" style="color: #60a5fa;">{{ $totalFabrica }}</div>
        <div class="kpi-subtitle">Fábrica & Kanban interno</div>
    </div>

    <div class="kpi-card" style="border-left-color: #10b981;">
        <div class="kpi-title" style="color: #6ee7b7;">Separados / Retirados</div>
        <div class="kpi-value" style="color: #10b981;">{{ $totalSeparadoRetirado }}</div>
        <div class="kpi-subtitle">Concluídos no almoxarifado</div>
    </div>
</div>

<!-- Grid de Indicadores Financeiros (R$) -->
<div class="kpi-grid" style="margin-bottom: 1.5rem;">
    <div class="kpi-card" style="border-left-color: #38bdf8;">
        <div class="kpi-title" style="color: #7dd3fc;">Valor Total de Compras</div>
        <div class="kpi-value" style="font-size: 1.5rem; color: #38bdf8;">R$ {{ number_format($valorTotalGeral, 2, ',', '.') }}</div>
        <div class="kpi-subtitle">Montante total acumulado</div>
    </div>

    <div class="kpi-card" style="border-left-color: #f59e0b;">
        <div class="kpi-title" style="color: #fcd34d;">Compras Pendentes</div>
        <div class="kpi-value" style="font-size: 1.5rem; color: #f59e0b;">R$ {{ number_format($valorTotalPendente, 2, ',', '.') }}</div>
        <div class="kpi-subtitle">Valor em aberto / cotação</div>
    </div>

    <div class="kpi-card" style="border-left-color: #60a5fa;">
        <div class="kpi-title" style="color: #93c5fd;">Compras Faturadas</div>
        <div class="kpi-value" style="font-size: 1.5rem; color: #60a5fa;">R$ {{ number_format($valorTotalFaturado, 2, ',', '.') }}</div>
        <div class="kpi-subtitle">NF faturada pelo fornecedor</div>
    </div>

    <div class="kpi-card" style="border-left-color: #10b981;">
        <div class="kpi-title" style="color: #6ee7b7;">Compras Pagas</div>
        <div class="kpi-value" style="font-size: 1.5rem; color: #10b981;">R$ {{ number_format($valorTotalPago, 2, ',', '.') }}</div>
        <div class="kpi-subtitle">Pagamentos liquidados</div>
    </div>
</div>

<!-- Grid de Gráficos -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
    <!-- Gráfico 1: Status PCP / Estoque -->
    <div class="card">
        <h3 style="font-size: 1rem; margin-bottom: 1rem; color: #a5b4fc;">📦 Distribuição por Status no PCP/Estoque</h3>
        <div style="position: relative; height: 280px; display: flex; justify-content: center;">
            <canvas id="chartStatusEstoque"></canvas>
        </div>
    </div>

    <!-- Gráfico 2: Montantes Financeiros em Compras (R$) -->
    <div class="card">
        <h3 style="font-size: 1rem; margin-bottom: 1rem; color: #38bdf8;">💳 Montantes Financeiros por Status de Pagamento (R$)</h3>
        <div style="position: relative; height: 280px;">
            <canvas id="chartStatusCompras"></canvas>
        </div>
    </div>
</div>

<!-- Gráfico 3: Demandas por Pedido de Venda em R$ -->
<div class="card">
    <h3 style="font-size: 1rem; margin-bottom: 1rem; color: #c084fc;">📋 Top Pedidos de Venda por Valor Total em Compras (R$)</h3>
    <div style="position: relative; height: 270px;">
        <canvas id="chartTopPedidos"></canvas>
    </div>
</div>

<!-- Script Chart.js -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Registrar o plugin de rótulos de dados (datalabels)
    if (typeof ChartDataLabels !== 'undefined') {
        Chart.register(ChartDataLabels);
    }

    // Chart 1: Donut Chart - Status PCP
    const ctx1 = document.getElementById('chartStatusEstoque').getContext('2d');
    new Chart(ctx1, {
        type: 'doughnut',
        data: {
            labels: ['FALTA *', 'SEPARADO', 'RETIRADO', 'FÁBRICA', 'KANBAN'],
            datasets: [{
                data: [
                    {{ $statusEstoqueCounts['FALTA'] }},
                    {{ $statusEstoqueCounts['SEPARADO'] }},
                    {{ $statusEstoqueCounts['RETIRADO'] }},
                    {{ $statusEstoqueCounts['FABRICA'] }},
                    {{ $statusEstoqueCounts['KANBAN'] }}
                ],
                backgroundColor: [
                    'rgba(239, 68, 68, 0.85)',   // FALTA - Red
                    'rgba(245, 158, 11, 0.85)',  // SEPARADO - Orange
                    'rgba(16, 185, 129, 0.85)',  // RETIRADO - Green
                    'rgba(59, 130, 246, 0.85)',  // FABRICA - Blue
                    'rgba(168, 85, 247, 0.85)'   // KANBAN - Purple
                ],
                borderColor: 'rgba(15, 23, 42, 1)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#f8fafc', font: { family: 'Inter', size: 11 } }
                },
                datalabels: {
                    color: '#ffffff',
                    font: { weight: 'bold', size: 12 },
                    formatter: function(value) {
                        return value > 0 ? value : '';
                    }
                }
            }
        }
    });

    // Chart 2: Bar Chart - Status Compras em R$
    const ctx2 = document.getElementById('chartStatusCompras').getContext('2d');
    new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: ['PENDENTE', 'ANTECIPADO', 'FATURADO', 'PAGO'],
            datasets: [{
                label: 'Valor Total (R$)',
                data: [
                    {{ $statusComprasValores['PENDENTE'] }},
                    {{ $statusComprasValores['ANTECIPADO'] }},
                    {{ $statusComprasValores['FATURADO'] }},
                    {{ $statusComprasValores['PAGO'] }}
                ],
                backgroundColor: [
                    'rgba(245, 158, 11, 0.85)',
                    'rgba(148, 163, 184, 0.85)',
                    'rgba(59, 130, 246, 0.85)',
                    'rgba(16, 185, 129, 0.85)'
                ],
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 25 } },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let value = context.raw || 0;
                            return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'top',
                    color: '#38bdf8',
                    font: { weight: 'bold', size: 11 },
                    formatter: function(value) {
                        return value > 0 ? 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : 'R$ 0,00';
                    }
                }
            },
            scales: {
                x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                y: { 
                    ticks: { 
                        color: '#94a3b8',
                        callback: function(value) { return 'R$ ' + value.toLocaleString('pt-BR'); }
                    }, 
                    grid: { color: 'rgba(255,255,255,0.05)' } 
                }
            }
        }
    });

    // Chart 3: Horizontal Bar - Top Pedidos por R$
    const ctx3 = document.getElementById('chartTopPedidos').getContext('2d');
    const pedidosLabels = [
        @foreach($topPedidosValores as $p)
            'Pedido {{ $p->pedido }}',
        @endforeach
    ];
    const pedidosData = [
        @foreach($topPedidosValores as $p)
            {{ floatval($p->total_valor) }},
        @endforeach
    ];

    new Chart(ctx3, {
        type: 'bar',
        data: {
            labels: pedidosLabels.length ? pedidosLabels : ['Nenhum Pedido Salvo'],
            datasets: [{
                label: 'Valor Total (R$)',
                data: pedidosData.length ? pedidosData : [0],
                backgroundColor: 'rgba(99, 102, 241, 0.85)',
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { right: 80 } },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let value = context.raw || 0;
                            return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'right',
                    color: '#c084fc',
                    font: { weight: 'bold', size: 11 },
                    formatter: function(value) {
                        return value > 0 ? 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : 'R$ 0,00';
                    }
                }
            },
            scales: {
                x: { 
                    ticks: { 
                        color: '#94a3b8',
                        callback: function(value) { return 'R$ ' + value.toLocaleString('pt-BR'); }
                    }, 
                    grid: { color: 'rgba(255,255,255,0.05)' } 
                },
                y: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' } }
            }
        }
    });
});
</script>
@endsection
