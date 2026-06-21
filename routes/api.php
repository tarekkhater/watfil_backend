<?php

use App\Http\Controllers\Company\AuthController as CompanyAuthController;
use App\Http\Controllers\Company\CatalogController;
use App\Http\Controllers\Company\CustomerController as CompanyCustomerController;
use App\Http\Controllers\Company\FinanceController as CompanyFinanceController;
use App\Http\Controllers\Company\OrderController as CompanyOrderController;
use App\Http\Controllers\Company\ProductController as CompanyProductController;
use App\Http\Controllers\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Customer\CompanyEngagementController as CustomerCompanyEngagementController;
use App\Http\Controllers\Customer\MaintenanceRequestController as CustomerMaintenanceRequestController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Public\CompanyController as PublicCompanyController;
use App\Http\Controllers\Public\GovernorateController as PublicGovernorateController;
use App\Http\Controllers\Public\MaintenanceLookupController as PublicMaintenanceLookupController;
use App\Http\Controllers\Public\ProductCatalogController as PublicProductCatalogController;
use App\Http\Controllers\Public\StoreController as PublicStoreController;
use App\Http\Controllers\SuperAdmin\AuthController as SuperAdminAuthController;
use App\Http\Controllers\SuperAdmin\CategoryController as SuperAdminCategoryController;
use App\Http\Controllers\SuperAdmin\CompanyController;
use App\Http\Controllers\SuperAdmin\FinanceController as SuperAdminFinanceController;
use App\Http\Controllers\SuperAdmin\GovernorateController;
use App\Http\Controllers\SuperAdmin\OrderController as SuperAdminOrderController;
use App\Http\Controllers\SuperAdmin\ProductTypeController as SuperAdminProductTypeController;
use App\Http\Controllers\SuperAdmin\SupplierController;
use App\Http\Controllers\SuperAdmin\SupplierProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes (no auth)
|--------------------------------------------------------------------------
*/
Route::prefix('public')->group(function () {
    Route::get('governorates', [PublicGovernorateController::class, 'index']);
    Route::get('product-types', [PublicProductCatalogController::class, 'productTypes']);
    Route::get('categories', [PublicProductCatalogController::class, 'categories']);
    Route::get('maintenance/lookups', [PublicMaintenanceLookupController::class, 'index']);
    Route::get('companies', [PublicCompanyController::class, 'index']);
    Route::get('companies/{company}', [PublicCompanyController::class, 'show']);
    Route::get('companies/{company}/products', [PublicStoreController::class, 'products']);
    Route::get('companies/{company}/products/{companyProduct}/installment-plans', [PublicStoreController::class, 'installmentPlans']);
});

/*
|--------------------------------------------------------------------------
| Customer Routes
|--------------------------------------------------------------------------
*/
Route::prefix('customer')->group(function () {

    Route::post('register', [CustomerAuthController::class, 'register']);
    Route::post('auth/check-phone', [CustomerAuthController::class, 'checkPhone']);
    Route::post('login', [CustomerAuthController::class, 'login']);
    Route::post('register/request-otp', [CustomerAuthController::class, 'requestOtp']);
    Route::post('register/verify', [CustomerAuthController::class, 'verifyRegistration']);

    Route::middleware(['auth:sanctum', 'customer'])->group(function () {
        Route::post('logout', [CustomerAuthController::class, 'logout']);
        Route::get('me', [CustomerAuthController::class, 'me']);
        Route::patch('profile', [CustomerAuthController::class, 'updateProfile']);

        Route::get('orders', [CustomerOrderController::class, 'index']);
        Route::post('orders', [CustomerOrderController::class, 'store']);
        Route::get('orders/{order}', [CustomerOrderController::class, 'show']);

        Route::get('maintenance-requests', [CustomerMaintenanceRequestController::class, 'index']);
        Route::post('maintenance-requests', [CustomerMaintenanceRequestController::class, 'store']);
        Route::get('maintenance-requests/{maintenanceRequest}', [CustomerMaintenanceRequestController::class, 'show']);

        Route::post('companies/{company}/like', [CustomerCompanyEngagementController::class, 'like']);
        Route::delete('companies/{company}/like', [CustomerCompanyEngagementController::class, 'unlike']);
        Route::post('companies/{company}/rating', [CustomerCompanyEngagementController::class, 'rate']);
        Route::delete('companies/{company}/rating', [CustomerCompanyEngagementController::class, 'removeRating']);
    });
});

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

        // Product types & categories
        Route::get('product-types', [SuperAdminProductTypeController::class, 'index']);
        Route::post('product-types', [SuperAdminProductTypeController::class, 'store']);
        Route::get('product-types/{productType}', [SuperAdminProductTypeController::class, 'show']);
        Route::post('product-types/{productType}', [SuperAdminProductTypeController::class, 'update']);
        Route::delete('product-types/{productType}', [SuperAdminProductTypeController::class, 'destroy']);

        Route::get('categories', [SuperAdminCategoryController::class, 'index']);
        Route::post('categories', [SuperAdminCategoryController::class, 'store']);
        Route::get('categories/{category}', [SuperAdminCategoryController::class, 'show']);
        Route::post('categories/{category}', [SuperAdminCategoryController::class, 'update']);
        Route::delete('categories/{category}', [SuperAdminCategoryController::class, 'destroy']);

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

        // Orders (monitoring)
        Route::get('orders', [SuperAdminOrderController::class, 'index']);
        Route::get('orders/{order}', [SuperAdminOrderController::class, 'show']);
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

        // Customers
        Route::get('customers', [CompanyCustomerController::class, 'index']);

        // Orders
        Route::get('orders', [CompanyOrderController::class, 'index']);
        Route::post('orders', [CompanyOrderController::class, 'store']);
        Route::get('orders/{order}', [CompanyOrderController::class, 'show']);
        Route::patch('orders/{order}/status', [CompanyOrderController::class, 'updateStatus']);
    });
});
