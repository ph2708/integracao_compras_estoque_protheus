<?php

namespace App\Http\Controllers;

use App\Models\EstoqueItem;
use App\Models\CompraItem;
use App\Models\PvMetadado;
use App\Services\ProtheusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

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
        if (!auth()->user()->canAccessPcp()) {
            abort(403, 'Acesso não autorizado ao Painel PCP.');
        }
        $canEditPcp = auth()->user()->canEditPcp();

        $searchPv = $request->get('search_pv');
        $searchCliente = $request->get('search_cliente');
        $searchStatusPcp = $request->get('search_status_pcp');
        $searchStatusPagamento = $request->get('search_status_pagamento');

        // Filtros Multi-Seleção Estilo Excel
        $fInfo = $request->get('f_info');
        $fStatusPv = $request->get('f_status_pv');
        $fFabrica = $request->get('f_fabrica');
        $fMarca = $request->get('f_marca');
        $fDataPronto = $request->get('f_data_pronto');
        $fDataProntoDe = $request->get('f_data_pronto_de');
        $fDataProntoAte = $request->get('f_data_pronto_ate');
        $fDataProntoMes = $request->get('f_data_pronto_mes');

        // Carregar Metadados de PVs cadastrados no banco
        $pvMetadados = PvMetadado::all()->keyBy('pedido');

        // Query base trazendo itens do Estoque com relacionamento de Compras
        $query = EstoqueItem::with('compraItem');

        if ($searchPv) {
            $pvTokens = array_filter(array_map('trim', explode(',', $searchPv)));
            if (!empty($pvTokens)) {
                $query->where(function ($q) use ($pvTokens) {
                    foreach ($pvTokens as $tok) {
                        $q->orWhere('pedido', 'like', '%' . $tok . '%');
                        if (is_numeric($tok) && strlen($tok) < 6) {
                            $padded = str_pad($tok, 6, '0', STR_PAD_LEFT);
                            $q->orWhere('pedido', 'like', '%' . $padded . '%');
                        }
                    }
                });
            }
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
            $stPagTokens = array_filter(array_map('trim', explode(',', $searchStatusPagamento)));
            if (!empty($stPagTokens)) {
                $query->whereHas('compraItem', function ($q) use ($stPagTokens) {
                    $q->where(function ($sub) use ($stPagTokens) {
                        foreach ($stPagTokens as $tok) {
                            if (in_array(strtoupper($tok), ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO'])) {
                                $sub->orWhereIn('status_pagamento', ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO']);
                            } else {
                                $sub->orWhere('status_pagamento', $tok);
                            }
                        }
                    });
                });
            }
        }

        $allEstoqueItems = $query->orderBy('pedido', 'desc')->get();

        // Agrupar itens por Pedido de Venda (PV / C2_PEDIDO)
        $grouped = $allEstoqueItems->groupBy(function ($item) {
            return $item->pedido ? trim($item->pedido) : 'SEM_PEDIDO';
        });

        // Buscar os Valores Brutos reais dos Pedidos de Venda diretamente na tabela SC6010 do Protheus
        $allPvKeys = array_filter(array_keys($grouped->toArray()), function($k) { return $k && $k !== 'SEM_PEDIDO'; });
        $protheusValoresBrutos = $this->protheusService->getValoresBrutosPvs($allPvKeys);

        $painelData = collect();
        $opcoesInfo = collect();
        $opcoesStatusPv = collect();
        $opcoesFabrica = collect();
        $opcoesMarca = collect();
        $opcoesDataPronto = collect();

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
            $valQtd = $meta ? ($meta->qtd ?? 1) : 1;
            $valTimeProd = $meta ? ($meta->time_prod ?? '0') : '0';
            $valDataEmissao = $meta ? ($meta->data_emissao ?? '-') : '-';
            $valDataContratual = $meta ? ($meta->data_contratual ?? '-') : '-';
            $valDataPaPg = $meta ? ($meta->data_pa_pg ?? '-') : '-';
            $valDataPronto = $meta ? ($meta->data_pronto ?? '-') : '-';
            $valDataBoom = $meta ? ($meta->data_boom ?? '-') : '-';
            $valDataLiberacaoEstoque = $meta ? ($meta->data_liberacao_estoque ?? '-') : '-';
            $valMetaValorBruto = $meta ? ($meta->valor_bruto ?? null) : null;
            $valUpdatedBy = $meta ? ($meta->updated_by ?? '-') : '-';
            $valUpdatedAt = ($meta && $meta->updated_at) ? $meta->updated_at->format('d/m/Y H:i') : '-';

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
            if (!empty($valDataPronto) && $valDataPronto !== '-') $opcoesDataPronto->push($valDataPronto);

            // Aplicar filtros multi-seleção e intervalo de datas (Data Pronto)
            $parsedValPronto = $this->parseDateToYmd($valDataPronto);
            $parsedDe = $this->parseDateToYmd($fDataProntoDe);
            $parsedAte = $this->parseDateToYmd($fDataProntoAte);

            if ($parsedDe && (!$parsedValPronto || $parsedValPronto < $parsedDe)) continue;
            if ($parsedAte && (!$parsedValPronto || $parsedValPronto > $parsedAte)) continue;

            if ($fDataProntoMes) {
                $mesTarget = str_pad($fDataProntoMes, 2, '0', STR_PAD_LEFT);
                if ($parsedValPronto) {
                    $itemMonth = substr($parsedValPronto, 5, 2);
                    if ($itemMonth !== $mesTarget) continue;
                } else {
                    $mesesNomes = [
                        '01' => ['JAN', 'JANEIRO', '/01'], '02' => ['FEV', 'FEVEREIRO', '/02'],
                        '03' => ['MAR', 'MARÇO', 'MARCO', '/03'], '04' => ['ABR', 'ABRIL', '/04'],
                        '05' => ['MAI', 'MAIO', '/05'], '06' => ['JUN', 'JUNHO', '/06'],
                        '07' => ['JUL', 'JULHO', '/07'], '08' => ['AGO', 'AGOSTO', '/08'],
                        '09' => ['SET', 'SETEMBRO', '/09'], '10' => ['OUT', 'OUTUBRO', '/10'],
                        '11' => ['NOV', 'NOVEMBRO', '/11'], '12' => ['DEZ', 'DEZEMBRO', '/12']
                    ];
                    $terms = $mesesNomes[$mesTarget] ?? ["/{$mesTarget}"];
                    $valUpper = strtoupper($valDataPronto);
                    $matched = false;
                    foreach ($terms as $t) {
                        if (str_contains($valUpper, $t)) { $matched = true; break; }
                    }
                    if (!$matched) continue;
                }
            }

            if ($fInfo && !$this->matchMultiFilter($valInfo, $fInfo)) continue;
            if ($fStatusPv && !$this->matchMultiFilter($valStatusPv, $fStatusPv, true)) continue;
            if ($fFabrica && !$this->matchMultiFilter($valFabrica, $fFabrica, true)) continue;
            if ($fMarca && !$this->matchMultiFilter($valMarca, $fMarca)) continue;
            if ($fDataPronto && !$this->matchMultiFilter($valDataPronto, $fDataPronto)) continue;

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

            if ($searchStatusPcp && !$this->matchMultiFilter($statusPcpGeral, $searchStatusPcp, true)) {
                continue;
            }

            // Alertas de Compras e Financeiros
            $semPedidoCompraCount = 0;
            $semPrecoCount = 0;
            $investimentoPendente = 0;
            $valorBruto = 0;
            $valorPa = 0;
            $valorFaturado = 0;
            $valorPago = 0;

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
                $valorBruto += $valTotal;

                if ($cItem) {
                    $stPag = strtoupper(trim($cItem->status_pagamento ?? ''));
                    if (in_array($stPag, ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO'])) {
                        $valorPa += $valTotal;
                    } elseif ($stPag === 'FATURADO') {
                        $valorFaturado += $valTotal;
                    } elseif ($stPag === 'PAGO') {
                        $valorPago += $valTotal;
                    }
                }

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
                $descClean = strtoupper(trim($it->descricao));
                $codClean = strtoupper(trim($it->codigo_produto));

                // Exclusão de acessórios (motorização, disjuntor motorizado, suporte de motor, cabo, etc.)
                $isIgnoredMotor = str_contains($descClean, 'MOTORIZ') || 
                                  str_contains($descClean, 'SUPORTE') || 
                                  str_contains($descClean, 'SPT MOTOR') || 
                                  str_contains($descClean, 'FIX DJ') || 
                                  str_contains($descClean, 'DISJUNTOR') || 
                                  str_contains($descClean, 'MOT 220V') || 
                                  str_contains($descClean, 'MOT 24V') || 
                                  str_contains($descClean, 'MOT ABB') || 
                                  str_contains($descClean, 'ELETROBOMBA') || 
                                  str_contains($descClean, 'CABO');

                $isMotor = !$isIgnoredMotor && (
                    str_starts_with($descClean, 'MOTOR ') || 
                    (str_contains($descClean, 'MOTOR') && (
                        str_contains($descClean, 'FPT') || 
                        str_contains($descClean, 'SCANIA') || 
                        str_contains($descClean, 'PERKINS') || 
                        str_contains($descClean, 'MWM') || 
                        str_contains($descClean, 'CUMMINS') || 
                        str_contains($descClean, 'VOLVO') || 
                        str_contains($descClean, 'WEG') || 
                        str_contains($descClean, 'DIESEL')
                    ))
                );

                if ($isMotor) {
                    $hasMotor = true;
                    if ($it->status === 'FALTA') {
                        $motorStatus = $valTotal > 0 ? 'R$ ' . number_format($valTotal, 2, ',', '.') : 'FALTA';
                    }
                }

                $isIgnoredAlternador = str_starts_with($descClean, 'SPT') || 
                                       str_starts_with($descClean, 'SUPORTE') || 
                                       str_contains($descClean, 'SUPORTE') || 
                                       str_contains($descClean, 'SPT ') || 
                                       str_contains($descClean, 'SPT-') || 
                                       str_contains($descClean, 'CAIXA ALTERNADOR') || 
                                       str_contains($descClean, 'POLIA') || 
                                       str_contains($descClean, 'CORREIA') || 
                                       str_contains($descClean, 'CABO');

                $isAlternador = !$isIgnoredAlternador && (
                    str_starts_with($codClean, 'GS') || 
                    str_starts_with($descClean, 'GS ') || 
                    str_starts_with($descClean, 'GS-') || 
                    str_starts_with($descClean, 'GSWG') || 
                    str_starts_with($descClean, 'GS WEG') || 
                    str_starts_with($descClean, 'GS WG') || 
                    str_starts_with($descClean, 'ALTERNADOR')
                );

                if ($isAlternador) {
                    $hasAlternador = true;
                    if ($it->status === 'FALTA') {
                        $alternadorStatus = $valTotal > 0 ? 'R$ ' . number_format($valTotal, 2, ',', '.') : 'FALTA';
                    }
                }
                $isIgnoredBase = str_contains($descClean, 'BASE RELE') || 
                                 str_contains($descClean, 'BASE RÊLE') || 
                                 str_contains($descClean, 'BASE P/ RL') || 
                                 str_contains($descClean, 'BASE PARA RELE') || 
                                 str_contains($descClean, 'BASE PARA INTTRV') || 
                                 str_contains($descClean, 'BASE INTTRV') || 
                                 str_contains($descClean, 'SUPORTE') || 
                                 str_contains($descClean, 'SPT ') || 
                                 str_contains($descClean, 'TQ COMB');

                $isBase = !$isIgnoredBase && (
                    str_starts_with($descClean, 'BS ') || 
                    str_starts_with($descClean, 'BS-') || 
                    str_starts_with($descClean, 'BS KSE') || 
                    str_starts_with($descClean, 'BS WG') || 
                    str_starts_with($descClean, 'BS WEG') || 
                    str_starts_with($codClean, 'BS') || 
                    str_contains($descClean, 'BASE ESTRUTURAL') || 
                    str_contains($descClean, 'BASE CHASSI') || 
                    str_contains($descClean, 'BASE GERADOR') || 
                    str_starts_with($descClean, 'BASE ') || 
                    $descClean === 'BASE'
                );

                if ($isBase) {
                    $hasBase = true;
                    if ($it->status === 'FALTA') {
                        $baseStatus = $valTotal > 0 ? 'R$ ' . number_format($valTotal, 2, ',', '.') : 'PEN';
                    }
                }
                $isIgnoredCarenagem = str_contains($descClean, 'BOTAO') || 
                                      str_contains($descClean, 'BOTÃO') || 
                                      str_contains($descClean, 'CHAVE') || 
                                      str_contains($descClean, 'FECHO') || 
                                      str_contains($descClean, 'EXTENSAO') || 
                                      str_contains($descClean, 'EXTENSÃO') || 
                                      str_contains($descClean, 'ADESIVO') || 
                                      str_contains($descClean, 'PLACA');

                $isCarenagem = !$isIgnoredCarenagem && (
                    str_starts_with($descClean, 'CARENAGEM') || 
                    str_starts_with($descClean, 'CRN ') || 
                    str_starts_with($descClean, 'CRN-') || 
                    str_starts_with($codClean, 'CRN') || 
                    str_starts_with($descClean, 'CAR ') || 
                    str_starts_with($descClean, 'CAR-') || 
                    str_starts_with($codClean, 'CAR') || 
                    str_contains($descClean, 'CARENAGEM')
                );

                if ($isCarenagem) {
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

            // Valor Bruto Real do PV: 1. Override Manual do Usuário | 2. Valor Real do Protheus SC6010 (Soma C6_VALOR) | 3. Acumulado de Componentes
            $somaComponentesBruto = $valorBruto;
            if ($valMetaValorBruto !== null && floatval($valMetaValorBruto) > 0) {
                $valorBruto = floatval($valMetaValorBruto);
            } elseif (isset($protheusValoresBrutos[$pvNum]) && floatval($protheusValoresBrutos[$pvNum]) > 0) {
                $valorBruto = floatval($protheusValoresBrutos[$pvNum]);
            }

            $painelData->push([
                'pv' => $pvNum,
                'cliente' => $clienteObs,
                'produto_pai' => $produtoPai,
                'info' => $valInfo,
                'status_pv' => $valStatusPv,
                'fabrica' => $valFabrica,
                'marca' => $valMarca,
                'qtd' => $valQtd,
                'time_prod' => $valTimeProd,
                'data_emissao' => $valDataEmissao,
                'data_contratual' => $valDataContratual,
                'data_pa_pg' => $valDataPaPg,
                'data_pronto' => $valDataPronto,
                'data_boom' => $valDataBoom,
                'data_liberacao_estoque' => $valDataLiberacaoEstoque,
                'total_componentes' => $totalComponentes,
                'total_falta' => $totalFalta,
                'total_separado' => $totalSeparado,
                'percent_separado' => $percentSeparado,
                'status_pcp_geral' => $statusPcpGeral,
                'status_badge_class' => $statusBadgeClass,
                'sem_pedido_compra_count' => $semPedidoCompraCount,
                'sem_preco_count' => $semPrecoCount,
                'valor_bruto' => $valorBruto,
                'investimento_pendente' => $investimentoPendente,
                'valor_pa' => $valorPa,
                'valor_faturado' => $valorFaturado,
                'valor_pago' => $valorPago,
                'motor_status' => $motorStatus,
                'alternador_status' => $alternadorStatus,
                'base_status' => $baseStatus,
                'carenagem_status' => $carenagemStatus,
                'updated_by' => $valUpdatedBy,
                'updated_at' => $valUpdatedAt,
                'items' => $items,
            ]);
        }

        // Ordenar a coleção do painel em sequência numérica pela coluna FÁBRICA (18, 19, 20... 99)
        $painelDataSorted = $painelData->sortBy(function ($item) {
            $fab = trim($item['fabrica'] ?? '');
            $numFab = (is_numeric($fab) && intval($fab) > 0) ? intval($fab) : 999999;
            return sprintf('%08d_%s', $numFab, $item['pv']);
        })->values();

        // Opções de Filtro Únicas
        $opcoesInfo = $opcoesInfo->unique()->sort()->values();
        $opcoesStatusPv = collect(['FATURADO', 'COMPRAS', 'ENGENHARIA', 'ESTOQUE', 'ENTREGUE', 'FINANCEIRO', 'CANCELADO'])->merge($opcoesStatusPv)->unique()->values();
        $opcoesFabrica = $opcoesFabrica->unique()->sort()->values();
        $opcoesMarca = $opcoesMarca->unique()->sort()->values();
        $opcoesDataPronto = $opcoesDataPronto->unique()->sort()->values();

        // Métricas Globais dos KPIs Superiores (calculadas sobre todos os PVs)
        $kpiTotalPv = $painelDataSorted->count();
        $kpiMediaSeparacao = $painelDataSorted->avg('percent_separado') ?? 0;
        $kpiInvestimentoTotal = $painelDataSorted->sum('investimento_pendente');
        $kpiValorBrutoTotal = $painelDataSorted->sum('valor_bruto');
        $kpiPvsComFalta = $painelDataSorted->where('total_falta', '>', 0)->count();

        // Paginação de 15 Pedidos de Venda por página para manter o painel ultra-rápido e leve
        $perPage = 15;
        $page = LengthAwarePaginator::resolveCurrentPage('page') ?: 1;
        $currentPageItems = $painelDataSorted->slice(($page - 1) * $perPage, $perPage)->values();

        $painelData = new LengthAwarePaginator(
            $currentPageItems,
            $painelDataSorted->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'page',
                'query' => $request->query(),
            ]
        );

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
            'fDataPronto',
            'fDataProntoDe',
            'fDataProntoAte',
            'fDataProntoMes',
            'opcoesInfo',
            'opcoesStatusPv',
            'opcoesFabrica',
            'opcoesMarca',
            'opcoesDataPronto',
            'kpiTotalPv',
            'kpiMediaSeparacao',
            'kpiInvestimentoTotal',
            'kpiValorBrutoTotal',
            'kpiPvsComFalta',
            'filiaisProtheus',
            'canEditPcp'
        ));
    }

    /**
     * Auxiliar para converter strings de datas (31/08/26, 31/08/2026, 2026-08-31) em YYYY-MM-DD para comparacao de intervalo
     */
    private function parseDateToYmd(?string $dateStr): ?string
    {
        if (empty($dateStr) || $dateStr === '-') return null;
        $dateStr = trim($dateStr);
        
        // Formato YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            return $dateStr;
        }

        // Formato DD/MM/YYYY ou DD/MM/YY ou DD-MM-YYYY ou DD-MM-YY
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})$/', $dateStr, $m)) {
            $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $year = $m[3];
            if (strlen($year) == 2) {
                $year = '20' . $year;
            }
            return "{$year}-{$month}-{$day}";
        }

        // Formato DD/MM (assume ano atual 2026)
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})$/', $dateStr, $m)) {
            $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            return "2026-{$month}-{$day}";
        }

        return null;
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
        $qtd = $request->input('qtd');
        $valorBrutoIn = $request->input('valor_bruto');
        $timeProd = $request->input('time_prod');
        $dataEmissao = $request->input('data_emissao');
        $dataContratual = $request->input('data_contratual');
        $dataPaPg = $request->input('data_pa_pg');
        $dataPronto = $request->input('data_pronto');
        $dataBoom = $request->input('data_boom');
        $dataLiberacaoEstoque = $request->input('data_liberacao_estoque');

        $valBrutoFloat = null;
        if ($valorBrutoIn !== null && $valorBrutoIn !== '') {
            $valBrutoClean = str_replace(['R$', ' ', '.'], '', $valorBrutoIn);
            $valBrutoClean = str_replace(',', '.', $valBrutoClean);
            $valBrutoFloat = floatval($valBrutoClean);
        }

        // Atualizar Metadados do PV
        PvMetadado::updateOrCreate(
            ['pedido' => $pvNum],
            [
                'info' => $info ?: null,
                'status_pv' => $statusPv ?: null,
                'fabrica' => $fabrica ?: null,
                'marca' => $marca ?: null,
                'qtd' => is_numeric($qtd) ? intval($qtd) : 1,
                'valor_bruto' => $valBrutoFloat,
                'time_prod' => $timeProd !== null ? trim($timeProd) : null,
                'data_emissao' => $dataEmissao !== null ? trim($dataEmissao) : null,
                'data_contratual' => $dataContratual !== null ? trim($dataContratual) : null,
                'data_pa_pg' => $dataPaPg !== null ? trim($dataPaPg) : null,
                'data_pronto' => $dataPronto !== null ? trim($dataPronto) : null,
                'data_boom' => $dataBoom !== null ? trim($dataBoom) : null,
                'data_liberacao_estoque' => $dataLiberacaoEstoque !== null ? trim($dataLiberacaoEstoque) : null,
                'updated_by' => auth()->user()->name ?? 'Sistema',
            ]
        );

        // Atualizar cliente_obs e produto_pai nos componentes de estoque
        if ($cliente || $prodPai) {
            $updates = [];
            if ($cliente) $updates['cliente_obs'] = $cliente;
            if ($prodPai) $updates['produto_pai'] = $prodPai;
            $updates['updated_by'] = auth()->user()->name ?? 'Sistema';
            
            EstoqueItem::where('pedido', $pvNum)->update($updates);
        }

        return redirect()->route('pcp-painel.index')->with('success', "Pedido de Venda {$pvNum} atualizado com sucesso!");
    }

    /**
     * Atualização em Lote dos Metadados do Pedido de Venda
     */
    public function updateBatch(Request $request)
    {
        $pvsData = $request->input('pvs', []);

        if (is_array($pvsData) && !empty($pvsData)) {
            foreach ($pvsData as $pvNum => $data) {
                if (empty($pvNum)) continue;

                $updatePayload = [
                    'info' => isset($data['info']) ? trim($data['info']) : null,
                    'status_pv' => isset($data['status_pv']) ? trim($data['status_pv']) : null,
                    'fabrica' => isset($data['fabrica']) ? trim($data['fabrica']) : null,
                    'marca' => isset($data['marca']) ? trim($data['marca']) : null,
                    'updated_by' => auth()->user()->name ?? 'Sistema',
                ];

                if (isset($data['qtd'])) $updatePayload['qtd'] = is_numeric($data['qtd']) ? intval($data['qtd']) : 1;
                if (isset($data['valor_bruto']) && $data['valor_bruto'] !== '') {
                    $vClean = str_replace(['R$', ' ', '.'], '', $data['valor_bruto']);
                    $vClean = str_replace(',', '.', $vClean);
                    $updatePayload['valor_bruto'] = floatval($vClean);
                }
                if (isset($data['time_prod'])) $updatePayload['time_prod'] = trim($data['time_prod']);
                if (isset($data['data_emissao'])) $updatePayload['data_emissao'] = trim($data['data_emissao']);
                if (isset($data['data_contratual'])) $updatePayload['data_contratual'] = trim($data['data_contratual']);
                if (isset($data['data_pa_pg'])) $updatePayload['data_pa_pg'] = trim($data['data_pa_pg']);
                if (isset($data['data_pronto'])) $updatePayload['data_pronto'] = trim($data['data_pronto']);
                if (isset($data['data_boom'])) $updatePayload['data_boom'] = trim($data['data_boom']);
                if (isset($data['data_liberacao_estoque'])) $updatePayload['data_liberacao_estoque'] = trim($data['data_liberacao_estoque']);

                PvMetadado::updateOrCreate(
                    ['pedido' => trim($pvNum)],
                    $updatePayload
                );
            }
        }

        return redirect()->back()->with('success', 'Alterações do Painel PCP salvas com sucesso!');
    }
}
