<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::dashboard')->name('dashboard');
Route::livewire('/disciplinas/{slug}', 'pages::disciplina')->name('disciplinas.show');
Route::livewire('/notas/nova', 'pages::create')->name('notas.criar');
Route::livewire('/conceitos/lista', 'pages::concepts')->name('concepts');
