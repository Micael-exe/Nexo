@php
    $tz = fn ($iso) => \Carbon\CarbonImmutable::parse($iso)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i');
    $slug = fn ($s) => \Illuminate\Support\Str::slug($s);
@endphp
<div class="dash-page" x-data x-on:report-selected.window="$nextTick(() => document.querySelector('.detail')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))">
    <div class="container">
        <div class="dash-head">
            <div>
                <div class="eyebrow">Painel municipal</div>
                <h1>Relatos recebidos</h1>
                <p>Olá, {{ auth()->user()->name }}. {{ $total }} {{ $total === 1 ? 'relato' : 'relatos' }} no total. Dados de demonstração.</p>
            </div>
        </div>

        <section class="dash-stats" aria-label="Resumo por situação">
            @foreach($statuses as $status)
                <button type="button" class="stat-card stat-{{ $slug($status) }} {{ $filterStatus === $status ? 'is-active' : '' }}"
                    wire:click="$set('filterStatus', '{{ $filterStatus === $status ? '' : $status }}')" aria-pressed="{{ $filterStatus === $status ? 'true' : 'false' }}">
                    <strong>{{ $counts[$status] ?? 0 }}</strong><span>{{ $status }}</span>
                </button>
            @endforeach
        </section>

        <div class="row g-4">
            <div class="col-lg-{{ $selected ? 7 : 9 }}">
                <section class="dash-card" aria-labelledby="list-heading">
                    <h2 id="list-heading" class="visually-hidden">Lista de relatos</h2>
                    <div class="dash-filters">
                        <div><label class="form-label" for="search">Buscar</label><input id="search" type="search" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Protocolo, endereço ou descrição"></div>
                        <div><label class="form-label" for="fcat">Tipo</label>
                            <select id="fcat" class="form-select" wire:model.live="filterCategory"><option value="">Todos</option>@foreach($categories as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                        <div><label class="form-label" for="fstatus">Situação</label>
                            <select id="fstatus" class="form-select" wire:model.live="filterStatus"><option value="">Todas</option>@foreach($statuses as $s)<option>{{ $s }}</option>@endforeach</select></div>
                    </div>

                    @forelse($reports as $r)
                        <button type="button" class="report-row {{ $selectedId === $r['id'] ? 'is-selected' : '' }}" wire:key="r-{{ $r['id'] }}" wire:click="select('{{ $r['id'] }}')">
                            <span class="report-row-main">
                                <strong>{{ $categories[$r['category']] ?? $r['category'] }}</strong>
                                <span>{{ $r['location']['address'] }}</span>
                                <small>{{ $r['protocol'] }} · {{ $tz($r['received_at']) }}@if(count($r['attachments'])) · {{ count($r['attachments']) }} foto(s)@endif</small>
                            </span>
                            <span class="status-pill status-{{ $slug($r['status']) }}">{{ $r['status'] }}</span>
                        </button>
                    @empty
                        <p class="dash-empty">{{ $total === 0 ? 'Nenhum relato recebido ainda.' : 'Nenhum relato corresponde aos filtros.' }}</p>
                    @endforelse

                    @if($lastPage > 1)
                        <div class="dash-pager">
                            <button type="button" class="button button-outline button-small" wire:click="previous" @disabled($page <= 1)>Anterior</button>
                            <span>Página {{ $page }} de {{ $lastPage }} ({{ $filteredTotal }})</span>
                            <button type="button" class="button button-outline button-small" wire:click="next" @disabled($page >= $lastPage)>Próxima</button>
                        </div>
                    @endif
                </section>
            </div>

            <div class="col-lg-{{ $selected ? 5 : 3 }}">
                @if($selected)
                    <section class="dash-card detail" aria-labelledby="detail-heading" style="scroll-margin-top:12px" wire:key="detail-{{ $selected['id'] }}">
                        <div class="detail-top">
                            <div><h2 id="detail-heading">{{ $categories[$selected['category']] ?? $selected['category'] }}</h2><code>{{ $selected['protocol'] }}</code></div>
                            <button type="button" class="detail-close" wire:click="close" aria-label="Fechar detalhes"><x-icon name="close" /></button>
                        </div>
                        <dl class="detail-list">
                            <div><dt>Descrição</dt><dd>{{ $selected['description'] }}</dd></div>
                            <div><dt>Endereço</dt><dd>{{ $selected['location']['address'] }}</dd></div>
                            <div><dt>Referência</dt><dd>{{ $selected['location']['reference'] }}</dd></div>
                            @if($selected['location']['latitude'] !== null)<div><dt>Coordenadas do aparelho</dt><dd>{{ $selected['location']['latitude'] }}, {{ $selected['location']['longitude'] }}</dd></div>@endif
                            <div><dt>Observado em</dt><dd>{{ $tz($selected['observed_at']) }}</dd></div>
                            <div><dt>Recebido em</dt><dd>{{ $tz($selected['received_at']) }}</dd></div>
                            <div><dt>Contato (não verificado)</dt><dd>{{ $selected['contact']['email'] ?: '—' }} · {{ $selected['contact']['phone'] ?: '—' }}</dd></div>
                        </dl>
                        @if(count($selected['attachments']))
                            <div class="detail-photos">
                                @foreach($selected['attachments'] as $a)
                                    <a href="{{ route('painel.anexo', [$selected['id'], $a['id']]) }}" target="_blank" rel="noopener"><img src="{{ route('painel.anexo', [$selected['id'], $a['id']]) }}" alt="Foto enviada pelo cidadão" loading="lazy"></a>
                                @endforeach
                            </div>
                        @endif

                        <form wire:submit="saveStatus" class="detail-form" novalidate>
                            <label for="newStatus" class="form-label">Situação</label>
                            <select id="newStatus" class="form-select mb-3" wire:model="newStatus">@foreach($statuses as $s)<option>{{ $s }}</option>@endforeach</select>
                            @error('newStatus')<p class="field-error">{{ $message }}</p>@enderror
                            <label for="note" class="form-label">Observação interna (opcional)</label>
                            <textarea id="note" class="form-control mb-1" rows="2" maxlength="500" wire:model="note"></textarea>
                            @error('note')<p class="field-error">{{ $message }}</p>@enderror
                            <button type="submit" class="button button-forest mt-3" wire:loading.attr="disabled">Salvar situação</button>
                            @if($saved)<p class="copy-status" role="status">{{ $saved }}</p>@endif
                        </form>

                        @if(count($selected['history']))
                            <h3 class="detail-sub">Histórico</h3>
                            <ul class="history">
                                @foreach(array_reverse($selected['history']) as $h)
                                    <li><strong>{{ $h['from'] }} → {{ $h['to'] }}</strong> <small>{{ $tz($h['at']) }} · {{ $h['by'] }}</small>@if($h['note'])<br>{{ $h['note'] }}@endif</li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                @else
                    <section class="dash-card" aria-labelledby="cat-heading">
                        <h2 id="cat-heading" class="dash-sub">Por tipo de problema</h2>
                        <ul class="cat-list">
                            @foreach($categories as $k => $label)
                                <li><span>{{ $label }}</span><strong>{{ $byCategory[$k] ?? 0 }}</strong></li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>
        </div>
    </div>
</div>
