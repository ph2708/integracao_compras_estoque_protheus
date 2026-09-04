<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Lista todos os usuários cadastrados
     */
    public function index()
    {
        $users = User::latest()->paginate(15);
        return view('users.index', compact('users'));
    }

    /**
     * Cadastra um novo usuário
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:ADMIN,COMPRAS,ESTOQUE,PCP,VISUALIZACAO,PERSONALIZADO',
            'permissao_fechamento_op' => 'nullable|boolean',
            'permissao_painel_pcp' => 'nullable|boolean',
            'permissao_painel_pcp_edicao' => 'nullable|boolean',
            'permissao_painel_montagem' => 'nullable|boolean',
            'permissao_compras_edicao' => 'nullable|boolean',
            'permissao_estoque_edicao' => 'nullable|boolean',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['permissao_fechamento_op'] = $request->has('permissao_fechamento_op');
        $validated['permissao_painel_pcp'] = $request->has('permissao_painel_pcp');
        $validated['permissao_painel_pcp_edicao'] = $request->has('permissao_painel_pcp_edicao');
        $validated['permissao_painel_montagem'] = $request->has('permissao_painel_montagem');
        $validated['permissao_compras_edicao'] = $request->has('permissao_compras_edicao');
        $validated['permissao_estoque_edicao'] = $request->has('permissao_estoque_edicao');

        User::create($validated);

        return redirect()->route('users.index')->with('success', 'Usuário cadastrado com sucesso!');
    }

    /**
     * Atualiza um usuário existente
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => 'required|in:ADMIN,COMPRAS,ESTOQUE,PCP,VISUALIZACAO,PERSONALIZADO',
            'password' => 'nullable|string|min:6',
            'permissao_fechamento_op' => 'nullable|boolean',
            'permissao_painel_pcp' => 'nullable|boolean',
            'permissao_painel_pcp_edicao' => 'nullable|boolean',
            'permissao_painel_montagem' => 'nullable|boolean',
            'permissao_compras_edicao' => 'nullable|boolean',
            'permissao_estoque_edicao' => 'nullable|boolean',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['permissao_fechamento_op'] = $request->has('permissao_fechamento_op');
        $validated['permissao_painel_pcp'] = $request->has('permissao_painel_pcp');
        $validated['permissao_painel_pcp_edicao'] = $request->has('permissao_painel_pcp_edicao');
        $validated['permissao_painel_montagem'] = $request->has('permissao_painel_montagem');
        $validated['permissao_compras_edicao'] = $request->has('permissao_compras_edicao');
        $validated['permissao_estoque_edicao'] = $request->has('permissao_estoque_edicao');

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'Usuário atualizado com sucesso!');
    }

    /**
     * Remove um usuário
     */
    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return redirect()->route('users.index')->with('error', 'Você não pode excluir a sua própria conta!');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuário excluído com sucesso!');
    }
}
