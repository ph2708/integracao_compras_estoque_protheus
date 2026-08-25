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
    .editable-cell-input {
        background-color: #0f172a;
        border: 1px solid #334155;
        border-radius: 0.25rem;
        color: #f8fafc;
        padding: 0.25rem 0.4rem;
        font-size: 0.75rem;
        width: 100%;
        box-sizing: border-box;
    }
    .editable-cell-input:focus {
        border-color: #6366f1;
        outline: none;
    }
</style>

<!-- Formulário de Filtro Superior -->
<form action="{{ route('pcp-painel.index') }}" method="GET" id="formFilterPcpPainel">
    <input type="hidden" name="f_info" id="input_f_info" value="{{ $fInfo }}">
    <input type="hidden" name="f_status_pv" id="input_f_status_pv" value="{{ $fStatusPv }}">
    <input type="hidden" name="f_fabrica" id="input_f_fabrica" value="{{ $fFabrica }}">
    <input type="hidden" name="f_marca" id="input_f_marca" value="{{ $fMarca }}">
</form>

<!-- Título da Página -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="font-size: 1.5rem; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
            🏭 Painel PCP GMGs (Visão Gerencial por PV)
        </h2>
        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.15rem;">
            Consolidação em tempo real do atendimento de estoque, compras, metadados (INFO, STATUS, FÁBRICA, MARCA) e componentes críticos por PV.
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem; align-items: center;">
        <button type="submit" form="formBatchPainelPcp" class="btn btn-primary" style="padding: 0.45rem 0.9rem; font-size: 0.8rem; background-color: #059669; border-color: #059669;" onclick="return confirm('Deseja salvar todas as alterações feitas nesta página?')">
            💾 Salvar Todas as Alterações da Página
        </button>
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

<!-- Filtros de Busca Principais -->
<div class="card" style="margin-bottom: 1.25rem; padding: 1rem;">
    <div style="display: flex; gap: 0.85rem; align-items: flex-end; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 160px;">
            <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.3rem; display: block;">N° Pedido de Venda (PV)</label>
            <input type="text" name="search_pv" value="{{ $searchPv }}" form="formFilterPcpPainel" class="form-control" placeholder="Ex: 006353..." style="padding: 0.4rem 0.6rem; font-size: 0.8rem;" onchange="document.getElementById('formFilterPcpPainel').submit()">
        </div>
        <div style="flex: 1.5; min-width: 220px;">
            <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.3rem; display: block;">Nome do Cliente / Obra (C2_OBS)</label>
            <input type="text" name="search_cliente" value="{{ $searchCliente }}" form="formFilterPcpPainel" class="form-control" placeholder="Ex: ISA ENERGIA..." style="padding: 0.4rem 0.6rem; font-size: 0.8rem;" onchange="document.getElementById('formFilterPcpPainel').submit()">
        </div>
        <div style="flex: 1; min-width: 160px;">
            <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.3rem; display: block;">Status PCP Componentes</label>
            <select name="search_status_pcp" form="formFilterPcpPainel" class="form-control" style="padding: 0.4rem 0.6rem; font-size: 0.8rem;" onchange="document.getElementById('formFilterPcpPainel').submit()">
                <option value="">-- Todos os Status PCP --</option>
                <option value="FALTA" {{ $searchStatusPcp === 'FALTA' ? 'selected' : '' }}>FALTA</option>
                <option value="SEPARADO" {{ $searchStatusPcp === 'SEPARADO' ? 'selected' : '' }}>SEPARADO</option>
                <option value="FABRICA" {{ $searchStatusPcp === 'FABRICA' ? 'selected' : '' }}>FABRICA</option>
                <option value="PARCIAL" {{ $searchStatusPcp === 'PARCIAL' ? 'selected' : '' }}>PARCIAL</option>
            </select>
        </div>
        <div style="display: flex; gap: 0.4rem;">
            <button type="submit" form="formFilterPcpPainel" class="btn btn-primary" style="padding: 0.4rem 0.85rem; font-size: 0.8rem; background-color: #6366f1;">
                🔍 Filtrar
            </button>
            @if($searchPv || $searchCliente || $searchStatusPcp || $searchStatusPagamento || $fInfo || $fStatusPv || $fFabrica || $fMarca)
                <a href="{{ route('pcp-painel.index') }}" class="btn btn-secondary" style="padding: 0.4rem 0.75rem; font-size: 0.8rem;">
                    ✕ Limpar Filtros
                </a>
            @endif
        </div>
    </div>
</div>

<!-- Tabela Principal do Painel PCP GMGs por PV com Edição Linear -->
<form action="{{ route('pcp-painel.update-batch') }}" method="POST" id="formBatchPainelPcp" onsubmit="window.mostrarLoading('💾 Salvando todas as alterações no Painel PCP...')">
    @csrf
    <div class="card" style="padding: 0; overflow: hidden;">
        <div class="table-responsive" style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.78rem;">
                <thead>
                    <tr style="background-color: #0f172a; border-bottom: 1px solid var(--border-color); color: #94a3b8; text-align: left;">
                        <th style="padding: 0.65rem 0.75rem; min-width: 130px;">INFO ✏️</th>
                        <th style="padding: 0.65rem 0.75rem; min-width: 130px;">STATUS (PV) ✏️</th>
                        <th style="padding: 0.65rem 0.75rem; min-width: 90px;">FÁBRICA ✏️</th>
                        <th style="padding: 0.65rem 0.75rem;">PV / Pedido</th>
                        <th style="padding: 0.65rem 0.75rem;">Cliente (C2_OBS)</th>
                        <th style="padding: 0.65rem 0.75rem;">Equipamento / Produto Pai</th>
                        <th style="padding: 0.65rem 0.75rem; min-width: 100px;">MARCA ✏️</th>
                        <th style="padding: 0.65rem 0.75rem; text-align: center;">Avanço Separação</th>
                        <th style="padding: 0.65rem 0.75rem; text-align: center;">Motor</th>
                        <th style="padding: 0.65rem 0.75rem; text-align: center;">Alternador</th>
                        <th style="padding: 0.65rem 0.75rem; text-align: center;">Base</th>
                        <th style="padding: 0.65rem 0.75rem; text-align: center;">Carenagem</th>
                        <th style="padding: 0.65rem 0.75rem; text-align: center;">Alertas Compras</th>
                        <th style="padding: 0.65rem 0.75rem; text-align: right;">Investimento Pend.</th>
                        <th style="padding: 0.65rem 0.75rem; text-align: center;">Ações</th>
                    </tr>

                    <!-- Linha de Filtros Múltiplos Estilo Excel no Cabeçalho -->
                    <tr class="filter-row" style="background-color: #1e293b; border-bottom: 2px solid var(--border-color);">
                        <!-- Filtro INFO -->
                        <th style="padding: 0.35rem 0.5rem;">
                            <div class="dropdown" style="position: relative; width: 100%;">
                                <button type="button" class="btn btn-secondary dropdown-toggle" id="btnFilterInfo" onclick="toggleMenuFilterInfo()" style="width: 100%; font-size: 0.7rem; padding: 0.2rem 0.35rem; justify-content: space-between; text-align: left; display: flex; align-items: center; background: #0f172a; border-color: #334155; height: 28px;">
                                    <span id="labelInfoSelecionados" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        @php $selInfoArr = $fInfo ? array_map('trim', explode(',', $fInfo)) : []; @endphp
                                        {{ empty($selInfoArr) ? 'INFO: Todos' : (count($selInfoArr) == 1 ? $selInfoArr[0] : count($selInfoArr) . ' Selec.') }}
                                    </span>
                                    <span style="font-size: 0.5rem;">▼</span>
                                </button>
                                <div id="dropdownMenuFilterInfo" style="display: none; position: absolute; top: 100%; left: 0; min-width: 200px; background: #1e293b; border: 1px solid #475569; border-radius: 0.5rem; padding: 0.6rem; z-index: 1000; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.5);">
                                    <div style="font-size: 0.7rem; font-weight: 700; color: #94a3b8; margin-bottom: 0.3rem; border-bottom: 1px solid #334155; padding-bottom: 0.2rem; display: flex; justify-content: space-between;">
                                        <span>FILTRAR INFO</span>
                                        <label style="font-weight: 400; cursor: pointer; color: #6366f1;">
                                            <input type="checkbox" id="chkFilterInfoAll" onchange="toggleSelectAllInfo(this)" {{ empty($selInfoArr) ? 'checked' : '' }}> Todos
                                        </label>
                                    </div>
                                    <div style="max-height: 180px; overflow-y: auto;">
                                        @foreach($opcoesInfo as $opInfo)
                                            <label style="display: flex; align-items: center; font-size: 0.725rem; margin-bottom: 0.3rem; cursor: pointer; color: #e2e8f0;">
                                                <input type="checkbox" class="chk-info-option" value="{{ $opInfo }}" {{ (empty($selInfoArr) || in_array($opInfo, $selInfoArr)) ? 'checked' : '' }} onchange="atualizarInfoLabel()" style="margin-right: 5px;">
                                                {{ $opInfo }}
                                            </label>
                                        @endforeach
                                    </div>
                                    <div style="margin-top: 0.4rem; padding-top: 0.3rem; border-top: 1px solid #334155; display: flex; justify-content: flex-end;">
                                        <button type="button" class="btn btn-primary" style="padding: 0.15rem 0.5rem; font-size: 0.68rem; background-color: #059669; border-color: #059669;" onclick="aplicarFiltroInfo()">✓ Aplicar</button>
                                    </div>
                                </div>
                            </div>
                        </th>

                        <!-- Filtro STATUS (PV) -->
                        <th style="padding: 0.35rem 0.5rem;">
                            <div class="dropdown" style="position: relative; width: 100%;">
                                <button type="button" class="btn btn-secondary dropdown-toggle" id="btnFilterStatusPv" onclick="toggleMenuFilterStatusPv()" style="width: 100%; font-size: 0.7rem; padding: 0.2rem 0.35rem; justify-content: space-between; text-align: left; display: flex; align-items: center; background: #0f172a; border-color: #334155; height: 28px;">
                                    <span id="labelStatusPvSelecionados" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        @php $selStPvArr = $fStatusPv ? array_map('trim', explode(',', $fStatusPv)) : []; @endphp
                                        {{ empty($selStPvArr) ? 'Status: Todos' : (count($selStPvArr) == 1 ? $selStPvArr[0] : count($selStPvArr) . ' Selec.') }}
                                    </span>
                                    <span style="font-size: 0.5rem;">▼</span>
                                </button>
                                <div id="dropdownMenuFilterStatusPv" style="display: none; position: absolute; top: 100%; left: 0; min-width: 200px; background: #1e293b; border: 1px solid #475569; border-radius: 0.5rem; padding: 0.6rem; z-index: 1000; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.5);">
                                    <div style="font-size: 0.7rem; font-weight: 700; color: #94a3b8; margin-bottom: 0.3rem; border-bottom: 1px solid #334155; padding-bottom: 0.2rem; display: flex; justify-content: space-between;">
                                        <span>STATUS DO PV</span>
                                        <label style="font-weight: 400; cursor: pointer; color: #6366f1;">
                                            <input type="checkbox" id="chkFilterStatusPvAll" onchange="toggleSelectAllStatusPv(this)" {{ empty($selStPvArr) ? 'checked' : '' }}> Todos
                                        </label>
                                    </div>
                                    <div style="max-height: 180px; overflow-y: auto;">
                                        @foreach($opcoesStatusPv as $opStPv)
                                            <label style="display: flex; align-items: center; font-size: 0.725rem; margin-bottom: 0.3rem; cursor: pointer; color: #e2e8f0;">
                                                <input type="checkbox" class="chk-status-pv-option" value="{{ $opStPv }}" {{ (empty($selStPvArr) || in_array($opStPv, $selStPvArr)) ? 'checked' : '' }} onchange="atualizarStatusPvLabel()" style="margin-right: 5px;">
                                                {{ $opStPv }}
                                            </label>
                                        @endforeach
                                    </div>
                                    <div style="margin-top: 0.4rem; padding-top: 0.3rem; border-top: 1px solid #334155; display: flex; justify-content: flex-end;">
                                        <button type="button" class="btn btn-primary" style="padding: 0.15rem 0.5rem; font-size: 0.68rem; background-color: #059669; border-color: #059669;" onclick="aplicarFiltroStatusPv()">✓ Aplicar</button>
                                    </div>
                                </div>
                            </div>
                        </th>

                        <!-- Filtro FÁBRICA -->
                        <th style="padding: 0.35rem 0.5rem;">
                            <div class="dropdown" style="position: relative; width: 100%;">
                                <button type="button" class="btn btn-secondary dropdown-toggle" id="btnFilterFabrica" onclick="toggleMenuFilterFabrica()" style="width: 100%; font-size: 0.7rem; padding: 0.2rem 0.35rem; justify-content: space-between; text-align: left; display: flex; align-items: center; background: #0f172a; border-color: #334155; height: 28px;">
                                    <span id="labelFabricaSelecionadas" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        @php $selFabArr = $fFabrica ? array_map('trim', explode(',', $fFabrica)) : []; @endphp
                                        {{ empty($selFabArr) ? 'Fábrica: Toda' : (count($selFabArr) == 1 ? $selFabArr[0] : count($selFabArr) . ' Selec.') }}
                                    </span>
                                    <span style="font-size: 0.5rem;">▼</span>
                                </button>
                                <div id="dropdownMenuFilterFabrica" style="display: none; position: absolute; top: 100%; left: 0; min-width: 180px; background: #1e293b; border: 1px solid #475569; border-radius: 0.5rem; padding: 0.6rem; z-index: 1000; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.5);">
                                    <div style="font-size: 0.7rem; font-weight: 700; color: #94a3b8; margin-bottom: 0.3rem; border-bottom: 1px solid #334155; padding-bottom: 0.2rem; display: flex; justify-content: space-between;">
                                        <span>FÁBRICA</span>
                                        <label style="font-weight: 400; cursor: pointer; color: #6366f1;">
                                            <input type="checkbox" id="chkFilterFabricaAll" onchange="toggleSelectAllFabrica(this)" {{ empty($selFabArr) ? 'checked' : '' }}> Toda
                                        </label>
                                    </div>
                                    <div style="max-height: 180px; overflow-y: auto;">
                                        @foreach($opcoesFabrica as $opFab)
                                            <label style="display: flex; align-items: center; font-size: 0.725rem; margin-bottom: 0.3rem; cursor: pointer; color: #e2e8f0;">
                                                <input type="checkbox" class="chk-fabrica-option" value="{{ $opFab }}" {{ (empty($selFabArr) || in_array($opFab, $selFabArr)) ? 'checked' : '' }} onchange="atualizarFabricaLabel()" style="margin-right: 5px;">
                                                Fábrica {{ $opFab }}
                                            </label>
                                        @endforeach
                                    </div>
                                    <div style="margin-top: 0.4rem; padding-top: 0.3rem; border-top: 1px solid #334155; display: flex; justify-content: flex-end;">
                                        <button type="button" class="btn btn-primary" style="padding: 0.15rem 0.5rem; font-size: 0.68rem; background-color: #059669; border-color: #059669;" onclick="aplicarFiltroFabrica()">✓ Aplicar</button>
                                    </div>
                                </div>
                            </div>
                        </th>

                        <th></th>
                        <th></th>
                        <th></th>

                        <!-- Filtro MARCA -->
                        <th style="padding: 0.35rem 0.5rem;">
                            <div class="dropdown" style="position: relative; width: 100%;">
                                <button type="button" class="btn btn-secondary dropdown-toggle" id="btnFilterMarca" onclick="toggleMenuFilterMarca()" style="width: 100%; font-size: 0.7rem; padding: 0.2rem 0.35rem; justify-content: space-between; text-align: left; display: flex; align-items: center; background: #0f172a; border-color: #334155; height: 28px;">
                                    <span id="labelMarcaSelecionadas" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        @php $selMarcaArr = $fMarca ? array_map('trim', explode(',', $fMarca)) : []; @endphp
                                        {{ empty($selMarcaArr) ? 'Marca: Todas' : (count($selMarcaArr) == 1 ? $selMarcaArr[0] : count($selMarcaArr) . ' Selec.') }}
                                    </span>
                                    <span style="font-size: 0.5rem;">▼</span>
                                </button>
                                <div id="dropdownMenuFilterMarca" style="display: none; position: absolute; top: 100%; left: 0; min-width: 180px; background: #1e293b; border: 1px solid #475569; border-radius: 0.5rem; padding: 0.6rem; z-index: 1000; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.5);">
                                    <div style="font-size: 0.7rem; font-weight: 700; color: #94a3b8; margin-bottom: 0.3rem; border-bottom: 1px solid #334155; padding-bottom: 0.2rem; display: flex; justify-content: space-between;">
                                        <span>MARCA</span>
                                        <label style="font-weight: 400; cursor: pointer; color: #6366f1;">
                                            <input type="checkbox" id="chkFilterMarcaAll" onchange="toggleSelectAllMarca(this)" {{ empty($selMarcaArr) ? 'checked' : '' }}> Todas
                                        </label>
                                    </div>
                                    <div style="max-height: 180px; overflow-y: auto;">
                                        @foreach($opcoesMarca as $opMarc)
                                            <label style="display: flex; align-items: center; font-size: 0.725rem; margin-bottom: 0.3rem; cursor: pointer; color: #e2e8f0;">
                                                <input type="checkbox" class="chk-marca-option" value="{{ $opMarc }}" {{ (empty($selMarcaArr) || in_array($opMarc, $selMarcaArr)) ? 'checked' : '' }} onchange="atualizarMarcaLabel()" style="margin-right: 5px;">
                                                {{ $opMarc }}
                                            </label>
                                        @endforeach
                                    </div>
                                    <div style="margin-top: 0.4rem; padding-top: 0.3rem; border-top: 1px solid #334155; display: flex; justify-content: flex-end;">
                                        <button type="button" class="btn btn-primary" style="padding: 0.15rem 0.5rem; font-size: 0.68rem; background-color: #059669; border-color: #059669;" onclick="aplicarFiltroMarca()">✓ Aplicar</button>
                                    </div>
                                </div>
                            </div>
                        </th>

                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($painelData as $pvItem)
                    @php $pvKey = $pvItem['pv']; @endphp
                    <tr class="pv-row" style="border-bottom: 1px solid var(--border-color); transition: background 0.15s;">
                        <!-- Célula Editável INFO -->
                        <td style="padding: 0.5rem 0.6rem;">
                            <input type="text" name="pvs[{{ $pvKey }}][info]" value="{{ $pvItem['info'] }}" class="editable-cell-input" placeholder="Ex: CAR 55 KVA...">
                        </td>

                        <!-- Célula Editável STATUS PV -->
                        <td style="padding: 0.5rem 0.6rem;">
                            <select name="pvs[{{ $pvKey }}][status_pv]" class="editable-cell-input" style="background-color: #0f172a; color: #f8fafc;">
                                <option value="">-- Selecione --</option>
                                <option value="FATURADO" {{ $pvItem['status_pv'] === 'FATURADO' ? 'selected' : '' }}>FATURADO</option>
                                <option value="COMPRAS" {{ $pvItem['status_pv'] === 'COMPRAS' ? 'selected' : '' }}>COMPRAS</option>
                                <option value="ENGENHARIA" {{ $pvItem['status_pv'] === 'ENGENHARIA' ? 'selected' : '' }}>ENGENHARIA</option>
                                <option value="ESTOQUE" {{ $pvItem['status_pv'] === 'ESTOQUE' ? 'selected' : '' }}>ESTOQUE</option>
                                <option value="FINANCEIRO" {{ $pvItem['status_pv'] === 'FINANCEIRO' ? 'selected' : '' }}>FINANCEIRO</option>
                                <option value="ENTREGUE" {{ $pvItem['status_pv'] === 'ENTREGUE' ? 'selected' : '' }}>ENTREGUE</option>
                                <option value="CANCELADO" {{ $pvItem['status_pv'] === 'CANCELADO' ? 'selected' : '' }}>CANCELADO</option>
                            </select>
                        </td>

                        <!-- Célula Editável FÁBRICA -->
                        <td style="padding: 0.5rem 0.6rem;">
                            <input type="text" name="pvs[{{ $pvKey }}][fabrica]" value="{{ $pvItem['fabrica'] }}" class="editable-cell-input" placeholder="Ex: 99, 18...">
                        </td>

                        <!-- PV / Pedido -->
                        <td style="padding: 0.65rem 0.75rem; font-weight: 700; color: #a5b4fc;">
                            {{ $pvItem['pv'] }}
                        </td>

                        <!-- Cliente -->
                        <td style="padding: 0.65rem 0.75rem; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $pvItem['cliente'] }}">
                            {{ $pvItem['cliente'] }}
                        </td>

                        <!-- Equipamento / Produto Pai -->
                        <td style="padding: 0.65rem 0.75rem; max-width: 190px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #cbd5e1;" title="{{ $pvItem['produto_pai'] }}">
                            {{ $pvItem['produto_pai'] }}
                        </td>

                        <!-- Célula Editável MARCA -->
                        <td style="padding: 0.5rem 0.6rem;">
                            <input type="text" name="pvs[{{ $pvKey }}][marca]" value="{{ $pvItem['marca'] }}" class="editable-cell-input" placeholder="Ex: PERKINS, SCANIA...">
                        </td>

                        <!-- Avanço Separação -->
                        <td style="padding: 0.65rem 0.75rem; min-width: 135px;">
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

                        <!-- Componentes Críticos -->
                        <td style="padding: 0.65rem 0.75rem; text-align: center;">
                            @if($pvItem['motor_status'] === 'OK') <span class="badge-ok">✓ OK</span>
                            @elseif($pvItem['motor_status'] === '-') <span style="color: #64748b;">-</span>
                            @else <span class="badge-alert" title="Investimento Pendente">{{ $pvItem['motor_status'] }}</span>
                            @endif
                        </td>
                        <td style="padding: 0.65rem 0.75rem; text-align: center;">
                            @if($pvItem['alternador_status'] === 'OK') <span class="badge-ok">✓ OK</span>
                            @elseif($pvItem['alternador_status'] === '-') <span style="color: #64748b;">-</span>
                            @else <span class="badge-alert" title="Investimento Pendente">{{ $pvItem['alternador_status'] }}</span>
                            @endif
                        </td>
                        <td style="padding: 0.65rem 0.75rem; text-align: center;">
                            @if($pvItem['base_status'] === 'OK') <span class="badge-ok">✓ OK</span>
                            @elseif($pvItem['base_status'] === '-') <span style="color: #64748b;">-</span>
                            @else <span class="badge-pen" title="Investimento Pendente">{{ $pvItem['base_status'] }}</span>
                            @endif
                        </td>
                        <td style="padding: 0.65rem 0.75rem; text-align: center;">
                            @if($pvItem['carenagem_status'] === 'OK') <span class="badge-ok">✓ OK</span>
                            @elseif($pvItem['carenagem_status'] === '-') <span style="color: #64748b;">-</span>
                            @else <span class="badge-pen" title="Investimento Pendente">{{ $pvItem['carenagem_status'] }}</span>
                            @endif
                        </td>

                        <!-- Alertas Compras -->
                        <td style="padding: 0.65rem 0.75rem; text-align: center;">
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

                        <!-- Investimento Pendente -->
                        <td style="padding: 0.65rem 0.75rem; text-align: right; font-weight: 700; color: {{ $pvItem['investimento_pendente'] > 0 ? '#fbbf24' : '#34d399' }};">
                            R$ {{ number_format($pvItem['investimento_pendente'], 2, ',', '.') }}
                        </td>

                        <!-- Ações -->
                        <td style="padding: 0.65rem 0.75rem; text-align: center;">
                            <div style="display: flex; gap: 0.25rem; justify-content: center;">
                                <button type="button" class="btn btn-secondary" onclick="toggleDetails('pv_details_{{ $pvItem['pv'] }}')" style="padding: 0.2rem 0.4rem; font-size: 0.7rem;" title="Expandir componentes">
                                    👁️
                                </button>
                                <a href="{{ route('estoque.index', ['f_pedido' => $pvItem['pv']]) }}" class="btn btn-secondary" style="padding: 0.2rem 0.4rem; font-size: 0.7rem;" title="Filtrar no Estoque">
                                    📦
                                </a>
                                <a href="{{ route('compras.index', ['f_pv' => $pvItem['pv']]) }}" class="btn btn-primary" style="padding: 0.2rem 0.4rem; font-size: 0.7rem; background-color: #6366f1;" title="Filtrar em Compras">
                                    🛒
                                </a>
                            </div>
                        </td>
                    </tr>

                    <!-- Linha Expansível de Detalhes do Pedido de Venda -->
                    <tr id="pv_details_{{ $pvItem['pv'] }}" style="display: none;">
                        <td colspan="15" class="expand-content">
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
                        <td colspan="15" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            🔍 Nenhum Pedido de Venda encontrado com os filtros selecionados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>

<script>
    function toggleDetails(elementId) {
        const el = document.getElementById(elementId);
        if (el) {
            el.style.display = el.style.display === 'none' ? 'table-row' : 'none';
        }
    }

    // Gestores dos Filtros Múltiplos Estilo Excel

    // 1. INFO
    function toggleMenuFilterInfo() { const el = document.getElementById('dropdownMenuFilterInfo'); if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none'; }
    function toggleSelectAllInfo(m) { document.querySelectorAll('.chk-info-option').forEach(c => c.checked = m.checked); atualizarInfoLabel(); }
    function atualizarInfoLabel() {
        const chks = document.querySelectorAll('.chk-info-option:checked');
        const tot = document.querySelectorAll('.chk-info-option').length;
        const vals = Array.from(chks).map(c => c.value);
        const lbl = document.getElementById('labelInfoSelecionados');
        const m = document.getElementById('chkFilterInfoAll');
        if (!lbl) return;
        if (vals.length === tot || vals.length === 0) { lbl.innerText = 'INFO: Todos'; if (m) m.checked = (vals.length === tot); }
        else { if (m) m.checked = false; lbl.innerText = vals.length === 1 ? vals[0] : vals.length + ' Selec.'; }
    }
    function aplicarFiltroInfo() {
        const chks = document.querySelectorAll('.chk-info-option:checked');
        const tot = document.querySelectorAll('.chk-info-option').length;
        const vals = Array.from(chks).map(c => c.value);
        document.getElementById('input_f_info').value = (vals.length === tot || vals.length === 0) ? '' : vals.join(',');
        document.getElementById('formFilterPcpPainel').submit();
    }

    // 2. STATUS PV
    function toggleMenuFilterStatusPv() { const el = document.getElementById('dropdownMenuFilterStatusPv'); if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none'; }
    function toggleSelectAllStatusPv(m) { document.querySelectorAll('.chk-status-pv-option').forEach(c => c.checked = m.checked); atualizarStatusPvLabel(); }
    function atualizarStatusPvLabel() {
        const chks = document.querySelectorAll('.chk-status-pv-option:checked');
        const tot = document.querySelectorAll('.chk-status-pv-option').length;
        const vals = Array.from(chks).map(c => c.value);
        const lbl = document.getElementById('labelStatusPvSelecionados');
        const m = document.getElementById('chkFilterStatusPvAll');
        if (!lbl) return;
        if (vals.length === tot || vals.length === 0) { lbl.innerText = 'Status: Todos'; if (m) m.checked = (vals.length === tot); }
        else { if (m) m.checked = false; lbl.innerText = vals.length === 1 ? vals[0] : vals.length + ' Selec.'; }
    }
    function aplicarFiltroStatusPv() {
        const chks = document.querySelectorAll('.chk-status-pv-option:checked');
        const tot = document.querySelectorAll('.chk-status-pv-option').length;
        const vals = Array.from(chks).map(c => c.value);
        document.getElementById('input_f_status_pv').value = (vals.length === tot || vals.length === 0) ? '' : vals.join(',');
        document.getElementById('formFilterPcpPainel').submit();
    }

    // 3. FÁBRICA
    function toggleMenuFilterFabrica() { const el = document.getElementById('dropdownMenuFilterFabrica'); if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none'; }
    function toggleSelectAllFabrica(m) { document.querySelectorAll('.chk-fabrica-option').forEach(c => c.checked = m.checked); atualizarFabricaLabel(); }
    function atualizarFabricaLabel() {
        const chks = document.querySelectorAll('.chk-fabrica-option:checked');
        const tot = document.querySelectorAll('.chk-fabrica-option').length;
        const vals = Array.from(chks).map(c => c.value);
        const lbl = document.getElementById('labelFabricaSelecionadas');
        const m = document.getElementById('chkFilterFabricaAll');
        if (!lbl) return;
        if (vals.length === tot || vals.length === 0) { lbl.innerText = 'Fábr.: Toda'; if (m) m.checked = (vals.length === tot); }
        else { if (m) m.checked = false; lbl.innerText = vals.length === 1 ? vals[0] : vals.length + ' Selec.'; }
    }
    function aplicarFiltroFabrica() {
        const chks = document.querySelectorAll('.chk-fabrica-option:checked');
        const tot = document.querySelectorAll('.chk-fabrica-option').length;
        const vals = Array.from(chks).map(c => c.value);
        document.getElementById('input_f_fabrica').value = (vals.length === tot || vals.length === 0) ? '' : vals.join(',');
        document.getElementById('formFilterPcpPainel').submit();
    }

    // 4. MARCA
    function toggleMenuFilterMarca() { const el = document.getElementById('dropdownMenuFilterMarca'); if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none'; }
    function toggleSelectAllMarca(m) { document.querySelectorAll('.chk-marca-option').forEach(c => c.checked = m.checked); atualizarMarcaLabel(); }
    function atualizarMarcaLabel() {
        const chks = document.querySelectorAll('.chk-marca-option:checked');
        const tot = document.querySelectorAll('.chk-marca-option').length;
        const vals = Array.from(chks).map(c => c.value);
        const lbl = document.getElementById('labelMarcaSelecionadas');
        const m = document.getElementById('chkFilterMarcaAll');
        if (!lbl) return;
        if (vals.length === tot || vals.length === 0) { lbl.innerText = 'Marca: Todas'; if (m) m.checked = (vals.length === tot); }
        else { if (m) m.checked = false; lbl.innerText = vals.length === 1 ? vals[0] : vals.length + ' Selec.'; }
    }
    function aplicarFiltroMarca() {
        const chks = document.querySelectorAll('.chk-marca-option:checked');
        const tot = document.querySelectorAll('.chk-marca-option').length;
        const vals = Array.from(chks).map(c => c.value);
        document.getElementById('input_f_marca').value = (vals.length === tot || vals.length === 0) ? '' : vals.join(',');
        document.getElementById('formFilterPcpPainel').submit();
    }

    // Fechar dropdowns ao clicar fora
    document.addEventListener('click', function(e) {
        ['Info', 'StatusPv', 'Fabrica', 'Marca'].forEach(name => {
            const menu = document.getElementById('dropdownMenuFilter' + name);
            const btn = document.getElementById('btnFilter' + name);
            if (menu && btn && !menu.contains(e.target) && !btn.contains(e.target)) {
                menu.style.display = 'none';
            }
        });
    });
</script>
@endsection
