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

    <form action="{{ route('estoque.store-batch') }}" method="POST" id="formImportBatch" style="display: none;">
        @csrf
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
    <form action="{{ route('estoque.store') }}" method="POST">
        @csrf
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.85rem;">
            <div class="form-group">
                <label class="form-label">Código do Produto *</label>
                <input type="text" name="codigo_produto" class="form-control" placeholder="Ex: PROD-1001" required>
            </div>
            <div class="form-group">
                <label class="form-label">Descrição</label>
                <input type="text" name="descricao" class="form-control" placeholder="Descrição do produto">
            </div>
            <div class="form-group">
                <label class="form-label">Ordem de Produção (OP)</label>
                <input type="text" name="op" class="form-control" placeholder="Ex: OP-2026-01">
            </div>
            <div class="form-group">
                <label class="form-label">Número do Pedido</label>
                <input type="text" name="pedido" class="form-control" placeholder="Ex: PED-5501">
            </div>
            <div class="form-group">
                <label class="form-label">Quantidade OP *</label>
                <input type="number" step="0.01" id="manual_qtd_op" name="quantidade" class="form-control" value="1" placeholder="Ex: 10">
            </div>
            <div class="form-group">
                <label class="form-label">Qtd em Estoque (Inicial = 0)</label>
                <input type="number" step="0.01" id="manual_qtd_est" name="quantidade_estoque" class="form-control" value="0" placeholder="Ex: 0">
            </div>
            <div class="form-group">
                <label class="form-label">Nome do Cliente (C2_OBS)</label>
                <input type="text" name="cliente_obs" class="form-control" placeholder="Ex: ELETRONET S.A">
            </div>
            <div class="form-group">
                <label class="form-label">Status PCP / Almox *</label>
                <select name="status" class="form-select" required>
                    <option value="FALTA">FALTA *</option>
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
<form action="{{ route('estoque.index') }}" method="GET" id="formFilterEstoque"></form>

<!-- Tabela de Itens de Estoque Salvos no MySQL -->
<div class="card">
    <form action="{{ route('estoque.update-batch') }}" method="POST" id="formBatchEstoque">
        @csrf
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; flex-wrap: wrap; gap: 0.5rem;">
            <h3 style="font-size: 1rem;">📋 Itens Cadastrados no Estoque Local (MySQL)</h3>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                @if(request()->hasAny(['f_pedido', 'f_produto', 'f_descricao', 'f_op', 'f_status', 'f_cliente']))
                    <a href="{{ route('estoque.index') }}" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
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
                        <th>Pedido (C2_PEDIDO)</th>
                        <th>Código Produto</th>
                        <th>Descrição</th>
                        <th>OP</th>
                        <th style="text-align: center;">Qtd OP</th>
                        <th style="color: #fcd34d; text-align: center;">Qtd Estoque ✏️</th>
                        <th style="color: #6ee7b7; text-align: center;">Qtd a Comprar</th>
                        <th>Status PCP Atual</th>
                        <th>Nome do Cliente (C2_OBS)</th>
                        <th style="color: #38bdf8;">Observação Estoque ✏️</th>
                        <th>Alterar Status PCP ✏️</th>
                        <th style="text-align: center; color: #a5b4fc;">Ações</th>
                    </tr>
                    <tr class="filter-row">
                        <th>
                            <input type="text" name="f_pedido" value="{{ request('f_pedido') }}" class="filter-input" placeholder="Ped..." form="formFilterEstoque" onchange="document.getElementById('formFilterEstoque').submit()">
                        </th>
                        <th>
                            <input type="text" name="f_produto" value="{{ request('f_produto') }}" class="filter-input" placeholder="Prod..." form="formFilterEstoque" onchange="document.getElementById('formFilterEstoque').submit()">
                        </th>
                        <th>
                            <input type="text" name="f_descricao" value="{{ request('f_descricao') }}" class="filter-input" placeholder="Desc..." form="formFilterEstoque" onchange="document.getElementById('formFilterEstoque').submit()">
                        </th>
                        <th>
                            <input type="text" name="f_op" value="{{ request('f_op') }}" class="filter-input" placeholder="OP..." form="formFilterEstoque" onchange="document.getElementById('formFilterEstoque').submit()">
                        </th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th>
                            <select name="f_status" class="filter-input" form="formFilterEstoque" onchange="document.getElementById('formFilterEstoque').submit()">
                                <option value="">-- Todos --</option>
                                <option value="FALTA" {{ request('f_status') == 'FALTA' ? 'selected' : '' }}>FALTA</option>
                                <option value="SEPARADO" {{ request('f_status') == 'SEPARADO' ? 'selected' : '' }}>SEPARADO</option>
                                <option value="RETIRADO" {{ request('f_status') == 'RETIRADO' ? 'selected' : '' }}>RETIRADO</option>
                                <option value="FABRICA" {{ request('f_status') == 'FABRICA' ? 'selected' : '' }}>FABRICA</option>
                                <option value="FABRICAR INTERNO KANBAN" {{ request('f_status') == 'FABRICAR INTERNO KANBAN' ? 'selected' : '' }}>KANBAN</option>
                            </select>
                        </th>
                        <th>
                            <input type="text" name="f_cliente" value="{{ request('f_cliente') }}" class="filter-input" placeholder="Cliente..." form="formFilterEstoque" onchange="document.getElementById('formFilterEstoque').submit()">
                        </th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                    <tr id="row_estoque_{{ $item->id }}">
                        <td><strong>{{ $item->pedido ?? '-' }}</strong></td>
                        <td><strong>{{ $item->codigo_produto }}</strong></td>
                        <td style="font-size: 0.775rem;">{{ $item->descricao ?? '-' }}</td>
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
                                   style="padding: 0.25rem 0.4rem; font-size: 0.75rem; width: 75px; color: #fcd34d; font-weight: 600; text-align: center;" 
                                   placeholder="0"
                                   oninput="recalcularQtdComprarTela({{ $item->id }})">
                        </td>
                        <td style="text-align: center;">
                            @php
                                $qtdComprarVal = max(0, floatval($item->quantidade) - $valQtdEstoque);
                            @endphp
                            <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 0.3rem; padding: 0.2rem 0.4rem; display: inline-block;">
                                <strong id="display_qtd_comprar_{{ $item->id }}" style="color: #6ee7b7; font-size: 0.85rem;">{{ $qtdComprarVal }}</strong>
                            </div>
                        </td>
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
                            <span id="badge_status_{{ $item->id }}" class="badge {{ $badgeClass }}">{{ $item->status }}</span>
                        </td>
                        <td style="font-size: 0.75rem; color: #cbd5e1;">
                            {{ $item->cliente_obs ?? '-' }}
                        </td>
                        <td>
                            <input type="text" 
                                   name="items[{{ $item->id }}][observacao_estoque]"
                                   id="input_obs_{{ $item->id }}"
                                   value="{{ $item->observacao_estoque }}" 
                                   class="form-control" 
                                   style="padding: 0.25rem 0.4rem; font-size: 0.75rem;" 
                                   placeholder="Observação estoque...">
                        </td>
                        <td>
                            <select name="items[{{ $item->id }}][status]"
                                    id="select_status_{{ $item->id }}" 
                                    class="form-select" 
                                    style="padding: 0.25rem 0.4rem; font-size: 0.7rem;">
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
                                    style="padding: 0.3rem 0.6rem; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 0.2rem;"
                                    onclick="solicitarConfirmacaoSave({{ $item->id }}, '{{ $item->codigo_produto }}')">
                                💾 Salvar Item
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                            Nenhum item encontrado no estoque local.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <!-- Controles de Paginação -->
    <div class="pagination-container">
        <div>
            Exibindo <strong>{{ $items->firstItem() ?? 0 }}</strong> a <strong>{{ $items->lastItem() ?? 0 }}</strong> de <strong>{{ $items->total() }}</strong> itens cadastrados
        </div>
        <div>
            {{ $items->links() }}
        </div>
    </div>
</div>

<!-- Formulário Oculto Auxiliar para Salvar Linha Única -->
<form action="" method="POST" id="formSingleRowEstoque" style="display: none;">
    @csrf
    @method('PUT')
    <input type="hidden" name="quantidade_estoque" id="single_qtd_est">
    <input type="hidden" name="observacao_estoque" id="single_obs">
    <input type="hidden" name="status" id="single_status">
</form>

<script>
let itemIdParaSalvar = null;

function recalcularQtdComprarTela(itemId) {
    const qtdOp = parseFloat(document.getElementById(`qtd_op_${itemId}`).innerText) || 0;
    const qtdEst = parseFloat(document.getElementById(`input_qtd_est_${itemId}`).value) || 0;
    const qtdComprar = Math.max(0, qtdOp - qtdEst);
    document.getElementById(`display_qtd_comprar_${itemId}`).innerText = qtdComprar;
}

// 1. Abrir Modal de Confirmação antes de Salvar Linha Única
function solicitarConfirmacaoSave(itemId, codigoProduto) {
    const statusVal = document.getElementById(`select_status_${itemId}`).value;
    const qtdEstVal = document.getElementById(`input_qtd_est_${itemId}`).value || '0';
    const obsVal = document.getElementById(`input_obs_${itemId}`).value || '-';

    itemIdParaSalvar = itemId;

    const detailsHtml = `
        Confirma a gravação dos dados para o produto <strong>${codigoProduto}</strong>?<br><br>
        • <strong>Status PCP:</strong> <span style="color: #fcd34d;">${statusVal}</span><br>
        • <strong>Qtd em Estoque:</strong> ${qtdEstVal}<br>
        • <strong>Observação:</strong> ${obsVal}
    `;

    document.getElementById('confirmTextDetails').innerHTML = detailsHtml;
    document.getElementById('modalConfirmacaoSave').style.display = 'block';
    document.getElementById('modalOverlay').style.display = 'block';
}

function fecharModalConfirmacao() {
    document.getElementById('modalConfirmacaoSave').style.display = 'none';
    document.getElementById('modalOverlay').style.display = 'none';
    itemIdParaSalvar = null;
}

// 2. Submeter formulário de linha única
document.getElementById('btnConfirmarSaveAction').addEventListener('click', () => {
    if (!itemIdParaSalvar) return;

    const id = itemIdParaSalvar;
    const form = document.getElementById('formSingleRowEstoque');
    form.action = `/estoque/${id}`;

    document.getElementById('single_qtd_est').value = document.getElementById(`input_qtd_est_${id}`).value || '0';
    document.getElementById('single_obs').value = document.getElementById(`input_obs_${id}`).value || '';
    document.getElementById('single_status').value = document.getElementById(`select_status_${id}`).value;

    fecharModalConfirmacao();
    form.submit();
});

function abrirModalConsultaProtheus() {
    document.getElementById('modalConsultaProtheus').style.display = 'block';
}

async function buscarItensProtheus() {
    const pedido = document.getElementById('consulta_pedido').value.trim();
    const filial = document.getElementById('consulta_filial').value;
    const labelStatus = document.getElementById('resultado_status_label');
    const formBatch = document.getElementById('formImportBatch');
    const tbody = document.getElementById('tbody_protheus_items');

    if (!pedido) {
        alert('Por favor, informe o número do Pedido (C2_PEDIDO).');
        return;
    }

    labelStatus.innerHTML = '⏳ Consultando itens no Protheus... Aguarde...';
    labelStatus.style.color = 'var(--accent)';
    formBatch.style.display = 'none';
    tbody.innerHTML = '';

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    try {
        const response = await fetch('{{ route("estoque.consultar-pedido") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ pedido: pedido, filial: filial })
        });

        const result = await response.json();

        if (result.success && result.items.length > 0) {
            labelStatus.innerHTML = `✅ <strong>${result.count} item(ns) encontrado(s)</strong> para o Pedido <strong>${pedido}</strong> na Filial <strong>${filial || 'Todas'}</strong>. Selecione os itens desejados e clique em importar:`;
            labelStatus.style.color = '#6ee7b7';

            let html = '';
            result.items.forEach((item, index) => {
                html += `
                <tr>
                    <td style="text-align: center;">
                        <input type="checkbox" class="checkItem" checked onchange="updateSelectedCount()" data-index="${index}">
                        <input type="hidden" name="items[${index}][codigo_produto]" value="${item.codigo_produto}">
                        <input type="hidden" name="items[${index}][descricao]" value="${item.descricao}">
                        <input type="hidden" name="items[${index}][op]" value="${item.op}">
                        <input type="hidden" name="items[${index}][pedido]" value="${item.pedido}">
                        <input type="hidden" name="items[${index}][cliente_obs]" value="${item.cliente_obs}">
                        <input type="hidden" name="items[${index}][quantidade]" value="${item.quantidade}">
                        <input type="hidden" name="items[${index}][quantidade_estoque]" value="0">
                        <input type="hidden" name="items[${index}][status]" value="FALTA">
                    </td>
                    <td><span class="badge badge-faturado">${item.filial}</span></td>
                    <td><strong>${item.pedido}</strong></td>
                    <td><code style="color: var(--accent); font-size: 0.75rem;">${item.op}</code></td>
                    <td><strong>${item.codigo_produto}</strong></td>
                    <td style="font-size: 0.75rem;">${item.descricao}</td>
                    <td><strong style="color: #38bdf8;">${item.quantidade}</strong></td>
                    <td style="font-size: 0.75rem; color: #cbd5e1;">${item.cliente_obs || '-'}</td>
                </tr>
                `;
            });

            tbody.innerHTML = html;
            formBatch.style.display = 'block';
            document.getElementById('checkAll').checked = true;
            updateSelectedCount();
        } else {
            labelStatus.innerHTML = `⚠️ ${result.message || 'Nenhum item encontrado no Protheus.'}`;
            labelStatus.style.color = '#fcd34d';
        }
    } catch (e) {
        labelStatus.innerHTML = '❌ Ocorreu um erro ao consultar o Protheus. Verifique a conexão.';
        labelStatus.style.color = '#fca5a5';
    }
}

function toggleAllCheckboxes(master) {
    const checkboxes = document.querySelectorAll('.checkItem');
    checkboxes.forEach(cb => {
        cb.checked = master.checked;
        const index = cb.getAttribute('data-index');
        const hiddenInputs = cb.closest('td').querySelectorAll('input[type="hidden"]');
        hiddenInputs.forEach(inp => inp.disabled = !master.checked);
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.checkItem');
    let count = 0;
    checkboxes.forEach(cb => {
        const hiddenInputs = cb.closest('td').querySelectorAll('input[type="hidden"]');
        if (cb.checked) {
            count++;
            hiddenInputs.forEach(inp => inp.disabled = false);
        } else {
            hiddenInputs.forEach(inp => inp.disabled = true);
        }
    });
    document.getElementById('label_selecionados_count').innerText = `${count} item(ns) selecionado(s)`;
    document.getElementById('btnImportSelected').disabled = (count === 0);
}
</script>
@endsection
