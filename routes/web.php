<?php

use App\Livewire\NovoRelato;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::get('/novo-relato', NovoRelato::class)->name('relatos.create');
