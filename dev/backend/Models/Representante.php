<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Representante extends Model
{
    protected $table = 'REPRESENTANTE';
    protected $primaryKey = 'ID_CONTATO_BLING';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['ID_CONTATO_BLING', 'ID_VENDEDOR', 'NOME_GRUPO_WHATSAPP', 'EXIBIR'];

    public function contatoExterno()
    {
        return $this->belongsTo(ContatoExterno::class, 'ID_CONTATO_BLING', 'ID_CONTATO_BLING');
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'ID_REPRESENTANTE', 'ID_CONTATO_BLING');
    }

    public function contatosFinanceiros()
    {
        return $this->belongsToMany(
            ContatoFinanceiro::class,
            'VINCULO_CONTATO_REPRESENTANTE',
            'ID_REPRESENTANTE',
            'ID_CONTATO',
            'ID_CONTATO_BLING',
            'ID_CONTATO'
        );
    }
}
