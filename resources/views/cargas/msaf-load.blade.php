@extends('...layouts.master')

@section('content')

<h1>Cargas</h1>
<div class="row">
    <div class="col-md-6">
        <p class="lead">Status de carga para todos os estabelecimentos ativos.</p>
    </div>
    <div title="" class="col-lg-2">
        <select name="slt_cargas" id="slt_cargas" class="form-control">
            <option value="2" <?php if($switch == 2) echo "selected"; ?>>Todos</option>
            <option value="1" <?php if($switch == 1) echo "selected"; ?>>Carregados</option>
            <option value="0" <?php if($switch == 0) echo "selected"; ?>>Não Carregados</option>
            <option value="3" <?php if($switch == 3) echo "selected"; ?>>Carregados Entradas</option>
            <option value="4" <?php if($switch == 4) echo "selected"; ?>>Não Carregados Entradas</option>
            <option value="5" <?php if($switch == 5) echo "selected"; ?>>Carregados Saídas</option>
            <option value="6" <?php if($switch == 6) echo "selected"; ?>>Não Carregados Saídas</option>
        </select>
    </div>
    <div class="col-md-1">
        {!! Form::open([
                    'route' => 'cargas'
        ]) !!}
        {!! Form::hidden('switch_val', $switch, ['class' => 'form-control']) !!}
        {!! Form::button('<i class="fa fa-refresh"></i> Atualizar', array('id' => 'atualiza_btn', 'class'=>'btn btn-default', 'type'=>'submit')) !!}
        {!! Form::close() !!}
    </div>
	@if ( Auth::user()->hasRole('admin') || Auth::user()->hasRole('msaf') || Auth::user()->hasRole('owner'))
    <div class="col-md-2">
        {!! Form::open([
            'route' => 'cargas.atualizar_entrada'
        ]) !!}
        {!! Form::button('<i class="fa fa-repeat"></i> Atualizar Todas Entradas', array('id' => 'atualizar_entrada_btn', 'class'=>'btn btn-default', 'type'=>'submit')) !!}
        {!! Form::close() !!}
    </div>
    <div class="col-md-1">
        {!! Form::open([
            'route' => 'cargas.reset'
        ]) !!}
        {!! Form::button('<i class="fa fa-repeat"></i> Reset', array('id' => 'reset_btn', 'class'=>'btn btn-default', 'type'=>'submit')) !!}
        {!! Form::close() !!}
    </div>
    @endif
</div>
<hr>
<table class="table table-bordered display" id="estabelecimentos-table">
    <thead>
    <tr>
        <th>CÓDIGO</th>
        <th>CNPJ</th>
        <th>UF</th>
        <th>CARGA ENTRADA</th>
        <th>CARGA SAÍDA</th>
        <th>ATUALIZAÇÃO ENTRADA</th>
        <th>ATUALIZAÇÃO SAÍDA</th>
        <th></th>
        <th></th>
    </tr>
    </thead>
</table>
<script>

$(function() {

    setInterval(function(){ $( '#atualiza_btn' ).click() }, 60000);

    $.fn.bootstrapSwitch.defaults.onText = 'CARREGADOS';
    $.fn.bootstrapSwitch.defaults.offText = 'TODOS';
    $("[name='switch-checkbox']").bootstrapSwitch();

    $('input[name="switch-checkbox"]').on('switchChange.bootstrapSwitch', function(event, state) {

      $('input[name="switch_val"]').val(state?1:0);
      $("body").css("cursor", "progress");
      $( "#atualiza_btn" ).click();
    });

    $( "#slt_cargas" ).change(function() {
      $('input[name="switch_val"]').val($(this).val());
      $( "#atualiza_btn" ).click();
    });    
    

    // Select the submit buttons of forms with data-confirm attribute
    var reset_button =$( '#reset_btn' );

    // On click of one of these submit buttons
    reset_button.on('click', function (e) {

        // Prevent the form to be submitted
        e.preventDefault();

        var button = $(this); // Get the button
        var form = button.closest('form'); // Get the related form
        var msg = 'Quer realmente zerar as informações de carga?'; // Get the confirm message

        if(confirm(msg)) form.submit(); // If the user confirm, submit the form
        $("body").css("cursor", "progress");

    });

    // Select the submit buttons of forms with data-confirm attribute
    var reset_button =$( '#atualizar_entrada_btn' );

    // On click of one of these submit buttons
    reset_button.on('click', function (e) {

        // Prevent the form to be submitted
        e.preventDefault();

        var button = $(this); // Get the button
        var form = button.closest('form'); // Get the related form
        var msg = 'Quer realmente atualizar as entradas de carga?'; // Get the confirm message

        if(confirm(msg)) form.submit(); // If the user confirm, submit the form
        $("body").css("cursor", "progress");

    });

    $( "#atualiza_btn" ).click(function() {
        $("body").css("cursor", "progress");
    });

    $('#estabelecimentos-table').DataTable({
        processing: true,
        serverSide: true,
        stateSave: true,
        ajax: {
                url: "{!! route('cargas.data') !!}",
                data: function (d) {
                    d.ativo = $("[name='switch_val']").val();
                }
            },
        columnDefs: [
          { "width": "80px", "targets": 3 },
          { "width": "80px", "targets": 4 },
          { "width": "180px", "targets": 5 },
          { "width": "180px", "targets": 6 },
          { "width": "12%", "targets": 7, "visible":false, "title": "Carga Entrada"},
          { "width": "12%", "targets": 8, "visible":false, "title": "Carga Saída"}
          ],
        columns: [
            {data: 'codigo', name: 'codigo'},
            {data: 'cnpj', name: 'cnpj',render: function ( data ) {
                                                      return printMaskCnpj(data);
                                                    }},
            {data: 'municipio.uf', name: 'municipio.uf', orderable: false},
            {data: 'id', name:'carga_entrada', searchable: false, orderable: false, render: function (data, type, row) {

                                                                  var url = '';
                                                                  @if ( Auth::user()->hasRole('msaf') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner'))
                                                                  if(row['carga_msaf_entrada']==1) {
                                                                      url += '<a href="{{ route('cargas.changeStateEntrada', array('0', ':id_estab')) }}"><img src="{{ URL::to('/') }}/assets/img/Green-icon.png" title="carga entrada efetuada" /></a>';
                                                                  } else {
                                                                      url += '<a href="{{ route('cargas.changeStateEntrada', array('1', ':id_estab')) }}"><img src="{{ URL::to('/') }}/assets/img/Red-icon.png" title="carga entrada não efetuada" /></a>';
                                                                  }
                                                                  url = url.replace(':id_estab', data);
                                                                  @else
                                                                  if(row['carga_msaf_entrada']==1) {
                                                                        url += '<img src="{{ URL::to('/') }}/assets/img/Green-icon.png" title="carga entrada efetuada" />';
                                                                    } else {
                                                                        url += '<img src="{{ URL::to('/') }}/assets/img/Red-icon.png" title="carga entrada não efetuada" />';
                                                                    }
                                                                  @endif
                                                                  return url;
            }},
            {data: 'id', name:'carga_saida', searchable: false, orderable: false, render: function (data, type, row) {

                                                                  var url = '';
                                                                  @if ( Auth::user()->hasRole('msaf') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner'))
                                                                  if(row['carga_msaf_saida']==1) {
                                                                        url += '<a href="{{ route('cargas.changeStateSaida', array('0', ':id_estab')) }}"><img src="{{ URL::to('/') }}/assets/img/Green-icon.png" title="carga saída efetuada" /></a>';
                                                                  } else {
                                                                        url += '<a href="{{ route('cargas.changeStateSaida', array('1', ':id_estab')) }}"><img src="{{ URL::to('/') }}/assets/img/Red-icon.png" title="carga saída não efetuada" /></a>';
                                                                  }
                                                                  url = url.replace(':id_estab', data);
                                                                  @else
                                                                    if(row['carga_msaf_saida']==1) {
                                                                          url += '<img src="{{ URL::to('/') }}/assets/img/Green-icon.png" title="carga saida efetuada" />';
                                                                      } else {
                                                                          url += '<img src="{{ URL::to('/') }}/assets/img/Red-icon.png" title="carga saida não efetuada" />';
                                                                      }
                                                                    @endif
                                                                  return url;
            }},
            {data: 'id' , searchable: false, orderable: false, name: 'alteracao_entrada',  render: function (data, type, row) {

                                                                  var url = '';

                                                                  if(row['Id_usuario_entrada'] > 0) {
                                                                        url += row['userEmailEntrada'] + '<br>' + mascararDate(row['Dt_alteracao_entrada']);
                                                                  } else {
                                                                        url += 'Inexistente' + '<br>' + mascararDate(row['Dt_alteracao_entrada']);
                                                                  }
                                                                  return url; 
                                                                }},
            {data: 'Dt_alteracao_saida', searchable: false, orderable: false, name: 'alteracao_saida',render: function (data, type, row) {

                                                                  var url = '';

                                                                  if(row['Id_usuario_saida'] > 0) {
                                                                        url += row['userEmailSaida'] + '<br>' + mascararDate(row['Dt_alteracao_saida']);
                                                                  } else {
                                                                        url += 'Inexistente' + '<br>' + mascararDate(row['Dt_alteracao_saida']);
                                                                  }
                                                                  return url; 
                                                                }},
            {data: 'id', name:'carga_entrada', searchable: false, orderable: false, render: function (data, type, row) {

                                                                  var url = '';
                                                                  @if ( Auth::user()->hasRole('msaf') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner'))
                                                                  if(row['carga_msaf_entrada']==1) {
                                                                      url += 'OK';
                                                                  } else {
                                                                      url += 'PENDENTE';
                                                                  }
                                                                  url = url.replace(':id_estab', data);
                                                                  @else
                                                                  if(row['carga_msaf_entrada']==1) {
                                                                        url += 'OK';
                                                                    } else {
                                                                        url += 'PENDENTE';
                                                                    }
                                                                  @endif
                                                                  return url;
            }},
            {data: 'id', name:'carga_saida', searchable: false, orderable: false, render: function (data, type, row) {

                                                                  var url = '';
                                                                  @if ( Auth::user()->hasRole('msaf') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner'))
                                                                  if(row['carga_msaf_saida']==1) {
                                                                        url += 'OK';
                                                                  } else {
                                                                        url += 'PENDENTE';
                                                                  }
                                                                  url = url.replace(':id_estab', data);
                                                                  @else
                                                                    if(row['carga_msaf_saida']==1) {
                                                                          url += 'OK';
                                                                      } else {
                                                                          url += 'PENDENTE';
                                                                      }
                                                                    @endif
                                                                  return url;
            }},
        ],
         language: {
                                    // "searchPlaceholder": "ID, P.A. ou descrição",
                                    "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
         },
         lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
         dom: 'l<"centerBtn"B>frtip',
         buttons: [
              {
                extend: 'copyHtml5',
                exportOptions: {
                   columns: [ 0, 1, 2, 7, 8, 5, 6]
                }
              },
              {
                extend: 'excelHtml5',
                exportOptions: {
                   columns: [ 0, 1, 2, 7, 8, 5, 6]
                }
             },
         ]

    });

});

</script>

@stop
