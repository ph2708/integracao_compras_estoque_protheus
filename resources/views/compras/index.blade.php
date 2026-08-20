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
    <form action="{{ route('compras.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 0.85rem; align-items: flex-end;" onsubmit="window.mostrarLoading('🔍 Consultando Pedido de Venda no Protheus... Aguarde...')">
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
    <form action="{{ route('compras.update-batch') }}" method="POST" id="formBatchCompras" onsubmit="window.mostrarLoading('💾 Salvando todas as alterações em Compras... Aguarde...')">
        @csrf
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; flex-wrap: wrap; gap: 0.5rem;">
            <h3 style="font-size: 1rem;">
                @if($searchPv)
                    📋 Itens do Pedido de Venda: <span style="color: var(--accent);">{{ $searchPv }}</span> (Filial: {{ $searchFilial ?: 'Todas' }})
                @else
                    📋 Todos os Itens em Compras
                @endif
            </h3>
            <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                <!-- Quadradinho Toggle para Exibir/Ocultar Coluna Produto Pai -->
                <button type="button" class="btn btn-secondary" onclick="toggleColunaProdutoPai()" style="padding: 0.35rem 0.65rem; font-size: 0.75rem; border-color: rgba(168, 85, 247, 0.5); font-weight: 500;">
                    <span id="iconSquarePaiCompras">🔲</span> Produto Pai Concatenado
                </button>

                @if($searchPv || request()->hasAny(['f_produto', 'f_descricao', 'f_prod_pai', 'f_op', 'f_cliente', 'f_status_pcp', 'f_pedido_compra', 'f_fornecedor', 'f_status_pagamento']))
                    <a href="{{ route('compras.index') }}" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" onclick="window.mostrarLoading('⏳ Limpando filtros...')">
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
                        <th class="col-produto-pai" style="display: none; color: #c084fc;">Código / Produto Pai Concatenado</th>
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
                            <input type="text" name="f_cliente" value="{{ request('f_cliente') }}" class="filter-input" placeholder="Multi: A, B..." form="formFilterCompras" onchange="document.getElementById('formFilterCompras').submit()">
                        </th>
                        <th>
                            <input type="text" name="f_produto" value="{{ request('f_produto') }}" class="filter-input" placeholder="Multi: 6164, 1050..." form="formFilterCompras" onchange="document.getElementById('formFilterCompras').submit()">
                        </th>
                        <th>
                            <input type="text" name="f_descricao" value="{{ request('f_descricao') }}" class="filter-input" placeholder="Multi: CABO, CHAVE..." form="formFilterCompras" onchange="document.getElementById('formFilterCompras').submit()">
                        </th>
                        <th class="col-produto-pai" style="display: none;">
                            <input type="text" name="f_prod_pai" value="{{ request('f_prod_pai') }}" class="filter-input" placeholder="Multi: QUADRO, 9510..." form="formFilterCompras" onchange="document.getElementById('formFilterCompras').submit()">
                        </th>
                        <th></th>
                        <th>
                            <input type="text" name="f_status_pcp" value="{{ request('f_status_pcp') }}" class="filter-input" placeholder="Multi: FALTA, RETIRADO..." form="formFilterCompras" onchange="document.getElementById('formFilterCompras').submit()">
                        </th>
                        <th>
                            <input type="text" name="f_pedido_compra" value="{{ request('f_pedido_compra') }}" class="filter-input" placeholder="Multi: PC1, PC2..." form="formFilterCompras" onchange="document.getElementById('formFilterCompras').submit()">
                        </th>
                        <th>
                            <input type="text" name="f_fornecedor" value="{{ request('f_fornecedor') }}" class="filter-input" placeholder="Multi: FORN A, FORN B..." form="formFilterCompras" onchange="document.getElementById('formFilterCompras').submit()">
                        </th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th>
                            <input type="text" name="f_status_pagamento" value="{{ request('f_status_pagamento') }}" class="filter-input" placeholder="Multi: PAGO, FATURADO..." form="formFilterCompras" onchange="document.getElementById('formFilterCompras').submit()">
                        </th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paginatedItems as $item)
                    @php
                        $estoqueId = $item['estoque_item_id'];
                    @endphp
                    <tr id="row_compra_{{ $estoqueId ?? $loop->index }}">
                        <td><strong>{{ $item['pedido_venda'] }}</strong></td>
                        <td style="font-size: 0.775rem;">{{ $item['cliente_obs'] }}</td>
                        <td><strong>{{ $item['codigo_produto'] }}</strong></td>
                        <td style="font-size: 0.775rem;">{{ $item['descricao'] }}</td>
                        <td class="col-produto-pai" style="display: none; font-size: 0.75rem; color: #c084fc;">
                            <code style="background: rgba(168, 85, 247, 0.15); padding: 0.15rem 0.35rem; border-radius: 0.25rem; border: 1px solid rgba(168, 85, 247, 0.3);">{{ $item['produto_pai'] ?? '-' }}</code>
                        </td>
                        <td style="text-align: center;">
                            <strong id="qtd_comprar_{{ $estoqueId }}" style="color: {{ $item['quantidade_comprar'] > 0 ? '#ef4444' : '#10b981' }};">
                                {{ $item['quantidade_comprar'] }}
                            </strong>
                        </td>
                        <td>
                            <span class="badge {{ $item['status_pcp_badge'] }}">{{ $item['status_pcp'] }}</span>
                        </td>

                        @if($estoqueId)
                        <!-- Campos Editáveis para Itens que estão no Banco MySQL Local -->
                        <td>
                            <input type="text" 
                                   name="items[{{ $estoqueId }}][pedido_compra]" 
                                   value="{{ $item['pedido_compra'] }}" 
                                   class="form-control" 
                                   placeholder="N° PC..."
                                   style="padding: 0.2rem 0.4rem; font-size: 0.75rem;">
                        </td>
                        <td>
                            <input type="text" 
                                   name="items[{{ $estoqueId }}][codigo_fornecedor]" 
                                   value="{{ $item['codigo_fornecedor'] }}" 
                                   class="form-control" 
                                   placeholder="Cód / Nome Fornecedor..."
                                   style="padding: 0.2rem 0.4rem; font-size: 0.75rem;">
                        </td>
                        <td>
                            <input type="number" 
                                   step="0.01" 
                                   name="items[{{ $estoqueId }}][valor_unitario]" 
                                   id="input_val_unit_{{ $estoqueId }}"
                                   value="{{ $item['valor_unitario'] }}" 
                                   class="form-control" 
                                   placeholder="0.00"
                                   style="padding: 0.2rem 0.4rem; font-size: 0.75rem; font-weight: 600; color: #fcd34d;"
                                   onchange="recalcularLinhaCompra({{ $estoqueId }})">
                        </td>
                        <td>
                            <input type="number" 
                                   step="0.01" 
                                   name="items[{{ $estoqueId }}][ipi]" 
                                   id="input_ipi_{{ $estoqueId }}"
                                   value="{{ $item['ipi'] }}" 
                                   class="form-control" 
                                   placeholder="0"
                                   style="padding: 0.2rem 0.4rem; font-size: 0.75rem; text-align: center;"
                                   onchange="recalcularLinhaCompra({{ $estoqueId }})">
                        </td>
                        <td>
                            <input type="number" 
                                   step="0.01" 
                                   name="items[{{ $estoqueId }}][frete]" 
                                   id="input_frete_{{ $estoqueId }}"
                                   value="{{ $item['frete'] }}" 
                                   class="form-control" 
                                   placeholder="0.00"
                                   style="padding: 0.2rem 0.4rem; font-size: 0.75rem; text-align: right;"
                                   onchange="recalcularLinhaCompra({{ $estoqueId }})">
                        </td>
                        <td>
                            <input type="date" 
                                   name="items[{{ $estoqueId }}][data_pc]" 
                                   value="{{ $item['data_pc'] ? (is_string($item['data_pc']) ? $item['data_pc'] : $item['data_pc']->format('Y-m-d')) : '' }}" 
                                   class="form-control" 
                                   style="padding: 0.15rem 0.3rem; font-size: 0.75rem;">
                        </td>
                        <td>
                            <input type="date" 
                                   name="items[{{ $estoqueId }}][data_pagamento]" 
                                   value="{{ $item['data_pagamento'] ? (is_string($item['data_pagamento']) ? $item['data_pagamento'] : $item['data_pagamento']->format('Y-m-d')) : '' }}" 
                                   class="form-control" 
                                   style="padding: 0.15rem 0.3rem; font-size: 0.75rem;">
                        </td>
                        <td>
                            <input type="text" 
                                   name="items[{{ $estoqueId }}][solicitacao_compra]" 
                                   value="{{ $item['solicitacao_compra'] }}" 
                                   class="form-control" 
                                   placeholder="N° SC..."
                                   style="padding: 0.2rem 0.4rem; font-size: 0.75rem;">
                        </td>
                        <td>
                            <select name="items[{{ $estoqueId }}][status_pagamento]" 
                                    class="form-select" 
                                    style="padding: 0.2rem 0.3rem; font-size: 0.75rem;">
                                <option value="PENDENTE" {{ $item['status_pagamento'] == 'PENDENTE' ? 'selected' : '' }}>PENDENTE</option>
                                <option value="PAGAMENTO ANTECIPADO" {{ $item['status_pagamento'] == 'PAGAMENTO ANTECIPADO' ? 'selected' : '' }}>PAG. ANTECIPADO</option>
                                <option value="FATURADO" {{ $item['status_pagamento'] == 'FATURADO' ? 'selected' : '' }}>FATURADO</option>
                                <option value="PAGO" {{ $item['status_pagamento'] == 'PAGO' ? 'selected' : '' }}>PAGO</option>
                            </select>
                        </td>
                        <td style="text-align: right; font-weight: 700; color: #6ee7b7;" id="label_val_total_{{ $estoqueId }}">
                            R$ {{ number_format($item['valor_total'], 2, ',', '.') }}
                        </td>
                        <td style="text-align: center;">
                            <button type="button" 
                                    class="btn btn-primary" 
                                    style="padding: 0.2rem 0.5rem; font-size: 0.7rem;"
                                    onclick="solicitarSalvarSingleCompra({{ $estoqueId }})">
                                💾 Salvar
                            </button>
                        </td>
                        @else
                        <!-- Somente Leitura para itens trazidos da API Protheus que ainda não estão salvos no Estoque Local -->
                        <td colspan="10" style="text-align: center; color: var(--text-muted); font-size: 0.75rem; background: rgba(255,255,255,0.02);">
                            <em>Importe este item no Painel de Estoque (PCP) para habilitar edição financeira.</em>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="18" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                            Nenhum item de compras encontrado com os filtros selecionados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <!-- Paginação Customizada Dark Mode -->
    @if($paginatedItems->hasPages())
        <div class="pagination-container">
            <div>
                Exibindo <strong>{{ $paginatedItems->firstItem() }}</strong> até <strong>{{ $paginatedItems->lastItem() }}</strong> de <strong>{{ $paginatedItems->total() }}</strong> itens em compras
            </div>
            <div>
                {{ $paginatedItems->links('pagination::bootstrap-4') }}
            </div>
        </div>
    @endif
</div>

<script>
function checkProdutoPaiVisibility() {
    const hasFilter = {{ request()->filled('f_prod_pai') ? 'true' : 'false' }};
    const show = localStorage.getItem('showColProdutoPai') === 'true' || hasFilter;
    
    document.querySelectorAll('.col-produto-pai').forEach(el => {
        el.style.display = show ? 'table-cell' : 'none';
    });
    
    const icon = document.getElementById('iconSquarePaiCompras');
    if (icon) {
        icon.innerText = show ? '☑️' : '🔲';
    }
}

function toggleColunaProdutoPai() {
    const current = localStorage.getItem('showColProdutoPai') === 'true';
    const newState = !current;
    localStorage.setItem('showColProdutoPai', newState);
    checkProdutoPaiVisibility();
}

document.addEventListener('DOMContentLoaded', function() {
    checkProdutoPaiVisibility();
});

function recalcularLinhaCompra(estoqueId) {
    const inputValUnit = document.getElementById('input_val_unit_' + estoqueId);
    const inputIpi = document.getElementById('input_ipi_' + estoqueId);
    const inputFrete = document.getElementById('input_frete_' + estoqueId);
    const labelQtdComprar = document.getElementById('qtd_comprar_' + estoqueId);
    const labelTotal = document.getElementById('label_val_total_' + estoqueId);

    if (!inputValUnit || !labelTotal || !labelQtdComprar) return;

    const valUnitario = parseFloat(inputValUnit.value) || 0;
    const ipi = parseFloat(inputIpi ? inputIpi.value : 0) || 0;
    const frete = parseFloat(inputFrete ? inputFrete.value : 0) || 0;
    const qtdComprar = parseFloat(labelQtdComprar.innerText) || 0;

    const valTotal = (valUnitario * qtdComprar) + (valUnitario * qtdComprar * (ipi / 100)) + frete;
    labelTotal.innerText = 'R$ ' + valTotal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function solicitarSalvarSingleCompra(estoqueId) {
    if (!confirm('Deseja salvar as alterações deste item de compras?')) return;
    window.mostrarLoading('💾 Salvando item de compras... Aguarde...');

    const row = document.getElementById('row_compra_' + estoqueId);
    const inputs = row.querySelectorAll('input, select');

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("compras.update-batch") }}';

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);

    inputs.forEach(input => {
        if (input.name) {
            const clone = input.cloneNode(true);
            form.appendChild(clone);
        }
    });

    document.body.appendChild(form);
    form.submit();
}
</script>
@endsection
