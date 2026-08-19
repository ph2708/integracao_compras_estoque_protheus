<?php

namespace App\Http\Controllers;

use App\Models\CompraItem;
use App\Models\EstoqueItem;
use App\Services\ProtheusService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ComprasController extends Controller
{
    protected $protheusService;

    public function __construct(ProtheusService $protheusService)
    {
        $this->protheusService = $protheusService;
    }

    /**
     * Exibe o Painel de Compras integrado ao Estoque
     */
    public function index(Request $request)
    {
        $searchPv = $request->get('pedido_venda');
        $searchFilial = $request->get('filial');

        $protheusItems = [];
        if ($searchPv) {
            $protheusItems = $this->protheusService->getItensPorPedido($searchPv, $searchFilial ?: null);
        } else {
            $protheusItems = $this->protheusService->getTodosPedidosVenda();
        }

        $estoqueItems = EstoqueItem::all()->keyBy(function ($item) {
            return $item->codigo_produto . '_' . ($item->pedido ?? '') . '_' . ($item->op ?? '');
        });

        $compraItems = CompraItem::all()->keyBy('estoque_item_id');

        $combinedItems = collect();

        foreach ($protheusItems as $pItem) {
            $key = $pItem['codigo_produto'] . '_' . ($pItem['pedido'] ?? '') . '_' . ($pItem['op'] ?? '');

            $estoqueMatch = $estoqueItems->get($key);

            if (!$estoqueMatch) {
                $estoqueMatch = $estoqueItems->firstWhere('codigo_produto', $pItem['codigo_produto']);
            }

            $compraMatch = $estoqueMatch ? $compraItems->get($estoqueMatch->id) : null;

            $valQtdOp = floatval($pItem['quantidade'] ?? ($estoqueMatch ? $estoqueMatch->quantidade : 1));
            $valQtdEstoque = $estoqueMatch ? floatval($estoqueMatch->quantidade_estoque) : 0;
            $valQtdComprar = max(0, $valQtdOp - $valQtdEstoque);

            $valUnitario = $compraMatch ? floatval($compraMatch->valor_unitario) : 0;
            $valIpi = $compraMatch ? floatval($compraMatch->ipi) : 0;
            $valFrete = $compraMatch ? floatval($compraMatch->frete) : 0;

            $valTotal = ($valUnitario * $valQtdComprar) + ($valUnitario * $valQtdComprar * ($valIpi / 100)) + $valFrete;

            $statusPcp = $estoqueMatch ? $estoqueMatch->status : 'FALTA';
            $badgePcp = match($statusPcp) {
                'FALTA' => 'badge-falta',
                'SEPARADO' => 'badge-separado',
                'RETIRADO' => 'badge-retirado',
                'FABRICA' => 'badge-fabrica',
                'FABRICAR INTERNO KANBAN' => 'badge-kanban',
                default => 'badge-falta'
            };

            $combinedItems->push([
                'id_key' => $key,
                'pedido_venda' => $pItem['pedido'] ?? ($estoqueMatch ? $estoqueMatch->pedido : '-'),
                'cliente_obs' => $pItem['cliente_obs'] ?? ($estoqueMatch ? $estoqueMatch->cliente_obs : '-'),
                'codigo_produto' => $pItem['codigo_produto'],
                'descricao' => $pItem['descricao'] ?? ($estoqueMatch ? $estoqueMatch->descricao : '-'),
                'op' => $pItem['op'] ?? ($estoqueMatch ? $estoqueMatch->op : '-'),
                'quantidade' => $valQtdOp,
                'quantidade_estoque' => $valQtdEstoque,
                'quantidade_comprar' => $valQtdComprar,
                'status_pcp' => $statusPcp,
                'status_pcp_badge' => $badgePcp,
                'estoque_item_id' => $estoqueMatch ? $estoqueMatch->id : null,
                'compra_id' => $compraMatch ? $compraMatch->id : null,
                'pedido_compra' => $compraMatch ? $compraMatch->pedido_compra : '',
                'codigo_fornecedor' => $compraMatch ? $compraMatch->codigo_fornecedor : '',
                'valor_unitario' => $valUnitario,
                'ipi' => $valIpi,
                'data_pc' => $compraMatch ? $compraMatch->data_pc : '',
                'data_pagamento' => $compraMatch ? $compraMatch->data_pagamento : '',
                'frete' => $valFrete,
                'solicitacao_compra' => $compraMatch ? $compraMatch->solicitacao_compra : '',
                'condicao_pagamento' => $compraMatch ? $compraMatch->condicao_pagamento : '',
                'valor_total' => $valTotal,
                'status_pagamento' => $compraMatch ? $compraMatch->status_pagamento : 'PENDENTE',
                'no_estoque' => $estoqueMatch ? true : false,
            ]);
        }

        // Filtros no Painel de Compras
        if ($request->filled('f_produto')) {
            $combinedItems = $combinedItems->filter(fn($i) => str_contains(strtolower($i['codigo_produto']), strtolower($request->f_produto)));
        }
        if ($request->filled('f_descricao')) {
            $combinedItems = $combinedItems->filter(fn($i) => str_contains(strtolower($i['descricao']), strtolower($request->f_descricao)));
        }
        if ($request->filled('f_op')) {
            $combinedItems = $combinedItems->filter(fn($i) => str_contains(strtolower($i['op']), strtolower($request->f_op)));
        }
        if ($request->filled('f_cliente')) {
            $combinedItems = $combinedItems->filter(fn($i) => str_contains(strtolower($i['cliente_obs']), strtolower($request->f_cliente)));
        }
        if ($request->filled('f_status_pcp')) {
            $combinedItems = $combinedItems->filter(fn($i) => $i['status_pcp'] === $request->f_status_pcp);
        }
        if ($request->filled('f_pedido_compra')) {
            $combinedItems = $combinedItems->filter(fn($i) => str_contains(strtolower($i['pedido_compra']), strtolower($request->f_pedido_compra)));
        }
        if ($request->filled('f_fornecedor')) {
            $combinedItems = $combinedItems->filter(fn($i) => str_contains(strtolower($i['codigo_fornecedor']), strtolower($request->f_fornecedor)));
        }
        if ($request->filled('f_status_pagamento')) {
            $combinedItems = $combinedItems->filter(fn($i) => $i['status_pagamento'] === $request->f_status_pagamento);
        }

        // Paginação Manual da Coleção
        $perPage = 15;
        $page = LengthAwarePaginator::resolveCurrentPage() ?: 1;
        $paginatedItems = new LengthAwarePaginator(
            $combinedItems->forPage($page, $perPage)->values(),
            $combinedItems->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        $filiaisProtheus = $this->protheusService->getFiliais();

        return view('compras.index', compact('paginatedItems', 'filiaisProtheus', 'searchPv', 'searchFilial'));
    }

    /**
     * Atualização individual de um item de compras
     */
    public function update(Request $request, $id)
    {
        $compraItem = CompraItem::findOrFail($id);

        $validated = $request->validate([
            'pedido_compra' => 'nullable|string',
            'codigo_fornecedor' => 'nullable|string',
            'valor_unitario' => 'nullable|numeric',
            'ipi' => 'nullable|numeric',
            'data_pc' => 'nullable|date',
            'data_pagamento' => 'nullable|date',
            'frete' => 'nullable|numeric',
            'solicitacao_compra' => 'nullable|string',
            'condicao_pagamento' => 'nullable|string',
            'status_pagamento' => 'required|string',
        ]);

        $estoqueItem = $compraItem->estoqueItem;
        $valQtdOp = $estoqueItem ? floatval($estoqueItem->quantidade) : 1;
        $valQtdEstoque = $estoqueItem ? floatval($estoqueItem->quantidade_estoque) : 0;
        $valQtdComprar = max(0, $valQtdOp - $valQtdEstoque);

        $valUnitario = floatval($validated['valor_unitario'] ?? 0);
        $valIpi = floatval($validated['ipi'] ?? 0);
        $valFrete = floatval($validated['frete'] ?? 0);

        $validated['valor_total'] = ($valUnitario * $valQtdComprar) + ($valUnitario * $valQtdComprar * ($valIpi / 100)) + $valFrete;

        $compraItem->update($validated);

        return redirect()->back()->with('success', 'Dados financeiros de compras salvos com sucesso!');
    }

    /**
     * Atualização em lote de todas as alterações feitas na página de compras
     */
    public function updateBatch(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
        ]);

        $updatedCount = 0;

        foreach ($request->items as $estoqueItemId => $itemData) {
            $estoqueItem = EstoqueItem::find($estoqueItemId);
            if (!$estoqueItem) continue;

            $valQtdOp = floatval($estoqueItem->quantidade);
            $valQtdEstoque = floatval($estoqueItem->quantidade_estoque);
            $valQtdComprar = max(0, $valQtdOp - $valQtdEstoque);

            $valUnitario = isset($itemData['valor_unitario']) ? floatval($itemData['valor_unitario']) : 0;
            $valIpi = isset($itemData['ipi']) ? floatval($itemData['ipi']) : 0;
            $valFrete = isset($itemData['frete']) ? floatval($itemData['frete']) : 0;
            $valTotal = ($valUnitario * $valQtdComprar) + ($valUnitario * $valQtdComprar * ($valIpi / 100)) + $valFrete;

            CompraItem::updateOrCreate(
                ['estoque_item_id' => $estoqueItemId],
                [
                    'pedido_compra' => $itemData['pedido_compra'] ?? null,
                    'codigo_fornecedor' => $itemData['codigo_fornecedor'] ?? null,
                    'valor_unitario' => $valUnitario,
                    'ipi' => $valIpi,
                    'data_pc' => !empty($itemData['data_pc']) ? $itemData['data_pc'] : null,
                    'data_pagamento' => !empty($itemData['data_pagamento']) ? $itemData['data_pagamento'] : null,
                    'frete' => $valFrete,
                    'solicitacao_compra' => $itemData['solicitacao_compra'] ?? null,
                    'condicao_pagamento' => $itemData['condicao_pagamento'] ?? null,
                    'valor_total' => $valTotal,
                    'status_pagamento' => $itemData['status_pagamento'] ?? 'PENDENTE',
                ]
            );
            $updatedCount++;
        }

        return redirect()->back()->with('success', "✅ {$updatedCount} item(ns) de compras salvos com sucesso!");
    }

    /**
     * Consulta dados do Pedido de Compra (SC7010) no Protheus
     */
    public function consultarProtheus(Request $request)
    {
        $request->validate([
            'pedido_compra' => 'required|string',
            'codigo_produto' => 'nullable|string',
        ]);

        $dadosProtheus = $this->protheusService->getDadosPedidoCompra(
            $request->pedido_compra,
            $request->codigo_produto
        );

        if (!$dadosProtheus) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido de Compra não encontrado no Protheus.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $dadosProtheus,
        ]);
    }
}
