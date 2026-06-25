<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Telefone extends Model
{
    protected $table = 'TEL';
    protected $primaryKey = 'ID_TEL';
    public $timestamps = false;

    protected $fillable = ['NUM_TEL', 'CONFIRMADO', 'ORIGEM'];

    public function contatosFinanceiros()
    {
        return $this->hasMany(ContatoFinanceiro::class, 'ID_TEL', 'ID_TEL');
    }

    public function contatosExternos()
    {
        return $this->belongsToMany(
            ContatoExterno::class,
            'contato_tel',
            'ID_TEL',
            'ID_CONTATO_BLING',
            'ID_TEL',
            'ID_CONTATO_BLING'
        );
    }
}
