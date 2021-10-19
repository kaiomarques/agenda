<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tributo extends Model
{
    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'nome',
        'descricao',
        'categoria_id'
    ];

    /**
     * Get the categoria record associated with the tributo.
     */
    public function categoria()
    {
        return $this->belongsTo('App\Models\Categoria','categoria_id');
    }

    public function prioridade()
    {
        return $this->belongsTo('App\Models\OrdemApuracao','Tributo_id');
    }

    /**
     * Get the regras for the tributo.
     */
    public function regras()
    {
        return $this->hasMany('App\Models\Regra');
    }

    /**
     * Get the atividades for the tributo.
     */
    public function atividades()
    {
        return $this->hasManyThrough('App\Models\Atividade', 'App\Models\Regra');
    }

    /**
     * The users that belong to the tributo.
     */
    public function users()
    {
        return $this->belongsToMany('App\Models\User');
    }

    /**
     * The empresas that belong to the tributo.
     */
    public function empresas()
    {
        return $this->belongsToMany('App\Models\Empresa');
    }
	
		public function conferenciaguias()
		{
				return $this->hasMany('App\Models\ConferenciaGuias');
		}
	
		public function gerazfic()
		{
				return $this->hasMany('App\Models\Gerazfic');
		}
}
