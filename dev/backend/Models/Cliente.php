<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'CLIENTE';
    protected $primaryKey = 'ID_CONTATO_BLING';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['ID_CONTATO_BLING', 'EXIBIR', 'PEDRAS'];

    public function contatoExterno()
    {
        return $this->belongsTo(ContatoExterno::class, 'ID_CONTATO_BLING', 'ID_CONTATO_BLING');
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'ID_CLIENTE', 'ID_CONTATO_BLING');
    }

    public function contatosFinanceiros()
    {
        return $this->belongsToMany(
            ContatoFinanceiro::class,
            'vinculo_contato_cliente',
            'ID_CLIENTE',
            'ID_CONTATO',
            'ID_CONTATO_BLING',
            'ID_CONTATO'
        );
    }
}
