<?php

use App\Http\Controllers\StaffController;
use App\Livewire\Cadastro;
use App\Livewire\Entrar;
use App\Livewire\NovoRelato;
use App\Livewire\Painel;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::get('/novo-relato', NovoRelato::class)->name('relatos.create');

// Área municipal
Route::middleware('guest')->group(function () {
    Route::get('/entrar', Entrar::class)->name('login');
    Route::get('/cadastro', Cadastro::class)->name('register');
});
Route::middleware('auth')->group(function () {
    Route::get('/painel', Painel::class)->name('painel');
    Route::get('/painel/anexos/{report}/{attachment}', [StaffController::class, 'attachment'])
        ->whereUuid(['report', 'attachment'])->name('painel.anexo');
    Route::post('/sair', [StaffController::class, 'logout'])->name('logout');
});
