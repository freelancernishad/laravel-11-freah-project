<?php

namespace App\Http\Controllers\Api\Global;
use App\Http\Controllers\Controller;

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
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    // Check the status of a user's subscription by userPackage ID
    public function checkSubscriptionStatus($userPackageId)
    {
        $userPackage = UserPackage::find($userPackageId);

        if (!$userPackage) {
            return response()->json([
                'message' => 'No subscription found for this package.',
            ], 400);
        }

        try {
            // Retrieve the subscription from Stripe
            $subscription = Subscription::retrieve($userPackage->stripe_subscription_id);

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
        $userPackage = UserPackage::find($userPackageId);

        if (!$userPackage || $userPackage->status !== 'active') {
            return response()->json([
                'message' => 'No active subscription found for this package.',
            ], 400);
        }

        try {
            $subscription = Subscription::retrieve($userPackage->stripe_subscription_id);

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

    // Pause the subscription by userPackage ID
    public function pauseSubscription(Request $request, $userPackageId)
    {
        $userPackage = UserPackage::find($userPackageId);

        if (!$userPackage || $userPackage->status !== 'active') {
            return response()->json([
                'message' => 'No active subscription found for this package.',
            ], 400);
        }

        try {
            $subscription = Subscription::retrieve($userPackage->stripe_subscription_id);

            // Pause the subscription
            $subscription->pause();

            // Update the UserPackage status to 'paused'
            $userPackage->update([
                'status' => 'paused',
                'paused_at' => now(),
            ]);

            return response()->json([
                'message' => 'Subscription paused successfully.',
            ], 200);
        } catch (Exception $e) {
            Log::error('Stripe Subscription Pause Error: ' . $e->getMessage());

            return response()->json([
                'message' => 'There was an error pausing the subscription.',
            ], 500);
        }
    }

    // Reactivate a paused subscription or handle 'inactive' status by userPackage ID
    public function reactivateSubscription(Request $request, $userPackageId)
    {
        $userPackage = UserPackage::find($userPackageId);

        if (!$userPackage || $userPackage->status !== 'paused') {
            return response()->json([
                'message' => 'No paused subscription found for this package.',
            ], 400);
        }

        try {
            $subscription = Subscription::retrieve($userPackage->stripe_subscription_id);

            // If the subscription is paused, resume it
            if ($subscription->status === 'paused') {
                $subscription->resume();

                // Update the UserPackage status to 'active'
                $userPackage->update([
                    'status' => 'active',
                    'paused_at' => null,
                ]);

                return response()->json([
                    'message' => 'Subscription reactivated successfully.',
                ], 200);
            }

            // If the subscription cannot be resumed, you can mark it inactive
            $userPackage->update([
                'status' => 'inactive',
            ]);

            return response()->json([
                'message' => 'Subscription cannot be reactivated. Marked as inactive.',
            ], 400);
        } catch (Exception $e) {
            Log::error('Stripe Subscription Reactivation Error: ' . $e->getMessage());

            return response()->json([
                'message' => 'There was an error reactivating the subscription.',
            ], 500);
        }
    }
}
