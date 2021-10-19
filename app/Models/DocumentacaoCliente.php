<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentacaoCliente extends Model
{
    protected $table = 'documentacaocliente';
    public $timestamps = false;
    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
		'emp_id',
		'categoria_id',
		'subcategoria_id',
        'descricao',
        'data_criacao',
        'id_user_autor',
        'data_atualizacao',
        'id_user_atualiza',
        'versao',
        'observacao',
        'arquivo'
    ];

    public function autor()
    {
        return $this->belongsTo('App\Models\User','id_user_autor');
    }

    public function userAtualiza()
    {
        return $this->belongsTo('App\Models\User','id_user_atualiza');
    }

    public function empresa()
    {
        return $this->belongsTo('App\Models\Empresa','emp_id');
	}
	
	public function documentacaoCategoria()
    {
        return $this->belongsTo('App\Models\DocumentacaoCategoria','categoria_id');
	}
	
	public function documentacaoSubcategoria()
    {
        return $this->belongsTo('App\Models\DocumentacaoSubcategoria','subcategoria_id');
    }
}
