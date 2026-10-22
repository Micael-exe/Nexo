<div class="auth-page">
    <div class="container">
        <div class="auth-card">
            <div class="eyebrow">Área municipal</div>
            <h1>Entrar no painel</h1>
            <p>Acesso exclusivo para servidores do órgão responsável pelos canais e bocas de lobo.</p>
            <form wire:submit="entrar" novalidate>
                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input id="email" type="email" class="form-control" wire:model="email" autocomplete="username" required @error('email') aria-invalid="true" aria-describedby="error-email" @enderror>
                    @error('email')<p class="field-error" id="error-email" role="alert">{{ $message }}</p>@enderror
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Senha</label>
                    <input id="password" type="password" class="form-control" wire:model="password" autocomplete="current-password" required @error('password') aria-invalid="true" aria-describedby="error-password" @enderror>
                    @error('password')<p class="field-error" id="error-password" role="alert">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="button button-forest w-100 justify-content-center" wire:loading.attr="disabled">Entrar <x-icon name="arrow-right" /></button>
            </form>
            <p class="auth-alt">Primeiro acesso? <a href="{{ route('register') }}">Cadastrar servidor</a></p>
        </div>
    </div>
</div>
