<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $morphClass = 'MorphEmp';
    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'codigo',
        'cnpj',
        'razao_social',
        'endereco',
        'num_endereco',
        'insc_estadual',
        'insc_municipal',
        'cod_municipio',
        'cliente_aprova'
    ];

    /**
     * Get the municipio record associated with the empresa.
     */
    public function municipio()
    {
        return $this->belongsTo('App\Models\Municipio','cod_municipio');
    }

    /**
     * Get the estabelecimentos for this empresa.
     */
    public function estabelecimentos()
    {
        return $this->hasMany('App\Models\Estabelecimento');
    }

    /**
     * Get the tributos active for the empresa.
     */
    public function tributos()
    {
        return $this->belongsToMany('App\Models\Tributo');
    }

    /**
     * Get the users enabled to operate for the empresa.
     */
    public function users()
    {
        return $this->belongsToMany('App\Models\User');
    }
	
		public function gerazfic()
		{
				return $this->hasMany('App\Models\Gerazfic');
		}
}
