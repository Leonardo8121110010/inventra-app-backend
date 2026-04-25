<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\MovementController;
use App\Http\Controllers\Api\ReferralAgentController;
use App\Http\Controllers\Api\CommissionRuleController;
use App\Http\Controllers\Api\CommissionController;
use App\Http\Controllers\Api\CashRegisterController;
use App\Http\Controllers\Api\AgentTypeController;
use App\Http\Controllers\Api\ExchangeRateController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\RoleMenuController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\PermissionManagementController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\CashMovementController;

// ── Public ───────────────────────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);

// ── Protected ────────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Roles and Permissions
    Route::get('/permissions', [PermissionController::class, 'index'])->middleware('permission:manage-roles');
    Route::get('/permissions/discover', [PermissionManagementController::class, 'discover'])->middleware('permission:manage-roles');
    Route::put('/permissions/{id}', [PermissionManagementController::class, 'update'])->middleware('permission:manage-roles');
    Route::apiResource('/roles', RoleController::class)->middleware('permission:manage-roles');
    Route::get('/role-menus/{roleId}', [RoleMenuController::class, 'index'])->middleware('permission:manage-roles');
    Route::put('/role-menus/{roleId}', [RoleMenuController::class, 'update'])->middleware('permission:manage-roles');
    Route::get('/roles/available-permissions', [RoleController::class, 'availablePermissions'])->middleware('permission:manage-roles');

    // Menu Items (dynamic sidebar navigation)
    Route::apiResource('/menu-items', MenuItemController::class)->middleware('permission:manage-roles');

    // Reference data
    Route::get('/branches',        [BranchController::class, 'index'])->middleware('permission:view-branches');
    Route::post('/branches',       [BranchController::class, 'store'])->middleware('permission:create-branches');
    Route::put('/branches/{id}',   [BranchController::class, 'update'])->middleware('permission:edit-branches');
    Route::delete('/branches/{id}',[BranchController::class, 'destroy'])->middleware('permission:delete-branches');

    // Products (product lines / families — formerly categories)
    Route::get('/products',        [ProductController::class, 'index'])->middleware('permission:view-products');
    Route::post('/products',       [ProductController::class, 'store'])->middleware('permission:create-products');
    Route::put('/products/{id}',   [ProductController::class, 'update'])->middleware('permission:edit-products');
    Route::delete('/products/{id}',[ProductController::class, 'destroy'])->middleware('permission:delete-products');

    Route::get('/brands',        [BrandController::class, 'index'])->middleware('permission:view-products');
    Route::post('/brands',       [BrandController::class, 'store'])->middleware('permission:create-products');
    Route::put('/brands/{id}',   [BrandController::class, 'update'])->middleware('permission:edit-products');
    Route::delete('/brands/{id}',[BrandController::class, 'destroy'])->middleware('permission:delete-products');

    Route::get('/suppliers',        [SupplierController::class, 'index'])->middleware('permission:view-suppliers');
    Route::post('/suppliers',       [SupplierController::class, 'store'])->middleware('permission:create-suppliers');
    Route::put('/suppliers/{id}',   [SupplierController::class, 'update'])->middleware('permission:edit-suppliers');
    Route::delete('/suppliers/{id}',[SupplierController::class, 'destroy'])->middleware('permission:delete-suppliers');

    // Articles (sellable items / SKUs — formerly products)
    Route::get('/articles',        [ArticleController::class, 'index'])->middleware('permission:view-articles');
    Route::post('/articles',       [ArticleController::class, 'store'])->middleware('permission:create-articles');
    Route::put('/articles/{id}',   [ArticleController::class, 'update'])->middleware('permission:edit-articles');
    Route::delete('/articles/{id}',[ArticleController::class, 'destroy'])->middleware('permission:delete-articles');

    // Users
    Route::get('/users',        [UserController::class, 'index'])->middleware('permission:view-users');
    Route::post('/users',       [UserController::class, 'store'])->middleware('permission:create-users');
    Route::put('/users/{id}',   [UserController::class, 'update'])->middleware('permission:edit-users');
    Route::delete('/users/{id}',[UserController::class, 'destroy'])->middleware('permission:delete-users');

    // Inventory
    Route::get('/inventory',                    [InventoryController::class, 'index'])->middleware('permission:view-inventory');
    Route::get('/inventory/{branch}',           [InventoryController::class, 'branch'])->middleware('permission:view-inventory', 'branch-access');
    Route::put('/inventory/{branch}/{article}', [InventoryController::class, 'adjust'])->middleware('permission:adjust-inventory', 'branch-access');

    // Sales
    Route::get('/sales',      [SaleController::class, 'index'])->middleware('permission:view-sales');
    Route::post('/sales',     [SaleController::class, 'store'])->middleware('permission:create-sales', 'branch-access');
    Route::get('/sales/{id}', [SaleController::class, 'show'])->middleware('permission:view-sales', 'branch-access-sale');
    Route::post('/sales/{id}/assign-agent', [SaleController::class, 'assignAgent'])->middleware('permission:create-sales');

    // Movements
    Route::get('/movements',  [MovementController::class, 'index'])->middleware('permission:view-movements');
    Route::post('/movements', [MovementController::class, 'store'])->middleware('permission:create-movements', 'branch-access');
    Route::post('/movements/transfer', [MovementController::class, 'transfer'])->middleware('permission:create-movements');

    // Agent Types
    Route::get('/agent-types',         [AgentTypeController::class, 'index'])->middleware('permission:view-agent-types');
    Route::post('/agent-types',        [AgentTypeController::class, 'store'])->middleware('permission:create-agent-types');
    Route::put('/agent-types/{id}',    [AgentTypeController::class, 'update'])->middleware('permission:edit-agent-types');
    Route::delete('/agent-types/{id}', [AgentTypeController::class, 'destroy'])->middleware('permission:delete-agent-types');

    // Referral Agents
    Route::get('/referral-agents',      [ReferralAgentController::class, 'index'])->middleware('permission:view-referral-agents');
    Route::post('/referral-agents',     [ReferralAgentController::class, 'store'])->middleware('permission:create-referral-agents');
    Route::put('/referral-agents/{id}', [ReferralAgentController::class, 'update'])->middleware('permission:edit-referral-agents');

    // Commission Rules
    Route::get('/commission-rules',      [CommissionRuleController::class, 'index'])->middleware('permission:view-commission-rules');
    Route::post('/commission-rules',     [CommissionRuleController::class, 'store'])->middleware('permission:create-commission-rules');
    Route::put('/commission-rules/{id}', [CommissionRuleController::class, 'update'])->middleware('permission:edit-commission-rules');

    // Commissions
    Route::get('/commissions',          [CommissionController::class, 'index'])->middleware('permission:view-commissions');
    Route::get('/commissions/active-visits', [CommissionController::class, 'activeVisits'])->middleware('permission:view-commissions|create-sales');
    Route::post('/commissions/arrival', [CommissionController::class, 'registerArrival'])->middleware('permission:view-commissions|create-sales');
    Route::get('/commissions/payout-profile/{agent_id}', [CommissionController::class, 'payoutProfile'])->middleware('permission:view-commissions|manage-cash-registers');
    Route::put('/commissions/{id}/pay', [CommissionController::class, 'pay'])->middleware('permission:pay-commissions');

    // Cash Registers
    Route::get('/cash-registers/current',     [CashRegisterController::class, 'current'])->middleware('permission:view-cash-registers');
    Route::post('/cash-registers/open',       [CashRegisterController::class, 'open'])->middleware('permission:manage-cash-registers', 'branch-access');
    Route::post('/cash-registers/{id}/close', [CashRegisterController::class, 'close'])->middleware('permission:manage-cash-registers');
    Route::post('/cash-registers/{id}/reopen', [CashRegisterController::class, 'reopen'])->middleware('permission:reopen-cash-registers');

    // Cash Movements
    Route::get('/cash-movements', [CashMovementController::class, 'index'])->middleware('permission:manage-cash-registers');
    Route::post('/cash-movements', [CashMovementController::class, 'store'])->middleware('permission:manage-cash-registers');
    Route::delete('/cash-movements/{id}', [CashMovementController::class, 'destroy'])->middleware('permission:manage-cash-registers');
    Route::get('/cash-movement-motives', [CashMovementController::class, 'motives'])->middleware('permission:manage-cash-registers');

    // Exchange Rates
    Route::get('/exchange-rates/available',   [ExchangeRateController::class, 'availableCurrencies'])->middleware('permission:view-exchange-rates');
    Route::get('/exchange-rates',             [ExchangeRateController::class, 'index'])->middleware('permission:view-exchange-rates');
    Route::get('/exchange-rates/history',     [ExchangeRateController::class, 'history'])->middleware('permission:view-exchange-rates');
    Route::post('/exchange-rates',            [ExchangeRateController::class, 'store'])->middleware('permission:manage-exchange-rates');
    Route::post('/exchange-rates/fetch-live', [ExchangeRateController::class, 'fetchLive'])->middleware('permission:manage-exchange-rates');
    Route::delete('/exchange-rates/{currency}',[ExchangeRateController::class, 'destroy'])->middleware('permission:manage-exchange-rates');

    // Reports
    Route::get('/reports/daily-inventory', [ReportController::class, 'dailyInventory'])->middleware('permission:view-inventory');
    Route::get('/reports/commissions', [ReportController::class, 'commissions'])->middleware('permission:view-commissions');
    Route::get('reports/closure', [ReportController::class, 'closure']);
    Route::get('/reports/general-inventory', [ReportController::class, 'generalInventory'])->middleware('permission:view-inventory');
});
