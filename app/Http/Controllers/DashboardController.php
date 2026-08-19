<?php

namespace App\Http\Controllers;

use App\Models\EstoqueItem;
use App\Models\CompraItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Dashboard com Indicadores Quantitativos e Financeiros (R$)
     */
    public function index()
    {
        // Métricas quantitativas de estoque
        $totalEstoque = EstoqueItem::count();
        $totalFalta = EstoqueItem::where('status', 'FALTA')->count();
        $totalFabrica = EstoqueItem::whereIn('status', ['FABRICA', 'FABRICAR INTERNO KANBAN'])->count();
        $totalSeparadoRetirado = EstoqueItem::whereIn('status', ['SEPARADO', 'RETIRADO'])->count();

        // Métricas financeiras (R$)
        $valorTotalGeral = floatval(CompraItem::sum('valor_total') ?? 0);
        $valorTotalPendente = floatval(CompraItem::where('status_pagamento', 'PENDENTE')->sum('valor_total') ?? 0);
        $valorTotalFaturado = floatval(CompraItem::where('status_pagamento', 'FATURADO')->sum('valor_total') ?? 0);
        $valorTotalPago = floatval(CompraItem::where('status_pagamento', 'PAGO')->sum('valor_total') ?? 0);
        $valorTotalAntecipado = floatval(CompraItem::where('status_pagamento', 'PAGAMENTO ANTECIPADO')->sum('valor_total') ?? 0);

        // Status PCP breakdown para Gráfico 1
        $statusEstoqueCounts = [
            'FALTA' => EstoqueItem::where('status', 'FALTA')->count(),
            'SEPARADO' => EstoqueItem::where('status', 'SEPARADO')->count(),
            'RETIRADO' => EstoqueItem::where('status', 'RETIRADO')->count(),
            'FABRICA' => EstoqueItem::where('status', 'FABRICA')->count(),
            'KANBAN' => EstoqueItem::where('status', 'FABRICAR INTERNO KANBAN')->count(),
        ];

        // Status Pagamento breakdown em R$ para Gráfico 2
        $statusComprasValores = [
            'PENDENTE' => $valorTotalPendente,
            'ANTECIPADO' => $valorTotalAntecipado,
            'FATURADO' => $valorTotalFaturado,
            'PAGO' => $valorTotalPago,
        ];

        // Top Pedidos por Valor Total (R$) para Gráfico 3
        $topPedidosValores = EstoqueItem::join('compras_items', 'estoque_items.id', '=', 'compras_items.estoque_item_id')
            ->select('estoque_items.pedido', DB::raw('SUM(compras_items.valor_total) as total_valor'))
            ->whereNotNull('estoque_items.pedido')
            ->where('estoque_items.pedido', '!=', '')
            ->groupBy('estoque_items.pedido')
            ->orderBy('total_valor', 'desc')
            ->limit(7)
            ->get();

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
            'topPedidosValores'
        ));
    }
}
