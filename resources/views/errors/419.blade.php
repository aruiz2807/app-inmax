
@extends('errors.layout')

@section('title', __('Session Expired'))
@section('code', '419')
@section('message', __('Session Expired'))
@section('extra')
    <div class="action">
        <a href="/">{{ __('Volver al inicio') }}</a>
    </div>
@endsection
