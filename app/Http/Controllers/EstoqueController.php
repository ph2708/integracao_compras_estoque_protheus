<?php

namespace App\Http\Controllers;

use App\Models\CompraItem;
use App\Models\EstoqueItem;
use App\Services\ProtheusService;
use Illuminate\Http\Request;

class EstoqueController extends Controller
{
    protected $protheusService;

    public function __construct(ProtheusService $protheusService)
    {
        $this->protheusService = $protheusService;
    }

    /**
     * Lista os itens de estoque cadastrados no MySQL com filtros e paginação
     */
    public function index(Request $request)
    {
        $query = EstoqueItem::query();

        if ($request->filled('f_pedido')) {
            $query->where('pedido', 'like', '%' . $request->f_pedido . '%');
        }
        if ($request->filled('f_produto')) {
            $query->where('codigo_produto', 'like', '%' . $request->f_produto . '%');
        }
        if ($request->filled('f_descricao')) {
            $query->where('descricao', 'like', '%' . $request->f_descricao . '%');
        }
        if ($request->filled('f_op')) {
            $query->where('op', 'like', '%' . $request->f_op . '%');
        }
        if ($request->filled('f_status')) {
            $query->where('status', $request->f_status);
        }
        if ($request->filled('f_cliente')) {
            $query->where('cliente_obs', 'like', '%' . $request->f_cliente . '%');
        }

        $items = $query->orderBy('updated_at', 'desc')->paginate(15)->withQueryString();
        $filiaisProtheus = $this->protheusService->getFiliais();

        return view('estoque.index', compact('items', 'filiaisProtheus'));
    }

    /**
     * Consulta itens de um Pedido de Venda (C2_PEDIDO) no Protheus via API
     */
    public function consultarPedido(Request $request)
    {
        $request->validate([
            'pedido' => 'required|string',
            'filial' => 'nullable|string',
        ]);

        $items = $this->protheusService->getItensPorPedido(
            $request->pedido,
            $request->filial ?: null
        );

        if (empty($items)) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum item encontrado no Protheus para este pedido.',
            ]);
        }

        return response()->json([
            'success' => true,
            'count' => count($items),
            'items' => $items,
        ]);
    }

    /**
     * Importação em lote dos itens selecionados do Protheus (Suporta JSON e Form Array)
     */
    public function storeBatch(Request $request)
    {
        $itemsData = [];
        if ($request->filled('items_json')) {
            $itemsData = json_decode($request->items_json, true) ?? [];
        } else {
            $itemsData = $request->items ?? [];
        }

        if (empty($itemsData)) {
            return redirect()->back()->with('error', 'Nenhum item válido foi enviado para importação.');
        }

        // ⚡ Busca em lote das prévias de valor unitário e fornecedor da SC7010 no momento da importação
        $codigosProdutos = array_filter(array_column($itemsData, 'codigo_produto'));
        $precosBatch = $this->protheusService->getUltimosPrecosBatch($codigosProdutos);

        $importedCount = 0;

        foreach ($itemsData as $itemData) {
            if (empty($itemData['codigo_produto'])) continue;

            $qtdOp = floatval($itemData['quantidade'] ?? 1);
            $qtdEstoque = isset($itemData['quantidade_estoque']) ? floatval($itemData['quantidade_estoque']) : 0;

            $estoqueItem = EstoqueItem::updateOrCreate(
                [
                    'codigo_produto' => $itemData['codigo_produto'],
                    'op' => $itemData['op'] ?? null,
                    'pedido' => $itemData['pedido'] ?? null,
                ],
                [
                    'descricao' => $itemData['descricao'] ?? null,
                    'cliente_obs' => $itemData['cliente_obs'] ?? null,
                    'quantidade' => $qtdOp,
                    'quantidade_estoque' => $qtdEstoque,
                    'status' => $itemData['status'] ?? 'FALTA',
                    'observacao_estoque' => $itemData['observacao_estoque'] ?? null,
                ]
            );

            // Pré-popula os dados financeiros de compras mantendo o Pedido de Compra EM BRANCO por padrão
            $prevCompra = $precosBatch[$itemData['codigo_produto']] ?? null;
            $valQtdComprar = max(0, $qtdOp - $qtdEstoque);
            $valUnitario = floatval($prevCompra['valor_unitario'] ?? 0);

            CompraItem::firstOrCreate(
                ['estoque_item_id' => $estoqueItem->id],
                [
                    'pedido_compra' => null, // Mantem em branco conforme solicitado
                    'codigo_fornecedor' => $prevCompra['codigo_fornecedor'] ?? null,
                    'valor_unitario' => $valUnitario,
                    'ipi' => 0,
                    'frete' => 0,
                    'valor_total' => $valUnitario * $valQtdComprar,
                    'status_pagamento' => 'PENDENTE',
                ]
            );

            $importedCount++;
        }

        return redirect()->route('estoque.index')
            ->with('success', "✅ {$importedCount} item(ns) importado(s) e salvos no estoque com sucesso!");
    }

    /**
     * Cadastro manual de um único item
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo_produto' => 'required|string',
            'quantidade' => 'required|numeric',
            'quantidade_estoque' => 'nullable|numeric',
            'status' => 'required|string',
            'descricao' => 'nullable|string',
            'op' => 'nullable|string',
            'pedido' => 'nullable|string',
            'cliente_obs' => 'nullable|string',
            'observacao_estoque' => 'nullable|string',
        ]);

        if (!isset($validated['quantidade_estoque'])) {
            $validated['quantidade_estoque'] = 0;
        }

        $estoqueItem = EstoqueItem::create($validated);

        // Busca prévia para item criado manualmente
        $prevCompra = $this->protheusService->getUltimoPrecoProduto($estoqueItem->codigo_produto);
        $valQtdComprar = max(0, floatval($estoqueItem->quantidade) - floatval($estoqueItem->quantidade_estoque));
        $valUnitario = floatval($prevCompra->valor_unitario ?? 0);

        CompraItem::create([
            'estoque_item_id' => $estoqueItem->id,
            'pedido_compra' => null, // Mantem em branco por padrao
            'codigo_fornecedor' => $prevCompra->codigo_fornecedor ?? null,
            'valor_unitario' => $valUnitario,
            'ipi' => 0,
            'frete' => 0,
            'valor_total' => $valUnitario * $valQtdComprar,
            'status_pagamento' => 'PENDENTE',
        ]);

        return redirect()->route('estoque.index')
            ->with('success', 'Item adicionado ao estoque com sucesso!');
    }

    /**
     * Atualização individual de um item no estoque local (MySQL)
     */
    public function update(Request $request, $id)
    {
        $estoqueItem = EstoqueItem::findOrFail($id);

        $validated = $request->validate([
            'quantidade_estoque' => 'nullable|numeric',
            'observacao_estoque' => 'nullable|string',
            'status' => 'required|string',
        ]);

        if (isset($validated['quantidade_estoque'])) {
            $validated['quantidade_estoque'] = floatval($validated['quantidade_estoque']);
        }

        $estoqueItem->update($validated);

        return redirect()->back()->with('success', 'Item do estoque atualizado com sucesso!');
    }

    /**
     * Atualização em lote de todas as alterações feitas na tabela de estoque
     */
    public function updateBatch(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
        ]);

        $updatedCount = 0;

        foreach ($request->items as $id => $itemData) {
            $estoqueItem = EstoqueItem::find($id);
            if (!$estoqueItem) continue;

            $estoqueItem->update([
                'quantidade_estoque' => isset($itemData['quantidade_estoque']) ? floatval($itemData['quantidade_estoque']) : $estoqueItem->quantidade_estoque,
                'observacao_estoque' => $itemData['observacao_estoque'] ?? $estoqueItem->observacao_estoque,
                'status' => $itemData['status'] ?? $estoqueItem->status,
            ]);

            $updatedCount++;
        }

        return redirect()->back()->with('success', "✅ {$updatedCount} item(ns) de estoque atualizados com sucesso!");
    }

    /**
     * Excluir um item do estoque
     */
    public function destroy($id)
    {
        $item = EstoqueItem::findOrFail($id);
        $item->delete();

        return redirect()->route('estoque.index')->with('success', 'Item removido com sucesso!');
    }
}
