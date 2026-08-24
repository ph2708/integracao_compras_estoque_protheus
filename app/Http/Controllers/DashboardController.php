<?php

namespace App\Http\Controllers;

use App\Models\CompraItem;
use App\Models\EstoqueItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Exibe o Dashboard de Indicadores e Gráficos Gerenciais
     */
    public function index(Request $request)
    {
        $searchPedido = $request->get('pedido');
        $searchStatusPcp = $request->get('status_pcp');
        $searchStatusPagamento = $request->get('status_pagamento');
        $searchCliente = $request->get('search_cliente');

        // Query Base Estoque (Sem filtro de status PCP para permitir exibição correta dos cards de distribuição)
        $estoqueQueryBase = EstoqueItem::query();
        if ($searchPedido) {
            $estoqueQueryBase->where('pedido', 'like', '%' . $searchPedido . '%');
        }
        if ($searchStatusPagamento) {
            $estoqueQueryBase->whereHas('compraItem', function ($q) use ($searchStatusPagamento) {
                if (in_array($searchStatusPagamento, ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO'])) {
                    $q->whereIn('status_pagamento', ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO']);
                } else {
                    $q->where('status_pagamento', $searchStatusPagamento);
                }
            });
        }
        if ($searchCliente) {
            $terms = array_filter(array_map('trim', explode(',', $searchCliente)));
            if (!empty($terms)) {
                $estoqueQueryBase->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->orWhere('cliente_obs', 'like', '%' . $term . '%');
                    }
                });
            }
        }

        // Query Estoque Filtrada (Inclui filtro de status PCP se fornecido pelo usuário)
        $estoqueQuery = (clone $estoqueQueryBase);
        if ($searchStatusPcp) {
            $estoqueQuery->where('status', $searchStatusPcp);
        }

        // Query Base Compras
        $comprasQuery = CompraItem::query();
        if ($searchPedido) {
            $comprasQuery->whereHas('estoqueItem', function ($q) use ($searchPedido) {
                $q->where('pedido', 'like', '%' . $searchPedido . '%');
            });
        }
        if ($searchStatusPcp) {
            $comprasQuery->whereHas('estoqueItem', function ($q) use ($searchStatusPcp) {
                $q->where('status', $searchStatusPcp);
            });
        }
        if ($searchStatusPagamento) {
            if (in_array($searchStatusPagamento, ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO'])) {
                $comprasQuery->whereIn('status_pagamento', ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO']);
            } else {
                $comprasQuery->where('status_pagamento', $searchStatusPagamento);
            }
        }
        if ($searchCliente) {
            $terms = array_filter(array_map('trim', explode(',', $searchCliente)));
            if (!empty($terms)) {
                $comprasQuery->whereHas('estoqueItem', function ($q) use ($terms) {
                    $q->where(function ($subQ) use ($terms) {
                        foreach ($terms as $term) {
                            $subQ->orWhere('cliente_obs', 'like', '%' . $term . '%');
                        }
                    });
                });
            }
        }

        // Métricas quantitativas de estoque
        $totalEstoque = (clone $estoqueQuery)->count();
        $totalFalta = (clone $estoqueQueryBase)->where('status', 'FALTA')->count();
        $totalFabrica = (clone $estoqueQueryBase)->whereIn('status', ['FABRICA', 'FABRICAR INTERNO KANBAN'])->count();
        $totalSeparado = (clone $estoqueQueryBase)->where('status', 'SEPARADO')->count();

        // Métricas financeiras (R$)
        $valorTotalGeral = floatval((clone $comprasQuery)->sum('valor_total') ?? 0);
        $valorTotalPendente = floatval((clone $comprasQuery)->where('status_pagamento', 'PENDENTE')->sum('valor_total') ?? 0);
        $valorTotalFaturado = floatval((clone $comprasQuery)->where('status_pagamento', 'FATURADO')->sum('valor_total') ?? 0);
        $valorTotalPago = floatval((clone $comprasQuery)->where('status_pagamento', 'PAGO')->sum('valor_total') ?? 0);
        $valorTotalAntecipado = floatval((clone $comprasQuery)->whereIn('status_pagamento', ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO'])->sum('valor_total') ?? 0);

        // Valor Total dos Itens com Status SEPARADO
        $valorTotalSeparado = floatval(
            (clone $estoqueQueryBase)->where('status', 'SEPARADO')
                ->join('compras_items', 'estoque_items.id', '=', 'compras_items.estoque_item_id')
                ->sum('compras_items.valor_total') ?? 0
        );

        // Status PCP breakdown para Gráfico 1
        $statusEstoqueCounts = [
            'FALTA' => (clone $estoqueQueryBase)->where('status', 'FALTA')->count(),
            'SEPARADO' => (clone $estoqueQueryBase)->where('status', 'SEPARADO')->count(),
            'RETIRADO' => (clone $estoqueQueryBase)->where('status', 'RETIRADO')->count(),
            'FABRICA' => (clone $estoqueQueryBase)->where('status', 'FABRICA')->count(),
            'KANBAN' => (clone $estoqueQueryBase)->where('status', 'FABRICAR INTERNO KANBAN')->count(),
        ];

        // Status Pagamento breakdown em R$ para Gráfico 2
        $statusComprasValores = [
            'PENDENTE' => $valorTotalPendente,
            'PA' => $valorTotalAntecipado,
            'FATURADO' => $valorTotalFaturado,
            'PAGO' => $valorTotalPago,
        ];

        // Top Pedidos por Valor Total (R$)
        $topPedidosQuery = EstoqueItem::join('compras_items', 'estoque_items.id', '=', 'compras_items.estoque_item_id')
            ->select('estoque_items.pedido', DB::raw('SUM(compras_items.valor_total) as total_valor'))
            ->whereNotNull('estoque_items.pedido')
            ->where('estoque_items.pedido', '!=', '');

        if ($searchPedido) {
            $topPedidosQuery->where('estoque_items.pedido', 'like', '%' . $searchPedido . '%');
        }
        if ($searchStatusPcp) {
            $topPedidosQuery->where('estoque_items.status', $searchStatusPcp);
        }
        if ($searchStatusPagamento) {
            if (in_array($searchStatusPagamento, ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO'])) {
                $topPedidosQuery->whereIn('compras_items.status_pagamento', ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO']);
            } else {
                $topPedidosQuery->where('compras_items.status_pagamento', $searchStatusPagamento);
            }
        }
        if ($searchCliente) {
            $terms = array_filter(array_map('trim', explode(',', $searchCliente)));
            if (!empty($terms)) {
                $topPedidosQuery->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->orWhere('estoque_items.cliente_obs', 'like', '%' . $term . '%');
                    }
                });
            }
        }

        $topPedidosValores = $topPedidosQuery->groupBy('estoque_items.pedido')
            ->orderBy('total_valor', 'desc')
            ->limit(7)
            ->get();

        // Top Fornecedores por Quantidade a Comprar e Valor Total (R$) - Mapeia '0' ou vazio para 'SEM FORNECEDOR'
        $fornecedorExpr = "CASE WHEN compras_items.codigo_fornecedor IS NULL OR TRIM(compras_items.codigo_fornecedor) = '' OR TRIM(compras_items.codigo_fornecedor) = '0' THEN 'SEM FORNECEDOR' ELSE TRIM(compras_items.codigo_fornecedor) END";

        $topFornecedoresQuery = CompraItem::join('estoque_items', 'compras_items.estoque_item_id', '=', 'estoque_items.id')
            ->select(
                DB::raw("{$fornecedorExpr} as codigo_fornecedor"),
                DB::raw('SUM(GREATEST(0, estoque_items.quantidade - estoque_items.quantidade_estoque)) as total_qtd_comprar'),
                DB::raw('SUM(estoque_items.quantidade * compras_items.valor_unitario) as total_valor')
            )
            ->where('estoque_items.status', '!=', 'FECHADO');

        if ($searchPedido) {
            $topFornecedoresQuery->where('estoque_items.pedido', 'like', '%' . $searchPedido . '%');
        }
        if ($searchStatusPcp) {
            $topFornecedoresQuery->where('estoque_items.status', $searchStatusPcp);
        }
        if ($searchStatusPagamento) {
            if (in_array($searchStatusPagamento, ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO'])) {
                $topFornecedoresQuery->whereIn('compras_items.status_pagamento', ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO']);
            } else {
                $topFornecedoresQuery->where('compras_items.status_pagamento', $searchStatusPagamento);
            }
        }
        if ($searchCliente) {
            $terms = array_filter(array_map('trim', explode(',', $searchCliente)));
            if (!empty($terms)) {
                $topFornecedoresQuery->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->orWhere('estoque_items.cliente_obs', 'like', '%' . $term . '%');
                    }
                });
            }
        }

        $topFornecedoresValores = $topFornecedoresQuery->groupBy(DB::raw($fornecedorExpr))
            ->orderBy('total_qtd_comprar', 'desc')
            ->limit(7)
            ->get();

        // Métricas de Fechamento de OPs
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $opsFechadasMes = EstoqueItem::where('status', 'FECHADO')
            ->whereNotNull('op')
            ->where('op', '!=', '')
            ->whereMonth('fechada_em', $currentMonth)
            ->whereYear('fechada_em', $currentYear)
            ->distinct('op')
            ->count('op');

        // Histórico mensal de OPs fechadas nos últimos 6 meses (Qtd + Valor R$)
        $opsFechadasPorMesRaw = EstoqueItem::select(
                DB::raw("DATE_FORMAT(estoque_items.fechada_em, '%Y-%m') as mes_ano"),
                DB::raw("COUNT(DISTINCT estoque_items.op) as total_ops"),
                DB::raw("SUM(estoque_items.quantidade * compras_items.valor_unitario) as valor_total_ops")
            )
            ->leftJoin('compras_items', 'estoque_items.id', '=', 'compras_items.estoque_item_id')
            ->where('estoque_items.status', 'FECHADO')
            ->whereNotNull('estoque_items.op')
            ->where('estoque_items.op', '!=', '')
            ->whereNotNull('estoque_items.fechada_em')
            ->groupBy('mes_ano')
            ->orderBy('mes_ano', 'asc')
            ->limit(6)
            ->get();

        $opsFechadasPorMesLabels = [];
        $opsFechadasPorMesValues = [];
        $opsFechadasPorMesValoresRs = [];
        foreach ($opsFechadasPorMesRaw as $row) {
            $opsFechadasPorMesLabels[] = $row->mes_ano;
            $opsFechadasPorMesValues[] = (int) $row->total_ops;
            $opsFechadasPorMesValoresRs[] = floatval($row->valor_total_ops ?? 0);
        }

        // Lista de Pedidos para o Filtro Dropdown
        $pedidosDisponiveis = EstoqueItem::whereNotNull('pedido')
            ->where('pedido', '!=', '')
            ->distinct()
            ->pluck('pedido');

        return view('dashboard.index', compact(
            'totalEstoque',
            'totalFalta',
            'totalFabrica',
            'totalSeparado',
            'valorTotalGeral',
            'valorTotalPendente',
            'valorTotalFaturado',
            'valorTotalPago',
            'valorTotalAntecipado',
            'valorTotalSeparado',
            'opsFechadasMes',
            'opsFechadasPorMesLabels',
            'opsFechadasPorMesValues',
            'opsFechadasPorMesValoresRs',
            'statusEstoqueCounts',
            'statusComprasValores',
            'topPedidosValores',
            'topFornecedoresValores',
            'pedidosDisponiveis',
            'searchPedido',
            'searchStatusPcp',
            'searchStatusPagamento',
            'searchCliente'
        ));
    }
}
