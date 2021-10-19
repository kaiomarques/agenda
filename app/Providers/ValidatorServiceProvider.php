<?php

namespace App\Providers;

use App\Models\Empresa;
use App\Models\Estabelecimento;
use Illuminate\Support\ServiceProvider;

class ValidatorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['validator']->extend('valida_regra', function($attribute, $value){
            $is_valid = false;
            if (substr($value,0,2)=='AS') {
                $is_valid = (preg_match('/^\d{4}/', substr($value, -4, 4)) && (int)substr($value, -2, 2) <= 12 && (int)substr($value, -4, 2) <= 31);

            } else if (substr($value,0,2)=='MS' || substr($value,0,2)=='QS') {
                $is_valid = (preg_match('/^[+,-]{1}\d{2}/', substr($value, -3, 3)) && (int)substr($value, -2, 2) <= 31);
            } else {
                $is_valid = preg_match('/^\d{2}/', substr($value, -2, 2));
            }

            return $is_valid;
        });

        $this->app['validator']->extend('valida_cnpj_raiz', function($attribute, $value){
            return substr($value,-7,4)=='0001';
        });

        $this->app['validator']->extend('valida_envio_empresa', function($attribute, $value){
            if ($value == 0) {
                return false;
            }
            return true;
        });

        $this->app['validator']->extend('valida_cnpj_estab', function($attribute, $value, $parameters){

            if (isset($parameters[0])) {
                $matriz = preg_replace("/[^0-9]/","",$parameters[0]);
                $value = preg_replace("/[^0-9]/","",$value);
                if ($matriz==$value) {
                    return false;  //Estabelecimento não pode ter o mesmo cnpj da matriz
                } else {
                    return substr($value, 0, 8) == substr($matriz, 0, 8);
                }

            } else {
                return false;
            }
        });

        $this->app['validator']->extend('formato_valido_periodoapuracao', function($attribute, $value, $parameters){

            $value = explode('/', $value);
            if (!checkdate($value[0], '01', $value[1])) {
                return false;
            }
            return true;
        });

        $this->app['validator']->extend('multiplo_cinco', function($attribute, $value, $parameters){

            $resto = $value % 5;
            if ($resto > 0) {
                return false;
            }

            return true;
        });

        $this->app['validator']->extend('valida_cnpj', function($attribute, $value){
            return $this->_valida_cnpj ( $value );
        });

        $this->app['validator']->extend('valida_cnpj_unique', function($attribute, $value){
            $value = preg_replace("/[^0-9]/","",$value);
            if (Estabelecimento::where('cnpj', '=', $value)->exists()) {
                return false;
            } else {
                return true;
            }
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /* Custom validation function */
    private function _valida_cnpj ( $param ) {
        $cnpj = preg_replace("/[^0-9]/","",$param);  //Eliminate the CNPJ MASK - Only numbers will be written on DB

        //CNPJ TEST
        if ($cnpj == '00000000000100')
            return true;

        // Valida tamanho
        if (strlen($cnpj) != 14)
            return false;

        // Valida primeiro dígito verificador
        for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++)
        {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }

        $resto = $soma % 11;

        if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto))
            return false;

        // Valida segundo dígito verificador
        for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++)
        {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }

        $resto = $soma % 11;

        return $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);
    }
}
