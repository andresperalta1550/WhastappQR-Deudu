<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('channels')->group(function () {
        /**
         * Get all channels
         */
        Route::get('/', [\App\Http\Controllers\Api\V1\Channel\GetChannelController::class, '__invoke']);

        /**
         * Get channel available for debtor
         */
        Route::get('/available/{coordinationId}', [\App\Http\Controllers\Api\V1\Channel\GetChannelAvailableForDebtorController::class, '__invoke']);

        /**
         * Get channels associated with coordination
         */
        Route::get('/associated/{coordinationId}', [\App\Http\Controllers\Api\V1\Channel\GetChannelsAssociatedWithCoordinationController::class, '__invoke']);

        /**
         * Put coordination id to channel
         */
        Route::put('/{channel}', [\App\Http\Controllers\Api\V1\Channel\PutChannelController::class, '__invoke']);
    });

    Route::prefix('messages')->group(function () {
        /**
         * Post a message
         */
        Route::post('/', [\App\Http\Controllers\Api\V1\Message\PostMessageController::class, '__invoke']);
    });

    Route::prefix('webhook')->group(function () {
        /**
         * Webhook endpoint
         */
        Route::post('/', [\App\Http\Controllers\Api\V1\Webhook\PostWebhookController::class, '__invoke']);
    });

    Route::prefix('debtors')->group(function () {
        /**
         * Get messages by debtor ID
         */
        Route::get('/{debtorId}/messages', [\App\Http\Controllers\Api\V1\Message\GetMessagesByDebtorController::class, '__invoke']);

        /**
         * Mark as read
         */
        Route::post('/{debtorId}/mark-as-read', [\App\Http\Controllers\Api\V1\Message\PostMarkAsReadController::class, '__invoke']);

        /**
         * Mark as unread
         */
        Route::post('/{debtorId}/mark-as-unread', [\App\Http\Controllers\Api\V1\Message\PostMarkAsUnreadController::class, '__invoke']);
    });

    Route::prefix('conversations')->group(function () {
        /**
         * Summary contacts by debtor IDs
         */
        Route::get('/summary/by-debtors', [\App\Http\Controllers\Api\V1\Contact\GetSummaryByDebtorsController::class, '__invoke']);
    });

    Route::prefix('batches')->group(function () {
        /**
         * Create a batch validator numbers
         */
        Route::post('/validator-numbers', [\App\Http\Controllers\Api\V1\ValidatorBatch\CreateValidatorBatchController::class, '__invoke']);
    });
});

