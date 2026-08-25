<?php

namespace App\Http\Controllers;

use App\Models\EstoqueItem;
use App\Models\CompraItem;
use App\Models\PvMetadado;
use Illuminate\Http\Request;

class PcpPainelController extends Controller
{
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
            $produtoPai = $items->pluck('produto_pai')->filter()->first() ?? '-';

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
            'kpiPvsComFalta'
        ));
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
