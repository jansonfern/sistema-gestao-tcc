<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Muitas Tentativas - 429</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style nonce="{{ $cspNonce ?? '' }}">
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #334155;
        }
        .error-container {
            text-align: center;
            padding: 2rem;
            max-width: 500px;
        }
        .error-code {
            font-size: 8rem;
            font-weight: 700;
            color: #0ea5e9;
            line-height: 1;
            margin-bottom: 1rem;
        }
        .error-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 1rem;
        }
        .error-message {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .btn-home {
            display: inline-block;
            background: #0ea5e9;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-home:hover {
            background: #0284c7;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">429</div>
        <h1 class="error-title">Muitas Tentativas</h1>
        <p class="error-message">
            Você realizou muitas requisições em pouco tempo. Aguarde alguns instantes e tente novamente.
        </p>
        <a href="{{ url('/') }}" class="btn-home">Voltar ao Início</a>
    </div>
</body>
</html>
