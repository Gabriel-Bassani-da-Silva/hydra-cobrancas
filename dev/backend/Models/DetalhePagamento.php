<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalhePagamento extends Model
{
    protected $table = 'DETALHE_PAGAMENTO';
    protected $primaryKey = 'ID_DETALHE';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'VALOR_PAGO_PEDIDO', 
        'ID_PEDIDO', 
        'ID_REGISTRO'
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'ID_PEDIDO', 'ID_PEDIDO');
    }
}
