<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PvMetadado extends Model
{
    use HasFactory;

    protected $table = 'pv_metadados';

    protected $fillable = [
        'pedido',
        'info',
        'observacao',
        'status_pv',
        'fabrica',
        'marca',
        'qtd',
        'valor_bruto',
        'time_prod',
        'data_emissao',
        'data_contratual',
        'data_pa_pg',
        'data_pronto',
        'data_pronto_real',
        'data_boom',
        'data_liberacao_estoque',
        'updated_by',
    ];
}
