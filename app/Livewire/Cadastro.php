<?php

namespace App\Livewire;

use App\Services\JsonUserRepository;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Cadastro extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $accessCode = '';

    public function cadastrar(JsonUserRepository $users)
    {
        $code = (string) config('canal.registration_code');
        abort_if($code === '', 404);

        $data = $this->validate([
            'name' => 'required|string|min:3|max:80',
            'email' => 'required|email|max:190',
            'password' => 'required|string|min:10|max:200|confirmed',
            'accessCode' => 'required|string|max:200',
        ], [], [
            'name' => 'nome', 'email' => 'e-mail', 'password' => 'senha', 'accessCode' => 'código de acesso',
        ]);

        $key = 'cadastro|'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'accessCode' => 'Muitas tentativas. Tente novamente em '.RateLimiter::availableIn($key).' segundos.',
            ]);
        }
        if (! hash_equals($code, $data['accessCode'])) {
            RateLimiter::hit($key, 600);
            throw ValidationException::withMessages(['accessCode' => 'Código de acesso inválido.']);
        }

        try {
            $user = $users->create($data['name'], $data['email'], $data['password']);
        } catch (DomainException) {
            throw ValidationException::withMessages(['email' => 'Este e-mail já está cadastrado. Use a opção Entrar.']);
        }

        RateLimiter::clear($key);
        Auth::loginUsingId($user['id']);

        return $this->redirectRoute('painel');
    }

    public function render()
    {
        return view('livewire.cadastro', ['enabled' => (string) config('canal.registration_code') !== ''])
            ->layout('components.layouts.app', ['title' => 'Cadastro de servidor']);
    }
}
