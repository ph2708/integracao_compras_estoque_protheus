@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header da Página -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                ⏱️ Status da Montagem & Coleta de Horas (Coletores Protheus)
            </h1>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0.25rem 0 0 0;">
                Acompanhamento em tempo real dos apontamentos de fábrica (Mecânica, Elétrica, Teste de Geradores e Carenagem).
            </p>
        </div>
    </div>

    <!-- Executive KPI Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 11fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div class="kpi-card" style="border-left: 4px solid #6366f1;">
            <span class="kpi-title">📄 OPs EM ACOMPANHAMENTO</span>
            <span class="kpi-value" style="color: #818cf8;">{{ $kpiTotalOps }}</span>
            <span style="font-size: 0.725rem; color: var(--text-muted);">Ordens com bips registrados</span>
        </div>

        <div class="kpi-card" style="border-left: 4px solid #10b981;">
            <span class="kpi-title">⏱️ HORAS MÃO DE OBRA (TOTAL)</span>
            <span class="kpi-value" style="color: #34d399;">{{ $kpiTotalHorasFmt }}</span>
            <span style="font-size: 0.725rem; color: var(--text-muted);">Tempo total acumulado nas estações</span>
        </div>

        <div class="kpi-card" style="border-left: 4px solid #f59e0b;">
            <span class="kpi-title">🧪 OPs EM TESTE / BANCO CARGA</span>
            <span class="kpi-value" style="color: #fbbf24;">{{ $kpiEmTesteCount }}</span>
            <span style="font-size: 0.725rem; color: var(--text-muted);">Estação de Teste Final ativa</span>
        </div>

        <div class="kpi-card" style="border-left: 4px solid #3b82f6;">
            <span class="kpi-title">🟢 MONTAGENS CONCLUÍDAS</span>
            <span class="kpi-value" style="color: #60a5fa;">{{ $kpiConcluidasCount }}</span>
            <span style="font-size: 0.725rem; color: var(--text-muted);">Teste Final encerrado com sucesso</span>
        </div>
    </div>

    <!-- Bar de Filtros Principais -->
    <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
        <form action="{{ route('pcp-montagem.index') }}" method="GET" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: flex-end;">
            <div style="flex: 1; min-width: 180px;">
                <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem; display: block;">Buscar por PV / OP</label>
                <input type="text" name="search_pv" value="{{ $searchPv }}" class="form-control" placeholder="Ex: 006882 ou 018700..." style="padding: 0.45rem 0.6rem; font-size: 0.85rem;">
            </div>

            <div style="flex: 1; min-width: 200px;">
                <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem; display: block;">Buscar por Cliente / Obra</label>
                <input type="text" name="search_cliente" value="{{ $searchCliente }}" class="form-control" placeholder="Nome do cliente..." style="padding: 0.45rem 0.6rem; font-size: 0.85rem;">
            </div>

            <div style="width: 130px;">
                <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem; display: block;">Filial Protheus</label>
                <select name="filial" class="form-control" style="padding: 0.45rem 0.6rem; font-size: 0.85rem;">
                    <option value="">Todas</option>
                    @foreach($filiaisProtheus as $f)
                        <option value="{{ $f }}" {{ $filialSel == $f ? 'selected' : '' }}>Filial {{ $f }}</option>
                    @endforeach
                </select>
            </div>

            <div style="width: 145px;">
                <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem; display: block;">📅 Apontado De</label>
                <input type="date" name="data_de" value="{{ $dataDe }}" class="form-control" style="padding: 0.45rem 0.6rem; font-size: 0.85rem;">
            </div>

            <div style="width: 145px;">
                <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem; display: block;">📅 Apontado Até</label>
                <input type="date" name="data_ate" value="{{ $dataAte }}" class="form-control" style="padding: 0.45rem 0.6rem; font-size: 0.85rem;">
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary" style="padding: 0.45rem 0.85rem; font-size: 0.85rem;">🔍 Filtrar</button>
                <a href="{{ route('pcp-montagem.index') }}" class="btn btn-secondary" style="padding: 0.45rem 0.85rem; font-size: 0.85rem;">Limpar</a>
            </div>
        </form>
    </div>

    <!-- Tabela Principal de Montagem e Horas -->
    <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden;">
        <div style="overflow-x: auto;">
            <table class="table" style="width: 100%; margin: 0; font-size: 0.825rem; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: rgba(30, 41, 59, 0.8); color: var(--text-muted); border-bottom: 2px solid var(--border-color); text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.5px;">
                        <th style="padding: 0.75rem; text-align: center; width: 60px;">Filial</th>
                        <th style="padding: 0.75rem; width: 100px;">PV / OP</th>
                        <th style="padding: 0.75rem;">Cliente / Obra</th>
                        <th style="padding: 0.75rem; min-width: 220px;">Equipamento / Produto Pai</th>
                        <th style="padding: 0.75rem; text-align: center; min-width: 140px;">🔧 Mecânica</th>
                        <th style="padding: 0.75rem; text-align: center; min-width: 140px;">⚡ Elétrica</th>
                        <th style="padding: 0.75rem; text-align: center; min-width: 140px;">🏗️ Carenagem</th>
                        <th style="padding: 0.75rem; text-align: center; min-width: 140px;">🧪 Teste Gerador</th>
                        <th style="padding: 0.75rem; text-align: right; width: 120px;">⏱️ Total Horas</th>
                        <th style="padding: 0.75rem; text-align: center; width: 80px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($painelCollection as $item)
                    <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.15s ease;" onmouseover="this.style.background='rgba(51, 65, 85, 0.3)'" onmouseout="this.style.background='transparent'">
                        <td style="padding: 0.65rem 0.75rem; text-align: center; font-weight: 700; color: #94a3b8;">
                            {{ $item['filial'] ?: '-' }}
                        </td>
                        <td style="padding: 0.65rem 0.75rem; font-weight: 700; color: #818cf8;">
                            <div>{{ $item['pv'] ?: 'OP ' . $item['op'] }}</div>
                            @if($item['pv'] && $item['op'])
                                <div style="font-size: 0.675rem; color: var(--text-muted); font-weight: 400;">OP {{ $item['op'] }}</div>
                            @endif
                        </td>
                        <td style="padding: 0.65rem 0.75rem; color: var(--text-color); font-weight: 500;">
                            {{ $item['cliente'] ?: 'CLIENTE NÃO IDENTIFICADO' }}
                        </td>
                        <td style="padding: 0.65rem 0.75rem; color: #c084fc; font-weight: 500;">
                            {{ $item['produto_pai'] }}
                        </td>

                        <!-- Estação 1: Mecânica -->
                        <td style="padding: 0.65rem 0.75rem; text-align: center;">
                            @if($item['mecanica']['status'] === 'CONCLUIDO')
                                <span class="badge" style="background-color: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); padding: 0.2rem 0.4rem; border-radius: 4px; font-weight: 700; font-size: 0.7rem; display: block; margin-bottom: 0.15rem;">
                                    🟢 Concluído
                                </span>
                            @elseif($item['mecanica']['status'] === 'EM_ANDAMENTO')
                                <span class="badge" style="background-color: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); padding: 0.2rem 0.4rem; border-radius: 4px; font-weight: 700; font-size: 0.7rem; display: block; margin-bottom: 0.15rem;">
                                    🟡 Em Andamento
                                </span>
                            @else
                                <span style="color: #64748b; font-size: 0.725rem; display: block; margin-bottom: 0.15rem;">⚪ Pendente</span>
                            @endif
                            <div style="font-size: 0.725rem; font-weight: 600; color: #f8fafc;">
                                ⏱️ {{ $item['mecanica_horas_fmt'] }}
                            </div>
                        </td>

                        <!-- Estação 2: Elétrica -->
                        <td style="padding: 0.65rem 0.75rem; text-align: center;">
                            @if($item['eletrica']['status'] === 'CONCLUIDO')
                                <span class="badge" style="background-color: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); padding: 0.2rem 0.4rem; border-radius: 4px; font-weight: 700; font-size: 0.7rem; display: block; margin-bottom: 0.15rem;">
                                    🟢 Concluído
                                </span>
                            @elseif($item['eletrica']['status'] === 'EM_ANDAMENTO')
                                <span class="badge" style="background-color: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); padding: 0.2rem 0.4rem; border-radius: 4px; font-weight: 700; font-size: 0.7rem; display: block; margin-bottom: 0.15rem;">
                                    🟡 Em Andamento
                                </span>
                            @else
                                <span style="color: #64748b; font-size: 0.725rem; display: block; margin-bottom: 0.15rem;">⚪ Pendente</span>
                            @endif
                            <div style="font-size: 0.725rem; font-weight: 600; color: #f8fafc;">
                                ⏱️ {{ $item['eletrica_horas_fmt'] }}
                            </div>
                        </td>

                        <!-- Estação 3: Carenagem -->
                        <td style="padding: 0.65rem 0.75rem; text-align: center;">
                            @if($item['carenagem']['status'] === 'CONCLUIDO')
                                <span class="badge" style="background-color: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); padding: 0.2rem 0.4rem; border-radius: 4px; font-weight: 700; font-size: 0.7rem; display: block; margin-bottom: 0.15rem;">
                                    🟢 Concluído
                                </span>
                            @elseif($item['carenagem']['status'] === 'EM_ANDAMENTO')
                                <span class="badge" style="background-color: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); padding: 0.2rem 0.4rem; border-radius: 4px; font-weight: 700; font-size: 0.7rem; display: block; margin-bottom: 0.15rem;">
                                    🟡 Em Andamento
                                </span>
                            @else
                                <span style="color: #64748b; font-size: 0.725rem; display: block; margin-bottom: 0.15rem;">⚪ Pendente</span>
                            @endif
                            <div style="font-size: 0.725rem; font-weight: 600; color: #f8fafc;">
                                ⏱️ {{ $item['carenagem_horas_fmt'] }}
                            </div>
                        </td>

                        <!-- Estação 4: Teste Final Gerador (Última Etapa da Fábrica) -->
                        <td style="padding: 0.65rem 0.75rem; text-align: center;">
                            @if($item['teste']['status'] === 'CONCLUIDO')
                                <span class="badge" style="background-color: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); padding: 0.2rem 0.4rem; border-radius: 4px; font-weight: 700; font-size: 0.7rem; display: block; margin-bottom: 0.15rem;">
                                    🟢 Concluído
                                </span>
                            @elseif($item['teste']['status'] === 'EM_ANDAMENTO')
                                <span class="badge" style="background-color: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); padding: 0.2rem 0.4rem; border-radius: 4px; font-weight: 700; font-size: 0.7rem; display: block; margin-bottom: 0.15rem;">
                                    🟡 Em Teste
                                </span>
                            @else
                                <span style="color: #64748b; font-size: 0.725rem; display: block; margin-bottom: 0.15rem;">⚪ Pendente</span>
                            @endif
                            <div style="font-size: 0.725rem; font-weight: 600; color: #f8fafc;">
                                ⏱️ {{ $item['teste_horas_fmt'] }}
                            </div>
                        </td>

                        <!-- Total Acumulado de Horas -->
                        <td style="padding: 0.65rem 0.75rem; text-align: right; font-weight: 800; font-size: 0.9rem; color: #38bdf8;">
                            {{ $item['total_horas_fmt'] }}
                        </td>

                        <!-- Ações -->
                        <td style="padding: 0.65rem 0.75rem; text-align: center;">
                            <button type="button" class="btn btn-secondary" onclick="toggleDetails('hist_{{ $item['op'] }}')" style="padding: 0.2rem 0.45rem; font-size: 0.7rem;" title="Ver histórico de bips">
                                👁️ Histórico
                            </button>
                        </td>
                    </tr>

                    <!-- Linha Expansível de Histórico de Bipamentos -->
                    <tr id="hist_{{ $item['op'] }}" style="display: none; background-color: rgba(15, 23, 42, 0.6);">
                        <td colspan="10" style="padding: 0.75rem 1.25rem;">
                            <div style="font-size: 0.775rem; font-weight: 700; color: #a5b4fc; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                📋 Histórico Detalhado de Bipamentos / Coletor Protheus (OP {{ $item['op'] }})
                            </div>
                            @if(empty($item['historico']))
                                <span style="font-size: 0.75rem; color: var(--text-muted);">Nenhum bipamento detalhado encontrado.</span>
                            @else
                                <table style="width: 100%; font-size: 0.75rem; border-collapse: collapse; background: rgba(30, 41, 59, 0.5); border-radius: 6px; overflow: hidden;">
                                    <thead>
                                        <tr style="background: rgba(51, 65, 85, 0.4); color: #94a3b8; text-align: left;">
                                            <th style="padding: 0.4rem 0.6rem;">Estação / Recurso</th>
                                            <th style="padding: 0.4rem 0.6rem;">Operação</th>
                                            <th style="padding: 0.4rem 0.6rem;">Início</th>
                                            <th style="padding: 0.4rem 0.6rem;">Término</th>
                                            <th style="padding: 0.4rem 0.6rem;">Horas Apontadas</th>
                                            <th style="padding: 0.4rem 0.6rem;">Encerramento (PT)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($item['historico'] as $h)
                                        <tr style="border-bottom: 1px solid rgba(51, 65, 85, 0.3);">
                                            <td style="padding: 0.4rem 0.6rem; font-weight: 600; color: #f8fafc;">{{ $h['recurso'] }}</td>
                                            <td style="padding: 0.4rem 0.6rem; color: #94a3b8;">Op {{ $h['operacao'] }}</td>
                                            <td style="padding: 0.4rem 0.6rem; color: #94a3b8;">{{ $h['data_ini'] }} {{ $h['hora_ini'] }}</td>
                                            <td style="padding: 0.4rem 0.6rem; color: #94a3b8;">{{ $h['data_fin'] }} {{ $h['hora_fin'] }}</td>
                                            <td style="padding: 0.4rem 0.6rem; font-weight: 700; color: #38bdf8;">{{ $h['tempo_str'] }}</td>
                                            <td style="padding: 0.4rem 0.6rem;">
                                                @if(str_contains($h['status_pt'], 'Total'))
                                                    <span style="color: #34d399; font-weight: 600;">✓ {{ $h['status_pt'] }}</span>
                                                @else
                                                    <span style="color: #fbbf24;">⏳ {{ $h['status_pt'] }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" style="padding: 2rem; text-align: center; color: var(--text-muted);">
                            Nenhum apontamento de montagem ou horas encontrado com os filtros atuais.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function toggleDetails(elementId) {
        const el = document.getElementById(elementId);
        if (el) {
            el.style.display = el.style.display === 'none' ? 'table-row' : 'none';
        }
    }
</script>
@endsection
