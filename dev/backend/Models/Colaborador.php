<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Colaborador extends Authenticatable
{
    protected $table = 'COLABORADOR';
    protected $primaryKey = 'ID_COLABORADOR';
    public $timestamps = false;
    
    protected $fillable = [
        'NOME_COLABORADOR',
        'SENHA',
    ];

    protected $hidden = [
        'SENHA',
    ];

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->SENHA;
    }
}
