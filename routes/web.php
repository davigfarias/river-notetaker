<?php

use App\Http\Middleware\EnsureAccessTokenIsValid;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::dashboard')->name('dashboard')->middleware(EnsureAccessTokenIsValid::class);
Route::livewire('/disciplinas/{slug}', 'pages::disciplina')->name('disciplinas.show')->middleware(EnsureAccessTokenIsValid::class);
Route::livewire('/notas/nova', 'pages::create')->name('notas.criar')->middleware(EnsureAccessTokenIsValid::class);
Route::livewire('/conceitos/lista', 'pages::concepts')->name('concepts')->middleware(EnsureAccessTokenIsValid::class);
Route::livewire('/entrar', 'pages::entrar')->name('entrar');
