<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Auth\ForgotPinPage;
use App\Livewire\Auth\PinSetupPage;
use App\Livewire\Doctors\DoctorsPage;
use App\Livewire\Services\ServicesPage;
use App\Livewire\Specialties\SpecialtiesPage;
use App\Livewire\Users\UsersPage;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/pin/setup/{token}', PinSetupPage::class)->name('pin.setup');
    Route::get('/forgot-pin', ForgotPinPage::class)->name('pin.forgot');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

/*services*/
    Route::get('/services', ServicesPage::class)->name('services');
/*--------*/

/*specialties*/
    Route::get('/specialties', SpecialtiesPage::class)->name('specialties');
/*--------*/

/*doctors*/
    Route::get('/doctors', DoctorsPage::class)->name('doctors');
/*--------*/

/*users*/
    Route::get('/users', UsersPage::class)->middleware('admin')->name('users');
/*--------*/

});
