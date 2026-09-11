<?php

use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\LoginRedirectController;
use App\Http\Controllers\Public\SubscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes — Marketing Website + Checkout
|--------------------------------------------------------------------------
*/

// Marketing home page
Route::get('/', [HomeController::class, 'index'])->name('home');

// "Login" on the marketing site — routes a visitor to their own company's
// portal/admin login (or asks which company, once) rather than living here.
Route::get('/login/employee', [LoginRedirectController::class, 'employee'])->name('login.employee');
Route::get('/login/admin',    [LoginRedirectController::class, 'admin'])->name('login.admin');
Route::post('/login/resolve', [LoginRedirectController::class, 'resolve'])->name('login.resolve');

// Policy pages
Route::view('/terms', 'public.terms')->name('terms');
Route::view('/privacy', 'public.privacy')->name('privacy');

// Checkout flow
Route::post('/checkout', [SubscriptionController::class, 'checkout'])->name('checkout');
Route::get('/checkout/success', [SubscriptionController::class, 'success'])->name('checkout.success');

// Stripe webhook — must be CSRF-exempt (handled below in bootstrap/app.php)
Route::post('/stripe/webhook', [SubscriptionController::class, 'webhook'])
    ->name('stripe.webhook')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);