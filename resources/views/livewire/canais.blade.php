<div class="channels-page" x-data="{ connectionFailed: false }" x-on:request-failed.window="connectionFailed = true">
    <div class="hero-shell">
        <section class="hero page-hero" aria-labelledby="page-heading">
            <div class="container">
                <div class="page-hero-grid">
                    <div class="page-hero-copy">
                        <div class="eyebrow">Canais acompanhados</div>
                        <h1 id="page-heading">Canais de Paulista</h1>
                        <p class="hero-description">Veja a situação observada em cada trecho do piloto: manutenção preventiva, inspeções e intervenções.</p>
                        <p class="hero-note"><x-icon name="shield" /> Dados fictícios de demonstração. Não representam a situação real dos canais.</p>
                    </div>
                    <div class="page-hero-art" aria-hidden="true"><img src="{{ asset('images/canal-paulista.svg') }}" alt="" width="760" height="720"></div>
                </div>
            </div>
        </section>
    </div>

    <div class="container channels-body">
        <div class="demo-notice"><x-icon name="info" /><p><strong>A situação é uma observação técnica datada.</strong> Ela não garante a ausência de alagamentos e este site não substitui a Defesa Civil ou os canais oficiais de emergência.</p></div>
        <div wire:offline class="connection-notice" role="status"><x-icon name="warning" /> Você está sem conexão. A lista pode não refletir a busca mais recente.</div>
        <div x-show="connectionFailed" x-cloak class="connection-notice" role="alert"><x-icon name="warning" /> Não foi possível atualizar a lista. Verifique sua conexão e tente novamente.</div>

        <form class="toolbar" role="search" aria-label="Buscar canais" x-on:submit.prevent>
            <div>
                <label for="field-search" class="form-label">Buscar por canal, bairro ou trecho</label>
                <div class="search-field">
                    <x-icon name="search" />
                    <input id="field-search" type="search" class="form-control" wire:model.live.debounce.300ms="search" maxlength="80" placeholder="Ex.: Janga, Trecho 06…" autocomplete="off" spellcheck="false">
                </div>
            </div>
            <div>
                <label for="field-status" class="form-label">Situação</label>
                <select id="field-status" class="form-select" wire:model.live="status">
                    <option value="">Todas as situações</option>
                    @foreach($statuses as $key => $meta)<option value="{{ $key }}">{{ $meta['label'] }}</option>@endforeach
                </select>
            </div>
            @if($filtered)<button type="button" class="button button-outline button-small toolbar-clear" wire:click="clearFilters">Limpar filtros</button>@endif
        </form>

        <p class="result-count" role="status" aria-live="polite">{{ count($channels) }} de {{ $total }} {{ $total === 1 ? 'canal' : 'canais' }} {{ $filtered ? 'com estes filtros' : 'no piloto' }}.</p>

        @if(count($channels))
            <ul class="record-list" aria-label="Canais do piloto">
                @foreach($channels as $channel)
                    <li class="record-card" wire:key="canal-{{ $channel['id'] }}" x-data="{ open: false }" x-bind:class="open ? 'is-open' : ''">
                        <button type="button" class="record-row" x-on:click="open = !open" x-bind:aria-expanded="open" aria-controls="detail-{{ $channel['id'] }}">
                            <span class="record-thumb" aria-hidden="true"><x-icon name="water" /></span>
                            <span class="record-main">
                                <span class="record-kicker">{{ $channel['id'] }} · Dados fictícios</span>
                                <span class="record-title">{{ $channel['name'] }}</span>
                                <span class="record-sub">{{ $channel['section'] }} · {{ $channel['district'] }}</span>
                            </span>
                            <span class="record-meta">
                                <span class="status-badge tone-{{ $channel['status_meta']['tone'] }}"><x-icon :name="$channel['status_meta']['icon']" /> {{ $channel['status_meta']['label'] }}</span>
                                <span>Observado em {{ $channel['observed_label'] }}</span>
                            </span>
                            <span class="record-chevron" aria-hidden="true"><x-icon name="chevron" /></span>
                            <span class="visually-hidden">Mostrar ou ocultar detalhes de {{ $channel['name'] }}</span>
                        </button>
                        <div id="detail-{{ $channel['id'] }}" class="record-detail" x-show="open" x-cloak>
                            <dl class="detail-grid">
                                <div><dt>Condição observada</dt><dd>{{ $channel['condition'] }}</dd></div>
                                <div><dt>Data da observação</dt><dd>{{ $channel['observed_label'] }}</dd></div>
                                <div><dt>Última manutenção validada</dt><dd>{{ $channel['maintenance_label'] }}</dd></div>
                                <div><dt>Próxima inspeção</dt><dd>{{ $channel['next_label'] }}</dd></div>
                                <div><dt>Extensão do trecho</dt><dd>{{ number_format($channel['length_m'], 0, ',', '.') }} m</dd></div>
                            </dl>
                            <p class="field-help mb-0">{{ $channel['status_meta']['label'] }}: {{ $channel['status_meta']['meaning'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="empty-state"><x-icon name="search" /><p>Nenhum canal encontrado com esses filtros.</p><button type="button" class="button button-outline button-small" wire:click="clearFilters">Limpar filtros</button></div>
        @endif

        <div class="action-band">
            <span class="action-band-icon" aria-hidden="true"><x-icon name="map" /></span>
            <div><h2>Visualize todos os canais no mapa</h2><p>O mapa público ainda não está disponível. Enquanto isso, esta lista traz a mesma informação de cada trecho.</p></div>
            <div class="action-band-actions"><button class="button button-outline button-small" type="button" disabled aria-label="Ver mapa — em breve">Ver mapa <span class="pending-pill">Em breve</span></button></div>
        </div>

        <section class="legend-card" aria-labelledby="legend-heading">
            <h2 id="legend-heading">Como ler as situações</h2>
            <ul>@foreach($statuses as $meta)<li><span class="status-badge tone-{{ $meta['tone'] }}"><x-icon :name="$meta['icon']" /> {{ $meta['label'] }}</span><span>{{ $meta['meaning'] }}</span></li>@endforeach</ul>
            <p class="field-help mb-0">Critérios de demonstração. Periodicidade e prazos reais dependem de aprovação do responsável municipal por trecho.</p>
        </section>
    </div>
</div>
