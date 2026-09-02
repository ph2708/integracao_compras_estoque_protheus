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
    .modal-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.75);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        backdrop-filter: blur(4px);
    }
    .modal-box {
        background: #0f172a;
        border: 1px solid #334155;
        border-radius: 0.75rem;
        width: 90%;
        max-width: 750px;
        padding: 1.25rem;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.8);
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
    <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
        @if($canEditPcp ?? true)
        <!-- Botão Consultar PVs Protheus -->
        <button type="button" class="btn btn-primary" onclick="abrirModalConsultarProtheus()" style="padding: 0.45rem 0.85rem; font-size: 0.8rem; background-color: #6366f1;">
            ➕ Consultar/Incluir PVs Protheus
        </button>

        <!-- Botão Criar PV Manual -->
        <button type="button" class="btn btn-secondary" onclick="abrirModalCriarPvManual()" style="padding: 0.45rem 0.85rem; font-size: 0.8rem;">
            ✏️ Criar PV Manual
        </button>
        @else
        <span class="badge" style="background-color: rgba(99, 102, 241, 0.2); color: #a5b4fc; border: 1px solid rgba(99, 102, 241, 0.4); padding: 0.35rem 0.65rem; font-size: 0.75rem;">
            👁️ Modo Visualização (Apenas Leitura)
        </span>
        @endif

        <!-- Botão Gestor de Colunas Visíveis (Excel) -->
        <div class="dropdown" style="position: relative; display: inline-block;">
            <button type="button" class="btn btn-secondary" onclick="toggleMenuColunasPainelPcp()" style="padding: 0.45rem 0.75rem; font-size: 0.8rem; border-color: rgba(99, 102, 241, 0.5); font-weight: 500;">
                ⚙️ Colunas Visíveis (Excel) ▾
            </button>
            <div id="dropdownMenuColunasPainelPcp" style="display: none; position: absolute; right: 0; top: 110%; z-index: 1000; background-color: #0f172a; border: 1px solid #334155; border-radius: 0.5rem; padding: 0.85rem; width: 300px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.7); font-size: 0.78rem;">
                <div style="font-weight: 700; margin-bottom: 0.6rem; color: #a5b4fc; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; padding-bottom: 0.4rem;">
                    <span>👁️ Exibir/Ocultar Colunas</span>
                    <span style="font-size: 0.7rem; color: #94a3b8; font-weight: normal;">(Salvo Auto)</span>
                </div>
                <div style="max-height: 290px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.45rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;"><input type="checkbox" id="chk_col-info" onchange="toggleColunaPainelPcp('col-info', this.checked)"> INFO ✏️</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;"><input type="checkbox" id="chk_col-status-pv" onchange="toggleColunaPainelPcp('col-status-pv', this.checked)"> STATUS (PV) ✏️</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: #38bdf8;"><input type="checkbox" id="chk_col-fabrica" onchange="toggleColunaPainelPcp('col-fabrica', this.checked)"> FÁBRICA ✏️ ⬆️</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;"><input type="checkbox" id="chk_col-pv" onchange="toggleColunaPainelPcp('col-pv', this.checked)"> PV / Pedido</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;"><input type="checkbox" id="chk_col-cliente" onchange="toggleColunaPainelPcp('col-cliente', this.checked)"> Cliente (C2_OBS)</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: #c084fc;"><input type="checkbox" id="chk_col-produto-pai" onchange="toggleColunaPainelPcp('col-produto-pai', this.checked)"> Equipamento / Produto Pai</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;"><input type="checkbox" id="chk_col-marca" onchange="toggleColunaPainelPcp('col-marca', this.checked)"> MARCA ✏️</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;"><input type="checkbox" id="chk_col-avanco" onchange="toggleColunaPainelPcp('col-avanco', this.checked)"> Avanço Separação</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;"><input type="checkbox" id="chk_col-motor" onchange="toggleColunaPainelPcp('col-motor', this.checked)"> Motor</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;"><input type="checkbox" id="chk_col-alternador" onchange="toggleColunaPainelPcp('col-alternador', this.checked)"> Alternador</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;"><input type="checkbox" id="chk_col-base" onchange="toggleColunaPainelPcp('col-base', this.checked)"> Base</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;"><input type="checkbox" id="chk_col-carenagem" onchange="toggleColunaPainelPcp('col-carenagem', this.checked)"> Carenagem</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;"><input type="checkbox" id="chk_col-alertas" onchange="toggleColunaPainelPcp('col-alertas', this.checked)"> Alertas Compras</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: #38bdf8;"><input type="checkbox" id="chk_col-valor-bruto" onchange="toggleColunaPainelPcp('col-valor-bruto', this.checked)"> VALOR BRUTO</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;"><input type="checkbox" id="chk_col-investimento" onchange="toggleColunaPainelPcp('col-investimento', this.checked)"> Investimento Pendente</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: #34d399;"><input type="checkbox" id="chk_col-valor-pago" onchange="toggleColunaPainelPcp('col-valor-pago', this.checked)"> INVESTIMENTO (PAGO)</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: #fbbf24;"><input type="checkbox" id="chk_col-valor-pa" onchange="toggleColunaPainelPcp('col-valor-pa', this.checked)"> PA (R$)</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: #60a5fa;"><input type="checkbox" id="chk_col-valor-faturado" onchange="toggleColunaPainelPcp('col-valor-faturado', this.checked)"> FATURADO (R$)</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;"><input type="checkbox" id="chk_col-qtd" onchange="toggleColunaPainelPcp('col-qtd', this.checked)"> QTD ✏️</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;"><input type="checkbox" id="chk_col-time-prod" onchange="toggleColunaPainelPcp('col-time-prod', this.checked)"> TIME PROD ✏️</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;"><input type="checkbox" id="chk_col-data-pa-pg" onchange="toggleColunaPainelPcp('col-data-pa-pg', this.checked)"> PA (PG) ✏️</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;"><input type="checkbox" id="chk_col-data-pronto" onchange="toggleColunaPainelPcp('col-data-pronto', this.checked)"> PRONTO ✏️</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;"><input type="checkbox" id="chk_col-data-contratual" onchange="toggleColunaPainelPcp('col-data-contratual', this.checked)"> DATA CONTRATUAL ✏️</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;"><input type="checkbox" id="chk_col-data-emissao" onchange="toggleColunaPainelPcp('col-data-emissao', this.checked)"> DATA EMISSÃO ✏️</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;"><input type="checkbox" id="chk_col-data-boom" onchange="toggleColunaPainelPcp('col-data-boom', this.checked)"> DATA BOOM ✏️</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;"><input type="checkbox" id="chk_col-data-liberacao-estoque" onchange="toggleColunaPainelPcp('col-data-liberacao-estoque', this.checked)"> LIBERAÇÃO ESTOQUE ✏️</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: #a5b4fc;"><input type="checkbox" id="chk_col-data-pronto-real" onchange="toggleColunaPainelPcp('col-data-pronto-real', this.checked)"> DATA PRONTO ✏️</label>
                </div>
            </div>
        </div>

        <button type="submit" form="formBatchPainelPcp" class="btn btn-primary" style="padding: 0.45rem 0.9rem; font-size: 0.8rem; background-color: #059669; border-color: #059669;" onclick="return confirm('Deseja salvar todas as alterações feitas nesta página?')">
            💾 Salvar Todas as Alterações da Página
        </button>
    </div>
</div>

<!-- Cards Executivos de KPI -->
<div style="display: flex; gap: 1rem; margin-bottom: 1.25rem; flex-wrap: wrap;">
    <div class="kpi-card" style="border-left: 4px solid #6366f1; background: rgba(99, 102, 241, 0.05); flex: 1; min-width: 170px;">
        <div class="kpi-title">📋 Pedidos Acompanhados</div>
        <div class="kpi-value" style="color: #818cf8;">{{ number_format($kpiTotalPv ?? 0, 0, ',', '.') }}</div>
        <div class="kpi-sub">Pedidos de Venda ativos na base</div>
    </div>
    <div class="kpi-card" style="border-left: 4px solid #10b981; background: rgba(16, 185, 129, 0.05); flex: 1; min-width: 170px;">
        <div class="kpi-title">📈 Avanço Médio Separação</div>
        <div class="kpi-value" style="color: #34d399;">{{ number_format($kpiMediaSeparacao ?? 0, 1, ',', '.') }}%</div>
        <div class="kpi-sub">Porcentagem média de atendimento</div>
    </div>
    <div class="kpi-card" style="border-left: 4px solid #38bdf8; background: rgba(56, 189, 248, 0.05); flex: 1.2; min-width: 200px;">
        <div class="kpi-title">💲 Valor Bruto Total (PVs)</div>
        <div class="kpi-value" style="color: #38bdf8;">R$ {{ number_format($kpiValorBrutoTotal ?? 0, 2, ',', '.') }}</div>
        <div class="kpi-sub">Soma do valor bruto real dos PVs</div>
    </div>
    <div class="kpi-card" style="border-left: 4px solid #f59e0b; background: rgba(245, 158, 11, 0.05); flex: 1.2; min-width: 200px;">
        <div class="kpi-title">💰 Investimento Pendente</div>
        <div class="kpi-value" style="color: #fbbf24;">R$ {{ number_format($kpiInvestimentoTotal ?? 0, 2, ',', '.') }}</div>
        <div class="kpi-sub">Montante total a comprar (Faltas)</div>
    </div>
    <div class="kpi-card" style="border-left: 4px solid #ef4444; background: rgba(239, 68, 68, 0.05); flex: 1; min-width: 170px;">
        <div class="kpi-title">⚠️ Pedidos com Faltas</div>
        <div class="kpi-value" style="color: #fca5a5;">{{ number_format($kpiPvsComFalta ?? 0, 0, ',', '.') }}</div>
        <div class="kpi-sub">Requerem ação de compras</div>
    </div>
</div>

<!-- Filtros de Busca Principais -->
<div class="card" style="margin-bottom: 1.25rem; padding: 1rem;">
    <div style="display: flex; gap: 0.85rem; align-items: flex-end; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 140px;">
            <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.3rem; display: block;">N° Pedido de Venda (PV)</label>
            <input type="text" name="search_pv" value="{{ $searchPv }}" form="formFilterPcpPainel" class="form-control" placeholder="Multi: 006353, 006354..." style="padding: 0.4rem 0.6rem; font-size: 0.8rem;" onchange="document.getElementById('formFilterPcpPainel').submit()">
        </div>
        <div style="flex: 1.4; min-width: 200px;">
            <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.3rem; display: block;">Nome do Cliente / Obra (C2_OBS)</label>
            <input type="text" name="search_cliente" value="{{ $searchCliente }}" form="formFilterPcpPainel" class="form-control" placeholder="Multi: ISA, COPEL..." style="padding: 0.4rem 0.6rem; font-size: 0.8rem;" onchange="document.getElementById('formFilterPcpPainel').submit()">
        </div>
        <div style="flex: 1; min-width: 140px;">
            <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.3rem; display: block;">Status PCP Componentes</label>
            <select name="search_status_pcp" form="formFilterPcpPainel" class="form-control" style="padding: 0.4rem 0.6rem; font-size: 0.8rem;" onchange="document.getElementById('formFilterPcpPainel').submit()">
                <option value="">-- Todos os Status --</option>
                <option value="FALTA" {{ $searchStatusPcp === 'FALTA' ? 'selected' : '' }}>FALTA</option>
                <option value="SEPARADO" {{ $searchStatusPcp === 'SEPARADO' ? 'selected' : '' }}>SEPARADO</option>
                <option value="FABRICA" {{ $searchStatusPcp === 'FABRICA' ? 'selected' : '' }}>FABRICA</option>
                <option value="PARCIAL" {{ $searchStatusPcp === 'PARCIAL' ? 'selected' : '' }}>PARCIAL</option>
            </select>
        </div>
        <div style="flex: 1.2; min-width: 140px;">
            <label style="font-size: 0.75rem; font-weight: 600; color: #a5b4fc; margin-bottom: 0.3rem; display: block;">📅 Mês Pronto</label>
            <select name="f_data_pronto_mes" id="top_f_data_pronto_mes" form="formFilterPcpPainel" class="form-control" style="padding: 0.4rem 0.6rem; font-size: 0.8rem; border-color: #6366f1; background-color: #0f172a; color: #a5b4fc; font-weight: 600;" onchange="syncMonthFilter(this.value)">
                <option value="">📅 Mês: Todos</option>
                <option value="01" {{ request('f_data_pronto_mes') == '01' ? 'selected' : '' }}>01 - Janeiro</option>
                <option value="02" {{ request('f_data_pronto_mes') == '02' ? 'selected' : '' }}>02 - Fevereiro</option>
                <option value="03" {{ request('f_data_pronto_mes') == '03' ? 'selected' : '' }}>03 - Março</option>
                <option value="04" {{ request('f_data_pronto_mes') == '04' ? 'selected' : '' }}>04 - Abril</option>
                <option value="05" {{ request('f_data_pronto_mes') == '05' ? 'selected' : '' }}>05 - Maio</option>
                <option value="06" {{ request('f_data_pronto_mes') == '06' ? 'selected' : '' }}>06 - Junho</option>
                <option value="07" {{ request('f_data_pronto_mes') == '07' ? 'selected' : '' }}>07 - Julho</option>
                <option value="08" {{ request('f_data_pronto_mes') == '08' ? 'selected' : '' }}>08 - Agosto</option>
                <option value="09" {{ request('f_data_pronto_mes') == '09' ? 'selected' : '' }}>09 - Setembro</option>
                <option value="10" {{ request('f_data_pronto_mes') == '10' ? 'selected' : '' }}>10 - Outubro</option>
                <option value="11" {{ request('f_data_pronto_mes') == '11' ? 'selected' : '' }}>11 - Novembro</option>
                <option value="12" {{ request('f_data_pronto_mes') == '12' ? 'selected' : '' }}>12 - Dezembro</option>
            </select>
        </div>
        <div style="display: flex; gap: 0.4rem; flex: 1.4; min-width: 240px;">
            <div style="flex: 1;">
                <label style="font-size: 0.75rem; font-weight: 600; color: #a5b4fc; margin-bottom: 0.3rem; display: block;">📅 Previsto De</label>
                <input type="text" name="f_data_pronto_de" value="{{ $fDataProntoDe }}" form="formFilterPcpPainel" class="form-control" placeholder="01/08/26" style="padding: 0.4rem 0.5rem; font-size: 0.8rem; border-color: #6366f1;" onchange="document.getElementById('formFilterPcpPainel').submit()">
            </div>
            <div style="flex: 1;">
                <label style="font-size: 0.75rem; font-weight: 600; color: #a5b4fc; margin-bottom: 0.3rem; display: block;">📅 Previsto Até</label>
                <input type="text" name="f_data_pronto_ate" value="{{ $fDataProntoAte }}" form="formFilterPcpPainel" class="form-control" placeholder="31/08/26" style="padding: 0.4rem 0.5rem; font-size: 0.8rem; border-color: #6366f1;" onchange="document.getElementById('formFilterPcpPainel').submit()">
            </div>
        </div>
        <div style="display: flex; gap: 0.4rem;">
            <button type="submit" form="formFilterPcpPainel" class="btn btn-primary" style="padding: 0.4rem 0.85rem; font-size: 0.8rem; background-color: #6366f1;">
                🔍 Filtrar
            </button>
            @if($searchPv || $searchCliente || $searchStatusPcp || $searchStatusPagamento || $fInfo || $fStatusPv || $fFabrica || $fMarca || $fDataPronto || $fDataProntoDe || $fDataProntoAte || request('f_data_pronto_mes'))
                <a href="{{ route('pcp-painel.index') }}" class="btn btn-secondary" style="padding: 0.4rem 0.75rem; font-size: 0.8rem;">
                    ✕ Limpar Filtros
                </a>
            @endif
            @if($canEditPcp ?? true)
                <button type="submit" form="formBatchPainelPcp" class="btn btn-primary" style="padding: 0.4rem 0.85rem; font-size: 0.8rem; background-color: #059669; border-color: #059669;">
                    💾 Salvar Lote
                </button>
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
                        <th class="col-info" style="padding: 0.65rem 0.75rem; min-width: 130px;">INFO ✏️</th>
                        <th class="col-status-pv" style="padding: 0.65rem 0.75rem; min-width: 130px;">STATUS (PV) ✏️</th>
                        <th class="col-fabrica" style="padding: 0.65rem 0.75rem; min-width: 90px; color: #38bdf8;" title="Ordenado em sequência numérica crescente">FÁBRICA ✏️ ⬆️</th>
                        <th class="col-pv" style="padding: 0.65rem 0.75rem;">PV / Pedido</th>
                        <th class="col-cliente" style="padding: 0.65rem 0.75rem;">Cliente (C2_OBS)</th>
                        <th class="col-produto-pai" style="padding: 0.65rem 0.75rem; min-width: 300px; color: #c084fc;">Equipamento / Produto Pai</th>
                        <th class="col-qtd" style="padding: 0.65rem 0.75rem; text-align: center; width: 60px;">QTD ✏️</th>
                        <th class="col-marca" style="padding: 0.65rem 0.75rem; min-width: 100px;">MARCA ✏️</th>
                        <th class="col-motor" style="padding: 0.65rem 0.75rem; text-align: center;">Motor</th>
                        <th class="col-alternador" style="padding: 0.65rem 0.75rem; text-align: center;">Alternador</th>
                        <th class="col-base" style="padding: 0.65rem 0.75rem; text-align: center;">Base</th>
                        <th class="col-carenagem" style="padding: 0.65rem 0.75rem; text-align: center;">Carenagem</th>
                        <th class="col-alertas" style="padding: 0.65rem 0.75rem; text-align: center;">Alertas Compras</th>
                        <th class="col-avanco" style="padding: 0.65rem 0.75rem; text-align: center;">Avanço Separação</th>
                        <th class="col-valor-pa" style="padding: 0.65rem 0.75rem; text-align: right; color: #fbbf24;">PA (R$)</th>
                        <th class="col-valor-faturado" style="padding: 0.65rem 0.75rem; text-align: right; color: #60a5fa;">FATURADO (R$)</th>
                        <th class="col-valor-pago" style="padding: 0.65rem 0.75rem; text-align: right; color: #34d399;">INVEST. (PAGO)</th>
                        <th class="col-valor-bruto" style="padding: 0.65rem 0.75rem; text-align: right; color: #38bdf8;">VALOR BRUTO</th>
                        <th class="col-investimento" style="padding: 0.65rem 0.75rem; text-align: right; color: #f87171;">INVEST. (FALTA)</th>
                        <th class="col-time-prod" style="padding: 0.65rem 0.75rem; text-align: center; width: 70px;">TIME PROD ✏️</th>
                        <th class="col-data-pa-pg" style="padding: 0.65rem 0.75rem; text-align: center;">PA (PG) ✏️</th>
                        <th class="col-data-pronto" style="padding: 0.65rem 0.75rem; text-align: center;">PREVISTO ✏️</th>
                        <th class="col-data-contratual" style="padding: 0.65rem 0.75rem; text-align: center;">DATA CONTRATUAL ✏️</th>
                        <th class="col-data-emissao" style="padding: 0.65rem 0.75rem; text-align: center;">DATA EMISSÃO ✏️</th>
                        <th class="col-data-boom" style="padding: 0.65rem 0.75rem; text-align: center;">DATA BOOM ✏️</th>
                        <th class="col-data-liberacao-estoque" style="padding: 0.65rem 0.75rem; text-align: center;">LIBERAÇÃO ESTQ. ✏️</th>
                        <th class="col-data-pronto-real" style="padding: 0.65rem 0.75rem; text-align: center; min-width: 120px; color: #a5b4fc;">DATA PRONTO ✏️</th>
                        <th class="col-updated-by" style="padding: 0.65rem 0.75rem; min-width: 140px; color: #a5b4fc;">Última Alteração</th>
                        <th class="col-acoes" style="padding: 0.65rem 0.75rem; text-align: center;">Ações</th>
                    </tr>

                    <!-- Linha de Filtros Múltiplos Estilo Excel no Cabeçalho -->
                    <tr class="filter-row" style="background-color: #1e293b; border-bottom: 2px solid var(--border-color);">
                        <!-- Filtro INFO -->
                        <th class="col-info" style="padding: 0.35rem 0.5rem;">
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
                        <th class="col-status-pv" style="padding: 0.35rem 0.5rem;">
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
                        <th class="col-fabrica" style="padding: 0.35rem 0.5rem;">
                            <div class="dropdown" style="position: relative; width: 100%;">
                                <button type="button" class="btn btn-secondary dropdown-toggle" id="btnFilterFabrica" onclick="toggleMenuFilterFabrica()" style="width: 100%; font-size: 0.7rem; padding: 0.2rem 0.35rem; justify-content: space-between; text-align: left; display: flex; align-items: center; background: #0f172a; border-color: #334155; height: 28px;">
                                    <span id="labelFabricaSelecionadas" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        @php $selFabArr = $fFabrica ? array_map('trim', explode(',', $fFabrica)) : []; @endphp
                                        {{ empty($selFabArr) ? 'Fábr.: Toda' : (count($selFabArr) == 1 ? $selFabArr[0] : count($selFabArr) . ' Selec.') }}
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

                        <th class="col-pv" style="padding: 0.35rem 0.5rem;">
                            <input type="text" name="search_pv" value="{{ $searchPv }}" class="form-control" placeholder="Multi: 006353, 006354..." form="formFilterPcpPainel" onchange="document.getElementById('formFilterPcpPainel').submit()" style="font-size: 0.7rem; padding: 0.2rem 0.35rem; height: 28px; background: #0f172a; border-color: #334155; color: #f8fafc;">
                        </th>
                        <th class="col-cliente" style="padding: 0.35rem 0.5rem;">
                            <input type="text" name="search_cliente" value="{{ $searchCliente }}" class="form-control" placeholder="Multi: ISA, COPEL..." form="formFilterPcpPainel" onchange="document.getElementById('formFilterPcpPainel').submit()" style="font-size: 0.7rem; padding: 0.2rem 0.35rem; height: 28px; background: #0f172a; border-color: #334155; color: #f8fafc;">
                        </th>
                        <th class="col-produto-pai"></th>

                        <!-- Filtro MARCA -->
                        <th class="col-marca" style="padding: 0.35rem 0.5rem;">
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

                        <th class="col-qtd"></th>
                        <th class="col-motor"></th>
                        <th class="col-alternador"></th>
                        <th class="col-base"></th>
                        <th class="col-carenagem"></th>
                        <th class="col-alertas"></th>
                        <th class="col-avanco"></th>
                        <th class="col-valor-pa"></th>
                        <th class="col-valor-faturado"></th>
                        <th class="col-valor-pago"></th>
                        <th class="col-valor-bruto"></th>
                        <th class="col-investimento"></th>
                        <th class="col-time-prod"></th>
                        <th class="col-data-pa-pg"></th>
                        <th class="col-data-pronto" style="padding: 0.35rem 0.5rem;">
                            <input type="text" name="f_data_pronto" value="{{ $fDataPronto }}" form="formFilterPcpPainel" class="form-control" placeholder="Previsto..." style="font-size: 0.7rem; padding: 0.2rem 0.35rem; height: 28px; text-align: center;" onchange="document.getElementById('formFilterPcpPainel').submit()">
                        </th>
                        <th class="col-data-contratual"></th>
                        <th class="col-data-emissao"></th>
                        <th class="col-data-boom"></th>
                        <th class="col-data-liberacao-estoque"></th>
                        <th class="col-data-pronto-real" style="padding: 0.35rem 0.5rem;">
                            <select id="table_f_data_pronto_mes" class="form-control" style="font-size: 0.7rem; padding: 0.2rem 0.35rem; height: 28px; background: #0f172a; border-color: #6366f1; color: #a5b4fc; font-weight: 600;" onchange="syncMonthFilter(this.value)">
                                <option value="">📅 Mês: Todos</option>
                                <option value="01" {{ request('f_data_pronto_mes') == '01' ? 'selected' : '' }}>01 - Jan</option>
                                <option value="02" {{ request('f_data_pronto_mes') == '02' ? 'selected' : '' }}>02 - Fev</option>
                                <option value="03" {{ request('f_data_pronto_mes') == '03' ? 'selected' : '' }}>03 - Mar</option>
                                <option value="04" {{ request('f_data_pronto_mes') == '04' ? 'selected' : '' }}>04 - Abr</option>
                                <option value="05" {{ request('f_data_pronto_mes') == '05' ? 'selected' : '' }}>05 - Mai</option>
                                <option value="06" {{ request('f_data_pronto_mes') == '06' ? 'selected' : '' }}>06 - Jun</option>
                                <option value="07" {{ request('f_data_pronto_mes') == '07' ? 'selected' : '' }}>07 - Jul</option>
                                <option value="08" {{ request('f_data_pronto_mes') == '08' ? 'selected' : '' }}>08 - Ago</option>
                                <option value="09" {{ request('f_data_pronto_mes') == '09' ? 'selected' : '' }}>09 - Set</option>
                                <option value="10" {{ request('f_data_pronto_mes') == '10' ? 'selected' : '' }}>10 - Out</option>
                                <option value="11" {{ request('f_data_pronto_mes') == '11' ? 'selected' : '' }}>11 - Nov</option>
                                <option value="12" {{ request('f_data_pronto_mes') == '12' ? 'selected' : '' }}>12 - Dez</option>
                            </select>
                        </th>
                        <th class="col-updated-by"></th>
                        <th class="col-acoes"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($painelData as $pvItem)
                    @php $pvKey = $pvItem['pv']; @endphp
                    <tr class="pv-row" style="border-bottom: 1px solid var(--border-color); transition: background 0.15s;">
                        <!-- Célula Editável INFO -->
                        <td class="col-info" style="padding: 0.5rem 0.6rem;">
                            <input type="text" name="pvs[{{ $pvKey }}][info]" value="{{ $pvItem['info'] }}" class="editable-cell-input" placeholder="Ex: CAR 55 KVA..." {{ !($canEditPcp ?? true) ? 'disabled style=opacity:0.6;cursor:not-allowed;' : '' }}>
                        </td>

                        <!-- Célula Editável STATUS PV -->
                        <td class="col-status-pv" style="padding: 0.5rem 0.6rem;">
                            <select name="pvs[{{ $pvKey }}][status_pv]" class="editable-cell-input" style="background-color: #0f172a; color: #f8fafc; {{ !($canEditPcp ?? true) ? 'opacity:0.6;cursor:not-allowed;' : '' }}" {{ !($canEditPcp ?? true) ? 'disabled' : '' }}>
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
                        <td class="col-fabrica" style="padding: 0.5rem 0.6rem;">
                            <input type="text" name="pvs[{{ $pvKey }}][fabrica]" value="{{ $pvItem['fabrica'] }}" class="editable-cell-input" placeholder="Ex: 99, 18..." {{ !($canEditPcp ?? true) ? 'disabled style=opacity:0.6;cursor:not-allowed;' : '' }}>
                        </td>

                        <!-- PV / Pedido -->
                        <td class="col-pv" style="padding: 0.65rem 0.75rem; font-weight: 700; color: #a5b4fc;">
                            {{ $pvItem['pv'] }}
                        </td>

                        <!-- Cliente -->
                        <td class="col-cliente" style="padding: 0.65rem 0.75rem; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $pvItem['cliente'] }}">
                            {{ $pvItem['cliente'] }}
                        </td>

                        <!-- Equipamento / Produto Pai -->
                        <td class="col-produto-pai" style="padding: 0.65rem 0.75rem; min-width: 300px; color: #c084fc; font-weight: 500; word-break: break-word;" title="{{ $pvItem['produto_pai'] }}">
                            {{ $pvItem['produto_pai'] }}
                        </td>

                        <!-- QTD -->
                        <td class="col-qtd" style="padding: 0.5rem 0.6rem; text-align: center;">
                            <input type="number" name="pvs[{{ $pvKey }}][qtd]" value="{{ $pvItem['qtd'] }}" class="editable-cell-input" style="width: 50px; text-align: center;" {{ !($canEditPcp ?? true) ? 'disabled style=opacity:0.6;cursor:not-allowed;' : '' }}>
                        </td>

                        <!-- Célula Editável MARCA -->
                        <td class="col-marca" style="padding: 0.5rem 0.6rem;">
                            <input type="text" name="pvs[{{ $pvKey }}][marca]" value="{{ $pvItem['marca'] }}" class="editable-cell-input" placeholder="Ex: PERKINS, SCANIA..." {{ !($canEditPcp ?? true) ? 'disabled style=opacity:0.6;cursor:not-allowed;' : '' }}>
                        </td>

                        <!-- Componentes Críticos -->
                        <td class="col-motor" style="padding: 0.65rem 0.75rem; text-align: center;">
                            @if($pvItem['motor_status'] === 'OK') <span class="badge-ok">✓ OK</span>
                            @elseif($pvItem['motor_status'] === '-') <span style="color: #64748b;">-</span>
                            @else <span class="badge-alert" title="Investimento Pendente">{{ $pvItem['motor_status'] }}</span>
                            @endif
                        </td>
                        <td class="col-alternador" style="padding: 0.65rem 0.75rem; text-align: center;">
                            @if($pvItem['alternador_status'] === 'OK') <span class="badge-ok">✓ OK</span>
                            @elseif($pvItem['alternador_status'] === '-') <span style="color: #64748b;">-</span>
                            @else <span class="badge-alert" title="Investimento Pendente">{{ $pvItem['alternador_status'] }}</span>
                            @endif
                        </td>
                        <td class="col-base" style="padding: 0.65rem 0.75rem; text-align: center;">
                            @if($pvItem['base_status'] === 'OK') <span class="badge-ok">✓ OK</span>
                            @elseif($pvItem['base_status'] === '-') <span style="color: #64748b;">-</span>
                            @else <span class="badge-pen" title="Investimento Pendente">{{ $pvItem['base_status'] }}</span>
                            @endif
                        </td>
                        <td class="col-carenagem" style="padding: 0.65rem 0.75rem; text-align: center;">
                            @if($pvItem['carenagem_status'] === 'OK') <span class="badge-ok">✓ OK</span>
                            @elseif($pvItem['carenagem_status'] === '-') <span style="color: #64748b;">-</span>
                            @else <span class="badge-pen" title="Investimento Pendente">{{ $pvItem['carenagem_status'] }}</span>
                            @endif
                        </td>

                        <!-- Alertas Compras -->
                        <td class="col-alertas" style="padding: 0.65rem 0.75rem; text-align: center;">
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

                        <!-- Avanço Separação -->
                        <td class="col-avanco" style="padding: 0.65rem 0.75rem; min-width: 135px;">
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

                        <!-- PA (R$) -->
                        <td class="col-valor-pa" style="padding: 0.65rem 0.75rem; text-align: right; font-weight: 600; color: #fbbf24;">
                            R$ {{ number_format($pvItem['valor_pa'], 2, ',', '.') }}
                        </td>

                        <!-- FATURADO (R$) -->
                        <td class="col-valor-faturado" style="padding: 0.65rem 0.75rem; text-align: right; font-weight: 600; color: #60a5fa;">
                            R$ {{ number_format($pvItem['valor_faturado'], 2, ',', '.') }}
                        </td>

                        <!-- INVESTIMENTO PAGO (R$) -->
                        <td class="col-valor-pago" style="padding: 0.65rem 0.75rem; text-align: right; font-weight: 700; color: #34d399;">
                            R$ {{ number_format($pvItem['valor_pago'], 2, ',', '.') }}
                        </td>

                        <!-- Valor Bruto -->
                        <td class="col-valor-bruto" style="padding: 0.5rem 0.6rem; text-align: right;">
                            <input type="text" name="pvs[{{ $pvKey }}][valor_bruto]" value="R$ {{ number_format($pvItem['valor_bruto'], 2, ',', '.') }}" class="editable-cell-input" style="text-align: right; font-weight: 700; color: #38bdf8;" {{ !($canEditPcp ?? true) ? 'disabled style=opacity:0.6;cursor:not-allowed;' : '' }}>
                        </td>

                        <!-- Investimento Pendente (Falta) -->
                        <td class="col-investimento" style="padding: 0.65rem 0.75rem; text-align: right; font-weight: 700; color: {{ $pvItem['investimento_pendente'] > 0 ? '#f87171' : '#34d399' }};">
                            R$ {{ number_format($pvItem['investimento_pendente'], 2, ',', '.') }}
                        </td>

                        <!-- TIME PROD -->
                        <td class="col-time-prod" style="padding: 0.5rem 0.6rem; text-align: center;">
                            <input type="text" name="pvs[{{ $pvKey }}][time_prod]" value="{{ $pvItem['time_prod'] }}" class="editable-cell-input" placeholder="Ex: 5" style="width: 50px; text-align: center;" {{ !($canEditPcp ?? true) ? 'disabled style=opacity:0.6;cursor:not-allowed;' : '' }}>
                        </td>

                        <!-- PA (PG) -->
                        <td class="col-data-pa-pg" style="padding: 0.5rem 0.6rem; text-align: center;">
                            <input type="text" name="pvs[{{ $pvKey }}][data_pa_pg]" value="{{ $pvItem['data_pa_pg'] }}" class="editable-cell-input" placeholder="Ex: 01/abr" style="width: 70px; text-align: center;" {{ !($canEditPcp ?? true) ? 'disabled style=opacity:0.6;cursor:not-allowed;' : '' }}>
                        </td>

                        <!-- PRONTO (Original) -->
                        <td class="col-data-pronto" style="padding: 0.5rem 0.6rem; text-align: center;">
                            <input type="text" name="pvs[{{ $pvKey }}][data_pronto]" value="{{ $pvItem['data_pronto'] }}" class="editable-cell-input" placeholder="Ex: 31/jul" style="width: 70px; text-align: center;" {{ !($canEditPcp ?? true) ? 'disabled style=opacity:0.6;cursor:not-allowed;' : '' }}>
                        </td>

                        <!-- DATA CONTRATUAL -->
                        <td class="col-data-contratual" style="padding: 0.5rem 0.6rem; text-align: center;">
                            <input type="text" name="pvs[{{ $pvKey }}][data_contratual]" value="{{ $pvItem['data_contratual'] }}" class="editable-cell-input" placeholder="DD/MM/AAAA" style="width: 90px; text-align: center;" {{ !($canEditPcp ?? true) ? 'disabled style=opacity:0.6;cursor:not-allowed;' : '' }}>
                        </td>

                        <!-- DATA EMISSÃO -->
                        <td class="col-data-emissao" style="padding: 0.5rem 0.6rem; text-align: center;">
                            <input type="text" name="pvs[{{ $pvKey }}][data_emissao]" value="{{ $pvItem['data_emissao'] }}" class="editable-cell-input" placeholder="DD/MM/AAAA" style="width: 90px; text-align: center;" {{ !($canEditPcp ?? true) ? 'disabled style=opacity:0.6;cursor:not-allowed;' : '' }}>
                        </td>

                        <!-- DATA BOOM -->
                        <td class="col-data-boom" style="padding: 0.5rem 0.6rem; text-align: center;">
                            <input type="text" name="pvs[{{ $pvKey }}][data_boom]" value="{{ $pvItem['data_boom'] }}" class="editable-cell-input" placeholder="DD/MM/AAAA" style="width: 90px; text-align: center;" {{ !($canEditPcp ?? true) ? 'disabled style=opacity:0.6;cursor:not-allowed;' : '' }}>
                        </td>

                        <!-- LIBERAÇÃO ESTOQUE -->
                        <td class="col-data-liberacao-estoque" style="padding: 0.5rem 0.6rem; text-align: center;">
                            <input type="text" name="pvs[{{ $pvKey }}][data_liberacao_estoque]" value="{{ $pvItem['data_liberacao_estoque'] }}" class="editable-cell-input" placeholder="DD/MM/AAAA" style="width: 90px; text-align: center;" {{ !($canEditPcp ?? true) ? 'disabled style=opacity:0.6;cursor:not-allowed;' : '' }}>
                        </td>

                        <!-- DATA PRONTO (Nova ao lado de Liberação Estoque) -->
                        <td class="col-data-pronto-real" style="padding: 0.5rem 0.6rem; text-align: center;">
                            <input type="text" name="pvs[{{ $pvKey }}][data_pronto_real]" value="{{ $pvItem['data_pronto_real'] }}" class="editable-cell-input" placeholder="Ex: 31/jul" style="width: 80px; text-align: center; border-color: #6366f1;" {{ !($canEditPcp ?? true) ? 'disabled style=opacity:0.6;cursor:not-allowed;' : '' }}>
                        </td>

                        <!-- Última Alteração -->
                        <td class="col-updated-by" style="padding: 0.5rem 0.6rem; font-size: 0.725rem; color: #cbd5e1;">
                            <strong style="color: #a5b4fc;">{{ $pvItem['updated_by'] ?? '-' }}</strong>
                            @if(isset($pvItem['updated_at']) && $pvItem['updated_at'] !== '-')
                                <br><span style="font-size: 0.65rem; color: #94a3b8;">📅 {{ $pvItem['updated_at'] }}</span>
                            @endif
                        </td>

                        <!-- Ações -->
                        <td class="col-acoes" style="padding: 0.65rem 0.75rem; text-align: center;">
                            <div style="display: flex; gap: 0.25rem; justify-content: center;">
                                @if($canEditPcp ?? true)
                                <button type="button" class="btn btn-secondary" onclick="abrirModalEditarPv('{{ $pvItem['pv'] }}', '{{ addslashes($pvItem['cliente']) }}', '{{ addslashes($pvItem['produto_pai']) }}', '{{ addslashes($pvItem['info']) }}', '{{ addslashes($pvItem['status_pv']) }}', '{{ addslashes($pvItem['fabrica']) }}', '{{ addslashes($pvItem['marca']) }}', '{{ $pvItem['qtd'] }}', '{{ addslashes($pvItem['time_prod']) }}', '{{ addslashes($pvItem['data_emissao']) }}', '{{ addslashes($pvItem['data_contratual']) }}', '{{ addslashes($pvItem['data_pa_pg']) }}', '{{ addslashes($pvItem['data_pronto']) }}', '{{ addslashes($pvItem['data_boom']) }}', '{{ addslashes($pvItem['data_liberacao_estoque']) }}', '{{ number_format($pvItem['valor_bruto'], 2, ',', '.') }}')" style="padding: 0.2rem 0.4rem; font-size: 0.7rem;" title="Editar dados deste PV">
                                    ✏️
                                </button>
                                @endif
                                <button type="button" class="btn btn-secondary" onclick="toggleDetails('pv_details_{{ $pvItem['pv'] }}')" style="padding: 0.2rem 0.4rem; font-size: 0.7rem;" title="Expandir componentes">
                                    👁️
                                </button>
                                <a href="{{ route('estoque.index', ['f_pedido' => $pvItem['pv']]) }}" class="btn btn-secondary" style="padding: 0.2rem 0.4rem; font-size: 0.7rem;" title="Filtrar no Estoque">
                                    📦
                                </a>
                                <a href="{{ route('compras.index', ['f_pv' => $pvItem['pv']]) }}" class="btn btn-primary" style="padding: 0.2rem 0.4rem; font-size: 0.7rem; background-color: #6366f1;" title="Filtrar em Compras">
                                    🛒
                                </a>
                                @if($canEditPcp ?? true)
                                <button type="button" class="btn btn-danger" onclick="confirmarExclusaoPv('{{ $pvItem['pv'] }}')" style="padding: 0.2rem 0.4rem; font-size: 0.7rem; background-color: #ef4444; border-color: #ef4444;" title="Excluir PV e componentes da base">
                                    🗑️
                                </button>
                                @endif
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

<!-- Paginação Customizada Dark Mode -->
@if($painelData->hasPages())
    <div style="margin-top: 1rem; display: flex; justify-content: space-between; align-items: center; background: #0f172a; padding: 0.75rem 1rem; border-radius: 0.5rem; border: 1px solid #334155; flex-wrap: wrap; gap: 0.5rem;">
        <div style="font-size: 0.8rem; color: #94a3b8;">
            Exibindo de <strong>{{ $painelData->firstItem() }}</strong> a <strong>{{ $painelData->lastItem() }}</strong> de <strong>{{ $painelData->total() }}</strong> Pedidos de Venda
        </div>
        <div>
            {{ $painelData->links() }}
        </div>
    </div>
@endif

<!-- Modal 1: Consultar Protheus e Selecionar PVs via Checkboxes -->
<div id="modalConsultarProtheus" class="modal-overlay">
    <div class="modal-box">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; padding-bottom: 0.75rem; margin-bottom: 1rem;">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: #a5b4fc; display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                🔍 Consultar Novos PVs no Protheus
            </h3>
            <button type="button" onclick="fecharModalConsultarProtheus()" style="background: none; border: none; color: #94a3b8; font-size: 1.25rem; cursor: pointer;">✕</button>
        </div>

        <div style="display: flex; gap: 0.75rem; margin-bottom: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 180px;">
                <label style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.3rem; display: block;">N° do Pedido de Venda (PV)</label>
                <input type="text" id="modal_search_pv_input" class="form-control" placeholder="Ex: 006883..." style="padding: 0.4rem 0.6rem; font-size: 0.85rem;">
            </div>
            <button type="button" class="btn btn-primary" onclick="executarBuscaProtheusModal()" style="padding: 0.45rem 1rem; font-size: 0.85rem; background-color: #6366f1;">
                🔍 Buscar no Protheus
            </button>
        </div>

        <!-- Área de Resultados da Consulta Protheus -->
        <div id="modal_protheus_results_area" style="display: none; border-top: 1px solid #334155; padding-top: 1rem; margin-top: 0.5rem;">
            <div style="font-size: 0.8rem; font-weight: 700; color: #34d399; margin-bottom: 0.75rem; display: flex; justify-content: space-between; align-items: center;">
                <span id="modal_protheus_count_label">0 Pedidos Encontrados</span>
                <label style="font-weight: 400; cursor: pointer; color: #a5b4fc; font-size: 0.75rem;">
                    <input type="checkbox" id="chk_select_all_modal_pvs" onchange="toggleSelectAllModalPvs(this)" checked> Marcar Todos
                </label>
            </div>

            <form action="{{ route('pcp-painel.importar-pvs') }}" method="POST" id="formImportarModalPvs" onsubmit="window.mostrarLoading('📥 Importando PVs selecionados...')">
                @csrf
                <input type="hidden" name="items_json" id="modal_import_items_json">
                
                <div id="modal_pvs_list_container" style="max-height: 280px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1rem;">
                    <!-- Preenchido via JavaScript -->
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.5rem; border-top: 1px solid #334155; padding-top: 0.75rem;">
                    <button type="button" class="btn btn-secondary" onclick="fecharModalConsultarProtheus()">Cancelar</button>
                    <button type="submit" class="btn btn-primary" style="background-color: #059669; border-color: #059669;">
                        📥 Importar PVs Selecionados
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Criar PV Manualmente -->
<div id="modalCriarPvManual" class="modal-overlay">
    <div class="modal-box" style="max-width: 550px;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; padding-bottom: 0.75rem; margin-bottom: 1rem;">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: #a5b4fc; display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                ✏️ Criar Pedido de Venda Manual
            </h3>
            <button type="button" onclick="fecharModalCriarPvManual()" style="background: none; border: none; color: #94a3b8; font-size: 1.25rem; cursor: pointer;">✕</button>
        </div>

        <form action="{{ route('pcp-painel.store-manual') }}" method="POST" onsubmit="window.mostrarLoading('✏️ Cadastrando PV manual...')">
            @csrf
            <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.25rem;">
                <div>
                    <label style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.25rem; display: block;">N° do Pedido de Venda (PV) *</label>
                    <input type="text" name="pedido" class="form-control" required placeholder="Ex: 006899 ou PV-MANUAL-01" style="padding: 0.45rem; font-size: 0.85rem;">
                </div>
                <div>
                    <label style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.25rem; display: block;">Nome do Cliente / Obra (C2_OBS)</label>
                    <input type="text" name="cliente_obs" class="form-control" placeholder="Ex: CONCESSIONARIA DA LINHA..." style="padding: 0.45rem; font-size: 0.85rem;">
                </div>
                <div>
                    <label style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.25rem; display: block;">Equipamento / Produto Pai</label>
                    <input type="text" name="produto_pai" class="form-control" placeholder="Ex: GMG MAQ450V DNQ BRN AB 46/26V" style="padding: 0.45rem; font-size: 0.85rem;">
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <div style="flex: 1;">
                        <label style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.25rem; display: block;">INFO</label>
                        <input type="text" name="info" class="form-control" placeholder="Ex: CAR 55 KVA 220" style="padding: 0.45rem; font-size: 0.85rem;">
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.25rem; display: block;">STATUS (PV)</label>
                        <select name="status_pv" class="form-control" style="padding: 0.45rem; font-size: 0.85rem;">
                            <option value="COMPRAS">COMPRAS</option>
                            <option value="FATURADO">FATURADO</option>
                            <option value="ENGENHARIA">ENGENHARIA</option>
                            <option value="ESTOQUE">ESTOQUE</option>
                            <option value="FINANCEIRO">FINANCEIRO</option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <div style="flex: 1;">
                        <label style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.25rem; display: block;">FÁBRICA</label>
                        <input type="text" name="fabrica" value="99" class="form-control" placeholder="Ex: 18, 19, 99" style="padding: 0.45rem; font-size: 0.85rem;">
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.25rem; display: block;">MARCA</label>
                        <input type="text" name="marca" class="form-control" placeholder="Ex: PERKINS, SCANIA" style="padding: 0.45rem; font-size: 0.85rem;">
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; border-top: 1px solid #334155; padding-top: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="fecharModalCriarPvManual()">Cancelar</button>
                <button type="submit" class="btn btn-primary" style="background-color: #6366f1;">
                    ✓ Confirmar e Salvar PV
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Form Escondido para Exclusão de PV -->
<form action="{{ route('pcp-painel.excluir-pv') }}" method="POST" id="formExcluirPvModal" onsubmit="window.mostrarLoading('🗑️ Excluindo Pedido de Venda e componentes...')">
    @csrf
    <input type="hidden" name="pedido" id="input_excluir_pv_num">
</form>

<!-- Modal 3: Editar PV Individualmente -->
<div id="modalEditarPv" class="modal-overlay">
    <div class="modal-box" style="max-width: 550px;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; padding-bottom: 0.75rem; margin-bottom: 1rem;">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: #a5b4fc; display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                ✏️ Editar Pedido de Venda <span id="modal_edit_pv_title"></span>
            </h3>
            <button type="button" onclick="fecharModalEditarPv()" style="background: none; border: none; color: #94a3b8; font-size: 1.25rem; cursor: pointer;">✕</button>
        </div>

        <form action="{{ route('pcp-painel.update-single-pv') }}" method="POST" onsubmit="window.mostrarLoading('💾 Salvando alterações do PV...')">
            @csrf
            <input type="hidden" name="pedido" id="modal_edit_pedido_input">

            <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.25rem;">
                <div>
                    <label style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.25rem; display: block;">Nome do Cliente / Obra (C2_OBS)</label>
                    <input type="text" name="cliente_obs" id="modal_edit_cliente_input" class="form-control" style="padding: 0.45rem; font-size: 0.85rem;">
                </div>
                <div>
                    <label style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.25rem; display: block;">Equipamento / Produto Pai</label>
                    <input type="text" name="produto_pai" id="modal_edit_produto_pai_input" class="form-control" style="padding: 0.45rem; font-size: 0.85rem;">
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <div style="flex: 1;">
                        <label style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.25rem; display: block;">INFO</label>
                        <input type="text" name="info" id="modal_edit_info_input" class="form-control" style="padding: 0.45rem; font-size: 0.85rem;">
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.25rem; display: block;">STATUS (PV)</label>
                        <select name="status_pv" id="modal_edit_status_pv_input" class="form-control" style="padding: 0.45rem; font-size: 0.85rem;">
                            <option value="">-- Selecione --</option>
                            <option value="FATURADO">FATURADO</option>
                            <option value="COMPRAS">COMPRAS</option>
                            <option value="ENGENHARIA">ENGENHARIA</option>
                            <option value="ESTOQUE">ESTOQUE</option>
                            <option value="FINANCEIRO">FINANCEIRO</option>
                            <option value="ENTREGUE">ENTREGUE</option>
                            <option value="CANCELADO">CANCELADO</option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <div style="flex: 1;">
                        <label style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.25rem; display: block;">FÁBRICA</label>
                        <input type="text" name="fabrica" id="modal_edit_fabrica_input" class="form-control" style="padding: 0.45rem; font-size: 0.85rem;">
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.25rem; display: block;">MARCA</label>
                        <input type="text" name="marca" id="modal_edit_marca_input" class="form-control" style="padding: 0.45rem; font-size: 0.85rem;">
                    </div>
                </div>

                <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                    <div style="flex: 1;">
                        <label style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.25rem; display: block;">QTD</label>
                        <input type="number" name="qtd" id="modal_edit_qtd_input" class="form-control" style="padding: 0.45rem; font-size: 0.85rem;">
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 0.75rem; font-weight: 600; color: #38bdf8; margin-bottom: 0.25rem; display: block;">VALOR BRUTO (R$)</label>
                        <input type="text" name="valor_bruto" id="modal_edit_valor_bruto_input" class="form-control" style="padding: 0.45rem; font-size: 0.85rem; color: #38bdf8; font-weight: 700;">
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.25rem; display: block;">TIME PROD</label>
                        <input type="text" name="time_prod" id="modal_edit_time_prod_input" class="form-control" style="padding: 0.45rem; font-size: 0.85rem;">
                    </div>
                </div>

                <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                    <div style="flex: 1;">
                        <label style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.25rem; display: block;">PA (PG)</label>
                        <input type="text" name="data_pa_pg" id="modal_edit_data_pa_pg_input" class="form-control" style="padding: 0.45rem; font-size: 0.85rem;">
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.25rem; display: block;">PRONTO</label>
                        <input type="text" name="data_pronto" id="modal_edit_data_pronto_input" class="form-control" style="padding: 0.45rem; font-size: 0.85rem;">
                    </div>
                </div>

                <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                    <div style="flex: 1;">
                        <label style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.25rem; display: block;">DATA CONTRATUAL</label>
                        <input type="text" name="data_contratual" id="modal_edit_data_contratual_input" class="form-control" style="padding: 0.45rem; font-size: 0.85rem;">
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.25rem; display: block;">DATA EMISSÃO</label>
                        <input type="text" name="data_emissao" id="modal_edit_data_emissao_input" class="form-control" style="padding: 0.45rem; font-size: 0.85rem;">
                    </div>
                </div>

                <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                    <div style="flex: 1;">
                        <label style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.25rem; display: block;">DATA BOOM</label>
                        <input type="text" name="data_boom" id="modal_edit_data_boom_input" class="form-control" style="padding: 0.45rem; font-size: 0.85rem;">
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 0.75rem; font-weight: 600; color: #94a3b8; margin-bottom: 0.25rem; display: block;">LIBERAÇÃO ESTOQUE</label>
                        <input type="text" name="data_liberacao_estoque" id="modal_edit_data_liberacao_estoque_input" class="form-control" style="padding: 0.45rem; font-size: 0.85rem;">
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; border-top: 1px solid #334155; padding-top: 0.75rem; margin-top: 1rem;">
                <button type="button" class="btn btn-secondary" onclick="fecharModalEditarPv()">Cancelar</button>
                <button type="submit" class="btn btn-primary" style="background-color: #059669; border-color: #059669;">
                    💾 Salvar Alterações do PV
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleDetails(elementId) {
        const el = document.getElementById(elementId);
        if (el) {
            el.style.display = el.style.display === 'none' ? 'table-row' : 'none';
        }
    }

    function confirmarExclusaoPv(pvNum) {
        if (confirm('⚠️ Tem certeza que deseja EXCLUIR permanentemente o Pedido de Venda ' + pvNum + ' e todas as suas matérias-primas da base de dados?')) {
            document.getElementById('input_excluir_pv_num').value = pvNum;
            document.getElementById('formExcluirPvModal').submit();
        }
    }

    // Modal Consulta Protheus
    let modalProtheusItemsRaw = [];
    let modalProtheusPvsMap = {};

    function abrirModalConsultarProtheus() {
        document.getElementById('modalConsultarProtheus').style.display = 'flex';
    }
    function fecharModalConsultarProtheus() {
        document.getElementById('modalConsultarProtheus').style.display = 'none';
    }

    function abrirModalCriarPvManual() {
        document.getElementById('modalCriarPvManual').style.display = 'flex';
    }
    function fecharModalCriarPvManual() {
        document.getElementById('modalCriarPvManual').style.display = 'none';
    }

    function abrirModalEditarPv(pv, cliente, prodPai, info, statusPv, fabrica, marca, qtd, timeProd, dataEmissao, dataContratual, dataPaPg, dataPronto, dataBoom, dataLiberacaoEstoque, valorBruto) {
        document.getElementById('modal_edit_pv_title').innerText = pv;
        document.getElementById('modal_edit_pedido_input').value = pv;
        document.getElementById('modal_edit_cliente_input').value = cliente;
        document.getElementById('modal_edit_produto_pai_input').value = prodPai;
        document.getElementById('modal_edit_info_input').value = info;
        document.getElementById('modal_edit_status_pv_input').value = statusPv;
        document.getElementById('modal_edit_fabrica_input').value = fabrica;
        document.getElementById('modal_edit_marca_input').value = marca;
        document.getElementById('modal_edit_qtd_input').value = qtd || 1;
        document.getElementById('modal_edit_valor_bruto_input').value = valorBruto ? 'R$ ' + valorBruto : '';
        document.getElementById('modal_edit_time_prod_input').value = timeProd || '0';
        document.getElementById('modal_edit_data_emissao_input').value = dataEmissao || '-';
        document.getElementById('modal_edit_data_contratual_input').value = dataContratual || '-';
        document.getElementById('modal_edit_data_pa_pg_input').value = dataPaPg || '-';
        document.getElementById('modal_edit_data_pronto_input').value = dataPronto || '-';
        document.getElementById('modal_edit_data_boom_input').value = dataBoom || '-';
        document.getElementById('modal_edit_data_liberacao_estoque_input').value = dataLiberacaoEstoque || '-';
        document.getElementById('modalEditarPv').style.display = 'flex';
    }
    function fecharModalEditarPv() {
        document.getElementById('modalEditarPv').style.display = 'none';
    }

    function executarBuscaProtheusModal() {
        const pvInput = document.getElementById('modal_search_pv_input').value.trim();
        if (!pvInput) {
            alert('Informe o número do Pedido de Venda para consultar.');
            return;
        }

        window.mostrarLoading('🔍 Buscando novos PVs no Protheus...');

        fetch('{{ route("pcp-painel.consultar-protheus") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ pedido: pvInput })
        })
        .then(res => res.json())
        .then(data => {
            window.esconderLoading();
            if (!data.success) {
                alert(data.message || 'Nenhum PV encontrado.');
                return;
            }

            modalProtheusPvsMap = {};
            data.pvs.forEach(pvObj => {
                modalProtheusPvsMap[pvObj.pv] = pvObj;
            });

            renderModalPvsList(data.pvs);
        })
        .catch(err => {
            window.esconderLoading();
            alert('Erro ao consultar o Protheus. Tente novamente.');
        });
    }

    function renderModalPvsList(pvs) {
        const container = document.getElementById('modal_pvs_list_container');
        const countLabel = document.getElementById('modal_protheus_count_label');
        const area = document.getElementById('modal_protheus_results_area');
        
        container.innerHTML = '';
        countLabel.innerText = pvs.length + ' Pedido(s) Encontrado(s) (' + pvs.reduce((acc, p) => acc + p.count, 0) + ' componentes)';
        area.style.display = 'block';

        pvs.forEach(pvObj => {
            const cardHtml = `
                <div style="background: #1e293b; border: 1px solid #334155; border-radius: 0.5rem; padding: 0.6rem 0.8rem; display: flex; align-items: center; justify-content: space-between;">
                    <label style="display: flex; align-items: center; gap: 0.6rem; font-size: 0.825rem; font-weight: 600; cursor: pointer; color: #f8fafc; margin: 0;">
                        <input type="checkbox" class="chk-modal-pv-item" value="${pvObj.pv}" checked onchange="atualizarModalImportJson()">
                        <span style="color: #a5b4fc;">PV ${pvObj.pv}</span> - <span style="color: #cbd5e1;">${pvObj.cliente}</span>
                    </label>
                    <span style="font-size: 0.725rem; color: #34d399; font-weight: 700; background: rgba(16,185,129,0.15); padding: 0.15rem 0.4rem; border-radius: 0.2rem;">
                        ${pvObj.count} componentes
                    </span>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', cardHtml);
        });

        atualizarModalImportJson();
    }

    function toggleSelectAllModalPvs(masterChk) {
        document.querySelectorAll('.chk-modal-pv-item').forEach(c => c.checked = masterChk.checked);
        atualizarModalImportJson();
    }

    function atualizarModalImportJson() {
        const selectedPvNums = Array.from(document.querySelectorAll('.chk-modal-pv-item:checked')).map(c => c.value);
        let itemsToImport = [];

        selectedPvNums.forEach(pvNum => {
            if (modalProtheusPvsMap[pvNum] && modalProtheusPvsMap[pvNum].items) {
                itemsToImport = itemsToImport.concat(modalProtheusPvsMap[pvNum].items);
            }
        });

        document.getElementById('modal_import_items_json').value = JSON.stringify(itemsToImport);
    }

    // Gestor de Visibilidade de Colunas (Excel) do Painel PCP
    const PAINEL_PCP_COLUNAS_PADRAO = {
        'col-info': true,
        'col-status-pv': true,
        'col-fabrica': true,
        'col-pv': true,
        'col-cliente': true,
        'col-produto-pai': true,
        'col-qtd': true,
        'col-marca': true,
        'col-motor': true,
        'col-alternador': true,
        'col-base': true,
        'col-carenagem': true,
        'col-alertas': true,
        'col-avanco': true,
        'col-valor-pa': true,
        'col-valor-faturado': true,
        'col-valor-pago': true,
        'col-valor-bruto': true,
        'col-investimento': true,
        'col-time-prod': true,
        'col-data-pa-pg': true,
        'col-data-pronto': true,
        'col-data-contratual': true,
        'col-data-emissao': true,
        'col-data-boom': true,
        'col-data-liberacao-estoque': true,
        'col-data-pronto-real': true
    };

    function toggleMenuColunasPainelPcp() {
        const el = document.getElementById('dropdownMenuColunasPainelPcp');
        if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
    }

    function toggleColunaPainelPcp(colClass, isChecked) {
        const elements = document.querySelectorAll('.' + colClass);
        elements.forEach(el => {
            el.style.display = isChecked ? '' : 'none';
        });
        let prefs = JSON.parse(localStorage.getItem('painel_pcp_colunas_visiveis') || '{}');
        prefs[colClass] = isChecked;
        localStorage.setItem('painel_pcp_colunas_visiveis', JSON.stringify(prefs));
    }

    function inicializarColunasPainelPcp() {
        let prefs = JSON.parse(localStorage.getItem('painel_pcp_colunas_visiveis') || '{}');
        Object.keys(PAINEL_PCP_COLUNAS_PADRAO).forEach(colClass => {
            let isChecked = prefs.hasOwnProperty(colClass) ? prefs[colClass] : PAINEL_PCP_COLUNAS_PADRAO[colClass];
            const chk = document.getElementById('chk_' + colClass);
            if (chk) chk.checked = isChecked;
            toggleColunaPainelPcp(colClass, isChecked);
        });
    }

    document.addEventListener('DOMContentLoaded', inicializarColunasPainelPcp);

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

    function syncMonthFilter(val) {
        const topSel = document.getElementById('top_f_data_pronto_mes');
        const tableSel = document.getElementById('table_f_data_pronto_mes');
        if (topSel) topSel.value = val;
        if (tableSel) tableSel.value = val;
        document.getElementById('formFilterPcpPainel').submit();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const reqMes = "{{ request('f_data_pronto_mes') }}";
        if (reqMes) {
            const topSel = document.getElementById('top_f_data_pronto_mes');
            const tableSel = document.getElementById('table_f_data_pronto_mes');
            if (topSel) topSel.value = reqMes;
            if (tableSel) tableSel.value = reqMes;
        }
    });

    // Fechar dropdowns ao clicar fora
    document.addEventListener('click', function(e) {
        ['Info', 'StatusPv', 'Fabrica', 'Marca'].forEach(name => {
            const menu = document.getElementById('dropdownMenuFilter' + name);
            const btn = document.getElementById('btnFilter' + name);
            if (menu && btn && !menu.contains(e.target) && !btn.contains(e.target)) {
                menu.style.display = 'none';
            }
        });
        const menuCol = document.getElementById('dropdownMenuColunasPainelPcp');
        if (menuCol && !menuCol.contains(e.target) && !e.target.closest('.dropdown')) {
            menuCol.style.display = 'none';
        }
    });
</script>
@endsection
