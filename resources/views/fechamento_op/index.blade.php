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
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <!-- Abas -->
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="{{ route('fechamento-op.index', ['tab' => 'prontas', 'search_op' => $searchOp]) }}" 
               class="btn {{ $tab === 'prontas' ? 'btn-primary' : 'btn-secondary' }}" 
               style="{{ $tab === 'prontas' ? 'background-color: #059669;' : '' }}">
                🟢 Prontas para Fechar (0 FALTA)
            </a>
            <a href="{{ route('fechamento-op.index', ['tab' => 'pendentes', 'search_op' => $searchOp]) }}" 
               class="btn {{ $tab === 'pendentes' ? 'btn-primary' : 'btn-secondary' }}"
               style="{{ $tab === 'pendentes' ? 'background-color: #d97706;' : '' }}">
                ⏳ Pendentes de Compras
            </a>
            <a href="{{ route('fechamento-op.index', ['tab' => 'encerradas', 'search_op' => $searchOp]) }}" 
               class="btn {{ $tab === 'encerradas' ? 'btn-primary' : 'btn-secondary' }}"
               style="{{ $tab === 'encerradas' ? 'background-color: #4f46e5;' : '' }}">
                🔒 OPs Encerradas (Histórico)
            </a>
            <a href="{{ route('fechamento-op.index', ['tab' => 'todas', 'search_op' => $searchOp]) }}" 
               class="btn {{ $tab === 'todas' ? 'btn-primary' : 'btn-secondary' }}">
                📋 Todas as OPs
            </a>
        </div>

        <!-- Busca por Número de OP -->
        <form action="{{ route('fechamento-op.index') }}" method="GET" style="display: flex; gap: 0.5rem; flex: 1; max-width: 320px;">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="text" 
                   name="search_op" 
                   value="{{ $searchOp }}" 
                   class="form-control" 
                   placeholder="Buscar por N° da OP..." 
                   style="padding: 0.4rem 0.65rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.4rem 0.75rem;">🔍</button>
            @if($searchOp)
                <a href="{{ route('fechamento-op.index', ['tab' => $tab]) }}" class="btn btn-secondary" style="padding: 0.4rem 0.65rem;">✕</a>
            @endif
        </form>
    </div>
</div>

<!-- Tabela de OPs agrupadas -->
<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
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
                @forelse($opsList as $itemOp)
                <tr>
                    <td>
                        <strong style="color: #c084fc; font-size: 0.9rem;">OP #{{ $itemOp['op'] }}</strong>
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
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                        Nenhuma Ordem de Produção encontrada nesta categoria.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
