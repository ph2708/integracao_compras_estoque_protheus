<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProtheusService;
use App\Models\EstoqueItem;
use App\Models\PvMetadado;

class MontagemPainelController extends Controller
{
    protected $protheusService;

    public function __construct(ProtheusService $protheusService)
    {
        $this->protheusService = $protheusService;
    }

    public function index(Request $request)
    {
        if (!auth()->user()->canAccessMontagem()) {
            abort(403, 'Acesso não autorizado ao Painel de Montagem.');
        }

        $filialSel = $request->input('filial');
        $dataDe = $request->input('data_de');
        $dataAte = $request->input('data_ate');
        $searchPv = trim($request->input('search_pv', ''));
        $searchCliente = trim($request->input('search_cliente', ''));
        $searchStatusEstacao = $request->input('search_status_estacao');

        // Busca apontamentos brutos do Protheus (SH6010 / SH1010)
        $apontamentosRaw = $this->protheusService->getApontamentosMontagem($filialSel, $dataDe, $dataAte);

        // Agrupar apontamentos por OP / Pedido
        $opGroups = [];

        foreach ($apontamentosRaw as $ap) {
            $pvNum = trim($ap['pedido'] ?? '');
            $opNum = trim($ap['op'] ?? '');
            $groupKey = $pvNum ?: ($opNum ?: 'SEM_ID');

            if (!isset($opGroups[$groupKey])) {
                $opGroups[$groupKey] = [
                    'pv' => $pvNum,
                    'op' => $opNum,
                    'cliente' => trim($ap['cliente'] ?? ''),
                    'filial' => trim($ap['filial'] ?? ''),
                    'mecanica' => ['tempo_min' => 0, 'status' => 'PENDENTE', 'ultimo_apontamento' => null, 'count' => 0],
                    'eletrica' => ['tempo_min' => 0, 'status' => 'PENDENTE', 'ultimo_apontamento' => null, 'count' => 0],
                    'teste' => ['tempo_min' => 0, 'status' => 'PENDENTE', 'ultimo_apontamento' => null, 'count' => 0],
                    'carenagem' => ['tempo_min' => 0, 'status' => 'PENDENTE', 'ultimo_apontamento' => null, 'count' => 0],
                    'outros' => ['tempo_min' => 0, 'count' => 0],
                    'total_minutos' => 0,
                    'historico' => []
                ];
            }

            // Converter H6_TEMPO (ex: 001:30 ou 000:17) para minutos
            $tempoStr = trim($ap['tempo'] ?? '000:00');
            $minutos = $this->parseTempoToMinutes($tempoStr);

            $opGroups[$groupKey]['total_minutos'] += $minutos;

            $codRecurso = trim($ap['cod_recurso'] ?? '');
            $nomeRecurso = strtoupper(trim($ap['nome_recurso'] ?? ''));
            $isTotal = (trim($ap['pt'] ?? '') === 'T');

            $dataHoraStr = $this->formatDataHora($ap['data_ini'] ?? '', $ap['hora_ini'] ?? '');

            // Mapeamento de Estações
            $estacaoKey = null;
            if ($codRecurso === '000004' || str_contains($nomeRecurso, 'MECANICA')) {
                $estacaoKey = 'mecanica';
            } elseif ($codRecurso === '000002' || str_contains($nomeRecurso, 'ELETRICA')) {
                $estacaoKey = 'eletrica';
            } elseif ($codRecurso === '000007' || str_contains($nomeRecurso, 'TESTE')) {
                $estacaoKey = 'teste';
            } elseif ($codRecurso === '000008' || str_contains($nomeRecurso, 'CARENAGEM')) {
                $estacaoKey = 'carenagem';
            }

            if ($estacaoKey) {
                $opGroups[$groupKey][$estacaoKey]['tempo_min'] += $minutos;
                $opGroups[$groupKey][$estacaoKey]['count']++;
                if ($isTotal) {
                    $opGroups[$groupKey][$estacaoKey]['status'] = 'CONCLUIDO';
                } elseif ($opGroups[$groupKey][$estacaoKey]['status'] !== 'CONCLUIDO') {
                    $opGroups[$groupKey][$estacaoKey]['status'] = 'EM_ANDAMENTO';
                }
                $opGroups[$groupKey][$estacaoKey]['ultimo_apontamento'] = $dataHoraStr;
            } else {
                $opGroups[$groupKey]['outros']['tempo_min'] += $minutos;
                $opGroups[$groupKey]['outros']['count']++;
            }

            // Registro no histórico
            $opGroups[$groupKey]['historico'][] = [
                'recurso' => $nomeRecurso ?: "Recurso {$codRecurso}",
                'operacao' => $ap['operacao'] ?? '',
                'data_ini' => $ap['data_ini'] ?? '',
                'hora_ini' => $ap['hora_ini'] ?? '',
                'data_fin' => $ap['data_fin'] ?? '',
                'hora_fin' => $ap['hora_fin'] ?? '',
                'tempo_str' => $tempoStr,
                'status_pt' => $isTotal ? 'Encerrado (Total)' : 'Parcial'
            ];
        }

        // Cruzar com PvMetadados / EstoqueItems para obter Produto Pai e Nome Amigável do Cliente
        $pvsList = collect($opGroups)->keys()->filter(fn($k) => strlen($k) == 6)->values();
        $metadados = PvMetadado::whereIn('pedido', $pvsList)->get()->keyBy('pedido');
        $produtosPaiMap = EstoqueItem::whereIn('pedido', $pvsList)
            ->whereNotNull('produto_pai')
            ->where('produto_pai', '!=', '')
            ->get()
            ->groupBy('pedido')
            ->map(fn($group) => $group->first()->produto_pai);

        $painelCollection = collect();

        foreach ($opGroups as $key => $data) {
            $pvNum = $data['pv'];
            $meta = $metadados->get($pvNum);

            $prodPai = $produtosPaiMap->get($pvNum) ?? '-';
            // Tratar GMG no produto pai
            if (str_contains($prodPai, '-')) {
                $partes = explode('-', $prodPai, 2);
                if (isset($partes[1]) && str_contains(strtoupper($partes[1]), 'GMG')) {
                    $prodPai = trim($partes[1]);
                }
            }

            $clienteObs = $data['cliente'];
            if ($meta && !empty($meta->cliente_obs)) {
                $clienteObs = $meta->cliente_obs;
            }

            // Aplicar Filtros de Busca
            if ($searchPv && !str_contains(strtoupper($pvNum . ' ' . $data['op']), strtoupper($searchPv))) {
                continue;
            }
            if ($searchCliente && !str_contains(strtoupper($clienteObs), strtoupper($searchCliente))) {
                continue;
            }

            // Formatação amigável das horas
            $data['mecanica_horas_fmt'] = $this->formatMinutesToHoursStr($data['mecanica']['tempo_min']);
            $data['eletrica_horas_fmt'] = $this->formatMinutesToHoursStr($data['eletrica']['tempo_min']);
            $data['teste_horas_fmt'] = $this->formatMinutesToHoursStr($data['teste']['tempo_min']);
            $data['carenagem_horas_fmt'] = $this->formatMinutesToHoursStr($data['carenagem']['tempo_min']);
            $data['total_horas_fmt'] = $this->formatMinutesToHoursStr($data['total_minutos']);

            $data['produto_pai'] = $prodPai;
            $data['cliente'] = $clienteObs;
            $data['info'] = $meta->info ?? '-';
            $data['fabrica'] = $meta->fabrica ?? '99';

            $painelCollection->push($data);
        }

        // KPIs executivos
        $kpiTotalOps = $painelCollection->count();
        $kpiTotalMinutos = $painelCollection->sum('total_minutos');
        $kpiTotalHorasFmt = $this->formatMinutesToHoursStr($kpiTotalMinutos);
        $kpiEmTesteCount = $painelCollection->where('teste.status', '!=', 'PENDENTE')->count();
        $kpiConcluidasCount = $painelCollection->where('teste.status', 'CONCLUIDO')->count();

        // Lista de Filiais
        $filiaisProtheus = $this->protheusService->listarFiliais();
        if (empty($filiaisProtheus)) {
            $filiaisProtheus = ['01', '02', '04', '05', '06', '10', '15', '21', '22'];
        }

        return view('pcp_montagem.index', compact(
            'painelCollection',
            'searchPv',
            'searchCliente',
            'filialSel',
            'dataDe',
            'dataAte',
            'filiaisProtheus',
            'kpiTotalOps',
            'kpiTotalHorasFmt',
            'kpiEmTesteCount',
            'kpiConcluidasCount'
        ));
    }

    private function parseTempoToMinutes(string $tempoStr): int
    {
        $tempoStr = trim($tempoStr);
        if (empty($tempoStr) || !str_contains($tempoStr, ':')) {
            return 0;
        }

        $partes = explode(':', $tempoStr);
        $horas = intval($partes[0] ?? 0);
        $minutos = intval($partes[1] ?? 0);

        return ($horas * 60) + $minutos;
    }

    private function formatMinutesToHoursStr(int $totalMinutos): string
    {
        if ($totalMinutos <= 0) {
            return '0h 00m';
        }
        $h = floor($totalMinutos / 60);
        $m = $totalMinutos % 60;
        return sprintf('%dh %02dm', $h, $m);
    }

    private function formatDataHora(?string $dataYmd, ?string $horaHm): string
    {
        if (empty($dataYmd) || strlen($dataYmd) < 8) {
            return '-';
        }
        $d = substr($dataYmd, 6, 2) . '/' . substr($dataYmd, 4, 2) . '/' . substr($dataYmd, 0, 4);
        return $horaHm ? "{$d} {$horaHm}" : $d;
    }
}
