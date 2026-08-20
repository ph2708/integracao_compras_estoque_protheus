<?php

namespace App\Http\Controllers;

use App\Models\EstoqueItem;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OpFechamentoController extends Controller
{
    /**
     * Lista as Ordens de Produção agrupadas para fechamento com suporte a Paginação e Filtros
     */
    public function index(Request $request)
    {
        $searchOp = $request->get('search_op');
        $searchPedido = $request->get('search_pedido');
        $searchCliente = $request->get('search_cliente');
        $searchDescricao = $request->get('search_descricao');
        $tab = $request->get('tab', 'prontas'); // prontas, pendentes, encerradas, todas

        // Busca todas as OPs agrupadas do Estoque com filtros aplicados
        $query = EstoqueItem::select('op')
            ->whereNotNull('op')
            ->where('op', '!=', '');

        if ($searchOp) {
            $terms = array_filter(array_map('trim', explode(',', $searchOp)));
            if (!empty($terms)) {
                $query->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->orWhere('op', 'like', '%' . $term . '%');
                    }
                });
            }
        }

        if ($searchPedido) {
            $terms = array_filter(array_map('trim', explode(',', $searchPedido)));
            if (!empty($terms)) {
                $query->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->orWhere('pedido', 'like', '%' . $term . '%');
                    }
                });
            }
        }

        if ($searchCliente) {
            $terms = array_filter(array_map('trim', explode(',', $searchCliente)));
            if (!empty($terms)) {
                $query->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->orWhere('cliente_obs', 'like', '%' . $term . '%');
                    }
                });
            }
        }

        if ($searchDescricao) {
            $terms = array_filter(array_map('trim', explode(',', $searchDescricao)));
            if (!empty($terms)) {
                $query->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->orWhere('descricao', 'like', '%' . $term . '%')
                          ->orWhere('descricao_longa', 'like', '%' . $term . '%')
                          ->orWhere('produto_pai', 'like', '%' . $term . '%');
                    }
                });
            }
        }

        $opsGrouped = $query->groupBy('op')->pluck('op');

        $opsList = collect();

        foreach ($opsGrouped as $opNumber) {
            $itensOp = EstoqueItem::where('op', $opNumber)->get();
            if ($itensOp->isEmpty()) continue;

            $totalItens = $itensOp->count();
            $qtdFalta = $itensOp->where('status', 'FALTA')->count();
            $qtdSeparado = $itensOp->where('status', 'SEPARADO')->count();
            $qtdRetirado = $itensOp->where('status', 'RETIRADO')->count();
            $qtdFabrica = $itensOp->whereIn('status', ['FABRICA', 'FABRICAR INTERNO KANBAN'])->count();
            $qtdFechado = $itensOp->where('status', 'FECHADO')->count();

            $isFechada = ($qtdFechado === $totalItens);
            $isElegivel = (!$isFechada && $qtdFalta === 0);

            // Filtro por Abas
            if ($tab === 'prontas' && !$isElegivel) continue;
            if ($tab === 'pendentes' && ($isFechada || $isElegivel)) continue;
            if ($tab === 'encerradas' && !$isFechada) continue;

            $primeiroItem = $itensOp->first();

            $opsList->push([
                'op' => $opNumber,
                'pedido' => $primeiroItem->pedido ?? '-',
                'cliente_obs' => $primeiroItem->cliente_obs ?? '-',
                'total_itens' => $totalItens,
                'qtd_falta' => $qtdFalta,
                'qtd_separado' => $qtdSeparado,
                'qtd_retirado' => $qtdRetirado,
                'qtd_fabrica' => $qtdFabrica,
                'qtd_fechado' => $qtdFechado,
                'is_fechada' => $isFechada,
                'is_elegivel' => $isElegivel,
                'fechada_em' => $primeiroItem->fechada_em ? $primeiroItem->fechada_em->format('d/m/Y H:i') : null,
                'itens' => $itensOp,
            ]);
        }

        // Paginação da Coleção de OPs (15 por página)
        $perPage = 15;
        $page = $request->get('page', 1);
        $paginatedOps = new LengthAwarePaginator(
            $opsList->forPage($page, $perPage)->values(),
            $opsList->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('fechamento_op.index', compact('paginatedOps', 'searchOp', 'searchPedido', 'searchCliente', 'searchDescricao', 'tab'));
    }

    /**
     * Processa o encerramento/fechamento de uma OP individual
     */
    public function fecharOp(Request $request, $opNumber)
    {
        $user = auth()->user();
        if (!$user || !$user->canCloseOp()) {
            return redirect()->back()->with('error', '⛔ Você não possui permissão para encerrar Ordens de Produção!');
        }

        $itensOp = EstoqueItem::where('op', $opNumber)->get();

        if ($itensOp->isEmpty()) {
            return redirect()->back()->with('error', "Ordem de Produção #{$opNumber} não encontrada.");
        }

        $qtdFalta = $itensOp->where('status', 'FALTA')->count();
        if ($qtdFalta > 0) {
            return redirect()->back()->with('error', "⚠️ A OP #{$opNumber} ainda possui {$qtdFalta} item(ns) com status FALTA e não pode ser encerrada.");
        }

        // Atualiza todos os itens da OP para FECHADO
        EstoqueItem::where('op', $opNumber)->update([
            'status' => 'FECHADO',
            'fechada_em' => now(),
            'fechada_por' => $user->id,
        ]);

        return redirect()->back()->with('success', "🔒 Ordem de Produção #{$opNumber} encerrada com sucesso! Todos os {$itensOp->count()} itens foram arquivados das visões diárias.");
    }

    /**
     * Encerramento em Lote de Múltiplas OPs Selecionadas
     */
    public function fecharOpsLote(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->canCloseOp()) {
            return redirect()->back()->with('error', '⛔ Você não possui permissão para encerrar Ordens de Produção!');
        }

        $opNumbers = $request->input('op_numbers', []);

        if (empty($opNumbers)) {
            return redirect()->back()->with('error', 'Nenhuma Ordem de Produção foi selecionada para fechamento.');
        }

        $closedCount = 0;
        $skippedCount = 0;

        foreach ($opNumbers as $opNumber) {
            $itensOp = EstoqueItem::where('op', $opNumber)->get();
            if ($itensOp->isEmpty()) continue;

            $qtdFalta = $itensOp->where('status', 'FALTA')->count();
            if ($qtdFalta > 0) {
                $skippedCount++;
                continue;
            }

            EstoqueItem::where('op', $opNumber)->update([
                'status' => 'FECHADO',
                'fechada_em' => now(),
                'fechada_por' => $user->id,
            ]);

            $closedCount++;
        }

        if ($closedCount > 0) {
            $msg = "🔒 Total de {$closedCount} Ordem(ns) de Produção encerrada(s) com sucesso!";
            if ($skippedCount > 0) {
                $msg .= " ({$skippedCount} OP(s) ignorada(s) por possuírem itens em FALTA).";
            }
            return redirect()->back()->with('success', $msg);
        }

        return redirect()->back()->with('error', 'Nenhuma das OPs selecionadas pôde ser encerrada (possuem pendências de compra).');
    }
}
