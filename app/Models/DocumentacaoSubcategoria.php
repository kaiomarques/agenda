<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentacaoSubcategoria extends Model
{
    protected $table = 'documentacaosubcategoria';
	
	/**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'subcategoria_id',
		'subcategoria_descricao',
		'subcategoria_status',
		'created_at',
		'updated_at'
    ];

    /**
     * Get the documentacaoCliente for this subcategoria.
     */
    public function documentacaoCliente()
    {
        return $this->hasMany('App\Models\DocumentacaoCliente');
	}
	
	 /**
     * Get the documentacaoCategoria for this subcategoria.
     */
   public function documentacaoCategoria()
    {
        return $this->belongsTo('App\Models\DocumentacaoCategoria', 'categoria_id');
    }
}
