<html>
<head>

</head>
<body>
    <h1><font color="#B22222"><b>B</b></font>ravo - Tax Calendar</h1>
    <div>Prezado usuario,</div>
    <p>
        Para mudar a senha, clica aqui: <a href="{{ $link = url('password/reset', $data['token']).'?email='.urlencode($data['mail']) }}"> click! </a>
    </p>
    <div>Bom trabalho!</div>
    <br/>
    <div>At.te</div>
    <div>Bravo - Tax Calendar Time</div>
</body>
</html>