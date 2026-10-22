<div class="auth-page">
    <div class="container">
        <div class="auth-card">
            <div class="eyebrow">Área municipal</div>
            <h1>Cadastro de servidor</h1>
            @if(! $enabled)
                <div class="demo-notice"><x-icon name="info" /><p>O cadastro está desativado. Quem administra a instalação deve definir <code>CANAL_CODIGO_CADASTRO</code> no arquivo <code>.env</code> para liberá-lo.</p></div>
                <p class="auth-alt"><a href="{{ route('login') }}">Voltar para Entrar</a></p>
            @else
                <p>Informe o código de acesso fornecido pelo órgão responsável. Ele impede que pessoas sem vínculo vejam os relatos.</p>
                <form wire:submit="cadastrar" novalidate>
                    @foreach([['name','Nome','text','name'],['email','E-mail institucional','email','username'],['password','Senha (mínimo 10 caracteres)','password','new-password'],['password_confirmation','Repita a senha','password','new-password'],['accessCode','Código de acesso','password','off']] as [$field,$label,$type,$auto])
                        <div class="mb-3" wire:key="f-{{ $field }}">
                            <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                            <input id="{{ $field }}" type="{{ $type }}" class="form-control" wire:model="{{ $field }}" autocomplete="{{ $auto }}" required @error($field) aria-invalid="true" aria-describedby="error-{{ $field }}" @enderror>
                            @error($field)<p class="field-error" id="error-{{ $field }}" role="alert">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                    <button type="submit" class="button button-forest w-100 justify-content-center mt-2" wire:loading.attr="disabled">Criar acesso <x-icon name="arrow-right" /></button>
                </form>
                <p class="auth-alt">Já tem acesso? <a href="{{ route('login') }}">Entrar</a></p>
            @endif
        </div>
    </div>
</div>
