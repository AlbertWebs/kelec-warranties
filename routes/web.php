<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ClaimController as AdminClaimController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DealerController;
use App\Http\Controllers\Admin\DemoDataController;
use App\Http\Controllers\Admin\DocumentDownloadController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OdooController;
use App\Http\Controllers\Admin\OdooProductSyncController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PurchaseSourceController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\LegalContentController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SmsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WarrantyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\CompleteRegistrationController;
use App\Http\Controllers\Public\ContentPageController;
use App\Http\Controllers\Public\MarketingConsentController;
use App\Http\Controllers\Public\WarrantyCertificateController;
use App\Http\Controllers\Public\WarrantyHubController;
use App\Http\Controllers\Public\WarrantyLookupController;
use App\Http\Controllers\Public\WarrantyRegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('public.home');
})->name('home');

Route::get('/privacy-policy', [ContentPageController::class, 'privacy'])->name('privacy-policy');
Route::get('/warranty-terms', [ContentPageController::class, 'terms'])->name('warranty-terms');
Route::view('/product-lookup', 'public.product-lookup.index')->name('product.lookup');
Route::view('/find-store', 'public.pages.find-store')->name('find-store');

Route::middleware('throttle:warranty-registration')->group(function () {
    Route::get('/register-warranty', [WarrantyRegistrationController::class, 'create'])->name('register-warranty.create');
    Route::post('/register-warranty/serial-check', [WarrantyRegistrationController::class, 'checkSerial'])->name('register-warranty.serial-check');
    Route::post('/register-warranty', [WarrantyRegistrationController::class, 'store'])->name('register-warranty.store');
    Route::get('/register-warranty/success/{reference}', [WarrantyRegistrationController::class, 'success'])->name('register-warranty.success');
});

Route::middleware('throttle:warranty-lookup')->group(function () {
    Route::get('/warranty-lookup', [WarrantyLookupController::class, 'create'])->name('warranty.lookup');
    Route::post('/warranty-lookup', [WarrantyLookupController::class, 'store'])->name('warranty.lookup.store');
    Route::post('/warranty-lookup/resend', [WarrantyLookupController::class, 'resend'])->name('warranty.lookup.resend');
});

Route::middleware('throttle:warranty-lookup')->group(function () {
    Route::get('/warranty', [WarrantyHubController::class, 'show'])->name('warranty.hub');
    Route::post('/warranty/claim/verify', [WarrantyHubController::class, 'verify'])->name('warranty.claim.verify');
    Route::post('/warranty/claim', [WarrantyHubController::class, 'store'])->name('warranty.claim.store');
    Route::post('/warranty/claim/reset', [WarrantyHubController::class, 'reset'])->name('warranty.claim.reset');
});

Route::get('/warranty/{reference}/certificate', [WarrantyCertificateController::class, 'show'])->name('warranty.certificate');
Route::get('/warranty/{reference}/certificate/download', [WarrantyCertificateController::class, 'download'])->name('warranty.certificate.download');

Route::middleware('throttle:warranty-lookup')->group(function () {
    Route::get('/consent/{token}', [MarketingConsentController::class, 'show'])->name('consent.show');
    Route::post('/consent/{token}', [MarketingConsentController::class, 'store'])->name('consent.store');
    Route::get('/complete-registration/{token}', [CompleteRegistrationController::class, 'show'])->name('complete-registration.show');
    Route::post('/complete-registration/{token}', [CompleteRegistrationController::class, 'store'])->name('complete-registration.store');
});

Route::get('/dashboard', function () {
    $user = auth()->user();

    if ($user && $user->hasAnyRole(['super_admin', 'warranty_admin', 'customer_support'])) {
        return redirect()->route('admin.dashboard');
    }

    return view('dashboard');
})->middleware(['auth', 'verified', 'active'])->name('dashboard');

Route::middleware(['auth', 'verified', 'active', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/warranties', [WarrantyController::class, 'index'])->name('warranties.index');
    Route::get('/warranties/pending', [WarrantyController::class, 'pending'])->name('warranties.pending');
    Route::get('/warranties/export', [WarrantyController::class, 'export'])->name('warranties.export');
    Route::get('/warranties/{warranty}', [WarrantyController::class, 'show'])->name('warranties.show');
    Route::put('/warranties/{warranty}', [WarrantyController::class, 'update'])->name('warranties.update');
    Route::post('/warranties/{warranty}/approve', [WarrantyController::class, 'approve'])->name('warranties.approve');
    Route::post('/warranties/{warranty}/reject', [WarrantyController::class, 'reject'])->name('warranties.reject');
    Route::post('/warranties/{warranty}/notes', [WarrantyController::class, 'addNote'])->name('warranties.notes');
    Route::post('/warranties/{warranty}/resend', [WarrantyController::class, 'resend'])->name('warranties.resend');

    Route::get('/claims', [AdminClaimController::class, 'index'])->name('claims.index');
    Route::get('/claims/{claim}', [AdminClaimController::class, 'show'])->name('claims.show');
    Route::put('/claims/{claim}', [AdminClaimController::class, 'update'])->name('claims.update');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');

    Route::resource('products', ProductController::class)->except(['show']);
    Route::resource('product-categories', ProductCategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('dealers', DealerController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('purchase-sources', PurchaseSourceController::class)->only(['index', 'store', 'update']);

    Route::get('/odoo', [OdooController::class, 'index'])->name('odoo.index');
    Route::post('/odoo/test-connection', [OdooController::class, 'testConnection'])->name('odoo.test');
    Route::post('/odoo/retry-failures', [OdooController::class, 'retryFailures'])->name('odoo.retry');
    Route::get('/odoo-products', [OdooProductSyncController::class, 'index'])->name('odoo.products.index');
    Route::post('/odoo-products/sync', [OdooProductSyncController::class, 'sync'])->name('odoo.products.sync');
    Route::post('/odoo-products/retry-pending', [OdooProductSyncController::class, 'retryPending'])->name('odoo.products.retry-pending');
    Route::post('/odoo-products/retry/{failure}', [OdooProductSyncController::class, 'retryFailure'])->name('odoo.products.retry-failure');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');

    Route::get('/sms', [SmsController::class, 'index'])->name('sms.index');
    Route::put('/sms/settings', [SmsController::class, 'updateSettings'])->name('sms.settings');
    Route::post('/sms/refresh-balance', [SmsController::class, 'refreshBalance'])->name('sms.refresh-balance');
    Route::post('/sms/test', [SmsController::class, 'sendTest'])->name('sms.test');
    Route::post('/sms/{smsLog}/delivery-report', [SmsController::class, 'deliveryReport'])->name('sms.delivery-report');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');

    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/test-email', [SettingsController::class, 'sendTestEmail'])->name('settings.test-email');
    Route::get('/legal-pages', [LegalContentController::class, 'edit'])->name('legal.edit');
    Route::put('/legal-pages', [LegalContentController::class, 'update'])->name('legal.update');

    Route::get('/documents/{document}/download', DocumentDownloadController::class)->name('documents.download');

    Route::get('/danger-zone', [DemoDataController::class, 'show'])->name('demo-data.show');
    Route::post('/danger-zone/seed', [DemoDataController::class, 'seed'])->name('demo-data.seed');
    Route::post('/danger-zone/wipe', [DemoDataController::class, 'wipe'])->name('demo-data.wipe');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/customer.php';
