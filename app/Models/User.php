<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'permissao_fechamento_op',
        'permissao_painel_pcp',
        'permissao_painel_pcp_edicao',
        'permissao_painel_montagem',
        'permissao_compras_edicao',
        'permissao_estoque_edicao',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissao_fechamento_op' => 'boolean',
            'permissao_painel_pcp' => 'boolean',
            'permissao_painel_pcp_edicao' => 'boolean',
            'permissao_painel_montagem' => 'boolean',
            'permissao_compras_edicao' => 'boolean',
            'permissao_estoque_edicao' => 'boolean',
        ];
    }

    public function canCloseOp(): bool
    {
        return $this->role === 'ADMIN' || (bool) $this->permissao_fechamento_op;
    }

    public function canAccessPcp(): bool
    {
        return $this->role === 'ADMIN' || (bool) $this->permissao_painel_pcp;
    }

    public function canEditPcp(): bool
    {
        return $this->role === 'ADMIN' || ((bool) $this->permissao_painel_pcp && (bool) $this->permissao_painel_pcp_edicao);
    }

    public function canAccessMontagem(): bool
    {
        return $this->role === 'ADMIN' || (bool) $this->permissao_painel_montagem;
    }

    public function canEditCompras(): bool
    {
        if ($this->role === 'ADMIN' || $this->role === 'COMPRAS') {
            return true;
        }
        return (bool) $this->permissao_compras_edicao;
    }

    public function canEditEstoque(): bool
    {
        if ($this->role === 'ADMIN' || $this->role === 'ESTOQUE') {
            return true;
        }
        return (bool) $this->permissao_estoque_edicao;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->role === 'ADMIN';
    }

    public function isCompras(): bool
    {
        return in_array($this->role, ['ADMIN', 'COMPRAS']);
    }

    public function isEstoque(): bool
    {
        return in_array($this->role, ['ADMIN', 'ESTOQUE']);
    }
}
