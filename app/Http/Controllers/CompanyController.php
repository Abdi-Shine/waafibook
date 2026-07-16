<?php

namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\AuditLog;
use App\Models\Account;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Shareholder;
use App\Models\Employee;
use App\Models\SalesOrder;
use App\Models\ProductStock;
use App\Models\Backup;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use App\Mail\BackupMail;
use App\Services\StorageUsageService;

class CompanyController extends Controller
{
    public function companyOverviewDashboard(Request $request)
    {
        $company = Company::find(auth()->user()->company_id);
        $dateRange = $request->date_range ?? 'this_month';
        
        // Define date bounds based on range
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        $prevStart = now()->subMonth()->startOfMonth();
        $prevEnd = now()->subMonth()->endOfMonth();

        if ($dateRange === 'today') {
            $start = now()->startOfDay(); $end = now()->endOfDay();
            $prevStart = now()->subDay()->startOfDay(); $prevEnd = now()->subDay()->endOfDay();
        } elseif ($dateRange === 'yesterday') {
            $start = now()->subDay()->startOfDay(); $end = now()->subDay()->endOfDay();
            $prevStart = now()->subDays(2)->startOfDay(); $prevEnd = now()->subDays(2)->endOfDay();
        } elseif ($dateRange === 'last_7_days') {
            $start = now()->subDays(7)->startOfDay(); $end = now();
            $prevStart = now()->subDays(14)->startOfDay(); $prevEnd = now()->subDays(7)->endOfDay();
        } elseif ($dateRange === 'last_30_days') {
            $start = now()->subDays(30)->startOfDay(); $end = now();
            $prevStart = now()->subDays(60)->startOfDay(); $prevEnd = now()->subDays(30)->endOfDay();
        } elseif ($dateRange === 'this_year') {
            $start = now()->startOfYear(); $end = now()->endOfYear();
            $prevStart = now()->subYear()->startOfYear(); $prevEnd = now()->subYear()->endOfYear();
        }

        // Current Period Stats
        $totalRevenue = SalesOrder::query()->whereBetween('invoice_date', [$start, $end])->sum('paid_amount');
        $totalExpenses = Expense::query()->whereBetween('expense_date', [$start, $end])->sum('amount');
        
        // Previous Period Stats
        $prevRevenue = SalesOrder::query()->whereBetween('invoice_date', [$prevStart, $prevEnd])->sum('paid_amount');
        $prevExpenses = Expense::query()->whereBetween('expense_date', [$prevStart, $prevEnd])->sum('amount');

        $revenueGrowth = $prevRevenue > 0 ? (($totalRevenue - $prevRevenue) / $prevRevenue) * 100 : 0;
        $expenseGrowth = $prevExpenses > 0 ? (($totalExpenses - $prevExpenses) / $prevExpenses) * 100 : 0;

        $stats = [
            'revenue' => $totalRevenue,
            'prev_revenue' => $prevRevenue,
            'revenue_growth' => $revenueGrowth,
            'expenses' => $totalExpenses,
            'prev_expenses' => $prevExpenses,
            'expense_growth' => $expenseGrowth,
            'profit' => $totalRevenue - $totalExpenses,
            'branches' => Branch::query()->count(),
        ];

        // Recent Transactions (Union of Sales and Expenses)
        $sales = SalesOrder::query()->select('id', 'invoice_date as date', DB::raw("CONCAT('#ORD-', id) as txn_id"), 'branch_id', DB::raw("'Revenue' as type"), 'paid_amount as amount', DB::raw("'Completed' as status"))
            ->latest('invoice_date')
            ->take(5);
        $expenses = Expense::query()->select('id', 'expense_date as date', DB::raw("CONCAT('#EXP-', id) as txn_id"), 'branch_id', DB::raw("'Expense' as type"), 'amount', 'status')
            ->latest('expense_date')
            ->take(5);
        
        $recentTxns = $sales->get()->concat($expenses->get())->sortByDesc('date')->take(10);
        $branches = Branch::query()->pluck('name', 'id');

        // Chart Data (Last 6 Months)
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $months->push(now()->subMonths($i));
        }

        $chartData = $months->map(function($date) {
            $m = $date->month;
            $y = $date->year;
            return [
                'label' => $date->format('M Y'),
                'revenue' => SalesOrder::query()->whereYear('invoice_date', $y)->whereMonth('invoice_date', $m)->sum('paid_amount'),
                'expenses' => Expense::query()->whereYear('expense_date', $y)->whereMonth('expense_date', $m)->sum('amount'),
            ];
        });

        // Monthly Profit Chart (Current Year)
        $monthlyProfit = collect();
        for ($i = 1; $i <= 12; $i++) {
            $rev = SalesOrder::query()->whereYear('invoice_date', now()->year)->whereMonth('invoice_date', $i)->sum('paid_amount');
            $exp = Expense::query()->whereYear('expense_date', now()->year)->whereMonth('expense_date', $i)->sum('amount');
            $monthlyProfit->push($rev - $exp);
        }

        return view('frontend.dashboard.company_overview_dashboard', compact('company', 'stats', 'recentTxns', 'branches', 'chartData', 'monthlyProfit', 'dateRange'));
    }

    public function branchPerformanceDashboard(Request $request)
    {
        $company = Company::find(auth()->user()->company_id);
        $dateRange = $request->date_range ?? 'this_month';
        
        // Date Bounds
        $start = now()->startOfMonth(); $end = now()->endOfMonth();
        if ($dateRange === 'this_year') { $start = now()->startOfYear(); $end = now()->endOfYear(); }
        elseif ($dateRange === 'last_7_days') { $start = now()->subDays(7)->startOfDay(); $end = now(); }

        $branches = Branch::query()->get()->map(function($branch) use ($start, $end) {
            $branch->revenue = SalesOrder::query()->where('branch_id', $branch->id)->whereBetween('invoice_date', [$start, $end])->sum('paid_amount');
            $branch->expenses = Expense::query()->where('branch_id', $branch->id)->whereBetween('expense_date', [$start, $end])->sum('amount');
            $branch->profit = $branch->revenue - $branch->expenses;
            $branch->margin = $branch->revenue > 0 ? ($branch->profit / $branch->revenue) * 100 : 0;
            return $branch;
        });

        $stats = [
            'top_revenue_branch' => $branches->sortByDesc('revenue')->first(),
            'top_profit_branch' => $branches->sortByDesc('profit')->first(),
            'total_revenue' => $branches->sum('revenue'),
            'total_expenses' => $branches->sum('expenses'),
            'total_profit' => $branches->sum('profit'),
        ];

        return view('frontend.dashboard.branch_performance_dashboard', compact('company', 'branches', 'stats', 'dateRange'));
    }

    public function salesAnalyticsDashboard(Request $request)
    {
        $company = Company::find(auth()->user()->company_id);
        $dateRange = $request->date_range ?? 'this_month';
        
        $start = now()->startOfMonth(); $end = now()->endOfMonth();
        if ($dateRange === 'this_year') { $start = now()->startOfYear(); $end = now()->endOfYear(); }
        elseif ($dateRange === 'last_7_days') { $start = now()->subDays(7)->startOfDay(); $end = now(); }

        $totalSales = SalesOrder::query()->whereBetween('invoice_date', [$start, $end])->sum('paid_amount');
        $txnCount = SalesOrder::query()->whereBetween('invoice_date', [$start, $end])->count();
        $avgTxn = $txnCount > 0 ? $totalSales / $txnCount : 0;

        $categorySales = Category::query()->get()->map(function($category) use ($start, $end) {
            $category->revenue = SalesOrder::query()
                ->join('sales_order_items', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
                ->join('products', 'sales_order_items.product_id', '=', 'products.id')
                ->where('products.category_id', $category->id)
                ->whereBetween('sales_orders.invoice_date', [$start, $end])
                ->sum('sales_order_items.total_price');
            return $category;
        })->sortByDesc('revenue');

        $topProducts = Product::query()
            ->join('sales_order_items', 'products.id', '=', 'sales_order_items.product_id')
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->whereBetween('sales_orders.invoice_date', [$start, $end])
            ->select('products.product_name', 'products.product_code', DB::raw('SUM(sales_order_items.quantity) as units_sold'), DB::raw('SUM(sales_order_items.total_price) as revenue'))
            ->groupBy('products.id', 'products.product_name', 'products.product_code')
            ->orderByDesc('revenue')
            ->take(5)
            ->get();

        return view('frontend.dashboard.sales_analytics_dashboard', compact('company', 'totalSales', 'txnCount', 'avgTxn', 'categorySales', 'topProducts', 'dateRange'));
    }

    public function inventoryOverviewDashboard(Request $request)
    {
        $company = Company::find(auth()->user()->company_id);
        
        $totalStockValue = ProductStock::query()
            ->join('products', 'product_stocks.product_id', '=', 'products.id')
            ->sum(DB::raw('product_stocks.quantity * products.purchase_price'));

        $lowStockCount = Product::query()
            ->join('product_stocks', 'products.id', '=', 'product_stocks.product_id')
            ->whereRaw('product_stocks.quantity <= products.low_stock_threshold')
            ->count();

        $outOfStockCount = ProductStock::query()->where('quantity', '<=', 0)->count();
        $totalSKUs = Product::query()->count();

        $categoryStock = Category::query()->get()->map(function($category) {
            $category->stock_value = ProductStock::query()
                ->join('products', 'product_stocks.product_id', '=', 'products.id')
                ->where('products.category_id', $category->id)
                ->sum(DB::raw('product_stocks.quantity * products.purchase_price'));
            return $category;
        })->sortByDesc('stock_value');

        $lowStockItems = Product::query()
            ->join('product_stocks', 'products.id', '=', 'product_stocks.product_id')
            ->with('category')
            ->whereRaw('product_stocks.quantity <= products.low_stock_threshold')
            ->select('products.*', 'product_stocks.quantity as current_stock')
            ->take(10)
            ->get();

        return view('frontend.dashboard.inventory_overview_dashboard', compact('company', 'totalStockValue', 'lowStockCount', 'outOfStockCount', 'totalSKUs', 'categoryStock', 'lowStockItems'));
    }

    public function financialSnapshotDashboard(Request $request)
    {
        $company = Company::find(auth()->user()->company_id);
        $dateRange = $request->date_range ?? 'this_month';
        
        $start = now()->startOfMonth(); $end = now()->endOfMonth();
        if ($dateRange === 'this_year') { $start = now()->startOfYear(); $end = now()->endOfYear(); }
        elseif ($dateRange === 'last_7_days') { $start = now()->subDays(7)->startOfDay(); $end = now(); }

        $revenue = SalesOrder::query()->whereBetween('invoice_date', [$start, $end])->sum('paid_amount');
        $expenses = Expense::query()->whereBetween('expense_date', [$start, $end])->sum('amount');
        $netProfit = $revenue - $expenses;

        // Filter to bank/cash asset accounts only
        $bankBalances = Account::query()->where('category', 'assets')->whereNotNull('bank_name')->get();
        $totalBankBalance = $bankBalances->sum('balance');

        return view('frontend.dashboard.financial_snapshot_dashboard', compact('company', 'revenue', 'expenses', 'netProfit', 'bankBalances', 'totalBankBalance', 'dateRange'));
    }

    public function settings()
    {
        $company = Company::find(auth()->user()->company_id);
        return view('frontend.setting.company_setting', compact('company'));
    }

    public function index()
    {
        $userBranchId = Auth::user()->getAssignedBranchId();

        $company      = Company::find(auth()->user()->company_id);
        $branches     = Branch::query()->when($userBranchId, fn($q) => $q->where('id', $userBranchId))->get();
        $bankAccounts = Account::query()->whereIn('type', ['bank', 'cash'])->where('is_active', 1)->get();

        return view('frontend.setting.company_structure', compact('company', 'branches', 'bankAccounts'));
    }

    public function updateCompany(Request $request)
    {
        $company = Company::find(auth()->user()->company_id);
        $data = $request->except(['_token', 'logo']);

        // Handle checkboxes - list all boolean fields from the form
        $booleanFields = [
            'allow_negative_inventory', 'track_expiry', 'enable_barcode', 
            'force_2fa', 'log_overrides', 'maintenance_mode', 'enable_api',
            'round_invoice'
        ];

        foreach ($booleanFields as $field) {
            $data[$field] = $request->has($field) ? 1 : 0;
        }

        if ($request->hasFile('logo')) {
            $cid = auth()->user()->company_id;
            if ($cid && StorageUsageService::isOverStorageLimit($cid)) {
                $limit = StorageUsageService::limitGB($cid);
                return redirect()->back()->with('error',
                    "Storage limit of {$limit} GB reached. Please delete old backups or upgrade your plan to upload new files."
                );
            }
            $file = $request->file('logo');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/company'), $filename);
            
            // Delete old logo if exists
            if ($company && $company->logo && file_exists(public_path($company->logo))) {
                @unlink(public_path($company->logo));
            }
            
            $data['logo'] = 'uploads/company/' . $filename;
        }

        if ($company) {
            $company->update($data);
        } else {
            Company::query()->create($data);
        }
        return redirect()->back()->with('success', 'Company updated successfully');
    }

    public function storeBranch(Request $request)
    {
        $data = $request->except(['_token', '_method', 'branch_id_hidden', 'account_id']);
        
        $company = Company::find(auth()->user()->company_id);
        if ($company) {
            $data['company_id'] = $company->id;
        }
        
        // If an account is selected, sync names to branch record for display
        if ($request->account_id) {
            $account = Account::query()->find($request->account_id);
            if ($account) {
                $data['account_name'] = $account->name . ($account->account_number ? ' - ' . $account->account_number : '');
                $data['bank_name'] = $account->bank_name;
            }
        }

        $branch = Branch::query()->create($data);

        // Update the Chart of Account to point to this branch
        if ($request->account_id) {
            Account::query()->where('id', $request->account_id)->update(['branch_id' => $branch->id]);
        }

        // Every branch needs its own Cash on Hand account for POS/cash tracking —
        // the chosen account_id above is for an existing bank account, not this.
        Account::query()->firstOrCreate(
            ['company_id' => $company->id, 'branch_id' => $branch->id, 'type' => 'cash'],
            [
                'code'     => '1110-' . $branch->id,
                'name'     => $branch->name . ' - Cash on Hand',
                'category' => 'assets',
                'balance'  => 0,
                'is_active' => true,
            ]
        );

        return redirect()->back()->with('success', 'Branch added successfully');
    }

    public function updateBranch(Request $request, $id)
    {
        $branch = Branch::query()->findOrFail($id);
        $data = $request->except(['_token', '_method', 'branch_id_hidden', 'account_id']);

        // Sync Account
        if ($request->account_id) {
            $account = Account::query()->find($request->account_id);
            if ($account) {
                $data['account_name'] = $account->name . ($account->account_number ? ' - ' . $account->account_number : '');
                $data['bank_name'] = $account->bank_name;
                
                // Unlink old accounts for this branch, then link new one
                Account::query()->where('branch_id', $branch->id)->update(['branch_id' => null]);
                $account->update(['branch_id' => $branch->id]);
            }
        } else {
            // If account was cleared, unlink it from the chart
            Account::query()->where('branch_id', $branch->id)->update(['branch_id' => null]);
            $data['account_name'] = null;
            $data['bank_name'] = null;
        }

        $branch->update($data);
        return redirect()->back()->with('success', 'Branch updated successfully');
    }

    public function destroyBranch($id)
    {
        Branch::query()->findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Branch deleted successfully');
    }


    public function auditLogs()
    {
        $logs = AuditLog::query()->with('user')->latest()->paginate(50);
        
        $stats = [
            'total' => AuditLog::query()->count(),
            'success' => AuditLog::query()->where('status', 'success')->count(),
            'warning' => AuditLog::query()->where('status', 'warning')->count(),
            'failed' => AuditLog::query()->where('status', 'failed')->count(),
        ];

        return view('frontend.setting.audit_logs', compact('logs', 'stats'));
    }

    public function profileUser()
    {
        $user = auth()->user();
        
        // Fetch real stats for the user
        $stats = [
            'transactions' => SalesOrder::query()->where('created_by', $user->id)->count(),
            'products'     => Product::query()->where('created_by', $user->id)->count(),
            'customers'    => Customer::query()->count(), // Customers are shared, but we can count total
            'logs_count'   => AuditLog::query()->where('user_id', $user->id)->count(),
        ];

        // Fetch recent user activity from Audit Logs
        $activities = AuditLog::query()->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return view('frontend.setting.profile_user', compact('stats', 'activities'));
    }

    public function branchesView()
    {
        $userBranchId = auth()->user()->getAssignedBranchId();

        $branches = Branch::query()
            ->when($userBranchId, fn(\Illuminate\Database\Eloquent\Builder $q) => $q->where('id', $userBranchId))
            ->paginate(10)
            ->through(function (Branch $branch) {
                $branch->employee_count = Employee::query()->where('branch', $branch->name)->count();
                $branch->total_revenue  = SalesOrder::query()->where('branch_id', $branch->id)->sum('paid_amount');
                $branch->total_stock    = ProductStock::query()->where('branch_id', $branch->id)->sum('quantity');
                return $branch;
            });

        $stats = [
            'total_branches'  => $branches->total(),
            'total_revenue'   => SalesOrder::query()->when($userBranchId, fn(\Illuminate\Database\Eloquent\Builder $q) => $q->where('branch_id', $userBranchId))->sum('paid_amount'),
            'total_employees' => Employee::query()->when($userBranchId, function (\Illuminate\Database\Eloquent\Builder $q) use ($userBranchId) {
                $name = Branch::query()->find($userBranchId)?->name;
                return $q->where('branch', $name);
            })->count(),
            'total_stock' => ProductStock::query()->when($userBranchId, fn(\Illuminate\Database\Eloquent\Builder $q) => $q->where('branch_id', $userBranchId))->sum('quantity'),
        ];

        $company      = Company::find(auth()->user()->company_id);
        $bankAccounts = Account::query()->whereIn('type', ['bank', 'cash'])->where('is_active', 1)->get();
        return view('frontend.branch.branches', compact('branches', 'stats', 'company', 'bankAccounts'));
    }


    public function backupRestore()
    {
        // Restore backups history if db was rewritten
        $snapshotFile = storage_path('app/backup_table_snapshot.json');
        if (file_exists($snapshotFile)) {
            $snapshot = json_decode(file_get_contents($snapshotFile), true);
            if ($snapshot) {
                if (!Schema::hasColumn('backups', 'restore_status')) {
                    Artisan::call('migrate', ['--force' => true]);
                }
                foreach ($snapshot as $b) {
                    Backup::query()->updateOrCreate(['id' => $b['id']], $b);
                }
            }
            @unlink($snapshotFile);
        }

        // Re-apply restore status after a DB restore (file survives DB overwrite)
        $restoreMarkerFile = storage_path('app/last_restore.json');
        if (file_exists($restoreMarkerFile)) {
            $marker = json_decode(file_get_contents($restoreMarkerFile), true);
            if ($marker) {
                // Ensure the restore_status column exists in the (possibly old) restored DB
                if (!Schema::hasColumn('backups', 'restore_status')) {
                    Artisan::call('migrate', ['--force' => true]);
                }
                Backup::query()->where('id', $marker['backup_id'])->update([
                    'restore_status' => 'restored',
                    'restored_at'    => $marker['restored_at'],
                ]);
                // Delete marker so it doesn't apply again
                @unlink($restoreMarkerFile);
            }
        }

        $backups = Backup::query()->latest()->get();
        
        $totalBackups = $backups->count();
        $successBackups = $backups->where('status', 'success')->count();
        $successRate = $totalBackups > 0 ? round(($successBackups / $totalBackups) * 100) . '%' : '100%';

        $stats = [
            'total_backups' => $totalBackups,
            'success_rate' => $successRate,
        ];

        $company = Company::find(auth()->user()->company_id);

        // Approximate DB size
        $dbName = config('database.connections.mysql.database');
        $dbSize = DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size FROM information_schema.TABLES WHERE table_schema = ?", [$dbName])[0]->size ?? 0;
        
        $settings = [
            'backup_retention' => $company->backup_retention ?? 30,
            'auto_backup_enabled' => (bool)($company->auto_backup_enabled ?? true),
            'backup_time' => $company->backup_time ?? '02:00',
            'server_time' => now()->format('Y/m/d H:i:s'),
        ];

        return view('frontend.setting.backup_restore', compact('backups', 'stats', 'dbSize', 'settings', 'company'));
    }

    public function createBackup()
    {
        try {
            $cid = auth()->user()->company_id;
            if ($cid && StorageUsageService::isOverStorageLimit($cid)) {
                $limit = StorageUsageService::limitGB($cid);
                $used  = StorageUsageService::usedGB($cid);
                return response()->json([
                    'success' => false,
                    'message' => "Storage limit reached ({$used} GB / {$limit} GB). Delete old backups or upgrade your plan."
                ]);
            }

            $filename = "backup-" . now()->format('Y-m-d-H-i-s') . ".sql";
            $directory = storage_path('app/backups');
            
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $path = $directory . DIRECTORY_SEPARATOR . $filename;
            
            $dbConfig = config('database.connections.mysql');
            $user = $dbConfig['username'];
            $password = $dbConfig['password'];
            $database = $dbConfig['database'];
            $host = $dbConfig['host'];

            $mysqldumpPath = 'mysqldump';
            if (PHP_OS_FAMILY === 'Windows') {
                $possiblePaths = [
                    'C:\xampp\mysql\bin\mysqldump.exe',
                    'C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqldump.exe',
                    'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe',
                    'C:\Program Files\MySQL\MySQL Server 5.7\bin\mysqldump.exe'
                ];
                foreach ($possiblePaths as $p) {
                    if (file_exists($p)) {
                        $mysqldumpPath = $p;
                        break;
                    }
                }
            }

            $command = sprintf(
                '%s --user=%s %s --host=%s %s > %s',
                escapeshellarg($mysqldumpPath),
                escapeshellarg($user),
                $password ? "--password=" . escapeshellarg($password) : "",
                escapeshellarg($host),
                escapeshellarg($database),
                escapeshellarg($path)
            );

            exec($command, $output, $returnVar);

            if ($returnVar !== 0 || !file_exists($path)) {
                throw new \Exception("Backup failed with error code $returnVar. Please check database permissions.");
            }

            $company = auth()->user()->company;
            Backup::query()->create([
                'company_id' => $company ? $company->id : null,
                'filename' => $filename,
                'path' => 'backups/' . $filename,
                'size' => filesize($path),
                'type' => 'manual',
                'status' => 'success'
            ]);

            return response()->json(['success' => true, 'message' => 'Backup created successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function downloadBackup($id)
    {
        $backup = Backup::query()->find($id);
        if (!$backup) {
            return redirect()->back()->with('error', 'Backup record not found.');
        }
        $path = storage_path('app/' . $backup->path);
        
        if (!file_exists($path)) {
            return redirect()->back()->with('error', 'Backup file not found on disk.');
        }

        return response()->download($path);
    }

    public function deleteBackup($id)
    {
        $backup = Backup::query()->find($id);
        if (!$backup) {
            return response()->json(['success' => false, 'message' => 'Backup record not found.']);
        }
        $path = storage_path('app/' . $backup->path);

        if (file_exists($path)) {
            @unlink($path);
        }

        $backup->delete();
        return response()->json(['success' => true, 'message' => 'Backup deleted successfully']);
    }

    public function restoreBackup($id)
    {
        try {
            $backup = Backup::query()->find($id);

            if (!$backup) {
                return response()->json(['success' => false, 'message' => 'Backup record not found. It may have been deleted.']);
            }

            $path = storage_path('app/' . $backup->path);

            if (!file_exists($path)) {
                throw new \Exception("Backup file not found on disk.");
            }

            // Write a file marker BEFORE overwriting DB — files survive DB restores
            $restoreMarkerFile = storage_path('app/last_restore.json');
            file_put_contents($restoreMarkerFile, json_encode([
                'backup_id'   => $id,
                'restored_at' => now()->toDateTimeString(),
            ]));
            
            // Snapshot the backups table so we don't lose the history
            $allBackups = Backup::all()->toArray();
            file_put_contents(storage_path('app/backup_table_snapshot.json'), json_encode($allBackups));

            $dbConfig = config('database.connections.mysql');
            $user = $dbConfig['username'];
            $password = $dbConfig['password'];
            $database = $dbConfig['database'];
            $host = $dbConfig['host'];

            // Find mysql binary
            $mysqlPath = 'mysql';
            if (PHP_OS_FAMILY === 'Windows') {
                $possiblePaths = [
                    'C:\xampp\mysql\bin\mysql.exe',
                    'C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe',
                    'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe',
                    'C:\Program Files\MySQL\MySQL Server 5.7\bin\mysql.exe'
                ];
                foreach ($possiblePaths as $p) {
                    if (file_exists($p)) {
                        $mysqlPath = $p;
                        break;
                    }
                }
            }

            $command = sprintf(
                '%s --user=%s %s --host=%s %s < %s',
                escapeshellarg($mysqlPath),
                escapeshellarg($user),
                $password ? "--password=" . escapeshellarg($password) : "",
                escapeshellarg($host),
                escapeshellarg($database),
                escapeshellarg($path)
            );

            set_time_limit(300);
            exec($command . ' 2>&1', $output, $returnVar);

            if ($returnVar !== 0) {
                // Delete marker — restore failed, DB wasn't overwritten
                @unlink($restoreMarkerFile);
                @unlink(storage_path('app/backup_table_snapshot.json'));
                $errorMsg = implode("\n", $output);
                $backup->update(['restore_status' => 'failed']);
                throw new \Exception("Restore failed (Code $returnVar): " . $errorMsg);
            }

            return response()->json(['success' => true, 'message' => 'System successfully rolled back to snapshot.']);
        } catch (\Exception $e) {
            Log::error('Restore error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Restoration failed: ' . $e->getMessage()]);
        }
    }

    public function backupToGmail(Request $request)
    {
        try {
            $email = $request->email ?? config('mail.from.address');
            
            // Generate temporary backup for email
            $filename = "gmail-backup-" . now()->format('Y-m-d-H-i-s') . ".sql";
            $directory = storage_path('app/backups');
            if (!file_exists($directory)) mkdir($directory, 0755, true);
            $path = $directory . DIRECTORY_SEPARATOR . $filename;
            
            $dbConfig = config('database.connections.mysql');
            $mysqldumpPath = 'mysqldump';
            if (PHP_OS_FAMILY === 'Windows') {
                $possiblePaths = [
                    'C:\xampp\mysql\bin\mysqldump.exe',
                    'C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqldump.exe',
                    'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe',
                    'C:\Program Files\MySQL\MySQL Server 5.7\bin\mysqldump.exe'
                ];
                foreach ($possiblePaths as $p) {
                    if (file_exists($p)) {
                        $mysqldumpPath = $p;
                        break;
                    }
                }
            }

            $command = sprintf(
                '%s --user=%s %s --host=%s %s > %s',
                escapeshellarg($mysqldumpPath),
                escapeshellarg($dbConfig['username']),
                $dbConfig['password'] ? "--password=" . escapeshellarg($dbConfig['password']) : "",
                escapeshellarg($dbConfig['host']),
                escapeshellarg($dbConfig['database']),
                escapeshellarg($path)
            );

            exec($command, $output, $returnVar);

            if ($returnVar !== 0 || !file_exists($path)) {
                throw new \Exception("Failed to generate backup for email.");
            }

            // Send Mail
            Mail::to($email)->send(new BackupMail($path, $filename));

            // Record in DB
            Backup::query()->create([
                'filename' => $filename,
                'path' => 'backups/' . $filename,
                'size' => filesize($path),
                'type' => 'gmail',
                'status' => 'success'
            ]);

            return response()->json(['success' => true, 'message' => 'Backup sent to ' . $email]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function branchTransfer()
    {
        $userBranchId = Auth::user()->getAssignedBranchId();
        $company      = Company::find(auth()->user()->company_id);
        $products     = Product::query()->with('stocks')->get();
        $branches     = Branch::query()->when($userBranchId, fn($q) => $q->where('id', $userBranchId))->get();

        $transfers = \App\Models\BranchTransfer::query()
            ->with(['fromBranch', 'toBranch', 'product', 'requester', 'approver'])
            ->when($userBranchId, fn($q) => $q->where(fn($sq) => $sq->where('from_branch_id', $userBranchId)->orWhere('to_branch_id', $userBranchId)))
            ->latest()
            ->get();

        $stats = [
            'pending'         => $transfers->where('status', 'pending')->count(),
            'approved_today'  => $transfers->where('status', 'completed')->where('approved_at', '>=', now()->startOfDay())->count(),
            'completed_month' => $transfers->where('status', 'completed')->where('completed_at', '>=', now()->startOfMonth())->sum('quantity'),
            'total_value'     => $transfers->where('status', 'completed')->where('completed_at', '>=', now()->startOfMonth())->sum(fn($t) => $t->quantity * ($t->product->purchase_price ?? 0)),
        ];

        return view('frontend.branch.branch_transfer', compact('company', 'branches', 'products', 'stats', 'transfers'));
    }

    public function branchTransferStore(Request $request)
    {
        $id       = $request->id;
        $transfer = null;

        if ($id) {
            $transfer = \App\Models\BranchTransfer::query()->findOrFail($id);
            if ($transfer->status !== 'pending') {
                return response()->json(['success' => false, 'message' => 'Only pending transfers can be updated.'], 422);
            }
        }

        $request->validate([
            'from_branch_id' => 'required|exists:branches,id',
            'to_branch_id'   => 'required|exists:branches,id|different:from_branch_id',
            'product_id'     => 'required|exists:products,id',
            'quantity'       => 'required|integer|min:1',
        ]);

        $userBranchId = auth()->user()->getAssignedBranchId();
        if ($userBranchId && $userBranchId != $request->from_branch_id && $userBranchId != $request->to_branch_id) {
            return response()->json(['success' => false, 'message' => 'You can only initiate transfers involving your assigned branch.'], 403);
        }

        $stock = ProductStock::query()->where('product_id', $request->product_id)->where('branch_id', $request->from_branch_id)->first();
        if (!$stock || $stock->quantity < $request->quantity) {
            return response()->json(['success' => false, 'message' => 'Insufficient stock in source branch.'], 422);
        }

        if ($id) {
            $transfer->update([
                'from_branch_id' => $request->from_branch_id,
                'to_branch_id'   => $request->to_branch_id,
                'product_id'     => $request->product_id,
                'quantity'       => $request->quantity,
                'remarks'        => $request->remarks,
            ]);
            return response()->json(['success' => true, 'message' => 'Transfer updated successfully.']);
        }

        $last       = \App\Models\BranchTransfer::query()->latest()->first();
        $nextId     = $last ? $last->id + 1 : 1;
        $transferNo = 'BTF-' . now()->format('Ymd') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        \App\Models\BranchTransfer::query()->create([
            'transfer_no'    => $transferNo,
            'from_branch_id' => $request->from_branch_id,
            'to_branch_id'   => $request->to_branch_id,
            'product_id'     => $request->product_id,
            'quantity'       => $request->quantity,
            'remarks'        => $request->remarks,
            'status'         => 'pending',
            'requested_by'   => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Transfer request submitted successfully.']);
    }

    public function branchTransferAction(Request $request, $id)
    {
        $transfer = \App\Models\BranchTransfer::query()->findOrFail($id);
        $action   = $request->action;
        $userRole = strtolower(trim(auth()->user()->role ?? ''));
        $isAdmin  = in_array($userRole, ['admin', 'super admin', 'manager', 'branch manager']);

        if ($action === 'approve') {
            if (!$isAdmin && auth()->id() === $transfer->requested_by) {
                return response()->json(['success' => false, 'message' => 'You cannot approve a transfer you requested.'], 403);
            }

            $fromStock = ProductStock::query()->where('product_id', $transfer->product_id)->where('branch_id', $transfer->from_branch_id)->first();
            if (!$fromStock || $fromStock->quantity < $transfer->quantity) {
                return response()->json(['success' => false, 'message' => 'Insufficient stock to complete transfer.'], 422);
            }

            DB::transaction(function () use ($transfer, $fromStock) {
                $fromStock->decrement('quantity', $transfer->quantity);

                $toStock = ProductStock::query()->firstOrCreate(
                    ['product_id' => $transfer->product_id, 'branch_id' => $transfer->to_branch_id],
                    ['quantity' => 0]
                );
                $toStock->increment('quantity', $transfer->quantity);

                $transfer->update([
                    'status'       => 'completed',
                    'approved_by'  => auth()->id(),
                    'approved_at'  => now(),
                    'completed_at' => now(),
                ]);
            });

            return response()->json(['success' => true, 'message' => 'Transfer approved and stock moved.']);
        }

        if ($action === 'reject') {
            $transfer->update(['status' => 'rejected']);
            return response()->json(['success' => true, 'message' => 'Transfer rejected.']);
        }

        return response()->json(['success' => false, 'message' => 'Invalid action.'], 400);
    }

    public function branchTransferDestroy($id)
    {
        try {
            $transfer = \App\Models\BranchTransfer::query()->findOrFail($id);
            if ($transfer->status !== 'pending') {
                return response()->json(['success' => false, 'message' => 'Cannot delete a processed transfer.'], 403);
            }
            $transfer->delete();
            return response()->json(['success' => true, 'message' => 'Transfer deleted.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getBranchStock(Request $request)
    {
        $stock = ProductStock::query()
            ->where('product_id', $request->product_id)
            ->where('branch_id', $request->branch_id)
            ->first();

        return response()->json(['success' => true, 'available_stock' => $stock ? $stock->quantity : 0]);
    }

    public function updateBackupSettings(Request $request)
    {
        try {
            $company = Company::find(auth()->user()->company_id);
            if (!$company) throw new \Exception("Company configuration not found.");

            Log::info('Backup Settings Update Request:', $request->all());

            $company->update([
                'auto_backup_enabled' => $request->automated,
                'backup_retention' => $request->retention ?? 30,
                'backup_time' => $request->backup_time ?? '02:00',
            ]);


            return response()->json(['success' => true, 'message' => 'Security protocols synchronized successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function triggerScheduledBackup(Request $request)
    {
        try {
            $cid     = auth()->user()->company_id;
            $company = Company::withoutGlobalScopes()->find($cid);

            if (!$company) {
                return response()->json(['success' => false, 'message' => 'Company not found.']);
            }

            // Respect the toggle: if disabled in DB, skip (JS also guards this, but double-check)
            // Use request-sent state as authoritative so a fresh DB default never silently blocks
            $automatedEnabled = $request->input('automated', $company->auto_backup_enabled);
            if (!$automatedEnabled) {
                return response()->json(['success' => false, 'message' => 'Automated protocol is disabled.']);
            }

            $currentTime   = now()->format('H:i');
            $scheduledTime = $company->backup_time ?? '02:00';

            // Not yet time
            if ($currentTime < $scheduledTime) {
                return response()->json(['success' => false, 'message' => 'Not yet time for scheduled archival.']);
            }

            // Already ran today at or after the scheduled time
            $scheduledDateTime = now()->toDateString() . ' ' . $scheduledTime . ':00';
            $alreadyRun = Backup::withoutGlobalScopes()
                ->where('company_id', $cid)
                ->where('type', 'auto')
                ->where('created_at', '>=', $scheduledDateTime)
                ->where('status', 'success')
                ->exists();

            if ($alreadyRun) {
                return response()->json(['success' => false, 'message' => 'Automated snapshot already completed today.']);
            }

            // Execute — pass company_id so the command backs up the right tenant
            Artisan::call('app:system-backup', ['--type' => 'auto', '--company' => $cid]);
            $output = Artisan::output();

            return response()->json(['success' => true, 'message' => 'Scheduled archival protocol executed.', 'output' => $output]);

        } catch (\Exception $e) {
            Log::error("Manual Schedule Trigger Failed: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
