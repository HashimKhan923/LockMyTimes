<?php

use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\SubscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes — Marketing Website + Checkout
|--------------------------------------------------------------------------
*/

// Marketing home page
Route::get('/', [HomeController::class, 'index'])->name('home');

// Checkout flow
Route::post('/checkout', [SubscriptionController::class, 'checkout'])->name('checkout');
Route::get('/checkout/success', [SubscriptionController::class, 'success'])->name('checkout.success');

// Stripe webhook — must be CSRF-exempt (handled below in bootstrap/app.php)
Route::post('/stripe/webhook', [SubscriptionController::class, 'webhook'])
    ->name('stripe.webhook')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);