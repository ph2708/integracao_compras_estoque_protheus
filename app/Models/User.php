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
        ];
    }

    public function canCloseOp(): bool
    {
        return $this->role === 'ADMIN' || (bool) $this->permissao_fechamento_op;
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
