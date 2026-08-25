<?php

namespace App\Http\Controllers;

use App\Models\EstoqueItem;
use App\Models\CompraItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PcpPainelController extends Controller
{
    /**
     * Exibe o Painel Gerencial PCP GMGs agrupado por Pedido de Venda (PV)
     */
    public function index(Request $request)
    {
        $searchPv = $request->get('search_pv');
        $searchCliente = $request->get('search_cliente');
        $searchStatusPcp = $request->get('search_status_pcp');
        $searchStatusPagamento = $request->get('search_status_pagamento');

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

        foreach ($grouped as $pvNum => $items) {
            if ($pvNum === 'SEM_PEDIDO' && $items->isEmpty()) continue;

            $clienteObs = $items->pluck('cliente_obs')->filter()->first() ?? '-';
            $produtoPai = $items->pluck('produto_pai')->filter()->first() ?? '-';

            $totalComponentes = $items->count();
            $totalFalta = $items->where('status', 'FALTA')->count();
            $totalSeparado = $items->where('status', 'SEPARADO')->count();
            $totalRetirado = $items->where('status', 'RETIRADO')->count();
            $totalFabrica = $items->whereIn('status', ['FABRICA', 'FABRICAR INTERNO KANBAN'])->count();
            $totalFechado = $items->where('status', 'FECHADO')->count();

            // Porcentagens de Separação / Atendimento
            $percentSeparado = $totalComponentes > 0 ? round(($totalSeparado / $totalComponentes) * 100, 1) : 0;

            // Status PCP Geral do Pedido de Venda
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

            // Aplicar filtro de Status PCP caso fornecido
            if ($searchStatusPcp && $statusPcpGeral !== $searchStatusPcp) {
                continue;
            }

            // Alertas de Compras e Financeiros
            $semPedidoCompraCount = 0;
            $semPrecoCount = 0;
            $investimentoPendente = 0;
            $valorPa = 0;
            $valorFaturado = 0;
            $valorPago = 0;

            // Status de Componentes Críticos
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
                $stPag = $cItem ? strtoupper(trim($cItem->status_pagamento ?? '')) : 'PENDENTE';

                if ($it->status === 'FALTA') {
                    if (!$cItem || empty(trim($cItem->pedido_compra ?? ''))) {
                        $semPedidoCompraCount++;
                    }
                    if ($valUnit <= 0) {
                        $semPrecoCount++;
                    }

                    $investimentoPendente += $valTotal;

                    if (in_array($stPag, ['PA', 'PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO'])) {
                        $valorPa += $valTotal;
                    } elseif ($stPag === 'FATURADO') {
                        $valorFaturado += $valTotal;
                    } elseif ($stPag === 'PAGO') {
                        $valorPago += $valTotal;
                    }
                }

                // Verificação de Componentes Especiais
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
                'total_componentes' => $totalComponentes,
                'total_falta' => $totalFalta,
                'total_separado' => $totalSeparado,
                'total_fabrica' => $totalFabrica,
                'percent_separado' => $percentSeparado,
                'status_pcp_geral' => $statusPcpGeral,
                'status_badge_class' => $statusBadgeClass,
                'sem_pedido_compra_count' => $semPedidoCompraCount,
                'sem_preco_count' => $semPrecoCount,
                'investimento_pendente' => $investimentoPendente,
                'valor_pa' => $valorPa,
                'valor_faturado' => $valorFaturado,
                'valor_pago' => $valorPago,
                'motor_status' => $motorStatus,
                'alternador_status' => $alternadorStatus,
                'base_status' => $baseStatus,
                'carenagem_status' => $carenagemStatus,
                'items' => $items,
            ]);
        }

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
            'kpiTotalPv',
            'kpiMediaSeparacao',
            'kpiInvestimentoTotal',
            'kpiPvsComFalta'
        ));
    }
}
