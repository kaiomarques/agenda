<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Regraenviolotefilial extends Model
{
    protected $table = "regraenviolotefilial";
    const UPDATED_AT = null;
    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'id_regraenviolote',
        'id_estabelecimento'
    ];

    /**
        * Get the tributo that owns the regra.
    */
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
