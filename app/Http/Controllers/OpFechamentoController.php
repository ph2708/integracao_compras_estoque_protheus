<?php

namespace App\Http\Controllers;

use App\Models\EstoqueItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpFechamentoController extends Controller
{
    /**
     * Lista as Ordens de Produção agrupadas para fechamento
     */
    public function index(Request $request)
    {
        $searchOp = $request->get('search_op');
        $tab = $request->get('tab', 'prontas'); // prontas, pendentes, encerradas, todas

        // Busca todas as OPs agrupadas do Estoque
        $query = EstoqueItem::select('op')
            ->whereNotNull('op')
            ->where('op', '!=', '');

        if ($searchOp) {
            $query->where('op', 'like', '%' . $searchOp . '%');
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

        return view('fechamento_op.index', compact('opsList', 'searchOp', 'tab'));
    }

    /**
     * Processa o encerramento/fechamento de uma OP inteira
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
}
