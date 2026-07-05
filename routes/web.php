<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DownloadController as AdminDownloadController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Admin\PlanUpgradeRequestController as AdminPlanUpgradeRequestController;
use App\Http\Controllers\Admin\PlatformController as AdminPlatformController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PlisioWebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\UpgradeController;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');

Route::get('/', [DownloadController::class, 'home'])->name('home');
Route::post('/analyze', [DownloadController::class, 'analyze'])->name('analyze');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/refund', [PageController::class, 'refund'])->name('refund');
Route::get('/support', [SupportController::class, 'landing'])->name('support');

Route::post('/webhooks/plisio', [PlisioWebhookController::class, 'handle'])->name('webhooks.plisio');

Route::middleware(['auth'])->group(function () {
    Route::get('/account', [AccountController::class, 'index'])->name('account');
    Route::post('/account/upgrade-requests/{upgradeRequest}/dismiss', [AccountController::class, 'dismissUpgradeRequest'])
        ->name('account.upgrade-requests.dismiss');
    Route::post('/account/upgrade-requests/{upgradeRequest}/cancel-crypto', [AccountController::class, 'cancelCryptoUpgrade'])
        ->name('account.upgrade-requests.cancel-crypto');
    Route::get('/dashboard', fn () => redirect()->route('account'))->name('dashboard');

    Route::prefix('support')->name('support.')->group(function () {
        Route::get('/tickets', [SupportController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/create', [SupportController::class, 'create'])->name('tickets.create');
        Route::post('/tickets', [SupportController::class, 'store'])->name('tickets.store');
        Route::get('/tickets/{supportTicket}', [SupportController::class, 'show'])->name('tickets.show');
        Route::post('/tickets/{supportTicket}/messages', [SupportController::class, 'reply'])->name('tickets.messages.store');
    });

    Route::post('/downloads', [DownloadController::class, 'store'])
        ->middleware('quota')
        ->name('downloads.store');
    Route::get('/downloads/{download}', [DownloadController::class, 'show'])->name('downloads.show');
    Route::get('/downloads/{download}/file', [DownloadController::class, 'file'])
        ->name('downloads.file');

    Route::get('/upgrade', [UpgradeController::class, 'index'])->name('upgrade.index');
    Route::get('/upgrade/{plan}', [UpgradeController::class, 'show'])->name('upgrade.show');
    Route::get('/upgrade/{plan}/bank', [UpgradeController::class, 'bank'])->name('upgrade.bank');
    Route::post('/upgrade/{plan}/bank', [UpgradeController::class, 'storeBank'])->name('upgrade.bank.store');
    Route::post('/upgrade/{plan}/crypto', [UpgradeController::class, 'storeCrypto'])->name('upgrade.crypto.store');
    Route::get('/upgrade/{plan}/crypto/success', [UpgradeController::class, 'cryptoSuccess'])->name('upgrade.crypto.success');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('admin')
    ->middleware(['auth', 'admin'])
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::resource('users', AdminUserController::class)->only(['index', 'show', 'update']);
        Route::post('users/{user}/grant-complimentary', [AdminUserController::class, 'grantComplimentary'])->name('users.grant-complimentary');
        Route::post('users/{user}/grant-paid', [AdminUserController::class, 'grantPaidManual'])->name('users.grant-paid');
        Route::post('users/{user}/revoke-plan', [AdminUserController::class, 'revokePlan'])->name('users.revoke-plan');
        Route::post('downloads/{download}/retry', [AdminDownloadController::class, 'retry'])->name('downloads.retry');
        Route::resource('downloads', AdminDownloadController::class)->only(['index', 'show']);
        Route::get('upgrade-requests', [AdminPlanUpgradeRequestController::class, 'index'])->name('upgrade-requests.index');
        Route::post('upgrade-requests/{upgradeRequest}/approve', [AdminPlanUpgradeRequestController::class, 'approve'])->name('upgrade-requests.approve');
        Route::post('upgrade-requests/{upgradeRequest}/reject', [AdminPlanUpgradeRequestController::class, 'reject'])->name('upgrade-requests.reject');
        Route::resource('support-tickets', AdminSupportTicketController::class)->only(['index', 'show']);
        Route::post('support-tickets/{supportTicket}/messages', [AdminSupportTicketController::class, 'reply'])->name('support-tickets.messages.store');
        Route::post('support-tickets/{supportTicket}/close', [AdminSupportTicketController::class, 'close'])->name('support-tickets.close');
        Route::post('support-tickets/{supportTicket}/reopen', [AdminSupportTicketController::class, 'reopen'])->name('support-tickets.reopen');
        Route::resource('plans', AdminPlanController::class);
        Route::resource('platforms', AdminPlatformController::class)->only(['index', 'update']);
        Route::get('settings', [AdminSettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [AdminSettingController::class, 'update'])->name('settings.update');
        Route::post('settings/platform-cookies/{platform}', [AdminSettingController::class, 'updatePlatformCookies'])->name('settings.platform-cookies');
    });

require __DIR__.'/auth.php';
