<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentacaoCategoria extends Model
{
	protected $table = 'documentacaocategoria';
	
	/**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'categoria_id',
        'categoria_descricao'
    ];

    /**
     * Get the documentacaoCliente for this categoria.
     */
    public function documentacaoCliente()
    {
        return $this->hasMany('App\Models\DocumentacaoCliente');
    }

}
