<x-layouts.app>
    <div class="hero-shell">
        <section class="hero" aria-labelledby="hero-heading">
            <div class="container">
                <div class="hero-grid">
                    <div class="hero-copy">
                        <div class="eyebrow">Cidade, natureza e comunidade</div>
                        <h1 id="hero-heading">Cuidar dos canais é cuidar de <em>Paulista.</em></h1>
                        <p class="hero-description">Um espaço para aproximar moradores do cuidado com os canais. Comece com um relato: o que você observou pode ajudar a entender o que precisa de atenção.</p>
                        <div class="hero-actions">
                            <a class="button button-lime" href="{{ route('relatos.create') }}">Registrar um relato <x-icon name="arrow-up-right" /></a>
                            <a class="text-link" href="#como-funciona">Conheça a proposta <x-icon name="arrow-right" /></a>
                        </div>
                        <p class="hero-note"><x-icon name="shield" /> Demonstração do projeto. Envie apenas dados fictícios.</p>
                    </div>
                    <div class="hero-art">
                        <img src="{{ asset('images/canal-paulista.svg') }}" width="760" height="720" alt="Ilustração de um canal cercado por vegetação, casas e uma ponte, representando a conexão entre a cidade e a natureza.">
                        <div class="art-label art-label-one" aria-hidden="true"><span class="label-icon"><x-icon name="leaf" /></span><span><strong>Cuidado com o território</strong><small>Um trecho de cada vez.</small></span></div>
                        <div class="art-label art-label-two" aria-hidden="true"><span class="label-icon"><x-icon name="pin" /></span><span><strong>A participação começa aqui</strong><small>Observar. Informar. Conectar.</small></span></div>
                        <span class="art-caption">ILUSTRAÇÃO DO PROJETO</span>
                    </div>
                </div>
                <div class="hero-bottom" aria-label="Visão geral da proposta">
                    <span class="hero-bottom-intro">Pequenas observações.<br>Um cuidado compartilhado.</span>
                    <div class="hero-step"><span>01</span> Relato com localização</div>
                    <div class="hero-step"><span>02</span> Verificação do problema</div>
                    <div class="hero-step"><span>03</span> Histórico por trecho</div>
                    <span class="last-note">Uma proposta para Paulista · PE</span>
                </div>
            </div>
        </section>
    </div>
    <section id="como-funciona" class="section-space" aria-labelledby="how-heading">
        <div class="container">
            <div class="how-grid">
                <div class="how-intro">
                    <div class="eyebrow">Como funciona a proposta</div>
                    <h2 id="how-heading" class="section-heading">O cuidado começa<br>com <em>um olhar atento.</em></h2>
                    <p class="section-description">Quem vive a cidade conhece seus caminhos. A proposta do Canal em Dia é conectar essa percepção ao registro e ao acompanhamento da manutenção.</p>
                    <a href="{{ route('relatos.create') }}" class="text-link">Experimente fazer um relato <x-icon name="arrow-right" /></a>
                </div>
                <div>
                    <div class="how-cards">
                        <article class="how-card"><span class="card-number">01</span><span class="card-icon"><x-icon name="pin" /></span><h3>Observe<br>e informe.</h3><p>Descreva o problema e indique o endereço do trecho. Fotos e GPS são opcionais.</p></article>
                        <article class="how-card"><span class="card-number">02</span><span class="card-icon"><x-icon name="calendar" /></span><h3>Conecte<br>as informações.</h3><p>No fluxo proposto, a equipe verifica o relato e organiza as inspeções necessárias.</p></article>
                        <article class="how-card"><span class="card-number">03</span><span class="card-icon"><x-icon name="water" /></span><h3>Construa<br>um histórico.</h3><p>A proposta reúne intervenções e novas ocorrências para apoiar a avaliação de cada trecho.</p></article>
                    </div>
                    <div class="scope-note"><x-icon name="info" /><span>Nesta versão, você pode conhecer a proposta e registrar relatos demonstrativos. A área municipal permite ao órgão responsável ver e atualizar os relatos. Acompanhamento e canais serão desenvolvidos pela equipe.</span></div>
                </div>
            </div>
        </div>
    </section>
    <section class="pre-footer" aria-labelledby="participation-heading">
        <div class="container"><div class="participation-banner">
            <div><h2 id="participation-heading">Sua observação pode ser<br>o começo do cuidado.</h2><p>Experimente o formulário e conheça como um relato pode reunir o local, a situação observada e as informações que ajudam a entender um problema.</p></div>
            <a class="button button-forest" href="{{ route('relatos.create') }}">Começar um relato <x-icon name="arrow-up-right" /></a>
        </div></div>
    </section>
</x-layouts.app>
