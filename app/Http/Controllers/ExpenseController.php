<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\PurchaseBill;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ExpenseController extends Controller
{
    private function currencySymbol(): string
    {
        $company = Company::find(auth()->user()->company_id);
        $map = ['SAR' => '﷼', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AED' => 'د.إ', 'KWD' => 'د.ك'];
        return $map[$company->currency ?? ''] ?? ($company->currency ?? '$');
    }

    public function index(Request $request)
    {
        $company = Company::find(auth()->user()->company_id);
        $curr = $this->currencySymbol();
        $suppliers = Supplier::query()->get();
        $expenseAccounts = Account::query()->where('category', 'expenses')
                                  ->where('type', '!=', 'parent')
                                  ->orderBy('name')
                                  ->get();
        $expenses = Expense::query()->with(['account', 'branch', 'bankAccount'])
                                   ->orderBy('expense_date', 'desc')
                                   ->get();
        $bills = PurchaseBill::query()->get();
        $branches = Branch::query()->get();
        $bankAccounts = Account::query()->whereIn('type', ['bank', 'cash'])
                               ->where('type', '!=', 'parent')
                               ->orderBy('name')
                               ->get(['id', 'name', 'code', 'branch_id']);

        $isMobile = (bool) preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|Windows Phone/i', $request->userAgent() ?? '')
            || $request->header('Sec-CH-UA-Mobile') === '?1'
            || $request->boolean('mobile');

        if ($isMobile) {
            return view('frontend.expense.add_all_expenses_pwa', compact('curr', 'expenseAccounts', 'expenses', 'bills', 'branches', 'bankAccounts'));
        }

        return view('frontend.expense.add_all_expenses', compact('curr', 'expenseAccounts', 'expenses', 'bills', 'branches', 'bankAccounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'expense_name'       => 'required|string|max:255',
            'amount'             => 'required|numeric|min:0',
            'expense_date'       => 'required|date',
            'expense_account_id' => 'required',
            'bank_account_id'    => 'required',
        ]);

        try {
            DB::beginTransaction();

            $cid = auth()->user()->company_id;

            $data = $request->except('_token');
            $data['created_by'] = auth()->id();
            $data['status'] = 'Approved';

            if ($request->hasFile('receipt')) {
                $file = $request->file('receipt');
                $filename = time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('upload/expenses'), $filename);
                $data['receipt'] = $filename;
            }

            $expense = Expense::query()->create($data);

            // Journal Entry: Dr Expense Account / Cr Bank/Cash
            $journalEntry = JournalEntry::query()->create([
                // Derived from the expense's own unique id rather than a
                // count-based sequence, which races under concurrent
                // requests and isn't scoped per company (see storeBill's
                // equivalent fix for the full explanation).
                'entry_number' => 'JE-EXP-' . date('Ymd') . '-' . str_pad($expense->id, 4, '0', STR_PAD_LEFT),
                'date'         => $request->expense_date,
                // "GE" (General Expense) distinguishes this from
                // PurchaseExpense's "EXP-PE-<id>" reference — both models
                // start their own id sequence from 1, so a plain "EXP-<id>"
                // lookup could otherwise match the wrong expense's entry.
                'reference'    => 'EXP-GE-' . $expense->id,
                'description'  => 'Expense: ' . $request->expense_name,
                'status'       => 'posted',
                'total_amount' => $request->amount,
                'company_id'   => $cid,
                'created_by'   => auth()->id(),
                'branch_id'    => $request->branch_id,
            ]);

            // Dr Expense Account
            JournalItem::query()->create([
                'journal_entry_id' => $journalEntry->id,
                'account_id'       => $request->expense_account_id,
                'company_id'       => $cid,
                'description'      => $request->expense_name,
                'debit'            => $request->amount,
                'credit'           => 0,
            ]);

            // Cr Bank/Cash Account
            JournalItem::query()->create([
                'journal_entry_id' => $journalEntry->id,
                'account_id'       => $request->bank_account_id,
                'company_id'       => $cid,
                'description'      => $request->expense_name,
                'debit'            => 0,
                'credit'           => $request->amount,
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Expense recorded and accounts updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error recording expense: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $expense = Expense::query()->findOrFail($id);

            // Delete journal items individually so the observer reverses account balances
            $journalEntry = JournalEntry::query()->where('reference', 'EXP-GE-' . $expense->id)->first();
            if ($journalEntry) {
                foreach ($journalEntry->items as $item) {
                    $item->delete();
                }
                $journalEntry->delete();
            }

            if ($expense->receipt && file_exists(public_path('upload/expenses/' . $expense->receipt))) {
                @unlink(public_path('upload/expenses/' . $expense->receipt));
            }

            $expense->delete();
            DB::commit();
            return redirect()->back()->with('success', 'Expense and its journal entry deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error deleting expense: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'expense_name'       => 'required|string|max:255',
            'amount'             => 'required|numeric|min:0',
            'expense_date'       => 'required|date',
            'expense_account_id' => 'required',
            'bank_account_id'    => 'required',
        ]);

        try {
            DB::beginTransaction();

            $cid = auth()->user()->company_id;
            $expense = Expense::query()->findOrFail($id);

            $data = $request->except('_token');
            if ($request->hasFile('receipt')) {
                if ($expense->receipt && file_exists(public_path('upload/expenses/' . $expense->receipt))) {
                    @unlink(public_path('upload/expenses/' . $expense->receipt));
                }
                $file = $request->file('receipt');
                $filename = time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('upload/expenses'), $filename);
                $data['receipt'] = $filename;
            }

            $expense->update($data);

            // Reverse old journal entry (delete items individually so observer fires)
            $journalEntry = JournalEntry::query()->where('reference', 'EXP-GE-' . $expense->id)->first();
            if ($journalEntry) {
                foreach ($journalEntry->items as $oldItem) {
                    $oldItem->delete();
                }

                // Update entry header
                $journalEntry->update([
                    'date'         => $request->expense_date,
                    'description'  => 'Expense: ' . $request->expense_name,
                    'total_amount' => $request->amount,
                    'company_id'   => $cid,
                ]);

                // Re-create items with new values
                JournalItem::query()->create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id'       => $request->expense_account_id,
                    'company_id'       => $cid,
                    'description'      => $request->expense_name,
                    'debit'            => $request->amount,
                    'credit'           => 0,
                ]);

                JournalItem::query()->create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id'       => $request->bank_account_id,
                    'company_id'       => $cid,
                    'description'      => $request->expense_name,
                    'debit'            => 0,
                    'credit'           => $request->amount,
                ]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Expense updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error updating expense: ' . $e->getMessage());
        }
    }

    public function receipt($id)
    {
        $expense = Expense::query()->with(['account', 'branch', 'bankAccount', 'supplier', 'createdBy'])->findOrFail($id);
        $company_profile = Company::find(auth()->user()->company_id);

        $pdf = Pdf::loadView('frontend.expense.pdf_expense_receipt', compact('expense', 'company_profile'))
                  ->setPaper('a4', 'portrait');

        return $pdf->stream('Receipt_EXP_' . $expense->id . '.pdf');
    }

    public function categoryView()
    {
        $currency = $this->currencySymbol();

        $categories = Account::query()
            ->where('category', 'expenses')
            ->withCount(['journalItems' => function ($q) {
                $q->whereHas('entry', fn ($query) => $query->where('status', 'posted'));
            }])
            ->orderBy('code')
            ->get();

        $parentAccounts = Account::query()
            ->where('category', 'expenses')
            ->where('type', 'parent')
            ->get();

        return view('frontend.expense.expense_categories', compact('categories', 'currency', 'parentAccounts'));
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:chart_of_accounts,code',
            'name' => 'required',
            'type' => 'required',
        ]);

        Account::query()->create([
            'code'        => $request->code,
            'name'        => $request->name,
            'category'    => 'expenses',
            'type'        => $request->type,
            'parent_id'   => $request->parent_id,
            'description' => $request->description,
            'is_active'   => 1,
            'company_id'  => auth()->user()->company_id,
        ]);

        return redirect()->back()->with('success', 'Expense category created successfully.');
    }

    public function updateCategory(Request $request, $id)
    {
        $category = Account::query()->findOrFail($id);

        $request->validate([
            'code' => 'required|unique:chart_of_accounts,code,' . $id,
            'name' => 'required',
            'type' => 'required',
        ]);

        $category->update([
            'code'        => $request->code,
            'name'        => $request->name,
            'type'        => $request->type,
            'parent_id'   => $request->parent_id,
            'description' => $request->description,
        ]);

        return redirect()->back()->with('success', 'Expense category updated successfully.');
    }

    public function deleteCategory($id)
    {
        $category = Account::query()->findOrFail($id);

        if ($category->children()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete category with sub-categories.');
        }

        if ($category->journalItems()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete category with transaction history.');
        }

        $category->delete();
        return redirect()->back()->with('success', 'Expense category deleted successfully.');
    }
}
