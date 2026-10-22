@props(['title' => 'O cuidado com Paulista começa com você'])
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#012624">
    <meta name="description" content="Canal em Dia: protótipo para participação da comunidade e acompanhamento da manutenção dos canais de Paulista, Pernambuco.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · Canal em Dia</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/css/theme.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <a class="skip-link" href="#conteudo">Ir para o conteúdo</a>
    <x-site-header />
    <main id="conteudo" tabindex="-1">{{ $slot }}</main>
    <x-site-footer />
    @livewireScripts
</body>
</html>
