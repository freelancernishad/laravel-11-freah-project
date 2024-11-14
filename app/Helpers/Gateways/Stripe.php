<?php
use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;


if (!function_exists('createStripeCheckoutSession')) {
    /**
     * Create a Stripe Checkout Session
     *
     * @param array $data
     * @param string $successUrl
     * @param string $cancelUrl
     * @return JsonResponse
     */
    function createStripeCheckoutSession(array $data): JsonResponse
    {
        // Assign default values using the null coalescing operator
        $amount = $data['amount'] ?? 100; // Default to 100 cents ($1.00) if not provided
        $currency = $data['currency'] ?? 'USD'; // Default currency is USD
        $userId = $data['user_id'] ?? null; // user_id is required and should be validated before calling this function
        $baseSuccessUrl = $data['success_url'] ?? 'http://localhost:8000/stripe/payment/success'; // Base success URL
        $baseCancelUrl = $data['cancel_url'] ?? 'http://localhost:8000/stripe/payment/cancel'; // Base cancel URL

        // Create a Payment record (set status as 'pending')
        $payment = Payment::create([
            'user_id' => $userId,
            'gateway' => 'stripe',
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'transaction_id' => uniqid(),
        ]);

        try {
            // Initialize Stripe with API key
            Stripe::setApiKey(config('STRIPE_SECRET'));

            // Add payment_id and session_id placeholders to URLs
            $successUrl = "{$baseSuccessUrl}?payment_id={$payment->id}&session_id={CHECKOUT_SESSION_ID}";
            $cancelUrl = "{$baseCancelUrl}?payment_id={$payment->id}&session_id={CHECKOUT_SESSION_ID}";

            // Create Stripe Checkout session
            $session = Session::create([
                'payment_method_types' => ['card', 'amazon_pay', 'us_bank_account'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => $currency,
                            'product_data' => [
                                'name' => 'Payment for User #' . $userId,
                            ],
                            'unit_amount' => $amount * 100,
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
            ]);

            // Update the payment with the Stripe session ID
            $payment->update(['transaction_id' => $session->id]);

            // Return the session URL for frontend redirection
            return response()->json(['session_url' => $session->url]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
