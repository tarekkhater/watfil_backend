<?php

use App\Http\Controllers\Company\AuthController as CompanyAuthController;
use App\Http\Controllers\Company\CatalogController;
use App\Http\Controllers\Company\FinanceController as CompanyFinanceController;
use App\Http\Controllers\Company\ProductController as CompanyProductController;
use App\Http\Controllers\SuperAdmin\AuthController as SuperAdminAuthController;
use App\Http\Controllers\SuperAdmin\CompanyController;
use App\Http\Controllers\SuperAdmin\FinanceController as SuperAdminFinanceController;
use App\Http\Controllers\SuperAdmin\GovernorateController;
use App\Http\Controllers\SuperAdmin\SupplierController;
use App\Http\Controllers\SuperAdmin\SupplierProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Super Admin Routes
|--------------------------------------------------------------------------
*/
Route::prefix('super-admin')->group(function () {

    Route::post('login', [SuperAdminAuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'super_admin'])->group(function () {

        Route::post('logout', [SuperAdminAuthController::class, 'logout']);
        Route::get('me', [SuperAdminAuthController::class, 'me']);

        // Governorates (read-only, populated via seeder)
        Route::get('governorates', [GovernorateController::class, 'index']);

        // Companies CRUD
        Route::get('companies', [CompanyController::class, 'index']);
        Route::post('companies', [CompanyController::class, 'store']);
        Route::get('companies/{company}', [CompanyController::class, 'show']);
        Route::post('companies/{company}', [CompanyController::class, 'update']);
        Route::delete('companies/{company}', [CompanyController::class, 'destroy']);
        Route::patch('companies/{company}/toggle-status', [CompanyController::class, 'toggleStatus']);
        Route::get('companies/{company}/wallet', [CompanyController::class, 'showWallet']);
        Route::patch('companies/{company}/wallet', [CompanyController::class, 'updateWallet']);
        Route::post('companies/{company}/wallet/adjust', [CompanyController::class, 'adjustWallet']);
        Route::get('companies/{company}/wallet/transactions', [SuperAdminFinanceController::class, 'companyWalletTransactions']);

        // Finance
        Route::get('finance/commissions/summary', [SuperAdminFinanceController::class, 'commissionSummary']);
        Route::get('finance/withdrawal-requests', [SuperAdminFinanceController::class, 'withdrawalRequests']);
        Route::patch('finance/withdrawal-requests/{withdrawalRequest}/approve', [SuperAdminFinanceController::class, 'approveWithdrawal']);
        Route::patch('finance/withdrawal-requests/{withdrawalRequest}/reject', [SuperAdminFinanceController::class, 'rejectWithdrawal']);
        Route::patch('finance/withdrawal-requests/{withdrawalRequest}/pay', [SuperAdminFinanceController::class, 'payWithdrawal']);

        // Suppliers CRUD
        Route::get('suppliers', [SupplierController::class, 'index']);
        Route::post('suppliers', [SupplierController::class, 'store']);
        Route::get('suppliers/{supplier}', [SupplierController::class, 'show']);
        Route::post('suppliers/{supplier}', [SupplierController::class, 'update']);
        Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy']);

        // Supplier Products CRUD
        Route::get('supplier-products', [SupplierProductController::class, 'index']);
        Route::post('supplier-products', [SupplierProductController::class, 'store']);
        Route::get('supplier-products/{supplierProduct}', [SupplierProductController::class, 'show']);
        Route::post('supplier-products/{supplierProduct}', [SupplierProductController::class, 'update']);
        Route::delete('supplier-products/{supplierProduct}', [SupplierProductController::class, 'destroy']);
        Route::patch('supplier-products/{supplierProduct}/toggle-status', [SupplierProductController::class, 'toggleStatus']);
    });
});

/*
|--------------------------------------------------------------------------
| Company Routes
|--------------------------------------------------------------------------
*/
Route::prefix('company')->group(function () {

    Route::post('login', [CompanyAuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'company'])->group(function () {

        Route::post('logout', [CompanyAuthController::class, 'logout']);
        Route::get('me', [CompanyAuthController::class, 'me']);

        // Company own products
        Route::get('products', [CompanyProductController::class, 'index']);
        Route::post('products', [CompanyProductController::class, 'store']);
        Route::get('products/{companyProduct}', [CompanyProductController::class, 'show']);
        Route::post('products/{companyProduct}', [CompanyProductController::class, 'update']);
        Route::delete('products/{companyProduct}', [CompanyProductController::class, 'destroy']);

        // Supplier catalog management
        Route::get('catalog/available', [CatalogController::class, 'availableProducts']);
        Route::get('catalog/mine', [CatalogController::class, 'mycatalog']);
        Route::post('catalog/add', [CatalogController::class, 'addToMyCatalog']);
        Route::post('catalog/remove', [CatalogController::class, 'removeFromMyCatalog']);
        Route::post('catalog/{supplierProduct}', [CatalogController::class, 'store']);
        Route::delete('catalog/{supplierProduct}', [CatalogController::class, 'destroy']);

        // Finance
        Route::get('wallet/transactions', [CompanyFinanceController::class, 'transactions']);
        Route::post('wallet/withdrawals', [CompanyFinanceController::class, 'storeWithdrawal']);
    });
});
