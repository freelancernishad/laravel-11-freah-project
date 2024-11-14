<?php

namespace App\Http\Controllers\Api\Gateway\Stripe;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Stripe\Webhook;
use Illuminate\Support\Facades\Validator;

class StripeController extends Controller
{
    // Set up Stripe API key
    public function __construct()
    {
        Stripe::setApiKey(config('STRIPE_SECRET'));
    }

    // Create a payment session for Stripe Checkout
    public function createCheckoutSession(Request $request)
    {
        // Validate incoming data
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|string|max:3',
            'user_id' => 'required|exists:users,id',
            'success_url' => 'required|url',
            'cancel_url' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Pass only validated data to the helper function
        return createStripeCheckoutSession($validator->validated());
    }
    // Handle Stripe Webhook
    public function handleWebhook(Request $request)
    {
        // Secret key for Stripe Webhook signature verification
        $endpoint_secret = config('STRIPE_WEBHOOK_SECRET');

        // Get raw body and signature header
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');

        try {
            // Verify webhook signature
            $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);

            // Handle different event types
            switch ($event->type) {
                case 'checkout.session.completed':
                    $session = $event->data->object; // Contains \Stripe\Checkout\Session

                    // Find the payment record and update status
                    $payment = Payment::where('transaction_id', $session->id)->first();
                    if ($payment) {
                        $payment->update([
                            'status' => 'completed',
                            'paid_at' => now(),
                            'response_data' => json_encode($session),
                        ]);
                    }
                    break;

                case 'payment_intent.succeeded':
                    // Handle successful payment
                    $paymentIntent = $event->data->object; // Contains \Stripe\PaymentIntent
                    $payment = Payment::where('transaction_id', $paymentIntent->id)->first();
                    if ($payment) {
                        $payment->update([
                            'status' => 'completed',
                            'paid_at' => now(),
                        ]);
                    }
                    break;

                case 'payment_intent.payment_failed':
                    // Handle failed payment
                    $paymentIntent = $event->data->object;
                    $payment = Payment::where('transaction_id', $paymentIntent->id)->first();
                    if ($payment) {
                        $payment->update([
                            'status' => 'failed',
                        ]);
                    }
                    break;

                // Handle other events as needed

                default:
                    // Unexpected event type
                    return response()->json(['message' => 'Event type not handled'], 400);
            }

            // Respond to Stripe that the webhook was received successfully
            return response()->json(['message' => 'Webhook handled'], 200);

        } catch (\Exception $e) {
            // If there is an error with the webhook or signature verification
            return response()->json(['error' => 'Webhook Error: ' . $e->getMessage()], 400);
        }
    }

    // Create a PaymentIntent (for processing payment)
    public function createPaymentIntent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|string|max:3',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        try {
            // Create PaymentIntent with Stripe
            $paymentIntent = PaymentIntent::create([
                'amount' => $validatedData['amount'] * 100, // Amount in cents
                'currency' => $validatedData['currency'],
                'payment_method_types' => ['card'],
            ]);

            // Respond with the client secret for the frontend to use
            return response()->json(['client_secret' => $paymentIntent->client_secret]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error creating PaymentIntent: ' . $e->getMessage()], 500);
        }
    }

    // Confirm the payment with a PaymentIntent
    public function confirmPaymentIntent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required|string',
            'payment_method_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        try {
            // Confirm the payment with the provided payment method ID
            $paymentIntent = PaymentIntent::retrieve($validatedData['payment_intent_id']);
            $paymentIntent->confirm([
                'payment_method' => $validatedData['payment_method_id'],
            ]);

            // Respond with the payment status
            return response()->json(['status' => $paymentIntent->status]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error confirming PaymentIntent: ' . $e->getMessage()], 500);
        }
    }
}

