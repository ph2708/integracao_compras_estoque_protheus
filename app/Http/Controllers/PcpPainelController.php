<?php

namespace App\Http\Controllers;

use App\Models\EstoqueItem;
use App\Models\CompraItem;
use App\Models\PvMetadado;
use App\Services\ProtheusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PcpPainelController extends Controller
{
    protected ProtheusService $protheusService;

    public function __construct(ProtheusService $protheusService)
    {
        $this->protheusService = $protheusService;
    }

    /**
     * Auxiliar para filtro de múltipla seleção (separado por vírgula)
     */
    private function matchMultiFilter(?string $itemValue, ?string $filterValue, bool $isExact = false): bool
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
     * Exibe o Painel Gerencial PCP GMGs agrupado por Pedido de Venda (PV)
     */
    public function index(Request $request)
    {
        $searchPv = $request->get('search_pv');
        $searchCliente = $request->get('search_cliente');
        $searchStatusPcp = $request->get('search_status_pcp');
        $searchStatusPagamento = $request->get('search_status_pagamento');

        // Filtros Multi-Seleção Estilo Excel
        $fInfo = $request->get('f_info');
        $fStatusPv = $request->get('f_status_pv');
        $fFabrica = $request->get('f_fabrica');
        $fMarca = $request->get('f_marca');

        // Carregar Metadados de PVs cadastrados no banco
        $pvMetadados = PvMetadado::all()->keyBy('pedido');

        // Query base trazendo itens do Estoque com relacionamento de Compras
        $query = EstoqueItem::with('compraItem');

        if ($searchPv) {
            $query->where('pedido', 'like', '%' . $searchPv . '%');
        }
        if ($searchCliente) {
            $terms = array_filter(array_map('trim', explode(',', $searchCliente)));
            if (!empty($terms)) {
                $query->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->orWhere('cliente_obs', 'like', '%' . $term . '%');
                    }
                });
            }
        }
        if ($searchStatusPagamento) {
            $query->whereHas('compraItem', function ($q) use ($searchStatusPagamento) {
                if (in_array($searchStatusPagamento, ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO'])) {
                    $q->whereIn('status_pagamento', ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO']);
                } else {
                    $q->where('status_pagamento', $searchStatusPagamento);
                }
            });
        }

        $allEstoqueItems = $query->orderBy('pedido', 'desc')->get();

        // Agrupar itens por Pedido de Venda (PV / C2_PEDIDO)
        $grouped = $allEstoqueItems->groupBy(function ($item) {
            return $item->pedido ? trim($item->pedido) : 'SEM_PEDIDO';
        });

        $painelData = collect();
        $opcoesInfo = collect();
        $opcoesStatusPv = collect();
        $opcoesFabrica = collect();
        $opcoesMarca = collect();

        foreach ($grouped as $pvNum => $items) {
            if ($pvNum === 'SEM_PEDIDO' && $items->isEmpty()) continue;

            $clienteObs = $items->pluck('cliente_obs')->filter()->first() ?? '-';

            // Priorizar o produto_pai que contém a sigla GMG no texto após o hífen (-)
            $gmgProdutoPai = $items->pluck('produto_pai')->filter(function ($p) {
                if (empty($p)) return false;
                $parts = explode('-', $p, 2);
                if (count($parts) > 1) {
                    $afterHyphen = trim($parts[1]);
                    return str_starts_with(strtoupper($afterHyphen), 'GMG') || str_contains(strtoupper($afterHyphen), 'GMG');
                }
                return str_contains(strtoupper($p), 'GMG');
            })->first();

            $produtoPai = $gmgProdutoPai ?: ($items->pluck('produto_pai')->filter()->first() ?? '-');

            // Buscar metadados salvos do PV
            $meta = $pvMetadados->get($pvNum);
            $valInfo = $meta ? ($meta->info ?? '') : '';
            $valStatusPv = $meta ? ($meta->status_pv ?? '') : '';
            $valFabrica = $meta ? ($meta->fabrica ?? '') : '';
            $valMarca = $meta ? ($meta->marca ?? '') : '';

            // Tentar inferir Marca se não cadastrado
            if (empty($valMarca)) {
                foreach ($items as $it) {
                    $d = strtoupper($it->descricao . ' ' . $it->descricao_longa);
                    if (str_contains($d, 'SCANIA')) { $valMarca = 'SCANIA'; break; }
                    if (str_contains($d, 'PERKINS')) { $valMarca = 'PERKINS'; break; }
                    if (str_contains($d, 'FPT')) { $valMarca = 'FPT'; break; }
                    if (str_contains($d, 'VOLVO')) { $valMarca = 'VOLVO'; break; }
                }
            }

            if (!empty($valInfo)) $opcoesInfo->push($valInfo);
            if (!empty($valStatusPv)) $opcoesStatusPv->push($valStatusPv);
            if (!empty($valFabrica)) $opcoesFabrica->push($valFabrica);
            if (!empty($valMarca)) $opcoesMarca->push($valMarca);

            // Aplicar filtros multi-seleção
            if ($fInfo && !$this->matchMultiFilter($valInfo, $fInfo)) continue;
            if ($fStatusPv && !$this->matchMultiFilter($valStatusPv, $fStatusPv, true)) continue;
            if ($fFabrica && !$this->matchMultiFilter($valFabrica, $fFabrica, true)) continue;
            if ($fMarca && !$this->matchMultiFilter($valMarca, $fMarca)) continue;

            $totalComponentes = $items->count();
            $totalFalta = $items->where('status', 'FALTA')->count();
            $totalSeparado = $items->where('status', 'SEPARADO')->count();
            $totalRetirado = $items->where('status', 'RETIRADO')->count();
            $totalFabrica = $items->whereIn('status', ['FABRICA', 'FABRICAR INTERNO KANBAN'])->count();

            // Porcentagem de Separação
            $percentSeparado = $totalComponentes > 0 ? round(($totalSeparado / $totalComponentes) * 100, 1) : 0;

            // Status PCP Geral dos Componentes
            if ($totalFalta > 0) {
                $statusPcpGeral = 'FALTA';
                $statusBadgeClass = 'badge-falta';
            } elseif ($totalFabrica > 0) {
                $statusPcpGeral = 'FABRICA';
                $statusBadgeClass = 'badge-fabrica';
            } elseif ($totalSeparado === $totalComponentes) {
                $statusPcpGeral = 'SEPARADO';
                $statusBadgeClass = 'badge-separado';
            } else {
                $statusPcpGeral = 'PARCIAL';
                $statusBadgeClass = 'badge-kanban';
            }

            if ($searchStatusPcp && $statusPcpGeral !== $searchStatusPcp) {
                continue;
            }

            // Alertas de Compras e Financeiros
            $semPedidoCompraCount = 0;
            $semPrecoCount = 0;
            $investimentoPendente = 0;

            // Componentes Críticos
            $motorStatus = 'OK';
            $alternadorStatus = 'OK';
            $baseStatus = 'OK';
            $carenagemStatus = 'OK';

            $hasMotor = false;
            $hasAlternador = false;
            $hasBase = false;
            $hasCarenagem = false;

            foreach ($items as $it) {
                $cItem = $it->compraItem;
                $valUnit = $cItem ? floatval($cItem->valor_unitario) : 0;
                $valTotal = $cItem ? floatval($cItem->valor_total) : 0;

                if ($it->status === 'FALTA') {
                    if (!$cItem || empty(trim($cItem->pedido_compra ?? ''))) {
                        $semPedidoCompraCount++;
                    }
                    if ($valUnit <= 0) {
                        $semPrecoCount++;
                    }
                    $investimentoPendente += $valTotal;
                }

                $desc = strtoupper($it->descricao . ' ' . $it->descricao_longa . ' ' . $it->codigo_produto);
                if (str_contains($desc, 'MOTOR')) {
                    $hasMotor = true;
                    if ($it->status === 'FALTA') {
                        $motorStatus = $valTotal > 0 ? 'R$ ' . number_format($valTotal, 2, ',', '.') : 'FALTA';
                    }
                }
                if (str_contains($desc, 'ALTERNADOR')) {
                    $hasAlternador = true;
                    if ($it->status === 'FALTA') {
                        $alternadorStatus = $valTotal > 0 ? 'R$ ' . number_format($valTotal, 2, ',', '.') : 'FALTA';
                    }
                }
                if (str_contains($desc, 'BASE')) {
                    $hasBase = true;
                    if ($it->status === 'FALTA') {
                        $baseStatus = $valTotal > 0 ? 'R$ ' . number_format($valTotal, 2, ',', '.') : 'PEN';
                    }
                }
                if (str_contains($desc, 'CARENAGEM') || str_contains($desc, 'SILENCIOSO') || str_contains($desc, 'CHASSIS')) {
                    $hasCarenagem = true;
                    if ($it->status === 'FALTA') {
                        $carenagemStatus = $valTotal > 0 ? 'R$ ' . number_format($valTotal, 2, ',', '.') : 'PEN';
                    }
                }
            }

            if (!$hasMotor) $motorStatus = '-';
            if (!$hasAlternador) $alternadorStatus = '-';
            if (!$hasBase) $baseStatus = '-';
            if (!$hasCarenagem) $carenagemStatus = '-';

            $painelData->push([
                'pv' => $pvNum,
                'cliente' => $clienteObs,
                'produto_pai' => $produtoPai,
                'info' => $valInfo,
                'status_pv' => $valStatusPv,
                'fabrica' => $valFabrica,
                'marca' => $valMarca,
                'total_componentes' => $totalComponentes,
                'total_falta' => $totalFalta,
                'total_separado' => $totalSeparado,
                'percent_separado' => $percentSeparado,
                'status_pcp_geral' => $statusPcpGeral,
                'status_badge_class' => $statusBadgeClass,
                'sem_pedido_compra_count' => $semPedidoCompraCount,
                'sem_preco_count' => $semPrecoCount,
                'investimento_pendente' => $investimentoPendente,
                'motor_status' => $motorStatus,
                'alternador_status' => $alternadorStatus,
                'base_status' => $baseStatus,
                'carenagem_status' => $carenagemStatus,
                'items' => $items,
            ]);
        }

        // Ordenar a coleção do painel em sequência numérica pela coluna FÁBRICA (18, 19, 20... 99)
        $painelData = $painelData->sortBy(function ($item) {
            $fab = trim($item['fabrica'] ?? '');
            $numFab = (is_numeric($fab) && intval($fab) > 0) ? intval($fab) : 999999;
            return sprintf('%08d_%s', $numFab, $item['pv']);
        })->values();

        // Opções de Filtro Únicas
        $opcoesInfo = $opcoesInfo->unique()->sort()->values();
        $opcoesStatusPv = collect(['FATURADO', 'COMPRAS', 'ENGENHARIA', 'ESTOQUE', 'ENTREGUE', 'FINANCEIRO', 'CANCELADO'])->merge($opcoesStatusPv)->unique()->values();
        $opcoesFabrica = $opcoesFabrica->unique()->sort()->values();
        $opcoesMarca = $opcoesMarca->unique()->sort()->values();

        // Métricas Globais dos KPIs Superiores
        $kpiTotalPv = $painelData->count();
        $kpiMediaSeparacao = $painelData->avg('percent_separado') ?? 0;
        $kpiInvestimentoTotal = $painelData->sum('investimento_pendente');
        $kpiPvsComFalta = $painelData->where('total_falta', '>', 0)->count();

        // Lista de Filiais do Protheus
        $filiaisProtheus = $this->protheusService->listarFiliais();
        if (empty($filiaisProtheus)) {
            $filiaisProtheus = ['01', '02', '03', '04', '05', '10', '15', '20', '22', '25', '30'];
        }

        return view('pcp_painel.index', compact(
            'painelData',
            'searchPv',
            'searchCliente',
            'searchStatusPcp',
            'searchStatusPagamento',
            'fInfo',
            'fStatusPv',
            'fFabrica',
            'fMarca',
            'opcoesInfo',
            'opcoesStatusPv',
            'opcoesFabrica',
            'opcoesMarca',
            'kpiTotalPv',
            'kpiMediaSeparacao',
            'kpiInvestimentoTotal',
            'kpiPvsComFalta',
            'filiaisProtheus'
        ));
    }

    /**
     * Consulta itens no Protheus e agrupa por PV com suporte a seleção por checkboxes
     */
    public function consultarProtheus(Request $request)
    {
        $pedido = $request->input('pedido');
        $filiais = $request->input('filiais');

        if (empty($pedido)) {
            return response()->json(['success' => false, 'message' => 'Informe o número do pedido para consultar.']);
        }

        $items = $this->protheusService->getItensPorPedido($pedido, $filiais);

        if (empty($items)) {
            return response()->json(['success' => false, 'message' => 'Nenhum item ou PV encontrado no Protheus para a consulta.']);
        }

        // Agrupar por PV (C2_PEDIDO)
        $grouped = collect($items)->groupBy(function ($it) {
            return isset($it['pedido']) ? trim($it['pedido']) : 'SEM_PEDIDO';
        });

        $pvsResult = [];
        foreach ($grouped as $pvNum => $pvItems) {
            $clienteObs = $pvItems->pluck('cliente_obs')->filter()->first() ?? '-';
            $produtoPai = $pvItems->pluck('produto_pai')->filter()->first() ?? '-';

            $pvsResult[] = [
                'pv' => $pvNum,
                'cliente' => $clienteObs,
                'produto_pai' => $produtoPai,
                'count' => $pvItems->count(),
                'items' => $pvItems->values()->all(),
            ];
        }

        return response()->json([
            'success' => true,
            'count_pvs' => count($pvsResult),
            'total_items' => count($items),
            'pvs' => $pvsResult,
        ]);
    }

    /**
     * Importação dos PVs e itens selecionados via Checkboxes
     */
    public function importarPvsSelecionados(Request $request)
    {
        $itemsJson = $request->input('items_json');
        $itemsData = json_decode($itemsJson, true) ?? [];

        if (empty($itemsData)) {
            return redirect()->back()->with('error', 'Nenhum item/PV selecionado para importação.');
        }

        // Prévia em lote de preços e fornecedores do último PC do Protheus
        $codigosProdutos = array_filter(array_column($itemsData, 'codigo_produto'));
        $precosBatch = $this->protheusService->getUltimosPrecosBatch($codigosProdutos);

        $importedCount = 0;
        $pvsImportados = [];

        DB::transaction(function () use ($itemsData, $precosBatch, &$importedCount, &$pvsImportados) {
            foreach ($itemsData as $row) {
                if (empty($row['codigo_produto']) || empty($row['op'])) continue;

                $pvNum = trim($row['pedido'] ?? '');
                if ($pvNum) $pvsImportados[$pvNum] = true;

                $qtdOp = floatval($row['quantidade'] ?? 0);
                $qtdEstoque = floatval($row['quantidade_estoque'] ?? 0);
                $qtdComprar = max(0, $qtdOp - $qtdEstoque);

                $statusDefault = ($qtdEstoque >= $qtdOp && $qtdOp > 0) ? 'SEPARADO' : 'FALTA';

                $estoqueItem = EstoqueItem::updateOrCreate(
                    [
                        'codigo_produto' => $row['codigo_produto'],
                        'op'             => $row['op'],
                    ],
                    [
                        'descricao'          => $row['descricao'] ?? '',
                        'descricao_longa'    => $row['descricao_longa'] ?? null,
                        'produto_pai'        => $row['produto_pai'] ?? null,
                        'pedido'             => $pvNum ?: null,
                        'cliente_obs'        => $row['cliente_obs'] ?? null,
                        'quantidade'         => $qtdOp,
                        'quantidade_estoque' => $qtdEstoque,
                        'status'             => $statusDefault,
                    ]
                );

                $codProd = $row['codigo_produto'];
                $sugestao = $precosBatch[$codProd] ?? null;

                $compraItem = CompraItem::where('estoque_item_id', $estoqueItem->id)->first();
                if (!$compraItem) {
                    $fornecedorSug = $sugestao ? ($sugestao['codigo_fornecedor'] ?? null) : null;
                    $valUnitSug    = $sugestao ? floatval($sugestao['valor_unitario'] ?? 0) : 0;
                    $valTotalSug   = $qtdComprar * $valUnitSug;

                    CompraItem::create([
                        'estoque_item_id'   => $estoqueItem->id,
                        'codigo_fornecedor' => $fornecedorSug,
                        'valor_unitario'    => $valUnitSug,
                        'valor_total'       => $valTotalSug,
                    ]);
                }

                $importedCount++;
            }

            // Criar entrada em pv_metadados para cada PV importado se não existir
            foreach (array_keys($pvsImportados) as $pvNum) {
                PvMetadado::firstOrCreate(
                    ['pedido' => $pvNum],
                    ['status_pv' => 'COMPRAS', 'fabrica' => '99']
                );
            }
        });

        return redirect()->route('pcp-painel.index')->with('success', "Importados {$importedCount} componentes de " . count($pvsImportados) . " Pedidos de Venda com sucesso!");
    }

    /**
     * Criação Manual de um Pedido de Venda (PV)
     */
    public function storeManual(Request $request)
    {
        $request->validate([
            'pedido' => 'required|string',
            'cliente_obs' => 'nullable|string',
            'produto_pai' => 'nullable|string',
        ]);

        $pvNum = trim($request->input('pedido'));
        $cliente = trim($request->input('cliente_obs', ''));
        $prodPai = trim($request->input('produto_pai', ''));

        // Salvar Metadados do PV
        PvMetadado::updateOrCreate(
            ['pedido' => $pvNum],
            [
                'info' => trim($request->input('info', '')),
                'status_pv' => trim($request->input('status_pv', 'COMPRAS')),
                'fabrica' => trim($request->input('fabrica', '99')),
                'marca' => trim($request->input('marca', '')),
            ]
        );

        // Criar registro base em estoque_items caso o PV não possua itens cadastrados
        $exists = EstoqueItem::where('pedido', $pvNum)->exists();
        if (!$exists) {
            $eItem = EstoqueItem::create([
                'codigo_produto' => 'GMG-MANUAL-' . strtoupper(substr(md5(time()), 0, 5)),
                'op' => 'OP-' . $pvNum . '-01',
                'pedido' => $pvNum,
                'cliente_obs' => $cliente ?: $pvNum,
                'produto_pai' => $prodPai ?: 'GERADOR GMG MANUAL',
                'descricao' => 'EQUIPAMENTO GERADOR GMG (PV MANUAL)',
                'quantidade' => 1,
                'quantidade_estoque' => 0,
                'status' => 'FALTA',
            ]);

            CompraItem::create([
                'estoque_item_id' => $eItem->id,
                'valor_unitario' => 0,
                'valor_total' => 0,
            ]);
        }

        return redirect()->route('pcp-painel.index')->with('success', "Pedido de Venda {$pvNum} cadastrado manualmente com sucesso!");
    }

    /**
     * Exclusão de um Pedido de Venda e todas as suas matérias-primas da base
     */
    public function destroyPv(Request $request)
    {
        $pvNum = trim($request->input('pedido'));

        if (empty($pvNum)) {
            return redirect()->back()->with('error', 'Pedido de Venda inválido para exclusão.');
        }

        DB::transaction(function () use ($pvNum) {
            $itemIds = EstoqueItem::where('pedido', $pvNum)->pluck('id');
            if ($itemIds->isNotEmpty()) {
                CompraItem::whereIn('estoque_item_id', $itemIds)->delete();
                EstoqueItem::whereIn('id', $itemIds)->delete();
            }
            PvMetadado::where('pedido', $pvNum)->delete();
        });

        return redirect()->route('pcp-painel.index')->with('success', "Pedido de Venda {$pvNum} e seus componentes foram excluídos com sucesso!");
    }

    /**
     * Atualização individual de um Pedido de Venda via Modal do Lápis
     */
    public function updateSinglePv(Request $request)
    {
        $request->validate([
            'pedido' => 'required|string',
        ]);

        $pvNum = trim($request->input('pedido'));
        $cliente = trim($request->input('cliente_obs', ''));
        $prodPai = trim($request->input('produto_pai', ''));
        $info = trim($request->input('info', ''));
        $statusPv = trim($request->input('status_pv', ''));
        $fabrica = trim($request->input('fabrica', ''));
        $marca = trim($request->input('marca', ''));

        // Atualizar Metadados do PV
        PvMetadado::updateOrCreate(
            ['pedido' => $pvNum],
            [
                'info' => $info ?: null,
                'status_pv' => $statusPv ?: null,
                'fabrica' => $fabrica ?: null,
                'marca' => $marca ?: null,
            ]
        );

        // Atualizar cliente_obs e produto_pai nos componentes de estoque
        if ($cliente || $prodPai) {
            $updates = [];
            if ($cliente) $updates['cliente_obs'] = $cliente;
            if ($prodPai) $updates['produto_pai'] = $prodPai;
            
            EstoqueItem::where('pedido', $pvNum)->update($updates);
        }

        return redirect()->route('pcp-painel.index')->with('success', "Pedido de Venda {$pvNum} atualizado com sucesso!");
    }

    /**
     * Atualização em Lote dos Metadados do Pedido de Venda (INFO, STATUS, FÁBRICA, MARCA)
     */
    public function updateBatch(Request $request)
    {
        $pvsData = $request->input('pvs', []);

        if (is_array($pvsData) && !empty($pvsData)) {
            foreach ($pvsData as $pvNum => $data) {
                if (empty($pvNum)) continue;

                PvMetadado::updateOrCreate(
                    ['pedido' => trim($pvNum)],
                    [
                        'info' => isset($data['info']) ? trim($data['info']) : null,
                        'status_pv' => isset($data['status_pv']) ? trim($data['status_pv']) : null,
                        'fabrica' => isset($data['fabrica']) ? trim($data['fabrica']) : null,
                        'marca' => isset($data['marca']) ? trim($data['marca']) : null,
                    ]
                );
            }
        }

        return redirect()->back()->with('success', 'Alterações do Painel PCP salvas com sucesso!');
    }
}
