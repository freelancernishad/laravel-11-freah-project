<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Gateway\Stripe\StripeController;
use App\Http\Controllers\StripeSubscriptionController;


Route::post('/stripe/create-checkout-session', [StripeController::class, 'createCheckoutSession']);
Route::post('/stripe/webhook', [StripeController::class, 'handleWebhook']);


Route::post('/stripe/create-payment-intent', [StripeController::class, 'createPaymentIntent']);
Route::post('/stripe/confirm-payment-intent', [StripeController::class, 'confirmPaymentIntent']);





    // Route to cancel the subscription
    Route::post('subscription/cancel', [StripeSubscriptionController::class, 'cancelSubscription']);

    // Route to reactivate a canceled subscription
    Route::post('subscription/reactivate', [StripeSubscriptionController::class, 'reactivateSubscription']);

