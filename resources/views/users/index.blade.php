@extends('layouts.app')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700;">👥 Gestão de Usuários & Perfis</h1>
        <p style="color: var(--text-muted); font-size: 0.8rem;">Controle de permissões e credenciais de acesso ao sistema.</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="document.getElementById('modalAddUser').style.display='block'">
            + Novo Usuário
        </button>
    </div>
</div>

<!-- Modal 1: Novo Usuário -->
<div class="card" id="modalAddUser" style="display: none; border-color: rgba(99, 102, 241, 0.5); max-width: 500px; margin: 0 auto 1.25rem auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
        <h3 style="font-size: 1rem; color: #a5b4fc;">➕ Cadastrar Novo Usuário</h3>
        <button type="button" class="btn btn-secondary" style="padding: 0.2rem 0.5rem;" onclick="document.getElementById('modalAddUser').style.display='none'">✕</button>
    </div>

    <form action="{{ route('users.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label class="form-label">Nome Completo *</label>
            <input type="text" name="name" class="form-control" placeholder="Ex: João da Silva" required>
        </div>

        <div class="form-group">
            <label class="form-label">E-mail Corporativo *</label>
            <input type="email" name="email" class="form-control" placeholder="joao@maquigeral.com.br" required>
        </div>

        <div class="form-group">
            <label class="form-label">Perfil / Pergunta de Acesso *</label>
            <select name="role" class="form-select" required>
                <option value="ADMIN">ADMIN (Acesso Total + Gestão de Usuários)</option>
                <option value="COMPRAS">COMPRAS (Dashboard + Painel de Compras)</option>
                <option value="ESTOQUE">ESTOQUE (Dashboard + Painel de Estoque/PCP)</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Senha Inicial *</label>
            <input type="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres" required>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalAddUser').style.display='none'">Cancelar</button>
            <button type="submit" class="btn btn-primary">Salvar Usuário</button>
        </div>
    </form>
</div>

<!-- Modal 2: Editar Usuário / Resetar Senha -->
<div class="card" id="modalEditUser" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10000; width: 90%; max-width: 500px; border-color: rgba(99, 102, 241, 0.8); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.85);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
        <h3 style="font-size: 1rem; color: #a5b4fc;">✏️ Editar Usuário</h3>
        <button type="button" class="btn btn-secondary" style="padding: 0.2rem 0.5rem;" onclick="fecharModalEditUser()">✕</button>
    </div>

    <form action="" method="POST" id="formEditUser">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label class="form-label">Nome Completo *</label>
            <input type="text" name="name" id="edit_name" class="form-control" required>
        </div>

        <div class="form-group">
            <label class="form-label">E-mail *</label>
            <input type="email" name="email" id="edit_email" class="form-control" required>
        </div>

        <div class="form-group">
            <label class="form-label">Perfil de Acesso *</label>
            <select name="role" id="edit_role" class="form-select" required>
                <option value="ADMIN">ADMIN (Acesso Total + Gestão de Usuários)</option>
                <option value="COMPRAS">COMPRAS (Dashboard + Painel de Compras)</option>
                <option value="ESTOQUE">ESTOQUE (Dashboard + Painel de Estoque/PCP)</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Nova Senha (Deixe em branco para manter a atual)</label>
            <input type="password" name="password" class="form-control" placeholder="Preencha apenas se for alterar a senha">
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
            <button type="button" class="btn btn-secondary" onclick="fecharModalEditUser()">Cancelar</button>
            <button type="submit" class="btn btn-primary">Atualizar Dados</button>
        </div>
    </form>
</div>
<div id="modalOverlayUser" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.75); z-index: 9999;" onclick="fecharModalEditUser()"></div>

<!-- Tabela de Usuários -->
<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Perfil de Acesso</th>
                    <th>Data de Cadastro</th>
                    <th style="text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                <tr>
                    <td><strong>{{ $u->name }}</strong></td>
                    <td style="color: #cbd5e1;">{{ $u->email }}</td>
                    <td>
                        @php
                            $roleBadge = match($u->role) {
                                'ADMIN' => 'badge-faturado',
                                'COMPRAS' => 'badge-antecipado',
                                'ESTOQUE' => 'badge-separado',
                                default => 'badge-pendente'
                            };
                        @endphp
                        <span class="badge {{ $roleBadge }}">{{ $u->role }}</span>
                    </td>
                    <td style="font-size: 0.75rem; color: var(--text-muted);">
                        {{ $u->created_at ? $u->created_at->format('d/m/Y H:i') : '-' }}
                    </td>
                    <td style="text-align: center;">
                        <div style="display: flex; gap: 0.3rem; justify-content: center;">
                            <button type="button" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" onclick='abrirModalEditUser({{ json_encode($u) }})'>
                                ✏️ Editar
                            </button>
                            @if(auth()->id() !== $u->id)
                                <form action="{{ route('users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir o usuário {{ $u->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; color: #fca5a5;">
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
function abrirModalEditUser(user) {
    document.getElementById('formEditUser').action = `/users/${user.id}`;
    document.getElementById('edit_name').value = user.name;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.role;

    document.getElementById('modalEditUser').style.display = 'block';
    document.getElementById('modalOverlayUser').style.display = 'block';
}

function fecharModalEditUser() {
    document.getElementById('modalEditUser').style.display = 'none';
    document.getElementById('modalOverlayUser').style.display = 'none';
}
</script>
@endsection
