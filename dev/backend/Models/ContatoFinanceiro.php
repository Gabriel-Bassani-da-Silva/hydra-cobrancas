<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContatoFinanceiro extends Model
{
    protected $table = 'CONTATO_FINANCEIRO';
    protected $primaryKey = 'ID_CONTATO';
    public $timestamps = false;

    protected $fillable = ['NOME_CONTATO', 'ID_TEL'];

    public function telefone()
    {
        return $this->belongsTo(Telefone::class, 'ID_TEL', 'ID_TEL');
    }

    public function clientes()
    {
        return $this->belongsToMany(
            Cliente::class,
            'vinculo_contato_cliente',
            'ID_CONTATO',
            'ID_CLIENTE',
            'ID_CONTATO',
            'ID_CONTATO_BLING'
        );
    }

    public function representantes()
    {
        return $this->belongsToMany(
            Representante::class,
            'vinculo_contato_representante',
            'ID_CONTATO',
            'ID_REPRESENTANTE',
            'ID_CONTATO',
            'ID_CONTATO_BLING'
        );
    }
}
