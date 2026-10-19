<<<<<<< HEAD
@extends('errors.layout')
=======
@extends('errors::minimal')
>>>>>>> errors-blade

@section('title', __('Too Many Requests'))
@section('code', '429')
@section('message', __('Too Many Requests'))
