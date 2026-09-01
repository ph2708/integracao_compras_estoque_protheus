@extends('layouts.app')

@section('content')
<div style="display: flex; justify-space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700; color: #f8fafc; display: flex; align-items: center; gap: 0.5rem;">
            👥 Gestão de Usuários & Permissões
        </h1>
        <p style="color: var(--text-muted); font-size: 0.825rem; margin-top: 0.2rem;">
            Gerencie credenciais, perfis predefinidos e permissões granulares por módulo.
        </p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="abrirModalAddUser()" style="display: flex; align-items: center; gap: 0.4rem; padding: 0.5rem 0.9rem; font-weight: 600;">
            <span>➕ Novo Usuário</span>
        </button>
    </div>
</div>

<!-- Modal 1: Novo Usuário -->
<div class="card" id="modalAddUser" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10000; width: 92%; max-width: 560px; border-color: rgba(99, 102, 241, 0.8); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.9); max-height: 92vh; overflow-y: auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.6rem;">
        <h3 style="font-size: 1.1rem; color: #a5b4fc; margin: 0; font-weight: 700; display: flex; align-items: center; gap: 0.4rem;">
            ➕ Cadastrar Novo Usuário
        </h3>
        <button type="button" class="btn btn-secondary" style="padding: 0.2rem 0.5rem; font-size: 0.8rem;" onclick="fecharModalAddUser()">✕</button>
    </div>

    <form action="{{ route('users.store') }}" method="POST">
        @csrf
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
            <div class="form-group" style="margin-bottom: 0.75rem;">
                <label class="form-label" style="font-size: 0.775rem; font-weight: 600;">Nome Completo *</label>
                <input type="text" name="name" class="form-control" placeholder="Ex: João da Silva" required style="font-size: 0.825rem; padding: 0.4rem 0.6rem;">
            </div>

            <div class="form-group" style="margin-bottom: 0.75rem;">
                <label class="form-label" style="font-size: 0.775rem; font-weight: 600;">E-mail Corporativo *</label>
                <input type="email" name="email" class="form-control" placeholder="joao@maquigeral.com.br" required style="font-size: 0.825rem; padding: 0.4rem 0.6rem;">
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 0.85rem;">
            <label class="form-label" style="font-size: 0.775rem; font-weight: 600; color: #a5b4fc;">Perfil de Acesso (Preset)*</label>
            <select name="role" id="add_role" class="form-select" onchange="aplicarPresetPerfil('add')" required style="font-size: 0.825rem; padding: 0.45rem 0.6rem; border-color: #6366f1; background-color: #0f172a; color: #f8fafc; font-weight: 600;">
                <option value="ADMIN">ADMIN (Acesso Total + Gestão de Usuários)</option>
                <option value="COMPRAS">COMPRAS (Dashboard + Compras + Estoque)</option>
                <option value="ESTOQUE">ESTOQUE (Dashboard + Estoque PCP)</option>
                <option value="PCP" selected>PCP / PRODUÇÃO (Painel PCP + Montagem + Fechamento OP)</option>
                <option value="VISUALIZACAO">VISUALIZAÇÃO (Apenas Leitura nos Painéis)</option>
                <option value="PERSONALIZADO">⚡ PERSONALIZADO (Ajustes Específicos)</option>
            </select>
            <span style="font-size: 0.7rem; color: #94a3b8; margin-top: 0.25rem; display: block;">
                Ao selecionar um perfil, as permissões abaixo são sincronizadas automaticamente.
            </span>
        </div>

        <!-- Grid de Cartões de Módulos Específicos -->
        <div style="background: rgba(15, 23, 42, 0.7); padding: 0.85rem; border-radius: 8px; border: 1px solid #334155; margin-bottom: 0.85rem;">
            <div style="font-size: 0.75rem; font-weight: 700; color: #a5b4fc; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.6rem; display: flex; align-items: center; justify-content: space-between;">
                <span>Permissões Específicas de Módulos</span>
                <span id="add_custom_badge" class="badge badge-antecipado" style="font-size: 0.65rem; display: none;">Perfil Ajustado Manualmente</span>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 0.55rem;">
                <!-- Módulo PCP -->
                <div style="background: rgba(30, 41, 59, 0.6); padding: 0.6rem 0.75rem; border-radius: 6px; border: 1px solid #475569;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.3rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: #f8fafc; font-size: 0.8rem; font-weight: 600; margin: 0;">
                            <input type="checkbox" name="permissao_painel_pcp" id="add_permissao_painel_pcp" value="1" onchange="verificarMudancaCustom('add')" checked>
                            🏭 Painel PCP GMGs
                        </label>
                        <span style="font-size: 0.675rem; color: #94a3b8;">Visão Gerencial por PV</span>
                    </div>
                    <div style="margin-left: 1.5rem; padding-top: 0.2rem; border-top: 1px dashed rgba(255,255,255,0.1);">
                        <label style="display: flex; align-items: center; gap: 0.4rem; cursor: pointer; color: #c084fc; font-size: 0.75rem; margin: 0;">
                            <input type="checkbox" name="permissao_painel_pcp_edicao" id="add_permissao_painel_pcp_edicao" value="1" onchange="verificarMudancaCustom('add')" checked>
                            ✏️ Permitir Edição de Células/PVs <span style="color: #94a3b8; font-weight: 400;">(Desmarcado = Apenas Leitura)</span>
                        </label>
                    </div>
                </div>

                <!-- Módulo Montagem & Horas -->
                <div style="background: rgba(30, 41, 59, 0.6); padding: 0.6rem 0.75rem; border-radius: 6px; border: 1px solid #475569; display: flex; align-items: center; justify-content: space-between;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: #f8fafc; font-size: 0.8rem; font-weight: 600; margin: 0;">
                        <input type="checkbox" name="permissao_painel_montagem" id="add_permissao_painel_montagem" value="1" onchange="verificarMudancaCustom('add')" checked>
                        ⏱️ Painel Montagem & Coleta de Horas
                    </label>
                    <span style="font-size: 0.675rem; color: #94a3b8;">Coletores de Fábrica</span>
                </div>

                <!-- Módulo Fechamento de OP -->
                <div style="background: rgba(30, 41, 59, 0.6); padding: 0.6rem 0.75rem; border-radius: 6px; border: 1px solid #475569; display: flex; align-items: center; justify-content: space-between;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: #f8fafc; font-size: 0.8rem; font-weight: 600; margin: 0;">
                        <input type="checkbox" name="permissao_fechamento_op" id="add_permissao_fechamento_op" value="1" onchange="verificarMudancaCustom('add')" checked>
                        🔒 Fechamento de OP (Encerrar Produção)
                    </label>
                    <span style="font-size: 0.675rem; color: #94a3b8;">Encerramento de Ordem</span>
                </div>
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 0.85rem;">
            <label class="form-label" style="font-size: 0.775rem; font-weight: 600;">Senha Inicial *</label>
            <input type="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres" required style="font-size: 0.825rem; padding: 0.4rem 0.6rem;">
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
            <button type="button" class="btn btn-secondary" onclick="fecharModalAddUser()">Cancelar</button>
            <button type="submit" class="btn btn-primary" style="padding: 0.4rem 1rem; font-weight: 600;">💾 Salvar Usuário</button>
        </div>
    </form>
</div>

<!-- Modal 2: Editar Usuário / Permissões -->
<div class="card" id="modalEditUser" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10000; width: 92%; max-width: 560px; border-color: rgba(99, 102, 241, 0.8); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.9); max-height: 92vh; overflow-y: auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.6rem;">
        <h3 style="font-size: 1.1rem; color: #a5b4fc; margin: 0; font-weight: 700; display: flex; align-items: center; gap: 0.4rem;">
            ✏️ Editar Usuário & Permissões
        </h3>
        <button type="button" class="btn btn-secondary" style="padding: 0.2rem 0.5rem; font-size: 0.8rem;" onclick="fecharModalEditUser()">✕</button>
    </div>

    <form action="" method="POST" id="formEditUser">
        @csrf
        @method('PUT')
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
            <div class="form-group" style="margin-bottom: 0.75rem;">
                <label class="form-label" style="font-size: 0.775rem; font-weight: 600;">Nome Completo *</label>
                <input type="text" name="name" id="edit_name" class="form-control" required style="font-size: 0.825rem; padding: 0.4rem 0.6rem;">
            </div>

            <div class="form-group" style="margin-bottom: 0.75rem;">
                <label class="form-label" style="font-size: 0.775rem; font-weight: 600;">E-mail *</label>
                <input type="email" name="email" id="edit_email" class="form-control" required style="font-size: 0.825rem; padding: 0.4rem 0.6rem;">
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 0.85rem;">
            <label class="form-label" style="font-size: 0.775rem; font-weight: 600; color: #a5b4fc;">Perfil de Acesso (Preset)*</label>
            <select name="role" id="edit_role" class="form-select" onchange="aplicarPresetPerfil('edit')" required style="font-size: 0.825rem; padding: 0.45rem 0.6rem; border-color: #6366f1; background-color: #0f172a; color: #f8fafc; font-weight: 600;">
                <option value="ADMIN">ADMIN (Acesso Total + Gestão de Usuários)</option>
                <option value="COMPRAS">COMPRAS (Dashboard + Compras + Estoque)</option>
                <option value="ESTOQUE">ESTOQUE (Dashboard + Estoque PCP)</option>
                <option value="PCP">PCP / PRODUÇÃO (Painel PCP + Montagem + Fechamento OP)</option>
                <option value="VISUALIZACAO">VISUALIZAÇÃO (Apenas Leitura nos Painéis)</option>
                <option value="PERSONALIZADO">⚡ PERSONALIZADO (Ajustes Específicos)</option>
            </select>
        </div>

        <!-- Grid de Cartões de Módulos Específicos -->
        <div style="background: rgba(15, 23, 42, 0.7); padding: 0.85rem; border-radius: 8px; border: 1px solid #334155; margin-bottom: 0.85rem;">
            <div style="font-size: 0.75rem; font-weight: 700; color: #a5b4fc; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.6rem; display: flex; align-items: center; justify-content: space-between;">
                <span>Permissões Específicas de Módulos</span>
                <span id="edit_custom_badge" class="badge badge-antecipado" style="font-size: 0.65rem; display: none;">Perfil Ajustado Manualmente</span>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 0.55rem;">
                <!-- Módulo PCP -->
                <div style="background: rgba(30, 41, 59, 0.6); padding: 0.6rem 0.75rem; border-radius: 6px; border: 1px solid #475569;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.3rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: #f8fafc; font-size: 0.8rem; font-weight: 600; margin: 0;">
                            <input type="checkbox" name="permissao_painel_pcp" id="edit_permissao_painel_pcp" value="1" onchange="verificarMudancaCustom('edit')">
                            🏭 Painel PCP GMGs
                        </label>
                        <span style="font-size: 0.675rem; color: #94a3b8;">Visão Gerencial por PV</span>
                    </div>
                    <div style="margin-left: 1.5rem; padding-top: 0.2rem; border-top: 1px dashed rgba(255,255,255,0.1);">
                        <label style="display: flex; align-items: center; gap: 0.4rem; cursor: pointer; color: #c084fc; font-size: 0.75rem; margin: 0;">
                            <input type="checkbox" name="permissao_painel_pcp_edicao" id="edit_permissao_painel_pcp_edicao" value="1" onchange="verificarMudancaCustom('edit')">
                            ✏️ Permitir Edição de Células/PVs <span style="color: #94a3b8; font-weight: 400;">(Desmarcado = Apenas Leitura)</span>
                        </label>
                    </div>
                </div>

                <!-- Módulo Montagem & Horas -->
                <div style="background: rgba(30, 41, 59, 0.6); padding: 0.6rem 0.75rem; border-radius: 6px; border: 1px solid #475569; display: flex; align-items: center; justify-content: space-between;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: #f8fafc; font-size: 0.8rem; font-weight: 600; margin: 0;">
                        <input type="checkbox" name="permissao_painel_montagem" id="edit_permissao_painel_montagem" value="1" onchange="verificarMudancaCustom('edit')">
                        ⏱️ Painel Montagem & Coleta de Horas
                    </label>
                    <span style="font-size: 0.675rem; color: #94a3b8;">Coletores de Fábrica</span>
                </div>

                <!-- Módulo Fechamento de OP -->
                <div style="background: rgba(30, 41, 59, 0.6); padding: 0.6rem 0.75rem; border-radius: 6px; border: 1px solid #475569; display: flex; align-items: center; justify-content: space-between;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: #f8fafc; font-size: 0.8rem; font-weight: 600; margin: 0;">
                        <input type="checkbox" name="permissao_fechamento_op" id="edit_permissao_fechamento_op" value="1" onchange="verificarMudancaCustom('edit')">
                        🔒 Fechamento de OP (Encerrar Produção)
                    </label>
                    <span style="font-size: 0.675rem; color: #94a3b8;">Encerramento de Ordem</span>
                </div>
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 0.85rem;">
            <label class="form-label" style="font-size: 0.775rem; font-weight: 600;">Nova Senha <span style="color: #94a3b8; font-weight: 400;">(Deixe em branco para manter a atual)</span></label>
            <input type="password" name="password" class="form-control" placeholder="Preencha apenas se for alterar a senha" style="font-size: 0.825rem; padding: 0.4rem 0.6rem;">
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
            <button type="button" class="btn btn-secondary" onclick="fecharModalEditUser()">Cancelar</button>
            <button type="submit" class="btn btn-primary" style="padding: 0.4rem 1rem; font-weight: 600;">💾 Atualizar Dados</button>
        </div>
    </form>
</div>
<div id="modalOverlayUser" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.8); z-index: 9999;" onclick="fecharModaisUser()"></div>

<!-- Tabela de Usuários Redesenhada -->
<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="min-width: 180px;">Usuário / E-mail</th>
                    <th style="min-width: 130px;">Perfil Principal</th>
                    <th style="min-width: 280px;">Resumo de Permissões Módulos</th>
                    <th style="text-align: center; min-width: 120px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                <tr>
                    <td>
                        <strong style="color: #f8fafc; font-size: 0.875rem;">{{ $u->name }}</strong>
                        <br><span style="font-size: 0.725rem; color: #94a3b8;">📧 {{ $u->email }}</span>
                    </td>
                    <td>
                        @php
                            $roleBadge = match($u->role) {
                                'ADMIN' => 'badge-faturado',
                                'COMPRAS' => 'badge-antecipado',
                                'ESTOQUE' => 'badge-separado',
                                'PCP' => 'badge-fechado',
                                'VISUALIZACAO' => 'badge-pendente',
                                'PERSONALIZADO' => 'badge-kanban',
                                default => 'badge-pendente'
                            };
                            $roleLabel = match($u->role) {
                                'ADMIN' => '👑 ADMIN',
                                'COMPRAS' => '🛒 COMPRAS',
                                'ESTOQUE' => '📦 ESTOQUE',
                                'PCP' => '🏭 PCP',
                                'VISUALIZACAO' => '👁️ LEITURA',
                                'PERSONALIZADO' => '⚡ CUSTOM',
                                default => $u->role
                            };
                        @endphp
                        <span class="badge {{ $roleBadge }}" style="font-weight: 700; font-size: 0.725rem;">{{ $roleLabel }}</span>
                    </td>
                    <td>
                        <div style="display: flex; gap: 0.35rem; flex-wrap: wrap; align-items: center;">
                            @if($u->role === 'ADMIN')
                                <span class="badge badge-faturado" style="font-size: 0.675rem;">🌟 Acesso Total (Master)</span>
                            @else
                                <!-- Badge PCP -->
                                @if($u->canAccessPcp())
                                    <span class="badge {{ $u->canEditPcp() ? 'badge-separado' : 'badge-antecipado' }}" style="font-size: 0.675rem;" title="Painel PCP GMGs">
                                        🏭 PCP {{ $u->canEditPcp() ? '(✏️ Edição)' : '(👁️ Leitura)' }}
                                    </span>
                                @else
                                    <span class="badge badge-falta" style="font-size: 0.675rem; opacity: 0.6;">🚫 PCP</span>
                                @endif

                                <!-- Badge Montagem -->
                                @if($u->canAccessMontagem())
                                    <span class="badge badge-separado" style="font-size: 0.675rem;" title="Montagem & Coleta de Horas">
                                        ⏱️ Montagem
                                    </span>
                                @else
                                    <span class="badge badge-falta" style="font-size: 0.675rem; opacity: 0.6;">🚫 Montagem</span>
                                @endif

                                <!-- Badge Fechamento OP -->
                                @if($u->canCloseOp())
                                    <span class="badge badge-fechado" style="font-size: 0.675rem;" title="Fechamento de OP">
                                        🔒 Fechar OP
                                    </span>
                                @endif

                                <!-- Badge Compras / Estoque -->
                                @if(in_array($u->role, ['COMPRAS', 'ESTOQUE']))
                                    <span class="badge badge-antecipado" style="font-size: 0.675rem;">
                                        {{ $u->role === 'COMPRAS' ? '🛒 Compras + 📦 Estoque' : '📦 Estoque PCP' }}
                                    </span>
                                @endif
                            @endif
                        </div>
                    </td>
                    <td style="text-align: center;">
                        <div style="display: flex; gap: 0.3rem; justify-content: center;">
                            <button type="button" class="btn btn-secondary" style="padding: 0.25rem 0.55rem; font-size: 0.725rem;" onclick='abrirModalEditUser({{ json_encode($u) }})'>
                                ✏️ Editar
                            </button>
                            @if(auth()->id() !== $u->id)
                                <form action="{{ route('users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir o usuário {{ $u->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-secondary" style="padding: 0.25rem 0.55rem; font-size: 0.725rem; color: #fca5a5;">
                                        🗑️ Excluir
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Paginação -->
    <div class="pagination-container">
        <div>
            Exibindo <strong>{{ $users->firstItem() ?? 0 }}</strong> a <strong>{{ $users->lastItem() ?? 0 }}</strong> de <strong>{{ $users->total() }}</strong> usuários
        </div>
        <div>
            {{ $users->links() }}
        </div>
    </div>
</div>

<script>
function aplicarPresetPerfil(prefix) {
    const roleSelect = document.getElementById(prefix + '_role');
    const pcpChk = document.getElementById(prefix + '_permissao_painel_pcp');
    const pcpEdicaoChk = document.getElementById(prefix + '_permissao_painel_pcp_edicao');
    const montagemChk = document.getElementById(prefix + '_permissao_painel_montagem');
    const opChk = document.getElementById(prefix + '_permissao_fechamento_op');
    const customBadge = document.getElementById(prefix + '_custom_badge');

    if (!roleSelect) return;
    const role = roleSelect.value;

    if (customBadge) customBadge.style.display = (role === 'PERSONALIZADO') ? 'inline-block' : 'none';

    if (role === 'ADMIN') {
        if (pcpChk) { pcpChk.checked = true; pcpChk.disabled = true; }
        if (pcpEdicaoChk) { pcpEdicaoChk.checked = true; pcpEdicaoChk.disabled = true; }
        if (montagemChk) { montagemChk.checked = true; montagemChk.disabled = true; }
        if (opChk) { opChk.checked = true; opChk.disabled = true; }
    } else {
        if (pcpChk) pcpChk.disabled = false;
        if (pcpEdicaoChk) pcpEdicaoChk.disabled = false;
        if (montagemChk) montagemChk.disabled = false;
        if (opChk) opChk.disabled = false;

        if (role === 'PCP') {
            if (pcpChk) pcpChk.checked = true;
            if (pcpEdicaoChk) pcpEdicaoChk.checked = true;
            if (montagemChk) montagemChk.checked = true;
            if (opChk) opChk.checked = true;
        } else if (role === 'COMPRAS') {
            if (pcpChk) pcpChk.checked = true;
            if (pcpEdicaoChk) pcpEdicaoChk.checked = true;
            if (montagemChk) montagemChk.checked = false;
            if (opChk) opChk.checked = false;
        } else if (role === 'ESTOQUE') {
            if (pcpChk) pcpChk.checked = true;
            if (pcpEdicaoChk) pcpEdicaoChk.checked = false;
            if (montagemChk) montagemChk.checked = false;
            if (opChk) opChk.checked = false;
        } else if (role === 'VISUALIZACAO') {
            if (pcpChk) pcpChk.checked = true;
            if (pcpEdicaoChk) pcpEdicaoChk.checked = false;
            if (montagemChk) montagemChk.checked = true;
            if (opChk) opChk.checked = false;
        }
    }
}

function verificarMudancaCustom(prefix) {
    const roleSelect = document.getElementById(prefix + '_role');
    const customBadge = document.getElementById(prefix + '_custom_badge');
    if (!roleSelect) return;

    if (roleSelect.value !== 'ADMIN') {
        roleSelect.value = 'PERSONALIZADO';
        if (customBadge) customBadge.style.display = 'inline-block';
    }
}

function abrirModalAddUser() {
    document.getElementById('modalAddUser').style.display = 'block';
    document.getElementById('modalOverlayUser').style.display = 'block';
    aplicarPresetPerfil('add');
}

function fecharModalAddUser() {
    document.getElementById('modalAddUser').style.display = 'none';
    document.getElementById('modalOverlayUser').style.display = 'none';
}

function abrirModalEditUser(user) {
    document.getElementById('formEditUser').action = `/users/${user.id}`;
    document.getElementById('edit_name').value = user.name;
    document.getElementById('edit_email').value = user.email;
    
    const roleSelect = document.getElementById('edit_role');
    roleSelect.value = user.role || 'PCP';

    const pcpChk = document.getElementById('edit_permissao_painel_pcp');
    const pcpEdicaoChk = document.getElementById('edit_permissao_painel_pcp_edicao');
    const montagemChk = document.getElementById('edit_permissao_painel_montagem');
    const opChk = document.getElementById('edit_permissao_fechamento_op');

    if (pcpChk) pcpChk.checked = (user.role === 'ADMIN') || (user.permissao_painel_pcp === undefined ? true : !!user.permissao_painel_pcp);
    if (pcpEdicaoChk) pcpEdicaoChk.checked = (user.role === 'ADMIN') || (user.permissao_painel_pcp_edicao === undefined ? true : !!user.permissao_painel_pcp_edicao);
    if (montagemChk) montagemChk.checked = (user.role === 'ADMIN') || (user.permissao_painel_montagem === undefined ? true : !!user.permissao_painel_montagem);
    if (opChk) opChk.checked = !!user.permissao_fechamento_op;

    if (user.role === 'ADMIN') {
        aplicarPresetPerfil('edit');
    } else {
        if (pcpChk) pcpChk.disabled = false;
        if (pcpEdicaoChk) pcpEdicaoChk.disabled = false;
        if (montagemChk) montagemChk.disabled = false;
        if (opChk) opChk.disabled = false;
    }

    document.getElementById('modalEditUser').style.display = 'block';
    document.getElementById('modalOverlayUser').style.display = 'block';
}

function fecharModalEditUser() {
    document.getElementById('modalEditUser').style.display = 'none';
    document.getElementById('modalOverlayUser').style.display = 'none';
}

function fecharModaisUser() {
    fecharModalAddUser();
    fecharModalEditUser();
}
</script>
@endsection
