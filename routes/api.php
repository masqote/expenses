<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AdjustmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\GroupExpenseController;
use App\Http\Controllers\GroupSummaryController;
use App\Http\Controllers\TelegramLinkController;
use App\Http\Controllers\TelegramWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public auth routes - register disabled
// Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Telegram webhook (no Sanctum auth — secured via secret token)
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);

    // Expenses
    Route::apiResource('expenses', ExpenseController::class)->except(['show']);

    // Income
    Route::apiResource('income', IncomeController::class)->except(['show']);

    // Salary
    Route::get('/salary', [SalaryController::class, 'show']);
    Route::post('/salary', [SalaryController::class, 'store']);
    Route::delete('/salary/{period}', [SalaryController::class, 'destroy']);

    // Personal summary
    Route::get('/summary', [SummaryController::class, 'show']);

    // Groups
    Route::post('/groups', [GroupController::class, 'store']);
    Route::get('/groups/my', [GroupController::class, 'myGroup']);
    Route::get('/groups/list', [GroupController::class, 'myGroups']);
    Route::get('/groups/{group}', [GroupController::class, 'show']);
    Route::delete('/groups/{group}/members/{user}', [GroupController::class, 'removeMember']);

    // Group expenses and summary (requires group membership)
    Route::get('/groups/{group}/expenses', [GroupExpenseController::class, 'index'])
        ->middleware('group.member');
    Route::get('/groups/{group}/summary', [GroupSummaryController::class, 'show'])
        ->middleware('group.member');

    // Invites
    Route::post('/groups/{group}/invites', [InviteController::class, 'store']);
    Route::post('/invites/{invite}/accept', [InviteController::class, 'accept']);
    Route::post('/invites/{invite}/decline', [InviteController::class, 'decline']);
    Route::get('/invites', [InviteController::class, 'index']);

    // Telegram link token
    Route::post('/telegram/link-token', [TelegramLinkController::class, 'generate']);

    // Adjustments
    Route::get('/adjustments', [AdjustmentController::class, 'index']);
    Route::post('/adjustments', [AdjustmentController::class, 'store']);
    Route::delete('/adjustments/{id}', [AdjustmentController::class, 'destroy']);
});
