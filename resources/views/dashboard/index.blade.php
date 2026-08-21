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

<!-- Filtros no Dashboard (Digitação de Pedido de Venda + Listas Suspensas de Status + Descrição Multi-Termos) -->
<div class="card" style="border-color: rgba(99, 102, 241, 0.4); margin-bottom: 1.25rem;">
    <form action="{{ route('dashboard') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 0.85rem; align-items: flex-end;">
        <div class="form-group" style="margin-bottom: 0; min-width: 180px; flex: 2;">
            <label class="form-label">Número do Pedido de Venda ✏️</label>
            <input type="text" 
                   name="pedido" 
                   value="{{ $searchPedido }}" 
                   class="form-control" 
                   placeholder="Digite o N° do Pedido (Ex: 006614)..."
                   list="listPedidosDisponiveis">
            <datalist id="listPedidosDisponiveis">
                @foreach($pedidosDisponiveis as $p)
                    <option value="{{ $p }}">
                @endforeach
            </datalist>
        </div>

        <div class="form-group" style="margin-bottom: 0; min-width: 220px; flex: 2.5;">
            <label class="form-label">Descrição do Produto (Multi: CABO, CHAVE...) ✏️</label>
            <input type="text" 
                   name="descricao" 
                   value="{{ $searchDescricao }}" 
                   class="form-control" 
                   placeholder="Multi: CABO, CHAVE, DISJUNTOR...">
        </div>

        <div class="form-group" style="margin-bottom: 0; min-width: 170px; flex: 1.8;">
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

        <div class="form-group" style="margin-bottom: 0; min-width: 170px; flex: 1.8;">
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
            @if($searchPedido || $searchStatusPcp || $searchStatusPagamento || $searchDescricao)
                <a href="{{ route('dashboard') }}" class="btn btn-secondary" style="padding: 0.45rem 0.65rem;">
                    ✕ Limpar
                </a>
            @endif
        </div>
    </form>
</div>

<!-- Grid de Indicadores Quantitativos -->
<div class="kpi-grid" style="margin-bottom: 1rem; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
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
        <div class="kpi-title" style="color: #6ee7b7;">Itens SEPARADOS</div>
        <div class="kpi-value" style="color: #10b981;">{{ $totalSeparado }}</div>
        <div class="kpi-subtitle">Almoxarifado (Status SEPARADO)</div>
    </div>

    <div class="kpi-card" style="border-left-color: #c084fc;">
        <div class="kpi-title" style="color: #d8b4fe;">OPs Fechadas no Mês</div>
        <div class="kpi-value" style="color: #c084fc;">{{ $opsFechadasMes }}</div>
        <div class="kpi-subtitle">Encerradas neste mês</div>
    </div>
</div>

<!-- Grid de Indicadores Financeiros (R$) -->
<div class="kpi-grid" style="margin-bottom: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
    <div class="kpi-card" style="border-left-color: #38bdf8;">
        <div class="kpi-title" style="color: #7dd3fc;">Valor Total de Compras</div>
        <div class="kpi-value" style="font-size: 1.85rem; color: #38bdf8;">R$ {{ number_format($valorTotalGeral, 2, ',', '.') }}</div>
        <div class="kpi-subtitle">Montante total acumulado</div>
    </div>

    <div class="kpi-card" style="border-left-color: #a855f7;">
        <div class="kpi-title" style="color: #c084fc;">Valor Total SEPARADO</div>
        <div class="kpi-value" style="font-size: 1.85rem; color: #c084fc;">R$ {{ number_format($valorTotalSeparado, 2, ',', '.') }}</div>
        <div class="kpi-subtitle">Montante dos itens separados</div>
    </div>

    <div class="kpi-card" style="border-left-color: #f59e0b;">
        <div class="kpi-title" style="color: #fcd34d;">Compras Pendentes</div>
        <div class="kpi-value" style="font-size: 1.85rem; color: #f59e0b;">R$ {{ number_format($valorTotalPendente, 2, ',', '.') }}</div>
        <div class="kpi-subtitle">Valor em aberto / cotação</div>
    </div>

    <div class="kpi-card" style="border-left-color: #60a5fa;">
        <div class="kpi-title" style="color: #93c5fd;">Compras Faturadas</div>
        <div class="kpi-value" style="font-size: 1.85rem; color: #60a5fa;">R$ {{ number_format($valorTotalFaturado, 2, ',', '.') }}</div>
        <div class="kpi-subtitle">NF faturada pelo fornecedor</div>
    </div>

    <div class="kpi-card" style="border-left-color: #10b981;">
        <div class="kpi-title" style="color: #6ee7b7;">Compras Pagas</div>
        <div class="kpi-value" style="font-size: 1.85rem; color: #10b981;">R$ {{ number_format($valorTotalPago, 2, ',', '.') }}</div>
        <div class="kpi-subtitle">Pagamentos liquidados</div>
    </div>
</div>

<!-- Grid de Gráficos parte 1 -->
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

<!-- Grid de Gráficos parte 2 (Top Pedidos & OPs Fechadas por Mês) -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
    <!-- Gráfico 3: Demandas por Pedido de Venda em R$ -->
    <div class="card">
        <h3 style="font-size: 1rem; margin-bottom: 1rem; color: #c084fc;">📋 Top Pedidos de Venda por Valor Total em Compras (R$)</h3>
        <div style="position: relative; height: 270px;">
            <canvas id="chartTopPedidos"></canvas>
        </div>
    </div>

    <!-- Gráfico 4: OPs Fechadas por Mês -->
    <div class="card">
        <h3 style="font-size: 1rem; margin-bottom: 1rem; color: #34d399;">🔒 Ordens de Produção (OPs) Fechadas por Mês</h3>
        <div style="position: relative; height: 270px;">
            <canvas id="chartOpsFechadas"></canvas>
        </div>
    </div>
</div>

<!-- Grid de Gráficos parte 3 (Top Fornecedores por Necessidade de Compra) -->
<div style="display: grid; grid-template-columns: 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
    <!-- Gráfico 5: Top Fornecedores por Quantidade a Comprar -->
    <div class="card">
        <h3 style="font-size: 1rem; margin-bottom: 1rem; color: #f59e0b;">🏭 Top Fornecedores por Quantidade a Comprar (unidades) e Montante (R$)</h3>
        <div style="position: relative; height: 300px;">
            <canvas id="chartTopFornecedores"></canvas>
        </div>
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

    // Chart 4: Bar - OPs Fechadas por Mês (Qtd + Valor R$)
    const ctx4 = document.getElementById('chartOpsFechadas').getContext('2d');
    const opsFechadasLabels = {!! json_encode($opsFechadasPorMesLabels) !!};
    const opsFechadasValues = {!! json_encode($opsFechadasPorMesValues) !!};
    const opsFechadasValoresRs = {!! json_encode($opsFechadasPorMesValoresRs) !!};

    new Chart(ctx4, {
        type: 'bar',
        data: {
            labels: opsFechadasLabels.length ? opsFechadasLabels : ['Sem OPs Fechadas'],
            datasets: [{
                label: 'OPs Fechadas',
                data: opsFechadasValues.length ? opsFechadasValues : [0],
                backgroundColor: 'rgba(52, 211, 153, 0.85)',
                borderColor: '#10b981',
                borderWidth: 1,
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
                            let q = context.raw || 0;
                            let v = opsFechadasValoresRs[context.dataIndex] || 0;
                            return q + ' OP(s) - Montante: R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'top',
                    color: '#34d399',
                    font: { weight: 'bold', size: 11 },
                    formatter: function(value, context) {
                        if (value <= 0) return '0';
                        let v = opsFechadasValoresRs[context.dataIndex] || 0;
                        return value + ' OP(s) | R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }
                }
            },
            scales: {
                x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                y: { ticks: { color: '#94a3b8', stepSize: 1 }, grid: { color: 'rgba(255,255,255,0.05)' }, beginAtZero: true }
            }
        }
    });

    // Chart 5: Horizontal Bar - Top Fornecedores por Quantidade a Comprar
    const ctx5 = document.getElementById('chartTopFornecedores').getContext('2d');
    const fornecedoresLabels = {!! json_encode($topFornecedoresValores->pluck('codigo_fornecedor')) !!};
    const fornecedoresQtdValues = {!! json_encode($topFornecedoresValores->pluck('total_qtd_comprar')->map(fn($v) => (float)$v)) !!};
    const fornecedoresRsValues = {!! json_encode($topFornecedoresValores->pluck('total_valor')->map(fn($v) => (float)$v)) !!};

    new Chart(ctx5, {
        type: 'bar',
        data: {
            labels: fornecedoresLabels.length ? fornecedoresLabels : ['Sem Dados'],
            datasets: [{
                label: 'Qtd. a Comprar (un)',
                data: fornecedoresQtdValues.length ? fornecedoresQtdValues : [0],
                backgroundColor: 'rgba(245, 158, 11, 0.85)',
                borderColor: '#f59e0b',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { right: 170 } },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let q = context.raw || 0;
                            let v = fornecedoresRsValues[context.dataIndex] || 0;
                            return q.toLocaleString('pt-BR') + ' un | Montante: R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'right',
                    color: '#fcd34d',
                    font: { weight: 'bold', size: 11 },
                    formatter: function(value, context) {
                        if (value <= 0) return '0 un';
                        let v = fornecedoresRsValues[context.dataIndex] || 0;
                        return value.toLocaleString('pt-BR') + ' un | R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }
                }
            },
            scales: {
                x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' }, beginAtZero: true },
                y: { ticks: { color: '#f8fafc', font: { weight: 'bold' } }, grid: { color: 'rgba(255,255,255,0.05)' } }
            }
        }
    });
});
</script>
@endsection
