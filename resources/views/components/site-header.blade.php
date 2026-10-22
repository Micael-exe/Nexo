<div class="demo-strip"><span class="demo-dot"></span><strong>Ambiente de demonstração.</strong> Use somente informações fictícias.</div>
<header class="site-header" x-data="{ menuOpen: false }" x-on:keydown.escape.window="menuOpen = false">
    <div class="container">
        <div class="header-inner">
            <a href="{{ route('home') }}" class="brand" aria-label="Canal em Dia — página inicial">
                <span class="brand-mark"><x-icon name="water" /></span>
                <span class="brand-name">Canal em Dia<small>Cuidado que conecta</small></span>
            </a>
            <nav class="desktop-nav" aria-label="Navegação principal">
                <a href="{{ route('home') }}" class="nav-item {{ request()->routeIs('home') ? 'active' : '' }}" @if(request()->routeIs('home')) aria-current="page" @endif>Início</a>
                <button class="nav-pending" type="button" disabled aria-label="Acompanhar relato — em breve">Acompanhar <span class="pending-pill">Em breve</span></button>
                <button class="nav-pending" type="button" disabled aria-label="Canais acompanhados — em breve">Canais <span class="pending-pill">Em breve</span></button>
            </nav>
            <div class="header-actions">
                <div class="staff-nav"><x-staff-nav /></div>
                <a href="{{ route('relatos.create') }}" class="button button-forest button-small" @if(request()->routeIs('relatos.create')) aria-current="page" @endif>Novo relato <x-icon name="arrow-up-right" /></a>
                <button class="mobile-toggle" type="button" x-on:click="menuOpen = !menuOpen" x-bind:aria-expanded="menuOpen" aria-controls="mobile-navigation" aria-label="Abrir ou fechar menu">
                    <span x-show="!menuOpen"><x-icon name="menu" /></span><span x-show="menuOpen" x-cloak><x-icon name="close" /></span>
                </button>
            </div>
        </div>
        <nav id="mobile-navigation" class="mobile-nav d-lg-none" aria-label="Navegação principal no celular" x-show="menuOpen" x-cloak>
            <a class="nav-item" href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>Início</a>
            <button class="nav-pending" type="button" disabled>Acompanhar relato <span class="pending-pill">Em breve</span></button>
            <button class="nav-pending" type="button" disabled>Canais acompanhados <span class="pending-pill">Em breve</span></button>
            <x-staff-nav />
            <a class="button button-forest" href="{{ route('relatos.create') }}">Novo relato <x-icon name="arrow-right" /></a>
        </nav>
    </div>
</header>
