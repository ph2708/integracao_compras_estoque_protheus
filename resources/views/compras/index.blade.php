@extends('layouts.app')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700;">🛒 Painel de Compras</h1>
        <p style="color: var(--text-muted); font-size: 0.8rem;">Gerenciamento financeiro e controle de fornecedores com lançamento em modal sem barra de rolagem.</p>
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

<!-- Tabela de Itens de Compras (Enxuta sem Scroll Horizontal) -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; flex-wrap: wrap; gap: 0.5rem;">
        <h3 style="font-size: 1rem;">
            @if($searchPv)
                📋 Itens do Pedido de Venda: <span style="color: var(--accent);">{{ $searchPv }}</span> (Filial: {{ $searchFilial ?: 'Todas' }})
            @else
                📋 Todos os Itens em Compras
            @endif
        </h3>
        @if($searchPv || request()->hasAny(['f_produto', 'f_descricao', 'f_op', 'f_cliente', 'f_status_pcp', 'f_pedido_compra', 'f_fornecedor', 'f_status_pagamento']))
            <a href="{{ route('compras.index') }}" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                ✕ Limpar Todos os Filtros
            </a>
        @endif
    </div>

    <!-- Filtros por Colunas -->
    <form action="{{ route('compras.index') }}" method="GET" id="formFilterCompras">
        @if($searchPv)<input type="hidden" name="pedido_venda" value="{{ $searchPv }}">@endif
        @if($searchFilial)<input type="hidden" name="filial" value="{{ $searchFilial }}">@endif
    </form>

    <div class="table-responsive" style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>PV</th>
                    <th>Nome do Cliente (C2_OBS)</th>
                    <th>Código Produto</th>
                    <th>Descrição</th>
                    <th style="color: #6ee7b7; text-align: center;">Qtd Comprar</th>
                    <th>Status PCP</th>
                    <th>Pedido Compra</th>
                    <th>Status Pagamento</th>
                    <th style="color: #6ee7b7; text-align: right;">Valor Total (Calc)</th>
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
                    <td>
                        <strong style="font-size: 0.85rem; color: #f8fafc;">
                            {{ $item['pedido_venda'] }}
                        </strong>
                    </td>
                    <td>
                        <span style="font-size: 0.75rem; color: #cbd5e1; font-weight: 500;">
                            {{ $item['cliente_obs'] }}
                        </span>
                    </td>
                    <td><strong>{{ $item['codigo_produto'] }}</strong></td>
                    <td style="font-size: 0.775rem;">{{ $item['descricao'] }}</td>
                    <td style="text-align: center;">
                        <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 0.3rem; padding: 0.2rem 0.4rem; display: inline-block;">
                            <strong style="color: #6ee7b7; font-size: 0.85rem;">{{ floatval($item['quantidade_comprar']) }}</strong>
                        </div>
                    </td>
                    <td>
                        <span class="badge {{ $item['status_pcp_badge'] }}">
                            {{ $item['status_pcp'] }}
                        </span>
                    </td>
                    <td>
                        <span style="font-weight: 600; color: #38bdf8;">
                            {{ $item['pedido_compra'] ?: '-' }}
                        </span>
                    </td>
                    <td>
                        @php
                            $badgePagamento = match($item['status_pagamento']) {
                                'PAGAMENTO ANTECIPADO' => 'badge-antecipado',
                                'FATURADO' => 'badge-faturado',
                                'PAGO' => 'badge-pago',
                                default => 'badge-pendente'
                            };
                        @endphp
                        <span class="badge {{ $badgePagamento }}">
                            {{ $item['status_pagamento'] }}
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <strong style="color: #6ee7b7; font-size: 0.85rem;">
                            R$ {{ number_format(floatval($item['valor_total']), 2, ',', '.') }}
                        </strong>
                    </td>
                    <td style="text-align: center;">
                        @if($item['no_estoque'] && $item['compra_id'])
                            <button type="button" 
                                    class="btn btn-primary" 
                                    style="padding: 0.3rem 0.6rem; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 0.25rem;"
                                    onclick='abrirModalEdicaoCompra({{ json_encode($item) }})'>
                                ✏️ Lançar / Editar
                            </button>
                        @else
                            <span style="color: var(--text-muted); font-size: 0.7rem;"><em>Aguardando Estoque</em></span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                        @if($searchPv)
                            Nenhum item encontrado para os filtros aplicados no Pedido de Venda <strong>{{ $searchPv }}</strong>.
                        @else
                            Nenhum item cadastrado ou encontrado nos filtros aplicados.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

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

<!-- MODAL ESPAÇOSO E ORGANIZADO DE LANÇAMENTO FINANCEIRO DE COMPRAS -->
<div class="card" id="modalEdicaoCompra" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10000; width: 95%; max-width: 800px; max-height: 90vh; overflow-y: auto; border-color: rgba(99, 102, 241, 0.8); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.85);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
        <h3 style="font-size: 1.15rem; color: #a5b4fc; margin: 0;" id="modalTitleCompra">🛒 Lançamento Financeiro de Compras</h3>
        <button type="button" class="btn btn-secondary" style="padding: 0.2rem 0.5rem;" onclick="fecharModalEdicaoCompra()">✕</button>
    </div>

    <form action="" method="POST" id="formModalUpdateCompra">
        @csrf
        @method('PUT')

        <!-- Bloco 1: Dados do Insumo (Somente Leitura) -->
        <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 0.5rem; padding: 0.75rem 1rem; margin-bottom: 1rem;">
            <h4 style="font-size: 0.85rem; color: #38bdf8; margin-top: 0; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">📦 1. Identificação do Insumo</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem; font-size: 0.8rem;">
                <div><strong>PV:</strong> <span id="lbl_pv" style="color: #f8fafc;">-</span></div>
                <div><strong>Cliente:</strong> <span id="lbl_cliente" style="color: #cbd5e1;">-</span></div>
                <div><strong>Produto:</strong> <span id="lbl_produto" style="color: var(--accent);">-</span></div>
                <div><strong>OP:</strong> <span id="lbl_op" style="color: #a5b4fc;">-</span></div>
                <div><strong>Qtd OP:</strong> <span id="lbl_qtd_op">-</span></div>
                <div><strong>Qtd Estoque:</strong> <span id="lbl_qtd_est" style="color: #fcd34d;">-</span></div>
                <div><strong>Qtd a Comprar:</strong> <strong id="lbl_qtd_comprar" style="color: #6ee7b7; font-size: 0.9rem;">-</strong></div>
            </div>
            <div style="margin-top: 0.5rem; font-size: 0.8rem;">
                <strong>Descrição:</strong> <span id="lbl_descricao" style="color: #cbd5e1;">-</span>
            </div>
        </div>

        <!-- Bloco 2: Dados do Pedido de Compra Protheus -->
        <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 0.5rem; padding: 0.75rem 1rem; margin-bottom: 1rem;">
            <h4 style="font-size: 0.85rem; color: #a5b4fc; margin-top: 0; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">🏭 2. Dados do Pedido de Compra (Protheus)</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.85rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Número do Pedido de Compra</label>
                    <div style="display: flex; gap: 0.25rem;">
                        <input type="text" name="pedido_compra" id="m_pedido_compra" class="form-control" placeholder="Ex: PED-9012">
                        <button type="button" class="btn btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;" onclick="buscarFornecedorProtheusModal()">🔍 Buscar Protheus</button>
                    </div>
                    <small id="m_status_protheus" style="font-size: 0.65rem; display: block; margin-top: 0.25rem;"></small>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Código / Nome do Fornecedor</label>
                    <input type="text" name="codigo_fornecedor" id="m_codigo_fornecedor" class="form-control" placeholder="Ex: 001290 (WEG DRIVES)">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Condição de Pagamento</label>
                    <input type="text" name="condicao_pagamento" id="m_condicao_pagamento" class="form-control" placeholder="Ex: 30/60 DDL">
                </div>
            </div>
        </div>

        <!-- Bloco 3: Valores, Frete & Prazos -->
        <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 0.5rem; padding: 0.75rem 1rem; margin-bottom: 1rem;">
            <h4 style="font-size: 0.85rem; color: #fcd34d; margin-top: 0; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">💰 3. Valores, Frete & Datas</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.85rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Valor Unitário (R$) *</label>
                    <input type="number" step="0.01" name="valor_unitario" id="m_valor_unitario" class="form-control" placeholder="0.00" oninput="calcularTotalModal()">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">IPI (%)</label>
                    <input type="number" step="0.01" name="ipi" id="m_ipi" class="form-control" placeholder="0%" oninput="calcularTotalModal()">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Frete (R$)</label>
                    <input type="number" step="0.01" name="frete" id="m_frete" class="form-control" placeholder="0.00" oninput="calcularTotalModal()">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Data do PC</label>
                    <input type="date" name="data_pc" id="m_data_pc" class="form-control">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Data de Pagamento</label>
                    <input type="date" name="data_pagamento" id="m_data_pagamento" class="form-control">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Solicitação de Compra (SC)</label>
                    <input type="text" name="solicitacao_compra" id="m_solicitacao_compra" class="form-control" placeholder="N° SC">
                </div>
            </div>
        </div>

        <!-- Bloco 4: Resumo Calculado & Status de Pagamento -->
        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 0.5rem; padding: 0.85rem 1rem; margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span style="font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; display: block;">Valor Total Calculado (Qtd Comprar x Preço + IPI + Frete)</span>
                <strong id="m_valor_total_display" style="font-size: 1.6rem; color: #6ee7b7;">R$ 0,00</strong>
            </div>

            <div class="form-group" style="margin-bottom: 0; min-width: 200px;">
                <label class="form-label">Status do Pagamento *</label>
                <select name="status_pagamento" id="m_status_pagamento" class="form-select" style="font-weight: 600;">
                    <option value="PENDENTE">PENDENTE</option>
                    <option value="PAGAMENTO ANTECIPADO">PAGAMENTO ANTECIPADO</option>
                    <option value="FATURADO">FATURADO</option>
                    <option value="PAGO">PAGO</option>
                </select>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
            <button type="button" class="btn btn-secondary" onclick="fecharModalEdicaoCompra()">Cancelar</button>
            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1.25rem;">💾 Salvar Dados Financeiros</button>
        </div>
    </form>
</div>
<div id="modalOverlayCompra" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.75); z-index: 9999;" onclick="fecharModalEdicaoCompra()"></div>

<script>
let currentModalItem = null;

function abrirModalEdicaoCompra(item) {
    currentModalItem = item;

    // Atualizar URL de destino do formulário
    document.getElementById('formModalUpdateCompra').action = `/compras/${item.compra_id}`;

    // Preencher rótulos fixos do bloco 1
    document.getElementById('lbl_pv').innerText = item.pedido_venda || '-';
    document.getElementById('lbl_cliente').innerText = item.cliente_obs || '-';
    document.getElementById('lbl_produto').innerText = item.codigo_produto || '-';
    document.getElementById('lbl_op').innerText = item.op || '-';
    document.getElementById('lbl_qtd_op').innerText = item.quantidade || '1';
    document.getElementById('lbl_qtd_est').innerText = item.quantidade_estoque || '0';
    document.getElementById('lbl_qtd_comprar').innerText = item.quantidade_comprar || '1';
    document.getElementById('lbl_descricao').innerText = item.descricao || '-';

    // Preencher formulário
    document.getElementById('m_pedido_compra').value = item.pedido_compra || '';
    document.getElementById('m_codigo_fornecedor').value = item.codigo_fornecedor || '';
    document.getElementById('m_condicao_pagamento').value = item.condicao_pagamento || '';
    document.getElementById('m_valor_unitario').value = item.valor_unitario || '';
    document.getElementById('m_ipi').value = item.ipi || '';
    document.getElementById('m_frete').value = item.frete || '';
    document.getElementById('m_data_pc').value = item.data_pc || '';
    document.getElementById('m_data_pagamento').value = item.data_pagamento || '';
    document.getElementById('m_solicitacao_compra').value = item.solicitacao_compra || '';
    document.getElementById('m_status_pagamento').value = item.status_pagamento || 'PENDENTE';

    document.getElementById('m_status_protheus').innerText = '';

    calcularTotalModal();

    document.getElementById('modalEdicaoCompra').style.display = 'block';
    document.getElementById('modalOverlayCompra').style.display = 'block';
}

function fecharModalEdicaoCompra() {
    document.getElementById('modalEdicaoCompra').style.display = 'none';
    document.getElementById('modalOverlayCompra').style.display = 'none';
    currentModalItem = null;
}

function calcularTotalModal() {
    if (!currentModalItem) return;

    const qtdComprar = parseFloat(currentModalItem.quantidade_comprar) || 1;
    const valUnit = parseFloat(document.getElementById('m_valor_unitario').value) || 0;
    const ipiVal = parseFloat(document.getElementById('m_ipi').value) || 0;
    const freteVal = parseFloat(document.getElementById('m_frete').value) || 0;

    const total = (valUnit * qtdComprar) + (valUnit * qtdComprar * (ipiVal / 100)) + freteVal;

    document.getElementById('m_valor_total_display').innerText = 'R$ ' + total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

async function buscarFornecedorProtheusModal() {
    if (!currentModalItem) return;

    const pedidoVal = document.getElementById('m_pedido_compra').value.trim();
    const statusLabel = document.getElementById('m_status_protheus');

    if (!pedidoVal) {
        statusLabel.innerText = '⚠️ Informe o Pedido de Compra.';
        statusLabel.style.color = '#fcd34d';
        return;
    }

    statusLabel.innerText = '⏳ Buscando no Protheus (SC7010)...';
    statusLabel.style.color = 'var(--accent)';

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    try {
        const response = await fetch('{{ route("compras.consultar-protheus") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ pedido_compra: pedidoVal, codigo_produto: currentModalItem.codigo_produto })
        });

        const result = await response.json();

        if (result.success) {
            statusLabel.innerText = '✅ Dados do Fornecedor carregados!';
            statusLabel.style.color = '#6ee7b7';

            if (result.data.codigo_fornecedor) {
                document.getElementById('m_codigo_fornecedor').value = result.data.codigo_fornecedor;
            }
            if (result.data.condicao_pagamento) {
                document.getElementById('m_condicao_pagamento').value = result.data.condicao_pagamento;
            }
        } else {
            statusLabel.innerText = '⚠️ Pedido não encontrado no Protheus.';
            statusLabel.style.color = '#fcd34d';
        }
    } catch (e) {
        statusLabel.innerText = '❌ Erro de conexão ao buscar no Protheus.';
        statusLabel.style.color = '#fca5a5';
    }
}
</script>
@endsection
