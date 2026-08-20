<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EstoqueItem extends Model
{
    use HasFactory;

    protected $table = 'estoque_items';

    protected $fillable = [
        'codigo_produto',
        'descricao',
        'descricao_longa',
        'produto_pai',
        'op',
        'pedido',
        'cliente_obs',
        'quantidade',
        'quantidade_estoque',
        'status',
        'observacao_estoque',
    ];

    protected $appends = [
        'quantidade_comprar',
    ];

    /**
     * Accessor: Quantidade a Comprar = MAX(0, Quantidade OP - Quantidade Estoque)
     */
    public function getQuantidadeComprarAttribute(): float
    {
        $qtdOp = floatval($this->quantidade);
        $qtdEstoque = floatval($this->quantidade_estoque);
        return max(0, $qtdOp - $qtdEstoque);
    }

    public function compraItem(): HasOne
    {
        return $this->hasOne(CompraItem::class, 'estoque_item_id');
    }
}
