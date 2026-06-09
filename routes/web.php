<?php

// Facades
use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Auth\AdminAuthenticatedSessionController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\ReceptionistTicketController;
use App\Http\Controllers\WhatsAppWebhookController;

// Livewire - Appointments
use App\Livewire\Appointments\AppointmentsPage;

// Livewire - Auth
use App\Livewire\Auth\ForgotPinPage;
use App\Livewire\Auth\PinSetupPage;

// Livewire - Coupons
use App\Livewire\Coupons\CouponsPage;

// Livewire - Doctors
use App\Livewire\Doctors\DoctorsPage;

// Livewire - Home
use App\Livewire\Home\DashboardPage;

// Livewire - Medications
use App\Livewire\Medications\MedicationsPage;

// Livewire - Clerk
use App\Livewire\Clerk\DashboardPage as ClerkDashboardPage;
use App\Livewire\Clerk\DispensationPage;
use App\Livewire\Clerk\InventoryPage;

// Livewire - Receptionist
use App\Livewire\Receptionist\AppointmentsPage as ReceptionistAppointmentsPage;
use App\Livewire\Receptionist\PendingResultsPage as ReceptionistPendingResultsPage;
use App\Livewire\Receptionist\PaymentPage as ReceptionistPaymentPage;
use App\Livewire\Receptionist\RequestsPage as ReceptionistRequestsPage;

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
use App\Livewire\WhatsApp\WhatsAppConsolePage;

// Livewire - Mobile
use App\Livewire\Mobile\ContactPage;

// Livewire - Mobile - Doctor
use App\Livewire\Mobile\Doctor\AcceptConfirmationPage;
use App\Livewire\Mobile\Doctor\DRHistoryNotePage;
use App\Livewire\Mobile\Doctor\DRHistoryPage;
use App\Livewire\Mobile\Doctor\DRHomePage;
use App\Livewire\Mobile\Doctor\DRNotesPage;
use App\Livewire\Mobile\Doctor\DRResultsPendingPage;
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

//Livewire - Reports - Commissions
use App\Livewire\Reports\CommissionsPage;
use App\Livewire\Reports\SalesPage;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('webhooks.whatsapp.verify');
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'receive'])->name('webhooks.whatsapp.receive');

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

        Route::get('/appointments', AppointmentsPage::class)->name('appointments');

        Route::get('/coupons', CouponsPage::class)->name('coupons');

        Route::get('/doctors', DoctorsPage::class)->name('doctors');

        Route::get('/medications', MedicationsPage::class)->name('medications');

        Route::get('/offices', OfficesPage::class)->name('offices');

        Route::get('/plans', PlansPage::class)->name('plans');

        Route::get('/preregistrations', PolicyPreregistrationsPage::class)->name('preregistrations');

        Route::get('/policies', PoliciesPage::class)->name('policies');

        Route::get('/reports/commissions', CommissionsPage::class)->name('reports.commissions');
        Route::get('/reports/sales', SalesPage::class)->name('reports.sales');

        Route::get('/services', ServicesPage::class)->name('services');

        Route::get('/specialties', SpecialtiesPage::class)->name('specialties');

        Route::get('/users', UsersPage::class)->middleware('admin')->name('users');
        Route::get('/whatsapp/console', WhatsAppConsolePage::class)->middleware('admin')->name('whatsapp.console');

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
        Route::get('/results-pending', DRResultsPendingPage::class)->name('doctor.results-pending');

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
        Route::get('/medications', MedicationsPage::class)->name('clerk.medications');
    });

    Route::prefix('receptionist')->middleware('profile:Receptionist')->group(function () {
        Route::get('/appointments', ReceptionistAppointmentsPage::class)->name('receptionist.appointments');
        Route::get('/pending-results', ReceptionistPendingResultsPage::class)->name('receptionist.pending-results');
        Route::get('/requests', ReceptionistRequestsPage::class)->name('receptionist.requests');
        Route::get('/payment/{appointment}', ReceptionistPaymentPage::class)->name('receptionist.payment');
        Route::get('/payment/{appointment}/ticket', ReceptionistTicketController::class)->name('receptionist.payment.ticket');
    });
});
