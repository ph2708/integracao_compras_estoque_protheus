@extends('layouts.app')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
            ⚙️ Gestão de Base de Dados (Importador, Exportador & Limpador)
        </h1>
        <p style="color: var(--text-muted); font-size: 0.8rem;">Ferramentas administrativas para manutenção, carga em lote, exportação e zeramento da base local MySQL.</p>
    </div>
    
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <!-- Botão Baixar Modelo -->
        <a href="{{ route('importar.modelo') }}" class="btn btn-primary" style="background-color: #059669; font-weight: 600;">
            📥 Baixar Planilha Modelo (.CSV)
        </a>

        <!-- Botão Exportar Base Completa -->
        <a href="{{ route('importar.export') }}" class="btn btn-primary" style="background-color: #0284c7; font-weight: 600;">
            📤 Exportar Base Atual (.CSV)
        </a>

        <!-- Botão Limpar Base -->
        <form action="{{ route('importar.clear') }}" method="POST" onsubmit="return confirm('⚠️ ATENÇÃO: Tem certeza que deseja apagar TODOS os registros do Estoque PCP e Compras? Esta ação é irreversível!')" style="margin: 0;">
            @csrf
            <button type="submit" class="btn btn-primary" style="background-color: #dc2626; font-weight: 600;">
                🗑️ Limpar Toda a Base
            </button>
        </form>
    </div>
</div>

<!-- KPIs de Status da Base Atual -->
<div class="kpi-grid" style="margin-bottom: 1.25rem;">
    <div class="kpi-card" style="border-left-color: #6366f1;">
        <div class="kpi-title">Itens Registrados no Estoque PCP</div>
        <div class="kpi-value" style="color: #a5b4fc;">{{ number_format($totalEstoque, 0, ',', '.') }}</div>
        <div class="kpi-subtitle">Registros cadastrados na tabela <code>estoque_items</code></div>
    </div>

    <div class="kpi-card" style="border-left-color: #10b981;">
        <div class="kpi-title">Itens Registrados em Compras</div>
        <div class="kpi-value" style="color: #6ee7b7;">{{ number_format($totalCompras, 0, ',', '.') }}</div>
        <div class="kpi-subtitle">Registros vinculados na tabela <code>compras_items</code></div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
    <!-- Form Card: Upload de Planilha -->
    <div class="card" style="border-color: rgba(99, 102, 241, 0.5);">
        <h3 style="font-size: 1.1rem; color: #a5b4fc; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            📥 Carga / Importação de Planilha
        </h3>

        <form action="{{ route('importar.process') }}" method="POST" enctype="multipart/form-data" onsubmit="window.mostrarLoading('📥 Importando planilha e alimentando o banco de dados... Aguarde...')">
            @csrf
            
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="form-label" style="font-weight: 600;">Selecione o Arquivo (.xlsx, .xls ou .csv) *</label>
                <input type="file" name="arquivo" class="form-control" accept=".xlsx,.xls,.csv" required style="padding: 0.5rem;">
                <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">
                    Tamanho máximo: 20MB. Formatos aceitos: Excel (.xlsx, .xls) ou Texto Separado por Ponto e Vírgula (.csv).
                </p>
            </div>

            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="form-label" style="font-weight: 600;">Modo de Importação *</label>
                <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.35rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.85rem; color: #fcd34d;">
                        <input type="radio" name="modo" value="truncate" checked>
                        <strong>🔄 Zerar base atual e importar do zero</strong> (Substitui todos os dados antigos)
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.85rem; color: #6ee7b7;">
                        <input type="radio" name="modo" value="append">
                        <strong>➕ Manter dados atuais e mesclar novos itens</strong> (Adiciona os novos registros)
                    </label>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 0.6rem; font-size: 0.95rem; font-weight: 600;">
                    🚀 Iniciar Importação para o MySQL
                </button>
            </div>
        </form>
    </div>

    <!-- Instructions Card: Passos para Importação -->
    <div class="card" style="border-color: rgba(56, 189, 248, 0.4);">
        <h3 style="font-size: 1.1rem; color: #38bdf8; margin-bottom: 1rem;">ℹ️ Instruções e Recursos</h3>
        
        <ol style="padding-left: 1.25rem; font-size: 0.85rem; line-height: 1.6; color: #cbd5e1;">
            <li style="margin-bottom: 0.5rem;">
                <strong>📤 Exportar Base Atual</strong>: Permite baixar um arquivo CSV com 100% dos dados cadastrados atualmente, podendo ser editado e reimportado a qualquer momento.
            </li>
            <li style="margin-bottom: 0.5rem;">
                <strong>🗑️ Limpar Toda a Base</strong>: Apaga todos os registros das tabelas locais para realizar testes ou reiniciar os lançamentos.
            </li>
            <li style="margin-bottom: 0.5rem;">
                <strong>📥 Baixar Modelo</strong>: Fornece um arquivo exemplo zerado com todos os cabeçalhos das 16 colunas aceitas pelo sistema.
            </li>
            <li style="margin-bottom: 0.5rem;">
                O campo <code>STATUS PCP</code> aceita: <span style="color: #fca5a5;">FALTA</span>, <span style="color: #fcd34d;">SEPARADO</span>, <span style="color: #6ee7b7;">RETIRADO</span>, <span style="color: #93c5fd;">FABRICA</span> ou <span style="color: #c084fc;">KANBAN</span>.
            </li>
            <li style="margin-bottom: 0.5rem;">
                Os valores numéricos (Valores em R$, Quantidades e IPI) usam ponto <code>.</code> como separador decimal.
            </li>
        </ol>
    </div>
</div>

<!-- Guia Detalhado de Colunas Requeridas -->
<div class="card">
    <h3 style="font-size: 1rem; color: #a5b4fc; margin-bottom: 0.85rem;">📋 Guia de Estrutura de Colunas da Planilha (16 Colunas Aceitas)</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Nome exato da Coluna no Excel</th>
                    <th>Obrigatório</th>
                    <th>Tipo de Dado</th>
                    <th>Descrição & Exemplo</th>
                </tr>
            </thead>
            <tbody style="font-size: 0.8rem;">
                <tr>
                    <td><code>ORDEM PRODUCAO</code></td>
                    <td>Não</td>
                    <td>Texto</td>
                    <td>Número da Ordem de Produção (Ex: <code>01872201001</code>)</td>
                </tr>
                <tr>
                    <td><code>PV</code></td>
                    <td>Não</td>
                    <td>Texto</td>
                    <td>Número do Pedido de Venda Protheus (Ex: <code>006614</code>)</td>
                </tr>
                <tr>
                    <td><code>CODIGO PRODUTO</code></td>
                    <td><strong style="color: #ef4444;">SIM *</strong></td>
                    <td>Texto</td>
                    <td>Código único do componente (Ex: <code>61645000102</code>)</td>
                </tr>
                <tr>
                    <td><code>DESCRIÇÃO COMPONENTE</code></td>
                    <td>Não</td>
                    <td>Texto</td>
                    <td>Descrição detalhada da matéria-prima/componente</td>
                </tr>
                <tr>
                    <td><code>STATUS PCP</code></td>
                    <td><strong style="color: #ef4444;">SIM *</strong></td>
                    <td>Texto</td>
                    <td><code>FALTA</code>, <code>SEPARADO</code>, <code>RETIRADO</code>, <code>FABRICA</code>, <code>KANBAN</code></td>
                </tr>
                <tr>
                    <td><code>QTD EM ESTOQUE</code></td>
                    <td>Não</td>
                    <td>Número</td>
                    <td>Quantidade disponível fisicamente no almoxarifado (Default: <code>0</code>)</td>
                </tr>
                <tr>
                    <td><code>OBSERVAÇÃO ESTOQUE</code></td>
                    <td>Não</td>
                    <td>Texto</td>
                    <td>Observações livres do almoxarifado/estoque PCP</td>
                </tr>
                <tr>
                    <td><code>SOLICITAÇÃO DE COMPRA</code></td>
                    <td>Não</td>
                    <td>Texto</td>
                    <td>Número da solicitação interna de compras (Ex: <code>SC-5012</code>)</td>
                </tr>
                <tr>
                    <td><code>COMPRADO</code></td>
                    <td>Não</td>
                    <td>Número</td>
                    <td>Quantidade a ser comprada / já comprada</td>
                </tr>
                <tr>
                    <td><code>VALOR UNITARIO COMPRA</code></td>
                    <td>Não</td>
                    <td>Número</td>
                    <td>Valor unitário cotado em R$ (Ex: <code>10.50</code>)</td>
                </tr>
                <tr>
                    <td><code>TIPO PAGAMENTO/FATURADO</code></td>
                    <td>Não</td>
                    <td>Texto</td>
                    <td><code>PENDENTE</code>, <code>PAGAMENTO ANTECIPADO</code>, <code>FATURADO</code>, <code>PAGO</code></td>
                </tr>
                <tr>
                    <td><code>FORNECEDOR SELECIONADO</code></td>
                    <td>Não</td>
                    <td>Texto</td>
                    <td>Código e Nome do fornecedor (Ex: <code>003825 - CODEMA</code>)</td>
                </tr>
                <tr>
                    <td><code>PEDIDO COMPRA (PROTHEUS)</code></td>
                    <td>Não</td>
                    <td>Texto</td>
                    <td>Número do Pedido de Compra Protheus (Pode ficar em branco)</td>
                </tr>
                <tr>
                    <td><code>IPI COMPRA</code></td>
                    <td>Não</td>
                    <td>Número</td>
                    <td>Porcentagem do IPI (Ex: <code>5</code>)</td>
                </tr>
                <tr>
                    <td><code>DATA EMISSÃO PC</code></td>
                    <td>Não</td>
                    <td>Data</td>
                    <td>Data da ordem do PC (Ex: <code>2026-08-20</code>)</td>
                </tr>
                <tr>
                    <td><code>DATA PREVISÃO PGTO</code></td>
                    <td>Não</td>
                    <td>Data</td>
                    <td>Data de previsão de pagamento (Ex: <code>2026-08-30</code>)</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
