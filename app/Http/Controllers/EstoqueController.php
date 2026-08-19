<?php

namespace App\Http\Controllers;

use App\Models\EstoqueItem;
use App\Models\CompraItem;
use App\Services\ProtheusService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class EstoqueController extends Controller
{
    protected ProtheusService $protheusService;

    public function __construct(ProtheusService $protheusService)
    {
        $this->protheusService = $protheusService;
    }

    /**
     * List all items in the Estoque panel with Column Filters & Pagination
     */
    public function index(Request $request)
    {
        $query = EstoqueItem::with('compraItem');

        if ($request->filled('f_pedido')) {
            $query->where('pedido', 'like', '%' . trim($request->f_pedido) . '%');
        }
        if ($request->filled('f_produto')) {
            $query->where('codigo_produto', 'like', '%' . trim($request->f_produto) . '%');
        }
        if ($request->filled('f_descricao')) {
            $query->where('descricao', 'like', '%' . trim($request->f_descricao) . '%');
        }
        if ($request->filled('f_op')) {
            $query->where('op', 'like', '%' . trim($request->f_op) . '%');
        }
        if ($request->filled('f_status')) {
            $query->where('status', trim($request->f_status));
        }
        if ($request->filled('f_cliente')) {
            $query->where('cliente_obs', 'like', '%' . trim($request->f_cliente) . '%');
        }

        $items = $query->latest()->paginate(15)->withQueryString();
        $filiaisProtheus = $this->protheusService->listarFiliais();

        return view('estoque.index', compact('items', 'filiaisProtheus'));
    }

    /**
     * Endpoint API para consultar itens do Protheus por C2_PEDIDO e C2_FILIAL
     */
    public function consultarPedido(Request $request): JsonResponse
    {
        $request->validate([
            'pedido' => 'required|string',
            'filial' => 'nullable|string',
        ]);

        $filial = $request->filial ?: null;
        $items = $this->protheusService->getPedidoItems($request->pedido, $filial);

        if (empty($items)) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum item encontrado para o Pedido ' . $request->pedido . ($filial ? " na Filial $filial" : '') . ' no Protheus.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'count' => count($items),
            'items' => array_map(function ($item) {
                return [
                    'filial' => $item['C2_FILIAL'] ?? '',
                    'pedido' => $item['C2_PEDIDO'] ?? '',
                    'codigo_produto' => $item['C2_PRODUTO'] ?? '',
                    'descricao' => $item['B1_DESC'] ?? ($item['B5_CEME'] ?? ''),
                    'op' => $item['D4_OP'] ?? ($item['C2_NUM'] ?? ''),
                    'cliente_obs' => $item['C2_OBS'] ?? '',
                    'quantidade' => floatval($item['QUANTIDADE'] ?? 1),
                    'quantidade_estoque' => floatval($item['QUANTIDADE'] ?? 1),
                ];
            }, $items)
        ]);
    }

    /**
     * Importar em lote os itens selecionados do Protheus para o MySQL
     */
    public function storeBatch(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.codigo_produto' => 'required|string',
            'items.*.descricao' => 'nullable|string',
            'items.*.op' => 'nullable|string',
            'items.*.pedido' => 'nullable|string',
            'items.*.cliente_obs' => 'nullable|string',
            'items.*.quantidade' => 'nullable|numeric',
            'items.*.quantidade_estoque' => 'nullable|numeric',
            'items.*.status' => 'required|in:FALTA,SEPARADO,RETIRADO,FABRICA,FABRICAR INTERNO KANBAN',
            'items.*.observacao_estoque' => 'nullable|string',
        ]);

        $count = 0;
        foreach ($validated['items'] as $itemData) {
            $item = EstoqueItem::create($itemData);

            CompraItem::create([
                'estoque_item_id' => $item->id,
                'status_pagamento' => 'PENDENTE',
            ]);

            if (!empty($itemData['pedido'])) {
                Cache::forget("protheus_pv_" . $itemData['pedido'] . "_filial_all");
                Cache::forget("protheus_pv_" . $itemData['pedido'] . "_filial_22");
            }

            $count++;
        }

        return redirect()->route('estoque.index')->with('success', "$count item(ns) importado(s) e salvos no Estoque local (MySQL)!");
    }

    /**
     * Store a single manual item in MySQL
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo_produto' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:255',
            'op' => 'nullable|string|max:255',
            'pedido' => 'nullable|string|max:255',
            'cliente_obs' => 'nullable|string|max:255',
            'quantidade' => 'nullable|numeric',
            'quantidade_estoque' => 'nullable|numeric',
            'status' => 'required|in:FALTA,SEPARADO,RETIRADO,FABRICA,FABRICAR INTERNO KANBAN',
            'observacao_estoque' => 'nullable|string',
        ]);

        $item = EstoqueItem::create($validated);

        CompraItem::create([
            'estoque_item_id' => $item->id,
            'status_pagamento' => 'PENDENTE',
        ]);

        if (!empty($validated['pedido'])) {
            Cache::forget("protheus_pv_" . $validated['pedido'] . "_filial_all");
            Cache::forget("protheus_pv_" . $validated['pedido'] . "_filial_22");
        }

        return redirect()->route('estoque.index')->with('success', 'Item adicionado ao estoque com sucesso!');
    }

    /**
     * Update item in Estoque por ID garantido
     */
    public function update(Request $request, $id)
    {
        $estoqueItem = EstoqueItem::findOrFail($id);

        $validated = $request->validate([
            'status' => 'nullable|in:FALTA,SEPARADO,RETIRADO,FABRICA,FABRICAR INTERNO KANBAN',
            'quantidade_estoque' => 'nullable|numeric|min:0',
            'observacao_estoque' => 'nullable|string',
        ]);

        $estoqueItem->update($validated);

        // Atualizar CompraItem se existir
        if ($estoqueItem->compraItem) {
            $compraItem = $estoqueItem->compraItem;
            $qtdComprar = $estoqueItem->quantidade_comprar;
            $valUnit = floatval($compraItem->valor_unitario ?? 0);
            $ipiVal = floatval($compraItem->ipi ?? 0);
            $freteVal = floatval($compraItem->frete ?? 0);

            $valorTotalCalc = ($valUnit * $qtdComprar) + ($valUnit * $qtdComprar * ($ipiVal / 100)) + $freteVal;
            $compraItem->update(['valor_total' => $valorTotalCalc > 0 ? $valorTotalCalc : 0]);
        }

        if (!empty($estoqueItem->pedido)) {
            Cache::forget("protheus_pv_" . $estoqueItem->pedido . "_filial_all");
            Cache::forget("protheus_pv_" . $estoqueItem->pedido . "_filial_22");
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Status/Estoque atualizado com sucesso!',
                'quantidade_comprar' => $estoqueItem->quantidade_comprar,
                'status' => $estoqueItem->status
            ]);
        }

        return redirect()->back()->with('success', 'Item de estoque atualizado com sucesso!');
    }
}
