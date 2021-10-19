@extends('...layouts.graficos')
@section('content')

{!! Form::open([
    'route' => 'graficos'
]) !!}

<?php 
    $displayLink    = 'none';
    $displayCombo   = 'block';
    if (!empty($empresas_selecionadas)) {
        $displayLink    = 'block';
        $displayCombo   = 'none';
    }
?>
<!---
<div class="graficos">
   <div class="col-md-6" id="link0">
                
   </div>
   <div class="col-md-6" id="link1">
                
   </div>
   <div class="col-md-6" id="link2">
                
   </div>
   <div class="col-md-6" id="link3">
                
   </div>
    
</div>
-->

    <iframe src="" id="frameGrafico" width="100%" scrolling="no" frameborder="0" style="border:0; background: #eceff3; min-height: 1000px;">
    </iframe>
    
</div>

<script type="text/javascript">
  
  var arrayFromPHP = <?php echo json_encode($empresas_selecionadas) ?>;
  
  var chamadas = 0;   
  count = 0;
  setInterval(function(){
      $.each(arrayFromPHP, function (i, elem) {
         if (count == 0) {
            AjaxFunctionGrafico(i, elem)
         } else {
            doTimeOut(i, elem);   
         }
         count++;
      });
    },1000);
  
  
  
  function doTimeOut (key, array) {
    chamadas++;
    setTimeout(function() {
        AjaxFunctionGrafico(key, array)
    }, chamadas*15000);
  }

  function AjaxFunctionGrafico(key, array)
  { 
    var ids = '';
    if (array[0] != undefined) {
        ids = array[0].key;
    }

    if (array[1] != undefined) {
        ids = ids+','+array[1].key;
    }

    if (array[2] != undefined) {
        ids = ids+','+array[2].key;
    }

    if (array[3] != undefined) {
        ids = ids+','+array[3].key;
    }
  
    var iframe = document.getElementById('frameGrafico');
    iframe.src = 'dashboard?layout=entregometro&empresas='+ids+'&cor=yellow&emp_id=1';

     
  }

</script>
@stop
 