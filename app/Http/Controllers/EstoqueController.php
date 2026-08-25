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
     * Auxiliar para aplicação de filtros com suporte a múltiplos itens separados por vírgula (Ex: 018722, 018723)
     */
    private function applyMultiFilter(&$query, $column, $value)
    {
        if (empty($value)) return;
        $tokens = array_filter(array_map('trim', explode(',', $value)));
        if (empty($tokens)) return;

        $query->where(function($q) use ($column, $tokens) {
            foreach ($tokens as $token) {
                if ($column === 'status') {
                    $q->orWhere($column, '=', $token);
                } else {
                    $q->orWhere($column, 'like', '%' . $token . '%');
                }
            }
        });
    }

    /**
     * Lista os itens de estoque cadastrados no MySQL com suporte a múltiplos itens nos filtros e paginação
     */
    public function index(Request $request)
    {
        $query = EstoqueItem::leftJoin('pv_metadados', 'estoque_items.pedido', '=', 'pv_metadados.pedido')
            ->select('estoque_items.*', \DB::raw("COALESCE(NULLIF(pv_metadados.fabrica, ''), '99') as fabrica_seq"));

        $this->applyMultiFilter($query, 'estoque_items.pedido', $request->f_pedido);
        $this->applyMultiFilter($query, 'codigo_produto', $request->f_produto);
        $this->applyMultiFilter($query, 'descricao', $request->f_descricao);
        $this->applyMultiFilter($query, 'descricao_longa', $request->f_desc_longa);
        $this->applyMultiFilter($query, 'estoque_items.pedido', $request->f_pv);
        $this->applyMultiFilter($query, 'produto_pai', $request->f_prod_pai);
        $this->applyMultiFilter($query, 'op', $request->f_op);
        if ($request->filled('f_fabrica')) {
            $this->applyMultiFilter($query, 'pv_metadados.fabrica', $request->f_fabrica);
        }
        if ($request->filled('f_status')) {
            $this->applyMultiFilter($query, 'estoque_items.status', $request->f_status);
        } else {
            $query->where('estoque_items.status', '!=', 'FECHADO');
        }
        $this->applyMultiFilter($query, 'cliente_obs', $request->f_cliente);

        $items = $query->orderByRaw("CASE WHEN pv_metadados.fabrica REGEXP '^[0-9]+$' THEN CAST(pv_metadados.fabrica AS UNSIGNED) ELSE 999999 END ASC")
            ->orderBy('estoque_items.pedido', 'asc')
            ->orderBy('estoque_items.id', 'desc')
            ->paginate(30)
            ->withQueryString();

        $filiaisProtheus = $this->protheusService->getFiliais();
        if (empty($filiaisProtheus)) {
            $filiaisProtheus = ['01', '02', '03', '04', '05', '10', '15', '20', '22', '25', '30'];
        }

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
                    'descricao_longa' => $itemData['descricao_longa'] ?? ($itemData['descricao'] ?? null),
                    'produto_pai' => $itemData['produto_pai'] ?? null,
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
            $valUnitario = floatval($prevCompra['valor_unitario'] ?? ($prevCompra['preco'] ?? 0));
            $codFornecedor = $prevCompra['codigo_fornecedor'] ?? ($prevCompra['fornecedor'] ?? null);

            CompraItem::firstOrCreate(
                ['estoque_item_id' => $estoqueItem->id],
                [
                    'pedido_compra' => null, // Mantem em branco conforme solicitado
                    'codigo_fornecedor' => $codFornecedor,
                    'valor_unitario' => $valUnitario,
                    'ipi' => 0,
                    'frete' => 0,
                    'valor_total' => $valUnitario * $valQtdComprar,
                    'status_pagamento' => 'PENDENTE',
                ]
            );

            $importedCount++;
        }

        return redirect()->route('estoque.index')->with('success', "✅ {$importedCount} item(ns) importado(s) com sucesso para o banco de dados MySQL!");
    }

    /**
     * Cadastro manual de um item no Estoque
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo_produto' => 'required|string',
            'descricao' => 'nullable|string',
            'descricao_longa' => 'nullable|string',
            'produto_pai' => 'nullable|string',
            'op' => 'nullable|string',
            'pedido' => 'nullable|string',
            'cliente_obs' => 'nullable|string',
            'quantidade' => 'required|numeric|min:0.01',
            'quantidade_estoque' => 'nullable|numeric|min:0',
            'status' => 'required|in:FALTA,SEPARADO,RETIRADO,FABRICA,FABRICAR INTERNO KANBAN',
            'observacao_estoque' => 'nullable|string',
        ]);

        $qtdOp = floatval($validated['quantidade']);
        $qtdEstoque = floatval($validated['quantidade_estoque'] ?? 0);

        $estoqueItem = EstoqueItem::create($validated);

        // Busca prévia de valor unitário e fornecedor na SC7010
        $prevCompra = $this->protheusService->getUltimoPrecoFornecedor($validated['codigo_produto']);
        $valQtdComprar = max(0, $qtdOp - $qtdEstoque);
        $valUnitario = floatval($prevCompra['valor_unitario'] ?? ($prevCompra['preco'] ?? 0));
        $codFornecedor = $prevCompra['codigo_fornecedor'] ?? ($prevCompra['fornecedor'] ?? null);

        CompraItem::create([
            'estoque_item_id' => $estoqueItem->id,
            'pedido_compra' => null,
            'codigo_fornecedor' => $codFornecedor,
            'valor_unitario' => $valUnitario,
            'ipi' => 0,
            'frete' => 0,
            'valor_total' => $valUnitario * $valQtdComprar,
            'status_pagamento' => 'PENDENTE',
        ]);

        return redirect()->route('estoque.index')->with('success', 'Item adicionado ao Estoque com sucesso!');
    }

    /**
     * Atualização individual de um item do Estoque
     */
    public function update(Request $request, $id)
    {
        $estoqueItem = EstoqueItem::findOrFail($id);

        $validated = $request->validate([
            'quantidade_estoque' => 'nullable|numeric|min:0',
            'status' => 'required|in:FALTA,SEPARADO,RETIRADO,FABRICA,FABRICAR INTERNO KANBAN',
            'observacao_estoque' => 'nullable|string',
            'produto_pai' => 'nullable|string',
            'descricao_longa' => 'nullable|string',
        ]);

        if (array_key_exists('quantidade_estoque', $validated)) {
            $validated['quantidade_estoque'] = (is_null($validated['quantidade_estoque']) || $validated['quantidade_estoque'] === '') ? 0 : floatval($validated['quantidade_estoque']);
        }

        $estoqueItem->update($validated);

        // Recalcula o valor total em compras se a quantidade em estoque mudou
        if ($estoqueItem->compraItem) {
            $valQtdComprar = max(0, floatval($estoqueItem->quantidade) - floatval($estoqueItem->quantidade_estoque));
            $valUnitario = floatval($estoqueItem->compraItem->valor_unitario);
            $valIpi = floatval($estoqueItem->compraItem->ipi);
            $frete = floatval($estoqueItem->compraItem->frete);

            $valTotal = ($valUnitario * $valQtdComprar) + ($valUnitario * $valQtdComprar * ($valIpi / 100)) + $frete;
            $estoqueItem->compraItem->update(['valor_total' => $valTotal]);
        }

        return redirect()->route('estoque.index')->with('success', 'Item de estoque atualizado com sucesso!');
    }

    /**
     * Atualização em lote de múltiplos itens editados na tabela de Estoque
     */
    public function updateBatch(Request $request)
    {
        $itemsData = $request->input('items', []);

        if (empty($itemsData)) {
            return redirect()->back()->with('error', 'Nenhuma alteração foi enviada.');
        }

        $updatedCount = 0;

        foreach ($itemsData as $id => $data) {
            $estoqueItem = EstoqueItem::find($id);
            if (!$estoqueItem) continue;

            $updateData = [];
            if (array_key_exists('quantidade_estoque', $data)) {
                $updateData['quantidade_estoque'] = (is_null($data['quantidade_estoque']) || $data['quantidade_estoque'] === '') ? 0 : floatval($data['quantidade_estoque']);
            }
            if (isset($data['status'])) {
                $updateData['status'] = $data['status'];
            }
            if (array_key_exists('observacao_estoque', $data)) {
                $updateData['observacao_estoque'] = $data['observacao_estoque'];
            }
            if (array_key_exists('produto_pai', $data)) {
                $updateData['produto_pai'] = $data['produto_pai'];
            }
            if (array_key_exists('descricao_longa', $data)) {
                $updateData['descricao_longa'] = $data['descricao_longa'];
            }

            if (!empty($updateData)) {
                $estoqueItem->update($updateData);

                // Recalcula o valor total em compras
                if ($estoqueItem->compraItem) {
                    $valQtdComprar = max(0, floatval($estoqueItem->quantidade) - floatval($estoqueItem->quantidade_estoque));
                    $valUnitario = floatval($estoqueItem->compraItem->valor_unitario);
                    $valIpi = floatval($estoqueItem->compraItem->ipi);
                    $frete = floatval($estoqueItem->compraItem->frete);

                    $valTotal = ($valUnitario * $valQtdComprar) + ($valUnitario * $valQtdComprar * ($valIpi / 100)) + $frete;
                    $estoqueItem->compraItem->update(['valor_total' => $valTotal]);
                }

                $updatedCount++;
            }
        }

        return redirect()->route('estoque.index')->with('success', "✅ {$updatedCount} item(ns) de estoque atualizado(s) com sucesso!");
    }
}
