<div class="report-page" x-data="{ locating: false, locationMessage: '', copyMessage: '', connectionFailed: false, sessionExpired: false }"
    x-on:validation-failed.window="$nextTick(() => $refs.errorSummary?.focus())"
    x-on:receipt-created.window="$nextTick(() => { document.getElementById('receipt-heading')?.focus(); window.scrollTo({ top: 0, behavior: 'smooth' }); })"
    x-on:form-reset.window="locationMessage = ''; copyMessage = ''; connectionFailed = false; $nextTick(() => document.getElementById('report-heading')?.focus())"
    x-on:request-failed.window="connectionFailed = true; sessionExpired = $event.detail.expired">
    <div class="container">
        <nav class="breadcrumb-line" aria-label="Caminho de navegação"><a href="{{ route('home') }}">Início</a><x-icon name="chevron" /><span aria-current="page">Novo relato</span></nav>

        @if($receipt)
            <section class="receipt-card" aria-labelledby="receipt-heading" wire:key="receipt">
                <div class="receipt-top">
                    <div class="success-icon"><x-icon name="check" /></div>
                    <h1 id="receipt-heading" tabindex="-1">Relato demonstrativo registrado.</h1>
                    <p>O envio foi salvo nesta demonstração. Guarde o protocolo e a chave abaixo. Este registro não será encaminhado para atendimento pela prefeitura.</p>
                </div>
                <div class="receipt-body">
                    <dl class="receipt-metadata">
                        <div><dt>Seu protocolo</dt><dd class="receipt-protocol">{{ $receipt['protocol'] }}</dd></div>
                        <div><dt>Registrado em</dt><dd>{{ $receipt['received_at'] }}</dd></div>
                        <div><dt>Situação</dt><dd>{{ $receipt['status'] }}</dd></div>
                    </dl>
                    <div class="credential-box">
                        <h2>Guarde sua chave de acesso</h2>
                        <p>A chave é privada e aparece neste recibo para você guardar. A página de acompanhamento ainda não está disponível nesta versão.</p>
                        <div class="credential-row">
                            <code x-ref="credential" tabindex="0" aria-label="Chave privada de acesso">{{ $receipt['credential'] }}</code>
                            <button type="button" class="button button-outline" x-on:click="window.CanalEmDia.copyCredential($refs.credential.textContent.trim(), $data, $refs.credential)"><x-icon name="clipboard" /> Copiar chave</button>
                        </div>
                        <div class="copy-status" role="status" x-text="copyMessage" x-show="copyMessage" x-cloak></div>
                    </div>
                    <div class="receipt-notice"><x-icon name="info" /><span>Não há triagem municipal nem envio de e-mail nesta demonstração. O projeto ainda depende de validação com a prefeitura para eventual uso real.</span></div>
                    <div class="receipt-actions"><button type="button" wire:click="resetForm" class="button button-forest" wire:loading.attr="disabled" wire:target="resetForm">Fazer outro relato <x-icon name="plus" /></button><a href="{{ route('home') }}" class="button button-outline">Voltar ao início <x-icon name="arrow-right" /></a></div>
                </div>
            </section>
        @else
            <div class="report-heading" wire:key="report-heading">
                <div><div class="eyebrow">Participar é o primeiro passo</div><h1 id="report-heading" tabindex="-1">Conte o que você observou.</h1><p>Informe a situação e ajude a identificar o trecho. Você não precisa de GPS nem de fotografias para fazer um relato.</p></div>
                <span class="report-label"><x-icon name="leaf" /> Um cuidado compartilhado</span>
            </div>
            <div class="demo-notice"><x-icon name="info" /><p><strong>Este é um formulário de demonstração.</strong> Use somente informações fictícias. Ele não recebe ocorrências reais e não substitui os canais oficiais ou o atendimento de emergência.</p></div>

            <div wire:offline class="connection-notice" role="status"><x-icon name="warning" /> Você está sem conexão. O preenchimento permanece nesta página. Conecte-se novamente antes de enviar; evite fechar ou atualizar a aba.</div>
            <div x-show="connectionFailed" x-cloak class="connection-notice" role="alert"><x-icon name="warning" /><span x-show="!sessionExpired">Não foi possível concluir a comunicação. O preenchimento permanece na tela. Verifique sua conexão e tente novamente.</span><span x-show="sessionExpired">A sessão desta página expirou. Guarde as informações preenchidas antes de atualizar a página e tentar novamente.</span></div>

            @if($errors->any())
                <div class="error-summary" role="alert" tabindex="-1" x-ref="errorSummary">
                    <h2>Confira os campos antes de enviar</h2>
                    <ul>@foreach($errors->messages() as $field => $messages)<li>@if(in_array($field, ['submission', 'website'])){{ $messages[0] }}@else<a href="#field-{{ str_starts_with($field, 'photos') ? 'photos' : $field }}">{{ $messages[0] }}</a>@endif</li>@endforeach</ul>
                </div>
            @endif

            <div class="report-layout">
                <form wire:submit="submit" x-on:submit="connectionFailed = false" class="report-card" novalidate aria-label="Novo relato demonstrativo">
                    <div class="honeypot" aria-hidden="true"><label for="field-website">Deixe este campo vazio</label><input id="field-website" type="text" wire:model="website" tabindex="-1" autocomplete="off"></div>

                    <section class="form-section" aria-labelledby="observation-heading">
                        <div class="form-section-title"><span class="section-number" aria-hidden="true">01</span><div><h2 id="observation-heading">O que está acontecendo?</h2><p>Uma descrição clara ajuda a entender a situação observada.</p></div></div>
                        <fieldset class="category-fieldset" id="field-category" aria-describedby="category-help @error('category') error-category @enderror">
                            <legend class="field-label">Tipo de problema <span class="required-mark" aria-hidden="true">*</span></legend>
                            <div class="category-grid">@foreach($categories as $value => $label)<label class="category-choice" wire:key="category-{{ $value }}"><input type="radio" wire:model="category" value="{{ $value }}" required @error('category') aria-invalid="true" @enderror><span>{{ $label }}</span></label>@endforeach</div>
                            <p class="field-help mb-0" id="category-help">Escolha a opção mais próxima do que você observou.</p>
                            @error('category')<p class="field-error" id="error-category">{{ $message }}</p>@enderror
                        </fieldset>
                        <div class="mb-4">
                            <label for="field-description" class="form-label">Descreva a situação <span class="required-mark" aria-hidden="true">*</span></label>
                            <textarea id="field-description" class="form-control @error('description') is-invalid @enderror" wire:model="description" rows="4" minlength="20" maxlength="1000" required placeholder="Ex.: há acúmulo de materiais no trecho próximo à ponte. Informe o que observou, sem incluir dados pessoais." aria-describedby="description-help @error('description') error-description @enderror" @error('description') aria-invalid="true" @enderror></textarea>
                            <div class="field-bottom"><p class="field-help mb-0" id="description-help">Entre 20 e 1.000 caracteres. Evite nomes, documentos ou informações privadas.</p><span class="char-counter" aria-hidden="true"><span x-text="($wire.description || '').length">{{ mb_strlen($description) }}</span> / 1.000</span></div>
                            @error('description')<p class="field-error" id="error-description">{{ $message }}</p>@enderror
                        </div>
                        <div><label for="field-observedAt" class="form-label">Quando você observou? <span class="optional">Opcional</span></label><input id="field-observedAt" type="datetime-local" wire:model="observedAt" class="form-control @error('observedAt') is-invalid @enderror" aria-describedby="observedAt-help @error('observedAt') error-observedAt @enderror" @error('observedAt') aria-invalid="true" @enderror><p class="field-help mb-0" id="observedAt-help">Informe a data e a hora, se souber. Você pode deixar em branco.</p>@error('observedAt')<p class="field-error" id="error-observedAt">{{ $message }}</p>@enderror</div>
                    </section>

                    <section class="form-section" aria-labelledby="location-heading">
                        <div class="form-section-title"><span class="section-number" aria-hidden="true">02</span><div><h2 id="location-heading">Onde fica o trecho?</h2><p>Preencha o endereço e uma referência, mesmo se usar a localização.</p></div></div>
                        <div class="gps-box"><div class="gps-intro"><x-icon name="location" /><div><strong>Adicionar localização do dispositivo</strong><p>É opcional. Use somente se estiver no local e em segurança.</p></div></div><button type="button" class="button button-outline" x-on:click="window.CanalEmDia.locate($wire, $data)" x-bind:disabled="locating"><x-icon name="location" /><span x-text="locating ? 'Localizando…' : 'Usar localização'">Usar localização</span></button></div>
                        <p class="gps-message" role="status" x-text="locationMessage" x-show="locationMessage" x-cloak></p>
                        <div class="mb-4"><label for="field-address" class="form-label">Endereço ou nome da via <span class="required-mark" aria-hidden="true">*</span></label><input id="field-address" type="text" wire:model="address" maxlength="200" required autocomplete="off" class="form-control @error('address') is-invalid @enderror" placeholder="Ex.: Rua das Flores, próximo ao número 100, bairro Exemplo" aria-describedby="address-help @error('address') error-address @enderror" @error('address') aria-invalid="true" @enderror><p class="field-help mb-0" id="address-help">Na demonstração, use um endereço fictício. Inclua o bairro para situar o trecho.</p>@error('address')<p class="field-error" id="error-address">{{ $message }}</p>@enderror</div>
                        <div><label for="field-reference" class="form-label">Ponto de referência <span class="required-mark" aria-hidden="true">*</span></label><input id="field-reference" type="text" wire:model="reference" maxlength="200" required autocomplete="off" class="form-control @error('reference') is-invalid @enderror" placeholder="Ex.: ao lado da ponte, em frente à praça" aria-describedby="reference-help @error('reference') error-reference @enderror" @error('reference') aria-invalid="true" @enderror><p class="field-help mb-0" id="reference-help">Uma referência visível ajuda a diferenciar os trechos.</p>@error('reference')<p class="field-error" id="error-reference">{{ $message }}</p>@enderror</div>
                        <div class="coordinates" x-show="$wire.latitude && $wire.longitude" x-cloak><span>Latitude: <span x-text="$wire.latitude"></span></span><span>Longitude: <span x-text="$wire.longitude"></span></span><button type="button" class="button button-outline button-small" x-on:click="$wire.set('latitude', '', false); $wire.set('longitude', '', false); locationMessage = 'Localização removida. O endereço e a referência são suficientes para continuar.'">Remover localização</button></div>
                        @error('latitude')<p class="field-error" id="field-latitude">{{ $message }}</p>@enderror
                        @error('longitude')<p class="field-error" id="field-longitude">{{ $message }}</p>@enderror
                    </section>

                    <section class="form-section" aria-labelledby="photos-heading">
                        <div class="form-section-title"><span class="section-number" aria-hidden="true">03</span><div><h2 id="photos-heading">Quer adicionar fotos? <span class="optional">Opcional</span></h2><p>Fotografias podem ajudar a compreender o relato.</p></div></div>
                        <div class="upload-zone" id="field-photos"><div class="upload-icon"><x-icon name="camera" /></div><label for="photos-upload">Escolher fotografias</label><input id="photos-upload" class="upload-input" wire:model="photos" type="file" multiple accept="image/jpeg,image/png" aria-describedby="photos-help @error('photos') error-photos @enderror"><p id="photos-help">Até 3 imagens JPEG ou PNG, com no máximo 5 MB cada.</p></div>
                        <p class="field-help mb-0 mt-2">Use apenas imagens fictícias nesta demonstração. Não inclua rostos, placas ou áreas privadas e não se aproxime de uma situação de risco.</p>
                        <div class="upload-status" wire:loading wire:target="photos" role="status"><span class="loading-spinner"></span> Preparando as fotografias…</div>
                        @error('photos')<p class="field-error" id="error-photos">{{ $message }}</p>@enderror
                        @foreach($errors->get('photos.*') as $photoMessages) @foreach($photoMessages as $photoMessage)<p class="field-error">{{ $photoMessage }}</p>@endforeach @endforeach
                        @if(count($photos))<ul class="upload-list" aria-label="Fotografias selecionadas">@foreach($photos as $index => $photo)@if($photo instanceof \Illuminate\Http\UploadedFile)<li wire:key="photo-{{ $index }}"><x-icon name="file" /><span class="upload-file-name">{{ $photo->getClientOriginalName() }}</span><button type="button" class="remove-upload" wire:click="removePhoto({{ $index }})" wire:loading.attr="disabled" wire:target="removePhoto" aria-label="Remover fotografia {{ $index + 1 }}"><x-icon name="trash" /></button></li>@endif @endforeach</ul>@endif
                    </section>

                    <section class="form-section" aria-labelledby="contact-heading">
                        <div class="form-section-title"><span class="section-number" aria-hidden="true">04</span><div><h2 id="contact-heading">Contato e privacidade</h2><p>O contato é opcional. Você também pode enviar sem preenchê-lo.</p></div></div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6"><label for="field-email" class="form-label">E-mail <span class="optional">Opcional</span></label><input id="field-email" wire:model="email" type="email" maxlength="254" inputmode="email" autocomplete="off" class="form-control @error('email') is-invalid @enderror" placeholder="exemplo@example.com" aria-describedby="contact-help @error('email') error-email @enderror" @error('email') aria-invalid="true" @enderror>@error('email')<p class="field-error" id="error-email">{{ $message }}</p>@enderror</div>
                            <div class="col-md-6"><label for="field-phone" class="form-label">Telefone <span class="optional">Opcional</span></label><input id="field-phone" wire:model="phone" type="tel" maxlength="30" inputmode="tel" autocomplete="off" class="form-control @error('phone') is-invalid @enderror" placeholder="(00) 00000-0000" aria-describedby="contact-help @error('phone') error-phone @enderror" @error('phone') aria-invalid="true" @enderror>@error('phone')<p class="field-error" id="error-phone">{{ $message }}</p>@enderror</div>
                            <div class="col-12"><p class="field-help mb-0 mt-0" id="contact-help">Utilize somente contatos fictícios. Esta versão não envia e-mails nem mensagens.</p></div>
                        </div>
                        <details class="privacy-box"><summary>Leia o aviso de privacidade da demonstração</summary><div class="privacy-content"><p>Este protótipo registra os dados preenchidos e as fotografias no ambiente local do projeto para demonstrar o fluxo de criação de relatos. Não há encaminhamento para órgãos públicos ou atendimento municipal.</p><p>Não informe dados pessoais reais nem envie imagens identificáveis de terceiros. O contato é opcional. Endereço, referência, descrição e categoria são necessários para demonstrar o relato. As coordenadas só são adicionadas se você escolher usar a localização e permitir o acesso no navegador.</p><p>Guarde o protocolo e a chave exibidos ao final. O acompanhamento ainda não está disponível. Antes de qualquer uso real, a equipe deverá definir com os responsáveis a governança, a base legal, a retenção e os canais para exercício de direitos.</p></div></details>
                        <label class="privacy-check" for="field-privacyAcknowledged"><input id="field-privacyAcknowledged" type="checkbox" wire:model="privacyAcknowledged" required @error('privacyAcknowledged') aria-invalid="true" aria-describedby="error-privacyAcknowledged" @enderror><span>Li o aviso de privacidade e entendi que este é um registro demonstrativo, com uso somente de informações fictícias. <span class="required-mark" aria-hidden="true">*</span></span></label>
                        @error('privacyAcknowledged')<p class="field-error" id="error-privacyAcknowledged">{{ $message }}</p>@enderror
                    </section>
                    <div class="form-footer"><p>Os campos com <span class="required-mark" aria-hidden="true">*</span><span class="visually-hidden">asterisco</span> são obrigatórios. Revise as informações antes de enviar.</p><button type="submit" class="button button-forest" wire:loading.attr="disabled" wire:target="submit,photos" wire:offline.attr="disabled"><span wire:loading.remove wire:target="submit">Enviar relato demonstrativo <x-icon name="arrow-up-right" /></span><span wire:loading wire:target="submit"><span class="loading-spinner"></span> Registrando relato…</span></button></div>
                </form>

                <aside class="report-sidebar" aria-label="Orientações para o relato">
                    <div class="sidebar-card dark"><div class="eyebrow">Cuidado que conecta</div><h2>Um relato claro.<br>Um trecho identificado.</h2><p>As informações organizadas são o primeiro passo para conhecer melhor uma situação.</p><ol class="sidebar-steps"><li><strong>Descreva</strong><span>O que você observou.</span></li><li><strong>Localize</strong><span>Endereço e referência.</span></li><li><strong>Guarde</strong><span>O protocolo e a chave.</span></li></ol></div>
                    <div class="sidebar-card"><div class="sidebar-safe"><x-icon name="shield" /><div><h3>Sua segurança vem primeiro.</h3><p>Observe de um lugar seguro. Não entre no canal nem se aproxime de áreas alagadas para registrar um problema.</p></div></div></div>
                    <p class="sidebar-caption">Piloto em proposta: trecho, equipe e horários de atendimento ainda dependem de definição com a prefeitura. Não há atendimento real por este site.</p>
                </aside>
            </div>
        @endif
    </div>
</div>
