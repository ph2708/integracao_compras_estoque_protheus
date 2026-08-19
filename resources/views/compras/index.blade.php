@extends('layouts.app')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700;">🛒 Painel de Compras</h1>
        <p style="color: var(--text-muted); font-size: 0.8rem;">Gerenciamento financeiro com edição linear direta na tabela e gravação em massa.</p>
    </div>
</div>

<!-- Filtro e Busca por PV (Pedido de Venda) no Protheus -->
<div class="card" style="border-color: rgba(99, 102, 241, 0.4); margin-bottom: 1.25rem;">
    <form action="{{ route('compras.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 0.85rem; align-items: flex-end;">
        <div class="form-group" style="margin-bottom: 0; min-width: 150px; flex: 1;">
            <label class="form-label">Filial Protheus</label>
            <select name="filial" class="form-select">
                <option value="">-- Todas --</option>
                @foreach($filiaisProtheus as $fil)
                    <option value="{{ $fil }}" {{ $searchFilial == $fil || ($fil == '22' && !$searchFilial) ? 'selected' : '' }}>Filial {{ $fil }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0; min-width: 250px; flex: 3;">
            <label class="form-label">Buscar por Pedido de Venda (C2_PEDIDO / PV)</label>
            <input type="text" name="pedido_venda" value="{{ $searchPv }}" class="form-control" placeholder="Digite o N° do PV (ex: 006614)...">
        </div>

        <div style="min-width: 120px; flex: 1;">
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                🔍 Buscar PV
            </button>
        </div>
    </form>
</div>

<!-- Formulário Oculto de Filtros por Colunas em Compras -->
<form action="{{ route('compras.index') }}" method="GET" id="formFilterCompras">
    @if($searchPv)
        <input type="hidden" name="pedido_venda" value="{{ $searchPv }}">
    @endif
    @if($searchFilial)
        <input type="hidden" name="filial" value="{{ $searchFilial }}">
    @endif
</form>

<!-- Tabela de Itens de Compras (Com Edição Linear e Botão Salvar Tudo) -->
<div class="card">
    <form action="{{ route('compras.update-batch') }}" method="POST" id="formBatchCompras">
        @csrf
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; flex-wrap: wrap; gap: 0.5rem;">
            <h3 style="font-size: 1rem;">
                @if($searchPv)
                    📋 Itens do Pedido de Venda: <span style="color: var(--accent);">{{ $searchPv }}</span> (Filial: {{ $searchFilial ?: 'Todas' }})
                @else
                    📋 Todos os Itens em Compras
                @endif
            </h3>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                @if($searchPv || request()->hasAny(['f_produto', 'f_descricao', 'f_op', 'f_cliente', 'f_status_pcp', 'f_pedido_compra', 'f_fornecedor', 'f_status_pagamento']))
                    <a href="{{ route('compras.index') }}" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                        ✕ Limpar Todos os Filtros
                    </a>
                @endif
                <button type="submit" class="btn btn-primary" style="padding: 0.4rem 0.85rem; font-size: 0.8rem; background-color: #059669;" onclick="return confirm('Deseja salvar todas as alterações financeiras editadas nesta página?')">
                    💾 Salvar Todas as Alterações da Página
                </button>
            </div>
        </div>

        <div class="table-responsive" style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>PV</th>
                        <th>Cliente (C2_OBS)</th>
                        <th>Código Produto</th>
                        <th>Descrição</th>
                        <th style="color: #6ee7b7; text-align: center;">Qtd Comprar</th>
                        <th>Status PCP</th>
                        <th style="color: #38bdf8; min-width: 140px;">Pedido Compra ✏️</th>
                        <th style="color: #38bdf8; min-width: 150px;">Código / Fornecedor ✏️</th>
                        <th style="color: #fcd34d; min-width: 110px;">Valor Unit. (R$) ✏️</th>
                        <th style="color: #fcd34d; min-width: 75px;">IPI (%) ✏️</th>
                        <th style="color: #fcd34d; min-width: 100px;">Frete (R$) ✏️</th>
                        <th style="min-width: 130px;">Data PC ✏️</th>
                        <th style="min-width: 130px;">Data Pagamento ✏️</th>
                        <th style="min-width: 110px;">Solicitação Compra ✏️</th>
                        <th>Status Pagamento ✏️</th>
                        <th style="color: #6ee7b7; text-align: right; min-width: 130px;">Valor Total (Calc)</th>
                        <th style="text-align: center; color: #a5b4fc;">Ações</th>
                    </tr>
                    <tr class="filter-row">
                        <th></th>
                        <th>
                            <input type="text" name="f_cliente" value="{{ request('f_cliente') }}" class="filter-input" placeholder="Cliente..." form="formFilterCompras" onchange="document.getElementById('formFilterCompras').submit()">
                        </th>
                        <th>
                            <input type="text" name="f_produto" value="{{ request('f_produto') }}" class="filter-input" placeholder="Produto..." form="formFilterCompras" onchange="document.getElementById('formFilterCompras').submit()">
                        </th>
                        <th>
                            <input type="text" name="f_descricao" value="{{ request('f_descricao') }}" class="filter-input" placeholder="Descrição..." form="formFilterCompras" onchange="document.getElementById('formFilterCompras').submit()">
                        </th>
                        <th></th>
                        <th>
                            <select name="f_status_pcp" class="filter-input" form="formFilterCompras" onchange="document.getElementById('formFilterCompras').submit()">
                                <option value="">-- Todos --</option>
                                <option value="FALTA" {{ request('f_status_pcp') == 'FALTA' ? 'selected' : '' }}>FALTA</option>
                                <option value="SEPARADO" {{ request('f_status_pcp') == 'SEPARADO' ? 'selected' : '' }}>SEPARADO</option>
                                <option value="RETIRADO" {{ request('f_status_pcp') == 'RETIRADO' ? 'selected' : '' }}>RETIRADO</option>
                                <option value="FABRICA" {{ request('f_status_pcp') == 'FABRICA' ? 'selected' : '' }}>FABRICA</option>
                                <option value="FABRICAR INTERNO KANBAN" {{ request('f_status_pcp') == 'FABRICAR INTERNO KANBAN' ? 'selected' : '' }}>KANBAN</option>
                            </select>
                        </th>
                        <th>
                            <input type="text" name="f_pedido_compra" value="{{ request('f_pedido_compra') }}" class="filter-input" placeholder="Ped. Compra..." form="formFilterCompras" onchange="document.getElementById('formFilterCompras').submit()">
                        </th>
                        <th>
                            <input type="text" name="f_fornecedor" value="{{ request('f_fornecedor') }}" class="filter-input" placeholder="Fornecedor..." form="formFilterCompras" onchange="document.getElementById('formFilterCompras').submit()">
                        </th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th>
                            <select name="f_status_pagamento" class="filter-input" form="formFilterCompras" onchange="document.getElementById('formFilterCompras').submit()">
                                <option value="">-- Todos --</option>
                                <option value="PENDENTE" {{ request('f_status_pagamento') == 'PENDENTE' ? 'selected' : '' }}>PENDENTE</option>
                                <option value="PAGAMENTO ANTECIPADO" {{ request('f_status_pagamento') == 'PAGAMENTO ANTECIPADO' ? 'selected' : '' }}>ANTECIPADO</option>
                                <option value="FATURADO" {{ request('f_status_pagamento') == 'FATURADO' ? 'selected' : '' }}>FATURADO</option>
                                <option value="PAGO" {{ request('f_status_pagamento') == 'PAGO' ? 'selected' : '' }}>PAGO</option>
                            </select>
                        </th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paginatedItems as $index => $item)
                    <tr>
                        <td><strong>{{ $item['pedido_venda'] }}</strong></td>
                        <td style="font-size: 0.75rem; color: #cbd5e1;">{{ $item['cliente_obs'] }}</td>
                        <td><strong>{{ $item['codigo_produto'] }}</strong></td>
                        <td style="font-size: 0.775rem;">{{ $item['descricao'] }}</td>
                        <td style="text-align: center;">
                            <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 0.3rem; padding: 0.2rem 0.4rem; display: inline-block;">
                                <strong id="qtd_comprar_{{ $item['estoque_item_id'] ?? $index }}" style="color: #6ee7b7; font-size: 0.85rem;">{{ floatval($item['quantidade_comprar']) }}</strong>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $item['status_pcp_badge'] }}">{{ $item['status_pcp'] }}</span>
                        </td>

                        @if($item['no_estoque'] && $item['estoque_item_id'])
                            <!-- Pedido de Compra -->
                            <td>
                                <div style="display: flex; gap: 0.2rem;">
                                    <input type="text" 
                                           name="items[{{ $item['estoque_item_id'] }}][pedido_compra]" 
                                           id="pc_{{ $item['estoque_item_id'] }}"
                                           value="{{ $item['pedido_compra'] }}" 
                                           class="form-control" 
                                           style="padding: 0.25rem 0.4rem; font-size: 0.75rem;" 
                                           placeholder="Ped. Compra...">
                                    <button type="button" class="btn btn-secondary" style="padding: 0.25rem 0.4rem; font-size: 0.65rem;" onclick="consultarFornecedorLinha('{{ $item['estoque_item_id'] }}', '{{ $item['codigo_produto'] }}')">🔍</button>
                                </div>
                            </td>

                            <!-- Fornecedor -->
                            <td>
                                <input type="text" 
                                       name="items[{{ $item['estoque_item_id'] }}][codigo_fornecedor]" 
                                       id="forn_{{ $item['estoque_item_id'] }}"
                                       value="{{ $item['codigo_fornecedor'] }}" 
                                       class="form-control" 
                                       style="padding: 0.25rem 0.4rem; font-size: 0.75rem;" 
                                       placeholder="Fornecedor...">
                            </td>

                            <!-- Valor Unitario -->
                            <td>
                                <input type="number" step="0.01" 
                                       name="items[{{ $item['estoque_item_id'] }}][valor_unitario]" 
                                       id="vunit_{{ $item['estoque_item_id'] }}"
                                       value="{{ $item['valor_unitario'] ?: '' }}" 
                                       class="form-control" 
                                       style="padding: 0.25rem 0.4rem; font-size: 0.75rem; color: #fcd34d; font-weight: 600;" 
                                       placeholder="0.00" 
                                       oninput="calcularTotalLinha('{{ $item['estoque_item_id'] }}')">
                            </td>

                            <!-- IPI % -->
                            <td>
                                <input type="number" step="0.01" 
                                       name="items[{{ $item['estoque_item_id'] }}][ipi]" 
                                       id="ipi_{{ $item['estoque_item_id'] }}"
                                       value="{{ $item['ipi'] ?: '' }}" 
                                       class="form-control" 
                                       style="padding: 0.25rem 0.4rem; font-size: 0.75rem;" 
                                       placeholder="0%" 
                                       oninput="calcularTotalLinha('{{ $item['estoque_item_id'] }}')">
                            </td>

                            <!-- Frete -->
                            <td>
                                <input type="number" step="0.01" 
                                       name="items[{{ $item['estoque_item_id'] }}][frete]" 
                                       id="frete_{{ $item['estoque_item_id'] }}"
                                       value="{{ $item['frete'] ?: '' }}" 
                                       class="form-control" 
                                       style="padding: 0.25rem 0.4rem; font-size: 0.75rem;" 
                                       placeholder="0.00" 
                                       oninput="calcularTotalLinha('{{ $item['estoque_item_id'] }}')">
                            </td>

                            <!-- Data PC -->
                            <td>
                                <input type="date" 
                                       name="items[{{ $item['estoque_item_id'] }}][data_pc]" 
                                       value="{{ $item['data_pc'] }}" 
                                       class="form-control" 
                                       style="padding: 0.25rem 0.4rem; font-size: 0.7rem;">
                            </td>

                            <!-- Data Pagamento -->
                            <td>
                                <input type="date" 
                                       name="items[{{ $item['estoque_item_id'] }}][data_pagamento]" 
                                       value="{{ $item['data_pagamento'] }}" 
                                       class="form-control" 
                                       style="padding: 0.25rem 0.4rem; font-size: 0.7rem;">
                            </td>

                            <!-- Solicitação Compra -->
                            <td>
                                <input type="text" 
                                       name="items[{{ $item['estoque_item_id'] }}][solicitacao_compra]" 
                                       value="{{ $item['solicitacao_compra'] }}" 
                                       class="form-control" 
                                       style="padding: 0.25rem 0.4rem; font-size: 0.75rem;" 
                                       placeholder="SC...">
                            </td>

                            <!-- Status Pagamento -->
                            <td>
                                <select name="items[{{ $item['estoque_item_id'] }}][status_pagamento]" 
                                        class="form-select" 
                                        style="padding: 0.25rem 0.4rem; font-size: 0.7rem; font-weight: 600;">
                                    <option value="PENDENTE" {{ $item['status_pagamento'] == 'PENDENTE' ? 'selected' : '' }}>PENDENTE</option>
                                    <option value="PAGAMENTO ANTECIPADO" {{ $item['status_pagamento'] == 'PAGAMENTO ANTECIPADO' ? 'selected' : '' }}>ANTECIPADO</option>
                                    <option value="FATURADO" {{ $item['status_pagamento'] == 'FATURADO' ? 'selected' : '' }}>FATURADO</option>
                                    <option value="PAGO" {{ $item['status_pagamento'] == 'PAGO' ? 'selected' : '' }}>PAGO</option>
                                </select>
                            </td>

                            <!-- Valor Total Calculado -->
                            <td style="text-align: right;">
                                <strong id="vtotal_{{ $item['estoque_item_id'] }}" style="color: #6ee7b7; font-size: 0.85rem;">
                                    R$ {{ number_format(floatval($item['valor_total']), 2, ',', '.') }}
                                </strong>
                            </td>

                            <!-- Botao Salvar Unico -->
                            <td style="text-align: center;">
                                <button type="button" 
                                        class="btn btn-primary" 
                                        style="padding: 0.3rem 0.6rem; font-size: 0.75rem;"
                                        onclick="salvarUnicoCompras('{{ $item['estoque_item_id'] }}', '{{ $item['compra_id'] }}')">
                                    💾 Salvar
                                </button>
                            </td>
                        @else
                            <td colspan="11" style="text-align: center; color: var(--text-muted); font-size: 0.75rem;">
                                <em>Aguardando inclusão no Estoque PCP para editar valores</em>
                            </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="17" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                            Nenhum item encontrado nos filtros aplicados.
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
            Exibindo <strong>{{ $paginatedItems->firstItem() ?? 0 }}</strong> a <strong>{{ $paginatedItems->lastItem() ?? 0 }}</strong> de <strong>{{ $paginatedItems->total() }}</strong> itens em compras
        </div>
        <div>
            {{ $paginatedItems->links() }}
        </div>
    </div>
</div>

<!-- Formulário Oculto Auxiliar para Salvar Linha Única de Compras -->
<form action="" method="POST" id="formSingleRowCompras" style="display: none;">
    @csrf
    @method('PUT')
    <input type="hidden" name="pedido_compra" id="single_c_pc">
    <input type="hidden" name="codigo_fornecedor" id="single_c_forn">
    <input type="hidden" name="valor_unitario" id="single_c_vunit">
    <input type="hidden" name="ipi" id="single_c_ipi">
    <input type="hidden" name="frete" id="single_c_frete">
    <input type="hidden" name="data_pc" id="single_c_dpc">
    <input type="hidden" name="data_pagamento" id="single_c_dpag">
    <input type="hidden" name="solicitacao_compra" id="single_c_sc">
    <input type="hidden" name="status_pagamento" id="single_c_status">
</form>

<script>
function calcularTotalLinha(estoqueItemId) {
    const qtdElement = document.getElementById(`qtd_comprar_${estoqueItemId}`);
    if (!qtdElement) return;

    const qtdComprar = parseFloat(qtdElement.innerText) || 0;
    const vunit = parseFloat(document.getElementById(`vunit_${estoqueItemId}`)?.value) || 0;
    const ipi = parseFloat(document.getElementById(`ipi_${estoqueItemId}`)?.value) || 0;
    const frete = parseFloat(document.getElementById(`frete_${estoqueItemId}`)?.value) || 0;

    const total = (vunit * qtdComprar) + (vunit * qtdComprar * (ipi / 100)) + frete;

    const totalElement = document.getElementById(`vtotal_${estoqueItemId}`);
    if (totalElement) {
        totalElement.innerText = 'R$ ' + total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
}

function salvarUnicoCompras(estoqueItemId, compraId) {
    const form = document.getElementById('formSingleRowCompras');
    if (!compraId) {
        alert('Salve as alterações através do botão "Salvar Todas as Alterações da Página" para gerar o registro.');
        return;
    }

    form.action = `/compras/${compraId}`;

    document.getElementById('single_c_pc').value = document.getElementById(`pc_${estoqueItemId}`)?.value || '';
    document.getElementById('single_c_forn').value = document.getElementById(`forn_${estoqueItemId}`)?.value || '';
    document.getElementById('single_c_vunit').value = document.getElementById(`vunit_${estoqueItemId}`)?.value || '0';
    document.getElementById('single_c_ipi').value = document.getElementById(`ipi_${estoqueItemId}`)?.value || '0';
    document.getElementById('single_c_frete').value = document.getElementById(`frete_${estoqueItemId}`)?.value || '0';
    document.getElementById('single_c_status').value = document.querySelector(`select[name="items[${estoqueItemId}][status_pagamento]"]`)?.value || 'PENDENTE';

    form.submit();
}

async function consultarFornecedorLinha(estoqueItemId, codigoProduto) {
    const pcInput = document.getElementById(`pc_${estoqueItemId}`);
    const fornInput = document.getElementById(`forn_${estoqueItemId}`);
    if (!pcInput || !fornInput) return;

    const pedidoComp = pcInput.value.trim();
    if (!pedidoComp) {
        alert('Por favor, digite o número do Pedido de Compra.');
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    try {
        const response = await fetch('{{ route("compras.consultar-protheus") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ pedido_compra: pedidoComp, codigo_produto: codigoProduto })
        });

        const result = await response.json();

        if (result.success && result.data.codigo_fornecedor) {
            fornInput.value = result.data.codigo_fornecedor;
            alert(`✅ Fornecedor [${result.data.codigo_fornecedor}] carregado com sucesso do Protheus!`);
        } else {
            alert('⚠️ Pedido de Compra não encontrado no Protheus.');
        }
    } catch (e) {
        alert('❌ Erro de conexão ao buscar no Protheus.');
    }
}
</script>
@endsection
