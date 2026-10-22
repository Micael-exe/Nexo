<?php

use App\Livewire\AcompanharRelato;
use App\Livewire\Canais;
use App\Livewire\NovoRelato;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::get('/novo-relato', NovoRelato::class)->name('relatos.create');
Route::get('/acompanhar', AcompanharRelato::class)->name('relatos.track');
Route::get('/canais', Canais::class)->name('canais.index');
