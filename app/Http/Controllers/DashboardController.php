<?php

namespace App\Http\Controllers;

use App\Models\EstoqueItem;
use App\Models\CompraItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Dashboard com Indicadores Quantitativos, Financeiros (R$) e Filtro por Pedido de Venda
     */
    public function index(Request $request)
    {
        $searchPedido = $request->get('pedido');

        // Query Base Estoque
        $estoqueQuery = EstoqueItem::query();
        if ($searchPedido) {
            $estoqueQuery->where('pedido', 'like', '%' . $searchPedido . '%');
        }

        // Query Base Compras
        $comprasQuery = CompraItem::query();
        if ($searchPedido) {
            $comprasQuery->whereHas('estoqueItem', function ($q) use ($searchPedido) {
                $q->where('pedido', 'like', '%' . $searchPedido . '%');
            });
        }

        // Métricas quantitativas de estoque
        $totalEstoque = (clone $estoqueQuery)->count();
        $totalFalta = (clone $estoqueQuery)->where('status', 'FALTA')->count();
        $totalFabrica = (clone $estoqueQuery)->whereIn('status', ['FABRICA', 'FABRICAR INTERNO KANBAN'])->count();
        $totalSeparadoRetirado = (clone $estoqueQuery)->whereIn('status', ['SEPARADO', 'RETIRADO'])->count();

        // Métricas financeiras (R$)
        $valorTotalGeral = floatval((clone $comprasQuery)->sum('valor_total') ?? 0);
        $valorTotalPendente = floatval((clone $comprasQuery)->where('status_pagamento', 'PENDENTE')->sum('valor_total') ?? 0);
        $valorTotalFaturado = floatval((clone $comprasQuery)->where('status_pagamento', 'FATURADO')->sum('valor_total') ?? 0);
        $valorTotalPago = floatval((clone $comprasQuery)->where('status_pagamento', 'PAGO')->sum('valor_total') ?? 0);
        $valorTotalAntecipado = floatval((clone $comprasQuery)->where('status_pagamento', 'PAGAMENTO ANTECIPADO')->sum('valor_total') ?? 0);

        // Status PCP breakdown para Gráfico 1
        $statusEstoqueCounts = [
            'FALTA' => (clone $estoqueQuery)->where('status', 'FALTA')->count(),
            'SEPARADO' => (clone $estoqueQuery)->where('status', 'SEPARADO')->count(),
            'RETIRADO' => (clone $estoqueQuery)->where('status', 'RETIRADO')->count(),
            'FABRICA' => (clone $estoqueQuery)->where('status', 'FABRICA')->count(),
            'KANBAN' => (clone $estoqueQuery)->where('status', 'FABRICAR INTERNO KANBAN')->count(),
        ];

        // Status Pagamento breakdown em R$ para Gráfico 2
        $statusComprasValores = [
            'PENDENTE' => $valorTotalPendente,
            'ANTECIPADO' => $valorTotalAntecipado,
            'FATURADO' => $valorTotalFaturado,
            'PAGO' => $valorTotalPago,
        ];

        // Top Pedidos por Valor Total (R$) para Gráfico 3
        $topPedidosQuery = EstoqueItem::join('compras_items', 'estoque_items.id', '=', 'compras_items.estoque_item_id')
            ->select('estoque_items.pedido', DB::raw('SUM(compras_items.valor_total) as total_valor'))
            ->whereNotNull('estoque_items.pedido')
            ->where('estoque_items.pedido', '!=', '');

        if ($searchPedido) {
            $topPedidosQuery->where('estoque_items.pedido', 'like', '%' . $searchPedido . '%');
        }

        $topPedidosValores = $topPedidosQuery->groupBy('estoque_items.pedido')
            ->orderBy('total_valor', 'desc')
            ->limit(7)
            ->get();

        // Lista de Pedidos para o Filtro Dropdown
        $pedidosDisponiveis = EstoqueItem::whereNotNull('pedido')
            ->where('pedido', '!=', '')
            ->distinct()
            ->pluck('pedido');

        return view('dashboard.index', compact(
            'totalEstoque',
            'totalFalta',
            'totalFabrica',
            'totalSeparadoRetirado',
            'valorTotalGeral',
            'valorTotalPendente',
            'valorTotalFaturado',
            'valorTotalPago',
            'valorTotalAntecipado',
            'statusEstoqueCounts',
            'statusComprasValores',
            'topPedidosValores',
            'pedidosDisponiveis',
            'searchPedido'
        ));
    }
}
