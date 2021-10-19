<html>
<head>

</head>
<body>
    <h1><font color="#B22222"><b>B</b></font>ravo Plataforma – Fiscal</h1>
    <div>Prezado Analista,

                segue a lista das atividades em aberto cujo vencimento está proximo ou com prazo vencido:</div>
    <p>
      @foreach ($data['messageLines'] as $messageLine)
        {{ $messageLine }}<br>
      @endforeach
    </p>
    <div>Bom trabalho!</div>
    <br/>
    <div>Atenciosamente</div>
    <div>Bravo – BPO</div>
    <hr/>
    <br/>
    <a href="https://bravoplataforma.bravobpo.com.br">Link Bravo Plataforma</a>
    <hr/>
</body>
</html>