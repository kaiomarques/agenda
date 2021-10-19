<?php

namespace App\Http\Controllers;

use App\Models\Municipio;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Yajra\Datatables\Datatables;

class MunicipiosController extends Controller
{
    public function index()
    {
       return view('municipios.index');
    }

    public function anyData()
    {
        return Datatables::of(Municipio::select('*'))->make(true);
    }

}
