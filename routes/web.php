<?php

// Facades
use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Auth\AdminAuthenticatedSessionController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\ReceptionistTicketController;
use App\Http\Controllers\WhatsAppMediaAttachmentController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Controllers\ProfileSelectionController;

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
use App\Livewire\Clerk\SuppliersPage;
use App\Livewire\Clerk\PurchasesPage;

// Livewire - Receptionist
use App\Livewire\Receptionist\AppointmentsPage as ReceptionistAppointmentsPage;
use App\Livewire\Receptionist\PendingResultsPage as ReceptionistPendingResultsPage;
use App\Livewire\Receptionist\PaymentPage as ReceptionistPaymentPage;
use App\Livewire\Receptionist\RequestsPage as ReceptionistRequestsPage;

// Livewire - Offices
use App\Livewire\Offices\OfficesPage;

// Livewire - Plans
use App\Livewire\Plans\PlansPage;
use App\Livewire\Permissions\PermissionsPage;

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
use App\Livewire\Mobile\User\AmbulancePage;
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

    // Multi-profile selector routes
    Route::get('/login/profiles', [ProfileSelectionController::class, 'index'])->name('login.profiles');
    Route::get('/login/profiles/{id}', [ProfileSelectionController::class, 'select'])->name('login.profiles.select');
});

Route::get('/contact', ContactPage::class)->name('user.contact');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    Route::get('/dashboard', DashboardPage::class)
        ->middleware('profile:Admin,Sales')
        ->middleware('permission:view.dashboard')
        ->name('dashboard');

    Route::get('/attachment/{note_id}', [AttachmentController::class, 'download'])->name('attachment.download');
    Route::get('/attachment/{service_id}/preview', [AttachmentController::class, 'preview'])->name('attachment.preview');
    Route::get('/external-service/{external_service_id}', [AttachmentController::class, 'downloadExternalService'])->name('external-service.download');

    Route::prefix('admin')->middleware('profile:Admin,Sales')->group(function () {

        Route::get('/appointments', AppointmentsPage::class)
            ->middleware('permission:view.admin.appointments')
            ->name('appointments');

        Route::get('/coupons', CouponsPage::class)
            ->middleware('permission:view.settings.coupons')
            ->name('coupons');

        Route::get('/doctors', DoctorsPage::class)
            ->middleware('permission:view.admin.doctors')
            ->name('doctors');

        Route::get('/offices', OfficesPage::class)
            ->middleware('permission:view.settings.offices')
            ->name('offices');

        Route::get('/plans', PlansPage::class)
            ->middleware('permission:view.settings.plans')
            ->name('plans');

        Route::get('/preregistrations', PolicyPreregistrationsPage::class)
            ->middleware('permission:view.admin.preregistrations')
            ->name('preregistrations');

        Route::get('/policies', PoliciesPage::class)
            ->middleware('permission:view.admin.policies')
            ->name('policies');

        Route::get('medications', MedicationsPage::class)
            ->middleware('permission:view.clerk.medications')
            ->name('clerk.medications');
        Route::get('suppliers', SuppliersPage::class)
            ->middleware('permission:view.clerk.suppliers')
            ->name('clerk.suppliers');
        Route::get('purchases', PurchasesPage::class)
            ->middleware('permission:view.clerk.purchases')
            ->name('clerk.purchases');

        Route::get('/reports/commissions', CommissionsPage::class)
            ->middleware('permission:view.reports.commissions')
            ->name('reports.commissions');
        Route::get('/reports/sales', SalesPage::class)
            ->middleware('permission:view.reports.sales')
            ->name('reports.sales');

        Route::get('/services', ServicesPage::class)
            ->middleware('permission:view.settings.services')
            ->name('services');

        Route::get('/specialties', SpecialtiesPage::class)
            ->middleware('permission:view.settings.specialties')
            ->name('specialties');

        Route::get('/users', UsersPage::class)
            ->middleware('admin')
            ->middleware('permission:view.admin.users')
            ->name('users');
        Route::get('/whatsapp/console', WhatsAppConsolePage::class)
            ->middleware('admin')
            ->middleware('permission:view.admin.whatsapp_console')
            ->name('whatsapp.console');
        Route::get('/whatsapp/attachments/{attachment}/preview', [WhatsAppMediaAttachmentController::class, 'preview'])
            ->middleware('admin')
            ->middleware('permission:view.admin.whatsapp_console')
            ->name('whatsapp.attachments.preview');
        Route::get('/whatsapp/attachments/{attachment}/download', [WhatsAppMediaAttachmentController::class, 'download'])
            ->middleware('admin')
            ->middleware('permission:view.admin.whatsapp_console')
            ->name('whatsapp.attachments.download');

        Route::get('/settings/whatsapp', WhatsAppSettingsPage::class)
            ->middleware('admin')
            ->middleware('permission:view.settings.whatsapp')
            ->name('settings.whatsapp');
        Route::get('/settings/legal', LegalSettingsPage::class)
            ->middleware('admin')
            ->middleware('permission:view.settings.legal')
            ->name('settings.legal');
        Route::get('/settings/parameters', ParametersPage::class)
            ->middleware('admin')
            ->middleware('permission:view.settings.parameters')
            ->name('settings.parameters');
        Route::get('/settings/permissions', PermissionsPage::class)
            ->middleware('admin')
            ->middleware('permission:view.settings.permissions')
            ->name('settings.permissions');
    });

    Route::prefix('user')->middleware('profile:User,Admin')->group(function () {
        Route::get('/home', HomePage::class)->name('user.home');

        Route::get('/schedule', SchedulePage::class)->name('user.schedule');
        Route::get('/schedule-confirmation', ScheduleConfirmationPage::class)->name('user.schedule-confirmation');
        Route::get('/schedule-cancellation', ScheduleCancellationPage::class)->name('user.schedule-cancellation');

        Route::get('/ambulance', AmbulancePage::class)->name('user.ambulance');

        Route::get('/status', PolicyStatusPage::class)->name('user.status');

        Route::get('/record', RecordPage::class)->name('user.record');

        Route::get('/history', HistoryPage::class)->name('user.history');

        Route::get('/notes/{appointment}', NotesPage::class)->name('user.notes');

        Route::get('/rating/{appointment}', RatingPage::class)->name('user.rating');
        Route::get('/rating-confirmation', RatingConfirmationPage::class)->name('user.rating-confirmation');

        Route::get('/my-profile', ProfilePage::class)->name('user.my-profile');
    });

    Route::prefix('doctor')->middleware('profile:Doctor,Admin')->group(function () {

        Route::get('/home', DRHomePage::class)
            ->middleware('permission:view.doctor.home')
            ->name('doctor.home');

        Route::get('/schedule/{appointment}', DRSchedulePage::class)->name('doctor.schedule');
        Route::get('/schedule-confirmation', DRScheduleConfirmationPage::class)->name('doctor.schedule-confirmation');

        Route::get('/notes/{appointment}/edit', DRNotesPage::class)
            ->middleware('permission:edit.doctor.appointments')
            ->name('doctor.notes.edit');

        Route::get('/history', DRHistoryPage::class)
            ->middleware('permission:view.doctor.history')
            ->name('doctor.history');
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
        Route::get('/dispensation', DispensationPage::class)
            ->middleware('permission:view.clerk.dispensation')
            ->name('clerk.dispensation');
        Route::get('/inventory', InventoryPage::class)->name('clerk.inventory');
    });

    Route::prefix('receptionist')->middleware('profile:Receptionist,Doctor')->group(function () {
        Route::get('/policies', PoliciesPage::class)
            ->middleware('permission:view.receptionist.policies')
            ->name('recepcionist.policies');
        Route::get('/appointments', ReceptionistAppointmentsPage::class)
            ->middleware('permission:view.receptionist.appointments')
            ->name('receptionist.appointments');
        Route::get('/pending-results', ReceptionistPendingResultsPage::class)
            ->middleware('permission:view.receptionist.pending_results')
            ->name('receptionist.pending-results');
        Route::get('/requests', ReceptionistRequestsPage::class)
            ->middleware('permission:view.receptionist.requests')
            ->name('receptionist.requests');
        Route::get('/payment/{appointment}', ReceptionistPaymentPage::class)
            ->middleware('permission:view.receptionist.appointments')
            ->name('receptionist.payment');
        Route::get('/payment/{appointment}/{type}/ticket', ReceptionistTicketController::class)
            ->middleware('permission:view.receptionist.appointments')
            ->name('receptionist.payment.ticket');

        // PARCHE GACHO
        Route::get('/dispensation', DispensationPage::class)
            ->name('recepcionist.dispensation');
    });
});
