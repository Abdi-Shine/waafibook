<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Loan;
use App\Models\Employee;
use App\Models\Account;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    private function currencySymbol(): string
    {
        $company = Company::find(auth()->user()->company_id);
        $map = ['SAR' => '﷼', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AED' => 'د.إ', 'KWD' => 'د.ك'];
        return $map[$company->currency ?? ''] ?? ($company->currency ?? '$');
    }

    public function viewLoan(Request $request)
    {
        $company  = Company::find(auth()->user()->company_id);
        $currency = $this->currencySymbol();
        $employees = Employee::query()->get();
        $loans = Loan::with('employee')->latest()->get();

        $activeLoans  = $loans->where('status', 'active');
        $pendingLoans = $loans->where('status', 'pending');
        $settledLoans = $loans->where('status', 'settled');

        $totalOutstanding = $activeLoans->sum('balance');
        $activeCount      = $activeLoans->count();

        $thisMonthLoans = $loans->where('created_at', '>=', now()->startOfMonth());
        $thisMonthValue = $thisMonthLoans->sum('amount');
        $thisMonthCount = $thisMonthLoans->count();

        $pendingCount = $pendingLoans->count();
        $pendingValue = $pendingLoans->sum('amount');

        $totalRecovered = $loans->whereIn('status', ['active', 'settled'])->sum('recovered');
        $totalDisbursed = $loans->whereIn('status', ['active', 'settled'])->sum('amount');
        $recoveryRate   = $totalDisbursed > 0 ? round(($totalRecovered / $totalDisbursed) * 100, 1) : 0;

        $isMobile = (bool) preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|Windows Phone/i', $request->userAgent() ?? '')
            || $request->header('Sec-CH-UA-Mobile') === '?1'
            || $request->boolean('mobile');

        if ($isMobile) {
            return view('frontend.expense.cash_loan_pwa', compact(
                'employees', 'loans', 'activeLoans', 'pendingLoans', 'settledLoans',
                'totalOutstanding', 'activeCount', 'thisMonthValue', 'thisMonthCount',
                'pendingCount', 'pendingValue', 'totalRecovered', 'recoveryRate', 'currency'
            ));
        }

        return view('frontend.expense.cash_loan', compact(
            'employees', 'loans', 'activeLoans', 'pendingLoans', 'settledLoans',
            'totalOutstanding', 'activeCount', 'thisMonthValue', 'thisMonthCount',
            'pendingCount', 'pendingValue', 'totalRecovered', 'recoveryRate', 'currency'
        ));
    }

    public function storeLoan(Request $request)
    {
        $validated = $request->validate([
            'borrower_name'     => 'required|string|max:255',
            'borrower_type'     => 'nullable|string',
            'phone'             => 'nullable|string|max:30',
            'employee_id'       => 'nullable|exists:employees,id',
            'amount'            => 'required|numeric|min:1',
            'start_date'        => 'required|date',
            'type'              => 'nullable|string',
            'reason'            => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $loan = new Loan();
            $loan->borrower_name     = $validated['borrower_name'];
            $loan->borrower_type     = $validated['borrower_type'] ?? 'individual';
            $loan->phone             = $validated['phone'] ?? null;
            $loan->employee_id       = $validated['employee_id'] ?? null;
            $loan->amount            = $validated['amount'];
            $loan->monthly_deduction = $validated['amount']; // one-time full repayment
            $loan->start_date        = $validated['start_date'];
            $loan->balance           = $validated['amount'];
            $loan->duration          = 1;
            $loan->type              = $validated['type'] ?? 'personal';
            $loan->reason            = $validated['reason'];
            $loan->status            = 'pending';
            $loan->save();

            $loan->loan_id = 'LN-' . date('Y') . '-' . str_pad($loan->id, 3, '0', STR_PAD_LEFT);
            $loan->save();

            DB::commit();
            return redirect()->back()->with('success', 'Loan request submitted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error submitting loan: ' . $e->getMessage());
        }
    }

    public function updateLoan(Request $request, $id)
    {
        $loan = Loan::query()->findOrFail($id);

        $validated = $request->validate([
            'borrower_name'     => 'required|string|max:255',
            'borrower_type'     => 'nullable|string',
            'phone'             => 'nullable|string|max:30',
            'employee_id'       => 'nullable|exists:employees,id',
            'amount'            => 'required|numeric|min:1',
            'start_date'        => 'required|date',
            'type'              => 'nullable|string',
            'reason'            => 'required|string',
        ]);

        if ($loan->status !== 'pending') {
            return redirect()->back()->with('error', 'Cannot edit a loan that is already active or settled.');
        }

        $loan->borrower_name     = $validated['borrower_name'];
        $loan->borrower_type     = $validated['borrower_type'] ?? 'individual';
        $loan->phone             = $validated['phone'] ?? null;
        $loan->employee_id       = $validated['employee_id'] ?? null;
        $loan->amount            = $validated['amount'];
        $loan->monthly_deduction = $validated['amount'];
        $loan->start_date        = $validated['start_date'];
        $loan->balance           = $validated['amount'];
        $loan->duration          = 1;
        $loan->type              = $validated['type'] ?? 'personal';
        $loan->reason            = $validated['reason'];
        $loan->save();

        return redirect()->back()->with('success', 'Loan request updated successfully.');
    }

    public function changeStatus(Request $request, $id)
    {
        $loan   = Loan::query()->with('employee')->findOrFail($id);
        $status = $request->status;

        if (!in_array($status, ['active', 'settled', 'rejected'])) {
            return redirect()->back()->with('error', 'Invalid status update.');
        }

        try {
            DB::beginTransaction();

            $cid = auth()->user()->company_id;

            // Activating: create disbursement journal entry
            if ($status === 'active' && $loan->status === 'pending') {
                $loansReceivableAccount = Account::withoutGlobalScopes()
                    ->where('company_id', $cid)
                    ->where(function ($q) {
                        $q->where('code', '1300')
                          ->orWhere('name', 'like', '%Employee Loan%')
                          ->orWhere('name', 'like', '%Staff Loan%');
                    })
                    ->first();

                $cashAccount = Account::withoutGlobalScopes()
                    ->where('company_id', $cid)
                    ->where(function ($q) {
                        $q->where('code', '1110')
                          ->orWhere('type', 'cash')
                          ->orWhere('name', 'like', '%Cash on Hand%');
                    })
                    ->orderByRaw("CASE WHEN code = '1110' THEN 0 WHEN type = 'cash' THEN 1 ELSE 2 END")
                    ->first();

                if ($cashAccount) {
                    $entry = JournalEntry::query()->create([
                        'entry_number' => 'JE-LN-' . date('Ymd') . '-' . str_pad($loan->id, 5, '0', STR_PAD_LEFT),
                        'date'         => now()->toDateString(),
                        'reference'    => 'LOAN-' . $loan->loan_id,
                        'description'  => 'Loan disbursement: ' . ($loan->employee->name ?? 'Employee') . ' (' . $loan->loan_id . ')',
                        'status'       => 'posted',
                        'total_amount' => $loan->amount,
                        'company_id'   => $cid,
                        'created_by'   => Auth::id(),
                    ]);

                    // Dr Employee Loans Receivable (if account exists)
                    if ($loansReceivableAccount) {
                        JournalItem::query()->create([
                            'journal_entry_id' => $entry->id,
                            'account_id'       => $loansReceivableAccount->id,
                            'company_id'       => $cid,
                            'description'      => 'Loan to ' . ($loan->employee->name ?? 'Employee'),
                            'debit'            => $loan->amount,
                            'credit'           => 0,
                        ]);
                    }

                    // Cr Cash on Hand — always reduce cash
                    JournalItem::query()->create([
                        'journal_entry_id' => $entry->id,
                        'account_id'       => $cashAccount->id,
                        'company_id'       => $cid,
                        'description'      => 'Loan disbursement ' . $loan->loan_id,
                        'debit'            => 0,
                        'credit'           => $loan->amount,
                    ]);
                }
            }

            $loan->status = $status;
            if ($status === 'settled') {
                $loan->recovered = $loan->amount;
                $loan->balance   = 0;
            }
            $loan->save();

            DB::commit();
            return redirect()->back()->with('success', 'Loan status updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error updating loan status: ' . $e->getMessage());
        }
    }

    public function receivePayment(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();

            $loan   = Loan::query()->findOrFail($id);
            $cid    = auth()->user()->company_id;
            $amount = min((float) $request->amount, (float) $loan->balance);

            if ($amount <= 0) {
                return redirect()->back()->with('error', 'Loan is already fully settled.');
            }

            $loan->recovered = (float) $loan->recovered + $amount;
            $loan->balance   = (float) $loan->balance   - $amount;

            if ($loan->balance <= 0.005) {
                $loan->balance = 0;
                $loan->status  = 'settled';
            }
            $loan->save();

            // GL: Dr Cash on Hand (money returned) / Cr Loans Receivable
            $cashAccount = Account::withoutGlobalScopes()
                ->where('company_id', $cid)
                ->where(function ($q) {
                    $q->where('code', '1110')
                      ->orWhere('type', 'cash')
                      ->orWhere('name', 'like', '%Cash on Hand%');
                })
                ->orderByRaw("CASE WHEN code = '1110' THEN 0 WHEN type = 'cash' THEN 1 ELSE 2 END")
                ->first();

            $loansReceivableAccount = Account::withoutGlobalScopes()
                ->where('company_id', $cid)
                ->where(function ($q) {
                    $q->where('code', '1300')
                      ->orWhere('name', 'like', '%Employee Loan%')
                      ->orWhere('name', 'like', '%Staff Loan%')
                      ->orWhere('name', 'like', '%Loans Receivable%');
                })
                ->first();

            if ($cashAccount) {
                $borrowerName = $loan->borrower_name ?: ($loan->employee->full_name ?? 'Borrower');
                $entry = JournalEntry::query()->create([
                    'entry_number' => 'JE-LNR-' . date('Ymd') . '-' . str_pad($loan->id, 5, '0', STR_PAD_LEFT),
                    'date'         => now()->toDateString(),
                    'reference'    => 'LOANPAY-' . $loan->loan_id,
                    'description'  => 'Loan repayment received: ' . $borrowerName . ' (' . $loan->loan_id . ')',
                    'status'       => 'posted',
                    'total_amount' => $amount,
                    'company_id'   => $cid,
                    'created_by'   => Auth::id(),
                ]);

                // Dr Cash on Hand
                JournalItem::query()->create([
                    'journal_entry_id' => $entry->id,
                    'account_id'       => $cashAccount->id,
                    'company_id'       => $cid,
                    'description'      => 'Loan repayment from ' . $borrowerName,
                    'debit'            => $amount,
                    'credit'           => 0,
                ]);

                // Cr Loans Receivable
                if ($loansReceivableAccount) {
                    JournalItem::query()->create([
                        'journal_entry_id' => $entry->id,
                        'account_id'       => $loansReceivableAccount->id,
                        'company_id'       => $cid,
                        'description'      => 'Loan repayment ' . $loan->loan_id,
                        'debit'            => 0,
                        'credit'           => $amount,
                    ]);
                }
            }

            DB::commit();

            $msg = $loan->status === 'settled'
                ? 'Loan fully settled! Cash on Hand has been updated.'
                : 'Payment of ' . number_format($amount, 2) . ' recorded. Remaining balance: ' . number_format($loan->balance, 2);

            return redirect()->back()->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error recording payment: ' . $e->getMessage());
        }
    }

    public function deleteLoan($id)
    {
        $loan = Loan::query()->findOrFail($id);

        if (!in_array($loan->status, ['pending', 'rejected'])) {
            return redirect()->back()->with('error', 'Cannot delete active or settled loan records.');
        }

        $loan->delete();
        return redirect()->back()->with('success', 'Loan record deleted.');
    }

    public function loanPayslip($id)
    {
        $company  = Company::find(auth()->user()->company_id);
        $currency = $this->currencySymbol();
        $loan     = Loan::with('employee')->findOrFail($id);

        return view('frontend.expense.cash_loan_pdf_payslip', compact('loan', 'currency', 'company'));
    }
}
