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
        <a href="{{ route('fechamento-op.index', ['tab' => 'prontas', 'search_op' => $searchOp, 'search_pedido' => $searchPedido, 'search_cliente' => $searchCliente, 'search_descricao' => $searchDescricao]) }}" 
           class="btn {{ $tab === 'prontas' ? 'btn-primary' : 'btn-secondary' }}" 
           style="{{ $tab === 'prontas' ? 'background-color: #059669; border-color: #059669;' : '' }}">
            🟢 Prontas para Fechar (0 FALTA)
        </a>
        <a href="{{ route('fechamento-op.index', ['tab' => 'pendentes', 'search_op' => $searchOp, 'search_pedido' => $searchPedido, 'search_cliente' => $searchCliente, 'search_descricao' => $searchDescricao]) }}" 
           class="btn {{ $tab === 'pendentes' ? 'btn-primary' : 'btn-secondary' }}"
           style="{{ $tab === 'pendentes' ? 'background-color: #d97706; border-color: #d97706;' : '' }}">
            ⏳ Pendentes de Compras
        </a>
        <a href="{{ route('fechamento-op.index', ['tab' => 'encerradas', 'search_op' => $searchOp, 'search_pedido' => $searchPedido, 'search_cliente' => $searchCliente, 'search_descricao' => $searchDescricao]) }}" 
           class="btn {{ $tab === 'encerradas' ? 'btn-primary' : 'btn-secondary' }}"
           style="{{ $tab === 'encerradas' ? 'background-color: #4f46e5; border-color: #4f46e5;' : '' }}">
            🔒 OPs Encerradas (Histórico)
        </a>
        <a href="{{ route('fechamento-op.index', ['tab' => 'todas', 'search_op' => $searchOp, 'search_pedido' => $searchPedido, 'search_cliente' => $searchCliente, 'search_descricao' => $searchDescricao]) }}" 
           class="btn {{ $tab === 'todas' ? 'btn-primary' : 'btn-secondary' }}">
            📋 Todas as OPs
        </a>
    </div>

    <!-- Barra de Filtros de Pesquisa -->
    <form action="{{ route('fechamento-op.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end;">
        <input type="hidden" name="tab" value="{{ $tab }}">

        <div style="flex: 1; min-width: 140px;">
            <label class="form-label">N° da OP ✏️</label>
            <input type="text" name="search_op" value="{{ $searchOp }}" class="form-control" placeholder="Ex: 018662...">
        </div>

        <div style="flex: 1; min-width: 140px;">
            <label class="form-label">Pedido de Venda ✏️</label>
            <input type="text" name="search_pedido" value="{{ $searchPedido }}" class="form-control" placeholder="Ex: 006614...">
        </div>

        <div style="flex: 1.5; min-width: 180px;">
            <label class="form-label">Nome do Cliente ✏️</label>
            <input type="text" name="search_cliente" value="{{ $searchCliente }}" class="form-control" placeholder="Ex: CONDOMINIO...">
        </div>

        <div style="flex: 2; min-width: 200px;">
            <label class="form-label">Descrição do Produto / Pai ✏️</label>
            <input type="text" name="search_descricao" value="{{ $searchDescricao }}" class="form-control" placeholder="Multi: CABO, CHAVE...">
        </div>

        <div style="display: flex; gap: 0.4rem; align-items: flex-end;">
            <button type="submit" class="btn btn-primary" style="padding: 0.45rem 0.85rem;">
                🔍 Filtrar
            </button>
            @if($searchOp || $searchPedido || $searchCliente || $searchDescricao)
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

<!-- Tabela de OPs agrupadas -->
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
                    <th style="width: 40px; text-align: center;">
                        <input type="checkbox" id="chkSelectAllOps" onchange="toggleSelectAllOps(this.checked)" style="cursor: pointer;">
                    </th>
                    <th style="min-width: 140px;">Número da OP</th>
                    <th>Pedido de Venda</th>
                    <th>Nome do Cliente</th>
                    <th style="text-align: center;">Total de Itens</th>
                    <th style="text-align: center;">Status PCP dos Componentes</th>
                    <th style="text-align: center;">Situação da OP</th>
                    <th style="text-align: center; min-width: 160px;">Ações de Encerramento</th>
                </tr>
            </thead>
            <tbody>
                @forelse($paginatedOps as $itemOp)
                <tr>
                    <td style="text-align: center;">
                        <input type="checkbox" 
                               name="op_numbers[]" 
                               value="{{ $itemOp['op'] }}" 
                               form="formBatchCloseOps" 
                               class="chk-op-select" 
                               {{ !$itemOp['is_elegivel'] ? 'disabled' : '' }} 
                               style="cursor: pointer;">
                    </td>
                    <td>
                        <strong style="color: #c084fc; font-size: 0.9rem; cursor: pointer; text-decoration: underline;" 
                                onclick='abrirModalConferenciaOp({{ json_encode($itemOp) }})' 
                                title="Clique para abrir a janela de conferência dos componentes desta OP">
                            🔍 OP #{{ $itemOp['op'] }}
                        </strong>
                    </td>
                    <td><strong>{{ $itemOp['pedido'] }}</strong></td>
                    <td style="font-size: 0.775rem;">{{ $itemOp['cliente_obs'] }}</td>
                    <td style="text-align: center;">
                        <span class="badge badge-faturado" style="font-size: 0.8rem;">{{ $itemOp['total_itens'] }} itens</span>
                    </td>
                    <td style="text-align: center;">
                        <div style="display: flex; gap: 0.35rem; justify-content: center; flex-wrap: wrap;">
                            @if($itemOp['qtd_falta'] > 0)
                                <span class="badge badge-falta" title="Possui necessidades pendentes">{{ $itemOp['qtd_falta'] }} FALTA</span>
                            @endif
                            @if($itemOp['qtd_separado'] > 0)
                                <span class="badge badge-separado">{{ $itemOp['qtd_separado'] }} SEPARADO</span>
                            @endif
                            @if($itemOp['qtd_retirado'] > 0)
                                <span class="badge badge-retirado">{{ $itemOp['qtd_retirado'] }} RETIRADO</span>
                            @endif
                            @if($itemOp['qtd_fabrica'] > 0)
                                <span class="badge badge-fabrica">{{ $itemOp['qtd_fabrica'] }} FABRICA</span>
                            @endif
                            @if($itemOp['qtd_fechado'] > 0)
                                <span class="badge badge-kanban">{{ $itemOp['qtd_fechado'] }} FECHADO</span>
                            @endif
                        </div>
                    </td>
                    <td style="text-align: center;">
                        @if($itemOp['is_fechada'])
                            <span class="badge badge-kanban" style="background: rgba(99, 102, 241, 0.2); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.4);">
                                🔒 ENCERRADA {{ $itemOp['fechada_em'] ? '('.$itemOp['fechada_em'].')' : '' }}
                            </span>
                        @elseif($itemOp['is_elegivel'])
                            <span class="badge badge-separado" style="font-size: 0.75rem;">
                                🟢 PRONTA PARA FECHAR
                            </span>
                        @else
                            <span class="badge badge-falta" style="font-size: 0.75rem;">
                                ⏳ PENDENTE DE COMPRAS
                            </span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        @if($itemOp['is_elegivel'])
                            @if(auth()->user()->canCloseOp())
                                <form action="{{ route('fechamento-op.fechar', $itemOp['op']) }}" method="POST" onsubmit="return confirm('Deseja encerrar definitivamente a OP #{{ $itemOp['op'] }}? Todos os {{ $itemOp['total_itens'] }} itens serão arquivados das tabelas operacionais.')">
                                    @csrf
                                    <button type="submit" class="btn btn-primary" style="padding: 0.35rem 0.75rem; font-size: 0.75rem; background-color: #059669; border-color: #059669;">
                                        🔒 Encerrar OP
                                    </button>
                                </form>
                            @else
                                <span style="font-size: 0.7rem; color: var(--text-muted);" title="Apenas usuários autorizados podem fechar OPs">
                                    🔒 Sem Permissão
                                </span>
                            @endif
                        @elseif($itemOp['is_fechada'])
                            <span style="font-size: 0.75rem; color: #818cf8; font-weight: 500;">
                                ✓ Arquivada
                            </span>
                        @else
                            <span style="font-size: 0.75rem; color: #fca5a5;">
                                🛑 Requer comprar itens FALTA
                            </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                        Nenhuma Ordem de Produção encontrada nesta categoria.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginação -->
    <div class="pagination-container">
        <div>
            Exibindo <strong>{{ $paginatedOps->firstItem() ?? 0 }}</strong> a <strong>{{ $paginatedOps->lastItem() ?? 0 }}</strong> de <strong>{{ $paginatedOps->total() }}</strong> Ordens de Produção
        </div>
        <div>
            {{ $paginatedOps->links() }}
        </div>
    </div>
</div>

<!-- Modal: Janela de Conferência de Itens da OP -->
<div class="card" id="modalConferenciaOp" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10000; width: 95%; max-width: 950px; max-height: 90vh; overflow-y: auto; border-color: rgba(168, 85, 247, 0.8); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.9);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
        <h3 style="font-size: 1.1rem; color: #c084fc;" id="modalConferenciaTitle">🔍 Conferência de Itens da Ordem de Produção</h3>
        <button type="button" class="btn btn-secondary" style="padding: 0.2rem 0.5rem;" onclick="fecharModalConferenciaOp()">✕</button>
    </div>

    <!-- Info Banner da OP -->
    <div style="display: flex; gap: 1.25rem; flex-wrap: wrap; margin-bottom: 1rem; background: rgba(255,255,255,0.03); padding: 0.75rem 1rem; border-radius: 0.5rem; border: 1px solid var(--border-color);">
        <div><span style="color: var(--text-muted); font-size: 0.75rem;">Número da OP:</span> <strong id="modalConfOpNum" style="color: #c084fc;">-</strong></div>
        <div><span style="color: var(--text-muted); font-size: 0.75rem;">Pedido de Venda:</span> <strong id="modalConfPedido" style="color: #60a5fa;">-</strong></div>
        <div><span style="color: var(--text-muted); font-size: 0.75rem;">Nome do Cliente:</span> <strong id="modalConfCliente" style="color: #fcd34d;">-</strong></div>
        <div><span style="color: var(--text-muted); font-size: 0.75rem;">Total de Componentes:</span> <span id="modalConfTotalItens" class="badge badge-faturado">0</span></div>
    </div>

    <!-- Tabela de Componentes da OP -->
    <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
        <table>
            <thead>
                <tr>
                    <th>Status PCP</th>
                    <th>Código Produto</th>
                    <th>Descrição</th>
                    <th>Produto Pai Concatenado</th>
                    <th style="text-align: center;">Qtd OP</th>
                    <th style="text-align: center;">Qtd Estoque</th>
                    <th style="text-align: center;">Qtd Comprar</th>
                    <th>Observação do PCP</th>
                </tr>
            </thead>
            <tbody id="tbodyConferenciaOp">
                <!-- Preenchido dinamicamente via JS -->
            </tbody>
        </table>
    </div>

    <div style="display: flex; justify-content: flex-end; margin-top: 1.25rem;">
        <button type="button" class="btn btn-secondary" onclick="fecharModalConferenciaOp()">Fechar Janela de Conferência</button>
    </div>
</div>
<div id="modalOverlayConferenciaOp" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.8); z-index: 9999;" onclick="fecharModalConferenciaOp()"></div>

<script>
function toggleSelectAllOps(checked) {
    document.querySelectorAll('.chk-op-select:not(:disabled)').forEach(chk => {
        chk.checked = checked;
    });
}

function abrirModalConferenciaOp(dataOp) {
    document.getElementById('modalConferenciaTitle').innerText = `🔍 Janela de Conferência de Componentes - OP #${dataOp.op}`;
    document.getElementById('modalConfOpNum').innerText = `#${dataOp.op}`;
    document.getElementById('modalConfPedido').innerText = dataOp.pedido;
    document.getElementById('modalConfCliente').innerText = dataOp.cliente_obs;
    document.getElementById('modalConfTotalItens').innerText = `${dataOp.total_itens} item(ns)`;

    const tbody = document.getElementById('tbodyConferenciaOp');
    tbody.innerHTML = '';

    dataOp.itens.forEach(item => {
        const tr = document.createElement('tr');

        const badgeClass = item.status === 'FALTA' ? 'badge-falta' :
                           item.status === 'SEPARADO' ? 'badge-separado' :
                           item.status === 'RETIRADO' ? 'badge-retirado' :
                           item.status === 'FABRICA' ? 'badge-fabrica' : 'badge-kanban';

        const qtdOp = parseFloat(item.quantidade || 0);
        const qtdEst = parseFloat(item.quantidade_estoque || 0);
        const qtdComprar = Math.max(0, qtdOp - qtdEst);

        tr.innerHTML = `
            <td><span class="badge ${badgeClass}">${item.status}</span></td>
            <td><strong>${item.codigo_produto}</strong></td>
            <td style="font-size: 0.775rem;">${item.descricao || '-'}</td>
            <td style="font-size: 0.775rem;"><span class="badge-produto-pai">${item.produto_pai || '-'}</span></td>
            <td style="text-align: center;"><strong>${qtdOp}</strong></td>
            <td style="text-align: center; color: #fcd34d;">${qtdEst}</td>
            <td style="text-align: center; font-weight: bold; color: ${qtdComprar > 0 ? '#ef4444' : '#10b981'};">${qtdComprar}</td>
            <td style="font-size: 0.75rem; color: var(--text-muted);">${item.observacao_estoque || '-'}</td>
        `;
        tbody.appendChild(tr);
    });

    document.getElementById('modalConferenciaOp').style.display = 'block';
    document.getElementById('modalOverlayConferenciaOp').style.display = 'block';
}

function fecharModalConferenciaOp() {
    document.getElementById('modalConferenciaOp').style.display = 'none';
    document.getElementById('modalOverlayConferenciaOp').style.display = 'none';
}
</script>
@endsection
