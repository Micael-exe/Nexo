<div class="tracking-page" x-data="{ connectionFailed: false, sessionExpired: false, reveal: false }"
    x-on:validation-failed.window="$nextTick(() => $refs.errorSummary?.focus())"
    x-on:report-opened.window="$nextTick(() => { const t = document.getElementById('tracking-heading'); t?.focus(); t?.scrollIntoView({ behavior: 'smooth', block: 'start' }); })"
    x-on:session-cleared.window="reveal = false; connectionFailed = false; $nextTick(() => document.getElementById('page-heading')?.focus())"
    x-on:request-failed.window="connectionFailed = true; sessionExpired = $event.detail.expired">
    <div class="hero-shell">
        <section class="hero page-hero" aria-labelledby="page-heading">
            <div class="container">
                <div class="page-hero-grid">
                    <div class="page-hero-copy">
                        <div class="eyebrow">Acompanhamento privado</div>
                        <h1 id="page-heading" tabindex="-1">Acompanhar seu protocolo</h1>
                        <p class="hero-description">Informe o protocolo e a chave de acesso que aparecem no recibo do relato para consultar a situação.</p>
                        <form wire:submit="consult" x-on:submit="connectionFailed = false" class="lookup-panel" novalidate aria-label="Consultar protocolo">
                            <div>
                                <label for="field-protocol" class="form-label">Protocolo</label>
                                <input id="field-protocol" type="text" wire:model="protocol" class="form-control protocol-input @error('protocol') is-invalid @enderror" placeholder="CED-20261022-0123456789AB" maxlength="40" autocomplete="off" autocapitalize="characters" spellcheck="false" required @error('protocol') aria-describedby="error-protocol" aria-invalid="true" @enderror>
                                @error('protocol')<p class="field-error" id="error-protocol">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="field-credential" class="form-label">Chave de acesso</label>
                                <div class="input-with-action">
                                    <input id="field-credential" x-bind:type="reveal ? 'text' : 'password'" type="password" wire:model="credential" class="form-control @error('credential') is-invalid @enderror" placeholder="Cole a chave do recibo" maxlength="80" autocomplete="off" autocapitalize="off" spellcheck="false" required @error('credential') aria-describedby="error-credential" aria-invalid="true" @enderror>
                                    <button type="button" class="input-action" x-on:click="reveal = !reveal" x-bind:aria-pressed="reveal" aria-label="Mostrar a chave digitada"><span x-text="reveal ? 'Ocultar' : 'Mostrar'">Mostrar</span></button>
                                </div>
                                @error('credential')<p class="field-error" id="error-credential">{{ $message }}</p>@enderror
                            </div>
                            <button type="submit" class="button button-lime" wire:loading.attr="disabled" wire:target="consult" wire:offline.attr="disabled">
                                <span wire:loading.remove wire:target="consult">Consultar <x-icon name="arrow-right" /></span>
                                <span wire:loading wire:target="consult"><span class="loading-spinner"></span> Consultando…</span>
                            </button>
                        </form>
                        <p class="hero-note"><x-icon name="shield" /> O protocolo sozinho não dá acesso aos dados. A chave é privada: não a compartilhe.</p>
                    </div>
                    <div class="page-hero-art" aria-hidden="true"><img src="{{ asset('images/canal-paulista.svg') }}" alt="" width="760" height="720"></div>
                </div>
            </div>
        </section>
    </div>

    <div class="container tracking-body">
        <div class="demo-notice"><x-icon name="info" /><p><strong>Consulta de demonstração.</strong> Só encontra relatos enviados nesta versão local, com dados fictícios. Não consulta o atendimento municipal nem substitui a Defesa Civil ou os canais oficiais.</p></div>

        <div wire:offline class="connection-notice" role="status"><x-icon name="warning" /> Você está sem conexão. Os campos permanecem na página; conecte-se novamente antes de consultar.</div>
        <div x-show="connectionFailed" x-cloak class="connection-notice" role="alert"><x-icon name="warning" /><span x-show="!sessionExpired">Não foi possível concluir a comunicação. Verifique sua conexão e tente novamente.</span><span x-show="sessionExpired">A sessão desta página expirou. Atualize a página e consulte novamente.</span></div>
        @if($storageFailed)<div class="connection-notice" role="alert"><x-icon name="warning" /> Não foi possível carregar seus protocolos agora. Tente novamente em instantes.</div>@endif

        @if($errors->any())
            <div class="error-summary" role="alert" tabindex="-1" x-ref="errorSummary">
                <h2>Confira os dados da consulta</h2>
                <ul>@foreach($errors->messages() as $field => $messages)<li>@if($field === 'lookup'){{ $messages[0] }}@else<a href="#field-{{ $field }}">{{ $messages[0] }}</a>@endif</li>@endforeach</ul>
            </div>
        @endif

        @if($selected)
            <section class="tracking-card" aria-labelledby="tracking-heading" wire:key="detail-{{ $selected['id'] }}">
                <div class="tracking-card-top">
                    <div>
                        <div class="eyebrow">Protocolo</div>
                        <h2 id="tracking-heading" tabindex="-1">{{ $selected['protocol'] }}</h2>
                        <p>{{ $selected['category'] }} · registrado em {{ $selected['received_label'] }}</p>
                    </div>
                    <span class="status-badge tone-{{ $selected['status_meta']['tone'] }}"><x-icon :name="$selected['status_meta']['icon']" /> {{ $selected['status'] }}</span>
                </div>
                <div class="tracking-card-body">
                    <dl class="receipt-metadata">
                        <div><dt>Situação</dt><dd>{{ $selected['status'] }}</dd></div>
                        <div><dt>Última atualização</dt><dd>{{ $selected['updated_label'] }}</dd></div>
                        <div><dt>Prazo definido pela coordenação</dt><dd>{{ $selected['deadline_label'] }}</dd></div>
                    </dl>
                    <p class="status-summary">{{ $selected['status_meta']['summary'] }}</p>
                    <ol class="status-track" aria-label="Etapas do relato">
                        @foreach($steps as $position => $step)
                            @php
                                $state = '';
                                if ($selected['step_index'] !== null) {
                                    $state = $position < $selected['step_index'] ? 'is-done' : ($position === $selected['step_index'] ? 'is-current' : '');
                                }
                            @endphp
                            <li class="{{ $state }}">{{ $step }}@if($state === 'is-current')<span class="visually-hidden"> (etapa atual)</span>@elseif($state === 'is-done')<span class="visually-hidden"> (concluída)</span>@endif</li>
                        @endforeach
                    </ol>
                    <p class="field-help">Nesta demonstração, apenas a etapa Recebido é gerada. As demais dependem de triagem municipal, que ainda não existe.</p>

                    <div class="tracking-columns">
                        <div>
                            <h3 class="block-title">Histórico</h3>
                            <ol class="timeline">
                                @foreach($selected['timeline'] as $entry)
                                    <li><time>{{ $entry['label'] }}</time><strong>{{ $entry['title'] }}</strong>@if($entry['message'])<p>{{ $entry['message'] }}</p>@endif</li>
                                @endforeach
                            </ol>
                        </div>
                        <div>
                            <h3 class="block-title">Resumo do relato</h3>
                            <dl class="summary-list">
                                <div><dt>Descrição</dt><dd>{{ $selected['description'] }}</dd></div>
                                <div><dt>Endereço</dt><dd>{{ $selected['address'] }}</dd></div>
                                <div><dt>Referência</dt><dd>{{ $selected['reference'] }}</dd></div>
                                <div><dt>Fotografias</dt><dd>{{ $selected['photos_count'] }}</dd></div>
                            </dl>
                        </div>
                    </div>
                    <div class="tracking-actions">
                        <button class="button button-outline button-small" type="button" disabled aria-label="Adicionar informações — em breve">Adicionar informações <span class="pending-pill">Em breve</span></button>
                        <button class="button button-outline button-small" type="button" disabled aria-label="Solicitar revisão — em breve">Solicitar revisão <span class="pending-pill">Em breve</span></button>
                        <button class="button button-outline button-small" type="button" wire:click="close">Fechar detalhes</button>
                    </div>
                </div>
            </section>
        @endif

        <section class="tracking-list" aria-labelledby="list-heading">
            <div class="section-bar">
                <div>
                    <div class="eyebrow">Nesta sessão</div>
                    <h2 id="list-heading">Seus protocolos</h2>
                    <p>A lista fica guardada só nesta sessão do navegador. A chave não é armazenada.</p>
                </div>
                @if(count($reports))<button type="button" class="button button-outline button-small" wire:click="endSession" wire:loading.attr="disabled" wire:target="endSession">Encerrar consulta e limpar lista</button>@endif
            </div>
            @if(count($reports))
                <ul class="record-list">
                    @foreach($reports as $report)
                        <li class="record-card {{ $selected && $selected['id'] === $report['id'] ? 'is-selected' : '' }}" wire:key="report-{{ $report['id'] }}">
                            <button type="button" class="record-row" wire:click="open('{{ $report['id'] }}')" @if($selected && $selected['id'] === $report['id']) aria-current="true" @endif>
                                <span class="record-thumb" aria-hidden="true"><x-icon name="clipboard" /></span>
                                <span class="record-main">
                                    <span class="record-kicker">{{ $report['protocol'] }}</span>
                                    <span class="record-title">{{ $report['category'] }}</span>
                                    <span class="record-sub">{{ $report['address'] }} · {{ $report['reference'] }}</span>
                                </span>
                                <span class="record-meta">
                                    <span class="status-badge tone-{{ $report['status_meta']['tone'] }}"><x-icon :name="$report['status_meta']['icon']" /> {{ $report['status'] }}</span>
                                    <span>{{ $report['received_label'] }}</span>
                                </span>
                                <span class="record-chevron" aria-hidden="true"><x-icon name="chevron" /></span>
                                <span class="visually-hidden">Ver detalhes do protocolo {{ $report['protocol'] }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="empty-state"><x-icon name="search" /><p>Nenhum protocolo consultado nesta sessão. Informe o protocolo e a chave de acesso acima para começar.</p></div>
            @endif
        </section>

        <div class="action-band">
            <span class="action-band-icon" aria-hidden="true"><x-icon name="leaf" /></span>
            <div><h2>Não encontrou seu protocolo?</h2><p>Confira se o protocolo e a chave foram copiados por inteiro. A recuperação de acesso exige verificação do contato e ainda não está disponível nesta versão.</p></div>
            <div class="action-band-actions"><a class="button button-outline button-small" href="{{ route('relatos.create') }}">Registrar novo relato <x-icon name="arrow-up-right" /></a></div>
        </div>
    </div>
</div>
