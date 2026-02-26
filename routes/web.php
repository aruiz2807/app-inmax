<?php

use App\Http\Controllers\Auth\AdminAuthenticatedSessionController;
use App\Livewire\Auth\ForgotPinPage;
use App\Livewire\Auth\PinSetupPage;
use App\Livewire\Doctors\DoctorsPage;
use App\Livewire\Mobile\Doctor\DRHomePage;
use App\Livewire\Mobile\Doctor\DRHistoryPage;
use App\Livewire\Mobile\Doctor\DRNotesPage;
use App\Livewire\Mobile\Doctor\NoShowConfirmationPage;
use App\Livewire\Mobile\Doctor\NotesConfirmationPage;
use App\Livewire\Mobile\User\ContactPage;
use App\Livewire\Mobile\User\HistoryPage;
use App\Livewire\Mobile\User\PolicyStatusPage;
use App\Livewire\Mobile\User\ProfilePage;
use App\Livewire\Mobile\User\RecordPage;
use App\Livewire\Mobile\User\ScheduleCancellationPage;
use App\Livewire\Mobile\User\ScheduleConfirmationPage;
use App\Livewire\Mobile\User\SchedulePage;
use App\Livewire\Plans\PlansPage;
use App\Livewire\Policies\PoliciesPage;
use App\Livewire\Services\ServicesPage;
use App\Livewire\Settings\LegalSettingsPage;
use App\Livewire\Settings\WhatsAppSettingsPage;
use App\Livewire\Specialties\SpecialtiesPage;
use App\Livewire\Users\UsersPage;
use Illuminate\Support\Facades\Route;

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

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware('profile:Admin,Sales')->name('dashboard');

    Route::prefix('admin')->middleware('profile:Admin,Sales')->group(function () {

        Route::get('/doctors', DoctorsPage::class)->name('doctors');

        Route::get('/plans', PlansPage::class)->name('plans');

        Route::get('/policies', PoliciesPage::class)->name('policies');

        Route::get('/services', ServicesPage::class)->name('services');

        Route::get('/specialties', SpecialtiesPage::class)->name('specialties');

        Route::get('/users', UsersPage::class)->middleware('admin')->name('users');

        Route::get('/settings/whatsapp', WhatsAppSettingsPage::class)->middleware('admin')->name('settings.whatsapp');
        Route::get('/settings/legal', LegalSettingsPage::class)->middleware('admin')->name('settings.legal');

    });

    Route::prefix('user')->middleware('profile:User,Admin')->group(function () {

        Route::get('/home', function () {
            return view('livewire.mobile.user.home');
        })->name('user.home');

        Route::get('/schedule', SchedulePage::class)->name('user.schedule');
        Route::get('/schedule-confirmation', ScheduleConfirmationPage::class)->name('user.schedule-confirmation');
        Route::get('/schedule-cancellation', ScheduleCancellationPage::class)->name('user.schedule-cancellation');

        Route::get('/status', PolicyStatusPage::class)->name('user.status');

        Route::get('/record', RecordPage::class)->name('user.record');

        Route::get('/history', HistoryPage::class)->name('user.history');

        Route::get('/my-profile', ProfilePage::class)->name('user.my-profile');

        Route::get('/contact', ContactPage::class)->name('user.contact');
    });

    Route::prefix('doctor')->middleware('profile:Doctor,Admin')->group(function () {

        Route::get('/home', DRHomePage::class)->name('doctor.home');

        Route::get('/history', DRHistoryPage::class)->name('doctor.history');

        Route::get('/notes/{appointment}', DRNotesPage::class)->name('doctor.notes');
        Route::get('/notes-confirmation', NotesConfirmationPage::class)->name('doctor.notes-confirmation');

        Route::get('/noshow-confirmation', NoShowConfirmationPage::class)->name('doctor.noshow-confirmation');
    });

});
