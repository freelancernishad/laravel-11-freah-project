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

class StripeSubscriptionController extends Controller
{
    public function __construct()
    {
        // Set the API key for Stripe (can be set in .env or config/services.php)
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
    }

    // Check the status of a user's subscription by userPackage ID
    public function checkSubscriptionStatus($userPackageId)
    {
        // Find the UserPackage by ID
        $userPackage = UserPackage::find($userPackageId);

        if (!$userPackage) {
            return response()->json([
                'message' => 'No subscription found for this package.',
            ], 400);
        }

        try {
            // Retrieve the subscription from Stripe
            $subscription = Subscription::retrieve($userPackage->stripe_subscription_id);

            // Return the status of the subscription
            return response()->json([
                'status' => $subscription->status,
            ], 200);
        } catch (Exception $e) {
            Log::error('Stripe Subscription Status Error: ' . $e->getMessage());

            return response()->json([
                'message' => 'There was an error retrieving the subscription status.',
            ], 500);
        }
    }

    // Cancel an existing subscription by userPackage ID
    public function cancelSubscription(Request $request, $userPackageId)
    {
        // Find the UserPackage by ID
        $userPackage = UserPackage::find($userPackageId);

        if (!$userPackage || $userPackage->status !== 'active') {
            return response()->json([
                'message' => 'No active subscription found for this package.',
            ], 400);
        }

        try {
            // Retrieve the subscription from Stripe
            $subscription = Subscription::retrieve($userPackage->stripe_subscription_id);

            // Check the status of the subscription from Stripe
            if ($subscription->status === 'canceled') {
                return response()->json([
                    'message' => 'The subscription is already canceled.',
                ], 400);
            }

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

    // Reactivate a canceled subscription by userPackage ID
    public function reactivateSubscription(Request $request, $userPackageId)
    {
        // Find the UserPackage by ID
        $userPackage = UserPackage::find($userPackageId);

        if (!$userPackage || $userPackage->status !== 'canceled') {
            return response()->json([
                'message' => 'No canceled subscription found for this package.',
            ], 400);
        }

        try {
            // Retrieve the subscription from Stripe
            $subscription = Subscription::retrieve($userPackage->stripe_subscription_id);

            // Check if the subscription is already active
            if ($subscription->status === 'active') {
                return response()->json([
                    'message' => 'The subscription is already active.',
                ], 400);
            }

            // Reactivate the subscription in Stripe
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
