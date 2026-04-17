<?php

// Facades
use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Auth\AdminAuthenticatedSessionController;
use App\Http\Controllers\AttachmentController;

// Livewire - Appointments
use App\Livewire\Appointments\AppointmentsPage;

// Livewire - Auth
use App\Livewire\Auth\ForgotPinPage;
use App\Livewire\Auth\PinSetupPage;

// Livewire - Doctors
use App\Livewire\Doctors\DoctorsPage;

// Livewire - Home
use App\Livewire\Home\DashboardPage;

// Livewire - Clerk
use App\Livewire\Clerk\DashboardPage as ClerkDashboardPage;
use App\Livewire\Clerk\DispensationPage;
use App\Livewire\Clerk\InventoryPage;

// Livewire - Offices
use App\Livewire\Offices\OfficesPage;

// Livewire - Plans
use App\Livewire\Plans\PlansPage;

// Livewire - Policies
use App\Livewire\Policies\PolicyPreregistrationPage;
use App\Livewire\Policies\PolicyPreregistrationsPage;
use App\Livewire\Policies\PoliciesPage;

// Livewire - Services
use App\Livewire\Services\ServicesPage;

// Livewire - Settings
use App\Livewire\Settings\LegalSettingsPage;
use App\Livewire\Settings\ParametersPage;
use App\Livewire\Settings\WhatsAppSettingsPage;

// Livewire - Specialties
use App\Livewire\Specialties\SpecialtiesPage;

// Livewire - Users
use App\Livewire\Users\UsersPage;

// Livewire - Mobile
use App\Livewire\Mobile\ContactPage;

// Livewire - Mobile - Doctor
use App\Livewire\Mobile\Doctor\AcceptConfirmationPage;
use App\Livewire\Mobile\Doctor\DRHistoryNotePage;
use App\Livewire\Mobile\Doctor\DRHistoryPage;
use App\Livewire\Mobile\Doctor\DRHomePage;
use App\Livewire\Mobile\Doctor\DRNotesPage;
use App\Livewire\Mobile\Doctor\DRProfilePage;
use App\Livewire\Mobile\Doctor\DRRecordPage;
use App\Livewire\Mobile\Doctor\DRRequestsPage;
use App\Livewire\Mobile\Doctor\DRScheduleConfirmationPage;
use App\Livewire\Mobile\Doctor\DRSchedulePage;
use App\Livewire\Mobile\Doctor\NoShowConfirmationPage;
use App\Livewire\Mobile\Doctor\NotesConfirmationPage;
use App\Livewire\Mobile\Doctor\RejectConfirmationPage;

// Livewire - Mobile - User
use App\Livewire\Mobile\User\HistoryPage;
use App\Livewire\Mobile\User\HomePage;
use App\Livewire\Mobile\User\NotesPage;
use App\Livewire\Mobile\User\PolicyStatusPage;
use App\Livewire\Mobile\User\ProfilePage;
use App\Livewire\Mobile\User\RatingConfirmationPage;
use App\Livewire\Mobile\User\RatingPage;
use App\Livewire\Mobile\User\RecordPage;
use App\Livewire\Mobile\User\ScheduleCancellationPage;
use App\Livewire\Mobile\User\ScheduleConfirmationPage;
use App\Livewire\Mobile\User\SchedulePage;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/pin/setup/{token}', PinSetupPage::class)->name('pin.setup');
    Route::get('/policy-registration/{token}', PolicyPreregistrationPage::class)->name('policy.preregistration');
    Route::get('/forgot-pin', ForgotPinPage::class)->name('pin.forgot');
    Route::get('/admin/login', [AdminAuthenticatedSessionController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:admin-login')
        ->name('admin.login.store');
});

Route::get('/contact', ContactPage::class)->name('user.contact');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    Route::get('/dashboard', DashboardPage::class)->middleware('profile:Admin,Sales')->name('dashboard');

    Route::get('/attachment/{note_id}', [AttachmentController::class, 'download'])->name('attachment.download');
    Route::get('/external-service/{external_service_id}', [AttachmentController::class, 'downloadExternalService'])->name('external-service.download');

    Route::prefix('admin')->middleware('profile:Admin,Sales')->group(function () {

        Route::get('/doctors', DoctorsPage::class)->name('doctors');

        Route::get('/plans', PlansPage::class)->name('plans');

        Route::get('/preregistrations', PolicyPreregistrationsPage::class)->name('preregistrations');

        Route::get('/policies', PoliciesPage::class)->name('policies');

        Route::get('/appointments', AppointmentsPage::class)->name('appointments');

        Route::get('/offices', OfficesPage::class)->name('offices');

        Route::get('/services', ServicesPage::class)->name('services');

        Route::get('/specialties', SpecialtiesPage::class)->name('specialties');

        Route::get('/users', UsersPage::class)->middleware('admin')->name('users');

        Route::get('/settings/whatsapp', WhatsAppSettingsPage::class)->middleware('admin')->name('settings.whatsapp');
        Route::get('/settings/legal', LegalSettingsPage::class)->middleware('admin')->name('settings.legal');
        Route::get('/settings/parameters', ParametersPage::class)->middleware('admin')->name('settings.parameters');

    });

    Route::prefix('user')->middleware('profile:User,Admin')->group(function () {
        Route::get('/home', HomePage::class)->name('user.home');

        Route::get('/schedule', SchedulePage::class)->name('user.schedule');
        Route::get('/schedule-confirmation', ScheduleConfirmationPage::class)->name('user.schedule-confirmation');
        Route::get('/schedule-cancellation', ScheduleCancellationPage::class)->name('user.schedule-cancellation');

        Route::get('/status', PolicyStatusPage::class)->name('user.status');

        Route::get('/record', RecordPage::class)->name('user.record');

        Route::get('/history', HistoryPage::class)->name('user.history');

        Route::get('/notes/{appointment}', NotesPage::class)->name('user.notes');

        Route::get('/rating/{appointment}', RatingPage::class)->name('user.rating');
        Route::get('/rating-confirmation', RatingConfirmationPage::class)->name('user.rating-confirmation');

        Route::get('/my-profile', ProfilePage::class)->name('user.my-profile');
    });

    Route::prefix('doctor')->middleware('profile:Doctor,Admin')->group(function () {

        Route::get('/home', DRHomePage::class)->name('doctor.home');

        Route::get('/schedule/{appointment}', DRSchedulePage::class)->name('doctor.schedule');
        Route::get('/schedule-confirmation', DRScheduleConfirmationPage::class)->name('doctor.schedule-confirmation');

        Route::get('/history', DRHistoryPage::class)->name('doctor.history');
        Route::get('/history/note/{appointment}', DRHistoryNotePage::class)->name('history.notes');

        Route::get('/requests', DRRequestsPage::class)->name('doctor.requests');

        Route::get('/record/{user}', DRRecordPage::class)->name('doctor.record');

        Route::get('/notes/{appointment}', DRNotesPage::class)->name('doctor.notes');
        Route::get('/notes-confirmation', NotesConfirmationPage::class)->name('doctor.notes-confirmation');

        Route::get('/noshow-confirmation', NoShowConfirmationPage::class)->name('doctor.noshow-confirmation');
        Route::get('/accept-confirmation', AcceptConfirmationPage::class)->name('doctor.accept-confirmation');
        Route::get('/reject-confirmation', RejectConfirmationPage::class)->name('doctor.reject-confirmation');

        Route::get('/my-profile', DRProfilePage::class)->name('doctor.my-profile');
    });

    Route::prefix('clerk')->middleware('profile:Clerk')->group(function () {
        Route::get('/dashboard', ClerkDashboardPage::class)->name('clerk.dashboard');
        Route::get('/dispensation', DispensationPage::class)->name('clerk.dispensation');
        Route::get('/inventory', InventoryPage::class)->name('clerk.inventory');
    });
});
