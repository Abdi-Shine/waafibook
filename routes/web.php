<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

Route::get('/', function () { return redirect()->route('login'); })->name('landing');
Route::get('/offline', fn() => view('offline'))->name('offline');
Route::get('/manifest.json', fn() => response()->file(public_path('manifest.json'), ['Content-Type' => 'application/manifest+json']))->name('manifest');
Route::get('/sw.js', fn() => response()->file(public_path('sw.js'), ['Content-Type' => 'application/javascript', 'Service-Worker-Allowed' => '/']))->name('sw');
Route::get('/demo', [\App\Http\Controllers\DemoRequestController::class, 'index'])->name('demo.request');
Route::post('/demo', [\App\Http\Controllers\DemoRequestController::class, 'store'])->name('demo.request.store');
Route::get('/terms', function() { return view('terms'); })->name('terms');
Route::get('/privacy', function() { return view('privacy'); })->name('privacy');

// Public, signed link so a customer can open an invoice PDF (e.g. from WhatsApp) without logging in.
Route::get('/invoice/{id}/view', [App\Http\Controllers\SalesController::class, 'publicPdf'])
    ->name('sales.invoice.public-pdf')
    ->middleware('signed');

// Public, signed link so a customer can open their statement PDF (e.g. from WhatsApp) without logging in.
Route::get('/statement/{id}/view', [App\Http\Controllers\CustomerController::class, 'publicStatement'])
    ->name('customer.statement.public-pdf')
    ->middleware('signed');

// Public, signed link so a supplier can open their statement PDF (e.g. from WhatsApp) without logging in.
Route::get('/supplier-statement/{id}/view', [App\Http\Controllers\SupplierController::class, 'publicStatement'])
    ->name('supplier.statement.public-pdf')
    ->middleware('signed');

Route::get('/dashboard', function () {
    $accounts = \App\Models\Account::all();

    $assets = $accounts->where('category', 'assets')->sum('balance');
    $liabilities = \App\Models\Supplier::sum('amount_balance');
    $baseEquity = $accounts->where('category', 'equity')->sum('balance');
    $revenue = $accounts->where('category', 'revenue')->sum('balance');
    $expenses = $accounts->where('category', 'expenses')->sum('balance');
    $netProfit = $revenue - $expenses;
    $cashOnHand = $accounts->where('category', 'assets')
        ->filter(function($account) {
            $name = strtolower($account->name);
            return str_contains($name, 'cash') || str_contains($name, 'bank');
        })->sum('balance');

    // Read from the Inventory GL account itself rather than recalculated as
    // qty * today's purchase_price, so this stays consistent with the
    // Product Inventory page and Chart of Accounts (see ProductController::index).
    $inventoryAccount = \App\Models\Account::query()->where('code', '1150')->first()
        ?: \App\Models\Account::query()->where('type', 'inventory')->first()
        ?: \App\Models\Account::query()->where('name', 'like', '%Inventory%')->first();
    $stockValue = $inventoryAccount
        ? \App\Models\JournalItem::query()->where('account_id', $inventoryAccount->id)
            ->selectRaw('SUM(debit) - SUM(credit) as balance')->value('balance') ?? 0
        : 0;

    // Read from the Accounts Receivable GL account itself, same pattern as
    // Stock Value above — Customer::sum('amount_balance') was used here
    // before, but that field goes negative the moment a customer is owed a
    // refund (e.g. a return on an already-paid invoice), which isn't a
    // receivable at all; the GL correctly books that as a separate
    // liability instead, so it doesn't drag this figure negative.
    $receivableAccount = \App\Models\Account::query()->where('code', '1140')->first()
        ?: \App\Models\Account::query()->where('name', 'like', '%Receivable%')->first();
    $accountsReceivable = $receivableAccount
        ? \App\Models\JournalItem::query()->where('account_id', $receivableAccount->id)
            ->selectRaw('SUM(debit) - SUM(credit) as balance')->value('balance') ?? 0
        : 0;

    $stats = [
        'assets' => $assets,
        'liabilities' => $liabilities,
        'equity' => $baseEquity + $netProfit,
        'revenue' => $revenue,
        'expenses' => $expenses,
        'net_profit' => $netProfit,
        'cash_on_hand' => $cashOnHand,
        'stock_value' => $stockValue,
        'accounts_receivable' => $accountsReceivable,
        
        // Featured Order Counts
        'orders_placed'    => \App\Models\SalesOrder::count(),
        'orders_pending'   => \App\Models\SalesOrder::where('status', 'pending')->count(),
        'orders_partial'   => \App\Models\SalesOrder::where('status', 'partial')->count(),
        'orders_completed' => \App\Models\SalesOrder::where('status', 'completed')->count(),

        'total_sales_value' => \App\Models\SalesOrder::sum('total_amount'),
        'total_paid'        => \App\Models\SalesOrder::sum('paid_amount'),
        'total_due'         => \App\Models\SalesOrder::sum('due_amount'),
        'orders_cancelled'  => \App\Models\SalesOrder::where('status', 'cancelled')->count(),
        'orders_returned'   => \App\Models\SalesOrder::where('status', 'returned')->count(),

        // Purchase Statistics
        'purchase_total'    => \App\Models\PurchaseBill::sum('total_amount'),
        'purchase_paid'     => \App\Models\PurchaseBill::sum('paid_amount'),
        'purchase_due'      => \App\Models\PurchaseBill::sum('balance_amount'),
        'purchase_count'    => \App\Models\PurchaseBill::count(),
        'purchase_pending'  => \App\Models\PurchaseBill::where('status', 'pending')->count(),
        'purchase_completed'=> \App\Models\PurchaseBill::where('status', 'completed')->count(),
    ];

    // Monthly Statistics
    $monthlySales = [];
    $monthlyProfit = [];
    for ($i = 1; $i <= 12; $i++) {
        $sales = \App\Models\SalesOrder::whereMonth('invoice_date', $i)->whereYear('invoice_date', date('Y'))->sum('total_amount');
        $monthlySales[] = (float)$sales;
        
        // Simple profit calculation: Sales minus COGS
        $cogs = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->join('products', 'sales_order_items.product_id', '=', 'products.id')
            ->where('sales_orders.company_id', auth()->user()->company_id)
            ->whereMonth('sales_orders.invoice_date', $i)
            ->whereYear('sales_orders.invoice_date', date('Y'))
            ->sum(DB::raw('sales_order_items.quantity * products.purchase_price'));
        
        $monthlyProfit[] = (float)($sales - $cogs);
    }

    $stats['monthly_sales'] = $monthlySales;
    $stats['monthly_profit'] = $monthlyProfit;
    $stats['max_monthly_val'] = max(array_merge($monthlySales, $monthlyProfit, [100]));

    $orderCount = $stats['orders_placed'];
    $customerCount = \App\Models\Customer::count();

    return view('admin.index', compact('stats', 'orderCount', 'customerCount'));
})->middleware(['auth', 'verified', 'permission:Dashboard'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/ask-ai', [App\Http\Controllers\AskAiController::class, 'index'])->name('ask-ai');
    Route::post('/ask-ai', [App\Http\Controllers\AskAiController::class, 'ask'])->name('ask-ai.ask');

    Route::post('/announcements/{id}/dismiss', function ($id) {
        $dismissed = session('dismissed_announcements', []);
        $dismissed[] = (int) $id;
        session(['dismissed_announcements' => array_unique($dismissed)]);
        return response()->noContent();
    })->name('announcements.dismiss');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/lock-screen', function () {
        return view('auth.lock-screen');
    })->name('lock-screen');

    Route::post('/unlock', function (Request $request) {
        if (Hash::check($request->password, Auth::user()->password)) {
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false, 'message' => 'The password you entered is incorrect.']);
    })->name('unlock');

    Route::get('/profile-user', [App\Http\Controllers\CompanyController::class, 'profileUser'])->name('profile-user');

    // Dashboard Sub-routes
    Route::middleware('permission:Dashboard')->group(function () {
        Route::get('/company-overview', [App\Http\Controllers\CompanyController::class, 'companyOverviewDashboard'])->name('company_overview_dashboard');
        Route::get('/branch-performance', [App\Http\Controllers\CompanyController::class, 'branchPerformanceDashboard'])->name('branch_performance_dashboard');
        Route::get('/sales-analytics', [App\Http\Controllers\CompanyController::class, 'salesAnalyticsDashboard'])->name('sales_analytics_dashboard');
        Route::get('/inventory-overview', [App\Http\Controllers\CompanyController::class, 'inventoryOverviewDashboard'])->name('inventory_overview_dashboard');
        Route::get('/financial-snapshot', [App\Http\Controllers\CompanyController::class, 'financialSnapshotDashboard'])->name('financial_snapshot_dashboard');
        Route::get('/company-structure', [App\Http\Controllers\CompanyController::class, 'index'])->name('company-structure');
    });

    // System Administration
    Route::middleware('permission:System Admin')->group(function () {
        Route::get('/company-settings', [App\Http\Controllers\CompanyController::class, 'settings'])->name('company-settings');
        Route::get('/feature-settings', [App\Http\Controllers\FeatureSettingController::class, 'index'])->name('feature-settings');
        Route::post('/feature-settings/update', [App\Http\Controllers\FeatureSettingController::class, 'update'])->name('feature-settings.update');
        Route::post('/feature-settings/reset', [App\Http\Controllers\FeatureSettingController::class, 'reset'])->name('feature-settings.reset');
        Route::post('/company/update', [App\Http\Controllers\CompanyController::class, 'updateCompany'])->name('company.update');
        Route::post('/branch/create', [App\Http\Controllers\CompanyController::class, 'storeBranch'])->name('branch.store');
        Route::put('/branch/update/{id}', [App\Http\Controllers\CompanyController::class, 'updateBranch'])->name('branch.update')->middleware('tenant.owns:branches');
        Route::delete('/branch/delete/{id}', [App\Http\Controllers\CompanyController::class, 'destroyBranch'])->name('branch.delete')->middleware(['tenant.owns:branches', 'permission:System Admin,delete', 'delete.password']);
        Route::get('/capital-deposit', [App\Http\Controllers\CapitalDepositController::class, 'index'])->name('capital-deposit');
        Route::post('/capital-deposit', [App\Http\Controllers\CapitalDepositController::class, 'store'])->name('capital-deposit.store');
        Route::post('/shareholder/store', [App\Http\Controllers\CapitalDepositController::class, 'storeShareholder'])->name('shareholder.store');
        Route::put('/shareholder/update/{id}', [App\Http\Controllers\CapitalDepositController::class, 'updateShareholder'])->name('shareholder.update')->middleware('tenant.owns:shareholders');
        Route::delete('/shareholder/delete/{id}', [App\Http\Controllers\CapitalDepositController::class, 'destroyShareholder'])->name('shareholder.delete')->middleware(['tenant.owns:shareholders', 'permission:System Admin,delete', 'delete.password']);
        Route::get('/shareholder/{id}/statement', [App\Http\Controllers\CapitalDepositController::class, 'statement'])->name('shareholder.statement')->middleware('tenant.owns:shareholders');

        // Employee CRUD routes
        Route::get('/employees', [App\Http\Controllers\EmployeeController::class, 'index'])->name('employee.index');
        Route::get('/employees/assign-login', [App\Http\Controllers\EmployeeController::class, 'assignLogin'])->name('employee.assign-login');
        Route::post('/employees/assign-login/{id}', [App\Http\Controllers\EmployeeController::class, 'updateLoginAccess'])->name('employee.update-login')->middleware('tenant.owns:employees');
        Route::get('/employees/create', [App\Http\Controllers\EmployeeController::class, 'create'])->name('employee.create');
        Route::post('/employees', [App\Http\Controllers\EmployeeController::class, 'store'])->name('employee.store');
        Route::put('/employees/{id}', [App\Http\Controllers\EmployeeController::class, 'update'])->name('employee.update')->middleware('tenant.owns:employees');
        Route::delete('/employees/{id}', [App\Http\Controllers\EmployeeController::class, 'destroy'])->name('employee.destroy')->middleware(['tenant.owns:employees', 'permission:System Admin,delete', 'delete.password']);

        // Role Management Routes
        Route::get('/roles', [App\Http\Controllers\RoleController::class, 'index'])->name('role.index');
        Route::post('/roles', [App\Http\Controllers\RoleController::class, 'store'])->name('role.store');
        Route::put('/roles/{id}', [App\Http\Controllers\RoleController::class, 'update'])->name('role.update')->middleware('tenant.owns:roles');
        Route::delete('/roles/{id}', [App\Http\Controllers\RoleController::class, 'destroy'])->name('role.destroy')->middleware(['tenant.owns:roles', 'permission:System Admin,delete', 'delete.password']);

        Route::get('/backup-restore', [App\Http\Controllers\CompanyController::class, 'backupRestore'])->name('backup-restore');
        Route::post('/backup/create', [App\Http\Controllers\CompanyController::class, 'createBackup'])->name('backup.create');
        Route::get('/backup/download/{id}', [App\Http\Controllers\CompanyController::class, 'downloadBackup'])->name('backup.download')->middleware('tenant.owns:backups');
        Route::delete('/backup/delete/{id}', [App\Http\Controllers\CompanyController::class, 'deleteBackup'])->name('backup.delete')->middleware(['tenant.owns:backups', 'permission:System Admin,delete', 'delete.password']);
        Route::post('/backup/restore/{id}', [App\Http\Controllers\CompanyController::class, 'restoreBackup'])->name('backup.restore')->middleware('tenant.owns:backups');
        Route::post('/backup/gmail', [App\Http\Controllers\CompanyController::class, 'backupToGmail'])->name('backup.gmail');
        Route::post('/backup/settings', [App\Http\Controllers\CompanyController::class, 'updateBackupSettings'])->name('backup.settings.update');
        Route::post('/backup/trigger-scheduled', [App\Http\Controllers\CompanyController::class, 'triggerScheduledBackup'])->name('backup.scheduled.trigger');
    });

    // Accounting
    Route::middleware('permission:Accounting')->group(function () {
        Route::get('/accounts', [App\Http\Controllers\AccountController::class, 'index'])->name('account.index');
        Route::get('/accounts/export', [App\Http\Controllers\AccountController::class, 'export'])->name('account.export');
        Route::get('/accounts/download-template', [App\Http\Controllers\AccountController::class, 'downloadTemplate'])->name('account.download-template');
        Route::post('/accounts/import', [App\Http\Controllers\AccountController::class, 'import'])->name('account.import');
        Route::get('/accounts/ledger', [App\Http\Controllers\AccountController::class, 'ledger'])->name('account.ledger');
        Route::get('/accounts/trial-balance', [App\Http\Controllers\AccountController::class, 'trialBalance'])->name('account.trial-balance');
        Route::get('/accounts/bank', [App\Http\Controllers\AccountController::class, 'bank'])->name('local_bank_account.index');
        Route::get('/cash-management', [App\Http\Controllers\AccountController::class, 'cashManagement'])->name('cash_management.index');
        Route::get('/cash-in-hand', [App\Http\Controllers\AccountController::class, 'cashInHand'])->name('cash_in_hand.index');
        Route::get('/account-management', [App\Http\Controllers\BankTransactionController::class, 'index'])->name('account_management.index');
        Route::post('/account-management/deposit', [App\Http\Controllers\BankTransactionController::class, 'storeDeposit'])->name('bank.transaction.deposit');
        Route::post('/account-management/withdraw', [App\Http\Controllers\BankTransactionController::class, 'storeWithdrawal'])->name('bank.transaction.withdraw');
        Route::post('/account-management/transfer', [App\Http\Controllers\BankTransactionController::class, 'storeTransfer'])->name('bank.transaction.transfer');
        Route::post('/account-management/adjustment', [App\Http\Controllers\BankTransactionController::class, 'storeAdjustment'])->name('bank.transaction.adjustment');
        Route::put('/account-management/transaction/{id}', [App\Http\Controllers\BankTransactionController::class, 'updateTransaction'])->name('bank.transaction.update');
        Route::delete('/account-management/transaction/{id}', [App\Http\Controllers\BankTransactionController::class, 'destroyTransaction'])->name('bank.transaction.destroy');
        Route::post('/accounts', [App\Http\Controllers\AccountController::class, 'store'])->name('account.store');
        Route::put('/accounts/{id}', [App\Http\Controllers\AccountController::class, 'update'])->name('account.update')->middleware('tenant.owns:chart_of_accounts');
        Route::delete('/accounts/{id}', [App\Http\Controllers\AccountController::class, 'destroy'])->name('account.destroy')->middleware(['tenant.owns:chart_of_accounts', 'permission:Accounting,delete', 'delete.password']);
        Route::patch('/accounts/{id}/toggle-status', [App\Http\Controllers\AccountController::class, 'toggleStatus'])->name('account.toggle-status')->middleware('tenant.owns:chart_of_accounts');
        Route::post('/accounts/recalculate-balances', [App\Http\Controllers\AccountController::class, 'recalculateBalances'])->name('account.recalculate-balances');

        // Journal Entry Routes
        Route::get('/journal-entries', [App\Http\Controllers\JournalEntryController::class, 'index'])->name('journal.index');
        Route::post('/journal-entries', [App\Http\Controllers\JournalEntryController::class, 'store'])->name('journal.store');
        Route::delete('/journal-entries/{id}', [App\Http\Controllers\JournalEntryController::class, 'destroy'])->name('journal.destroy')->middleware(['tenant.owns:journal_entries', 'permission:Accounting,delete', 'delete.password']);
    });

    // Parties
    Route::middleware('permission:Parties')->group(function () {
        Route::get('/parties/ledger', [App\Http\Controllers\PartiesController::class, 'ledgerView'])->name('parties.ledger');
        Route::get('/parties/{type}/{id}/ledger-data', [App\Http\Controllers\PartiesController::class, 'ledgerData'])->name('parties.ledger-data');
        Route::delete('/parties/{type}/{id}/opening-balance', [App\Http\Controllers\PartiesController::class, 'deleteOpeningBalance'])->name('parties.opening-balance.delete')->middleware(['permission:Parties,delete', 'delete.password']);

        Route::get('/customers', [App\Http\Controllers\CustomerController::class, 'index'])->name('customer.index');
        Route::get('/customers/export', [App\Http\Controllers\CustomerController::class, 'export'])->name('customer.export');
        Route::get('/customers/{id}/statement', [App\Http\Controllers\CustomerController::class, 'statement'])->name('customer.statement')->middleware('tenant.owns:customers');
        Route::get('/customers/{id}/statement/download', [App\Http\Controllers\CustomerController::class, 'downloadStatement'])->name('download.statement.customer')->middleware('tenant.owns:customers');
        Route::get('/customers/{id}/statement/email', [App\Http\Controllers\CustomerController::class, 'emailStatement'])->name('email.statement.customer')->middleware('tenant.owns:customers');
        Route::post('/customers', [App\Http\Controllers\CustomerController::class, 'store'])->name('customer.store');
        Route::post('/customers/import', [App\Http\Controllers\CustomerController::class, 'import'])->name('customer.import');
        Route::put('/customers/{id}', [App\Http\Controllers\CustomerController::class, 'update'])->name('customer.update')->middleware('tenant.owns:customers');
        Route::patch('/customers/{id}/deactivate', [App\Http\Controllers\CustomerController::class, 'deactivate'])->name('customer.deactivate')->middleware(['tenant.owns:customers', 'permission:Parties,delete']);
        Route::delete('/customers/{id}', [App\Http\Controllers\CustomerController::class, 'destroy'])->name('customer.destroy')->middleware(['tenant.owns:customers', 'permission:Parties,delete', 'delete.password']);

        Route::get('/suppliers', [App\Http\Controllers\SupplierController::class, 'index'])->name('supplier.index');
        Route::get('/suppliers/export', [App\Http\Controllers\SupplierController::class, 'export'])->name('supplier.export');
        Route::get('/suppliers/{id}/statement', [App\Http\Controllers\SupplierController::class, 'statement'])->name('supplier.statement')->middleware('tenant.owns:suppliers');
        Route::get('/suppliers/{id}/statement/download', [App\Http\Controllers\SupplierController::class, 'downloadStatement'])->name('download.statement.supplier')->middleware('tenant.owns:suppliers');
        Route::get('/suppliers/{id}/statement/email', [App\Http\Controllers\SupplierController::class, 'emailStatement'])->name('email.statement.supplier')->middleware('tenant.owns:suppliers');
        Route::post('/suppliers', [App\Http\Controllers\SupplierController::class, 'store'])->name('supplier.store');
        Route::post('/suppliers/import', [App\Http\Controllers\SupplierController::class, 'import'])->name('supplier.import');
        Route::put('/suppliers/{id}', [App\Http\Controllers\SupplierController::class, 'update'])->name('supplier.update')->middleware('tenant.owns:suppliers');
        Route::patch('/suppliers/{id}/deactivate', [App\Http\Controllers\SupplierController::class, 'deactivate'])->name('supplier.deactivate')->middleware(['tenant.owns:suppliers', 'permission:Parties,delete']);
        Route::delete('/suppliers/{id}', [App\Http\Controllers\SupplierController::class, 'destroy'])->name('supplier.destroy')->middleware(['tenant.owns:suppliers', 'permission:Parties,delete', 'delete.password']);
    });

    // Product
    Route::middleware('permission:Product')->group(function () {
        Route::get('/products', [App\Http\Controllers\ProductController::class, 'index'])->name('product.index');
        Route::get('/products/export', [App\Http\Controllers\ProductController::class, 'export'])->name('product.export');
        Route::get('/products/download-template', [App\Http\Controllers\ProductController::class, 'downloadTemplate'])->name('product.download-template');
        Route::post('/products/import', [App\Http\Controllers\ProductController::class, 'import'])->name('product.import');
        Route::post('/products/store', [App\Http\Controllers\ProductController::class, 'store'])->name('product.store');
        Route::put('/products/update/{id}', [App\Http\Controllers\ProductController::class, 'update'])->name('product.update')->middleware('tenant.owns:products');
        Route::delete('/products/delete/{id}', [App\Http\Controllers\ProductController::class, 'destroy'])->name('product.delete')->middleware(['tenant.owns:products', 'permission:Product,delete', 'delete.password']);
        Route::get('/products/ledger', [App\Http\Controllers\ProductController::class, 'ledgerView'])->name('product.ledger');
        Route::get('/products/{id}/ledger-data', [App\Http\Controllers\ProductController::class, 'ledgerData'])->name('product.ledger-data')->middleware('tenant.owns:products');
        Route::delete('/products/{id}/opening-stock', [App\Http\Controllers\ProductController::class, 'deleteOpeningStock'])->name('product.opening-stock.delete')->middleware(['tenant.owns:products', 'permission:Product,delete', 'delete.password']);
        Route::post('/products/update-status/{id}', [App\Http\Controllers\ProductController::class, 'updateStatus'])->name('product.update-status')->middleware('tenant.owns:products');
        Route::post('/products/quick-store', [App\Http\Controllers\ProductController::class, 'quickStore'])->name('product.quick.store');
        Route::get('/products/low-stock', [App\Http\Controllers\ProductController::class, 'lowStockView'])->name('low-stock.view');
        Route::get('/products/stock-summary', [App\Http\Controllers\ProductController::class, 'stockSummaryView'])->name('store-summary.view');
        Route::get('/products/stock-adjustment', [App\Http\Controllers\ProductController::class, 'stockAdjustmentView'])->name('stock-adjustment.view');
        Route::post('/products/stock-adjustment', [App\Http\Controllers\ProductController::class, 'stockAdjustmentStore'])->name('stock-adjustment.store');

        // Category Routes
        Route::get('/categories', [App\Http\Controllers\CategoryController::class, 'index'])->name('category.index');
        Route::post('/categories/store', [App\Http\Controllers\CategoryController::class, 'store'])->name('category.store');
        Route::put('/categories/update/{id}', [App\Http\Controllers\CategoryController::class, 'update'])->name('category.update');
        Route::delete('/categories/delete/{id}', [App\Http\Controllers\CategoryController::class, 'destroy'])->name('category.delete')->middleware(['permission:Product,delete', 'delete.password']);

        Route::get('/units', [App\Http\Controllers\UnitController::class, 'index'])->name('units.index');
        Route::post('/units/store', [App\Http\Controllers\UnitController::class, 'store'])->name('units.store');
        Route::put('/units/update/{id}', [App\Http\Controllers\UnitController::class, 'update'])->name('units.update');
        Route::delete('/units/delete/{id}', [App\Http\Controllers\UnitController::class, 'destroy'])->name('units.delete')->middleware(['permission:Product,delete', 'delete.password']);
    });

    // Purchase
    Route::middleware('permission:Purchase')->group(function () {
        Route::get('/purchase/orders', [App\Http\Controllers\PurchaseController::class, 'index'])->name('purchase.order.index');
        Route::get('/purchase/orders/create', [App\Http\Controllers\PurchaseController::class, 'index'])->name('purchase.order.create');
        Route::post('/purchase/orders', [App\Http\Controllers\PurchaseController::class, 'store'])->name('purchase.order.store');
        Route::get('/purchase/orders/{id}', [App\Http\Controllers\PurchaseController::class, 'show'])->name('purchase.order.show')->middleware('tenant.owns:purchase_orders');
        Route::get('/purchase/orders/{id}/print', [App\Http\Controllers\PurchaseController::class, 'printOrder'])->name('purchase.order.print')->middleware('tenant.owns:purchase_orders');
        Route::get('/purchase/orders/{id}/download', [App\Http\Controllers\PurchaseController::class, 'downloadOrderPdf'])->name('purchase.order.download')->middleware('tenant.owns:purchase_orders');
        Route::put('/purchase/orders/{id}', [App\Http\Controllers\PurchaseController::class, 'update'])->name('purchase.order.update')->middleware('tenant.owns:purchase_orders');
        Route::delete('/purchase/orders/{id}', [App\Http\Controllers\PurchaseController::class, 'destroy'])->name('purchase.order.delete')->middleware(['tenant.owns:purchase_orders', 'permission:Purchase,delete', 'delete.password']);
        Route::post('/purchase/orders/update-status/{id}', [App\Http\Controllers\PurchaseController::class, 'updateStatus'])->name('purchase.order.update-status')->middleware('tenant.owns:purchase_orders');
        Route::post('/purchase/orders/email/{id}', [App\Http\Controllers\PurchaseController::class, 'emailOrder'])->name('purchase.order.email')->middleware('tenant.owns:purchase_orders');

        Route::get('/purchase/bills/create', [App\Http\Controllers\PurchaseController::class, 'createBill'])->name('purchase.bill.create');
        Route::post('/purchase/bills', [App\Http\Controllers\PurchaseController::class, 'storeBill'])->name('purchase.bill.store');
        Route::post('/purchase/bills/draft', [App\Http\Controllers\PurchaseController::class, 'storeDraftBill'])->name('purchase.bill.draft');
        Route::post('/purchase/bills/draft/{id}', [App\Http\Controllers\PurchaseController::class, 'updateDraftBill'])->name('purchase.bill.draft.update')->middleware('tenant.owns:purchase_bills');
        Route::get('/purchase/bills/export', [App\Http\Controllers\PurchaseController::class, 'exportBill'])->name('purchase.bill.export');
        Route::get('/purchase/bills/{id}/edit', [App\Http\Controllers\PurchaseController::class, 'editBill'])->name('purchase.bill.edit')->middleware('tenant.owns:purchase_bills');
        Route::post('/purchase/bills/update/{id}', [App\Http\Controllers\PurchaseController::class, 'updateBill'])->name('purchase.bill.update')->middleware('tenant.owns:purchase_bills');
        Route::get('/purchase/bills/{id}', [App\Http\Controllers\PurchaseController::class, 'showBill'])->name('purchase.bill.show')->middleware('tenant.owns:purchase_bills');
        Route::delete('/purchase/bills/{id}', [App\Http\Controllers\PurchaseController::class, 'destroyBill'])->name('purchase.bill.delete')->middleware(['tenant.owns:purchase_bills', 'permission:Purchase,delete', 'delete.password']);
        Route::get('/purchase/bills', [App\Http\Controllers\PurchaseController::class, 'billIndex'])->name('purchase.bill');
        Route::get('/purchase/view', [App\Http\Controllers\PurchaseController::class, 'billIndex'])->name('purchase.view');
        Route::get('/purchase/expense', [App\Http\Controllers\PurchaseController::class, 'expense'])->name('purchase.expense');
        Route::post('/purchase/expense', [App\Http\Controllers\PurchaseController::class, 'storeExpense'])->name('purchase.expense.store');
        Route::get('/purchase/expense/{id}/view', [App\Http\Controllers\PurchaseController::class, 'viewExpense'])->name('purchase.expense.view')->middleware('tenant.owns:purchase_expenses');
        Route::get('/purchase/expense/{id}/pdf', [App\Http\Controllers\PurchaseController::class, 'downloadExpensePdf'])->name('purchase.expense.pdf')->middleware('tenant.owns:purchase_expenses');
        Route::put('/purchase/expense/{id}', [App\Http\Controllers\PurchaseController::class, 'updateExpense'])->name('purchase.expense.update')->middleware('tenant.owns:purchase_expenses');
        Route::delete('/purchase/expense/{id}', [App\Http\Controllers\PurchaseController::class, 'destroyExpense'])->name('purchase.expense.destroy')->middleware(['tenant.owns:purchase_expenses', 'permission:Purchase,delete', 'delete.password']);
        Route::get('/purchase/returns', [App\Http\Controllers\PurchaseController::class, 'returns'])->name('purchase.returns');
        Route::post('/purchase/returns', [App\Http\Controllers\PurchaseController::class, 'storeReturn'])->name('purchase.return.store');
        Route::delete('/purchase/returns/{id}', [App\Http\Controllers\PurchaseController::class, 'destroyReturn'])->name('purchase.return.delete')->middleware(['tenant.owns:purchase_returns', 'permission:Purchase,delete', 'delete.password']);
        Route::get('/purchase/returns/{id}/view', [App\Http\Controllers\PurchaseController::class, 'viewReturn'])->name('purchase.return.view')->middleware('tenant.owns:purchase_returns');
        Route::get('/purchase/returns/{id}/pdf', [App\Http\Controllers\PurchaseController::class, 'downloadReturnPdf'])->name('purchase.return.pdf')->middleware('tenant.owns:purchase_returns');
        Route::put('/purchase/returns/{id}', [App\Http\Controllers\PurchaseController::class, 'updateReturn'])->name('purchase.return.update')->middleware('tenant.owns:purchase_returns');

        // Payment Out Routes
        Route::get('/payment-out', [App\Http\Controllers\PaymentOutController::class, 'index'])->name('view_payment_out');
        Route::post('/payment-out/store', [App\Http\Controllers\PaymentOutController::class, 'store'])->name('payment_out.store');
        Route::get('/payment-out/view/{id}', [App\Http\Controllers\PaymentOutController::class, 'view'])->name('payment_out.view')->middleware('tenant.owns:supplier_payments');
        Route::get('/payment-out/download/{id}', [App\Http\Controllers\PaymentOutController::class, 'download'])->name('payment_out.download')->middleware('tenant.owns:supplier_payments');
        Route::delete('/payment-out/delete/{id}', [App\Http\Controllers\PaymentOutController::class, 'delete'])->name('payment_out.delete')->middleware(['tenant.owns:supplier_payments', 'permission:Purchase,delete', 'delete.password']);
        Route::post('/payment-out/update-status/{id}', [App\Http\Controllers\PaymentOutController::class, 'updateStatus'])->name('payment_out.update-status')->middleware('tenant.owns:supplier_payments');
    });

    // Sales & POS
    Route::middleware('permission:Sales & POS')->group(function () {
        Route::get('/sales/invoices', [App\Http\Controllers\SalesController::class, 'index'])->name('sales.invoice.view');
        Route::get('/sales/invoices/create', [App\Http\Controllers\SalesController::class, 'create'])->name('sales.invoice.create');
        Route::post('/sales/invoices', [App\Http\Controllers\SalesController::class, 'store'])->name('sales.invoice.store');
        Route::post('/sales/invoices/draft', [App\Http\Controllers\SalesController::class, 'storeDraft'])->name('sales.invoice.draft');
        Route::get('/sales/invoices/{id}', [App\Http\Controllers\SalesController::class, 'show'])->name('sales.invoice.show')->middleware('tenant.owns:sales_orders');
        Route::get('/sales/invoices/{id}/edit', [App\Http\Controllers\SalesController::class, 'edit'])->name('sales.invoice.edit')->middleware('tenant.owns:sales_orders');
        Route::put('/sales/invoices/{id}', [App\Http\Controllers\SalesController::class, 'update'])->name('sales.invoice.update')->middleware('tenant.owns:sales_orders');
        Route::delete('/sales/invoices/{id}', [App\Http\Controllers\SalesController::class, 'destroy'])->name('sales.invoice.delete')->middleware(['tenant.owns:sales_orders', 'permission:Sales & POS,delete', 'delete.password']);
        Route::get('/sales/invoices/{id}/pdf', [App\Http\Controllers\SalesController::class, 'pdf'])->name('sales.invoice.pdf')->middleware('tenant.owns:sales_orders');
        Route::get('/sales/invoices/{id}/pos-pdf', [App\Http\Controllers\SalesController::class, 'posInvoicePdf'])->name('sales.invoice.pos-pdf')->middleware('tenant.owns:sales_orders');
        Route::post('/sales/invoices/{id}/status', [App\Http\Controllers\SalesController::class, 'updateStatus'])->name('sales.invoice.update-status')->middleware('tenant.owns:sales_orders');
        Route::get('/sales/pos', [App\Http\Controllers\SalesController::class, 'pos'])->name('sales.pos.view');
        Route::post('/sales/quick-customer', [App\Http\Controllers\SalesController::class, 'quickStoreCustomer'])->name('sales.quick.customer');

        // Payment In Routes
        Route::get('/payment-in', [App\Http\Controllers\PaymentInController::class, 'index'])->name('view_payment_in');
        Route::post('/payment-in/store', [App\Http\Controllers\PaymentInController::class, 'store'])->name('payment_in.store');
        Route::get('/payment-in/view/{id}', [App\Http\Controllers\PaymentInController::class, 'view'])->name('payment_in.view')->middleware('tenant.owns:payment_ins');
        Route::get('/payment-in/download/{id}', [App\Http\Controllers\PaymentInController::class, 'download'])->name('payment_in.download')->middleware('tenant.owns:payment_ins');
        Route::delete('/payment-in/delete/{id}', [App\Http\Controllers\PaymentInController::class, 'delete'])->name('payment_in.delete')->middleware(['tenant.owns:payment_ins', 'permission:Sales & POS,delete', 'delete.password']);
        Route::post('/payment-in/update-status/{id}', [App\Http\Controllers\PaymentInController::class, 'updateStatus'])->name('payment_in.update-status')->middleware('tenant.owns:payment_ins');

        // Sales Return / Credit Note
        Route::get('/sales-return', [App\Http\Controllers\SalesReturnController::class, 'viewSalesReturn'])->name('sales.return.view');
        Route::post('/sales-return', [App\Http\Controllers\SalesReturnController::class, 'store'])->name('sales.return.store');
        Route::delete('/sales-return/{id}', [App\Http\Controllers\SalesReturnController::class, 'destroy'])->name('sales.return.destroy')->middleware(['tenant.owns:sales_returns', 'permission:Sales & POS,delete', 'delete.password']);
    });

    // Branches
    Route::middleware('permission:Branch & Store')->group(function () {
        Route::get('/branches-view', [App\Http\Controllers\CompanyController::class, 'branchesView'])->name('branches-view');
    });

    // Branch Transfer Routes
    Route::get('/branch-transfer', [App\Http\Controllers\CompanyController::class, 'branchTransfer'])->name('branch-transfer');
    Route::post('/branch-transfer/store', [App\Http\Controllers\CompanyController::class, 'branchTransferStore'])->name('branch-transfer.store');
    Route::get('/branch-transfer/check-stock', [App\Http\Controllers\CompanyController::class, 'getBranchStock'])->name('branch-transfer.check-stock');
    Route::post('/branch-transfer/action/{id}', [App\Http\Controllers\CompanyController::class, 'branchTransferAction'])->name('branch-transfer.action')->middleware('tenant.owns:branch_transfers');
    Route::delete('/branch-transfer/delete/{id}', [App\Http\Controllers\CompanyController::class, 'branchTransferDestroy'])->name('branch-transfer.delete')->middleware(['tenant.owns:branch_transfers', 'permission:Branch & Store,delete', 'delete.password']);

    // Expenses
    Route::middleware('permission:Expenses')->group(function () {
        Route::get('/expenses/view/all', [App\Http\Controllers\ExpenseController::class, 'index'])->name('expenses_view_all');
        Route::get('/payroll/list', [App\Http\Controllers\PayrollController::class, 'index'])->name('payroll.index');
        Route::get('/payroll/generate', [App\Http\Controllers\PayrollController::class, 'create'])->name('payroll.generate');
        Route::post('/payroll/store', [App\Http\Controllers\PayrollController::class, 'store'])->name('payroll.store');
        Route::get('/payroll/view/{id}', [App\Http\Controllers\PayrollController::class, 'show'])->name('payroll.show')->middleware('tenant.owns:payrolls');
        Route::put('/payroll/approve/{id}', [App\Http\Controllers\PayrollController::class, 'approve'])->name('payroll.approve')->middleware('tenant.owns:payrolls');
        Route::put('/payroll/pay/{id}', [App\Http\Controllers\PayrollController::class, 'markAsPaid'])->name('payroll.pay')->middleware('tenant.owns:payrolls');
        Route::delete('/payroll/delete/{id}', [App\Http\Controllers\PayrollController::class, 'destroy'])->name('payroll.delete')->middleware(['tenant.owns:payrolls', 'permission:Expenses,delete', 'delete.password']);
        Route::post('/payroll/repost/{id}', [App\Http\Controllers\PayrollController::class, 'repostJournal'])->name('payroll.repost')->middleware('tenant.owns:payrolls');
        Route::post('/expenses/store', [App\Http\Controllers\ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/expenses/edit/{id}', [App\Http\Controllers\ExpenseController::class, 'edit'])->name('expenses.edit')->middleware('tenant.owns:expenses');
        Route::post('/expenses/update/{id}', [App\Http\Controllers\ExpenseController::class, 'update'])->name('expenses.update')->middleware('tenant.owns:expenses');
        Route::delete('/expenses/delete/{id}', [App\Http\Controllers\ExpenseController::class, 'destroy'])->name('expenses.delete')->middleware(['tenant.owns:expenses', 'permission:Expenses,delete', 'delete.password']);
        Route::get('/expenses/receipt/{id}', [App\Http\Controllers\ExpenseController::class, 'receipt'])->name('expenses.receipt')->middleware('tenant.owns:expenses');

        // Loans
        Route::get('/loans', [App\Http\Controllers\LoanController::class, 'viewLoan'])->name('loan.view');
        Route::post('/loans/store', [App\Http\Controllers\LoanController::class, 'storeLoan'])->name('loan.store');
        Route::put('/loans/update/{id}', [App\Http\Controllers\LoanController::class, 'updateLoan'])->name('loan.update');
        Route::post('/loans/status/{id}', [App\Http\Controllers\LoanController::class, 'changeStatus'])->name('loan.status.update');
        Route::delete('/loans/delete/{id}', [App\Http\Controllers\LoanController::class, 'deleteLoan'])->name('loan.delete')->middleware(['permission:Expenses,delete', 'delete.password']);
        Route::get('/loans/payslip/{id}', [App\Http\Controllers\LoanController::class, 'loanPayslip'])->name('loan.payslip');
    });

    Route::middleware('permission:Reports')->group(function () {
        Route::get('/reports/dashboard', [App\Http\Controllers\ReportController::class, 'dashboard'])->name('all_reports');
        Route::get('/reports/sales', [App\Http\Controllers\ReportController::class, 'salesReport'])->name('reports.sales');
        Route::get('/reports/sales/pdf', [App\Http\Controllers\ReportController::class, 'exportSalesPdf'])->name('reports.sales.pdf');
        Route::get('/reports/sales/excel', [App\Http\Controllers\ReportController::class, 'exportSalesExcel'])->name('reports.sales.excel');
        Route::get('/reports/purchases', [App\Http\Controllers\ReportController::class, 'purchaseReport'])->name('reports.purchases');
        Route::get('/reports/purchases/pdf', [App\Http\Controllers\ReportController::class, 'exportPurchasesPdf'])->name('reports.purchases.pdf');
        Route::get('/reports/purchases/excel', [App\Http\Controllers\ReportController::class, 'exportPurchasesExcel'])->name('reports.purchases.excel');
        Route::get('/reports/daybook', [App\Http\Controllers\ReportController::class, 'daybookReport'])->name('reports.daybook');
        Route::get('/reports/daybook/pdf', [App\Http\Controllers\ReportController::class, 'exportDaybookPdf'])->name('reports.daybook.pdf');
        Route::get('/reports/daybook/excel', [App\Http\Controllers\ReportController::class, 'exportDaybookExcel'])->name('reports.daybook.excel');
        Route::get('/reports/transaction', [App\Http\Controllers\ReportController::class, 'transactionReport'])->name('reports.transaction');
        Route::get('/reports/transaction/pdf', [App\Http\Controllers\ReportController::class, 'exportTransactionPdf'])->name('reports.transaction.pdf');
        Route::get('/reports/transaction/excel', [App\Http\Controllers\ReportController::class, 'exportTransactionExcel'])->name('reports.transaction.excel');
        Route::get('/reports/profit-loss', [App\Http\Controllers\ReportController::class, 'profitLossReport'])->name('reports.profit_loss');
        Route::get('/reports/profit-loss/pdf', [App\Http\Controllers\ReportController::class, 'exportProfitLossPdf'])->name('reports.profit_loss.pdf');
        Route::get('/reports/bill-wise-profit', [App\Http\Controllers\ReportController::class, 'billWiseProfitReport'])->name('reports.bill_wise_profit');
        Route::get('/reports/bill-wise-profit/pdf', [App\Http\Controllers\ReportController::class, 'exportBillWiseProfitPdf'])->name('reports.bill_wise_profit.pdf');

        Route::get('/reports/trial-balance', [App\Http\Controllers\ReportController::class, 'trialBalanceReport'])->name('reports.trial_balance');
        Route::get('/reports/trial-balance/pdf', [App\Http\Controllers\ReportController::class, 'exportTrialBalancePdf'])->name('reports.trial_balance.pdf');
        Route::get('/reports/balance-sheet', [App\Http\Controllers\ReportController::class, 'balanceSheetReport'])->name('reports.balance_sheet');
        Route::get('/reports/balance-sheet/pdf', [App\Http\Controllers\ReportController::class, 'exportBalanceSheetPdf'])->name('reports.balance_sheet.pdf');
        Route::get('/reports/cash-flow', [App\Http\Controllers\ReportController::class, 'cashFlowReport'])->name('reports.cash_flow');
        Route::get('/reports/cash-flow/pdf', [App\Http\Controllers\ReportController::class, 'exportCashFlowPdf'])->name('reports.cash_flow.pdf');
        Route::get('/reports/parties-statement', [App\Http\Controllers\ReportController::class, 'partiesStatementReport'])->name('reports.party_statement');
        Route::get('/reports/parties-statement/pdf', [App\Http\Controllers\ReportController::class, 'exportPartiesStatementPdf'])->name('reports.party_statement.pdf');
        Route::get('/reports/party-wise-profit-loss', [App\Http\Controllers\ReportController::class, 'partyWiseProfitLossReport'])->name('reports.party_wise_profit_loss');
        Route::get('/reports/party-wise-profit-loss/pdf', [App\Http\Controllers\ReportController::class, 'exportPartyWiseProfitLossPdf'])->name('reports.party_wise_profit_loss.pdf');
        Route::get('/reports/party-wise-profit-loss/excel', [App\Http\Controllers\ReportController::class, 'exportPartyWiseProfitLossExcel'])->name('reports.party_wise_profit_loss.excel');
        Route::get('/reports/all-parties', [App\Http\Controllers\ReportController::class, 'allPartiesReport'])->name('reports.party_all');
        Route::get('/reports/all-parties/pdf', [App\Http\Controllers\ReportController::class, 'exportAllPartiesPdf'])->name('reports.party_all.pdf');
        Route::get('/reports/sales-purchase-by-party', [App\Http\Controllers\ReportController::class, 'salesPurchaseByPartyReport'])->name('reports.sales_purchase_by_party');
        Route::get('/reports/sales-purchase-by-party/pdf', [App\Http\Controllers\ReportController::class, 'exportSalesPurchaseByPartyPdf'])->name('reports.sales_purchase_by_party.pdf');
        Route::get('/reports/sales-purchase-by-party-group', [App\Http\Controllers\ReportController::class, 'salesPurchaseByPartyGroupReport'])->name('reports.sales_purchase_by_party_group');
        Route::get('/reports/sales-purchase-by-party-group/pdf', [App\Http\Controllers\ReportController::class, 'exportSalesPurchaseByPartyGroupPdf'])->name('reports.sales_purchase_by_party_group.pdf');
        Route::get('/reports/summary-stock', [App\Http\Controllers\ReportController::class, 'summaryStockReport'])->name('reports.summary_stock');
        Route::get('/reports/summary-stock/pdf', [App\Http\Controllers\ReportController::class, 'exportSummaryStockPdf'])->name('reports.summary_stock.pdf');
        Route::get('/reports/low-stock-summary', [App\Http\Controllers\ReportController::class, 'lowStockSummaryReport'])->name('reports.low_stock_summary');
        Route::get('/reports/low-stock-summary/pdf', [App\Http\Controllers\ReportController::class, 'exportLowStockSummaryPdf'])->name('reports.low_stock_summary.pdf');
        Route::get('/reports/item-report-by-party', [App\Http\Controllers\ReportController::class, 'itemReportByParty'])->name('reports.item_report_by_party');
        Route::get('/reports/item-report-by-party/pdf', [App\Http\Controllers\ReportController::class, 'exportItemReportByPartyPdf'])->name('reports.item_report_by_party.pdf');
        Route::get('/reports/item-wise-profit-loss', [App\Http\Controllers\ReportController::class, 'itemWiseProfitLossReport'])->name('reports.item_wise_profit_loss');
        Route::get('/reports/item-wise-profit-loss/pdf', [App\Http\Controllers\ReportController::class, 'exportItemWiseProfitLossPdf'])->name('reports.item_wise_profit_loss.pdf');
        Route::get('/reports/item-category-wise-profit-loss', [App\Http\Controllers\ReportController::class, 'itemCategoryWiseProfitLossReport'])->name('reports.item_category_wise_profit_loss');
        Route::get('/reports/item-category-wise-profit-loss/pdf', [App\Http\Controllers\ReportController::class, 'exportItemCategoryWiseProfitLossPdf'])->name('reports.item_category_wise_profit_loss.pdf');
        Route::get('/reports/stock-details', [App\Http\Controllers\ReportController::class, 'stockDetailsReport'])->name('reports.stock_details');
        Route::get('/reports/stock-details/pdf', [App\Http\Controllers\ReportController::class, 'exportStockDetailsPdf'])->name('reports.stock_details.pdf');
        Route::get('/reports/items-details', [App\Http\Controllers\ReportController::class, 'itemsDetailsReport'])->name('reports.items_details');
        Route::get('/reports/items-details/pdf', [App\Http\Controllers\ReportController::class, 'exportItemsDetailsPdf'])->name('reports.items_details.pdf');
        Route::get('/reports/sale-purchase-by-item-category', [App\Http\Controllers\ReportController::class, 'salePurchaseByItemCategoryReport'])->name('reports.sale_purchase_by_item_category');
        Route::get('/reports/sale-purchase-by-item-category/pdf', [App\Http\Controllers\ReportController::class, 'exportSalePurchaseByItemCategoryPdf'])->name('reports.sale_purchase_by_item_category.pdf');

        Route::get('/reports/item-wise-discount', [App\Http\Controllers\ReportController::class, 'itemWiseDiscountReport'])->name('reports.item_wise_discount_reports');
        Route::get('/reports/item-wise-discount/pdf', [App\Http\Controllers\ReportController::class, 'exportItemWiseDiscountPdf'])->name('reports.item_wise_discount_reports.pdf');
        Route::get('/reports/item-wise-discount/excel', [App\Http\Controllers\ReportController::class, 'exportItemWiseDiscountExcel'])->name('reports.item_wise_discount_reports.excel');

        Route::get('/reports/expense-category', [App\Http\Controllers\ReportController::class, 'expenseCategoryReport'])->name('reports.expense_category_report');
        Route::get('/reports/expense-category/pdf', [App\Http\Controllers\ReportController::class, 'exportExpenseCategoryPdf'])->name('reports.expense_category_report.pdf');
        Route::get('/reports/expense-category/excel', [App\Http\Controllers\ReportController::class, 'exportExpenseCategoryExcel'])->name('reports.expense_category_report.excel');

        Route::get('/reports/expense-item', [App\Http\Controllers\ReportController::class, 'expenseItemReport'])->name('reports.expense_item_report');
        Route::get('/reports/expense-item/pdf', [App\Http\Controllers\ReportController::class, 'exportExpenseItemPdf'])->name('reports.expense_item_report.pdf');
        Route::get('/reports/expense-item/excel', [App\Http\Controllers\ReportController::class, 'exportExpenseItemExcel'])->name('reports.expense_item_report.excel');

        Route::get('/audit-logs', [App\Http\Controllers\CompanyController::class, 'auditLogs'])->name('audit-logs');
    });

    // Subscription Management — admin-only, same as every other module
    Route::controller(App\Http\Controllers\SubscriptionController::class)->middleware('permission:System Admin')->group(function () {
        Route::get('/subscribers/plans', 'plansIndex')->name('subscribers.plans.index');
        Route::post('/subscribers/plans', 'plansStore')->name('subscribers.plans.store');
        Route::put('/subscribers/plans/{id}', 'plansUpdate')->name('subscribers.plans.update');
        Route::delete('/subscribers/plans/{id}', 'plansDestroy')->name('subscribers.plans.delete');
        Route::get('/subscribers/active', 'subscriptionsIndex')->name('subscribers.subscriptions.index');
        // Checkout (payment collection before subscription is activated)
        Route::get('/subscribers/checkout/{id}', 'checkout')->name('subscribers.checkout');
        Route::post('/subscribers/checkout/{id}', 'processPayment')->name('subscribers.checkout.pay');
    });

    // Host / Service Provider Management
    Route::middleware(['role:Super Admin'])->prefix('super_admin')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Host\HostDashboardController::class, 'dashboard'])->name('host.dashboard');
        Route::get('/companies', [App\Http\Controllers\Host\HostDashboardController::class, 'manageCompanies'])->name('host.companies');
        Route::post('/companies', [App\Http\Controllers\Host\HostDashboardController::class, 'storeCompany'])->name('host.companies.store');
        Route::patch('/companies/{id}/plan', [App\Http\Controllers\Host\HostDashboardController::class, 'managePlan'])->name('host.companies.manage-plan');
        Route::patch('/companies/{id}/status', [App\Http\Controllers\Host\HostDashboardController::class, 'toggleCompanyStatus'])->name('host.companies.toggle-status');
        Route::get('/companies/{id}/show', [App\Http\Controllers\Host\HostDashboardController::class, 'showCompany'])->name('host.companies.show');
        Route::put('/companies/{id}', [App\Http\Controllers\Host\HostDashboardController::class, 'updateCompany'])->name('host.companies.update');
        Route::delete('/companies/{id}', [App\Http\Controllers\Host\HostDashboardController::class, 'destroyCompany'])->name('host.companies.destroy');
        Route::post('/companies/bulk', [App\Http\Controllers\Host\HostDashboardController::class, 'bulkCompanyAction'])->name('host.companies.bulk');
        Route::get('/demo-requests', [App\Http\Controllers\Host\HostDashboardController::class, 'demoRequests'])->name('host.demo_requests');
        Route::patch('/demo-requests/{id}/status', [App\Http\Controllers\Host\HostDashboardController::class, 'updateDemoRequestStatus'])->name('host.demo_requests.status');

        Route::get('/users', [App\Http\Controllers\Host\HostDashboardController::class, 'users'])->name('host.users');
        Route::post('/users/{id}/reset-password', [App\Http\Controllers\Host\HostDashboardController::class, 'resetUserPassword'])->name('host.users.reset-password');
        Route::patch('/users/{id}/status', [App\Http\Controllers\Host\HostDashboardController::class, 'toggleUserStatus'])->name('host.users.toggle-status');
        Route::delete('/users/{id}', [App\Http\Controllers\Host\HostDashboardController::class, 'destroyUser'])->name('host.users.destroy');
        Route::get('/subscriptions', [App\Http\Controllers\Host\HostDashboardController::class, 'subscriptions'])->name('host.subscriptions');
        Route::get('/payments', [App\Http\Controllers\Host\HostDashboardController::class, 'payments'])->name('host.payments');
        Route::post('/payments', [App\Http\Controllers\Host\HostDashboardController::class, 'storePayment'])->name('host.payments.store');
        Route::patch('/payments/{id}/mark-paid', [App\Http\Controllers\Host\HostDashboardController::class, 'markPaymentPaid'])->name('host.payments.mark-paid');
        Route::delete('/payments/{id}', [App\Http\Controllers\Host\HostDashboardController::class, 'destroyPayment'])->name('host.payments.destroy');
        Route::patch('/subscriptions/{id}/cancel', [App\Http\Controllers\Host\HostDashboardController::class, 'cancelSubscriptionAction'])->name('host.subscriptions.cancel');
        Route::post('/subscriptions/{id}/send-invoice', [App\Http\Controllers\Host\HostDashboardController::class, 'sendInvoice'])->name('host.subscriptions.send-invoice');
        Route::get('/subscriptions/{id}/invoice', [App\Http\Controllers\Host\HostDashboardController::class, 'viewInvoice'])->name('host.subscriptions.invoice');
        Route::get('/subscriptions/{id}/invoice/pdf', [App\Http\Controllers\Host\HostDashboardController::class, 'downloadInvoicePdf'])->name('host.subscriptions.invoice.pdf');
        Route::get('/plans', [App\Http\Controllers\Host\HostDashboardController::class, 'plans'])->name('host.plans');
        Route::get('/subscription-plans', [App\Http\Controllers\Host\HostDashboardController::class, 'subscriptionPlans'])->name('host.subscription-plans');
        Route::post('/plans', [App\Http\Controllers\Host\HostDashboardController::class, 'storePlan'])->name('host.plans.store');
        Route::put('/plans/{id}', [App\Http\Controllers\Host\HostDashboardController::class, 'updatePlan'])->name('host.plans.update');
        Route::delete('/plans/{id}', [App\Http\Controllers\Host\HostDashboardController::class, 'destroyPlan'])->name('host.plans.destroy');
        Route::get('/announcements', [App\Http\Controllers\Host\HostDashboardController::class, 'announcements'])->name('host.announcements');
        Route::post('/announcements', [App\Http\Controllers\Host\HostDashboardController::class, 'storeAnnouncement'])->name('host.announcements.store');
        Route::patch('/announcements/{id}/send', [App\Http\Controllers\Host\HostDashboardController::class, 'sendAnnouncement'])->name('host.announcements.send');

        Route::get('/security', [App\Http\Controllers\Host\HostDashboardController::class, 'security'])->name('host.security');
        Route::get('/security/export', [App\Http\Controllers\Host\HostDashboardController::class, 'exportAuditLog'])->name('host.security.export');
        Route::delete('/security/sessions/{id}', [App\Http\Controllers\Host\HostDashboardController::class, 'forceLogoutSession'])->name('host.security.force-logout');

        Route::get('/reports', [App\Http\Controllers\Host\HostDashboardController::class, 'reports'])->name('host.reports');
        Route::get('/settings', [App\Http\Controllers\Host\HostDashboardController::class, 'settings'])->name('host.settings');
        Route::post('/settings', [App\Http\Controllers\Host\HostDashboardController::class, 'updateSettings'])->name('host.settings.update');
        Route::post('/settings/maintenance', [App\Http\Controllers\Host\HostDashboardController::class, 'toggleMaintenanceMode'])->name('host.settings.maintenance');
    });
});

// Plans & Pricing — combined subscribe / renew page (authenticated or public)
Route::get('/subscribers/pricing', function () {
    $plans   = \App\Models\SubscriptionPlan::where('status', 'active')->orderBy('price', 'asc')->get();
    $company = auth()->check() ? \App\Models\Company::find(auth()->user()->company_id) : null;
    $currentSubscription = auth()->check()
        ? \App\Models\Subscription::where('company_id', auth()->user()->company_id)
            ->with('plan')
            ->latest()
            ->first()
        : null;
    return view('frontend.subscribers.plans_pricing', compact('plans', 'company', 'currentSubscription'));
})->name('subscribers_pricing');

require __DIR__ . '/auth.php';

// Temporary Repair Route - Visit ims.thehorntech.com/repair-db to fix database errors
// Admin-only: this drops and reseeds the users table, so it must never be reachable
// without a valid admin session.
Route::get('/repair-db', function () {
    if (!auth()->check() || !in_array(strtolower(trim((string) auth()->user()->role)), ['admin', 'super admin'])) {
        abort(403, 'Only administrators can run the database repair tool.');
    }

    try {
        echo "=== Manual Repair Started ===<br>";

        echo "Cleaning up tables...<br>";
        // Using raw SQL to ensure success even if Schema classes fail
        \Illuminate\Support\Facades\DB::statement('DROP TABLE IF EXISTS sessions');
        \Illuminate\Support\Facades\DB::statement('DROP TABLE IF EXISTS password_reset_tokens');
        \Illuminate\Support\Facades\DB::statement('DROP TABLE IF EXISTS users');

        echo "Running migrations...<br>";
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);

        echo "Running seeders...<br>";
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);

        return "<br><b>Database repaired successfully!</b><br><a href='/'>Click here to go to Login</a>";
    } catch (\Exception $e) {
        return "<br><b style='color:red'>Error:</b> " . $e->getMessage() . "<br><br>Trace:<br>" . nl2br($e->getTraceAsString());
    }
})->middleware(['auth', 'verified']);
