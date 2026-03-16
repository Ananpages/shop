<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\ShopLinkController;
use App\Http\Controllers\ShopReviewController;

// Health check
Route::get('/health', fn() => response()->json(['success' => true, 'message' => 'Beibe API running 🚀']));

// Uganda Districts
Route::get('/districts', fn() => response()->json(['success' => true, 'data' => config('beibe.districts')]));

// ==================== AUTH ====================
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me',                [AuthController::class, 'me']);
        Route::put('/profile',           [AuthController::class, 'updateProfile']);
        Route::put('/change-password',   [AuthController::class, 'changePassword']);
        Route::post('/logout',           [AuthController::class, 'logout']);
    });
});

// ==================== CATEGORIES ====================
Route::get('/categories', [CategoryController::class, 'index']);

// ==================== PRODUCTS ====================
Route::get('/products/featured', [ProductController::class, 'featured']);
Route::get('/products',          [ProductController::class, 'index']);
Route::get('/products/{id}',     [ProductController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/products/my/list',  [ProductController::class, 'myProducts']);
    Route::post('/products',         [ProductController::class, 'store']);
    Route::put('/products/{id}',     [ProductController::class, 'update']);
    Route::delete('/products/{id}',  [ProductController::class, 'destroy']);
});

// ==================== SHOPS ====================
Route::get('/shops',         [ShopController::class, 'index']);
Route::get('/shops/{slug}',  [ShopController::class, 'show']);
Route::get('/shops/{shopId}/reviews',  [ShopReviewController::class, 'index']);
Route::post('/shops/{shopId}/reviews', [ShopReviewController::class, 'store'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/shops',              [ShopController::class, 'store']);
    Route::put('/shops/{id}',          [ShopController::class, 'update']);
    Route::get('/shops/my/dashboard',  [ShopController::class, 'dashboard']);
    Route::get('/shops/my/link',       [ShopLinkController::class, 'get']);
    Route::put('/shops/my/link',       [ShopLinkController::class, 'update']);
});

// ==================== CART ====================
Route::middleware('auth:sanctum')->prefix('cart')->group(function () {
    Route::get('/',        [CartController::class, 'index']);
    Route::post('/',       [CartController::class, 'store']);
    Route::put('/{id}',    [CartController::class, 'update']);
    Route::delete('/{id}', [CartController::class, 'destroy']);
    Route::delete('/',     [CartController::class, 'clear']);
});

// ==================== ORDERS ====================
Route::middleware('auth:sanctum')->prefix('orders')->group(function () {
    Route::get('/',              [OrderController::class, 'index']);
    Route::post('/',             [OrderController::class, 'store']);
    Route::get('/seller/list',   [OrderController::class, 'sellerOrders']);
    Route::get('/{id}',          [OrderController::class, 'show']);
    Route::put('/{id}/status',   [OrderController::class, 'updateStatus']);
});

// ==================== REVIEWS ====================
Route::middleware('auth:sanctum')->post('/reviews', [ReviewController::class, 'store']);

// ==================== WISHLIST ====================
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/wishlist',         [WishlistController::class, 'index']);
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle']);
});

// ==================== CHAT ====================
Route::middleware('auth:sanctum')->prefix('chat')->group(function () {
    Route::get('/conversations',       [ChatController::class, 'conversations']);
    Route::post('/start',              [ChatController::class, 'start']);
    Route::get('/unread/count',        [ChatController::class, 'unreadCount']);
    Route::get('/{id}/messages',       [ChatController::class, 'messages']);
    Route::post('/{id}/messages',      [ChatController::class, 'sendMessage']);
});

// ==================== NOTIFICATIONS ====================
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications',          [NotificationController::class, 'index']);
    Route::put('/notifications/read-all', [NotificationController::class, 'readAll']);
    Route::get('/recently-viewed',        [NotificationController::class, 'recentlyViewed']);
});

// ==================== UPLOAD ====================
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/upload',          [UploadController::class, 'single']);
    Route::post('/upload/multiple', [UploadController::class, 'multiple']);
    // Shop verification request
    Route::post('/shops/verify/request', [VerificationController::class, 'request']);
    Route::get('/shops/my/link',              [ShopLinkController::class, 'get']);
    Route::put('/shops/my/link',              [ShopLinkController::class, 'update']);
    Route::get('/shops/check-slug/{slug}',    [ShopLinkController::class, 'checkSlug'])->withoutMiddleware('auth:sanctum');

});

// ==================== ADMIN ====================
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/stats',                   [AdminController::class, 'stats']);
    Route::get('/users',                   [AdminController::class, 'users']);
    Route::put('/users/{id}/toggle',       [AdminController::class, 'toggleUser']);
    Route::get('/shops',                   [AdminController::class, 'shops']);
    Route::put('/shops/{id}/approve',      [AdminController::class, 'approveShop']);
    Route::put('/shops/{id}/reject',       [AdminController::class, 'rejectShop']);
    Route::put('/shops/{id}/suspend',      [AdminController::class, 'suspendShop']);
    Route::get('/products',                [AdminController::class, 'products']);
    Route::delete('/products/{id}',        [AdminController::class, 'removeProduct']);
    Route::get('/orders',                  [AdminController::class, 'orders']);
    Route::post('/categories',             [AdminController::class, 'createCategory']);
    // Shop verification approvals/rejections
    Route::put('/shops/{id}/verify', [VerificationController::class, 'approve']);
Route::put('/shops/{id}/reject-verification', [VerificationController::class, 'reject']);
});

