@extends('layouts.app')

@section('content')
<style>
    .kpi-card {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 0.75rem;
        padding: 1.1rem;
        flex: 1;
        min-width: 220px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.4);
    }
    .kpi-title {
        font-size: 0.75rem;
        color: var(--text-muted);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.35rem;
    }
    .kpi-value {
        font-size: 1.8rem;
        font-weight: 800;
        line-height: 1.1;
    }
    .kpi-sub {
        font-size: 0.725rem;
        color: var(--text-muted);
        margin-top: 0.35rem;
    }
    .badge-falta { background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.4); font-size: 0.7rem; padding: 0.2rem 0.5rem; border-radius: 0.25rem; font-weight: 600; }
    .badge-separado { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.4); font-size: 0.7rem; padding: 0.2rem 0.5rem; border-radius: 0.25rem; font-weight: 600; }
    .badge-fabrica { background: rgba(56, 189, 248, 0.2); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.4); font-size: 0.7rem; padding: 0.2rem 0.5rem; border-radius: 0.25rem; font-weight: 600; }
    .badge-kanban { background: rgba(192, 132, 252, 0.2); color: #c084fc; border: 1px solid rgba(192, 132, 252, 0.4); font-size: 0.7rem; padding: 0.2rem 0.5rem; border-radius: 0.25rem; font-weight: 600; }
    .badge-ok { background: rgba(16, 185, 129, 0.25); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.5); font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 0.2rem; font-size: 0.7rem; }
    .badge-pen { background: rgba(245, 158, 11, 0.2); color: #fcd34d; border: 1px solid rgba(245, 158, 11, 0.4); font-weight: 600; padding: 0.15rem 0.45rem; border-radius: 0.2rem; font-size: 0.7rem; }
    .badge-alert { background: rgba(239, 68, 68, 0.25); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.5); font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 0.2rem; font-size: 0.7rem; }
    
    .progress-bar-bg {
        background-color: #334155;
        border-radius: 0.5rem;
        height: 10px;
        width: 100%;
        overflow: hidden;
        margin-top: 0.3rem;
    }
    .progress-bar-fill {
        background: linear-gradient(90deg, #6366f1, #10b981);
        height: 100%;
        border-radius: 0.5rem;
        transition: width 0.4s ease;
    }
    .pv-row:hover {
        background-color: rgba(99, 102, 241, 0.08) !important;
    }
    .expand-content {
        background-color: #0f172a;
        padding: 1rem;
        border-bottom: 2px solid #334155;
    }
</style>

<!-- Título da Página -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
    <div>
        <h2 style="font-size: 1.5rem; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
            🏭 Painel PCP GMGs (Visão Gerencial por PV)
        </h2>
        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.15rem;">
            Consolidação em tempo real do atendimento de estoque, compras e componentes críticos por Pedido de Venda.
        </p>
    </div>
    <div>
        <a href="{{ route('estoque.index') }}" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; margin-right: 0.4rem;">
            📦 Ir para Estoque PCP
        </a>
        <a href="{{ route('compras.index') }}" class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background-color: #6366f1;">
            🛒 Ir para Compras
        </a>
    </div>
</div>

<!-- Cards Executivos de KPI -->
<div style="display: flex; gap: 1rem; margin-bottom: 1.25rem; flex-wrap: wrap;">
    <div class="kpi-card" style="border-left: 4px solid #6366f1; background: rgba(99, 102, 241, 0.05);">
        <div class="kpi-title">📋 Pedidos Acompanhados</div>
        <div class="kpi-value" style="color: #818cf8;">{{ number_format($kpiTotalPv ?? 0, 0, ',', '.') }}</div>
        <div class="kpi-sub">Pedidos de Venda ativos na base</div>
    </div>
    <div class="kpi-card" style="border-left: 4px solid #10b981; background: rgba(16, 185, 129, 0.05);">
        <div class="kpi-title">📈 Avanço Médio de Separação</div>
        <div class="kpi-value" style="color: #34d399;">{{ number_format($kpiMediaSeparacao ?? 0, 1, ',', '.') }}%</div>
        <div class="kpi-sub">Porcentagem média de atendimento</div>
    </div>
    <div class="kpi-card" style="border-left: 4px solid #f59e0b; background: rgba(245, 158, 11, 0.05);">
        <div class="kpi-title">💰 Investimento Pendente (Faltas)</div>
        <div class="kpi-value" style="color: #fbbf24;">R$ {{ number_format($kpiInvestimentoTotal ?? 0, 2, ',', '.') }}</div>
        <div class="kpi-sub">Montante total necessário a comprar</div>
    </div>
    <div class="kpi-card" style="border-left: 4px solid #ef4444; background: rgba(239, 68, 68, 0.05);">
        <div class="kpi-title">⚠️ Pedidos com Componentes Faltantes</div>
        <div class="kpi-value" style="color: #fca5a5;">{{ number_format($kpiPvsComFalta ?? 0, 0, ',', '.') }}</div>
        <div class="kpi-sub">Requerem ação imediata de compras</div>
    </div>
</div>

<!-- Filtros de Busca -->
<div class="card" style="margin-bottom: 1.25rem; padding: 1rem;">
    <form action="{{ route('pcp-painel.index') }}" method="GET" style="display: flex; gap: 0.85rem; align-items: flex-end; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 160px;">
            <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.3rem; display: block;">N° Pedido de Venda (PV)</label>
            <input type="text" name="search_pv" value="{{ $searchPv }}" class="form-control" placeholder="Ex: 006353..." style="padding: 0.4rem 0.6rem; font-size: 0.8rem;">
        </div>
        <div style="flex: 1.5; min-width: 220px;">
            <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.3rem; display: block;">Nome do Cliente / Obra (C2_OBS)</label>
            <input type="text" name="search_cliente" value="{{ $searchCliente }}" class="form-control" placeholder="Ex: ISA ENERGIA..." style="padding: 0.4rem 0.6rem; font-size: 0.8rem;">
        </div>
        <div style="flex: 1; min-width: 160px;">
            <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.3rem; display: block;">Status PCP Geral</label>
            <select name="search_status_pcp" class="form-control" style="padding: 0.4rem 0.6rem; font-size: 0.8rem;">
                <option value="">-- Todos os Status --</option>
                <option value="FALTA" {{ $searchStatusPcp === 'FALTA' ? 'selected' : '' }}>FALTA</option>
                <option value="SEPARADO" {{ $searchStatusPcp === 'SEPARADO' ? 'selected' : '' }}>SEPARADO</option>
                <option value="FABRICA" {{ $searchStatusPcp === 'FABRICA' ? 'selected' : '' }}>FABRICA</option>
                <option value="PARCIAL" {{ $searchStatusPcp === 'PARCIAL' ? 'selected' : '' }}>PARCIAL</option>
            </select>
        </div>
        <div style="display: flex; gap: 0.4rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.4rem 0.85rem; font-size: 0.8rem; background-color: #6366f1;">
                🔍 Filtrar Painel
            </button>
            @if($searchPv || $searchCliente || $searchStatusPcp || $searchStatusPagamento)
                <a href="{{ route('pcp-painel.index') }}" class="btn btn-secondary" style="padding: 0.4rem 0.75rem; font-size: 0.8rem;">
                    ✕ Limpar
                </a>
            @endif
        </div>
    </form>
</div>

<!-- Tabela Principal do Painel PCP GMGs por PV -->
<div class="card" style="padding: 0; overflow: hidden;">
    <div class="table-responsive" style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.8rem;">
            <thead>
                <tr style="background-color: #0f172a; border-bottom: 1px solid var(--border-color); color: #94a3b8; text-align: left;">
                    <th style="padding: 0.75rem 0.85rem;">PV / Pedido</th>
                    <th style="padding: 0.75rem 0.85rem;">Cliente (C2_OBS)</th>
                    <th style="padding: 0.75rem 0.85rem;">Equipamento / Produto Pai</th>
                    <th style="padding: 0.75rem 0.85rem; text-align: center;">Avanço Separação</th>
                    <th style="padding: 0.75rem 0.85rem; text-align: center;">Motor</th>
                    <th style="padding: 0.75rem 0.85rem; text-align: center;">Alternador</th>
                    <th style="padding: 0.75rem 0.85rem; text-align: center;">Base</th>
                    <th style="padding: 0.75rem 0.85rem; text-align: center;">Carenagem</th>
                    <th style="padding: 0.75rem 0.85rem; text-align: center;">Alertas Compras</th>
                    <th style="padding: 0.75rem 0.85rem; text-align: right;">Investimento Pend.</th>
                    <th style="padding: 0.75rem 0.85rem; text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($painelData as $pvItem)
                <tr class="pv-row" style="border-bottom: 1px solid var(--border-color); transition: background 0.15s;">
                    <td style="padding: 0.75rem 0.85rem; font-weight: 700; color: #a5b4fc;">
                        {{ $pvItem['pv'] }}
                    </td>
                    <td style="padding: 0.75rem 0.85rem; max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $pvItem['cliente'] }}">
                        {{ $pvItem['cliente'] }}
                    </td>
                    <td style="padding: 0.75rem 0.85rem; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #cbd5e1;" title="{{ $pvItem['produto_pai'] }}">
                        {{ $pvItem['produto_pai'] }}
                    </td>
                    <td style="padding: 0.75rem 0.85rem; min-width: 140px;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.725rem; margin-bottom: 0.15rem;">
                            <span style="font-weight: 700; color: {{ $pvItem['percent_separado'] == 100 ? '#34d399' : '#f8fafc' }};">
                                {{ $pvItem['percent_separado'] }}%
                            </span>
                            <span style="color: var(--text-muted);">
                                {{ $pvItem['total_separado'] }}/{{ $pvItem['total_componentes'] }} it
                            </span>
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width: {{ $pvItem['percent_separado'] }}%;"></div>
                        </div>
                    </td>
                    <td style="padding: 0.75rem 0.85rem; text-align: center;">
                        @if($pvItem['motor_status'] === 'OK')
                            <span class="badge-ok">✓ OK</span>
                        @elseif($pvItem['motor_status'] === '-')
                            <span style="color: #64748b;">-</span>
                        @else
                            <span class="badge-alert" title="Investimento Pendente">{{ $pvItem['motor_status'] }}</span>
                        @endif
                    </td>
                    <td style="padding: 0.75rem 0.85rem; text-align: center;">
                        @if($pvItem['alternador_status'] === 'OK')
                            <span class="badge-ok">✓ OK</span>
                        @elseif($pvItem['alternador_status'] === '-')
                            <span style="color: #64748b;">-</span>
                        @else
                            <span class="badge-alert" title="Investimento Pendente">{{ $pvItem['alternador_status'] }}</span>
                        @endif
                    </td>
                    <td style="padding: 0.75rem 0.85rem; text-align: center;">
                        @if($pvItem['base_status'] === 'OK')
                            <span class="badge-ok">✓ OK</span>
                        @elseif($pvItem['base_status'] === '-')
                            <span style="color: #64748b;">-</span>
                        @else
                            <span class="badge-pen" title="Investimento Pendente">{{ $pvItem['base_status'] }}</span>
                        @endif
                    </td>
                    <td style="padding: 0.75rem 0.85rem; text-align: center;">
                        @if($pvItem['carenagem_status'] === 'OK')
                            <span class="badge-ok">✓ OK</span>
                        @elseif($pvItem['carenagem_status'] === '-')
                            <span style="color: #64748b;">-</span>
                        @else
                            <span class="badge-pen" title="Investimento Pendente">{{ $pvItem['carenagem_status'] }}</span>
                        @endif
                    </td>
                    <td style="padding: 0.75rem 0.85rem; text-align: center;">
                        @if($pvItem['sem_pedido_compra_count'] > 0)
                            <span class="badge-alert" style="margin-right: 0.2rem;" title="Itens em falta sem Pedido de Compra">
                                🚫 {{ $pvItem['sem_pedido_compra_count'] }} S/ PC
                            </span>
                        @endif
                        @if($pvItem['sem_preco_count'] > 0)
                            <span class="badge-pen" title="Itens em falta com valor unitário zero">
                                ⚠️ {{ $pvItem['sem_preco_count'] }} S/ $
                            </span>
                        @endif
                        @if($pvItem['sem_pedido_compra_count'] == 0 && $pvItem['sem_preco_count'] == 0)
                            <span style="color: #34d399; font-size: 0.725rem;">✓ Regular</span>
                        @endif
                    </td>
                    <td style="padding: 0.75rem 0.85rem; text-align: right; font-weight: 700; color: {{ $pvItem['investimento_pendente'] > 0 ? '#fbbf24' : '#34d399' }};">
                        R$ {{ number_format($pvItem['investimento_pendente'], 2, ',', '.') }}
                    </td>
                    <td style="padding: 0.75rem 0.85rem; text-align: center;">
                        <div style="display: flex; gap: 0.3rem; justify-content: center;">
                            <button type="button" class="btn btn-secondary" onclick="toggleDetails('pv_details_{{ $pvItem['pv'] }}')" style="padding: 0.2rem 0.45rem; font-size: 0.7rem;" title="Expandir componentes">
                                👁️ Detalhes
                            </button>
                            <a href="{{ route('estoque.index', ['f_pedido' => $pvItem['pv']]) }}" class="btn btn-secondary" style="padding: 0.2rem 0.45rem; font-size: 0.7rem;" title="Filtrar no Estoque">
                                📦
                            </a>
                            <a href="{{ route('compras.index', ['f_pv' => $pvItem['pv']]) }}" class="btn btn-primary" style="padding: 0.2rem 0.45rem; font-size: 0.7rem; background-color: #6366f1;" title="Filtrar em Compras">
                                🛒
                            </a>
                        </div>
                    </td>
                </tr>

                <!-- Linha Expansível de Detalhes do Pedido de Venda -->
                <tr id="pv_details_{{ $pvItem['pv'] }}" style="display: none;">
                    <td colspan="11" class="expand-content">
                        <div style="font-size: 0.775rem; font-weight: 700; color: #a5b4fc; margin-bottom: 0.6rem; display: flex; justify-content: space-between; align-items: center;">
                            <span>📦 Componentes do Pedido de Venda {{ $pvItem['pv'] }} ({{ $pvItem['total_componentes'] }} itens)</span>
                            <span style="color: var(--text-muted); font-weight: normal;">Clique nos ícones 📦 ou 🛒 para editar este pedido diretamente nas abas de origem.</span>
                        </div>
                        <div style="max-height: 250px; overflow-y: auto; background: #1e293b; border-radius: 0.5rem; border: 1px solid #334155;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.75rem;">
                                <thead>
                                    <tr style="background: #0f172a; color: #94a3b8; text-align: left; border-bottom: 1px solid #334155;">
                                        <th style="padding: 0.4rem 0.6rem;">Status PCP</th>
                                        <th style="padding: 0.4rem 0.6rem;">OP</th>
                                        <th style="padding: 0.4rem 0.6rem;">Código</th>
                                        <th style="padding: 0.4rem 0.6rem;">Descrição Produto</th>
                                        <th style="padding: 0.4rem 0.6rem; text-align: center;">Qtd OP</th>
                                        <th style="padding: 0.4rem 0.6rem; text-align: center;">Estoque</th>
                                        <th style="padding: 0.4rem 0.6rem; text-align: center;">Comprar</th>
                                        <th style="padding: 0.4rem 0.6rem;">N° PC</th>
                                        <th style="padding: 0.4rem 0.6rem;">Fornecedor</th>
                                        <th style="padding: 0.4rem 0.6rem; text-align: right;">Val. Total (R$)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pvItem['items'] as $subIt)
                                    @php
                                        $cSub = $subIt->compraItem;
                                        $valSubUnit = $cSub ? floatval($cSub->valor_unitario) : 0;
                                        $valSubTotal = $cSub ? floatval($cSub->valor_total) : 0;
                                        $subQtdComprar = max(0, floatval($subIt->quantidade) - floatval($subIt->quantidade_estoque));
                                    @endphp
                                    <tr style="border-bottom: 1px solid #334155;">
                                        <td style="padding: 0.4rem 0.6rem;">
                                            <span class="badge {{ match($subIt->status) { 'FALTA'=>'badge-falta','SEPARADO'=>'badge-separado','RETIRADO'=>'badge-retirado','FABRICA'=>'badge-fabrica', default=>'badge-kanban' } }}">
                                                {{ $subIt->status }}
                                            </span>
                                        </td>
                                        <td style="padding: 0.4rem 0.6rem; color: #c084fc;">{{ $subIt->op ?? '-' }}</td>
                                        <td style="padding: 0.4rem 0.6rem; font-weight: 600;">{{ $subIt->codigo_produto }}</td>
                                        <td style="padding: 0.4rem 0.6rem;">{{ $subIt->descricao }}</td>
                                        <td style="padding: 0.4rem 0.6rem; text-align: center;">{{ $subIt->quantidade }}</td>
                                        <td style="padding: 0.4rem 0.6rem; text-align: center; color: #fcd34d;">{{ $subIt->quantidade_estoque }}</td>
                                        <td style="padding: 0.4rem 0.6rem; text-align: center; font-weight: 700; color: {{ $subQtdComprar > 0 ? '#ef4444' : '#10b981' }};">
                                            {{ $subQtdComprar }}
                                        </td>
                                        <td style="padding: 0.4rem 0.6rem;">{{ $cSub->pedido_compra ?? '-' }}</td>
                                        <td style="padding: 0.4rem 0.6rem;">{{ $cSub->codigo_fornecedor ?? '-' }}</td>
                                        <td style="padding: 0.4rem 0.6rem; text-align: right; color: #6ee7b7;">
                                            R$ {{ number_format($valSubTotal, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                        🔍 Nenhum Pedido de Venda encontrado com os filtros selecionados.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    function toggleDetails(elementId) {
        const el = document.getElementById(elementId);
        if (el) {
            el.style.display = el.style.display === 'none' ? 'table-row' : 'none';
        }
    }
</script>
@endsection
