@extends('errors::layout')

@section('title', 'Acesso negado')
@section('code', '403')
@section('message', $exception->getMessage() ?: 'Você não tem permissão para acessar este recurso.')
