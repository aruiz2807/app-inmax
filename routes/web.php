<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AdminAuthenticatedSessionController;
use App\Livewire\Auth\ForgotPinPage;
use App\Livewire\Plans\PlansPage;
use App\Livewire\Policies\PoliciesPage;
use App\Livewire\Auth\PinSetupPage;
use App\Livewire\Doctors\DoctorsPage;
use App\Livewire\Services\ServicesPage;
use App\Livewire\Specialties\SpecialtiesPage;
use App\Livewire\Users\UsersPage;
use App\Livewire\Settings\WhatsAppSettingsPage;

use App\Livewire\Mobile\User\PolicyStatusPage;
use App\Livewire\Mobile\User\SchedulePage;
use App\Livewire\Mobile\User\ScheduleConfirmationPage;
use App\Livewire\Mobile\User\RecordPage;
use App\Livewire\Mobile\User\HistoryPage;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/pin/setup/{token}', PinSetupPage::class)->name('pin.setup');
    Route::get('/forgot-pin', ForgotPinPage::class)->name('pin.forgot');
    Route::get('/admin/login', [AdminAuthenticatedSessionController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:admin-login')
        ->name('admin.login.store');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    Route::get('/dashboard', function () { return view('dashboard'); })->name('dashboard');

    Route::prefix('admin')->group(function () {

        Route::get('/doctors', DoctorsPage::class)->name('doctors');

        Route::get('/plans', PlansPage::class)->name('plans');

        Route::get('/policies', PoliciesPage::class)->name('policies');

        Route::get('/services', ServicesPage::class)->name('services');

        Route::get('/specialties', SpecialtiesPage::class)->name('specialties');

        Route::get('/users', UsersPage::class)->middleware('admin')->name('users');

        Route::get('/settings/whatsapp', WhatsAppSettingsPage::class)->middleware('admin')->name('settings.whatsapp');

    });

    Route::prefix('user')->group(function () {

        Route::get('/home', function () { return view('livewire.mobile.user.home'); })->name('user.home');

        Route::get('/schedule', SchedulePage::class)->name('user.schedule');
        Route::get('/schedule-confirmation', ScheduleConfirmationPage::class)->name('user.schedule-confirmation');

        Route::get('/status', PolicyStatusPage::class)->name('user.status');

        Route::get('/record', RecordPage::class)->name('user.record');

        Route::get('/history', HistoryPage::class)->name('user.history');
    });

    Route::prefix('doctors')->group(function () {

    });


});
