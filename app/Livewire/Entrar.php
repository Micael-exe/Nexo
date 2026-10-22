<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Entrar extends Component
{
    public string $email = '';
    public string $password = '';

    public function entrar()
    {
        $this->validate(
            ['email' => 'required|email|max:190', 'password' => 'required|string|max:200'],
            [],
            ['email' => 'e-mail', 'password' => 'senha'],
        );

        $key = 'login:'.Str::lower($this->email).'|'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Muitas tentativas. Tente novamente em '.RateLimiter::availableIn($key).' segundos.',
            ]);
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            RateLimiter::hit($key, 300);
            $this->password = '';
            throw ValidationException::withMessages(['email' => 'E-mail ou senha incorretos.']);
        }

        RateLimiter::clear($key);

        return $this->redirectIntended(route('painel'));
    }

    public function render()
    {
        return view('livewire.entrar')->layout('components.layouts.app', ['title' => 'Entrar']);
    }
}
