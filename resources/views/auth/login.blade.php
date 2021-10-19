<?php
header('location: '. config('app.url_plataforma')."/login");
exit();
?>
<!doctype html>
<html lang="pt-br">
@include('layouts.head')
<body>
    <div class="background-login" style="background-image: url('{{ URL::to('/') }}/assets/img/background-login.png');">
        <div class="logo-login">
            <img src="{{ URL::to('/') }}/assets/img/logo-login.png">
            <h3>Tax calendar</h3>
        </div>
        <div class="box-login">
                    <h3>LOGIN</h3>
                    <form class="form-horizontal" role="form" method="POST" action="{{ url('/login') }}">
                        {!! csrf_field() !!}

                        <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                            <div class="col-md-12">
                                <p>Informe seu e-mail</p>
                                <input type="email" class="form-control" placeholder="EMAIL" name="email" value="{{ old('email') }}">
                                @if ($errors->has('email'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                            <div class="col-md-12">
                                <p>Senha</p>
                                <input type="password" class="form-control" name="password" placeholder="SENHA">
                                @if ($errors->has('password'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-md-12">
                                <button type="submit" class="button-login">
                                     ENTRAR
                                </button>
                            </div>
                        </div>                      
                    </form>
        </div>
    </div>
</body>
</html>