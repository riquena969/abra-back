<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova inscrição de vendedor</title>
    <style>
        #email-body {
            max-width: 500px;
            display: block;
            margin: auto;
            font-family: "Google Sans", Roboto, RobotoDraft, Helvetica, Arial, sans-serif;
            color: #1f1f1f;
        }

        h1 {
            font-size: 20px;
            margin-bottom: 0;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
        }

        h2 {
            font-size: 18px;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        p {
            margin-bottom: 0;
            margin-top: 8px;
        }

        footer {
            margin-top: 16px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div id="email-body">
        <h1>Novo contato via Whatsapp</h1>
        <h2>Dados do contato:</h2>
        <p><strong>Nome:</strong> {{ $customer->name }}</p>
        <p><strong>E-mail:</strong> {{ $customer->email }}</p>
        <p><strong>Telefone:</strong> {{ $customer->phone }}</p>
        <p><strong>Formato do curso:</strong> {{ $customer->format }}</p>
        <p><strong>Curso:</strong> {{ $customer->course }}</p>

        <footer>
            <p>
                <small>E-mail enviado em <strong>{{ date('d/m/Y') }}</strong> por <strong>{{$_SERVER['REMOTE_ADDR']}}</strong></small>
            </p>
        </footer>
    </div>
</body>

</html>