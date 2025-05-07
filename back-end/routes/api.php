<?php
// File: routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\ComponentController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DrawingController;
use App\Http\Controllers\AnalyticsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Debug route to check if API is working
Route::get('test', function () {
    return response()->json(['message' => 'API is working']);
});

// Public routes
Route::group([], function () {
    
    // Machine routes
    Route::prefix('machines')->group(function () {
        Route::get('/', [MachineController::class, 'index']);
        Route::get('/{machine}', [MachineController::class, 'show']);
        Route::get('/{machine}/components', [MachineController::class, 'components']);
        Route::get('/{machine}/drawing', [MachineController::class, 'drawing']);
        Route::get('/{machine}/exploded-view', [MachineController::class, 'explodedView']);
    });
    
    // Component routes
    Route::prefix('components')->group(function () {
        Route::get('/', [ComponentController::class, 'index']);
        Route::get('/search', [ComponentController::class, 'search']);
        Route::get('/find-by-position', [ComponentController::class, 'findByPosition']);
        Route::get('/{component}', [ComponentController::class, 'show']);
    });
    
    // Category routes
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/{category}', [CategoryController::class, 'show']);
        Route::get('/{category}/components', [CategoryController::class, 'components']);
    });
    
    // Search routes
    Route::prefix('search')->group(function () {
        Route::get('/', [SearchController::class, 'index']);
        Route::get('/suggestions', [SearchController::class, 'suggestions']);
        Route::get('/history', [SearchController::class, 'history']);
        Route::get('/popular', [SearchController::class, 'popular']);
        Route::post('/advanced', [SearchController::class, 'advanced']);
    });
    
    // Favorite routes
    Route::prefix('favorites')->group(function () {
        Route::get('/', [FavoriteController::class, 'index']);
        Route::post('/check', [FavoriteController::class, 'checkStatus']);
        Route::post('/machines/{machine}/toggle', [FavoriteController::class, 'toggleMachine']);
        Route::post('/components/{component}/toggle', [FavoriteController::class, 'toggleComponent']);
        Route::post('/machines/{machine}', [FavoriteController::class, 'addMachine']);
        Route::post('/components/{component}', [FavoriteController::class, 'addComponent']);
        Route::delete('/machines/{machine}', [FavoriteController::class, 'removeMachine']);
        Route::delete('/components/{component}', [FavoriteController::class, 'removeComponent']);
        Route::get('/machines-with-components', [FavoriteController::class, 'getMachinesWithComponents']);
    });
    
    // Drawing routes
    Route::prefix('drawings')->group(function () {
        Route::get('/{drawing}', [DrawingController::class, 'show']);
        Route::get('/{drawing}/clickable-areas', [DrawingController::class, 'getClickableAreas']);
        Route::post('/{drawing}/find-component', [DrawingController::class, 'findComponentAtPosition']);
    });
    
    // Analytics routes
    Route::prefix('analytics')->group(function () {
        Route::get('/search-trends', [AnalyticsController::class, 'searchTrends']);
        Route::get('/popular-machines', [AnalyticsController::class, 'popularMachines']);
        Route::get('/popular-components', [AnalyticsController::class, 'popularComponents']);
        Route::get('/user-favorites', [AnalyticsController::class, 'userFavorites']);
    });
});

// Protected routes (if needed for admin functions)
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    
    // Machine management
    Route::post('/machines', [MachineController::class, 'store']);
    Route::put('/machines/{machine}', [MachineController::class, 'update']);
    Route::delete('/machines/{machine}', [MachineController::class, 'destroy']);
    
    // Component management
    Route::post('/components', [ComponentController::class, 'store']);
    Route::put('/components/{component}', [ComponentController::class, 'update']);
    Route::delete('/components/{component}', [ComponentController::class, 'destroy']);
    
    // Category management
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    
    // Drawing management
    Route::post('/drawings', [DrawingController::class, 'store']);
    Route::put('/drawings/{drawing}', [DrawingController::class, 'update']);
    Route::delete('/drawings/{drawing}', [DrawingController::class, 'destroy']);
    Route::post('/drawings/{drawing}/clickable-areas', [DrawingController::class, 'updateClickableAreas']);
});

// User routes (for authenticated users)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // User-specific favorites
    Route::get('/user/favorites', [FavoriteController::class, 'index']);
    Route::get('/user/search-history', [SearchController::class, 'history']);
});

// Files/Images routes
Route::prefix('files')->group(function () {
    Route::get('/machines/{filename}', function ($filename) {
        return response()->file(storage_path('app/public/machines/' . $filename));
    });
    
    Route::get('/components/{filename}', function ($filename) {
        return response()->file(storage_path('app/public/components/' . $filename));
    });
    
    Route::get('/drawings/{filename}', function ($filename) {
        return response()->file(storage_path('app/public/drawings/' . $filename));
    });
    
    Route::get('/categories/{filename}', function ($filename) {
        return response()->file(storage_path('app/public/categories/' . $filename));
    });
});

// Health check route
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => config('app.version', '1.0.0'),
    ]);
});

// API documentation route
Route::get('/docs', function () {
    return response()->json([
        'message' => 'API documentation',
        'endpoints' => [
            'machines' => '/api/machines',
            'components' => '/api/components',
            'categories' => '/api/categories',
            'search' => '/api/search',
            'favorites' => '/api/favorites',
            'drawings' => '/api/drawings',
        ],
    ]);
});