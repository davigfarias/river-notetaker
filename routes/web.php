<?php

use App\Http\Controllers\DownloadExportController;
use App\Http\Middleware\EnsureAccessTokenIsValid;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::dashboard')->name('dashboard')->middleware(EnsureAccessTokenIsValid::class);
Route::livewire('/disciplinas/{slug}', 'pages::disciplina')->name('disciplinas.show')->middleware(EnsureAccessTokenIsValid::class);
Route::livewire('/disciplinas/{slug}/notas/nova', 'pages::create')->name('notas.criar')->middleware(EnsureAccessTokenIsValid::class);
Route::livewire('/conceitos/lista', 'pages::concepts')->name('concepts')->middleware(EnsureAccessTokenIsValid::class);
Route::livewire('/conselhos/lista', 'pages::pastoral')->name('pastoral')->middleware(EnsureAccessTokenIsValid::class);
Route::livewire('/busca', 'pages::busca')->name('busca')->middleware(EnsureAccessTokenIsValid::class);

Route::middleware(EnsureAccessTokenIsValid::class)->group(function () {
    Route::livewire('/referencias/lista', 'pages::referencias')->name('referencias');
    Route::livewire('/referencias/busca', 'pages::buscar-referencias')->name('referencias.busca');
    Route::livewire('/referencias/exportacoes', 'pages::exportacoes')->name('referencias.exportacoes');
    Route::get('/referencias/exportacoes/{export}/download', DownloadExportController::class)->name('referencias.exportacoes.download');
    Route::livewire('/referencias/{id}/capitulos/{chapterId}/estudar', 'pages::estudar')
        ->whereNumber('id')
        ->whereNumber('chapterId')
        ->name('referencias.study');

    Route::livewire('/referencias/{id}/capitulos/{chapterId}/revisao', 'pages::revisao')
        ->whereNumber('id')
        ->whereNumber('chapterId')
        ->name('referencias.study.review');

    Route::livewire('/referencias/{id}/capitulos/{chapterId}/resultados', 'pages::resultados')
        ->whereNumber('id')
        ->whereNumber('chapterId')
        ->name('referencias.study.results');

    Route::livewire('/referencias/{id}', 'pages::referencia')->whereNumber('id')->name('referencias.show');
});

Route::livewire('/entrar', 'pages::entrar')->name('entrar');
