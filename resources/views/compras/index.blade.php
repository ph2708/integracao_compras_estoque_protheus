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

<!-- Subtotal e Métricas Dinâmicas do Filtro Atual em Compras -->
<div style="display: flex; gap: 1rem; margin-bottom: 1.25rem; flex-wrap: wrap;">
    <div class="card" style="flex: 1.5; min-width: 260px; padding: 1rem; border-left: 4px solid #10b981; background: rgba(16, 185, 129, 0.05); display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">💰 Subtotal dos Pedidos (Filtro Atual)</div>
            <div style="font-size: 1.6rem; font-weight: 800; color: #10b981; margin-top: 0.2rem;">
                R$ {{ number_format($subtotalValorFiltro ?? 0, 2, ',', '.') }}
            </div>
        </div>
        <div style="font-size: 2rem; opacity: 0.8;">💵</div>
    </div>
    <div class="card" style="flex: 1; min-width: 180px; padding: 1rem; border-left: 4px solid #38bdf8; background: rgba(56, 189, 248, 0.05); display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">📦 Total de Itens Filtrados</div>
            <div style="font-size: 1.6rem; font-weight: 800; color: #38bdf8; margin-top: 0.2rem;">
                {{ number_format($totalItensFiltro ?? 0, 0, ',', '.') }} <span style="font-size: 0.9rem; font-weight: 500;">itens</span>
            </div>
        </div>
        <div style="font-size: 2rem; opacity: 0.8;">📊</div>
    </div>
    <div class="card" style="flex: 1; min-width: 180px; padding: 1rem; border-left: 4px solid #f59e0b; background: rgba(245, 158, 11, 0.05); display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">🛒 Soma Qtd. a Comprar</div>
            <div style="font-size: 1.6rem; font-weight: 800; color: #f59e0b; margin-top: 0.2rem;">
                {{ number_format($subtotalQtdComprar ?? 0, 0, ',', '.') }} <span style="font-size: 0.9rem; font-weight: 500;">un</span>
            </div>
        </div>
        <div style="font-size: 2rem; opacity: 0.8;">📦</div>
    </div>
</div>

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
                <!-- Seletor de Colunas Visíveis Estilo Excel -->
                <div class="dropdown" style="position: relative; display: inline-block;">
                    <button type="button" class="btn btn-secondary" onclick="toggleMenuColunasCompras()" style="padding: 0.35rem 0.65rem; font-size: 0.75rem; border-color: rgba(99, 102, 241, 0.5); font-weight: 500;">
                        ⚙️ Colunas Visíveis (Excel) ▾
                    </button>
                    <div id="dropdownMenuColunasCompras" style="display: none; position: absolute; right: 0; top: 110%; z-index: 1000; background-color: #0f172a; border: 1px solid #334155; border-radius: 0.5rem; padding: 0.85rem; width: 320px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.7); font-size: 0.78rem;">
                        <div style="font-weight: 700; margin-bottom: 0.6rem; color: #a5b4fc; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; padding-bottom: 0.4rem;">
                            <span>👁️ Exibir/Ocultar Colunas</span>
                            <span style="font-size: 0.7rem; color: #94a3b8; font-weight: normal;">(Salvo Auto)</span>
                        </div>
                        <div style="max-height: 290px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.45rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" id="chk_col-status-pcp" onchange="toggleColunaCompras('col-status-pcp', this.checked)"> Status PCP
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" id="chk_col-pv" onchange="toggleColunaCompras('col-pv', this.checked)"> PV (Pedido de Venda)
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" id="chk_col-cliente" onchange="toggleColunaCompras('col-cliente', this.checked)"> Cliente (C2_OBS)
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" id="chk_col-codigo-produto" onchange="toggleColunaCompras('col-codigo-produto', this.checked)"> Código Produto
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" id="chk_col-descricao" onchange="toggleColunaCompras('col-descricao', this.checked)"> Descrição
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: #38bdf8;">
                                <input type="checkbox" id="chk_col-desc-longa" onchange="toggleColunaCompras('col-desc-longa', this.checked)"> Descrição Longa (SB5010)
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: #c084fc;">
                                <input type="checkbox" id="chk_col-produto-pai" onchange="toggleColunaCompras('col-produto-pai', this.checked)"> Produto Pai Concatenado
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" id="chk_col-qtd-comprar" onchange="toggleColunaCompras('col-qtd-comprar', this.checked)"> Qtd Comprar
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" id="chk_col-valor-total" onchange="toggleColunaCompras('col-valor-total', this.checked)"> Valor Total (Calculado)
                            </label>
                            <hr style="border-color: #334155; margin: 0.3rem 0;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; opacity: 0.6; cursor: not-allowed; color: #38bdf8;">
                                <input type="checkbox" checked disabled> 🔒 Pedido Compra ✏️ (Sempre Visível)
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; opacity: 0.6; cursor: not-allowed; color: #38bdf8;">
                                <input type="checkbox" checked disabled> 🔒 Fornecedor ✏️ (Sempre Visível)
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; opacity: 0.6; cursor: not-allowed; color: #fcd34d;">
                                <input type="checkbox" checked disabled> 🔒 Valor Unitário ✏️ (Sempre Visível)
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; opacity: 0.6; cursor: not-allowed;">
                                <input type="checkbox" checked disabled> 🔒 Status Pagamento ✏️ (Sempre Visível)
                            </label>
                        </div>
                    </div>
                </div>

                @if($searchPv || request()->hasAny(['f_pv', 'f_produto', 'f_descricao', 'f_desc_longa', 'f_prod_pai', 'f_op', 'f_cliente', 'f_status_pcp', 'f_pedido_compra', 'f_fornecedor', 'f_status_pagamento']))
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
                        <th class="col-status-pcp" style="min-width: 120px;">Status PCP</th>
                        <th class="col-pv">PV</th>
                        <th class="col-cliente">Cliente (C2_OBS)</th>
                        <th class="col-codigo-produto">Código Produto</th>
                        <th class="col-descricao">Descrição</th>
                        <th class="col-desc-longa" style="display: none; color: #38bdf8;">Descrição Longa (B5_CEME - SB5010)</th>
                        <th class="col-produto-pai" style="display: none; color: #c084fc;">Código / Produto Pai Concatenado</th>
                        <th class="col-qtd-comprar" style="color: #6ee7b7; text-align: center;">Qtd Comprar</th>
                        <th class="col-pedido-compra" style="color: #38bdf8; min-width: 140px;">Pedido Compra ✏️</th>
                        <th class="col-fornecedor" style="color: #38bdf8; min-width: 150px;">Código / Fornecedor ✏️</th>
                        <th class="col-valor-unitario" style="color: #fcd34d; min-width: 110px;">Valor Unit. (R$) ✏️</th>
                        <th class="col-ipi" style="color: #fcd34d; min-width: 75px;">IPI (%) ✏️</th>
                        <th class="col-frete" style="color: #fcd34d; min-width: 100px;">Frete (R$) ✏️</th>
                        <th class="col-data-pc" style="min-width: 130px;">Data PC ✏️</th>
                        <th class="col-data-pagamento" style="min-width: 130px;">Data Pagamento ✏️</th>
                        <th class="col-solicitacao-compra" style="min-width: 110px;">Solicitação Compra ✏️</th>
                        <th class="col-status-pagamento">Status Pagamento ✏️</th>
                        <th class="col-valor-total" style="color: #6ee7b7; text-align: right; min-width: 130px;">Valor Total (Calc)</th>
                        <th class="col-acoes" style="text-align: center; color: #a5b4fc;">Ações</th>
                    </tr>
                    <tr class="filter-row">
                        <th class="col-status-pcp">
                            <input type="text" name="f_status_pcp" value="{{ request('f_status_pcp') }}" class="filter-input" placeholder="Multi: FALTA, RETIRADO..." form="formFilterCompras" onchange="document.getElementById('formFilterCompras').submit()">
                        </th>
                        <th class="col-pv">
                            <input type="text" name="f_pv" value="{{ request('f_pv') }}" class="filter-input" placeholder="Multi: 005860..." form="formFilterCompras" onchange="document.getElementById('formFilterCompras').submit()">
                        </th>
                        <th class="col-cliente">
                            <input type="text" name="f_cliente" value="{{ request('f_cliente') }}" class="filter-input" placeholder="Multi: A, B..." form="formFilterCompras" onchange="document.getElementById('formFilterCompras').submit()">
                        </th>
                        <th class="col-codigo-produto">
                            <input type="text" name="f_produto" value="{{ request('f_produto') }}" class="filter-input" placeholder="Multi: 6164, 1050..." form="formFilterCompras" onchange="document.getElementById('formFilterCompras').submit()">
                        </th>
                        <th class="col-descricao">
                            <input type="text" name="f_descricao" value="{{ request('f_descricao') }}" class="filter-input" placeholder="Multi: CABO, CHAVE..." form="formFilterCompras" onchange="document.getElementById('formFilterCompras').submit()">
                        </th>
                        <th class="col-desc-longa" style="display: none;">
                            <input type="text" name="f_desc_longa" value="{{ request('f_desc_longa') }}" class="filter-input" placeholder="Multi: FLEXIVEL..." form="formFilterCompras" onchange="document.getElementById('formFilterCompras').submit()">
                        </th>
                        <th class="col-produto-pai" style="display: none;">
                            <input type="text" name="f_prod_pai" value="{{ request('f_prod_pai') }}" class="filter-input" placeholder="Multi: QUADRO, 9510..." form="formFilterCompras" onchange="document.getElementById('formFilterCompras').submit()">
                        </th>
                        <th class="col-qtd-comprar"></th>
                        <th class="col-pedido-compra">
                            <input type="text" name="f_pedido_compra" value="{{ request('f_pedido_compra') }}" class="filter-input" placeholder="Multi: PC1, PC2..." form="formFilterCompras" onchange="document.getElementById('formFilterCompras').submit()">
                        </th>
                        <th class="col-fornecedor">
                            <input type="text" name="f_fornecedor" value="{{ request('f_fornecedor') }}" class="filter-input" placeholder="Multi: FORN A, FORN B..." form="formFilterCompras" onchange="document.getElementById('formFilterCompras').submit()">
                        </th>
                        <th class="col-valor-unitario"></th>
                        <th class="col-ipi"></th>
                        <th class="col-frete"></th>
                        <th class="col-data-pc"></th>
                        <th class="col-data-pagamento"></th>
                        <th class="col-solicitacao-compra"></th>
                        <th class="col-status-pagamento">
                            <input type="text" name="f_status_pagamento" value="{{ request('f_status_pagamento') }}" class="filter-input" placeholder="Multi: PAGO, FATURADO..." form="formFilterCompras" onchange="document.getElementById('formFilterCompras').submit()">
                        </th>
                        <th class="col-valor-total"></th>
                        <th class="col-acoes"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paginatedItems as $item)
                    @php
                        $estoqueId = $item['estoque_item_id'];
                    @endphp
                    <tr id="row_compra_{{ $estoqueId ?? $loop->index }}">
                        <td class="col-status-pcp">
                            <span class="badge {{ $item['status_pcp_badge'] }}">{{ $item['status_pcp'] }}</span>
                        </td>
                        <td class="col-pv"><strong>{{ $item['pedido_venda'] }}</strong></td>
                        <td class="col-cliente" style="font-size: 0.775rem;">{{ $item['cliente_obs'] }}</td>
                        <td class="col-codigo-produto"><strong>{{ $item['codigo_produto'] }}</strong></td>
                        <td class="col-descricao" style="font-size: 0.775rem;">{{ $item['descricao'] }}</td>
                        <td class="col-desc-longa" style="display: none;">
                            <span class="badge-desc-longa">{{ $item['descricao_longa'] ?? ($item['descricao'] ?? '-') }}</span>
                        </td>
                        <td class="col-produto-pai" style="display: none;">
                            <span class="badge-produto-pai">{{ $item['produto_pai'] ?? '-' }}</span>
                        </td>
                        <td class="col-qtd-comprar" style="text-align: center;">
                            <strong id="qtd_comprar_{{ $estoqueId }}" style="color: {{ $item['quantidade_comprar'] > 0 ? '#ef4444' : '#10b981' }};">
                                {{ $item['quantidade_comprar'] }}
                            </strong>
                        </td>

                        @if($estoqueId)
                        <!-- Campos Editáveis para Itens que estão no Banco MySQL Local -->
                        <td class="col-pedido-compra">
                            <input type="text" 
                                   name="items[{{ $estoqueId }}][pedido_compra]" 
                                   value="{{ $item['pedido_compra'] }}" 
                                   class="form-control" 
                                   placeholder="N° PC..."
                                   style="padding: 0.2rem 0.4rem; font-size: 0.75rem;">
                        </td>
                        <td class="col-fornecedor">
                            <input type="text" 
                                   name="items[{{ $estoqueId }}][codigo_fornecedor]" 
                                   value="{{ $item['codigo_fornecedor'] }}" 
                                   class="form-control" 
                                   placeholder="Cód / Nome Fornecedor..."
                                   style="padding: 0.2rem 0.4rem; font-size: 0.75rem;">
                        </td>
                        <td class="col-valor-unitario">
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
                        <td class="col-ipi">
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
                        <td class="col-frete">
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
                        <td class="col-data-pc">
                            <input type="date" 
                                   name="items[{{ $estoqueId }}][data_pc]" 
                                   value="{{ $item['data_pc'] ? (is_string($item['data_pc']) ? $item['data_pc'] : $item['data_pc']->format('Y-m-d')) : '' }}" 
                                   class="form-control" 
                                   style="padding: 0.15rem 0.3rem; font-size: 0.75rem;">
                        </td>
                        <td class="col-data-pagamento">
                            <input type="date" 
                                   name="items[{{ $estoqueId }}][data_pagamento]" 
                                   value="{{ $item['data_pagamento'] ? (is_string($item['data_pagamento']) ? $item['data_pagamento'] : $item['data_pagamento']->format('Y-m-d')) : '' }}" 
                                   class="form-control" 
                                   style="padding: 0.15rem 0.3rem; font-size: 0.75rem;">
                        </td>
                        <td class="col-solicitacao-compra">
                            <input type="text" 
                                   name="items[{{ $estoqueId }}][solicitacao_compra]" 
                                   value="{{ $item['solicitacao_compra'] }}" 
                                   class="form-control" 
                                   placeholder="N° SC..."
                                   style="padding: 0.2rem 0.4rem; font-size: 0.75rem;">
                        </td>
                        <td class="col-status-pagamento">
                            <select name="items[{{ $estoqueId }}][status_pagamento]" 
                                     class="form-select" 
                                     style="padding: 0.2rem 0.3rem; font-size: 0.75rem;">
                                <option value="PENDENTE" {{ $item['status_pagamento'] == 'PENDENTE' ? 'selected' : '' }}>PENDENTE</option>
                                <option value="PAGAMENTO ANTECIPADO" {{ $item['status_pagamento'] == 'PAGAMENTO ANTECIPADO' ? 'selected' : '' }}>PAG. ANTECIPADO</option>
                                <option value="FATURADO" {{ $item['status_pagamento'] == 'FATURADO' ? 'selected' : '' }}>FATURADO</option>
                                <option value="PAGO" {{ $item['status_pagamento'] == 'PAGO' ? 'selected' : '' }}>PAGO</option>
                            </select>
                        </td>
                        <td class="col-valor-total" style="text-align: right; font-weight: 700; color: #6ee7b7;" id="label_val_total_{{ $estoqueId }}">
                            R$ {{ number_format($item['valor_total'], 2, ',', '.') }}
                        </td>
                        <td class="col-acoes" style="text-align: center;">
                            <button type="button" 
                                    class="btn btn-primary" 
                                    style="padding: 0.2rem 0.5rem; font-size: 0.7rem;"
                                    onclick="solicitarSalvarSingleCompra({{ $estoqueId }})">
                                💾 Salvar
                            </button>
                        </td>
                        @else
                        <!-- Somente Leitura para itens trazidos da API Protheus que ainda não estão salvos no Estoque Local -->
                        <td colspan="11" style="text-align: center; color: var(--text-muted); font-size: 0.75rem; background: rgba(255,255,255,0.02);">
                            <em>Importe este item no Painel de Estoque (PCP) para habilitar edição financeira.</em>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="19" style="text-align: center; color: var(--text-muted); padding: 2rem;">
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
function checkColumnsVisibility() {
    const hasFilterPai = {{ request()->filled('f_prod_pai') ? 'true' : 'false' }};
    const showPai = localStorage.getItem('showColProdutoPai') === 'true' || hasFilterPai;
    
    document.querySelectorAll('.col-produto-pai').forEach(el => {
        el.style.display = showPai ? 'table-cell' : 'none';
    });
    
    const iconPai = document.getElementById('iconSquarePaiCompras');
    if (iconPai) iconPai.innerText = showPai ? '☑️' : '🔲';

    const hasFilterDesc = {{ request()->filled('f_desc_longa') ? 'true' : 'false' }};
    const showDesc = localStorage.getItem('showColDescLonga') === 'true' || hasFilterDesc;

    document.querySelectorAll('.col-desc-longa').forEach(el => {
        el.style.display = showDesc ? 'table-cell' : 'none';
    });

    const iconDesc = document.getElementById('iconSquareDescLongaCompras');
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

// Gestor de Visibilidade de Colunas (Excel) para Compras
const COMPRAS_COLUNAS_PADRAO = {
    'col-status-pcp': true,
    'col-pv': true,
    'col-cliente': true,
    'col-codigo-produto': true,
    'col-descricao': true,
    'col-desc-longa': false,
    'col-produto-pai': false,
    'col-qtd-comprar': true,
    'col-valor-total': true
};

function toggleMenuColunasCompras() {
    const el = document.getElementById('dropdownMenuColunasCompras');
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

document.addEventListener('click', function(e) {
    const menu = document.getElementById('dropdownMenuColunasCompras');
    if (menu && !menu.contains(e.target) && !e.target.closest('.dropdown')) {
        menu.style.display = 'none';
    }
});

function toggleColunaCompras(colClass, isChecked) {
    const elements = document.querySelectorAll('.' + colClass);
    elements.forEach(el => {
        el.style.display = isChecked ? '' : 'none';
    });
    let prefs = JSON.parse(localStorage.getItem('compras_colunas_visiveis') || '{}');
    prefs[colClass] = isChecked;
    localStorage.setItem('compras_colunas_visiveis', JSON.stringify(prefs));
}

function inicializarColunasCompras() {
    let prefs = JSON.parse(localStorage.getItem('compras_colunas_visiveis') || '{}');
    Object.keys(COMPRAS_COLUNAS_PADRAO).forEach(colClass => {
        let isChecked = prefs.hasOwnProperty(colClass) ? prefs[colClass] : COMPRAS_COLUNAS_PADRAO[colClass];
        const chk = document.getElementById('chk_' + colClass);
        if (chk) chk.checked = isChecked;
        toggleColunaCompras(colClass, isChecked);
    });
}

document.addEventListener('DOMContentLoaded', inicializarColunasCompras);
</script>
@endsection
