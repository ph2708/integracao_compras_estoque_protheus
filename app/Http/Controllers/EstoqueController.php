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
            ->paginate(100)
            ->withQueryString();

        // Extrair Opções Reduzidas Dinâmicas para Descrição no Estoque (respeitando cliente, pv, produto, op, etc.)
        $queryDesc = EstoqueItem::leftJoin('pv_metadados', 'estoque_items.pedido', '=', 'pv_metadados.pedido');
        $this->applyMultiFilter($queryDesc, 'estoque_items.pedido', $request->f_pedido);
        $this->applyMultiFilter($queryDesc, 'codigo_produto', $request->f_produto);
        $this->applyMultiFilter($queryDesc, 'descricao_longa', $request->f_desc_longa);
        $this->applyMultiFilter($queryDesc, 'estoque_items.pedido', $request->f_pv);
        $this->applyMultiFilter($queryDesc, 'produto_pai', $request->f_prod_pai);
        $this->applyMultiFilter($queryDesc, 'op', $request->f_op);
        if ($request->filled('f_fabrica')) {
            $this->applyMultiFilter($queryDesc, 'pv_metadados.fabrica', $request->f_fabrica);
        }
        if ($request->filled('f_status')) {
            $this->applyMultiFilter($queryDesc, 'estoque_items.status', $request->f_status);
        } else {
            $queryDesc->where('estoque_items.status', '!=', 'FECHADO');
        }
        $this->applyMultiFilter($queryDesc, 'cliente_obs', $request->f_cliente);

        $opcoesDescricao = $queryDesc->whereNotNull('descricao')->where('descricao', '!=', '')->distinct()->pluck('descricao')->sort()->values();
        $fDescricao = $request->f_descricao;

        $filiaisProtheus = $this->protheusService->getFiliais();
        if (empty($filiaisProtheus)) {
            $filiaisProtheus = ['01', '02', '03', '04', '05', '10', '15', '20', '22', '25', '30'];
        }

        // Totais e Métricas Calculadas sobre o Filtro Atual
        $totalItensFiltro = (clone $query)->count();
        $totalQtdOpFiltro = floatval((clone $query)->sum('quantidade') ?? 0);
        $totalQtdEstoqueFiltro = floatval((clone $query)->sum('quantidade_estoque') ?? 0);
        $totalQtdComprarFiltro = floatval((clone $query)->sum(\DB::raw('GREATEST(0, quantidade - quantidade_estoque)')) ?? 0);

        return view('estoque.index', compact(
            'items',
            'filiaisProtheus',
            'opcoesDescricao',
            'fDescricao',
            'totalItensFiltro',
            'totalQtdOpFiltro',
            'totalQtdEstoqueFiltro',
            'totalQtdComprarFiltro'
        ));
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
    /**
     * Retorna em JSON os dados do produto/OP pesquisado para auto-preenchimento no Modal de Inserção Manual
     */
    public function lookupItemJson(Request $request)
    {
        $codigo = trim($request->get('codigo_produto', ''));
        $op = trim($request->get('op', ''));
        $pedido = trim($request->get('pedido', ''));

        if (empty($codigo) && empty($op) && empty($pedido)) {
            return response()->json(['success' => false, 'message' => 'Nenhum termo de busca fornecido.']);
        }

        // 1. Buscar primeiro em estoques locais existentes
        $query = EstoqueItem::query();
        if (!empty($op)) {
            $query->where('op', 'like', '%' . $op . '%');
        } elseif (!empty($codigo)) {
            $query->where('codigo_produto', $codigo);
        } elseif (!empty($pedido)) {
            $query->where('pedido', 'like', '%' . $pedido . '%');
        }

        $existente = $query->orderBy('id', 'desc')->first();

        if ($existente) {
            $desc = $existente->descricao;
            $descLonga = $existente->descricao_longa;
            $prodPai = $existente->produto_pai;
            $cliObs = $existente->cliente_obs;

            // Se descrição ou dados estiverem vazios no item local, busca no Protheus
            if (empty($desc) && (!empty($existente->pedido) || !empty($existente->op))) {
                $term = $existente->pedido ?: $existente->op;
                $itemsProtheus = $this->protheusService->getItensPorPedido($term);
                if (!empty($itemsProtheus)) {
                    $pItem = collect($itemsProtheus)->firstWhere('codigo_produto', $existente->codigo_produto) ?? $itemsProtheus[0];
                    if ($pItem) {
                        $desc = $desc ?: ($pItem['descricao'] ?? null);
                        $descLonga = $descLonga ?: ($pItem['descricao_longa'] ?? null);
                        $prodPai = $prodPai ?: ($pItem['produto_pai'] ?? null);
                        $cliObs = $cliObs ?: ($pItem['cliente_obs'] ?? null);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'source' => 'local',
                'codigo_produto' => $existente->codigo_produto,
                'descricao' => $desc,
                'descricao_longa' => $descLonga,
                'produto_pai' => $prodPai,
                'op' => $existente->op,
                'pedido' => $existente->pedido,
                'cliente_obs' => $cliObs,
                'quantidade' => floatval($existente->quantidade),
            ]);
        }

        // 2. Se não encontrou no MySQL local, tenta consultar no Protheus
        if (!empty($pedido) || !empty($op)) {
            $term = $pedido ?: $op;
            $itemsProtheus = $this->protheusService->getItensPorPedido($term);
            if (!empty($itemsProtheus)) {
                $pItem = collect($itemsProtheus)->firstWhere('codigo_produto', $codigo) ?? $itemsProtheus[0];
                return response()->json([
                    'success' => true,
                    'source' => 'protheus',
                    'codigo_produto' => $pItem['codigo_produto'] ?? $codigo,
                    'descricao' => $pItem['descricao'] ?? null,
                    'descricao_longa' => $pItem['descricao_longa'] ?? null,
                    'produto_pai' => $pItem['produto_pai'] ?? null,
                    'op' => $pItem['op'] ?? $op,
                    'pedido' => $pItem['pedido'] ?? $pedido,
                    'cliente_obs' => $pItem['cliente_obs'] ?? null,
                    'quantidade' => floatval($pItem['quantidade'] ?? 1),
                ]);
            }
        }

        return response()->json(['success' => false, 'message' => 'Nenhum registro encontrado.']);
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

        $codClean = strtoupper(trim($validated['codigo_produto']));
        $opClean = strtoupper(trim($validated['op'] ?? ''));
        $pedidoClean = strtoupper(trim($validated['pedido'] ?? ''));
        $qtdOp = floatval($validated['quantidade']);
        $qtdEstoque = floatval($validated['quantidade_estoque'] ?? 0);

        // Se descrição ou cliente vierem vazios, tenta enriquecer automaticamente com dados de itens existentes
        if (empty($validated['descricao']) || empty($validated['cliente_obs']) || empty($validated['produto_pai'])) {
            $ref = EstoqueItem::where(function($q) use ($codClean, $opClean, $pedidoClean) {
                if ($opClean) $q->where('op', $opClean);
                if ($codClean) $q->orWhere('codigo_produto', $codClean);
                if ($pedidoClean) $q->orWhere('pedido', $pedidoClean);
            })->whereNotNull('descricao')->orderBy('id', 'desc')->first();

            if ($ref) {
                if (empty($validated['descricao'])) $validated['descricao'] = $ref->descricao;
                if (empty($validated['descricao_longa'])) $validated['descricao_longa'] = $ref->descricao_longa;
                if (empty($validated['produto_pai'])) $validated['produto_pai'] = $ref->produto_pai;
                if (empty($validated['cliente_obs'])) $validated['cliente_obs'] = $ref->cliente_obs;
                if (empty($validated['pedido'])) $validated['pedido'] = $ref->pedido;
            }
        }

        // Prevenção de duplicidade: atualiza item existente com mesmo Código + OP (+ Pedido se houver)
        $existente = null;
        if (!empty($codClean) && !empty($opClean)) {
            $existente = EstoqueItem::where('codigo_produto', $codClean)
                ->where('op', $opClean)
                ->when(!empty($pedidoClean), function($q) use ($pedidoClean) {
                    $q->where('pedido', $pedidoClean);
                })->first();
        }

        if ($existente) {
            $existente->update([
                'quantidade' => $qtdOp,
                'quantidade_estoque' => $qtdEstoque,
                'status' => $validated['status'],
                'observacao_estoque' => $validated['observacao_estoque'] ?? $existente->observacao_estoque,
                'updated_by' => auth()->user()->name ?? 'Sistema',
            ]);
            $estoqueItem = $existente;
            $msgMsg = 'Item existente encontrado e atualizado com sucesso no Estoque!';
        } else {
            $validated['updated_by'] = auth()->user()->name ?? 'Sistema';
            $estoqueItem = EstoqueItem::create($validated);
            $msgMsg = 'Novo item adicionado ao Estoque com sucesso!';
        }

        // Busca prévia de valor unitário e fornecedor na SC7010
        $prevCompra = $this->protheusService->getUltimoPrecoFornecedor($validated['codigo_produto']);
        $valQtdComprar = max(0, $qtdOp - $qtdEstoque);
        $valUnitario = floatval($prevCompra['valor_unitario'] ?? ($prevCompra['preco'] ?? 0));
        $codFornecedor = $prevCompra['codigo_fornecedor'] ?? ($prevCompra['fornecedor'] ?? null);

        CompraItem::updateOrCreate(
            ['estoque_item_id' => $estoqueItem->id],
            [
                'codigo_fornecedor' => $codFornecedor,
                'valor_unitario' => $valUnitario,
                'ipi' => 0,
                'frete' => 0,
                'valor_total' => $valUnitario * $valQtdComprar,
                'status_pagamento' => 'PENDENTE',
            ]
        );

        return redirect()->route('estoque.index')->with('success', $msgMsg);
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

        $validated['updated_by'] = auth()->user()->name ?? 'Sistema';

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
                $updateData['updated_by'] = auth()->user()->name ?? 'Sistema';
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
