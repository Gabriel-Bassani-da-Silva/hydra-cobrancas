<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $table = 'PEDIDO';
    protected $primaryKey = 'ID_PEDIDO';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'ID_PEDIDO', 'NUM_PEDIDO', 'TOTAL_PEDIDO', 'DATA_VENCIMENTO', 
        'VALOR_PAGO_BLING', 'SITUACAO_PEDIDO', 'ID_REPRESENTANTE', 
        'ID_CLIENTE', 'ID_FORMA_PAGAMENTO', 'EXIBIR'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'ID_CLIENTE', 'ID_CONTATO_BLING');
    }

    public function representante()
    {
        return $this->belongsTo(Representante::class, 'ID_REPRESENTANTE', 'ID_CONTATO_BLING');
    }

    public function cobrancas()
    {
        return $this->belongsToMany(
            Cobranca::class,
            'vinculo_cobranca_pedido',
            'ID_PEDIDO',
            'ID_COBRANCA',
            'ID_PEDIDO',
            'ID_COBRANCA'
        );
    }
}
