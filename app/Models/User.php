<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable, HasRoles;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'access_level', 'reset_senha'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The roles that belong to the user.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * The atividades that belong to the user.
     */
    public function atividades()
    {
        return $this->belongsToMany(Atividade::class);
    }

    /**
     * The comentarios that belong to the user.
     */
    public function comentarios()
    {
        return $this->belongsToMany(Comentario::class);
    }

    /**
     * Get the tributos for the user.
     */
    public function tributos()
    {
        return $this->belongsToMany(Tributo::class);
    }

    /**
     * The empresas that belong to the user.
     */
    public function empresas()
    {
        return $this->belongsToMany(Empresa::class);
    }

    /**
     * Check if user is online.
     */
    public function isOnline()
    {
        return Cache::has('user-is-online-' . $this->id);
    }
	
		public function conferenciaguias()
		{
				return $this->hasMany('App\Models\ConferenciaGuias');
		}
	
		public function liberarguias()
		{
			return $this->hasMany('App\Models\Liberarguias');
		}
}
