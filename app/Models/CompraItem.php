<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompraItem extends Model
{
    use HasFactory;

    protected $table = 'compras_items';

    protected $fillable = [
        'estoque_item_id',
        'quantidade_adicional',
        'pedido_compra',
        'codigo_fornecedor',
        'valor_unitario',
        'ipi',
        'data_pc',
        'data_pagamento',
        'frete',
        'solicitacao_compra',
        'valor_total',
        'condicao_pagamento',
        'status_pagamento',
        'updated_by',
    ];

    public function estoqueItem(): BelongsTo
    {
        return $this->belongsTo(EstoqueItem::class, 'estoque_item_id');
    }
}
