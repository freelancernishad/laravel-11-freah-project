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

            // Check if the payment collection is paused
            $isPaused = !empty($subscription->pause_collection) ? true : false;

            return response()->json([
                'status' => $subscription->status,
                'payment_collection_paused' => $isPaused, // true if paused, false otherwise
                'pause_behavior' => $isPaused ? $subscription->pause_collection->behavior : null,
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

    public function pausePaymentCollection(Request $request, $userPackageId)
    {
        $userPackage = UserPackage::find($userPackageId);

        if (!$userPackage || $userPackage->status !== 'active') {
            return response()->json([
                'message' => 'No active subscription found for this package.',
            ], 400);
        }

        try {
            // Retrieve the subscription from Stripe
            $subscription = Subscription::retrieve($userPackage->stripe_subscription_id);

            // Pause the collection of payments without affecting the subscription (by setting the payment collection paused)
            // If Stripe doesn't have native pause, we use an alternative method to stop payments temporarily.
            // You can set `pause_collection` to true (if you're using Stripe's "pause collection" feature):
            $subscription->pause_collection = ['behavior' => 'mark_uncollectible']; // Or 'keep_as_draft' depending on your needs
            $subscription->save();

            // Update the UserPackage status to 'paused' in your system
            $userPackage->update([
                'status' => 'paused',
                'paused_at' => now(),
            ]);

            return response()->json([
                'message' => 'Payment collection paused successfully.',
            ], 200);
        } catch (Exception $e) {
            Log::error('Stripe Subscription Pause Payment Collection Error: ' . $e->getMessage());

            return response()->json([
                'message' => 'There was an error pausing the payment collection.',
            ], 500);
        }
    }






    public function reactivatePaymentCollection(Request $request, $userPackageId)
    {
        $userPackage = UserPackage::find($userPackageId);

        if (!$userPackage || $userPackage->status !== 'paused') {
            return response()->json([
                'message' => 'No paused subscription found for this package.',
            ], 400);
        }

        try {
            // Retrieve the subscription from Stripe
            $subscription = Subscription::retrieve($userPackage->stripe_subscription_id);

            // Check if payment collection is paused
            if (!empty($subscription->pause_collection)) {
                // Unpause payment collection by setting pause_collection to null
                $subscription->pause_collection = null;
                $subscription->save();
            }

            // Update the UserPackage status to 'active'
            $userPackage->update([
                'status' => 'active',
                'paused_at' => null,
            ]);

            return response()->json([
                'message' => 'Payment collection reactivated successfully.',
            ], 200);
        } catch (Exception $e) {
            Log::error('Stripe Subscription Reactivate Payment Collection Error: ' . $e->getMessage());

            return response()->json([
                'message' => 'There was an error reactivating the payment collection.',
            ], 500);
        }
    }


}
