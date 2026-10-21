
@extends('errors.layout')

@section('title', __('Sesión expirada'))
@section('code', '419')
@section('message', __('Sesión expirada'))
@section('extra')
    <div class="action">
        <a href="/">{{ __('Volver al inicio') }}</a>
    </div>
@endsection
