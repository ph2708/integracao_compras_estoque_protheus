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
    /**
     * Auxiliar para aplicar filtro com múltiplos valores separados por vírgula e preenchimento de zeros em pedidos
     */
    private function applyMultiFilter($query, string $column, ?string $value)
    {
        if (is_null($value) || trim($value) === '') {
            return;
        }

        $tokens = array_filter(array_map('trim', explode(',', $value)));
        if (empty($tokens)) {
            return;
        }

        $isPedidoCol = str_contains($column, 'pedido');

        $query->where(function ($q) use ($column, $tokens, $isPedidoCol) {
            foreach ($tokens as $idx => $tok) {
                if ($isPedidoCol && is_numeric($tok) && strlen($tok) < 6) {
                    $padded = str_pad($tok, 6, '0', STR_PAD_LEFT);
                    if ($idx === 0) {
                        $q->where(function($subQ) use ($column, $tok, $padded) {
                            $subQ->where($column, 'like', '%' . $tok . '%')
                                 ->orWhere($column, 'like', '%' . $padded . '%');
                        });
                    } else {
                        $q->orWhere(function($subQ) use ($column, $tok, $padded) {
                            $subQ->where($column, 'like', '%' . $tok . '%')
                                 ->orWhere($column, 'like', '%' . $padded . '%');
                        });
                    }
                } else {
                    if ($idx === 0) {
                        $q->where($column, 'like', '%' . $tok . '%');
                    } else {
                        $q->orWhere($column, 'like', '%' . $tok . '%');
                    }
                }
            }
        });
    }

    /**
     * Exibe o Dashboard de Indicadores e Gráficos Gerenciais
     */
    public function index(Request $request)
    {
        $searchPedido = $request->get('pedido');
        $searchStatusPcp = $request->get('status_pcp');
        $searchStatusPagamento = $request->get('status_pagamento');
        $searchCliente = $request->get('search_cliente');

        // Query Base Estoque
        $estoqueQueryBase = EstoqueItem::query();
        $this->applyMultiFilter($estoqueQueryBase, 'estoque_items.pedido', $searchPedido);
        $this->applyMultiFilter($estoqueQueryBase, 'estoque_items.cliente_obs', $searchCliente);
        if ($searchStatusPagamento) {
            $estoqueQueryBase->whereHas('compraItem', function ($q) use ($searchStatusPagamento) {
                if (in_array($searchStatusPagamento, ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO'])) {
                    $q->whereIn('status_pagamento', ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO']);
                } else {
                    $q->where('status_pagamento', $searchStatusPagamento);
                }
            });
        }

        // Query Estoque Filtrada (Inclui filtro de status PCP se fornecido pelo usuário)
        $estoqueQuery = (clone $estoqueQueryBase);
        if ($searchStatusPcp) {
            $this->applyMultiFilter($estoqueQuery, 'estoque_items.status', $searchStatusPcp);
        }

        // Query Base Compras
        $comprasQuery = CompraItem::query()->join('estoque_items', 'compras_items.estoque_item_id', '=', 'estoque_items.id');
        $this->applyMultiFilter($comprasQuery, 'estoque_items.pedido', $searchPedido);
        $this->applyMultiFilter($comprasQuery, 'estoque_items.cliente_obs', $searchCliente);
        if ($searchStatusPcp) {
            $this->applyMultiFilter($comprasQuery, 'estoque_items.status', $searchStatusPcp);
        }
        if ($searchStatusPagamento) {
            if (in_array($searchStatusPagamento, ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO'])) {
                $comprasQuery->whereIn('compras_items.status_pagamento', ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO']);
            } else {
                $comprasQuery->where('compras_items.status_pagamento', $searchStatusPagamento);
            }
        }

        // Métricas quantitativas de estoque
        $totalEstoque = (clone $estoqueQuery)->count();
        $totalFalta = (clone $estoqueQueryBase)->where('estoque_items.status', 'FALTA')->count();
        $totalFabrica = (clone $estoqueQueryBase)->whereIn('estoque_items.status', ['FABRICA', 'FABRICAR INTERNO KANBAN'])->count();
        $totalSeparado = (clone $estoqueQueryBase)->where('estoque_items.status', 'SEPARADO')->count();

        // Métricas financeiras (R$)
        $valorTotalGeral = floatval((clone $comprasQuery)->sum('compras_items.valor_total') ?? 0);
        $valorTotalPendente = floatval((clone $comprasQuery)->where('compras_items.status_pagamento', 'PENDENTE')->sum('compras_items.valor_total') ?? 0);
        $valorTotalFaturado = floatval((clone $comprasQuery)->where('compras_items.status_pagamento', 'FATURADO')->sum('compras_items.valor_total') ?? 0);
        $valorTotalPago = floatval((clone $comprasQuery)->where('compras_items.status_pagamento', 'PAGO')->sum('compras_items.valor_total') ?? 0);
        $valorTotalAntecipado = floatval((clone $comprasQuery)->whereIn('compras_items.status_pagamento', ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO'])->sum('compras_items.valor_total') ?? 0);

        // Valor Total dos Itens com Status SEPARADO
        $valorTotalSeparado = floatval(
            (clone $estoqueQueryBase)->where('estoque_items.status', 'SEPARADO')
                ->join('compras_items', 'estoque_items.id', '=', 'compras_items.estoque_item_id')
                ->sum('compras_items.valor_total') ?? 0
        );

        // Status PCP breakdown para Gráfico 1
        $statusEstoqueCounts = [
            'FALTA' => (clone $estoqueQueryBase)->where('estoque_items.status', 'FALTA')->count(),
            'SEPARADO' => (clone $estoqueQueryBase)->where('estoque_items.status', 'SEPARADO')->count(),
            'RETIRADO' => (clone $estoqueQueryBase)->where('estoque_items.status', 'RETIRADO')->count(),
            'FABRICA' => (clone $estoqueQueryBase)->where('estoque_items.status', 'FABRICA')->count(),
            'KANBAN' => (clone $estoqueQueryBase)->where('estoque_items.status', 'FABRICAR INTERNO KANBAN')->count(),
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

        $this->applyMultiFilter($topPedidosQuery, 'estoque_items.pedido', $searchPedido);
        $this->applyMultiFilter($topPedidosQuery, 'estoque_items.cliente_obs', $searchCliente);
        if ($searchStatusPcp) {
            $this->applyMultiFilter($topPedidosQuery, 'estoque_items.status', $searchStatusPcp);
        }
        if ($searchStatusPagamento) {
            if (in_array($searchStatusPagamento, ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO'])) {
                $topPedidosQuery->whereIn('compras_items.status_pagamento', ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO']);
            } else {
                $topPedidosQuery->where('compras_items.status_pagamento', $searchStatusPagamento);
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
                DB::raw('SUM(compras_items.valor_total) as total_valor')
            )
            ->where('estoque_items.status', '!=', 'FECHADO');

        $this->applyMultiFilter($topFornecedoresQuery, 'estoque_items.pedido', $searchPedido);
        $this->applyMultiFilter($topFornecedoresQuery, 'estoque_items.cliente_obs', $searchCliente);
        if ($searchStatusPcp) {
            $this->applyMultiFilter($topFornecedoresQuery, 'estoque_items.status', $searchStatusPcp);
        }
        if ($searchStatusPagamento) {
            if (in_array($searchStatusPagamento, ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO'])) {
                $topFornecedoresQuery->whereIn('compras_items.status_pagamento', ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO']);
            } else {
                $topFornecedoresQuery->where('compras_items.status_pagamento', $searchStatusPagamento);
            }
        }

        $topFornecedoresValores = $topFornecedoresQuery->groupBy(DB::raw($fornecedorExpr))
            ->orderBy('total_valor', 'desc')
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
                DB::raw("SUM(compras_items.valor_total) as valor_total_ops")
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

    /**
     * Retorna em JSON todos os itens do fornecedor selecionado para alimentar o Modal do Dashboard
     */
    public function getFornecedorItensJson(Request $request)
    {
        $codigoFornecedor = trim($request->get('fornecedor', ''));
        $searchPedido = $request->get('pedido');
        $searchStatusPcp = $request->get('status_pcp');
        $searchStatusPagamento = $request->get('status_pagamento');
        $searchCliente = $request->get('search_cliente');

        $query = CompraItem::join('estoque_items', 'compras_items.estoque_item_id', '=', 'estoque_items.id')
            ->select(
                'estoque_items.pedido as pedido_venda',
                'estoque_items.op',
                'estoque_items.cliente_obs',
                'estoque_items.codigo_produto',
                'estoque_items.descricao',
                'estoque_items.quantidade as qtd_op',
                'estoque_items.quantidade_estoque as qtd_estoque',
                DB::raw('GREATEST(0, estoque_items.quantidade - estoque_items.quantidade_estoque) as qtd_comprar'),
                'compras_items.codigo_fornecedor',
                'compras_items.pedido_compra',
                'compras_items.valor_unitario',
                'compras_items.valor_total',
                'estoque_items.status as status_pcp',
                'compras_items.status_pagamento'
            )
            ->where('estoque_items.status', '!=', 'FECHADO');

        if (empty($codigoFornecedor) || $codigoFornecedor === 'SEM FORNECEDOR' || $codigoFornecedor === '0') {
            $query->where(function ($q) {
                $q->whereNull('compras_items.codigo_fornecedor')
                  ->orWhere(DB::raw("TRIM(compras_items.codigo_fornecedor)"), '')
                  ->orWhere(DB::raw("TRIM(compras_items.codigo_fornecedor)"), '0');
            });
        } else {
            $query->where(DB::raw("TRIM(compras_items.codigo_fornecedor)"), $codigoFornecedor);
        }

        $this->applyMultiFilter($query, 'estoque_items.pedido', $searchPedido);
        $this->applyMultiFilter($query, 'estoque_items.cliente_obs', $searchCliente);
        if ($searchStatusPcp) {
            $this->applyMultiFilter($query, 'estoque_items.status', $searchStatusPcp);
        }
        if ($searchStatusPagamento) {
            if (in_array($searchStatusPagamento, ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO'])) {
                $query->whereIn('compras_items.status_pagamento', ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO']);
            } else {
                $query->where('compras_items.status_pagamento', $searchStatusPagamento);
            }
        }

        $items = $query->orderBy('estoque_items.pedido', 'desc')->get();

        $totalValorSum = $items->sum(fn($i) => floatval($i->valor_total));
        $totalQtdComprarSum = $items->sum(fn($i) => floatval($i->qtd_comprar));

        return response()->json([
            'success' => true,
            'fornecedor' => $codigoFornecedor ?: 'SEM FORNECEDOR',
            'count' => $items->count(),
            'total_valor' => $totalValorSum,
            'total_qtd_comprar' => $totalQtdComprarSum,
            'items' => $items,
        ]);
    }
}
