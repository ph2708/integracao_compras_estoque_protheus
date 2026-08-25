@extends('layouts.app')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700;">📦 Painel de Estoque (PCP)</h1>
        <p style="color: var(--text-muted); font-size: 0.8rem;">Gerenciamento de demanda do estoque local integrado ao Protheus.</p>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <button class="btn btn-primary" onclick="abrirModalConsultaProtheus()">
            🔍 Consultar Pedido no Protheus
        </button>
        <button class="btn btn-secondary" onclick="document.getElementById('modalAddManual').style.display='block'">
            + Adicionar Manual
        </button>
    </div>
</div>

<!-- Modal 1: Consulta Multi-Itens no Protheus -->
<div class="card" id="modalConsultaProtheus" style="display: none; border-color: rgba(99, 102, 241, 0.5); max-width: 1200px; margin: 0 auto 1.25rem auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
        <h3 style="font-size: 1rem; color: #a5b4fc;">🔍 Consultar Itens do Pedido no Protheus (SC2010)</h3>
        <button type="button" class="btn btn-secondary" style="padding: 0.2rem 0.5rem;" onclick="document.getElementById('modalConsultaProtheus').style.display='none'">✕</button>
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 0.85rem; margin-bottom: 1.25rem; align-items: flex-end;">
        <div class="form-group" style="margin-bottom: 0; min-width: 180px; flex: 1.2; position: relative;">
            <label class="form-label">Filiais (C2_FILIAL)</label>
            <div class="dropdown" style="position: relative;">
                <button type="button" 
                        class="btn btn-secondary dropdown-toggle" 
                        id="btnDropdownFiliais"
                        onclick="toggleMenuFiliaisProtheus()" 
                        style="width: 100%; text-align: left; justify-content: space-between; font-size: 0.8rem; padding: 0.45rem 0.65rem; display: flex; align-items: center;">
                    <span id="labelFiliaisSelecionadas">🏢 Todas as Filiais</span>
                    <span style="font-size: 0.65rem;">▼</span>
                </button>
                <div id="dropdownMenuFiliaisProtheus" 
                     class="dropdown-menu" 
                     style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: #1e293b; border: 1px solid #475569; border-radius: 0.5rem; padding: 0.75rem; z-index: 100; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5); max-height: 250px; overflow-y: auto; margin-top: 4px;">
                    <div style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; margin-bottom: 0.5rem; border-bottom: 1px solid #334155; padding-bottom: 0.35rem; display: flex; justify-content: space-between; align-items: center;">
                        <span>SELECIONAR FILIAIS</span>
                        <label style="font-weight: 400; cursor: pointer; color: #6366f1;">
                            <input type="checkbox" id="chkFilialSelectAll" onchange="toggleSelectAllFiliais(this)" checked style="margin-right: 3px;"> Todas
                        </label>
                    </div>
                    @foreach($filiaisProtheus as $fil)
                        <label style="display: flex; align-items: center; font-size: 0.8rem; margin-bottom: 0.35rem; cursor: pointer; color: #e2e8f0;">
                            <input type="checkbox" class="chk-filial-option" value="{{ $fil }}" checked onchange="atualizarLabelFiliais()" style="margin-right: 8px;">
                            Filial {{ $fil }}
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 0; min-width: 250px; flex: 3;">
            <label class="form-label">Número do Pedido (C2_PEDIDO) *</label>
            <input type="text" id="consulta_pedido" class="form-control" placeholder="Ex: 006614">
        </div>

        <div style="min-width: 120px; flex: 1;">
            <button type="button" class="btn btn-primary" style="width: 100%; justify-content: center;" onclick="buscarItensProtheus()">
                🔍 Listar Itens
            </button>
        </div>
    </div>

    <div id="resultado_status_label" style="margin-bottom: 0.85rem; font-size: 0.8rem; font-weight: 500;"></div>

    <form action="{{ route('estoque.store-batch') }}" method="POST" id="formImportBatch" style="display: none;" onsubmit="window.mostrarLoading('📥 Importando selecionados para o Estoque... Aguarde...')">
        @csrf
        <input type="hidden" name="items_json" id="input_items_json">

        <div class="table-responsive" style="max-height: 400px; overflow-y: auto; margin-bottom: 1rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
            <table>
                <thead style="position: sticky; top: 0; z-index: 10;">
                    <tr>
                        <th style="width: 35px; text-align: center;">
                            <input type="checkbox" id="checkAll" onchange="toggleAllCheckboxes(this)">
                        </th>
                        <th>Filial</th>
                        <th>Pedido</th>
                        <th>OP</th>
                        <th>Código Produto</th>
                        <th>Descrição</th>
                        <th class="col-desc-longa" style="display: none; color: #38bdf8;">Descrição Longa (SB5010)</th>
                        <th class="col-produto-pai" style="display: none; color: #c084fc;">Produto Pai Concatenado</th>
                        <th>Qtd OP</th>
                        <th>Nome do Cliente (C2_OBS)</th>
                    </tr>
                </thead>
                <tbody id="tbody_protheus_items"></tbody>
            </table>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
            <span id="label_selecionados_count" style="font-size: 0.8rem; color: var(--text-muted);">0 item(ns) selecionado(s)</span>
            <div style="display: flex; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalConsultaProtheus').style.display='none'">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnImportSelected">
                    📥 Importar Selecionados para o Estoque (MySQL)
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Modal 2: Inserção Manual de Item -->
<div class="card" id="modalAddManual" style="display: none; border-color: rgba(99, 102, 241, 0.4);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem;">
        <h3 style="font-size: 1rem;">Adicionar Item Manualmente</h3>
        <button type="button" class="btn btn-secondary" style="padding: 0.2rem 0.5rem;" onclick="document.getElementById('modalAddManual').style.display='none'">✕</button>
    </div>
    <form action="{{ route('estoque.store') }}" method="POST" onsubmit="window.mostrarLoading('📦 Adicionando item ao estoque... Aguarde...')">
        @csrf
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.85rem;">
            <div class="form-group">
                <label class="form-label">Código do Produto *</label>
                <input type="text" name="codigo_produto" class="form-control" placeholder="Ex: PROD-1001" required>
            </div>
            <div class="form-group">
                <label class="form-label">Descrição Curta (B1_DESC)</label>
                <input type="text" name="descricao" class="form-control" placeholder="Descrição do componente">
            </div>
            <div class="form-group">
                <label class="form-label">Descrição Longa (B5_CEME)</label>
                <input type="text" name="descricao_longa" class="form-control" placeholder="Descrição longa detalhada da SB5010">
            </div>
            <div class="form-group">
                <label class="form-label">Produto Pai Concatenado</label>
                <input type="text" name="produto_pai" class="form-control" placeholder="Ex: 951010010 - QUADRO QTA">
            </div>
            <div class="form-group">
                <label class="form-label">Ordem de Produção (OP)</label>
                <input type="text" name="op" class="form-control" placeholder="Ex: OP-01234">
            </div>
            <div class="form-group">
                <label class="form-label">Pedido de Venda (C2_PEDIDO)</label>
                <input type="text" name="pedido" class="form-control" placeholder="Ex: 006614">
            </div>
            <div class="form-group">
                <label class="form-label">Nome do Cliente (C2_OBS)</label>
                <input type="text" name="cliente_obs" class="form-control" placeholder="Ex: CLIENTE EXEMPLO">
            </div>
            <div class="form-group">
                <label class="form-label">Qtd Requisitada da OP *</label>
                <input type="number" step="0.01" name="quantidade" class="form-control" value="1" required>
            </div>
            <div class="form-group">
                <label class="form-label">Qtd Já Disponível em Estoque</label>
                <input type="number" step="0.01" name="quantidade_estoque" class="form-control" value="0">
            </div>
            <div class="form-group">
                <label class="form-label">Status PCP *</label>
                <select name="status" class="form-select" required>
                    <option value="FALTA">FALTA</option>
                    <option value="SEPARADO">SEPARADO</option>
                    <option value="RETIRADO">RETIRADO</option>
                    <option value="FABRICA">FABRICA</option>
                    <option value="FABRICAR INTERNO KANBAN">FABRICAR INTERNO KANBAN</option>
                </select>
            </div>
        </div>
        <div class="form-group" style="margin-top: 0.5rem;">
            <label class="form-label">Observação Interna do Estoque</label>
            <textarea name="observacao_estoque" class="form-control" rows="2" placeholder="Observações do almoxarifado..."></textarea>
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 0.85rem;">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalAddManual').style.display='none'">Cancelar</button>
            <button type="submit" class="btn btn-primary">Salvar no MySQL</button>
        </div>
    </form>
</div>

<!-- Modal 3: Modal de Confirmação de Alteração -->
<div class="card" id="modalConfirmacaoSave" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10000; min-width: 360px; max-width: 450px; border-color: rgba(99, 102, 241, 0.8); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.7);">
    <h3 style="font-size: 1.1rem; color: #a5b4fc; margin-bottom: 0.75rem;">⚠️ Confirmar Alterações do Item</h3>
    <div style="font-size: 0.85rem; color: #cbd5e1; margin-bottom: 0.75rem;" id="confirmTextDetails">
        Confirma a atualização deste item?
    </div>
    <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
        <button type="button" class="btn btn-secondary" onclick="fecharModalConfirmacao()">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnConfirmarSaveAction">✅ Sim, Salvar</button>
    </div>
</div>
<div id="modalOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.6); z-index: 9999;" onclick="fecharModalConfirmacao()"></div>

<!-- Formulário Oculto de Filtros por Colunas -->
<form action="{{ route('estoque.index') }}" method="GET" id="formFilterEstoque" onsubmit="window.mostrarLoading('🔍 Filtrando itens de estoque...')"></form>

<!-- Tabela de Itens de Estoque Salvos no MySQL -->
<div class="card">
    <form action="{{ route('estoque.update-batch') }}" method="POST" id="formBatchEstoque" onsubmit="window.mostrarLoading('💾 Salvando todas as alterações do Estoque... Aguarde...')">
        @csrf
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; flex-wrap: wrap; gap: 0.5rem;">
            <h3 style="font-size: 1rem;">📋 Itens Cadastrados no Estoque Local (MySQL)</h3>
            <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                <!-- Seletor de Colunas Visíveis Estilo Excel -->
                <div class="dropdown" style="position: relative; display: inline-block;">
                    <button type="button" class="btn btn-secondary" onclick="toggleMenuColunasEstoque()" style="padding: 0.35rem 0.65rem; font-size: 0.75rem; border-color: rgba(99, 102, 241, 0.5); font-weight: 500;">
                        ⚙️ Colunas Visíveis (Excel) ▾
                    </button>
                    <div id="dropdownMenuColunasEstoque" style="display: none; position: absolute; right: 0; top: 110%; z-index: 1000; background-color: #0f172a; border: 1px solid #334155; border-radius: 0.5rem; padding: 0.85rem; width: 310px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.7); font-size: 0.78rem;">
                        <div style="font-weight: 700; margin-bottom: 0.6rem; color: #a5b4fc; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; padding-bottom: 0.4rem;">
                            <span>👁️ Exibir/Ocultar Colunas</span>
                            <span style="font-size: 0.7rem; color: #94a3b8; font-weight: normal;">(Salvo Auto)</span>
                        </div>
                        <div style="max-height: 290px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.45rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: #38bdf8;">
                                <input type="checkbox" id="chk_col-fabrica" onchange="toggleColunaEstoque('col-fabrica', this.checked)"> FÁBRICA ✏️ ⬆️
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" id="chk_col-status-pcp-atual" onchange="toggleColunaEstoque('col-status-pcp-atual', this.checked)"> Status PCP Atual
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" id="chk_col-pv" onchange="toggleColunaEstoque('col-pv', this.checked)"> Pedido (C2_PEDIDO / PV)
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" id="chk_col-codigo-produto" onchange="toggleColunaEstoque('col-codigo-produto', this.checked)"> Código Produto
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" id="chk_col-descricao" onchange="toggleColunaEstoque('col-descricao', this.checked)"> Descrição Curta
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: #38bdf8;">
                                <input type="checkbox" id="chk_col-desc-longa" onchange="toggleColunaEstoque('col-desc-longa', this.checked)"> Descrição Longa (SB5010)
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: #c084fc;">
                                <input type="checkbox" id="chk_col-produto-pai" onchange="toggleColunaEstoque('col-produto-pai', this.checked)"> Produto Pai Concatenado
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" id="chk_col-op" onchange="toggleColunaEstoque('col-op', this.checked)"> Ordem de Produção (OP)
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" id="chk_col-qtd-op" onchange="toggleColunaEstoque('col-qtd-op', this.checked)"> Qtd OP
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" id="chk_col-qtd-comprar" onchange="toggleColunaEstoque('col-qtd-comprar', this.checked)"> Qtd a Comprar
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" id="chk_col-cliente" onchange="toggleColunaEstoque('col-cliente', this.checked)"> Nome do Cliente (C2_OBS)
                            </label>
                            <hr style="border-color: #334155; margin: 0.3rem 0;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; opacity: 0.6; cursor: not-allowed; color: #fcd34d;">
                                <input type="checkbox" checked disabled> 🔒 Qtd Estoque ✏️ (Sempre Visível)
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; opacity: 0.6; cursor: not-allowed; color: #38bdf8;">
                                <input type="checkbox" checked disabled> 🔒 Observação Estoque ✏️ (Sempre Visível)
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; opacity: 0.6; cursor: not-allowed;">
                                <input type="checkbox" checked disabled> 🔒 Alterar Status PCP ✏️ (Sempre Visível)
                            </label>
                        </div>
                    </div>
                </div>

                @if(request()->hasAny(['f_pedido', 'f_produto', 'f_descricao', 'f_desc_longa', 'f_prod_pai', 'f_op', 'f_status', 'f_cliente']))
                    <a href="{{ route('estoque.index') }}" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" onclick="window.mostrarLoading('⏳ Limpando filtros...')">
                        ✕ Limpar Filtros
                    </a>
                @endif
                <button type="submit" class="btn btn-primary" style="padding: 0.4rem 0.85rem; font-size: 0.8rem; background-color: #059669;" onclick="return confirm('Deseja salvar todas as alterações editadas nesta página?')">
                    💾 Salvar Todas as Alterações da Página
                </button>
            </div>
        </div>

        <div class="table-responsive" style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th class="col-fabrica" style="min-width: 80px; color: #38bdf8; text-align: center;" title="Sequência de montagem da fábrica definida no Painel PCP">FÁBRICA ⬆️</th>
                        <th class="col-status-pcp-atual" style="min-width: 130px;">Status PCP Atual</th>
                        <th class="col-pv">Pedido (C2_PEDIDO)</th>
                        <th class="col-codigo-produto">Código Produto</th>
                        <th class="col-descricao">Descrição</th>
                        <th class="col-desc-longa" style="display: none; color: #38bdf8;">Descrição Longa (B5_CEME - SB5010)</th>
                        <th class="col-produto-pai" style="display: none; color: #c084fc;">Código / Produto Pai Concatenado</th>
                        <th class="col-op">OP</th>
                        <th class="col-qtd-op" style="text-align: center;">Qtd OP</th>
                        <th class="col-qtd-estoque" style="color: #fcd34d; text-align: center;">Qtd Estoque ✏️</th>
                        <th class="col-qtd-comprar" style="color: #6ee7b7; text-align: center;">Qtd a Comprar</th>
                        <th class="col-cliente">Nome do Cliente (C2_OBS)</th>
                        <th class="col-obs-estoque" style="color: #38bdf8;">Observação Estoque ✏️</th>
                        <th class="col-status-pcp-edit">Alterar Status PCP ✏️</th>
                        <th class="col-acoes" style="text-align: center; color: #a5b4fc;">Ações</th>
                    </tr>
                    <tr class="filter-row">
                        <th class="col-fabrica">
                            <input type="text" name="f_fabrica" form="formFilterEstoque" class="form-control" placeholder="Fábrica..." value="{{ request('f_fabrica') }}" style="font-size: 0.725rem; padding: 0.25rem 0.4rem; height: 31px; text-align: center;" onchange="this.form.submit()">
                        </th>
                        <th class="col-status-pcp-atual">
                            <div class="dropdown" style="position: relative; width: 100%;">
                                <button type="button" 
                                        class="btn btn-secondary dropdown-toggle" 
                                        id="btnFilterStatusEstoque"
                                        onclick="toggleMenuFilterStatusEstoque()" 
                                        style="width: 100%; font-size: 0.725rem; padding: 0.25rem 0.4rem; justify-content: space-between; text-align: left; display: flex; align-items: center; background: #0f172a; border-color: #334155; height: 31px;">
                                    <span id="labelStatusEstoqueSelecionados" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        @php
                                            $reqStatus = request('f_status');
                                            $selStatusArr = $reqStatus ? array_map('trim', explode(',', $reqStatus)) : [];
                                        @endphp
                                        @if(empty($selStatusArr))
                                            Status: Todos
                                        @elseif(count($selStatusArr) == 1)
                                            {{ $selStatusArr[0] }}
                                        @else
                                            {{ count($selStatusArr) }} Selecionados
                                        @endif
                                    </span>
                                    <span style="font-size: 0.55rem; margin-left: 2px;">▼</span>
                                </button>
                                <div id="dropdownMenuFilterStatusEstoque" 
                                     style="display: none; position: absolute; top: 100%; left: 0; min-width: 210px; background: #1e293b; border: 1px solid #475569; border-radius: 0.5rem; padding: 0.65rem; z-index: 1000; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5);">
                                    <div style="font-size: 0.725rem; font-weight: 700; color: #94a3b8; margin-bottom: 0.4rem; border-bottom: 1px solid #334155; padding-bottom: 0.3rem; display: flex; justify-content: space-between; align-items: center;">
                                        <span>STATUS PCP ATUAL</span>
                                        <label style="font-weight: 400; cursor: pointer; color: #6366f1;">
                                            <input type="checkbox" id="chkFilterStatusEstoqueAll" onchange="toggleSelectAllStatusEstoque(this)" {{ empty($selStatusArr) ? 'checked' : '' }} style="margin-right: 3px;"> Todos
                                        </label>
                                    </div>
                                    @php
                                        $statusOpcoesEstoque = ['FALTA', 'SEPARADO', 'RETIRADO', 'FABRICA', 'FABRICAR INTERNO KANBAN'];
                                    @endphp
                                    @foreach($statusOpcoesEstoque as $stOpt)
                                        <label style="display: flex; align-items: center; font-size: 0.75rem; margin-bottom: 0.35rem; cursor: pointer; color: #e2e8f0;">
                                            <input type="checkbox" class="chk-status-estoque-option" value="{{ $stOpt }}" {{ (empty($selStatusArr) || in_array($stOpt, $selStatusArr)) ? 'checked' : '' }} onchange="atualizarStatusEstoqueLabel()" style="margin-right: 6px;">
                                            {{ $stOpt }}
                                        </label>
                                    @endforeach
                                    <div style="margin-top: 0.5rem; padding-top: 0.4rem; border-top: 1px solid #334155; display: flex; justify-content: flex-end;">
                                        <button type="button" class="btn btn-primary" style="padding: 0.2rem 0.6rem; font-size: 0.7rem; background-color: #059669; border-color: #059669;" onclick="aplicarFiltroStatusEstoque()">
                                            ✓ Aplicar
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="f_status" id="input_f_status" value="{{ request('f_status') }}" form="formFilterEstoque">
                            </div>
                        </th>
                        <th class="col-pv">
                            <input type="text" name="f_pedido" value="{{ request('f_pedido') }}" class="filter-input" placeholder="Multi: 0066, 0067..." form="formFilterEstoque" onchange="document.getElementById('formFilterEstoque').submit()">
                        </th>
                        <th class="col-codigo-produto">
                            <input type="text" name="f_produto" value="{{ request('f_produto') }}" class="filter-input" placeholder="Multi: 6164, 1050..." form="formFilterEstoque" onchange="document.getElementById('formFilterEstoque').submit()">
                        </th>
                        <th class="col-descricao">
                            <input type="text" name="f_descricao" value="{{ request('f_descricao') }}" class="filter-input" placeholder="Multi: CABO, CHAVE..." form="formFilterEstoque" onchange="document.getElementById('formFilterEstoque').submit()">
                        </th>
                        <th class="col-desc-longa" style="display: none;">
                            <input type="text" name="f_desc_longa" value="{{ request('f_desc_longa') }}" class="filter-input" placeholder="Multi: FLEXIVEL, ISOLADO..." form="formFilterEstoque" onchange="document.getElementById('formFilterEstoque').submit()">
                        </th>
                        <th class="col-produto-pai" style="display: none;">
                            <input type="text" name="f_prod_pai" value="{{ request('f_prod_pai') }}" class="filter-input" placeholder="Multi: QUADRO, 9510..." form="formFilterEstoque" onchange="document.getElementById('formFilterEstoque').submit()">
                        </th>
                        <th class="col-op">
                            <input type="text" name="f_op" value="{{ request('f_op') }}" class="filter-input" placeholder="Multi: 0187, 0188..." form="formFilterEstoque" onchange="document.getElementById('formFilterEstoque').submit()">
                        </th>
                        <th class="col-qtd-op"></th>
                        <th class="col-qtd-estoque"></th>
                        <th class="col-qtd-comprar"></th>
                        <th class="col-cliente">
                            <input type="text" name="f_cliente" value="{{ request('f_cliente') }}" class="filter-input" placeholder="Multi: CLIENTE A, B..." form="formFilterEstoque" onchange="document.getElementById('formFilterEstoque').submit()">
                        </th>
                        <th class="col-obs-estoque"></th>
                        <th class="col-status-pcp-edit"></th>
                        <th class="col-acoes"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                    <tr id="row_estoque_{{ $item->id }}">
                        <td class="col-fabrica" style="text-align: center;">
                            <span class="badge" style="background-color: #0284c7; color: #ffffff; font-size: 0.8rem; font-weight: 700; padding: 0.2rem 0.45rem; border-radius: 0.375rem;" title="Sequência de fábrica do PV (Painel PCP)">
                                {{ $item->fabrica_seq ?? '99' }}
                            </span>
                        </td>
                        <td class="col-status-pcp-atual">
                            @php
                                $badgeClass = match($item->status) {
                                    'FALTA' => 'badge-falta',
                                    'SEPARADO' => 'badge-separado',
                                    'RETIRADO' => 'badge-retirado',
                                    'FABRICA' => 'badge-fabrica',
                                    'FABRICAR INTERNO KANBAN' => 'badge-kanban',
                                    default => 'badge-falta'
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}" id="badge_status_{{ $item->id }}">{{ $item->status }}</span>
                        </td>
                        <td class="col-pv"><strong>{{ $item->pedido ?? '-' }}</strong></td>
                        <td class="col-codigo-produto"><strong>{{ $item->codigo_produto }}</strong></td>
                        <td class="col-descricao" style="font-size: 0.775rem;">{{ $item->descricao ?? '-' }}</td>
                        <td class="col-desc-longa" style="display: none;">
                            <span class="badge-desc-longa">{{ $item->descricao_longa ?? ($item->descricao ?? '-') }}</span>
                        </td>
                        <td class="col-produto-pai" style="display: none;">
                            <span class="badge-produto-pai">{{ $item->produto_pai ?? '-' }}</span>
                        </td>
                        <td class="col-op"><code style="color: var(--accent); font-size: 0.75rem;">{{ $item->op ?? '-' }}</code></td>
                        <td class="col-qtd-op" style="text-align: center;">
                            <strong id="qtd_op_{{ $item->id }}" style="color: #38bdf8; font-size: 0.85rem;">{{ floatval($item->quantidade) }}</strong>
                        </td>
                        <td class="col-qtd-estoque" style="text-align: center;">
                            @php
                                $valQtdEstoque = floatval($item->quantidade_estoque);
                            @endphp
                            <input type="number" 
                                   step="0.01" 
                                   name="items[{{ $item->id }}][quantidade_estoque]"
                                   id="input_qtd_est_{{ $item->id }}"
                                   value="{{ $valQtdEstoque }}" 
                                   class="form-control" 
                                   style="width: 100px; text-align: center; margin: 0 auto; padding: 0.2rem 0.4rem; font-weight: 600; color: #fcd34d;"
                                   onchange="recalcularLinhaEstoque({{ $item->id }})">
                        </td>
                        <td class="col-qtd-comprar" style="text-align: center;">
                            @php
                                $valQtdComprar = floatval($item->quantidade_comprar);
                            @endphp
                            <span id="label_qtd_comprar_{{ $item->id }}" 
                                  style="font-weight: 700; font-size: 0.85rem; color: {{ $valQtdComprar > 0 ? '#ef4444' : '#10b981' }};">
                                {{ $valQtdComprar }}
                            </span>
                        </td>
                        <td class="col-cliente" style="font-size: 0.775rem;">{{ $item->cliente_obs ?? '-' }}</td>
                        <td class="col-obs-estoque">
                            <input type="text" 
                                   name="items[{{ $item->id }}][observacao_estoque]" 
                                   id="input_obs_est_{{ $item->id }}"
                                   value="{{ $item->observacao_estoque }}" 
                                   class="form-control" 
                                   placeholder="Observação..."
                                   style="min-width: 140px; padding: 0.2rem 0.4rem; font-size: 0.75rem;">
                        </td>
                        <td class="col-status-pcp-edit">
                            <select name="items[{{ $item->id }}][status]" 
                                    id="select_status_{{ $item->id }}"
                                    class="form-select" 
                                    style="padding: 0.2rem 0.4rem; font-size: 0.75rem; min-width: 140px;"
                                    onchange="atualizarBadgeStatus({{ $item->id }})">
                                <option value="FALTA" {{ $item->status == 'FALTA' ? 'selected' : '' }}>FALTA</option>
                                <option value="SEPARADO" {{ $item->status == 'SEPARADO' ? 'selected' : '' }}>SEPARADO</option>
                                <option value="RETIRADO" {{ $item->status == 'RETIRADO' ? 'selected' : '' }}>RETIRADO</option>
                                <option value="FABRICA" {{ $item->status == 'FABRICA' ? 'selected' : '' }}>FABRICA</option>
                                <option value="FABRICAR INTERNO KANBAN" {{ $item->status == 'FABRICAR INTERNO KANBAN' ? 'selected' : '' }}>KANBAN</option>
                            </select>
                        </td>
                        <td class="col-acoes" style="text-align: center;">
                            <button type="button" 
                                    class="btn btn-primary" 
                                    style="padding: 0.2rem 0.5rem; font-size: 0.7rem;"
                                    onclick="solicitarConfirmacaoSave({{ $item->id }})">
                                💾 Salvar
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="14" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                            Nenhum item encontrado no estoque com os filtros aplicados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <!-- Paginação Customizada Dark Mode -->
    @if($items->hasPages())
        <div class="pagination-container">
            <div>
                Exibindo <strong>{{ $items->firstItem() }}</strong> até <strong>{{ $items->lastItem() }}</strong> de <strong>{{ $items->total() }}</strong> itens cadastrados
            </div>
            <div>
                {{ $items->links('pagination::bootstrap-4') }}
            </div>
        </div>
    @endif
</div>

<script>
let protheusItemsCache = [];
let pendingSaveItemId = null;

function checkColumnsVisibility() {
    const hasFilterPai = {{ request()->filled('f_prod_pai') ? 'true' : 'false' }};
    const showPai = localStorage.getItem('showColProdutoPai') === 'true' || hasFilterPai;
    
    document.querySelectorAll('.col-produto-pai').forEach(el => {
        el.style.display = showPai ? 'table-cell' : 'none';
    });
    
    const iconPai = document.getElementById('iconSquarePai');
    if (iconPai) iconPai.innerText = showPai ? '☑️' : '🔲';

    const hasFilterDesc = {{ request()->filled('f_desc_longa') ? 'true' : 'false' }};
    const showDesc = localStorage.getItem('showColDescLonga') === 'true' || hasFilterDesc;

    document.querySelectorAll('.col-desc-longa').forEach(el => {
        el.style.display = showDesc ? 'table-cell' : 'none';
    });

    const iconDesc = document.getElementById('iconSquareDescLonga');
    if (iconDesc) iconDesc.innerText = showDesc ? '☑️' : '🔲';
}

function toggleColunaProdutoPai() {
    const current = localStorage.getItem('showColProdutoPai') === 'true';
    localStorage.setItem('showColProdutoPai', !current);
    checkColumnsVisibility();
}

function toggleColunaDescricaoLonga() {
    const current = localStorage.getItem('showColDescLonga') === 'true';
    localStorage.setItem('showColDescLonga', !current);
    checkColumnsVisibility();
}

document.addEventListener('DOMContentLoaded', function() {
    checkColumnsVisibility();
});

function abrirModalConsultaProtheus() {
    document.getElementById('modalConsultaProtheus').style.display = 'block';
    document.getElementById('consulta_pedido').focus();
}

function buscarItensProtheus() {
    const pedido = document.getElementById('consulta_pedido').value.trim();
    const selectedFiliaisArr = getFiliaisSelecionadasArray();
    const totalFiliaisCount = document.querySelectorAll('.chk-filial-option').length;
    const filialParam = (selectedFiliaisArr.length === 0 || selectedFiliaisArr.length === totalFiliaisCount) 
        ? null 
        : selectedFiliaisArr.join(',');
    const statusLabel = document.getElementById('resultado_status_label');
    const tbody = document.getElementById('tbody_protheus_items');
    const formBatch = document.getElementById('formImportBatch');

    if (!pedido) {
        alert('Por favor, informe o número do Pedido ou C2_OBS!');
        return;
    }

    statusLabel.innerHTML = '⏳ Consultando itens no Protheus... Aguarde...';
    statusLabel.style.color = '#a5b4fc';
    tbody.innerHTML = '';
    formBatch.style.display = 'none';

    fetch('{{ route("estoque.consultar-pedido") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ pedido: pedido, filial: filialParam })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            statusLabel.innerHTML = '❌ ' + data.message;
            statusLabel.style.color = '#fca5a5';
            return;
        }

        protheusItemsCache = data.items;
        statusLabel.innerHTML = `✅ ${data.count} item(ns) encontrado(s) para o pedido "${pedido}" no Protheus. Selecione os itens abaixo para importar:`;
        statusLabel.style.color = '#6ee7b7';

        tbody.innerHTML = '';
        data.items.forEach((item, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="text-align: center;">
                    <input type="checkbox" class="item-checkbox" data-index="${index}" checked onchange="atualizarContadorSelecionados()">
                </td>
                <td>${item.filial || '-'}</td>
                <td><strong>${item.pedido || '-'}</strong></td>
                <td><code style="color: var(--accent);">${item.op || '-'}</code></td>
                <td><strong>${item.codigo_produto}</strong></td>
                <td style="font-size: 0.75rem;">${item.descricao || '-'}</td>
                <td class="col-desc-longa" style="display: none; font-size: 0.75rem; color: #38bdf8;">${item.descricao_longa || '-'}</td>
                <td class="col-produto-pai" style="display: none; font-size: 0.75rem; color: #c084fc;"><code>${item.produto_pai || '-'}</code></td>
                <td style="text-align: center;"><strong>${item.quantidade}</strong></td>
                <td style="font-size: 0.75rem;">${item.cliente_obs || '-'}</td>
            `;
            tbody.appendChild(tr);
        });

        formBatch.style.display = 'block';
        atualizarContadorSelecionados();
        checkColumnsVisibility();
    })
    .catch(err => {
        console.error(err);
        statusLabel.innerHTML = '❌ Erro ao se comunicar com o servidor. Tente novamente.';
        statusLabel.style.color = '#fca5a5';
    });
}

function toggleAllCheckboxes(source) {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(cb => cb.checked = source.checked);
    atualizarContadorSelecionados();
}

function atualizarContadorSelecionados() {
    const checkboxes = document.querySelectorAll('.item-checkbox:checked');
    const countLabel = document.getElementById('label_selecionados_count');
    const selectedItems = [];

    checkboxes.forEach(cb => {
        const idx = cb.getAttribute('data-index');
        if (protheusItemsCache[idx]) {
            selectedItems.push(protheusItemsCache[idx]);
        }
    });

    countLabel.innerText = `${selectedItems.length} item(ns) selecionado(s)`;
    document.getElementById('input_items_json').value = JSON.stringify(selectedItems);
}

function recalcularLinhaEstoque(id) {
    const qtdOpEl = document.getElementById('qtd_op_' + id);
    const inputEstEl = document.getElementById('input_qtd_est_' + id);
    const labelComprarEl = document.getElementById('label_qtd_comprar_' + id);

    if (!qtdOpEl || !inputEstEl || !labelComprarEl) return;

    const qtdOp = parseFloat(qtdOpEl.innerText) || 0;
    const qtdEst = parseFloat(inputEstEl.value) || 0;
    const qtdComprar = Math.max(0, qtdOp - qtdEst);

    labelComprarEl.innerText = qtdComprar;
    if (qtdComprar > 0) {
        labelComprarEl.style.color = '#ef4444';
    } else {
        labelComprarEl.style.color = '#10b981';
    }
}

function atualizarBadgeStatus(id) {
    const select = document.getElementById('select_status_' + id);
    const badge = document.getElementById('badge_status_' + id);
    if (!select || !badge) return;

    const val = select.value;
    badge.innerText = val;
    badge.className = 'badge ' + (
        val === 'FALTA' ? 'badge-falta' :
        val === 'SEPARADO' ? 'badge-separado' :
        val === 'RETIRADO' ? 'badge-retirado' :
        val === 'FABRICA' ? 'badge-fabrica' : 'badge-kanban'
    );
}

function solicitarConfirmacaoSave(id) {
    pendingSaveItemId = id;
    const select = document.getElementById('select_status_' + id);
    const inputEst = document.getElementById('input_qtd_est_' + id);
    const statusVal = select ? select.value : '';
    const qtdEstVal = inputEst ? inputEst.value : '0';

    const textDetails = document.getElementById('confirmTextDetails');
    textDetails.innerHTML = `Deseja salvar as alterações deste item?<br>• <strong>Qtd Estoque:</strong> ${qtdEstVal}<br>• <strong>Novo Status:</strong> ${statusVal}`;

    document.getElementById('modalConfirmacaoSave').style.display = 'block';
    document.getElementById('modalOverlay').style.display = 'block';
}

function fecharModalConfirmacao() {
    pendingSaveItemId = null;
    document.getElementById('modalConfirmacaoSave').style.display = 'none';
    document.getElementById('modalOverlay').style.display = 'none';
}

document.getElementById('btnConfirmarSaveAction').addEventListener('click', function() {
    if (!pendingSaveItemId) return;
    window.mostrarLoading('💾 Salvando item de estoque... Aguarde...');

    const row = document.getElementById('row_estoque_' + pendingSaveItemId);
    const inputEst = document.getElementById('input_qtd_est_' + pendingSaveItemId);
    const selectStatus = document.getElementById('select_status_' + pendingSaveItemId);

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ url("estoque") }}/' + pendingSaveItemId;

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);

    const methodInput = document.createElement('input');
    methodInput.type = 'hidden';
    methodInput.name = '_method';
    methodInput.value = 'PUT';
    form.appendChild(methodInput);

    if (inputEst) {
        const estInput = document.createElement('input');
        estInput.type = 'hidden';
        estInput.name = 'quantidade_estoque';
        estInput.value = (inputEst.value === '' || inputEst.value === null) ? '0' : inputEst.value;
        form.appendChild(estInput);
    }

    const inputObs = document.getElementById('input_obs_est_' + pendingSaveItemId);
    if (inputObs) {
        const obsInput = document.createElement('input');
        obsInput.type = 'hidden';
        obsInput.name = 'observacao_estoque';
        obsInput.value = inputObs.value;
        form.appendChild(obsInput);
    }

    if (selectStatus) {
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = selectStatus.value;
        form.appendChild(statusInput);
    }

    document.body.appendChild(form);
    form.submit();
});

// Gestor de Seleção Múltipla de Filiais Protheus
function toggleMenuFiliaisProtheus() {
    const el = document.getElementById('dropdownMenuFiliaisProtheus');
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

function toggleSelectAllFiliais(masterChk) {
    const checkboxes = document.querySelectorAll('.chk-filial-option');
    checkboxes.forEach(chk => chk.checked = masterChk.checked);
    atualizarLabelFiliais();
}

function getFiliaisSelecionadasArray() {
    const checkboxes = document.querySelectorAll('.chk-filial-option:checked');
    const values = [];
    checkboxes.forEach(chk => values.push(chk.value));
    return values;
}

function atualizarLabelFiliais() {
    const totalChk = document.querySelectorAll('.chk-filial-option').length;
    const selectedChk = document.querySelectorAll('.chk-filial-option:checked');
    const label = document.getElementById('labelFiliaisSelecionadas');
    const masterChk = document.getElementById('chkFilialSelectAll');

    if (!label) return;

    if (selectedChk.length === totalChk) {
        label.innerText = '🏢 Todas as Filiais';
        if (masterChk) masterChk.checked = true;
    } else if (selectedChk.length === 0) {
        label.innerText = '🏢 Nenhum (Todas)';
        if (masterChk) masterChk.checked = false;
    } else {
        if (masterChk) masterChk.checked = false;
        const vals = Array.from(selectedChk).map(c => c.value);
        if (vals.length <= 2) {
            label.innerText = '🏢 Filial ' + vals.join(', ');
        } else {
            label.innerText = `🏢 ${vals.length} Filiais Selecionadas`;
        }
    }
}

// Gestor de Visibilidade de Colunas (Excel) para Estoque
const ESTOQUE_COLUNAS_PADRAO = {
    'col-fabrica': true,
    'col-status-pcp-atual': true,
    'col-pv': true,
    'col-codigo-produto': true,
    'col-descricao': true,
    'col-desc-longa': false,
    'col-produto-pai': false,
    'col-op': true,
    'col-qtd-op': true,
    'col-qtd-comprar': true,
    'col-cliente': true
};

function toggleMenuColunasEstoque() {
    const el = document.getElementById('dropdownMenuColunasEstoque');
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

// Gestor de Seleção Múltipla do Filtro Status PCP Atual no Estoque
function toggleMenuFilterStatusEstoque() {
    const el = document.getElementById('dropdownMenuFilterStatusEstoque');
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

function toggleSelectAllStatusEstoque(masterChk) {
    const checkboxes = document.querySelectorAll('.chk-status-estoque-option');
    checkboxes.forEach(chk => chk.checked = masterChk.checked);
    atualizarStatusEstoqueLabel();
}

function atualizarStatusEstoqueLabel() {
    const checkboxes = document.querySelectorAll('.chk-status-estoque-option:checked');
    const totalChk = document.querySelectorAll('.chk-status-estoque-option').length;
    const values = Array.from(checkboxes).map(c => c.value);
    const label = document.getElementById('labelStatusEstoqueSelecionados');
    const masterChk = document.getElementById('chkFilterStatusEstoqueAll');

    if (!label) return;

    if (values.length === totalChk || values.length === 0) {
        label.innerText = 'Status: Todos';
        if (masterChk) masterChk.checked = (values.length === totalChk);
    } else {
        if (masterChk) masterChk.checked = false;
        if (values.length === 1) label.innerText = values[0];
        else label.innerText = values.length + ' Selecionados';
    }
}

function aplicarFiltroStatusEstoque() {
    const checkboxes = document.querySelectorAll('.chk-status-estoque-option:checked');
    const totalChk = document.querySelectorAll('.chk-status-estoque-option').length;
    const values = Array.from(checkboxes).map(c => c.value);
    const inputHidden = document.getElementById('input_f_status');

    if (values.length === totalChk || values.length === 0) {
        inputHidden.value = '';
    } else {
        inputHidden.value = values.join(',');
    }
    const menu = document.getElementById('dropdownMenuFilterStatusEstoque');
    if (menu) menu.style.display = 'none';
    document.getElementById('formFilterEstoque').submit();
}

document.addEventListener('click', function(e) {
    const menuCol = document.getElementById('dropdownMenuColunasEstoque');
    if (menuCol && !menuCol.contains(e.target) && !e.target.closest('.dropdown')) {
        menuCol.style.display = 'none';
    }
    const menuFil = document.getElementById('dropdownMenuFiliaisProtheus');
    const btnFil = document.getElementById('btnDropdownFiliais');
    if (menuFil && btnFil && !menuFil.contains(e.target) && !btnFil.contains(e.target)) {
        menuFil.style.display = 'none';
    }
    const menuSt = document.getElementById('dropdownMenuFilterStatusEstoque');
    const btnSt = document.getElementById('btnFilterStatusEstoque');
    if (menuSt && btnSt && !menuSt.contains(e.target) && !btnSt.contains(e.target)) {
        menuSt.style.display = 'none';
    }
});

function toggleColunaEstoque(colClass, isChecked) {
    const elements = document.querySelectorAll('.' + colClass);
    elements.forEach(el => {
        el.style.display = isChecked ? '' : 'none';
    });
    let prefs = JSON.parse(localStorage.getItem('estoque_colunas_visiveis') || '{}');
    prefs[colClass] = isChecked;
    localStorage.setItem('estoque_colunas_visiveis', JSON.stringify(prefs));
}

function inicializarColunasEstoque() {
    let prefs = JSON.parse(localStorage.getItem('estoque_colunas_visiveis') || '{}');
    Object.keys(ESTOQUE_COLUNAS_PADRAO).forEach(colClass => {
        let isChecked = prefs.hasOwnProperty(colClass) ? prefs[colClass] : ESTOQUE_COLUNAS_PADRAO[colClass];
        const chk = document.getElementById('chk_' + colClass);
        if (chk) chk.checked = isChecked;
        toggleColunaEstoque(colClass, isChecked);
    });
    atualizarLabelFiliais();
    atualizarStatusEstoqueLabel();
}

document.addEventListener('DOMContentLoaded', inicializarColunasEstoque);
</script>
@endsection
