@extends('layouts.app')

@section('content')
<div class="card" style="margin-bottom: 1.5rem; background: linear-gradient(135deg, rgba(30,41,59,0.9), rgba(15,23,42,0.95)); border: 1px solid rgba(99,102,241,0.2);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
        <div>
            <h1 style="font-size: 1.6rem; font-weight: 700; margin: 0; color: #f8fafc; display: flex; align-items: center; gap: 0.5rem;">
                📊 Dashboard de Indicadores & Gráficos
            </h1>
            <p style="color: #94a3b8; margin: 0.25rem 0 0 0; font-size: 0.85rem;">
                Visão gerencial em tempo real com montantes financeiros (R$) e demandas de estoque.
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('estoque.index') }}" class="btn btn-secondary" style="font-size: 0.8rem;">
                📦 Ir para Estoque
            </a>
            <a href="{{ route('compras.index') }}" class="btn btn-primary" style="font-size: 0.8rem;">
                🛒 Ir para Compras
            </a>
        </div>
    </div>

    <!-- Barra de Filtros do Dashboard -->
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
            <label class="form-label">Nome do Cliente (C2_OBS) ✏️</label>
            <input type="text" 
                   name="search_cliente" 
                   value="{{ $searchCliente }}" 
                   class="form-control" 
                   placeholder="Multi: CONDOMINIO, ISA, SP...">
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
                <option value="PA" {{ in_array($searchStatusPagamento, ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO']) ? 'selected' : '' }}>PA (Pagamento Antecipado)</option>
                <option value="FATURADO" {{ $searchStatusPagamento == 'FATURADO' ? 'selected' : '' }}>FATURADO</option>
                <option value="PAGO" {{ $searchStatusPagamento == 'PAGO' ? 'selected' : '' }}>PAGO</option>
            </select>
        </div>

        <div style="min-width: 120px; flex: 1; display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center;">
                🔍 Filtrar
            </button>
            @if($searchPedido || $searchStatusPcp || $searchStatusPagamento || $searchCliente)
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

<!-- PRIMEIRO LUGAR NOS GRÁFICOS: Fornecedores por Montante (R$) com Scroll -->
<div style="display: grid; grid-template-columns: 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
    <div class="card" style="border: 1px solid rgba(56, 189, 248, 0.3);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: #38bdf8; display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                🏭 Todos os Fornecedores Ordenados por Montante (R$) e Quantidade a Comprar
            </h3>
            <span style="font-size: 0.75rem; color: #94a3b8; background: #0f172a; padding: 0.25rem 0.6rem; border-radius: 0.375rem; border: 1px solid #334155;">
                📊 Total: <strong>{{ count($topFornecedoresValores) }}</strong> Fornecedores (Role para ver todos ↕️)
            </span>
        </div>
        <div style="max-height: 480px; overflow-y: auto; overflow-x: hidden; padding-right: 0.5rem; border: 1px solid rgba(255,255,255,0.05); border-radius: 0.5rem;">
            <div style="position: relative; height: {{ max(380, count($topFornecedoresValores) * 38) }}px; width: 100%;">
                <canvas id="chartTopFornecedores"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Grid de Gráficos parte 2 (Status PCP & Status Pagamento) -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
    <!-- Gráfico 2: Status PCP / Estoque -->
    <div class="card">
        <h3 style="font-size: 1rem; margin-bottom: 1rem; color: #a5b4fc;">📦 Distribuição por Status no PCP/Estoque</h3>
        <div style="position: relative; height: 280px; display: flex; justify-content: center;">
            <canvas id="chartStatusEstoque"></canvas>
        </div>
    </div>

    <!-- Gráfico 3: Montantes Financeiros em Compras (R$) -->
    <div class="card">
        <h3 style="font-size: 1rem; margin-bottom: 1rem; color: #38bdf8;">💳 Montantes Financeiros por Status de Pagamento (R$)</h3>
        <div style="position: relative; height: 280px;">
            <canvas id="chartStatusCompras"></canvas>
        </div>
    </div>
</div>

<!-- Grid de Gráficos parte 3 (Top Pedidos & OPs Fechadas por Mês) -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
    <!-- Gráfico 4: Demandas por Pedido de Venda em R$ -->
    <div class="card">
        <h3 style="font-size: 1rem; margin-bottom: 1rem; color: #c084fc;">📋 Top Pedidos de Venda por Valor Total em Compras (R$)</h3>
        <div style="position: relative; height: 270px;">
            <canvas id="chartTopPedidos"></canvas>
        </div>
    </div>

    <!-- Gráfico 5: OPs Fechadas por Mês -->
    <div class="card">
        <h3 style="font-size: 1rem; margin-bottom: 1rem; color: #34d399;">🔒 Ordens de Produção (OPs) Fechadas por Mês</h3>
        <div style="position: relative; height: 270px;">
            <canvas id="chartOpsFechadas"></canvas>
        </div>
    </div>
</div>

<!-- Script Chart.js -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Registrar o plugin de rótulos de dados (ChartDataLabels)
    if (typeof ChartDataLabels !== 'undefined') {
        Chart.register(ChartDataLabels);
    }

    const pluginDataLabels = typeof ChartDataLabels !== 'undefined' ? [ChartDataLabels] : [];

    // 1. Gráfico Top Fornecedores (Barras Horizontais)
    const ctxFornecedores = document.getElementById('chartTopFornecedores').getContext('2d');
    const rawFornecedoresLabels = {!! json_encode($topFornecedoresValores->pluck('codigo_fornecedor')) !!};
    const fornecedoresLabels = rawFornecedoresLabels.map(f => {
        let str = String(f || '').trim();
        return (str === '' || str === '0') ? 'SEM FORNECEDOR' : str;
    });
    const fornecedoresQtdValues = {!! json_encode($topFornecedoresValores->pluck('total_qtd_comprar')->map(fn($v) => (float)$v)) !!};
    const fornecedoresRsValues = {!! json_encode($topFornecedoresValores->pluck('total_valor')->map(fn($v) => (float)$v)) !!};

    new Chart(ctxFornecedores, {
        plugins: pluginDataLabels,
        type: 'bar',
        data: {
            labels: fornecedoresLabels.length ? fornecedoresLabels : ['Sem Dados'],
            datasets: [{
                label: 'Montante Total (R$)',
                data: fornecedoresRsValues.length ? fornecedoresRsValues : [0],
                backgroundColor: 'rgba(56, 189, 248, 0.85)',
                borderColor: '#38bdf8',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { right: 320 } },
            onClick: function(event, elements) {
                if (elements && elements.length > 0) {
                    const index = elements[0].index;
                    const codForn = rawFornecedoresLabels[index];
                    abrirModalFornecedor(codForn);
                }
            },
            onHover: (event, chartElement) => {
                event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default';
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let v = context.raw || 0;
                            let q = fornecedoresQtdValues[context.dataIndex] || 0;
                            return 'Montante: R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' | Qtd: ' + q.toLocaleString('pt-BR') + ' un';
                        }
                    }
                },
                datalabels: {
                    display: true,
                    anchor: 'end',
                    align: 'right',
                    color: '#38bdf8',
                    font: { weight: 'bold', size: 11 },
                    formatter: function(value, context) {
                        let q = fornecedoresQtdValues[context.dataIndex] || 0;
                        return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' (' + q.toLocaleString('pt-BR') + ' un)';
                    }
                }
            },
            scales: {
                x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' }, beginAtZero: true, grace: '25%' },
                y: { ticks: { color: '#f8fafc', font: { weight: 'bold', size: 11 } }, grid: { color: 'rgba(255,255,255,0.05)' } }
            }
        }
    });

    // 2. Gráfico Status PCP / Estoque (Doughnut)
    const ctx1 = document.getElementById('chartStatusEstoque').getContext('2d');
    new Chart(ctx1, {
        plugins: pluginDataLabels,
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
                    '#ef4444',
                    '#f59e0b',
                    '#10b981',
                    '#3b82f6',
                    '#a855f7'
                ],
                borderWidth: 2,
                borderColor: '#1e293b'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#cbd5e1', font: { size: 11, weight: 'bold' }, padding: 12 }
                },
                datalabels: {
                    display: true,
                    color: '#ffffff',
                    font: { weight: 'bold', size: 12 },
                    formatter: function(value) {
                        return value > 0 ? value : '';
                    }
                }
            }
        }
    });

    // 3. Gráfico Status Compras em R$ (Bar Chart Vertical - Velas)
    const ctx2 = document.getElementById('chartStatusCompras').getContext('2d');
    new Chart(ctx2, {
        plugins: pluginDataLabels,
        type: 'bar',
        data: {
            labels: ['PENDENTE', 'PA (ANTECIPADO)', 'FATURADO', 'PAGO'],
            datasets: [{
                label: 'Valor Total (R$)',
                data: [
                    {{ $statusComprasValores['PENDENTE'] }},
                    {{ $statusComprasValores['PA'] }},
                    {{ $statusComprasValores['FATURADO'] }},
                    {{ $statusComprasValores['PAGO'] }}
                ],
                backgroundColor: [
                    'rgba(245, 158, 11, 0.85)',
                    'rgba(168, 85, 247, 0.85)',
                    'rgba(96, 165, 250, 0.85)',
                    'rgba(16, 185, 129, 0.85)'
                ],
                borderColor: [
                    '#f59e0b',
                    '#a855f7',
                    '#60a5fa',
                    '#10b981'
                ],
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 40 } },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let value = context.raw || 0;
                            return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                    }
                },
                datalabels: {
                    display: true,
                    anchor: 'end',
                    align: 'top',
                    offset: 4,
                    color: '#f8fafc',
                    font: { weight: 'bold', size: 12 },
                    formatter: function(value) {
                        return value > 0 ? 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
                    }
                }
            },
            scales: {
                x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                y: {
                    grace: '20%',
                    beginAtZero: true,
                    ticks: {
                        color: '#94a3b8',
                        callback: function(value) {
                            return 'R$ ' + (value / 1000000).toFixed(1) + 'M';
                        }
                    },
                    grid: { color: 'rgba(255,255,255,0.05)' }
                }
            }
        }
    });

    // 4. Gráfico Top Pedidos de Venda em R$ (Bar Chart Vertical - Velas)
    const ctx3 = document.getElementById('chartTopPedidos').getContext('2d');
    const pedidosLabels = {!! json_encode($topPedidosValores->pluck('pedido')) !!};
    const pedidosData = {!! json_encode($topPedidosValores->pluck('total_valor')->map(fn($v) => (float)$v)) !!};

    new Chart(ctx3, {
        plugins: pluginDataLabels,
        type: 'bar',
        data: {
            labels: pedidosLabels.length ? pedidosLabels : ['Sem Dados'],
            datasets: [{
                label: 'Valor Total (R$)',
                data: pedidosData.length ? pedidosData : [0],
                backgroundColor: 'rgba(192, 132, 252, 0.85)',
                borderColor: '#c084fc',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 40 } },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let value = context.raw || 0;
                            return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                    }
                },
                datalabels: {
                    display: true,
                    anchor: 'end',
                    align: 'top',
                    offset: 4,
                    color: '#e9d5ff',
                    font: { weight: 'bold', size: 11 },
                    formatter: function(value) {
                        return value > 0 ? 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
                    }
                }
            },
            scales: {
                x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                y: {
                    grace: '20%',
                    beginAtZero: true,
                    ticks: {
                        color: '#94a3b8',
                        callback: function(value) {
                            return 'R$ ' + (value / 1000).toFixed(0) + 'k';
                        }
                    },
                    grid: { color: 'rgba(255,255,255,0.05)' }
                }
            }
        }
    });

    // 5. Gráfico OPs Fechadas por Mês (Bar Chart Vertical - Velas)
    const ctx4 = document.getElementById('chartOpsFechadas').getContext('2d');
    const opsFechadasLabels = {!! json_encode($opsFechadasPorMesLabels) !!};
    const opsFechadasValues = {!! json_encode($opsFechadasPorMesValues) !!};
    const opsFechadasValoresRs = {!! json_encode($opsFechadasPorMesValoresRs) !!};

    new Chart(ctx4, {
        plugins: pluginDataLabels,
        type: 'bar',
        data: {
            labels: opsFechadasLabels.length ? opsFechadasLabels : ['Sem Dados'],
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
            layout: { padding: { top: 40 } },
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
                    display: true,
                    anchor: 'end',
                    align: 'top',
                    offset: 4,
                    color: '#6ee7b7',
                    font: { weight: 'bold', size: 12 },
                    formatter: function(value, context) {
                        if (value <= 0) return '';
                        let v = opsFechadasValoresRs[context.dataIndex] || 0;
                        return value + ' OP(s) | R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }
                }
            },
            scales: {
                x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                y: {
                    grace: '20%',
                    beginAtZero: true,
                    ticks: { color: '#94a3b8', stepSize: 1 },
                    grid: { color: 'rgba(255,255,255,0.05)' }
                }
            }
        }
    });
});
</script>

<!-- Modal Flutuante de Itens do Fornecedor -->
<div id="modalFornecedorItens" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.75); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(4px);" onclick="if(event.target===this) fecharModalFornecedor()">
    <div style="background: #0f172a; border: 1px solid #334155; border-radius: 0.75rem; width: 92%; max-width: 1200px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.8); overflow: hidden;">
        <!-- Header do Modal -->
        <div style="background: #1e293b; padding: 1rem 1.25rem; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <h3 style="margin: 0; font-size: 1.15rem; color: #38bdf8; display: flex; align-items: center; gap: 0.5rem;">
                    📦 Matérias-Primas e Itens do Fornecedor: <span id="modalFornNome" style="color: #fcd34d; font-weight: 800;">-</span>
                </h3>
            </div>
            <button type="button" onclick="fecharModalFornecedor()" style="background: transparent; border: none; color: #94a3b8; font-size: 1.5rem; cursor: pointer; line-height: 1; padding: 0 0.5rem;">✕</button>
        </div>

        <!-- Banner de Metricas do Fornecedor -->
        <div style="display: flex; gap: 1rem; padding: 1rem 1.25rem; background: rgba(15, 23, 42, 0.6); border-bottom: 1px solid #334155; flex-wrap: wrap;">
            <div style="border-left: 3px solid #38bdf8; background: rgba(56, 189, 248, 0.08); padding: 0.5rem 0.85rem; border-radius: 0.375rem; flex: 1; min-width: 200px;">
                <div style="font-size: 0.7rem; font-weight: 700; color: #38bdf8; text-transform: uppercase;">💰 Montante Total (R$)</div>
                <div style="font-size: 1.25rem; font-weight: 800; color: #38bdf8;" id="modalFornTotalRs">R$ 0,00</div>
            </div>
            <div style="border-left: 3px solid #fcd34d; background: rgba(252, 211, 77, 0.08); padding: 0.5rem 0.85rem; border-radius: 0.375rem; flex: 1; min-width: 200px;">
                <div style="font-size: 0.7rem; font-weight: 700; color: #fcd34d; text-transform: uppercase;">📦 Total Qtd a Comprar</div>
                <div style="font-size: 1.25rem; font-weight: 800; color: #fcd34d;" id="modalFornTotalQtd">0 un</div>
            </div>
            <div style="border-left: 3px solid #a855f7; background: rgba(168, 85, 247, 0.08); padding: 0.5rem 0.85rem; border-radius: 0.375rem; flex: 1; min-width: 160px;">
                <div style="font-size: 0.7rem; font-weight: 700; color: #c084fc; text-transform: uppercase;">🏷️ Total de Linhas</div>
                <div style="font-size: 1.25rem; font-weight: 800; color: #c084fc;" id="modalFornTotalLinhas">0 itens</div>
            </div>
        </div>

        <!-- Conteudo Tabela -->
        <div style="padding: 1rem 1.25rem; overflow-y: auto; flex: 1;" id="modalFornContent">
            <div style="text-align: center; color: #94a3b8; padding: 2rem;" id="modalFornLoading">
                ⏳ Carregando matérias-primas e componentes do fornecedor...
            </div>
            <div class="table-responsive" id="modalFornTableWrapper" style="display: none;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.8rem;">
                    <thead>
                        <tr style="background: #1e293b; color: #94a3b8; text-align: left; font-weight: 700;">
                            <th style="padding: 0.6rem; border-bottom: 1px solid #334155;">PV / Pedido</th>
                            <th style="padding: 0.6rem; border-bottom: 1px solid #334155;">OP</th>
                            <th style="padding: 0.6rem; border-bottom: 1px solid #334155;">Cliente (C2_OBS)</th>
                            <th style="padding: 0.6rem; border-bottom: 1px solid #334155;">Código Produto</th>
                            <th style="padding: 0.6rem; border-bottom: 1px solid #334155;">Descrição</th>
                            <th style="padding: 0.6rem; border-bottom: 1px solid #334155; text-align: center; color: #38bdf8;">Qtd OP</th>
                            <th style="padding: 0.6rem; border-bottom: 1px solid #334155; text-align: center; color: #fcd34d;">Qtd Estq</th>
                            <th style="padding: 0.6rem; border-bottom: 1px solid #334155; text-align: center; color: #ef4444;">Qtd a Comprar</th>
                            <th style="padding: 0.6rem; border-bottom: 1px solid #334155; text-align: right; color: #a5b4fc;">Val. Unit (R$)</th>
                            <th style="padding: 0.6rem; border-bottom: 1px solid #334155; text-align: right; color: #6ee7b7;">Val. Total (R$)</th>
                            <th style="padding: 0.6rem; border-bottom: 1px solid #334155; text-align: center;">Status PCP</th>
                            <th style="padding: 0.6rem; border-bottom: 1px solid #334155; text-align: center;">Status Pag.</th>
                        </tr>
                    </thead>
                    <tbody id="modalFornTbody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function abrirModalFornecedor(codForn) {
    const modal = document.getElementById('modalFornecedorItens');
    const titleForn = document.getElementById('modalFornNome');
    const loading = document.getElementById('modalFornLoading');
    const wrapper = document.getElementById('modalFornTableWrapper');
    const tbody = document.getElementById('modalFornTbody');

    let displayForn = String(codForn || '').trim();
    if (!displayForn || displayForn === '0') displayForn = 'SEM FORNECEDOR';
    titleForn.innerText = displayForn;

    loading.style.display = 'block';
    wrapper.style.display = 'none';
    modal.style.display = 'flex';

    fetch(`/dashboard/fornecedor-itens?fornecedor=${encodeURIComponent(codForn || '')}`)
        .then(res => res.json())
        .then(data => {
            loading.style.display = 'none';
            if (data.success && data.items) {
                document.getElementById('modalFornTotalRs').innerText = 'R$ ' + Number(data.total_valor || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                document.getElementById('modalFornTotalQtd').innerText = Number(data.total_qtd_comprar || 0).toLocaleString('pt-BR') + ' un';
                document.getElementById('modalFornTotalLinhas').innerText = (data.count || 0) + ' itens';

                tbody.innerHTML = '';
                if (data.items.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="12" style="text-align: center; color: #94a3b8; padding: 1.5rem;">Nenhum item pendente encontrado para este fornecedor.</td></tr>';
                } else {
                    data.items.forEach(it => {
                        const tr = document.createElement('tr');
                        tr.style.borderBottom = '1px solid rgba(255,255,255,0.05)';
                        tr.innerHTML = `
                            <td style="padding: 0.55rem; color: #f8fafc; font-weight: 700;">${it.pedido_venda || '-'}</td>
                            <td style="padding: 0.55rem; color: #38bdf8;"><code>${it.op || '-'}</code></td>
                            <td style="padding: 0.55rem; color: #cbd5e1; font-size: 0.75rem;">${it.cliente_obs || '-'}</td>
                            <td style="padding: 0.55rem; color: #cbd5e1; font-weight: 600;">${it.codigo_produto || '-'}</td>
                            <td style="padding: 0.55rem; color: #e2e8f0; font-size: 0.75rem;">${it.descricao || '-'}</td>
                            <td style="padding: 0.55rem; text-align: center; color: #38bdf8; font-weight: 700;">${Number(it.qtd_op || 0).toLocaleString('pt-BR')}</td>
                            <td style="padding: 0.55rem; text-align: center; color: #fcd34d;">${Number(it.qtd_estoque || 0).toLocaleString('pt-BR')}</td>
                            <td style="padding: 0.55rem; text-align: center; color: #ef4444; font-weight: 700;">${Number(it.qtd_comprar || 0).toLocaleString('pt-BR')}</td>
                            <td style="padding: 0.55rem; text-align: right; color: #a5b4fc;">R$ ${Number(it.valor_unitario || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            <td style="padding: 0.55rem; text-align: right; color: #6ee7b7; font-weight: 700;">R$ ${Number(it.valor_total || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            <td style="padding: 0.55rem; text-align: center;"><span class="badge" style="background:#334155; color:#f8fafc; padding:0.15rem 0.4rem; font-size:0.68rem;">${it.status_pcp || '-'}</span></td>
                            <td style="padding: 0.55rem; text-align: center;"><span class="badge" style="background:#1e293b; color:#38bdf8; border:1px solid #334155; padding:0.15rem 0.4rem; font-size:0.68rem;">${it.status_pagamento || '-'}</span></td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
                wrapper.style.display = 'block';
            }
        })
        .catch(err => {
            loading.innerText = '❌ Erro ao carregar dados do fornecedor.';
        });
}

function fecharModalFornecedor() {
    document.getElementById('modalFornecedorItens').style.display = 'none';
}
</script>
@endsection
