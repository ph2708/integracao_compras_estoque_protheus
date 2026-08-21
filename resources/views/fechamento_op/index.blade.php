@extends('layouts.app')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700;">🔒 Painel de Fechamento de Ordens de Produção (OP)</h1>
        <p style="color: var(--text-muted); font-size: 0.8rem;">Encerramento de OPs atendidas sem pendências de compra. OPs encerradas são arquivadas das tabelas do dia a dia.</p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="{{ route('compras.index') }}" class="btn btn-secondary">🛒 Painel de Compras</a>
        <a href="{{ route('estoque.index') }}" class="btn btn-secondary">📦 Painel de Estoque</a>
    </div>
</div>

<!-- Filtros & Abas de Navegação -->
<div class="card" style="margin-bottom: 1.25rem; border-color: rgba(168, 85, 247, 0.4);">
    <!-- Abas Superior -->
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.85rem;">
        <a href="{{ route('fechamento-op.index', ['tab' => 'prontas', 'search_op' => $searchOp, 'search_pedido' => $searchPedido, 'search_cliente' => $searchCliente, 'search_codigo' => $searchCodigo, 'search_descricao' => $searchDescricao]) }}" 
           class="btn {{ $tab === 'prontas' ? 'btn-primary' : 'btn-secondary' }}" 
           style="{{ $tab === 'prontas' ? 'background-color: #059669; border-color: #059669;' : '' }}">
            🟢 Prontas para Fechar (0 FALTA)
        </a>
        <a href="{{ route('fechamento-op.index', ['tab' => 'pendentes', 'search_op' => $searchOp, 'search_pedido' => $searchPedido, 'search_cliente' => $searchCliente, 'search_codigo' => $searchCodigo, 'search_descricao' => $searchDescricao]) }}" 
           class="btn {{ $tab === 'pendentes' ? 'btn-primary' : 'btn-secondary' }}"
           style="{{ $tab === 'pendentes' ? 'background-color: #d97706; border-color: #d97706;' : '' }}">
            ⏳ Pendentes de Compras
        </a>
        <a href="{{ route('fechamento-op.index', ['tab' => 'encerradas', 'search_op' => $searchOp, 'search_pedido' => $searchPedido, 'search_cliente' => $searchCliente, 'search_codigo' => $searchCodigo, 'search_descricao' => $searchDescricao]) }}" 
           class="btn {{ $tab === 'encerradas' ? 'btn-primary' : 'btn-secondary' }}"
           style="{{ $tab === 'encerradas' ? 'background-color: #4f46e5; border-color: #4f46e5;' : '' }}">
            🔒 OPs Encerradas (Histórico)
        </a>
        <a href="{{ route('fechamento-op.index', ['tab' => 'items_expandidos', 'search_op' => $searchOp, 'search_pedido' => $searchPedido, 'search_cliente' => $searchCliente, 'search_codigo' => $searchCodigo, 'search_descricao' => $searchDescricao]) }}" 
           class="btn {{ $tab === 'items_expandidos' ? 'btn-primary' : 'btn-secondary' }}"
           style="{{ $tab === 'items_expandidos' ? 'background-color: #8b5cf6; border-color: #8b5cf6;' : '' }}">
            🔍 OPs Itens Expandidos
        </a>
        <a href="{{ route('fechamento-op.index', ['tab' => 'todas', 'search_op' => $searchOp, 'search_pedido' => $searchPedido, 'search_cliente' => $searchCliente, 'search_codigo' => $searchCodigo, 'search_descricao' => $searchDescricao]) }}" 
           class="btn {{ $tab === 'todas' ? 'btn-primary' : 'btn-secondary' }}">
            📋 Todas as OPs
        </a>
    </div>

    <!-- Barra de Filtros de Pesquisa -->
    <form action="{{ route('fechamento-op.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end;">
        <input type="hidden" name="tab" value="{{ $tab }}">

        <div style="flex: 1; min-width: 130px;">
            <label class="form-label">N° da OP ✏️</label>
            <input type="text" name="search_op" value="{{ $searchOp }}" class="form-control" placeholder="Ex: 018662...">
        </div>

        <div style="flex: 1; min-width: 130px;">
            <label class="form-label">Pedido de Venda ✏️</label>
            <input type="text" name="search_pedido" value="{{ $searchPedido }}" class="form-control" placeholder="Ex: 006614...">
        </div>

        <div style="flex: 1.3; min-width: 160px;">
            <label class="form-label">Nome do Cliente ✏️</label>
            <input type="text" name="search_cliente" value="{{ $searchCliente }}" class="form-control" placeholder="Ex: CONDOMINIO...">
        </div>

        <div style="flex: 1.3; min-width: 150px;">
            <label class="form-label">Código Produto ✏️</label>
            <input type="text" name="search_codigo" value="{{ $searchCodigo }}" class="form-control" placeholder="Multi: 6184, 1050...">
        </div>

        <div style="flex: 1.8; min-width: 180px;">
            <label class="form-label">Descrição Componente ✏️</label>
            <input type="text" name="search_descricao" value="{{ $searchDescricao }}" class="form-control" placeholder="Multi: CABO, BASE...">
        </div>

        <div style="display: flex; gap: 0.4rem; align-items: flex-end;">
            <button type="submit" class="btn btn-primary" style="padding: 0.45rem 0.85rem;">
                🔍 Filtrar
            </button>
            @if($searchOp || $searchPedido || $searchCliente || $searchCodigo || $searchDescricao)
                <a href="{{ route('fechamento-op.index', ['tab' => $tab]) }}" class="btn btn-secondary" style="padding: 0.45rem 0.65rem;">
                    ✕ Limpar
                </a>
            @endif
        </div>
    </form>
</div>

<!-- Form para Encerramento em Lote -->
<form action="{{ route('fechamento-op.fechar-lote') }}" method="POST" id="formBatchCloseOps">
    @csrf
</form>

@if($tab === 'items_expandidos')
<!-- VISÃO: OPs Itens Expandidos (Lista Individual de Componentes de OPs Encerradas) -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; flex-wrap: wrap; gap: 0.75rem;">
        <h3 style="font-size: 1rem; color: #c084fc;">📂 Itens Expandidos de Ordens de Produção Encerradas</h3>
        <span class="badge" style="background-color: #8b5cf6; font-size: 0.8rem; padding: 0.35rem 0.65rem;">
            Total de Itens: {{ $paginatedItems->total() }}
        </span>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Número da OP</th>
                    <th>Pedido de Venda</th>
                    <th>Nome do Cliente</th>
                    <th>Código Produto</th>
                    <th>Descrição Componente</th>
                    <th>Produto Pai</th>
                    <th style="text-align: center;">Qtd. OP</th>
                    <th style="text-align: center;">Status Item</th>
                    <th style="text-align: center;">Data Encerramento</th>
                </tr>
            </thead>
            <tbody>
                @forelse($paginatedItems as $item)
                    <tr>
                        <td>
                            <strong style="color: #c084fc; cursor: pointer;" onclick="abrirModalConferenciaOp('{{ $item->op }}')">
                                🔍 OP #{{ $item->op }}
                            </strong>
                        </td>
                        <td><strong>{{ $item->pedido ?? '-' }}</strong></td>
                        <td><small style="color: #94a3b8;">{{ $item->cliente_obs ?? '-' }}</small></td>
                        <td><code style="color: #cbd5e1; font-weight: 600;">{{ $item->codigo_produto }}</code></td>
                        <td>
                            <strong>{{ $item->descricao }}</strong>
                            @if($item->descricao_longa)
                                <br><small style="color: #64748b;">{{ Str::limit($item->descricao_longa, 60) }}</small>
                            @endif
                        </td>
                        <td><small style="color: #94a3b8;">{{ $item->produto_pai ?? '-' }}</small></td>
                        <td style="text-align: center; font-weight: 700; color: #fcd34d;">{{ number_format($item->quantidade, 0, ',', '.') }}</td>
                        <td style="text-align: center;">
                            <span class="badge badge-separado" style="background-color: #4f46e5;">🔒 FECHADO</span>
                        </td>
                        <td style="text-align: center;">
                            <small style="color: #a7f3d0; font-weight: 600;">
                                🔒 {{ $item->fechada_em ? \Carbon\Carbon::parse($item->fechada_em)->format('d/m/Y H:i') : '-' }}
                            </small>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 2rem; color: #94a3b8;">
                            Nenhum item de Ordem de Produção encerrada foi encontrado para os filtros pesquisados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginação da Lista de Itens -->
    @if($paginatedItems && $paginatedItems->hasPages())
        <div style="margin-top: 1.25rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div style="font-size: 0.8rem; color: #94a3b8;">
                Exibindo de <strong>{{ $paginatedItems->firstItem() }}</strong> até <strong>{{ $paginatedItems->lastItem() }}</strong> de <strong>{{ $paginatedItems->total() }}</strong> itens expandidos.
            </div>
            <div>
                {{ $paginatedItems->links() }}
            </div>
        </div>
    @endif
</div>

@else
<!-- VISÃO PADRÃO: Tabelas de OPs Agrupadas -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; flex-wrap: wrap; gap: 0.75rem;">
        <h3 style="font-size: 1rem; color: #a5b4fc;">📋 Lista de Ordens de Produção</h3>
        @if(auth()->user()->canCloseOp())
            <button type="submit" 
                    form="formBatchCloseOps" 
                    class="btn btn-primary" 
                    style="padding: 0.45rem 0.95rem; font-size: 0.8rem; background-color: #059669; border-color: #059669;" 
                    onclick="return confirm('Deseja encerrar simultaneamente todas as OPs selecionadas?')">
                🔒 Encerrar OPs Selecionadas em Lote
            </button>
        @endif
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 38px; text-align: center;">
                        <input type="checkbox" id="selectAllOps" onchange="toggleSelectAllOps(this)" style="cursor: pointer;">
                    </th>
                    <th>Número da OP</th>
                    <th>Pedido de Venda</th>
                    <th>Nome do Cliente</th>
                    <th style="text-align: center;">Total de Itens</th>
                    <th style="text-align: center;">Status PCP dos Componentes</th>
                    <th style="text-align: center;">Situação da OP</th>
                    <th style="text-align: center;">Ações de Encerramento</th>
                </tr>
            </thead>
            <tbody>
                @forelse($paginatedOps as $opData)
                    <tr>
                        <td style="text-align: center;">
                            @if($opData['is_elegivel'])
                                <input type="checkbox" name="op_numbers[]" value="{{ $opData['op'] }}" form="formBatchCloseOps" class="chk-op-item" style="cursor: pointer;">
                            @else
                                <input type="checkbox" disabled style="opacity: 0.3; cursor: not-allowed;" title="OP possui pendências de compra ou já está encerrada">
                            @endif
                        </td>
                        <td>
                            <strong style="color: #6366f1; cursor: pointer;" onclick="abrirModalConferenciaOp('{{ $opData['op'] }}')">
                                🔍 OP #{{ $opData['op'] }}
                            </strong>
                        </td>
                        <td><strong>{{ $opData['pedido'] }}</strong></td>
                        <td><small style="color: #94a3b8;">{{ $opData['cliente_obs'] }}</small></td>
                        <td style="text-align: center; font-weight: 700;">{{ $opData['total_itens'] }} itens</td>
                        <td style="text-align: center;">
                            @if($opData['qtd_falta'] > 0)
                                <span class="badge badge-falta" style="font-size: 0.75rem;">{{ $opData['qtd_falta'] }} FALTA</span>
                            @endif
                            @if($opData['qtd_separado'] > 0)
                                <span class="badge badge-separado" style="font-size: 0.75rem;">{{ $opData['qtd_separado'] }} SEPARADO</span>
                            @endif
                            @if($opData['qtd_retirado'] > 0)
                                <span class="badge badge-retirado" style="font-size: 0.75rem;">{{ $opData['qtd_retirado'] }} RETIRADO</span>
                            @endif
                            @if($opData['qtd_fabrica'] > 0)
                                <span class="badge badge-fabrica" style="font-size: 0.75rem;">{{ $opData['qtd_fabrica'] }} FÁBRICA</span>
                            @endif
                            @if($opData['qtd_fechado'] > 0)
                                <span class="badge badge-separado" style="background-color: #4f46e5; font-size: 0.75rem;">{{ $opData['qtd_fechado'] }} FECHADO</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            @if($opData['is_fechada'])
                                <span class="badge" style="background-color: #374151; color: #9ca3af; padding: 0.4rem 0.75rem; font-size: 0.75rem;">
                                    🔒 ENCERRADA ({{ $opData['fechada_em'] ?? 'Histórico' }})
                                </span>
                            @elseif($opData['is_elegivel'])
                                <span class="badge" style="background-color: #065f46; color: #a7f3d0; padding: 0.4rem 0.75rem; font-size: 0.75rem;">
                                    🟢 PRONTA PARA ENCERRAMENTO
                                </span>
                            @else
                                <span class="badge" style="background-color: #92400e; color: #fef3c7; padding: 0.4rem 0.75rem; font-size: 0.75rem;">
                                    ⏳ AGUARDANDO COMPRAS
                                </span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            @if($opData['is_fechada'])
                                <span style="font-size: 0.75rem; color: #6b7280;">✓ Arquivada</span>
                            @elseif($opData['is_elegivel'])
                                @if(auth()->user()->canCloseOp())
                                    <form action="{{ route('fechamento-op.fechar', $opData['op']) }}" method="POST" style="display: inline-block;">
                                        @csrf
                                        <button type="submit" class="btn btn-primary" style="padding: 0.25rem 0.65rem; font-size: 0.75rem; background-color: #059669; border-color: #059669;" onclick="return confirm('Confirmar o encerramento definitivo da OP #{{ $opData['op'] }}?')">
                                            🔒 Encerrar OP
                                        </button>
                                    </form>
                                @else
                                    <span class="badge badge-falta" style="font-size: 0.75rem;">Sem Permissão</span>
                                @endif
                            @else
                                <span style="font-size: 0.75rem; color: #ef4444;">Falta {{ $opData['qtd_falta'] }} item(ns)</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2rem; color: #94a3b8;">
                            Nenhuma Ordem de Produção encontrada para os filtros selecionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginação da Tabela Agrupada -->
    @if($paginatedOps->hasPages())
        <div style="margin-top: 1.25rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div style="font-size: 0.8rem; color: #94a3b8;">
                Exibindo de <strong>{{ $paginatedOps->firstItem() }}</strong> até <strong>{{ $paginatedOps->lastItem() }}</strong> de <strong>{{ $paginatedOps->total() }}</strong> Ordens de Produção.
            </div>
            <div>
                {{ $paginatedOps->links() }}
            </div>
        </div>
    @endif
</div>
@endif

<!-- Modal Pop-up de Conferência de Componentes da OP -->
<div id="modalConferenciaOp" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(4px); z-index: 9999; justify-content: center; align-items: center; padding: 1.5rem;">
    <div style="background: #1e293b; border: 1px solid #475569; border-radius: 12px; max-width: 900px; width: 100%; max-height: 85vh; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
        <!-- Cabeçalho do Modal -->
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center; background: #0f172a; border-top-left-radius: 12px; border-top-right-radius: 12px;">
            <h2 id="modalOpTitulo" style="font-size: 1.25rem; font-weight: 700; color: #f8fafc; margin: 0;">🔍 Componentes da OP</h2>
            <button type="button" onclick="fecharModalConferenciaOp()" style="background: transparent; border: none; color: #94a3b8; font-size: 1.5rem; cursor: pointer;">✕</button>
        </div>

        <!-- Conteúdo do Modal (Tabela de Componentes) -->
        <div style="padding: 1.5rem; overflow-y: auto; flex: 1;">
            <div id="modalOpLoading" style="text-align: center; padding: 2rem; color: #94a3b8;">
                ⏳ Carregando itens da OP...
            </div>
            <div id="modalOpConteudo" style="display: none;">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Código Produto</th>
                            <th>Descrição Componente</th>
                            <th>Produto Pai</th>
                            <th style="text-align: center;">Qtd. OP</th>
                            <th style="text-align: center;">Qtd. Estoque</th>
                            <th style="text-align: center;">Status PCP</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyModalItensOp">
                        <!-- Preenchido dinamicamente via JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Rodapé do Modal -->
        <div style="padding: 1rem 1.5rem; border-top: 1px solid #334155; display: flex; justify-content: flex-end; background: #0f172a; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
            <button type="button" onclick="fecharModalConferenciaOp()" class="btn btn-secondary">Fechar Janela</button>
        </div>
    </div>
</div>

<script>
// Toggle de seleção em lote para OPs elegíveis
function toggleSelectAllOps(masterChk) {
    const checkboxes = document.querySelectorAll('.chk-op-item');
    checkboxes.forEach(chk => {
        chk.checked = masterChk.checked;
    });
}

// Modal Pop-up de Conferência de Componentes por OP
const OPS_JSON_DATA = {!! json_encode($paginatedOps->items()) !!};

function abrirModalConferenciaOp(opNumber) {
    const modal = document.getElementById('modalConferenciaOp');
    const titulo = document.getElementById('modalOpTitulo');
    const loading = document.getElementById('modalOpLoading');
    const conteudo = document.getElementById('modalOpConteudo');
    const tbody = document.getElementById('tbodyModalItensOp');

    titulo.innerText = '🔍 Componentes da Ordem de Produção #' + opNumber;
    loading.style.display = 'block';
    conteudo.style.display = 'none';
    tbody.innerHTML = '';
    modal.style.display = 'flex';

    let opObj = OPS_JSON_DATA.find(o => o.op === opNumber);

    if (opObj && opObj.itens) {
        renderItensModal(opObj.itens);
    } else {
        fetch('{{ route("fechamento-op.index") }}?tab=todas&search_op=' + opNumber)
            .then(res => res.text())
            .then(html => {
                loading.style.display = 'none';
                conteudo.style.display = 'block';
            })
            .catch(() => {
                loading.innerHTML = '❌ Não foi possível carregar os componentes desta OP.';
            });
    }
}

function renderItensModal(itens) {
    const loading = document.getElementById('modalOpLoading');
    const conteudo = document.getElementById('modalOpConteudo');
    const tbody = document.getElementById('tbodyModalItensOp');

    tbody.innerHTML = '';
    itens.forEach(item => {
        const tr = document.createElement('tr');
        let statusBadgeClass = 'badge-falta';
        if (item.status === 'SEPARADO') statusBadgeClass = 'badge-separado';
        else if (item.status === 'RETIRADO') statusBadgeClass = 'badge-retirado';
        else if (item.status === 'FABRICA' || item.status === 'FABRICAR INTERNO KANBAN') statusBadgeClass = 'badge-fabrica';
        else if (item.status === 'FECHADO') statusBadgeClass = 'badge-separado';

        tr.innerHTML = `
            <td><code>${item.codigo_produto}</code></td>
            <td>
                <strong>${item.descricao || '-'}</strong>
                ${item.descricao_longa ? `<br><small style="color: #94a3b8;">${item.descricao_longa}</small>` : ''}
            </td>
            <td><small style="color: #94a3b8;">${item.produto_pai || '-'}</small></td>
            <td style="text-align: center; font-weight: 700; color: #fcd34d;">${item.quantidade}</td>
            <td style="text-align: center;">${item.quantidade_estoque || 0}</td>
            <td style="text-align: center;">
                <span class="badge ${statusBadgeClass}">${item.status}</span>
            </td>
        `;
        tbody.appendChild(tr);
    });

    loading.style.display = 'none';
    conteudo.style.display = 'block';
}

function fecharModalConferenciaOp() {
    const modal = document.getElementById('modalConferenciaOp');
    modal.style.display = 'none';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') fecharModalConferenciaOp();
});
</script>
@endsection
