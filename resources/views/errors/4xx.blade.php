@extends('errors::layout')

@section('title', 'Erro na requisição')
@section('code', $exception->getStatusCode())
@section('message', $exception->getMessage() ?: 'Não foi possível processar sua requisição.')
