<html>
<head>

</head>
<body>
    <h1><font color="#B22222"><b>B</b></font>ravo Plataforma – Fiscal</h1>
    <div>Envio automático dos arquivos referente às obrigações fiscais em {{ $data['data'] }}.</div>
    <div>
    @foreach($data['linkDownload'] as $key => $el)
    <?php $ultima = basename($key); ?>
        <a href="{{ $el['link'] }}"><?php echo $ultima; ?> - <?php echo $el['texto']; ?></a><br><br>
    @endforeach
    </div>

    <br/>
    <div>Não responder, isto é uma mensagem automática.</div><br>
    <div>Atenciosamente</div>
    <div>Bravo – BPO</div>
    <hr/>
    <br/>
    <a href="https://bravoplataforma.bravobpo.com.br">Link Bravo Plataforma</a>
    <hr/>
</body>
</html>