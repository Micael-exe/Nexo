@auth
    <a class="nav-item {{ request()->routeIs('painel') ? 'active' : '' }}" href="{{ route('painel') }}">Painel</a>
    <form method="POST" action="{{ route('logout') }}" class="d-inline">@csrf<button type="submit" class="nav-item nav-logout">Sair</button></form>
@else
    <a class="nav-item {{ request()->routeIs('login', 'register') ? 'active' : '' }}" href="{{ route('login') }}">Área municipal</a>
@endauth
