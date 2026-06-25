<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContatoExterno extends Model
{
    protected $table = 'CONTATO_EXTERNO';
    protected $primaryKey = 'ID_CONTATO_BLING';
    public $incrementing = false;
    public $timestamps = false;
    
    protected $fillable = [
        'ID_CONTATO_BLING',
        'NOME_CONTATO',
        'NUMERO_DOCUMENTO',
    ];
}
