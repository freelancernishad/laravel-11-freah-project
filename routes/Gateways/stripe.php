<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Gateway\Stripe\StripeController;
use App\Http\Controllers\StripeSubscriptionController;


Route::post('/stripe/create-checkout-session', [StripeController::class, 'createCheckoutSession']);
Route::post('/stripe/webhook', [StripeController::class, 'handleWebhook']);


Route::post('/stripe/create-payment-intent', [StripeController::class, 'createPaymentIntent']);
Route::post('/stripe/confirm-payment-intent', [StripeController::class, 'confirmPaymentIntent']);




// Check subscription status by userPackage ID
Route::get('/subscription/status/{userPackageId}', [StripeSubscriptionController::class, 'checkSubscriptionStatus']);

// Cancel subscription by userPackage ID
Route::post('/subscription/cancel/{userPackageId}', [StripeSubscriptionController::class, 'cancelSubscription']);

// Reactivate subscription by userPackage ID
Route::post('/subscription/reactivate/{userPackageId}', [StripeSubscriptionController::class, 'reactivateSubscription']);
