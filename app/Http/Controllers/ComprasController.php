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
     * Auxiliar para verificar se o valor do item corresponde ao filtro de busca multi-itens por vírgula (Ex: 018722, 018723)
     */
    private function matchMultiFilter($itemValue, $filterValue, $isExact = false): bool
    {
        if (empty($filterValue)) return true;
        $tokens = array_filter(array_map('trim', explode(',', $filterValue)));
        if (empty($tokens)) return true;

        $valLower = strtolower($itemValue ?? '');
        foreach ($tokens as $token) {
            $tokLower = strtolower($token);
            if ($isExact) {
                if ($valLower === $tokLower) return true;
            } else {
                if (str_contains($valLower, $tokLower)) return true;
            }
        }
        return false;
    }

    /**
     * Exibe o Painel de Compras integrado ao Estoque (100% Leitura do MySQL - Carregamento Instantâneo)
     */
    public function index(Request $request)
    {
        $searchPv = $request->get('pedido_venda');
        $searchFilial = $request->get('filial');

        $combinedItems = collect();
        $compraItems = CompraItem::all()->keyBy('estoque_item_id');

        if ($searchPv) {
            // Consulta específica do Pedido de Venda no Protheus
            $protheusItems = $this->protheusService->getItensPorPedido($searchPv, $searchFilial ?: null);
            $estoqueItems = EstoqueItem::all()->keyBy(function ($item) {
                return $item->codigo_produto . '_' . ($item->pedido ?? '') . '_' . ($item->op ?? '');
            });

            foreach ($protheusItems as $pItem) {
                if (!is_array($pItem)) continue;

                $codProduto = $pItem['codigo_produto'] ?? '';
                $key = $codProduto . '_' . ($pItem['pedido'] ?? '') . '_' . ($pItem['op'] ?? '');
                $estoqueMatch = $estoqueItems->get($key);

                if (!$estoqueMatch) {
                    $estoqueMatch = $estoqueItems->firstWhere('codigo_produto', $codProduto);
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
                    'codigo_produto' => $codProduto ?: '-',
                    'descricao' => $pItem['descricao'] ?? ($estoqueMatch ? $estoqueMatch->descricao : '-'),
                    'descricao_longa' => $pItem['descricao_longa'] ?? ($estoqueMatch ? $estoqueMatch->descricao_longa : '-'),
                    'produto_pai' => $pItem['produto_pai'] ?? ($estoqueMatch ? $estoqueMatch->produto_pai : '-'),
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
        } else {
            // ⚡ 100% Leitura do Banco MySQL Local (0.001s / Instantâneo)
            $estoqueItems = EstoqueItem::orderBy('updated_at', 'desc')->get();

            foreach ($estoqueItems as $estoqueItem) {
                $compraMatch = $compraItems->get($estoqueItem->id);

                $valQtdOp = floatval($estoqueItem->quantidade);
                $valQtdEstoque = floatval($estoqueItem->quantidade_estoque);
                $valQtdComprar = max(0, $valQtdOp - $valQtdEstoque);

                $valUnitario = $compraMatch ? floatval($compraMatch->valor_unitario) : 0;
                $valIpi = $compraMatch ? floatval($compraMatch->ipi) : 0;
                $valFrete = $compraMatch ? floatval($compraMatch->frete) : 0;
                $valTotal = ($valUnitario * $valQtdComprar) + ($valUnitario * $valQtdComprar * ($valIpi / 100)) + $valFrete;

                $statusPcp = $estoqueItem->status ?? 'FALTA';
                $badgePcp = match($statusPcp) {
                    'FALTA' => 'badge-falta',
                    'SEPARADO' => 'badge-separado',
                    'RETIRADO' => 'badge-retirado',
                    'FABRICA' => 'badge-fabrica',
                    'FABRICAR INTERNO KANBAN' => 'badge-kanban',
                    default => 'badge-falta'
                };

                $combinedItems->push([
                    'id_key' => $estoqueItem->id,
                    'pedido_venda' => $estoqueItem->pedido ?? '-',
                    'cliente_obs' => $estoqueItem->cliente_obs ?? '-',
                    'codigo_produto' => $estoqueItem->codigo_produto,
                    'descricao' => $estoqueItem->descricao ?? '-',
                    'descricao_longa' => $estoqueItem->descricao_longa ?? ($estoqueItem->descricao ?? '-'),
                    'produto_pai' => $estoqueItem->produto_pai ?? '-',
                    'op' => $estoqueItem->op ?? '-',
                    'quantidade' => $valQtdOp,
                    'quantidade_estoque' => $valQtdEstoque,
                    'quantidade_comprar' => $valQtdComprar,
                    'status_pcp' => $statusPcp,
                    'status_pcp_badge' => $badgePcp,
                    'estoque_item_id' => $estoqueItem->id,
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
                    'no_estoque' => true,
                ]);
            }
        }

        // Filtros Multi-Itens no Painel de Compras
        if ($request->filled('f_produto')) {
            $combinedItems = $combinedItems->filter(fn($i) => $this->matchMultiFilter($i['codigo_produto'], $request->f_produto));
        }
        if ($request->filled('f_descricao')) {
            $combinedItems = $combinedItems->filter(fn($i) => $this->matchMultiFilter($i['descricao'], $request->f_descricao));
        }
        if ($request->filled('f_desc_longa')) {
            $combinedItems = $combinedItems->filter(fn($i) => $this->matchMultiFilter($i['descricao_longa'], $request->f_desc_longa));
        }
        if ($request->filled('f_prod_pai')) {
            $combinedItems = $combinedItems->filter(fn($i) => $this->matchMultiFilter($i['produto_pai'], $request->f_prod_pai));
        }
        if ($request->filled('f_op')) {
            $combinedItems = $combinedItems->filter(fn($i) => $this->matchMultiFilter($i['op'], $request->f_op));
        }
        if ($request->filled('f_cliente')) {
            $combinedItems = $combinedItems->filter(fn($i) => $this->matchMultiFilter($i['cliente_obs'], $request->f_cliente));
        }
        if ($request->filled('f_status_pcp')) {
            $combinedItems = $combinedItems->filter(fn($i) => $this->matchMultiFilter($i['status_pcp'], $request->f_status_pcp, true));
        }
        if ($request->filled('f_pedido_compra')) {
            $combinedItems = $combinedItems->filter(fn($i) => $this->matchMultiFilter($i['pedido_compra'], $request->f_pedido_compra));
        }
        if ($request->filled('f_fornecedor')) {
            $combinedItems = $combinedItems->filter(fn($i) => $this->matchMultiFilter($i['codigo_fornecedor'], $request->f_fornecedor));
        }
        if ($request->filled('f_status_pagamento')) {
            $combinedItems = $combinedItems->filter(fn($i) => $this->matchMultiFilter($i['status_pagamento'], $request->f_status_pagamento, true));
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
            'status_pagamento' => 'required|in:PENDENTE,PAGAMENTO ANTECIPADO,FATURADO,PAGO',
        ]);

        $valUnitario = floatval($validated['valor_unitario'] ?? 0);
        $valIpi = floatval($validated['ipi'] ?? 0);
        $valFrete = floatval($validated['frete'] ?? 0);
        $qtdComprar = floatval($compraItem->estoqueItem ? $compraItem->estoqueItem->quantidade_comprar : 0);

        $valTotal = ($valUnitario * $qtdComprar) + ($valUnitario * $qtdComprar * ($valIpi / 100)) + $valFrete;
        $validated['valor_total'] = $valTotal;

        $compraItem->update($validated);

        return redirect()->route('compras.index')->with('success', 'Dados de compra atualizados com sucesso!');
    }

    /**
     * Atualização em lote de múltiplos itens editados na tabela de Compras
     */
    public function updateBatch(Request $request)
    {
        $itemsData = $request->input('items', []);

        if (empty($itemsData)) {
            return redirect()->back()->with('error', 'Nenhuma alteração foi enviada.');
        }

        $updatedCount = 0;

        foreach ($itemsData as $estoqueId => $data) {
            $compraItem = CompraItem::where('estoque_item_id', $estoqueId)->first();
            
            // Se o item de compra não existe ainda, cria um novo
            if (!$compraItem) {
                $compraItem = new CompraItem();
                $compraItem->estoque_item_id = $estoqueId;
            }

            $updateData = [];
            if (array_key_exists('pedido_compra', $data)) $updateData['pedido_compra'] = $data['pedido_compra'];
            if (array_key_exists('codigo_fornecedor', $data)) $updateData['codigo_fornecedor'] = $data['codigo_fornecedor'];
            if (array_key_exists('solicitacao_compra', $data)) $updateData['solicitacao_compra'] = $data['solicitacao_compra'];
            if (isset($data['valor_unitario'])) $updateData['valor_unitario'] = floatval($data['valor_unitario']);
            if (isset($data['ipi'])) $updateData['ipi'] = floatval($data['ipi']);
            if (array_key_exists('data_pc', $data)) $updateData['data_pc'] = $data['data_pc'];
            if (array_key_exists('data_pagamento', $data)) $updateData['data_pagamento'] = $data['data_pagamento'];
            if (isset($data['status_pagamento'])) $updateData['status_pagamento'] = $data['status_pagamento'];

            $valUnitario = isset($updateData['valor_unitario']) ? $updateData['valor_unitario'] : floatval($compraItem->valor_unitario);
            $valIpi = isset($updateData['ipi']) ? $updateData['ipi'] : floatval($compraItem->ipi);
            $valFrete = floatval($compraItem->frete);
            $qtdComprar = floatval($compraItem->estoqueItem ? $compraItem->estoqueItem->quantidade_comprar : 0);

            $valTotal = ($valUnitario * $qtdComprar) + ($valUnitario * $qtdComprar * ($valIpi / 100)) + $valFrete;
            $updateData['valor_total'] = $valTotal;

            $compraItem->fill($updateData);
            $compraItem->save();
            $updatedCount++;
        }

        return redirect()->route('compras.index')->with('success', "✅ {$updatedCount} item(ns) de compras atualizado(s) com sucesso!");
    }
}
