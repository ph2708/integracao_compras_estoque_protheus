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
        'status_pv',
        'fabrica',
        'marca',
    ];
}
