<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cobranca extends Model
{
    protected $table = 'COBRANCA';
    protected $primaryKey = 'ID_COBRANCA';
    public $timestamps = false;

    protected $fillable = [
        'DATA_INICIO', 'DATA_FIM', 'STATUS_ATENDIMENTO', 
        'ID_COLABORADOR', 'ID_CONTATO', 'ID_REPRESENTANTE'
    ];

    public function colaborador()
    {
        return $this->belongsTo(Colaborador::class, 'ID_COLABORADOR', 'ID_COLABORADOR');
    }

    public function contatoFinanceiro()
    {
        return $this->belongsTo(ContatoFinanceiro::class, 'ID_CONTATO', 'ID_CONTATO');
    }

    public function representante()
    {
        return $this->belongsTo(Representante::class, 'ID_REPRESENTANTE', 'ID_CONTATO_BLING');
    }

    public function clientes()
    {
        return $this->belongsToMany(
            Cliente::class,
            'vinculo_cobranca_cliente',
            'ID_COBRANCA',
            'ID_CONTATO_BLING',
            'ID_COBRANCA',
            'ID_CONTATO_BLING'
        );
    }

    public function pedidos()
    {
        return $this->belongsToMany(
            Pedido::class,
            'vinculo_cobranca_pedido',
            'ID_COBRANCA',
            'ID_PEDIDO',
            'ID_COBRANCA',
            'ID_PEDIDO'
        );
    }
}
