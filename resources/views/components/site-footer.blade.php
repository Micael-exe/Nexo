<footer class="site-footer">
    <div class="container">
        <div class="footer-top">
            <div class="footer-brand">
                <a href="{{ route('home') }}" class="brand" aria-label="Canal em Dia — voltar ao início"><span class="brand-mark"><x-icon name="water" /></span><span class="brand-name">Canal em Dia<small>Cuidado que conecta</small></span></a>
                <p>Uma proposta para aproximar comunidade, cuidado ambiental e manutenção dos canais.</p>
            </div>
            <nav class="footer-links" aria-label="Navegação do rodapé">
                <a href="{{ route('home') }}">Início</a>
                <a href="{{ route('relatos.create') }}">Novo relato</a>
                <a href="{{ route('relatos.track') }}">Acompanhar</a>
                <a href="{{ route('canais.index') }}">Canais</a>
                <button class="nav-pending" type="button" disabled>Área municipal <span class="pending-pill">Em breve</span></button>
            </nav>
        </div>
        <div class="footer-bottom">
            <p>Protótipo independente para o ideathon. Não é um serviço oficial nem um canal de atendimento real.</p>
            <span class="footer-local"><x-icon name="pin" /> Paulista, Pernambuco</span>
        </div>
    </div>
</footer>
