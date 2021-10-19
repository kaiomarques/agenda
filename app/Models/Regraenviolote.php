<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Regraenviolote extends Model
{
    protected $table = "regraenviolote";
    protected $morphClass = 'MorphRegraLote';
    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'id_empresa',
        'id_tributo',
        'email_1',
        'email_2',
        'email_3',
        'regra_geral',
        'envioaprovacao'
    ];

    /**
        * Get the tributo that owns the regra.
    */

    public function filiais(){
        return $this->hasMany(Regraenviolotefilial::class, 'id_regraenviolote');
    }

    public function tributo()
    {
        return $this->belongsTo('App\Models\Tributo');
    }

    /**
     * The estabelecimentos that are inactive for the regra.
     */
    public function estabelecimentos()
    {
        return $this->belongsToMany('App\Models\Estabelecimento');
    }

    public function setUpdatedAt($value)
    {
       //Do-nothing
    }

    public function getUpdatedAtColumn()
    {
        //Do-nothing
    }

    public function setCreatedAt($value)
    {
        //Do nothing
    }

}
