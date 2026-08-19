<?php

namespace App\Http\Controllers;

use App\Models\CompraItem;
use App\Models\EstoqueItem;
use App\Services\ProtheusService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;

class ComprasController extends Controller
{
    protected ProtheusService $protheusService;

    public function __construct(ProtheusService $protheusService)
    {
        $this->protheusService = $protheusService;
    }

    /**
     * List items in Painel Compras com Cálculo Automático do Valor Total sobre a Qtd a Comprar.
     */
    public function index(Request $request)
    {
        $filiaisProtheus = $this->protheusService->listarFiliais();

        $searchPv = $request->query('pedido_venda') ? trim($request->query('pedido_venda')) : null;
        $searchFilial = $request->query('filial') ? trim($request->query('filial')) : null;

        // Filtros específicos das colunas
        $fProduto = $request->query('f_produto') ? trim($request->query('f_produto')) : null;
        $fDescricao = $request->query('f_descricao') ? trim($request->query('f_descricao')) : null;
        $fOp = $request->query('f_op') ? trim($request->query('f_op')) : null;
        $fCliente = $request->query('f_cliente') ? trim($request->query('f_cliente')) : null;
        $fStatusPcp = $request->query('f_status_pcp') ?: $request->query('status_pcp');
        $fPedidoCompra = $request->query('f_pedido_compra') ? trim($request->query('f_pedido_compra')) : null;
        $fFornecedor = $request->query('f_fornecedor') ? trim($request->query('f_fornecedor')) : null;
        $fStatusPagamento = $request->query('f_status_pagamento') ? trim($request->query('f_status_pagamento')) : null;

        if (!empty($searchPv)) {
            $cacheKey = "protheus_pv_{$searchPv}_filial_" . ($searchFilial ?: 'all');
            $protheusItems = Cache::remember($cacheKey, 60, function () use ($searchPv, $searchFilial) {
                return $this->protheusService->getPedidoItems($searchPv, $searchFilial);
            });

            $localEstoqueItems = EstoqueItem::where('pedido', $searchPv)
                ->with('compraItem')
                ->get()
                ->keyBy(fn($item) => $item->pedido . '_' . $item->codigo_produto);

            $rawItems = [];
            foreach ($protheusItems as $pItem) {
                $c2Pedido = $pItem['C2_PEDIDO'] ?? $searchPv;
                $codProduto = $pItem['C2_PRODUTO'] ?? '';
                $op = $pItem['D4_OP'] ?? ($pItem['C2_NUM'] ?? '');
                $clienteObs = $pItem['C2_OBS'] ?? '';
                $quantidadeOp = floatval($pItem['QUANTIDADE'] ?? 1);

                $itemKey = $c2Pedido . '_' . $codProduto;
                $localEstoque = $localEstoqueItems->get($itemKey);

                if ($localEstoque) {
                    $compraItem = $localEstoque->compraItem;
                    if (!$compraItem) {
                        $compraItem = CompraItem::create([
                            'estoque_item_id' => $localEstoque->id,
                            'status_pagamento' => 'PENDENTE',
                        ]);
                    }

                    $valUnit = $compraItem->valor_unitario ? floatval($compraItem->valor_unitario) : 0;
                    $ipiPercent = $compraItem->ipi ? floatval($compraItem->ipi) : 0;
                    $freteVal = $compraItem->frete ? floatval($compraItem->frete) : 0;

                    $qtdOp = floatval($localEstoque->quantidade ?: $quantidadeOp);
                    $qtdEstoque = floatval($localEstoque->quantidade_estoque);
                    $qtdComprar = max(0, $qtdOp - $qtdEstoque);

                    // Cálculo automático do Valor Total sobre a Qtd a Comprar
                    $valorTotalCalc = ($valUnit * $qtdComprar) + ($valUnit * $qtdComprar * ($ipiPercent / 100)) + $freteVal;

                    $rawItems[] = [
                        'compra_id' => $compraItem->id,
                        'estoque_item_id' => $localEstoque->id,
                        'pedido_venda' => $c2Pedido,
                        'codigo_produto' => $codProduto,
                        'descricao' => $pItem['B1_DESC'] ?? ($localEstoque->descricao ?? '-'),
                        'op' => $op,
                        'cliente_obs' => $localEstoque->cliente_obs ?: ($clienteObs ?: '-'),
                        'quantidade' => $qtdOp,
                        'quantidade_estoque' => $qtdEstoque,
                        'quantidade_comprar' => $qtdComprar,
                        'status_pcp' => $localEstoque->status,
                        'status_pcp_badge' => $this->getBadgeClass($localEstoque->status),
                        'no_estoque' => true,
                        'pedido_compra' => $compraItem->pedido_compra ?? '',
                        'codigo_fornecedor' => $compraItem->codigo_fornecedor ?? '',
                        'valor_unitario' => $compraItem->valor_unitario ?? '',
                        'ipi' => $compraItem->ipi ?? '',
                        'data_pc' => $compraItem->data_pc ?? '',
                        'data_pagamento' => $compraItem->data_pagamento ?? '',
                        'frete' => $compraItem->frete ?? '',
                        'solicitacao_compra' => $compraItem->solicitacao_compra ?? '',
                        'valor_total' => $compraItem->valor_total ?: ($valorTotalCalc > 0 ? $valorTotalCalc : 0),
                        'condicao_pagamento' => $compraItem->condicao_pagamento ?? '',
                        'status_pagamento' => $compraItem->status_pagamento ?? 'PENDENTE',
                    ];
                } else {
                    $rawItems[] = [
                        'compra_id' => null,
                        'estoque_item_id' => null,
                        'pedido_venda' => $c2Pedido,
                        'codigo_produto' => $codProduto,
                        'descricao' => $pItem['B1_DESC'] ?? ($pItem['B5_CEME'] ?? '-'),
                        'op' => $op,
                        'cliente_obs' => $clienteObs ?: '-',
                        'quantidade' => $quantidadeOp,
                        'quantidade_estoque' => 0,
                        'quantidade_comprar' => $quantidadeOp,
                        'status_pcp' => 'Aguardando Estoque',
                        'status_pcp_badge' => 'badge-pendente',
                        'no_estoque' => false,
                        'pedido_compra' => '',
                        'codigo_fornecedor' => '',
                        'valor_unitario' => '',
                        'ipi' => '',
                        'data_pc' => '',
                        'data_pagamento' => '',
                        'frete' => '',
                        'solicitacao_compra' => '',
                        'valor_total' => 0,
                        'condicao_pagamento' => '',
                        'status_pagamento' => 'PENDENTE',
                    ];
                }
            }

            // Aplicar filtros nas colunas
            if ($fProduto) $rawItems = array_filter($rawItems, fn($i) => stripos($i['codigo_produto'], $fProduto) !== false);
            if ($fDescricao) $rawItems = array_filter($rawItems, fn($i) => stripos($i['descricao'], $fDescricao) !== false);
            if ($fOp) $rawItems = array_filter($rawItems, fn($i) => stripos($i['op'], $fOp) !== false);
            if ($fCliente) $rawItems = array_filter($rawItems, fn($i) => stripos($i['cliente_obs'], $fCliente) !== false);
            if ($fStatusPcp) $rawItems = array_filter($rawItems, fn($i) => $i['status_pcp'] === $fStatusPcp);
            if ($fPedidoCompra) $rawItems = array_filter($rawItems, fn($i) => stripos($i['pedido_compra'], $fPedidoCompra) !== false);
            if ($fFornecedor) $rawItems = array_filter($rawItems, fn($i) => stripos($i['codigo_fornecedor'], $fFornecedor) !== false);
            if ($fStatusPagamento) $rawItems = array_filter($rawItems, fn($i) => $i['status_pagamento'] === $fStatusPagamento);

            $page = Paginator::resolveCurrentPage();
            $perPage = 15;
            $offset = ($page - 1) * $perPage;
            $slicedItems = array_slice(array_values($rawItems), $offset, $perPage);

            $paginatedItems = new LengthAwarePaginator(
                $slicedItems,
                count($rawItems),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

        } else {
            // Carregamento local via MySQL
            $query = CompraItem::with(['estoqueItem']);

            if ($fStatusPcp) {
                $query->whereHas('estoqueItem', fn($q) => $q->where('status', $fStatusPcp));
            }
            if ($fProduto) {
                $query->whereHas('estoqueItem', fn($q) => $q->where('codigo_produto', 'like', "%$fProduto%"));
            }
            if ($fDescricao) {
                $query->whereHas('estoqueItem', fn($q) => $q->where('descricao', 'like', "%$fDescricao%"));
            }
            if ($fOp) {
                $query->whereHas('estoqueItem', fn($q) => $q->where('op', 'like', "%$fOp%"));
            }
            if ($fCliente) {
                $query->whereHas('estoqueItem', fn($q) => $q->where('cliente_obs', 'like', "%$fCliente%"));
            }
            if ($fPedidoCompra) {
                $query->where('pedido_compra', 'like', "%$fPedidoCompra%");
            }
            if ($fFornecedor) {
                $query->where('codigo_fornecedor', 'like', "%$fFornecedor%");
            }
            if ($fStatusPagamento) {
                $query->where('status_pagamento', $fStatusPagamento);
            }

            $localPaginated = $query->latest()->paginate(15)->withQueryString();

            $transformedItems = collect($localPaginated->items())->map(function ($item) {
                $valUnit = $item->valor_unitario ? floatval($item->valor_unitario) : 0;
                $ipiPercent = $item->ipi ? floatval($item->ipi) : 0;
                $freteVal = $item->frete ? floatval($item->frete) : 0;

                $qtdOp = floatval($item->estoqueItem->quantidade ?? 1);
                $qtdEstoque = floatval($item->estoqueItem->quantidade_estoque ?? 0);
                $qtdComprar = max(0, $qtdOp - $qtdEstoque);

                $valorTotalCalc = ($valUnit * $qtdComprar) + ($valUnit * $qtdComprar * ($ipiPercent / 100)) + $freteVal;

                return [
                    'compra_id' => $item->id,
                    'estoque_item_id' => $item->estoqueItem->id ?? null,
                    'pedido_venda' => $item->estoqueItem->pedido ?? '-',
                    'codigo_produto' => $item->estoqueItem->codigo_produto ?? '-',
                    'descricao' => $item->estoqueItem->descricao ?? '-',
                    'op' => $item->estoqueItem->op ?? '-',
                    'cliente_obs' => $item->estoqueItem->cliente_obs ?: ($item->estoqueItem->observacao_estoque ?: '-'),
                    'quantidade' => $qtdOp,
                    'quantidade_estoque' => $qtdEstoque,
                    'quantidade_comprar' => $qtdComprar,
                    'status_pcp' => $item->estoqueItem->status ?? 'FALTA',
                    'status_pcp_badge' => $this->getBadgeClass($item->estoqueItem->status ?? 'FALTA'),
                    'no_estoque' => true,
                    'pedido_compra' => $item->pedido_compra ?? '',
                    'codigo_fornecedor' => $item->codigo_fornecedor ?? '',
                    'valor_unitario' => $item->valor_unitario ?? '',
                    'ipi' => $item->ipi ?? '',
                    'data_pc' => $item->data_pc ?? '',
                    'data_pagamento' => $item->data_pagamento ?? '',
                    'frete' => $item->frete ?? '',
                    'solicitacao_compra' => $item->solicitacao_compra ?? '',
                    'valor_total' => $item->valor_total ?: ($valorTotalCalc > 0 ? $valorTotalCalc : 0),
                    'condicao_pagamento' => $item->condicao_pagamento ?? '',
                    'status_pagamento' => $item->status_pagamento ?? 'PENDENTE',
                ];
            });

            $paginatedItems = new LengthAwarePaginator(
                $transformedItems,
                $localPaginated->total(),
                $localPaginated->perPage(),
                $localPaginated->currentPage(),
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        return view('compras.index', compact('paginatedItems', 'filiaisProtheus', 'searchPv', 'searchFilial'));
    }

    /**
     * Endpoint para consultar Pedido de Compra no Protheus
     */
    public function consultarProtheus(Request $request): JsonResponse
    {
        $request->validate([
            'pedido_compra' => 'required|string',
            'codigo_produto' => 'nullable|string',
        ]);

        $pedidoCompra = trim($request->pedido_compra);
        $codigoProduto = $request->codigo_produto ? trim($request->codigo_produto) : null;

        $info = $this->protheusService->getFornecedorEPedidoCompra($pedidoCompra, $codigoProduto);

        if (!$info) {
            return response()->json([
                'success' => false,
                'message' => "Pedido de Compra '$pedidoCompra' não encontrado no Protheus (SC7010).",
            ], 404);
        }

        $codForn = $info->C7_FORNECE ?? ($info->A2_COD ?? '');
        $nomeForn = $info->A2_NOME ?? ($info->A2_NREDUZ ?? '');

        return response()->json([
            'success' => true,
            'data' => [
                'pedido_compra' => $pedidoCompra,
                'codigo_fornecedor' => $codForn . ($nomeForn ? " ($nomeForn)" : ''),
                'condicao_pagamento' => $info->CONDICAO_PAGAMENTO_DESC ?? ($info->C7_COND ?? ''),
            ]
        ]);
    }

    /**
     * Update CompraItem com Cálculo Automático do Valor Total sobre Qtd a Comprar
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'estoque_item_id' => 'nullable|exists:estoque_items,id',
            'pedido_compra' => 'nullable|string|max:255',
            'codigo_fornecedor' => 'nullable|string|max:255',
            'valor_unitario' => 'nullable|numeric',
            'ipi' => 'nullable|numeric',
            'data_pc' => 'nullable|date',
            'data_pagamento' => 'nullable|date',
            'frete' => 'nullable|numeric',
            'solicitacao_compra' => 'nullable|string|max:255',
            'condicao_pagamento' => 'nullable|string|max:255',
            'status_pagamento' => 'required|in:PAGAMENTO ANTECIPADO,FATURADO,PAGO,PENDENTE',
        ]);

        $compraItem = CompraItem::find($id);

        if (!$compraItem && !empty($validated['estoque_item_id'])) {
            $compraItem = CompraItem::create(['estoque_item_id' => $validated['estoque_item_id']]);
        }

        if ($compraItem) {
            // Obter Qtd a Comprar do EstoqueItem
            $qtdComprar = floatval($compraItem->estoqueItem->quantidade_comprar ?? 1);
            $valUnit = floatval($validated['valor_unitario'] ?? $compraItem->valor_unitario ?? 0);
            $ipiVal = floatval($validated['ipi'] ?? $compraItem->ipi ?? 0);
            $freteVal = floatval($validated['frete'] ?? $compraItem->frete ?? 0);

            // Fórmula: (VALOR UNITARIO * QTD A COMPRAR) + (VALOR UNITARIO * QTD A COMPRAR * (IPI / 100)) + FRETE
            $valorTotalCalc = ($valUnit * $qtdComprar) + ($valUnit * $qtdComprar * ($ipiVal / 100)) + $freteVal;
            $validated['valor_total'] = $valorTotalCalc > 0 ? $valorTotalCalc : 0;

            $compraItem->update($validated);
        }

        return redirect()->back()->with('success', 'Item de compras atualizado com sucesso!');
    }

    private function getBadgeClass(string $status): string
    {
        return match($status) {
            'FALTA' => 'badge-falta',
            'SEPARADO' => 'badge-separado',
            'RETIRADO' => 'badge-retirado',
            'FABRICA' => 'badge-fabrica',
            'FABRICAR INTERNO KANBAN' => 'badge-kanban',
            default => 'badge-pendente'
        };
    }
}
