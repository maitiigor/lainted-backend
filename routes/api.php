<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Bundles\BundleController;
use App\Http\Controllers\Api\V1\Chat\ConversationController;
use App\Http\Controllers\Api\V1\Chat\MessageController;
use App\Http\Controllers\Api\V1\Listings\CategoryController;
use App\Http\Controllers\Api\V1\Listings\DiscoveryController;
use App\Http\Controllers\Api\V1\Listings\ListingController;
use App\Http\Controllers\Api\V1\Listings\ListingPhotoController;
use App\Http\Controllers\Api\V1\Listings\RecentlyViewedController;
use App\Http\Controllers\Api\V1\Listings\SavedListingController;
use App\Http\Controllers\Api\V1\Locations\LgaController;
use App\Http\Controllers\Api\V1\Negotiation\OfferController;
use App\Http\Controllers\Api\V1\Notifications\DeviceTokenController;
use App\Http\Controllers\Api\V1\Orders\DeliveryAddressController;
use App\Http\Controllers\Api\V1\Orders\DeliveryQuoteController;
use App\Http\Controllers\Api\V1\Orders\OrderController;
use App\Http\Controllers\Api\V1\Payments\BankAccountController;
use App\Http\Controllers\Api\V1\Payments\PaystackWebhookController;
use App\Http\Controllers\Api\V1\Payments\SubscriptionController;
use App\Http\Controllers\Api\V1\Payments\WalletController;
use App\Http\Controllers\Api\V1\Trust\DisputeController;
use App\Http\Controllers\Api\V1\Trust\ReportController;
use App\Http\Controllers\Api\V1\Trust\ReviewController;
use App\Http\Controllers\Api\V1\Users\BlockedUserController;
use App\Http\Controllers\Api\V1\Users\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('otp/request', [AuthController::class, 'requestOtp'])
            ->middleware(['throttle:otp-per-phone', 'throttle:otp-per-ip']);
        Route::post('otp/verify', [AuthController::class, 'verifyOtp']);
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:20,1');

        Route::middleware('auth:sanctum')->post('logout', [AuthController::class, 'logout']);
    });

    Route::get('lgas', [LgaController::class, 'index']);

    // Public - Paystack calls this directly, no bearer token to send.
    // Verified via HMAC signature instead, see PaystackWebhookController.
    Route::post('webhooks/paystack', [PaystackWebhookController::class, 'handle']);

    Route::middleware(['auth:sanctum', 'account.active'])->group(function () {
        Route::get('me', [ProfileController::class, 'show']);
        // POST, not PUT: PHP doesn't populate $_POST for multipart requests
        // on any method other than POST, so a PUT here would silently drop
        // every field whenever a photo is attached.
        Route::post('me', [ProfileController::class, 'update']);
        Route::post('me/pickup-address', [ProfileController::class, 'updatePickupAddress']);
        Route::get('me/wardrobe-stats', [ProfileController::class, 'wardrobeStats']);
        Route::post('me/delete', [ProfileController::class, 'deleteAccount']);
        Route::post('me/restore-account', [ProfileController::class, 'restoreAccount']);
        Route::post('me/phone/request-otp', [ProfileController::class, 'requestPhoneChangeOtp']);
        Route::post('me/phone/confirm', [ProfileController::class, 'confirmPhoneChange']);

        Route::get('categories', [CategoryController::class, 'index']);

        Route::get('discover', [DiscoveryController::class, 'feed']);
        Route::get('saved-listings', [SavedListingController::class, 'index']);
        Route::get('recently-viewed', [RecentlyViewedController::class, 'index']);

        Route::get('listings', [ListingController::class, 'index']);
        Route::post('listings', [ListingController::class, 'store']);
        Route::get('listings/{listing}', [ListingController::class, 'show']);
        Route::put('listings/{listing}', [ListingController::class, 'update']);
        Route::delete('listings/{listing}', [ListingController::class, 'destroy']);
        Route::post('listings/{listing}/publish', [ListingController::class, 'publish']);
        Route::post('listings/{listing}/mark-sold', [ListingController::class, 'markSold']);
        // POST, not PUT - same PHP multipart-on-non-POST limitation as /me.
        Route::post('listings/{listing}/photos', [ListingPhotoController::class, 'store']);
        Route::delete('listings/{listing}/photos/{photo}', [ListingPhotoController::class, 'destroy']);
        Route::post('listings/{listing}/save', [SavedListingController::class, 'store']);
        Route::delete('listings/{listing}/save', [SavedListingController::class, 'destroy']);

        Route::get('bundles', [BundleController::class, 'index']);
        Route::post('bundles', [BundleController::class, 'store']);
        Route::put('bundles/{bundle}', [BundleController::class, 'update']);
        Route::delete('bundles/{bundle}', [BundleController::class, 'destroy']);

        Route::get('conversations', [ConversationController::class, 'index']);
        Route::post('conversations', [ConversationController::class, 'store']);
        Route::get('conversations/{conversation}', [ConversationController::class, 'show']);
        Route::post('conversations/{conversation}/read', [ConversationController::class, 'markRead']);
        Route::get('conversations/{conversation}/messages', [MessageController::class, 'index']);
        // POST, not PUT - same PHP multipart-on-non-POST limitation as /me.
        Route::post('conversations/{conversation}/messages', [MessageController::class, 'store']);

        Route::post('conversations/{conversation}/offers', [OfferController::class, 'store']);
        Route::post('offers/{offer}/counter', [OfferController::class, 'counter']);
        Route::post('offers/{offer}/accept', [OfferController::class, 'accept']);
        Route::post('offers/{offer}/decline', [OfferController::class, 'decline']);
        Route::post('offers/{offer}/retract', [OfferController::class, 'retract']);

        Route::get('delivery-addresses', [DeliveryAddressController::class, 'index']);
        Route::post('delivery-addresses', [DeliveryAddressController::class, 'store']);
        Route::post('delivery-quote', [DeliveryQuoteController::class, 'store']);

        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{order}', [OrderController::class, 'show']);
        Route::post('offers/{offer}/checkout', [OrderController::class, 'checkoutOffer']);
        Route::post('listings/{listing}/checkout', [OrderController::class, 'checkoutListing']);
        Route::post('orders/{order}/payment/confirm', [OrderController::class, 'confirmPayment']);
        Route::post('orders/{order}/prepared', [OrderController::class, 'markPrepared']);
        Route::post('orders/{order}/in-transit', [OrderController::class, 'markInTransit']);
        Route::post('orders/{order}/confirm-delivery', [OrderController::class, 'confirmDelivery']);
        Route::post('orders/{order}/mark-delivered', [OrderController::class, 'markDelivered']);
        Route::post('orders/{order}/confirm-receipt', [OrderController::class, 'confirmReceipt']);
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);

        Route::get('disputes', [DisputeController::class, 'index']);
        Route::get('disputes/{dispute}', [DisputeController::class, 'show']);
        // POST, not PUT - same PHP multipart-on-non-POST limitation as /me.
        Route::post('disputes/photos', [DisputeController::class, 'uploadPhoto']);
        Route::post('orders/{order}/disputes', [DisputeController::class, 'store']);
        Route::post('disputes/{dispute}/respond', [DisputeController::class, 'respond']);
        Route::post('disputes/{dispute}/accept-resolution', [DisputeController::class, 'acceptResolution']);
        Route::post('disputes/{dispute}/reject-resolution', [DisputeController::class, 'rejectResolution']);

        Route::post('orders/{order}/reviews', [ReviewController::class, 'store']);
        Route::get('users/{user}/reviews', [ReviewController::class, 'index']);
        Route::get('users/{user}', [ProfileController::class, 'showPublic']);

        Route::post('reports', [ReportController::class, 'store']);

        Route::get('banks', [BankAccountController::class, 'banks']);
        Route::post('bank-accounts/resolve', [BankAccountController::class, 'resolve']);
        Route::get('bank-accounts', [BankAccountController::class, 'index']);
        Route::post('bank-accounts', [BankAccountController::class, 'store']);
        Route::delete('bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy']);

        Route::get('wallet', [WalletController::class, 'show']);
        Route::get('wallet/transactions', [WalletController::class, 'transactions']);
        Route::post('wallet/withdraw', [WalletController::class, 'withdraw']);

        Route::get('subscription-plans', [SubscriptionController::class, 'plans']);
        Route::post('subscriptions/checkout', [SubscriptionController::class, 'checkout']);
        Route::post('subscriptions/payment/confirm', [SubscriptionController::class, 'confirmPayment']);

        Route::get('blocked-users', [BlockedUserController::class, 'index']);
        Route::post('blocked-users', [BlockedUserController::class, 'store']);
        Route::delete('blocked-users/{user}', [BlockedUserController::class, 'destroy']);

        Route::post('device-tokens', [DeviceTokenController::class, 'store']);
        Route::delete('device-tokens', [DeviceTokenController::class, 'destroy']);
    });
});
