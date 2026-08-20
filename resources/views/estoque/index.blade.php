@extends('layouts.app')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700;">📦 Painel de Estoque (PCP)</h1>
        <p style="color: var(--text-muted); font-size: 0.8rem;">Gerenciamento de demanda do estoque local integrado ao Protheus.</p>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <button class="btn btn-primary" onclick="abrirModalConsultaProtheus()">
            🔍 Consultar Pedido no Protheus
        </button>
        <button class="btn btn-secondary" onclick="document.getElementById('modalAddManual').style.display='block'">
            + Adicionar Manual
        </button>
    </div>
</div>

<!-- Modal 1: Consulta Multi-Itens no Protheus -->
<div class="card" id="modalConsultaProtheus" style="display: none; border-color: rgba(99, 102, 241, 0.5); max-width: 1200px; margin: 0 auto 1.25rem auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
        <h3 style="font-size: 1rem; color: #a5b4fc;">🔍 Consultar Itens do Pedido no Protheus (SC2010)</h3>
        <button type="button" class="btn btn-secondary" style="padding: 0.2rem 0.5rem;" onclick="document.getElementById('modalConsultaProtheus').style.display='none'">✕</button>
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 0.85rem; margin-bottom: 1.25rem; align-items: flex-end;">
        <div class="form-group" style="margin-bottom: 0; min-width: 150px; flex: 1;">
            <label class="form-label">Filial (C2_FILIAL)</label>
            <select id="consulta_filial" class="form-select">
                <option value="">-- Todas Filiais --</option>
                @foreach($filiaisProtheus as $fil)
                    <option value="{{ $fil }}" {{ $fil == '22' ? 'selected' : '' }}>Filial {{ $fil }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0; min-width: 250px; flex: 3;">
            <label class="form-label">Número do Pedido (C2_PEDIDO) *</label>
            <input type="text" id="consulta_pedido" class="form-control" placeholder="Ex: 006614">
        </div>

        <div style="min-width: 120px; flex: 1;">
            <button type="button" class="btn btn-primary" style="width: 100%; justify-content: center;" onclick="buscarItensProtheus()">
                🔍 Listar Itens
            </button>
        </div>
    </div>

    <div id="resultado_status_label" style="margin-bottom: 0.85rem; font-size: 0.8rem; font-weight: 500;"></div>

    <form action="{{ route('estoque.store-batch') }}" method="POST" id="formImportBatch" style="display: none;" onsubmit="window.mostrarLoading('📥 Importando selecionados para o Estoque... Aguarde...')">
        @csrf
        <input type="hidden" name="items_json" id="input_items_json">

        <div class="table-responsive" style="max-height: 400px; overflow-y: auto; margin-bottom: 1rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
            <table>
                <thead style="position: sticky; top: 0; z-index: 10;">
                    <tr>
                        <th style="width: 35px; text-align: center;">
                            <input type="checkbox" id="checkAll" onchange="toggleAllCheckboxes(this)">
                        </th>
                        <th>Filial</th>
                        <th>Pedido</th>
                        <th>OP</th>
                        <th>Código Produto</th>
                        <th>Descrição</th>
                        <th class="col-desc-longa" style="display: none; color: #38bdf8;">Descrição Longa (SB5010)</th>
                        <th class="col-produto-pai" style="display: none; color: #c084fc;">Produto Pai Concatenado</th>
                        <th>Qtd OP</th>
                        <th>Nome do Cliente (C2_OBS)</th>
                    </tr>
                </thead>
                <tbody id="tbody_protheus_items"></tbody>
            </table>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
            <span id="label_selecionados_count" style="font-size: 0.8rem; color: var(--text-muted);">0 item(ns) selecionado(s)</span>
            <div style="display: flex; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalConsultaProtheus').style.display='none'">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnImportSelected">
                    📥 Importar Selecionados para o Estoque (MySQL)
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Modal 2: Inserção Manual de Item -->
<div class="card" id="modalAddManual" style="display: none; border-color: rgba(99, 102, 241, 0.4);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem;">
        <h3 style="font-size: 1rem;">Adicionar Item Manualmente</h3>
        <button type="button" class="btn btn-secondary" style="padding: 0.2rem 0.5rem;" onclick="document.getElementById('modalAddManual').style.display='none'">✕</button>
    </div>
    <form action="{{ route('estoque.store') }}" method="POST" onsubmit="window.mostrarLoading('📦 Adicionando item ao estoque... Aguarde...')">
        @csrf
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.85rem;">
            <div class="form-group">
                <label class="form-label">Código do Produto *</label>
                <input type="text" name="codigo_produto" class="form-control" placeholder="Ex: PROD-1001" required>
            </div>
            <div class="form-group">
                <label class="form-label">Descrição Curta (B1_DESC)</label>
                <input type="text" name="descricao" class="form-control" placeholder="Descrição do componente">
            </div>
            <div class="form-group">
                <label class="form-label">Descrição Longa (B5_CEME)</label>
                <input type="text" name="descricao_longa" class="form-control" placeholder="Descrição longa detalhada da SB5010">
            </div>
            <div class="form-group">
                <label class="form-label">Produto Pai Concatenado</label>
                <input type="text" name="produto_pai" class="form-control" placeholder="Ex: 951010010 - QUADRO QTA">
            </div>
            <div class="form-group">
                <label class="form-label">Ordem de Produção (OP)</label>
                <input type="text" name="op" class="form-control" placeholder="Ex: OP-01234">
            </div>
            <div class="form-group">
                <label class="form-label">Pedido de Venda (C2_PEDIDO)</label>
                <input type="text" name="pedido" class="form-control" placeholder="Ex: 006614">
            </div>
            <div class="form-group">
                <label class="form-label">Nome do Cliente (C2_OBS)</label>
                <input type="text" name="cliente_obs" class="form-control" placeholder="Ex: CLIENTE EXEMPLO">
            </div>
            <div class="form-group">
                <label class="form-label">Qtd Requisitada da OP *</label>
                <input type="number" step="0.01" name="quantidade" class="form-control" value="1" required>
            </div>
            <div class="form-group">
                <label class="form-label">Qtd Já Disponível em Estoque</label>
                <input type="number" step="0.01" name="quantidade_estoque" class="form-control" value="0">
            </div>
            <div class="form-group">
                <label class="form-label">Status PCP *</label>
                <select name="status" class="form-select" required>
                    <option value="FALTA">FALTA</option>
                    <option value="SEPARADO">SEPARADO</option>
                    <option value="RETIRADO">RETIRADO</option>
                    <option value="FABRICA">FABRICA</option>
                    <option value="FABRICAR INTERNO KANBAN">FABRICAR INTERNO KANBAN</option>
                </select>
            </div>
        </div>
        <div class="form-group" style="margin-top: 0.5rem;">
            <label class="form-label">Observação Interna do Estoque</label>
            <textarea name="observacao_estoque" class="form-control" rows="2" placeholder="Observações do almoxarifado..."></textarea>
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 0.85rem;">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalAddManual').style.display='none'">Cancelar</button>
            <button type="submit" class="btn btn-primary">Salvar no MySQL</button>
        </div>
    </form>
</div>

<!-- Modal 3: Modal de Confirmação de Alteração -->
<div class="card" id="modalConfirmacaoSave" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10000; min-width: 360px; max-width: 450px; border-color: rgba(99, 102, 241, 0.8); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.7);">
    <h3 style="font-size: 1.1rem; color: #a5b4fc; margin-bottom: 0.75rem;">⚠️ Confirmar Alterações do Item</h3>
    <div style="font-size: 0.85rem; color: #cbd5e1; margin-bottom: 0.75rem;" id="confirmTextDetails">
        Confirma a atualização deste item?
    </div>
    <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
        <button type="button" class="btn btn-secondary" onclick="fecharModalConfirmacao()">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnConfirmarSaveAction">✅ Sim, Salvar</button>
    </div>
</div>
<div id="modalOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.6); z-index: 9999;" onclick="fecharModalConfirmacao()"></div>

<!-- Formulário Oculto de Filtros por Colunas -->
<form action="{{ route('estoque.index') }}" method="GET" id="formFilterEstoque" onsubmit="window.mostrarLoading('🔍 Filtrando itens de estoque...')"></form>

<!-- Tabela de Itens de Estoque Salvos no MySQL -->
<div class="card">
    <form action="{{ route('estoque.update-batch') }}" method="POST" id="formBatchEstoque" onsubmit="window.mostrarLoading('💾 Salvando todas as alterações do Estoque... Aguarde...')">
        @csrf
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; flex-wrap: wrap; gap: 0.5rem;">
            <h3 style="font-size: 1rem;">📋 Itens Cadastrados no Estoque Local (MySQL)</h3>
            <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                <!-- Quadradinho Toggle para Exibir/Ocultar Coluna Descrição Longa -->
                <button type="button" class="btn btn-secondary" onclick="toggleColunaDescricaoLonga()" style="padding: 0.35rem 0.65rem; font-size: 0.75rem; border-color: rgba(56, 189, 248, 0.5); font-weight: 500;">
                    <span id="iconSquareDescLonga">🔲</span> Descrição Longa (SB5010)
                </button>

                <!-- Quadradinho Toggle para Exibir/Ocultar Coluna Produto Pai -->
                <button type="button" class="btn btn-secondary" onclick="toggleColunaProdutoPai()" style="padding: 0.35rem 0.65rem; font-size: 0.75rem; border-color: rgba(168, 85, 247, 0.5); font-weight: 500;">
                    <span id="iconSquarePai">🔲</span> Produto Pai Concatenado
                </button>

                @if(request()->hasAny(['f_pedido', 'f_produto', 'f_descricao', 'f_desc_longa', 'f_prod_pai', 'f_op', 'f_status', 'f_cliente']))
                    <a href="{{ route('estoque.index') }}" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" onclick="window.mostrarLoading('⏳ Limpando filtros...')">
                        ✕ Limpar Filtros
                    </a>
                @endif
                <button type="submit" class="btn btn-primary" style="padding: 0.4rem 0.85rem; font-size: 0.8rem; background-color: #059669;" onclick="return confirm('Deseja salvar todas as alterações editadas nesta página?')">
                    💾 Salvar Todas as Alterações da Página
                </button>
            </div>
        </div>

        <div class="table-responsive" style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th style="min-width: 130px;">Status PCP Atual</th>
                        <th>Pedido (C2_PEDIDO)</th>
                        <th>Código Produto</th>
                        <th>Descrição</th>
                        <th class="col-desc-longa" style="display: none; color: #38bdf8;">Descrição Longa (B5_CEME - SB5010)</th>
                        <th class="col-produto-pai" style="display: none; color: #c084fc;">Código / Produto Pai Concatenado</th>
                        <th>OP</th>
                        <th style="text-align: center;">Qtd OP</th>
                        <th style="color: #fcd34d; text-align: center;">Qtd Estoque ✏️</th>
                        <th style="color: #6ee7b7; text-align: center;">Qtd a Comprar</th>
                        <th>Nome do Cliente (C2_OBS)</th>
                        <th style="color: #38bdf8;">Observação Estoque ✏️</th>
                        <th>Alterar Status PCP ✏️</th>
                        <th style="text-align: center; color: #a5b4fc;">Ações</th>
                    </tr>
                    <tr class="filter-row">
                        <th>
                            <input type="text" name="f_status" value="{{ request('f_status') }}" class="filter-input" placeholder="Multi: FALTA, RETIRADO..." form="formFilterEstoque" onchange="document.getElementById('formFilterEstoque').submit()">
                        </th>
                        <th>
                            <input type="text" name="f_pedido" value="{{ request('f_pedido') }}" class="filter-input" placeholder="Multi: 0066, 0067..." form="formFilterEstoque" onchange="document.getElementById('formFilterEstoque').submit()">
                        </th>
                        <th>
                            <input type="text" name="f_produto" value="{{ request('f_produto') }}" class="filter-input" placeholder="Multi: 6164, 1050..." form="formFilterEstoque" onchange="document.getElementById('formFilterEstoque').submit()">
                        </th>
                        <th>
                            <input type="text" name="f_descricao" value="{{ request('f_descricao') }}" class="filter-input" placeholder="Multi: CABO, CHAVE..." form="formFilterEstoque" onchange="document.getElementById('formFilterEstoque').submit()">
                        </th>
                        <th class="col-desc-longa" style="display: none;">
                            <input type="text" name="f_desc_longa" value="{{ request('f_desc_longa') }}" class="filter-input" placeholder="Multi: FLEXIVEL, ISOLADO..." form="formFilterEstoque" onchange="document.getElementById('formFilterEstoque').submit()">
                        </th>
                        <th class="col-produto-pai" style="display: none;">
                            <input type="text" name="f_prod_pai" value="{{ request('f_prod_pai') }}" class="filter-input" placeholder="Multi: QUADRO, 9510..." form="formFilterEstoque" onchange="document.getElementById('formFilterEstoque').submit()">
                        </th>
                        <th>
                            <input type="text" name="f_op" value="{{ request('f_op') }}" class="filter-input" placeholder="Multi: 0187, 0188..." form="formFilterEstoque" onchange="document.getElementById('formFilterEstoque').submit()">
                        </th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th>
                            <input type="text" name="f_cliente" value="{{ request('f_cliente') }}" class="filter-input" placeholder="Multi: CLIENTE A, B..." form="formFilterEstoque" onchange="document.getElementById('formFilterEstoque').submit()">
                        </th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                    <tr id="row_estoque_{{ $item->id }}">
                        <td>
                            @php
                                $badgeClass = match($item->status) {
                                    'FALTA' => 'badge-falta',
                                    'SEPARADO' => 'badge-separado',
                                    'RETIRADO' => 'badge-retirado',
                                    'FABRICA' => 'badge-fabrica',
                                    'FABRICAR INTERNO KANBAN' => 'badge-kanban',
                                    default => 'badge-falta'
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}" id="badge_status_{{ $item->id }}">{{ $item->status }}</span>
                        </td>
                        <td><strong>{{ $item->pedido ?? '-' }}</strong></td>
                        <td><strong>{{ $item->codigo_produto }}</strong></td>
                        <td style="font-size: 0.775rem;">{{ $item->descricao ?? '-' }}</td>
                        <td class="col-desc-longa" style="display: none; font-size: 0.75rem; color: #38bdf8;">
                            <span style="background: rgba(56, 189, 248, 0.12); padding: 0.15rem 0.35rem; border-radius: 0.25rem; border: 1px solid rgba(56, 189, 248, 0.3);">{{ $item->descricao_longa ?? ($item->descricao ?? '-') }}</span>
                        </td>
                        <td class="col-produto-pai" style="display: none; font-size: 0.75rem; color: #c084fc;">
                            <code style="background: rgba(168, 85, 247, 0.15); padding: 0.15rem 0.35rem; border-radius: 0.25rem; border: 1px solid rgba(168, 85, 247, 0.3);">{{ $item->produto_pai ?? '-' }}</code>
                        </td>
                        <td><code style="color: var(--accent); font-size: 0.75rem;">{{ $item->op ?? '-' }}</code></td>
                        <td style="text-align: center;">
                            <strong id="qtd_op_{{ $item->id }}" style="color: #38bdf8; font-size: 0.85rem;">{{ floatval($item->quantidade) }}</strong>
                        </td>
                        <td style="text-align: center;">
                            @php
                                $valQtdEstoque = floatval($item->quantidade_estoque);
                            @endphp
                            <input type="number" 
                                   step="0.01" 
                                   name="items[{{ $item->id }}][quantidade_estoque]"
                                   id="input_qtd_est_{{ $item->id }}"
                                   value="{{ $valQtdEstoque }}" 
                                   class="form-control" 
                                   style="width: 100px; text-align: center; margin: 0 auto; padding: 0.2rem 0.4rem; font-weight: 600; color: #fcd34d;"
                                   onchange="recalcularLinhaEstoque({{ $item->id }})">
                        </td>
                        <td style="text-align: center;">
                            @php
                                $valQtdComprar = floatval($item->quantidade_comprar);
                            @endphp
                            <span id="label_qtd_comprar_{{ $item->id }}" 
                                  style="font-weight: 700; font-size: 0.85rem; color: {{ $valQtdComprar > 0 ? '#ef4444' : '#10b981' }};">
                                {{ $valQtdComprar }}
                            </span>
                        </td>
                        <td style="font-size: 0.775rem;">{{ $item->cliente_obs ?? '-' }}</td>
                        <td>
                            <input type="text" 
                                   name="items[{{ $item->id }}][observacao_estoque]" 
                                   value="{{ $item->observacao_estoque }}" 
                                   class="form-control" 
                                   placeholder="Observação..."
                                   style="min-width: 140px; padding: 0.2rem 0.4rem; font-size: 0.75rem;">
                        </td>
                        <td>
                            <select name="items[{{ $item->id }}][status]" 
                                    id="select_status_{{ $item->id }}"
                                    class="form-select" 
                                    style="padding: 0.2rem 0.4rem; font-size: 0.75rem; min-width: 140px;"
                                    onchange="atualizarBadgeStatus({{ $item->id }})">
                                <option value="FALTA" {{ $item->status == 'FALTA' ? 'selected' : '' }}>FALTA</option>
                                <option value="SEPARADO" {{ $item->status == 'SEPARADO' ? 'selected' : '' }}>SEPARADO</option>
                                <option value="RETIRADO" {{ $item->status == 'RETIRADO' ? 'selected' : '' }}>RETIRADO</option>
                                <option value="FABRICA" {{ $item->status == 'FABRICA' ? 'selected' : '' }}>FABRICA</option>
                                <option value="FABRICAR INTERNO KANBAN" {{ $item->status == 'FABRICAR INTERNO KANBAN' ? 'selected' : '' }}>KANBAN</option>
                            </select>
                        </td>
                        <td style="text-align: center;">
                            <button type="button" 
                                    class="btn btn-primary" 
                                    style="padding: 0.2rem 0.5rem; font-size: 0.7rem;"
                                    onclick="solicitarConfirmacaoSave({{ $item->id }})">
                                💾 Salvar
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="14" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                            Nenhum item encontrado no estoque com os filtros aplicados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <!-- Paginação Customizada Dark Mode -->
    @if($items->hasPages())
        <div class="pagination-container">
            <div>
                Exibindo <strong>{{ $items->firstItem() }}</strong> até <strong>{{ $items->lastItem() }}</strong> de <strong>{{ $items->total() }}</strong> itens cadastrados
            </div>
            <div>
                {{ $items->links('pagination::bootstrap-4') }}
            </div>
        </div>
    @endif
</div>

<script>
let protheusItemsCache = [];
let pendingSaveItemId = null;

function checkColumnsVisibility() {
    const hasFilterPai = {{ request()->filled('f_prod_pai') ? 'true' : 'false' }};
    const showPai = localStorage.getItem('showColProdutoPai') === 'true' || hasFilterPai;
    
    document.querySelectorAll('.col-produto-pai').forEach(el => {
        el.style.display = showPai ? 'table-cell' : 'none';
    });
    
    const iconPai = document.getElementById('iconSquarePai');
    if (iconPai) iconPai.innerText = showPai ? '☑️' : '🔲';

    const hasFilterDesc = {{ request()->filled('f_desc_longa') ? 'true' : 'false' }};
    const showDesc = localStorage.getItem('showColDescLonga') === 'true' || hasFilterDesc;

    document.querySelectorAll('.col-desc-longa').forEach(el => {
        el.style.display = showDesc ? 'table-cell' : 'none';
    });

    const iconDesc = document.getElementById('iconSquareDescLonga');
    if (iconDesc) iconDesc.innerText = showDesc ? '☑️' : '🔲';
}

function toggleColunaProdutoPai() {
    const current = localStorage.getItem('showColProdutoPai') === 'true';
    localStorage.setItem('showColProdutoPai', !current);
    checkColumnsVisibility();
}

function toggleColunaDescricaoLonga() {
    const current = localStorage.getItem('showColDescLonga') === 'true';
    localStorage.setItem('showColDescLonga', !current);
    checkColumnsVisibility();
}

document.addEventListener('DOMContentLoaded', function() {
    checkColumnsVisibility();
});

function abrirModalConsultaProtheus() {
    document.getElementById('modalConsultaProtheus').style.display = 'block';
    document.getElementById('consulta_pedido').focus();
}

function buscarItensProtheus() {
    const pedido = document.getElementById('consulta_pedido').value.trim();
    const filial = document.getElementById('consulta_filial').value;
    const statusLabel = document.getElementById('resultado_status_label');
    const tbody = document.getElementById('tbody_protheus_items');
    const formBatch = document.getElementById('formImportBatch');

    if (!pedido) {
        alert('Por favor, informe o número do Pedido ou C2_OBS!');
        return;
    }

    statusLabel.innerHTML = '⏳ Consultando itens no Protheus... Aguarde...';
    statusLabel.style.color = '#a5b4fc';
    tbody.innerHTML = '';
    formBatch.style.display = 'none';

    fetch('{{ route("estoque.consultar-pedido") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ pedido: pedido, filial: filial })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            statusLabel.innerHTML = '❌ ' + data.message;
            statusLabel.style.color = '#fca5a5';
            return;
        }

        protheusItemsCache = data.items;
        statusLabel.innerHTML = `✅ ${data.count} item(ns) encontrado(s) para o pedido "${pedido}" no Protheus. Selecione os itens abaixo para importar:`;
        statusLabel.style.color = '#6ee7b7';

        tbody.innerHTML = '';
        data.items.forEach((item, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="text-align: center;">
                    <input type="checkbox" class="item-checkbox" data-index="${index}" checked onchange="atualizarContadorSelecionados()">
                </td>
                <td>${item.filial || '-'}</td>
                <td><strong>${item.pedido || '-'}</strong></td>
                <td><code style="color: var(--accent);">${item.op || '-'}</code></td>
                <td><strong>${item.codigo_produto}</strong></td>
                <td style="font-size: 0.75rem;">${item.descricao || '-'}</td>
                <td class="col-desc-longa" style="display: none; font-size: 0.75rem; color: #38bdf8;">${item.descricao_longa || '-'}</td>
                <td class="col-produto-pai" style="display: none; font-size: 0.75rem; color: #c084fc;"><code>${item.produto_pai || '-'}</code></td>
                <td style="text-align: center;"><strong>${item.quantidade}</strong></td>
                <td style="font-size: 0.75rem;">${item.cliente_obs || '-'}</td>
            `;
            tbody.appendChild(tr);
        });

        formBatch.style.display = 'block';
        atualizarContadorSelecionados();
        checkColumnsVisibility();
    })
    .catch(err => {
        console.error(err);
        statusLabel.innerHTML = '❌ Erro ao se comunicar com o servidor. Tente novamente.';
        statusLabel.style.color = '#fca5a5';
    });
}

function toggleAllCheckboxes(source) {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(cb => cb.checked = source.checked);
    atualizarContadorSelecionados();
}

function atualizarContadorSelecionados() {
    const checkboxes = document.querySelectorAll('.item-checkbox:checked');
    const countLabel = document.getElementById('label_selecionados_count');
    const selectedItems = [];

    checkboxes.forEach(cb => {
        const idx = cb.getAttribute('data-index');
        if (protheusItemsCache[idx]) {
            selectedItems.push(protheusItemsCache[idx]);
        }
    });

    countLabel.innerText = `${selectedItems.length} item(ns) selecionado(s)`;
    document.getElementById('input_items_json').value = JSON.stringify(selectedItems);
}

function recalcularLinhaEstoque(id) {
    const qtdOpEl = document.getElementById('qtd_op_' + id);
    const inputEstEl = document.getElementById('input_qtd_est_' + id);
    const labelComprarEl = document.getElementById('label_qtd_comprar_' + id);

    if (!qtdOpEl || !inputEstEl || !labelComprarEl) return;

    const qtdOp = parseFloat(qtdOpEl.innerText) || 0;
    const qtdEst = parseFloat(inputEstEl.value) || 0;
    const qtdComprar = Math.max(0, qtdOp - qtdEst);

    labelComprarEl.innerText = qtdComprar;
    if (qtdComprar > 0) {
        labelComprarEl.style.color = '#ef4444';
    } else {
        labelComprarEl.style.color = '#10b981';
    }
}

function atualizarBadgeStatus(id) {
    const select = document.getElementById('select_status_' + id);
    const badge = document.getElementById('badge_status_' + id);
    if (!select || !badge) return;

    const val = select.value;
    badge.innerText = val;
    badge.className = 'badge ' + (
        val === 'FALTA' ? 'badge-falta' :
        val === 'SEPARADO' ? 'badge-separado' :
        val === 'RETIRADO' ? 'badge-retirado' :
        val === 'FABRICA' ? 'badge-fabrica' : 'badge-kanban'
    );
}

function solicitarConfirmacaoSave(id) {
    pendingSaveItemId = id;
    const select = document.getElementById('select_status_' + id);
    const inputEst = document.getElementById('input_qtd_est_' + id);
    const statusVal = select ? select.value : '';
    const qtdEstVal = inputEst ? inputEst.value : '0';

    const textDetails = document.getElementById('confirmTextDetails');
    textDetails.innerHTML = `Deseja salvar as alterações deste item?<br>• <strong>Qtd Estoque:</strong> ${qtdEstVal}<br>• <strong>Novo Status:</strong> ${statusVal}`;

    document.getElementById('modalConfirmacaoSave').style.display = 'block';
    document.getElementById('modalOverlay').style.display = 'block';
}

function fecharModalConfirmacao() {
    pendingSaveItemId = null;
    document.getElementById('modalConfirmacaoSave').style.display = 'none';
    document.getElementById('modalOverlay').style.display = 'none';
}

document.getElementById('btnConfirmarSaveAction').addEventListener('click', function() {
    if (!pendingSaveItemId) return;
    window.mostrarLoading('💾 Salvando item de estoque... Aguarde...');

    const row = document.getElementById('row_estoque_' + pendingSaveItemId);
    const inputEst = document.getElementById('input_qtd_est_' + pendingSaveItemId);
    const selectStatus = document.getElementById('select_status_' + pendingSaveItemId);

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ url("estoque") }}/' + pendingSaveItemId;

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);

    const methodInput = document.createElement('input');
    methodInput.type = 'hidden';
    methodInput.name = '_method';
    methodInput.value = 'PUT';
    form.appendChild(methodInput);

    if (inputEst) {
        const estInput = document.createElement('input');
        estInput.type = 'hidden';
        estInput.name = 'quantidade_estoque';
        estInput.value = inputEst.value;
        form.appendChild(estInput);
    }

    if (selectStatus) {
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = selectStatus.value;
        form.appendChild(statusInput);
    }

    document.body.appendChild(form);
    form.submit();
});
</script>
@endsection
