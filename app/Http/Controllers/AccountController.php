<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Branch;
use App\Models\Account;
use App\Models\Company;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    private function currencySymbol(): string
    {
        $company = Company::find(auth()->user()->company_id);
        $map = ['SAR' => '﷼', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AED' => 'د.إ', 'KWD' => 'د.ك'];
        return $map[$company->currency ?? ''] ?? ($company->currency ?? '$');
    }

    public function index(Request $request)
    {
        $cid = auth()->user()->company_id;

        if ($request->has('sync')) {
            $this->syncBalances();
            return response()->json(['success' => true]);
        }

        $this->syncBalances();

        $allAccounts    = Account::query()->where('company_id', $cid)->orderBy('code')->get();
        $parentAccounts = $allAccounts->whereNull('parent_id');
        $branches       = Branch::query()->get();

        $totalBalance          = $allAccounts->where('type', '!=', 'parent')->sum('balance');
        $activeAccountsCount   = $allAccounts->where('is_active', 1)->count();
        $totalAccountCount     = $allAccounts->count();
        $inactiveAccountsCount = $allAccounts->where('is_active', 0)->count();

        $monthlyTransactions = JournalItem::query()
            ->where('company_id', $cid)
            ->whereHas('entry', function ($q) {
                $q->where('status', 'posted')
                  ->whereMonth('date', now()->month)
                  ->whereYear('date', now()->year);
            })->count();

        $postedEntries = JournalItem::query()
            ->where('company_id', $cid)
            ->whereHas('entry', fn ($q) => $q->where('status', 'posted'));

        $assetItems      = (clone $postedEntries)->whereHas('account', fn ($q) => $q->where('category', 'assets'));
        $totalAssets     = $assetItems->sum('debit') - $assetItems->sum('credit');

        $liabilityItems  = (clone $postedEntries)->whereHas('account', fn ($q) => $q->where('category', 'liabilities'));
        $totalLiabilities = $liabilityItems->sum('credit') - $liabilityItems->sum('debit');

        $revenueItems    = (clone $postedEntries)->whereHas('account', fn ($q) => $q->where('category', 'revenue'));
        $totalRevenue    = $revenueItems->sum('credit') - $revenueItems->sum('debit');

        $expenseItems    = (clone $postedEntries)->whereHas('account', fn ($q) => $q->where('category', 'expenses'));
        $totalExpenses   = $expenseItems->sum('debit') - $expenseItems->sum('credit');

        $equityItems     = (clone $postedEntries)->whereHas('account', fn ($q) => $q->where('category', 'equity'));
        $baseEquity      = $equityItems->sum('credit') - $equityItems->sum('debit');
        $totalEquity     = $baseEquity + ($totalRevenue - $totalExpenses);

        return view('frontend.account.chart_accounts', compact(
            'allAccounts', 'parentAccounts', 'branches',
            'totalBalance', 'activeAccountsCount', 'totalAccountCount',
            'inactiveAccountsCount', 'monthlyTransactions',
            'totalAssets', 'totalLiabilities', 'totalEquity', 'totalRevenue', 'totalExpenses'
        ));
    }

    public function cashManagement()
    {
        $cid = auth()->user()->company_id;

        $this->syncBalances();

        $branches    = Branch::with(['accounts' => fn ($q) => $q->orderBy('code')])->get();
        $allAccounts = Account::query()->where('company_id', $cid)->get();

        $totalCash      = $allAccounts->whereIn('type', ['bank', 'cash'])->sum('balance');
        // Receivables/Payables are tracked per customer/supplier (kept in sync across
        // opening balances, sales/purchases, payments and returns) — not on the ledger
        // account's balance, which only reflects posted journal entries.
        $receivables    = \App\Models\Customer::sum('amount_balance');
        $payables       = \App\Models\Supplier::sum('amount_balance');
        $activeBranches = Branch::query()->where('is_active', 1)->count();
        $companyCurrency = $this->currencySymbol();

        return view('frontend.account.cash_management', compact(
            'branches', 'totalCash', 'receivables', 'payables', 'activeBranches', 'companyCurrency'
        ));
    }

    public function syncBalances()
    {
        $cid      = auth()->user()->company_id;
        $accounts = Account::query()->where('company_id', $cid)->get();

        foreach ($accounts as $account) {
            $debitSum  = JournalItem::query()
                ->where('account_id', $account->id)
                ->where('company_id', $cid)
                ->whereHas('entry', fn ($q) => $q->where('status', 'posted'))
                ->sum('debit');

            $creditSum = JournalItem::query()
                ->where('account_id', $account->id)
                ->where('company_id', $cid)
                ->whereHas('entry', fn ($q) => $q->where('status', 'posted'))
                ->sum('credit');

            $account->balance = in_array($account->category, ['assets', 'expenses'])
                ? ($debitSum - $creditSum)
                : ($creditSum - $debitSum);

            $account->save();
        }
    }

    /**
     * Creates a journal entry to record an initial/opening balance.
     * The JournalItem observer (modifyAccountBalance) will update the account balance —
     * do NOT also write the balance directly to the account before calling this.
     */
    private function handleInitialBalance(Account $account, float $amount, bool $isAdjustment = false): void
    {
        $cid = auth()->user()->company_id ?? Auth::user()->company_id;

        /** @var Account|null $equityAccount */
        $equityAccount = Account::query()->where('company_id', $cid)->where('code', '3300')->first()
                      ?? Account::query()->where('company_id', $cid)->where('code', '3200')->first()
                      ?? Account::query()->where('company_id', $cid)->where('category', 'equity')->whereNotIn('type', ['parent'])->first();

        if (!$equityAccount) {
            throw new \RuntimeException('Missing equity account (code 3300). Seed the Chart of Accounts first.');
        }

        $ref = ($isAdjustment ? 'ADJ-OB' : 'OPEN') . '-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        /** @var JournalEntry $entry */
        $entry = JournalEntry::query()->create([
            'entry_number' => $ref,
            'date'         => now(),
            'reference'    => $ref,
            'description'  => ($isAdjustment ? 'Opening Balance Adjustment: ' : 'Initial Balance: ') . $account->name,
            'status'       => 'posted',
            'total_amount' => abs($amount),
            'company_id'   => $cid,
            'created_by'   => Auth::id() ?? 1,
        ]);

        $isDebitIncrease = in_array($account->category, ['assets', 'expenses']);
        $absAmount       = abs($amount);

        if ($amount > 0) {
            JournalItem::query()->create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $account->id,
                'company_id'       => $cid,
                'debit'            => $isDebitIncrease ? $absAmount : 0,
                'credit'           => $isDebitIncrease ? 0 : $absAmount,
                'description'      => 'Opening balance',
            ]);
            JournalItem::query()->create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $equityAccount->id,
                'company_id'       => $cid,
                'debit'            => $isDebitIncrease ? 0 : $absAmount,
                'credit'           => $isDebitIncrease ? $absAmount : 0,
                'description'      => 'Opening balance offset',
            ]);
        } else {
            JournalItem::query()->create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $account->id,
                'company_id'       => $cid,
                'debit'            => $isDebitIncrease ? 0 : $absAmount,
                'credit'           => $isDebitIncrease ? $absAmount : 0,
                'description'      => 'Opening balance adjustment',
            ]);
            JournalItem::query()->create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $equityAccount->id,
                'company_id'       => $cid,
                'debit'            => $isDebitIncrease ? $absAmount : 0,
                'credit'           => $isDebitIncrease ? 0 : $absAmount,
                'description'      => 'Opening balance adjustment offset',
            ]);
        }
        // The JournalItem observer (modifyAccountBalance) auto-updates account.balance on create.
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $request->validate([
            'code'     => 'required|unique:chart_of_accounts,code,NULL,id,company_id,' . $companyId,
            'name'     => 'required',
            'category' => 'required',
            'type'     => 'required',
        ]);

        DB::transaction(function () use ($request, $companyId) {
            $data = $request->only([
                'code', 'name', 'category', 'type', 'parent_id', 'balance', 'description',
                'bank_name', 'branch_name', 'account_number', 'iban', 'swift_code', 'currency', 'branch_id'
            ]);

            if (!empty($data['parent_id']) && !Account::query()->where('id', $data['parent_id'])->exists()) {
                $parent = Account::query()->where('code', $data['parent_id'])->first();
                $data['parent_id'] = $parent ? $parent->id : null;
            }

            // Capture desired initial balance, then create account at zero.
            // handleInitialBalance will set the balance via the observer — prevents double-counting.
            $initialBalance      = (float) ($data['balance'] ?? 0);
            $data['balance']     = 0;
            $data['company_id']  = $companyId;
            $data['is_active']   = $request->has('is_active') || $request->is_active == 1;

            /** @var Account $account */
            $account = Account::query()->create($data);

            if ($initialBalance != 0) {
                $this->handleInitialBalance($account, $initialBalance);
            }
        });

        return redirect()->back()->with('success', 'Account created successfully');
    }

    public function update(Request $request, $id)
    {
        /** @var Account $account */
        $account   = Account::query()->findOrFail($id);
        $companyId = auth()->user()->company_id;

        $request->validate([
            'code'     => 'required|unique:chart_of_accounts,code,' . $id . ',id,company_id,' . $companyId,
            'name'     => 'required',
            'category' => 'required',
            'type'     => 'required',
        ]);

        DB::transaction(function () use ($request, $account) {
            $data = $request->only([
                'code', 'name', 'category', 'type', 'parent_id', 'description',
                'bank_name', 'branch_name', 'account_number', 'iban', 'swift_code', 'currency', 'branch_id'
            ]);

            if (!empty($request->parent_id) && !Account::query()->where('id', $request->parent_id)->exists()) {
                $parent = Account::query()->where('code', $request->parent_id)->first();
                $data['parent_id'] = $parent ? $parent->id : null;
            }

            $data['is_active'] = $request->has('is_active') || $request->is_active == 1;

            $oldBalance  = $account->balance;
            $newBalance  = (float) ($request->balance ?? $oldBalance);

            // Update everything except balance — observer will handle balance via journal entry
            $account->update($data);

            // If balance was manually adjusted, create an adjustment journal entry for the delta.
            // Do NOT write newBalance directly — the observer will increment from oldBalance.
            if (abs($newBalance - $oldBalance) > 0.001) {
                $this->handleInitialBalance($account, $newBalance - $oldBalance, true);
            }
        });

        return redirect()->back()->with('success', 'Account updated successfully');
    }

    public function destroy(Request $request, $id)
    {
        $account = Account::query()->findOrFail($id);

        if ($account->children()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete account with sub-accounts');
        }

        if ($account->journalItems()->exists()) {
            if ($request->has('force') && $request->force == 'true') {
                DB::transaction(function () use ($account) {
                    $entryIds = $account->journalItems()->pluck('journal_entry_id')->unique();
                    foreach (JournalEntry::query()->whereIn('id', $entryIds)->get() as $entry) {
                        foreach ($entry->items as $item) {
                            $item->delete(); // observer reverses balances
                        }
                        $entry->delete();
                    }
                    $account->delete();
                });
                return redirect()->back()->with('success', 'Account and its associated journal entries deleted successfully.');
            }
            session()->flash('last_delete_id', $id);
            return redirect()->back()->with('error', 'usage_detected');
        }

        $account->delete();
        return redirect()->back()->with('success', 'Account deleted successfully');
    }

    public function toggleStatus($id)
    {
        $account = Account::query()->findOrFail($id);
        $account->is_active = !$account->is_active;
        $account->save();

        $status = $account->is_active ? 'unlocked' : 'locked';
        return redirect()->back()->with('success', "Account \"{$account->name}\" has been {$status}.");
    }

    public function ledger(Request $request)
    {
        $cid       = auth()->user()->company_id;
        $accountId = $request->account_id;
        $accounts  = Account::query()->where('company_id', $cid)->orderBy('code')->get();

        $selectedAccount = null;
        $items           = collect();

        if ($accountId) {
            $selectedAccount = Account::query()->where('company_id', $cid)->findOrFail($accountId);
            $items = JournalItem::query()->with('entry')
                ->where('journal_items.account_id', $accountId)
                ->where('journal_items.company_id', $cid)
                ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
                ->orderBy('journal_entries.date', 'asc')
                ->select('journal_items.*')
                ->get();
        }

        return view('frontend.account.ledger', compact('accounts', 'selectedAccount', 'items'));
    }

    public function cashInHand(Request $request)
    {
        $cid = auth()->user()->company_id;
        $userBranchId = Auth::user()->getAssignedBranchId();
        /** @var Company|null $company */
        $company = Company::find($cid);
        $companyCurrency = $company->currency ?? '$';

        $cashAccount = Account::query()
            ->where('company_id', $cid)
            ->where('type', 'cash')
            ->when($userBranchId, fn($q) => $q->where('branch_id', $userBranchId))
            ->orderByRaw("CASE WHEN LOWER(name) LIKE '%cash on hand%' OR LOWER(name) LIKE '%cash in hand%' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();

        $cashBalance = (float) ($cashAccount->balance ?? 0);
        $transactions = collect();

        if ($cashAccount) {
            $transactions = JournalItem::query()
                ->where('account_id', $cashAccount->id)
                ->with('entry')
                ->get()
                ->filter(fn($item) => $item->entry !== null)
                ->map(function ($item) {
                    [$type, $name] = $this->classifyCashJournalEntry($item->entry);
                    return [
                        'type'      => $type,
                        'name'      => $name,
                        'date'      => $item->entry->date ? $item->entry->date->format('d/m/Y') : '-',
                        'sort_date' => $item->entry->date ? $item->entry->date->timestamp : 0,
                        'sort_id'   => $item->id,
                        'amount'    => (float) ($item->debit > 0 ? $item->debit : $item->credit),
                        'direction' => $item->debit > 0 ? 'in' : 'out',
                    ];
                })
                ->sortByDesc(fn($t) => [$t['sort_date'], $t['sort_id']])
                ->values();
        }

        return view('frontend.account.cash_on_hand', compact('cashAccount', 'cashBalance', 'transactions', 'companyCurrency'));
    }

    /**
     * There is no polymorphic link from a JournalEntry back to the business
     * record that created it, so the entry_number/reference prefix (set
     * consistently by each module) is the only reliable way to label a cash
     * ledger row with a human-readable type + party name.
     */
    private function classifyCashJournalEntry(JournalEntry $entry): array
    {
        $ref = $entry->entry_number ?? '';
        $desc = $entry->description ?? '';

        $afterPrefix = function (string $prefix) use ($desc) {
            $name = trim(str_ireplace($prefix, '', $desc));
            return $name !== '' ? preg_replace('/[.:].*$/', '', $name) : null;
        };

        return match (true) {
            str_starts_with($ref, 'JE-SALE-')   => [$this->cashSaleType($ref), $afterPrefix('Sale to ') ?? 'Cash Sale'],
            str_starts_with($ref, 'EN-PAY-')    => ['Purchase', $afterPrefix('Payment to ') ?? '-'],
            str_starts_with($ref, 'EN-PB-')     => ['Purchase', $afterPrefix('Purchase Bill from ') ?? '-'],
            str_starts_with($ref, 'EN-PR-')     => ['Purchase Return', $desc ?: '-'],
            str_starts_with($ref, 'JE-PAY-')    => ['Payment-In', $afterPrefix('Payment received from ') ?? '-'],
            str_starts_with($ref, 'VOUCH-')     => ['Payment-Out', $afterPrefix('Payment to ') ?? '-'],
            str_starts_with($ref, 'EXP-') || str_contains($desc, 'Expense:') => ['Expense', $afterPrefix('Expense: ') ?? '-'],
            str_starts_with($ref, 'CAP-')       => ['Capital Deposit', $afterPrefix('Capital Deposit') ?? $afterPrefix('Initial Capital Deposit') ?? '-'],
            str_starts_with($ref, 'JE-LN-')     => ['Loan', $afterPrefix('Loan disbursement: ') ?? '-'],
            str_starts_with($ref, 'JE-PP-')     => ['Payroll', 'Payroll Payment'],
            str_starts_with($ref, 'JE-PR-')     => ['Payroll', 'Payroll Accrual'],
            str_starts_with($ref, 'DEP-')       => ['Deposit', $afterPrefix('Deposit from ') ?? '-'],
            str_starts_with($ref, 'WTH-')       => ['Withdrawal', $afterPrefix('Withdrawal to ') ?? '-'],
            str_starts_with($ref, 'TRF-')       => ['Transfer', 'Internal Transfer'],
            str_starts_with($ref, 'ADJ-')       => ['Adjustment', $afterPrefix('Balance Adjustment : ') ?? 'Balance Adjustment'],
            default                             => ['Journal Entry', $desc ?: ($entry->reference ?: '-')],
        };
    }

    // A sale's entry_number is "JE-SALE-{order_id}-{suffix}" — trace it back to the
    // order so a cart of only service items shows as "Service" instead of "Sale".
    private function cashSaleType(string $ref): string
    {
        if (!preg_match('/^JE-SALE-(\d+)-/', $ref, $m)) {
            return 'Sale';
        }

        $order = SalesOrder::with('items.product')->find($m[1]);
        if (!$order || $order->items->isEmpty()) {
            return 'Sale';
        }

        $allServices = $order->items->every(fn ($item) => $item->product?->product_type === 'service');

        return $allServices ? 'Service' : 'Sale';
    }

    public function trialBalance()
    {
        $cid      = auth()->user()->company_id;
        $accounts = Account::query()
            ->where('company_id', $cid)
            ->where('balance', '!=', 0)
            ->orderBy('code')
            ->get();

        $totalDebit  = 0;
        $totalCredit = 0;

        foreach ($accounts as $acc) {
            if (in_array($acc->category, ['assets', 'expenses'])) {
                if ($acc->balance >= 0) $totalDebit  += $acc->balance;
                else                    $totalCredit += abs($acc->balance);
            } else {
                if ($acc->balance >= 0) $totalCredit += $acc->balance;
                else                    $totalDebit  += abs($acc->balance);
            }
        }

        return view('frontend.account.trial_balance', compact('accounts', 'totalDebit', 'totalCredit'));
    }

    public function bank()
    {
        $cid      = auth()->user()->company_id;
        $branches = Branch::query()->get();
        $accounts = Account::query()->where('company_id', $cid)->get();

        $branchData = [];
        foreach ($branches as $branch) {
            $branchAccounts = $accounts->where('branch_id', $branch->id);
            $branchData[$branch->id] = [
                'branch'   => $branch,
                'accounts' => $branchAccounts,
                'totals'   => [
                    'cash_bank'   => $branchAccounts->whereIn('type', ['cash', 'bank'])->sum('balance'),
                    'receivables' => $branchAccounts->filter(fn (Account $a) => $a->category == 'assets' && (str_contains(strtolower($a->name), 'receivable') || $a->type == 'receivable'))->sum('balance'),
                    'payables'    => $branchAccounts->filter(fn (Account $a) => $a->category == 'liabilities' && (str_contains(strtolower($a->name), 'payable') || $a->type == 'payable'))->sum('balance'),
                    'expenses'    => $branchAccounts->where('category', 'expenses')->sum('balance'),
                    'purchases'   => $branchAccounts->filter(fn (Account $a) => $a->category == 'expenses' && str_contains(strtolower($a->name), 'purchase'))->sum('balance'),
                    'sales'       => $branchAccounts->where('category', 'revenue')->sum('balance'),
                ],
            ];
        }

        $totalCash         = $accounts->filter(fn (Account $a) => in_array($a->type, ['cash', 'bank']))->sum('balance');
        $totalReceivables  = $accounts->filter(fn (Account $a) => $a->category == 'assets' && (str_contains(strtolower($a->name), 'receivable') || $a->type == 'receivable'))->sum('balance');
        $totalPayables     = $accounts->filter(fn (Account $a) => $a->category == 'liabilities' && (str_contains(strtolower($a->name), 'payable') || $a->type == 'payable'))->sum('balance');
        $activeBranches    = $branches->where('is_active', 1)->count();

        return view('frontend.branch.cash_management', compact(
            'branches', 'branchData', 'totalCash', 'totalReceivables', 'totalPayables', 'activeBranches'
        ));
    }

    public function export()
    {
        $cid      = auth()->user()->company_id;
        $fileName = 'chart_of_accounts_' . date('Y-m-d_H_i_s') . '.csv';
        $accounts = Account::query()->where('company_id', $cid)->orderBy('code')->get();

        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $columns = ['Code', 'Name', 'Category', 'Type', 'Bank Name', 'Branch', 'Account Number', 'IBAN', 'Currency', 'Balance'];

        $callback = function () use ($accounts, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($accounts as $account) {
                fputcsv($file, [
                    $account->code,
                    $account->name,
                    $account->category,
                    $account->type,
                    $account->bank_name     ?? '',
                    $account->branch_name   ?? '',
                    $account->account_number ?? '',
                    $account->iban          ?? '',
                    $account->currency      ?? '',
                    $account->balance,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadTemplate()
    {
        $fileName = 'accounts_import_template.csv';
        $headers  = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $columns  = ['Code', 'Name', 'Category', 'Type', 'Bank Name', 'Branch', 'Account Number', 'IBAN', 'Currency', 'Balance'];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fputcsv($file, ['1021', 'SABB Bank', 'assets', 'bank', 'SABB', 'Riyadh Main', '123456789', 'SA123...', 'SAR', '1000.00']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:csv,txt|max:2048']);

        if ($request->hasFile('file')) {
            $file      = $request->file('file');
            $handle    = fopen($file->getPathname(), 'r');
            $companyId = auth()->user()->company_id;

            fgetcsv($handle, 1000, ','); // skip header row

            DB::beginTransaction();
            try {
                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    $code = trim($data[0] ?? '');
                    $name = trim($data[1] ?? '');
                    if (empty($code) || empty($name)) continue;

                    Account::query()->updateOrCreate(
                        ['code' => $code, 'company_id' => $companyId],
                        [
                            'name'           => $name,
                            'category'       => strtolower(trim($data[2] ?? 'assets')),
                            'type'           => strtolower(trim($data[3] ?? 'bank')),
                            'balance'        => floatval($data[9] ?? 0),
                            'is_active'      => true,
                            'bank_name'      => $data[4] ?? '',
                            'branch_name'    => $data[5] ?? '',
                            'account_number' => $data[6] ?? '',
                            'iban'           => $data[7] ?? '',
                            'currency'       => $data[8] ?? '',
                        ]
                    );
                }
                DB::commit();
                fclose($handle);
                return redirect()->back()->with('success', 'Accounts imported successfully!');
            } catch (\Exception $e) {
                DB::rollBack();
                fclose($handle);
                return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('error', 'Please upload a valid CSV file.');
    }

    private function seedDefaultAccounts()
    {
        $cid = auth()->user()->company_id;

        $defaults = [
            ['code' => '1',    'name' => 'Assets',                   'category' => 'assets',      'type' => 'parent'],
            ['code' => '1100', 'name' => 'Current Assets',           'category' => 'assets',      'type' => 'parent',    'parent_code' => '1'],
            ['code' => '1110', 'name' => 'Cash on Hand',             'category' => 'assets',      'type' => 'cash',      'parent_code' => '1100'],
            ['code' => '1130', 'name' => 'Bank Accounts',            'category' => 'assets',      'type' => 'parent',    'parent_code' => '1100'],
            ['code' => '1131', 'name' => 'Main Operating Bank',      'category' => 'assets',      'type' => 'bank',      'parent_code' => '1130'],
            ['code' => '1140', 'name' => 'Accounts Receivable',      'category' => 'assets',      'type' => 'receivable','parent_code' => '1100'],
            ['code' => '1150', 'name' => 'Inventory',                'category' => 'assets',      'type' => 'inventory', 'parent_code' => '1100'],
            ['code' => '1160', 'name' => 'Prepaid Expenses',         'category' => 'assets',      'type' => 'other',     'parent_code' => '1100'],
            ['code' => '1200', 'name' => 'Non-Current Assets',       'category' => 'assets',      'type' => 'parent',    'parent_code' => '1'],
            ['code' => '1210', 'name' => 'Property & Equipment',     'category' => 'assets',      'type' => 'fixed',     'parent_code' => '1200'],
            ['code' => '1220', 'name' => 'Accumulated Depreciation', 'category' => 'assets',      'type' => 'fixed',     'parent_code' => '1200'],
            ['code' => '2',    'name' => 'Liabilities',              'category' => 'liabilities', 'type' => 'parent'],
            ['code' => '2100', 'name' => 'Current Liabilities',      'category' => 'liabilities', 'type' => 'parent',    'parent_code' => '2'],
            ['code' => '2110', 'name' => 'Accounts Payable',         'category' => 'liabilities', 'type' => 'payable',   'parent_code' => '2100'],
            ['code' => '2120', 'name' => 'Taxes Payable',            'category' => 'liabilities', 'type' => 'liability', 'parent_code' => '2100'],
            ['code' => '2150', 'name' => 'Accrued Expenses',         'category' => 'liabilities', 'type' => 'liability', 'parent_code' => '2100'],
            ['code' => '2200', 'name' => 'Salaries Payable',         'category' => 'liabilities', 'type' => 'liability', 'parent_code' => '2'],
            ['code' => '2210', 'name' => 'Loans Payable',            'category' => 'liabilities', 'type' => 'liability', 'parent_code' => '2'],
            ['code' => '3',    'name' => 'Equity',                   'category' => 'equity',      'type' => 'parent'],
            ['code' => '3100', 'name' => 'Capital',                  'category' => 'equity',      'type' => 'parent',    'parent_code' => '3'],
            ['code' => '3110', 'name' => 'Owners Capital',           'category' => 'equity',      'type' => 'equity',    'parent_code' => '3100'],
            ['code' => '3200', 'name' => 'Retained Earnings',        'category' => 'equity',      'type' => 'equity',    'parent_code' => '3'],
            ['code' => '3300', 'name' => 'Opening Balance Equity',   'category' => 'equity',      'type' => 'equity',    'parent_code' => '3'],
            ['code' => '4',    'name' => 'Revenue',                  'category' => 'revenue',     'type' => 'parent'],
            ['code' => '4100', 'name' => 'Operating Revenue',        'category' => 'revenue',     'type' => 'parent',    'parent_code' => '4'],
            ['code' => '4110', 'name' => 'Sales Revenue',            'category' => 'revenue',     'type' => 'revenue',   'parent_code' => '4100'],
            ['code' => '4120', 'name' => 'Service Income',           'category' => 'revenue',     'type' => 'revenue',   'parent_code' => '4100'],
            ['code' => '4200', 'name' => 'Other Income',             'category' => 'revenue',     'type' => 'parent',    'parent_code' => '4'],
            ['code' => '4210', 'name' => 'Miscellaneous Income',     'category' => 'revenue',     'type' => 'revenue',   'parent_code' => '4200'],
            ['code' => '5',    'name' => 'Expenses',                 'category' => 'expenses',    'type' => 'parent'],
            ['code' => '5100', 'name' => 'Cost of Goods Sold',       'category' => 'expenses',    'type' => 'parent',    'parent_code' => '5'],
            ['code' => '5110', 'name' => 'Product Cost',             'category' => 'expenses',    'type' => 'expense',   'parent_code' => '5100'],
            ['code' => '5200', 'name' => 'Operating Expenses',       'category' => 'expenses',    'type' => 'parent',    'parent_code' => '5'],
            ['code' => '5210', 'name' => 'Salaries & Wages',         'category' => 'expenses',    'type' => 'operating', 'parent_code' => '5200'],
            ['code' => '5220', 'name' => 'Rent Expense',             'category' => 'expenses',    'type' => 'operating', 'parent_code' => '5200'],
            ['code' => '5230', 'name' => 'Utilities Expense',        'category' => 'expenses',    'type' => 'operating', 'parent_code' => '5200'],
            ['code' => '5240', 'name' => 'Office Supplies',          'category' => 'expenses',    'type' => 'operating', 'parent_code' => '5200'],
            ['code' => '5250', 'name' => 'Depreciation Expense',     'category' => 'expenses',    'type' => 'operating', 'parent_code' => '5200'],
            ['code' => '5280', 'name' => 'Travel & Transport',       'category' => 'expenses',    'type' => 'operating', 'parent_code' => '5200'],
        ];

        foreach ($defaults as $def) {
            $parentId = null;
            if (isset($def['parent_code'])) {
                $parent = Account::query()->where('company_id', $cid)->where('code', $def['parent_code'])->first();
                if ($parent) $parentId = $parent->id;
            }
            Account::query()->create([
                'company_id' => $cid,
                'code'       => $def['code'],
                'name'       => $def['name'],
                'category'   => $def['category'],
                'type'       => $def['type'],
                'parent_id'  => $parentId,
                'balance'    => 0,
            ]);
        }

        $this->seedBankAccounts();
    }

    private function seedBankAccounts()
    {
        $cid    = auth()->user()->company_id;
        $parent = Account::query()->where('company_id', $cid)->where('code', '1130')->first();
        if (!$parent) return;

        $banks = [
            ['code' => '1133', 'name' => 'Al Rajhi Bank',       'bank' => 'Al Rajhi Bank',       'account' => '1234567890', 'iban' => 'SA03800000001234567890'],
            ['code' => '1134', 'name' => 'Saudi National Bank',  'bank' => 'Saudi National Bank',  'account' => '0987654321', 'iban' => 'SA03400000000987654321'],
            ['code' => '1135', 'name' => 'Riyad Bank',           'bank' => 'Riyad Bank',           'account' => '555666777',  'iban' => 'SA0320000000000555666777'],
        ];

        foreach ($banks as $b) {
            Account::query()->create([
                'company_id'     => $cid,
                'code'           => $b['code'],
                'name'           => $b['name'],
                'category'       => 'assets',
                'type'           => 'bank',
                'parent_id'      => $parent->id,
                'balance'        => 0,
                'bank_name'      => $b['bank'],
                'branch_name'    => 'Main Office',
                'account_number' => $b['account'],
                'iban'           => $b['iban'],
            ]);
        }
    }

    public function recalculateBalances()
    {
        $cid = auth()->user()->company_id;

        $accounts = Account::withoutGlobalScopes()
            ->where('company_id', $cid)
            ->where('type', '!=', 'parent')
            ->get();

        DB::transaction(function () use ($accounts, $cid) {
            foreach ($accounts as $account) {
                $items = JournalItem::withoutGlobalScopes()
                    ->where('journal_items.account_id', $account->id)
                    ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
                    ->where('journal_entries.status', 'posted')
                    ->where('journal_entries.company_id', $cid)
                    ->select('journal_items.debit', 'journal_items.credit')
                    ->get();

                $totalDebit  = $items->sum('debit');
                $totalCredit = $items->sum('credit');

                $normalDebit = in_array($account->category, ['assets', 'expenses']);
                $balance     = $normalDebit ? ($totalDebit - $totalCredit) : ($totalCredit - $totalDebit);

                Account::withoutGlobalScopes()
                    ->where('id', $account->id)
                    ->update(['balance' => $balance]);
            }
        });

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->back()->with('success', 'Account balances recalculated from journal entries.');
    }
}
