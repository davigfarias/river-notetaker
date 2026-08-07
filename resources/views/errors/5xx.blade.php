@extends('errors::layout')

@section('title', 'Erro no servidor')
@section('code', $exception->getStatusCode())
@section('message', 'Algo deu errado do nosso lado. Já fomos notificados e estamos trabalhando nisso.')
