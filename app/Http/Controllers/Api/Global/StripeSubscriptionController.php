<?php

namespace App\Http\Controllers;

use Exception;
use Stripe\Stripe;
use App\Models\User;
use App\Models\Package;
use Stripe\Subscription;
use App\Models\UserPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class StripeSubscriptionController extends Controller
{
    public function __construct()
    {
        // Set the API key for Stripe (can be set in .env or config/services.php)
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
    }


    // Cancel an existing subscription
    public function cancelSubscription(Request $request)
    {
        $user = Auth::user();

        // Find the user's active package (if any)
        $userPackage = $user->userPackage;

        if (!$userPackage || $userPackage->status !== 'active') {
            return response()->json([
                'message' => 'No active subscription found.',
            ], 400);
        }

        try {
            // Retrieve the subscription from Stripe
            $subscription = Subscription::retrieve($userPackage->stripe_subscription_id);

            // Cancel the subscription
            $subscription->cancel();

            // Mark the UserPackage as canceled
            $userPackage->update([
                'status' => 'canceled',
                'canceled_at' => now(),
            ]);

            return response()->json([
                'message' => 'Subscription canceled successfully.',
            ], 200);
        } catch (Exception $e) {
            Log::error('Stripe Subscription Cancellation Error: ' . $e->getMessage());

            return response()->json([
                'message' => 'There was an error canceling the subscription.',
            ], 500);
        }
    }

    // Reactivate a canceled subscription
    public function reactivateSubscription(Request $request)
    {
        $user = Auth::user();

        // Find the user's canceled package (if any)
        $userPackage = $user->userPackage;

        if (!$userPackage || $userPackage->status !== 'canceled') {
            return response()->json([
                'message' => 'No canceled subscription found.',
            ], 400);
        }

        try {
            // Reactivate the subscription in Stripe
            $subscription = Subscription::retrieve($userPackage->stripe_subscription_id);
            $subscription->resume();

            // Update the UserPackage to active
            $userPackage->update([
                'status' => 'active',
                'canceled_at' => null,
            ]);

            return response()->json([
                'message' => 'Subscription reactivated successfully.',
            ], 200);
        } catch (Exception $e) {
            Log::error('Stripe Subscription Reactivation Error: ' . $e->getMessage());

            return response()->json([
                'message' => 'There was an error reactivating the subscription.',
            ], 500);
        }
    }
}
