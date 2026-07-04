<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BankTransactionController extends Controller
{
    private function isAdmin(): bool
    {
        $role = strtolower(trim((string) (Auth::user()->role ?? '')));
        return in_array($role, ['admin', 'super admin']);
    }

    public function updateTransaction(Request $request, $id)
    {
        if (!$this->isAdmin()) {
            abort(403, 'Only administrators can edit transactions.');
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'description' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:chart_of_accounts,id',
        ]);

        /** @var JournalEntry $entry */
        $entry = JournalEntry::query()->with('items')->findOrFail($id);
        $bankAccountIds = Account::query()->whereIn('type', ['bank', 'cash'])->pluck('id');

        DB::transaction(function () use ($entry, $request, $bankAccountIds) {
            $entry->update([
                'date' => $request->date,
                'description' => $request->description,
                'total_amount' => $request->amount,
            ]);

            foreach ($entry->items as $item) {
                $isBankLeg = $bankAccountIds->contains($item->account_id);

                $update = [
                    'debit' => $item->debit > 0 ? $request->amount : 0,
                    'credit' => $item->credit > 0 ? $request->amount : 0,
                ];

                // Only the non-bank ("category") leg's account can be retargeted.
                if (!$isBankLeg && $request->filled('category_id')) {
                    $update['account_id'] = $request->category_id;
                }

                $item->update($update);
            }
        });

        return redirect()->back()->with('success', 'Transaction updated successfully.');
    }

    public function destroyTransaction(Request $request, $id)
    {
        if (!$this->isAdmin()) {
            abort(403, 'Only administrators can delete transactions.');
        }

        $request->validate(['password' => 'required|string']);

        if (!\Illuminate\Support\Facades\Hash::check($request->password, Auth::user()->password)) {
            return redirect()->back()->with('error', 'Incorrect password — transaction was not deleted.');
        }

        /** @var JournalEntry $entry */
        $entry = JournalEntry::query()->with('items')->findOrFail($id);

        DB::transaction(function () use ($entry) {
            foreach ($entry->items as $item) {
                $item->delete(); // observer reverses the account balance
            }
            $entry->delete();
        });

        return redirect()->back()->with('success', 'Transaction deleted and balances reversed.');
    }

    public function index(Request $request)
    {
        // 1. Get Accounts
        $cid = auth()->user()->company_id;
        $bankAccounts = Account::query()->where('company_id', $cid)->whereIn('type', ['bank', 'cash'])->where('is_active', 1)->get();
        $accountCategories = Account::query()->where('company_id', $cid)->whereIn('code', ['3110', '1140', '2210'])->get();
        $parentAccounts = Account::query()->whereNull('parent_id')->orderBy('code')->get();
        
        // Stats
        $totalBalance = $bankAccounts->sum('balance');
        $activeAccounts = $bankAccounts->count();
        $branches = Branch::query()->get();
        /** @var Company|null $company */
        $company = Company::find(auth()->user()->company_id);
        $companyCurrency = $company->currency ?? '$';

        // 2. Identify transactions this month for stats
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        
        // This is a rough estimation of stats based on reference prefixes we will establish
        $depositsAmount = JournalEntry::query()->where('reference', 'like', 'DEP-%')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('total_amount');
            
        $depositsCount = JournalEntry::query()->where('reference', 'like', 'DEP-%')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->count();

        $withdrawalsAmount = JournalEntry::query()->where('reference', 'like', 'WTH-%')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('total_amount');
            
        $withdrawalsCount = JournalEntry::query()->where('reference', 'like', 'WTH-%')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->count();

        $transfersAmount = JournalEntry::query()->where('reference', 'like', 'TRF-%')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('total_amount');
            
        $transfersCount = JournalEntry::query()->where('reference', 'like', 'TRF-%')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->count();

        $adjustmentsAmount = JournalEntry::query()->where('reference', 'like', 'ADJ-%')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('total_amount');
            
        $adjustmentsCount = JournalEntry::query()->where('reference', 'like', 'ADJ-%')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->count();

        // 3. Get Recent Transactions across all banks with dynamic filtering
        $query = JournalEntry::query()->with(['items.account'])->orderBy('date', 'desc');

        // Apply filters
        if ($request->filled('account_id')) {
            $query->whereHas('items', function(\Illuminate\Database\Eloquent\Builder $q) use ($request) {
                $q->where('account_id', $request->account_id);
            });
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            /** @disregard P0406 */
            $query->where(function(\Illuminate\Database\Eloquent\Builder $q) use ($searchTerm) {
                $q->where('reference', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%')
                  ->orWhere('total_amount', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('items.account', function(\Illuminate\Database\Eloquent\Builder $sq) use ($searchTerm) {
                      $sq->where('name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('code', 'like', '%' . $searchTerm . '%');
                  });
            });
        }

        if ($request->filled('type')) {
            $prefix = match($request->type) {
                'deposits' => 'DEP-%',
                'withdrawals' => 'WTH-%',
                'transfers' => 'TRF-%',
                'adjustments' => 'ADJ-%',
                default => null
            };

            if ($prefix) {
                $query->where('reference', 'like', $prefix);
            }
        } else {
            // Default: show only our prefixed bank/cash transactions
            /** @disregard P0406 */
            $query->where(function(\Illuminate\Database\Eloquent\Builder $q) {
                $q->where('reference', 'like', 'DEP-%')
                  ->orWhere('reference', 'like', 'WTH-%')
                  ->orWhere('reference', 'like', 'TRF-%')
                  ->orWhere('reference', 'like', 'ADJ-%');
            });
        }

        $transactions = $query->paginate(10);
        $transactions->appends($request->all()); // Keep filters in pagination links

        // 4. Generate next reference previews
        $nextDepositRef = 'DEP-' . date('Ymd') . '-' . str_pad(JournalEntry::query()->where('reference', 'like', 'DEP-%')->count() + 1, 4, '0', STR_PAD_LEFT);
        $nextWithdrawRef = 'WTH-' . date('Ymd') . '-' . str_pad(JournalEntry::query()->where('reference', 'like', 'WTH-%')->count() + 1, 4, '0', STR_PAD_LEFT);
        $nextTransferRef = 'TRF-' . date('Ymd') . '-' . str_pad(JournalEntry::query()->where('reference', 'like', 'TRF-%')->count() + 1, 4, '0', STR_PAD_LEFT);
        $nextAdjustmentRef = 'ADJ-' . date('Ymd') . '-' . str_pad(JournalEntry::query()->where('reference', 'like', 'ADJ-%')->count() + 1, 4, '0', STR_PAD_LEFT);

        return view('frontend.account.account_management', [
            'bankAccounts' => $bankAccounts,
            'accountCategories' => $accountCategories,
            'totalBalance' => $totalBalance,
            'activeAccounts' => $activeAccounts,
            'depositsAmount' => $depositsAmount,
            'depositsCount' => $depositsCount,
            'withdrawalsAmount' => $withdrawalsAmount,
            'withdrawalsCount' => $withdrawalsCount,
            'transfersAmount' => $transfersAmount,
            'transfersCount' => $transfersCount,
            'adjustmentsAmount' => $adjustmentsAmount,
            'adjustmentsCount' => $adjustmentsCount,
            'transactions' => $transactions,
            'branches' => $branches,
            'companyCurrency' => $companyCurrency,
            'parentAccounts' => $parentAccounts,
            'nextDepositRef' => $nextDepositRef,
            'nextWithdrawRef' => $nextWithdrawRef,
            'nextTransferRef' => $nextTransferRef,
            'nextAdjustmentRef' => $nextAdjustmentRef,
        ]);
    }

    public function storeDeposit(Request $request)
    {
        $request->validate([
            'bank_account_id' => 'required|exists:chart_of_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'category_id' => 'required|exists:chart_of_accounts,id',
        ]);

        DB::transaction(function () use ($request) {
            $count = JournalEntry::query()->where('reference', 'like', 'DEP-%')->count() + 1;
            $ref = 'DEP-' . date('Ymd') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

            $entry = JournalEntry::query()->create([
                'entry_number' => $ref,
                'date' => $request->date,
                'reference' => $ref,
                'description' => 'Deposit from ' . ($request->received_from ?? 'Client') . ' : ' . $request->notes,
                'status' => 'posted',
                'total_amount' => $request->amount,
                'created_by' => Auth::id() ?? 1,
            ]);

            // Bank gets DEBIT (Money comes IN to asset)
            JournalItem::query()->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $request->bank_account_id,
                'debit' => $request->amount,
                'credit' => 0,
                'description' => 'Deposit ' . $ref
            ]);

            // Category gets CREDIT
            JournalItem::query()->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $request->category_id,
                'debit' => 0,
                'credit' => $request->amount,
                'description' => 'Deposit ' . $ref
            ]);
        });

        return redirect()->back()->with('success', 'Deposit recorded successfully.');
    }

    public function storeWithdrawal(Request $request)
    {
        $request->validate([
            'bank_account_id' => 'required|exists:chart_of_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'category_id' => 'required|exists:chart_of_accounts,id',
        ]);

        DB::transaction(function () use ($request) {
            $count = JournalEntry::query()->where('reference', 'like', 'WTH-%')->count() + 1;
            $ref = 'WTH-' . date('Ymd') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

            $entry = JournalEntry::query()->create([
                'entry_number' => $ref,
                'date' => $request->date,
                'reference' => $ref,
                'description' => 'Withdrawal to ' . ($request->paid_to ?? 'Vendor') . ' : ' . $request->notes,
                'status' => 'posted',
                'total_amount' => $request->amount,
                'created_by' => Auth::id() ?? 1,
            ]);

            // Category gets DEBIT (Expense goes up)
            JournalItem::query()->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $request->category_id,
                'debit' => $request->amount,
                'credit' => 0,
                'description' => 'Withdrawal ' . $ref
            ]);

            // Bank gets CREDIT (Money goes OUT of asset)
            JournalItem::query()->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $request->bank_account_id,
                'debit' => 0,
                'credit' => $request->amount,
                'description' => 'Withdrawal ' . $ref
            ]);
        });

        return redirect()->back()->with('success', 'Withdrawal recorded successfully.');
    }

    public function storeTransfer(Request $request)
    {
        $request->validate([
            'from_account_id' => 'required|exists:chart_of_accounts,id',
            'to_account_id' => 'required|exists:chart_of_accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
        ]);

        DB::transaction(function () use ($request) {
            $count = JournalEntry::query()->where('reference', 'like', 'TRF-%')->count() + 1;
            $ref = 'TRF-' . date('Ymd') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

            $fee = $request->fee ? mt_rand(0, 50) : 0; // if fee handling needed

            $entry = JournalEntry::query()->create([
                'entry_number' => $ref,
                'date' => $request->date,
                'reference' => $ref,
                'description' => 'Internal Transfer : ' . $request->reason,
                'status' => 'posted',
                'total_amount' => $request->amount,
                'created_by' => Auth::id() ?? 1,
            ]);

            // Receiving Bank gets DEBIT (Money comes IN)
            JournalItem::query()->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $request->to_account_id,
                'debit' => $request->amount,
                'credit' => 0,
                'description' => 'Transfer in ' . $ref
            ]);

            // Sending Bank gets CREDIT (Money goes OUT)
            JournalItem::query()->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $request->from_account_id,
                'debit' => 0,
                'credit' => $request->amount,
                'description' => 'Transfer out ' . $ref
            ]);
        });

        return redirect()->back()->with('success', 'Transfer completed successfully.');
    }

    public function storeAdjustment(Request $request)
    {
        $request->validate([
            'bank_account_id' => 'required|exists:chart_of_accounts,id',
            'type' => 'required|in:increase,decrease',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
        ]);

        DB::transaction(function () use ($request) {
            $count = JournalEntry::query()->where('reference', 'like', 'ADJ-%')->count() + 1;
            $ref = 'ADJ-' . date('Ymd') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

            $entry = JournalEntry::query()->create([
                'entry_number' => $ref,
                'date' => $request->date,
                'reference' => $ref,
                'description' => 'Balance Adjustment : ' . $request->reason,
                'status' => 'posted',
                'total_amount' => $request->amount,
                'created_by' => Auth::id() ?? 1,
            ]);

            // Use Retained Earnings or a catch-all equity account if category missing, let's just pick any equity/expense
            /** @var Account|null $adjAccount */
            $adjAccount = Account::query()->where('category', 'equity')->first();
            if (!$adjAccount) {
                /** @var Account|null $adjAccount */
                $adjAccount = Account::query()->where('category', 'expenses')->first();
            }
            if (!$adjAccount) {
                /** @var Account|null $adjAccount */
                $adjAccount = Account::query()->first();
            }

            if ($request->type == 'increase') {
                // Bank gets DEBIT
                JournalItem::query()->create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $request->bank_account_id,
                    'debit' => $request->amount,
                    'credit' => 0,
                ]);
                JournalItem::query()->create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $adjAccount->id,
                    'debit' => 0,
                    'credit' => $request->amount,
                ]);
            } else {
                // Bank gets CREDIT
                JournalItem::query()->create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $adjAccount->id,
                    'debit' => $request->amount,
                    'credit' => 0,
                ]);
                JournalItem::query()->create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $request->bank_account_id,
                    'debit' => 0,
                    'credit' => $request->amount,
                ]);
            }
        });

        return redirect()->back()->with('success', 'Adjustment recorded successfully.');
    }
}
