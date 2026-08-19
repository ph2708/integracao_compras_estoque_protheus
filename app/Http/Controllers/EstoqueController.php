<?php

namespace App\Http\Controllers;

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
     * Importação em lote dos itens selecionados do Protheus
     */
    public function storeBatch(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.codigo_produto' => 'required|string',
            'items.*.quantidade' => 'required|numeric',
        ]);

        $importedCount = 0;

        foreach ($request->items as $itemData) {
            $qtdOp = floatval($itemData['quantidade'] ?? 1);
            $qtdEstoque = isset($itemData['quantidade_estoque']) ? floatval($itemData['quantidade_estoque']) : $qtdOp;

            EstoqueItem::updateOrCreate(
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
            $validated['quantidade_estoque'] = $validated['quantidade'];
        }

        EstoqueItem::create($validated);

        return redirect()->route('estoque.index')
            ->with('success', 'Item adicionado ao estoque com sucesso!');
    }

    /**
     * Atualização individual de um item
     */
    public function update(Request $request, $id)
    {
        $estoqueItem = EstoqueItem::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string',
            'quantidade_estoque' => 'required|numeric',
            'observacao_estoque' => 'nullable|string',
        ]);

        $estoqueItem->update($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Produto {$estoqueItem->codigo_produto} atualizado com sucesso!",
                'item' => $estoqueItem
            ]);
        }

        return redirect()->back()->with('success', "Produto {$estoqueItem->codigo_produto} atualizado com sucesso!");
    }

    /**
     * Atualização em lote de todas as alterações feitas na página de estoque
     */
    public function updateBatch(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
        ]);

        $updatedCount = 0;

        foreach ($request->items as $id => $itemData) {
            $estoqueItem = EstoqueItem::find($id);
            if ($estoqueItem) {
                $estoqueItem->update([
                    'status' => $itemData['status'] ?? $estoqueItem->status,
                    'quantidade_estoque' => isset($itemData['quantidade_estoque']) ? floatval($itemData['quantidade_estoque']) : $estoqueItem->quantidade_estoque,
                    'observacao_estoque' => $itemData['observacao_estoque'] ?? $estoqueItem->observacao_estoque,
                ]);
                $updatedCount++;
            }
        }

        return redirect()->back()->with('success', "✅ {$updatedCount} item(ns) de estoque salvos com sucesso!");
    }
}
