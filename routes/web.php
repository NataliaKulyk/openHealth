<?php

declare(strict_types=1);

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Actions\Logout;
use App\Livewire\Auth\VerifyEmail;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\ForgotPassword;
use App\Http\Controllers\Auth\EHealthLoginController;
use App\Livewire\Employee\EmployeeEdit;
use App\Livewire\Employee\EmployeeShow;
use App\Livewire\Employee\EmployeeIndex;
use App\Livewire\Employee\EmployeeCreate;
use App\Livewire\Employee\EmployeePositionAdd;
use App\Livewire\Employee\EmployeeRequestEdit;
use App\Livewire\Employee\EmployeeRequestShow;
use App\Models\License;
use App\Livewire\License\LicenseEdit;
use App\Livewire\License\LicenseView;
use App\Livewire\License\LicenseCreate;
use App\Livewire\Patient\PatientComponent;
use App\Livewire\DiagnosticReport\DiagnosticReportCreate;
use App\Livewire\Procedure\ProcedureCreate;
use App\Models\LegalEntity;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use App\Livewire\Patient\PatientIndex;
use App\Livewire\Contract\ContractForm;
use App\Livewire\Division\DivisionForm;
use App\Livewire\Auth\SelectLegalEntity;
use App\Livewire\Contract\ContractIndex;
use App\Http\Controllers\HomeController;
use App\Livewire\Division\DivisionIndex;
use App\Http\Controllers\EmailController;
use App\Livewire\Encounter\EncounterEdit;
use App\Livewire\Encounter\EncounterCreate;
use App\Livewire\LegalEntity\EditLegalEntity;
use App\Livewire\License\LicenseIndex;
use App\Livewire\Patient\Records\PatientData;
use App\Livewire\Declaration\DeclarationIndex;
use App\Livewire\LegalEntity\CreateLegalEntity;
use App\Livewire\Patient\Records\PatientSummary;
use App\Livewire\Division\HealthcareServiceForm;
use App\Livewire\Patient\Records\PatientEpisodes;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Livewire\Dashboard;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [HomeController::class, 'index'])->name('home.index');
Route::post('/send-email', [EmailController::class, 'sendEmail'])->name('send.email');

/* Auth */

Route::get('/ehealth/oauth/', EHealthLoginController::class)->name('ehealth.oauth.callback');

Route::middleware('guest')->group(function () {
    Route::get('login', Login::class)->name('login');
    Route::get('register', Register::class)->name('register');
    Route::get('forgot-password', ForgotPassword::class)->name('forgot.password');
    Route::get('reset-password/{token}', ResetPassword::class)->name('password.reset');

    Route::get('verify-email', VerifyEmail::class)->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');
});

Route::post('logout', Logout::class)->name('logout');

/* Dashboard */

Route::middleware(['auth:web,ehealth', 'verified'])->group(function () {

    Route::get('/select-legal-entity', SelectLegalEntity::class)->name('legalEntity.select');

    Route::get('/dashboard/legal-entities/create', CreateLegalEntity::class)
        ->middleware(['auth:web', 'can:create,' . LegalEntity::class])
        ->name('legal-entity.new.create');

    Route::middleware(['can:access,legalEntity'])->prefix('/dashboard/{legalEntity}')->whereNumber('legalEntity')->group(function () {

        Route::get('/', Dashboard::class)->name('dashboard');

        Route::get('/edit', EditLegalEntity::class)->name('legal-entity.edit');

        // TODO: Should determine if this need to be implemented!
        Route::get('/create', CreateLegalEntity::class)
            ->middleware(['can:create,' . LegalEntity::class])
            ->name('legal-entity.create');

        Route::prefix('division')->group(function () {
            Route::get('/', DivisionIndex::class)->name('division.index');
            Route::get('/form/{id?}', DivisionForm::class)->name('division.form');
            Route::get('/{division}/healthcare-service', HealthcareServiceForm::class)->name('healthcare_service.index');
        });

        Route::prefix('employee')->name('employee.')->middleware('auth')->group(function () {
            Route::get('/', EmployeeIndex::class)->name('index');

            Route::get('/{employee}',EmployeeShow::class)
                ->name('show')->middleware('can:view,employee');

            Route::get('/{employee}/edit', EmployeeEdit::class)
                ->name('edit')->middleware('can:update,employee');
        });

        // --- Group for Employee Requests ---
        Route::prefix('employee-request')->name('employee-request.')->middleware('auth')->group(function () {
            Route::get('/create', EmployeeCreate::class)->name('create');
            Route::get('/party/{party}/position-add', EmployeePositionAdd::class)->name('position-add');

            Route::get('/{employee_request}', EmployeeRequestShow::class)
                ->name('show')->middleware('can:view,employee_request');

            Route::get('/{employee_request}/edit',EmployeeRequestEdit::class)
                ->name('edit')->middleware('can:update,employee_request');
        });

        Route::prefix('contract')->group(function () {
            Route::get('/', ContractIndex::class)->name('contract.index');
            Route::get('/form/{id?}', ContractForm::class)->name('contract.form');
        });


        // Routes related to legal entity licenses; primary license can't be edited
        Route::prefix('license')->middleware(['permission:license:read|license:write'])->group(function () {

            Route::get('/', LicenseIndex::class)->name('license.index')->middleware('permission:license:read');
            Route::get('/create', LicenseCreate::class)->name('license.create')->middleware('permission:license:write');

            Route::middleware(['can:access,license'])->prefix('{license}')->whereNumber('license')->group(function () {
                Route::get('/', function (LegalEntity $legalEntity, License $license) {
                    if (Gate::allows('write', [$license, $legalEntity]) && !$license->isPrimary) {
                        return App::call(LicenseEdit::class, [$legalEntity, $license]);
                    } else if (Gate::allows('access', [$license, $legalEntity])) {
                        return App::call(LicenseView::class, [$legalEntity, $license]);
                    }
                })->name('license.view');
            });
        });

        Route::prefix('declaration')->group(function () {
            Route::get('/', DeclarationIndex::class)->name('declaration.index');
        });

        Route::group(['middleware' => ['role:OWNER|ADMIN|DOCTOR']], static function () {
            Route::prefix('patient')->group(static function () {
                Route::get('/', PatientIndex::class)->name('patient.index');
                Route::get('/create/{patientId?}', PatientComponent::class)->name('patient.form');
                Route::get('/{patientId}/patient-data', PatientData::class)->name('patient.patient-data');
                Route::get('/{patientId}/summary', PatientSummary::class)->name('patient.summary');
                Route::get('/{patientId}/episodes', PatientEpisodes::class)->name('patient.episodes');

                Route::get('/{patientId}/encounter/create', EncounterCreate::class)->name('encounter.create');
                Route::get('/{patientId}/encounter/{encounterId}', EncounterEdit::class)->name('encounter.edit');

                Route::get('/{patientId}/diagnostic-report/create', DiagnosticReportCreate::class)->name('diagnostic-report.create');

                Route::get('/{patientId}/procedure/create', ProcedureCreate::class)->name('procedure.create');
            });
        });
    });
});

Route::get('/page-not-found', fn () => view('errors.404'))->name('url.page-not-found');

/*
 * GLOBAL FALLBACK ROUTE (MUST BE LAST IN web.php)
 * This Route::fallback() will trigger for ANY request that has not been matched by any route above.
 * This is final 404 handler for both authenticated and unauthenticated users,
 * or for routes that simply do not fit into any structured groups.
 */
Route::fallback(fn () => redirect()->route('url.page-not-found'));
