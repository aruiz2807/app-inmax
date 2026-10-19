<<<<<<< HEAD
@extends('errors.layout')

@section('title', __('Forbidden'))
@section('code', '403')
@section('message', __('Forbidden'))
=======
@extends('errors::minimal')

@section('title', __('Forbidden'))
@section('code', '403')
@section('message', __($exception->getMessage() ?: 'Forbidden'))
>>>>>>> errors-blade
