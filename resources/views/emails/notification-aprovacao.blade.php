<html>
<head>

</head>
<body>
    <h1><font color="#B22222"><b>B</b></font>ravo Plataforma – Fiscal</h1>

    <p>
      @foreach ($data['messageLines'] as $messageLine)
        {{ $messageLine }}<br>
      @endforeach
    </p>
    <?php if(isset($data['linkDownload']) && is_array($data['linkDownload']) ) { ?>
        <div>
            @foreach($data['linkDownload'] as $key => $el)
                <a href="{{ $el['link'] }}"><?php echo $el['texto']; ?></a><br><br>
            @endforeach
        </div>
    <?php } ?>
    <div>Obrigado pela atenção.</div>
    <br/>
    <div>Atenciosamente</div>
    <div>Bravo – BPO</div>
    <hr/>
    <br/>
    <a href="https://bravoplataforma.bravobpo.com.br">Link Bravo Plataforma</a>
</body>
</html>