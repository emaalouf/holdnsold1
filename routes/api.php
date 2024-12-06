<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\AuctionController;
use App\Http\Controllers\Api\BidController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AuctionAnalyticsController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\AutoBidController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SocialController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\DrawController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Authentication & User Account Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AuthController::class, 'user']);
        Route::patch('/user', [AuthController::class, 'update']);
    });

    Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
    Route::post('/password/reset', [AuthController::class, 'resetPassword']);
});

// Category Routes
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/{category}', [CategoryController::class, 'show']);

    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('/', [CategoryController::class, 'store']);
        Route::patch('/{category}', [CategoryController::class, 'update']);
        Route::delete('/{category}', [CategoryController::class, 'destroy']);
    });
});

// Auction Routes
Route::prefix('auctions')->group(function () {
    Route::get('/', [AuctionController::class, 'index']);
    Route::get('/{auction}', [AuctionController::class, 'show']);
    Route::get('/{auction}/bids', [AuctionController::class, 'bids']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [AuctionController::class, 'store']);
        Route::patch('/{auction}', [AuctionController::class, 'update'])->middleware('can:update,auction');
        Route::delete('/{auction}', [AuctionController::class, 'destroy'])->middleware('can:delete,auction');
        Route::post('/{auction}/close', [AuctionController::class, 'close'])->middleware('can:close,auction');
        Route::post('/{auction}/watch', [AuctionController::class, 'addToWatchlist']);
        Route::delete('/{auction}/watch', [AuctionController::class, 'removeFromWatchlist']);
    });
});

// Bidding Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auctions/{auction}/bid', [BidController::class, 'store']);
    Route::get('/users/{user}/bids', [BidController::class, 'userBids'])->middleware('can:view,user');
});

// Payment Routes (Optional)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auctions/{auction}/checkout', [PaymentController::class, 'checkout']);
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->middleware('can:view,payment');
});

// Admin Routes
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/users', [AdminController::class, 'users']);
    Route::patch('/users/{user}', [AdminController::class, 'updateUser']);
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::post('/admin/auctions/bulk-action', [AdminController::class, 'bulkActionAuctions']);
    Route::post('/admin/users/bulk-action', [AdminController::class, 'bulkActionUsers']);
});

// Add these routes to api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/watchlist', [AuctionController::class, 'getWatchlist']);
    Route::post('/auctions/{auction}/comments', [CommentController::class, 'store']);
    Route::get('/auctions/{auction}/comments', [CommentController::class, 'index']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
    Route::post('/users/{user}/reviews', [ReviewController::class, 'store']);
    Route::get('/users/{user}/reviews', [ReviewController::class, 'index']);
    Route::get('/users/{user}/rating', [ReviewController::class, 'getRating']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/mark-read', [NotificationController::class, 'markAsRead']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::get('/auctions/{auction}/analytics', [AuctionAnalyticsController::class, 'show']);
    Route::get('/user/seller-stats', [AuctionAnalyticsController::class, 'sellerStats']);
    Route::get('/messages', [MessageController::class, 'index']);
    Route::get('/messages/{conversation}', [MessageController::class, 'show']);
    Route::post('/messages/{user}', [MessageController::class, 'store']);
    Route::delete('/messages/{message}', [MessageController::class, 'destroy']);
    Route::post('/auctions/{auction}/auto-bid', [AutoBidController::class, 'setup']);
    Route::get('/user/auto-bids', [AutoBidController::class, 'index']);
    Route::delete('/auto-bids/{autoBid}', [AutoBidController::class, 'destroy']);
    Route::post('/auctions/{auction}/report', [ReportController::class, 'reportAuction']);
    Route::post('/users/{user}/report', [ReportController::class, 'reportUser']);
    Route::get('/auctions/{auction}/share/{platform}', [SocialController::class, 'share']);
    Route::get('/auctions/{auction}/share-stats', [SocialController::class, 'shareStats']);
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::get('/draws', [DrawController::class, 'index']);
    Route::get('/draws/{draw}', [DrawController::class, 'show']);
    Route::post('/draws/{draw}/enter', [DrawController::class, 'enter']);
});

// Wallet routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/wallet', [WalletController::class, 'show']);
});

// Admin wallet routes
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/admin/users/{user}/wallet/recharge', [WalletController::class, 'adminRecharge']);
    Route::post('/auctions/{auction}/draws', [DrawController::class, 'store']);
    Route::post('/draws/{draw}/select-winner', [DrawController::class, 'selectWinner']);
});
