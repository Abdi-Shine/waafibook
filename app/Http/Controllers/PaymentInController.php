<?php

namespace App\Http\Controllers;

use App\Models\PaymentIn;
use App\Models\Customer;
use App\Models\Company;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentInController extends Controller
{
    public function index(Request $request)
    {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = PaymentIn::query()->with('customer', 'creator', 'bankAccount');

        if ($request->search) {
            $search = $request->search;
            /** @disregard P0406 */
            $query->where(function(\Illuminate\Database\Eloquent\Builder $q) use ($search) {
                $q->where('receipt_no', 'like', '%' . $search . '%')
                  ->orWhere('invoice_no', 'like', '%' . $search . '%')
                  ->orWhereHas('customer', function(\Illuminate\Database\Eloquent\Builder $sub) use ($search) {
                      $sub->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->method) {
            $query->where('payment_method', $request->method);
        }

        if ($request->date) {
            $query->whereDate('payment_date', $request->date);
        }

        $payments = $query->latest()->paginate(10);
        
        $todayReceipts = PaymentIn::query()->whereDate('payment_date', now())->sum('amount');
        $monthReceipts = PaymentIn::query()->whereMonth('payment_date', now()->month)->sum('amount');
        $pendingPayments = PaymentIn::query()->where('status', 'pending')->sum('amount');
        $totalTransactions = PaymentIn::query()->whereMonth('payment_date', now()->month)->count();

        $customers = Customer::query()->orderBy('name')->get();
        /** @var Company|null $company */
        $company = Company::find(auth()->user()->company_id);
        $bankAccounts = Account::query()
            ->where('type', 'cash')
            ->where(fn($q) => $q->where('name', 'like', '%Cash on Hand%')->orWhere('name', 'like', '%Cash in Hand%'))
            ->where('is_active', 1)
            ->get();

        $suggestedInvoiceNo = 'INV-' . date('Ymd') . '-' . str_pad(PaymentIn::query()->count() + 1, 4, '0', STR_PAD_LEFT);

        return view('frontend.sales.payment_in', compact(
            'payments', 'todayReceipts', 'monthReceipts', 'pendingPayments', 
            'totalTransactions', 'customers', 'company', 'suggestedInvoiceNo', 'bankAccounts'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'bank_account_id' => 'required|exists:chart_of_accounts,id',
        ]);

        // This screen always credits Accounts Receivable for whatever amount
        // is typed in, with no real link to an actual invoice — "invoice_no"
        // is just a free-text memo, never validated against a real
        // SalesOrder. Recording a payment bigger than what the customer
        // actually owes (or recording one at all when they owe nothing) was
        // silently driving AR negative on the balance sheet with no
        // underlying transaction to justify it. Capping at their current
        // outstanding balance keeps the ledger honest.
        //
        // A NEGATIVE balance means the business owes the customer money
        // instead (e.g. a return on an already-paid invoice — see
        // SalesReturnController, which books that as Customer Refunds
        // Payable). That's a real, payable obligation too, just in the
        // opposite direction, so it's handled here as a 'refund' rather
        // than rejected outright.
        /** @var Customer|null $customer */
        $customer = Customer::query()->find($request->customer_id);
        $outstanding = (float) ($customer->amount_balance ?? 0);
        $isRefund = $outstanding < 0;
        $payable = abs($outstanding);

        if ($outstanding == 0) {
            return redirect()->back()->with('error', 'This customer has no outstanding balance — there is nothing to receive payment against.');
        }
        if ((float) $request->amount > $payable) {
            $label = $isRefund ? 'the refund owed to the customer' : 'the customer\'s outstanding balance';
            return redirect()->back()->with('error', 'Amount ($' . number_format($request->amount, 2) . ') exceeds ' . $label . ' ($' . number_format($payable, 2) . ').');
        }

        DB::transaction(function () use ($request, $isRefund) {
            $count = PaymentIn::query()->count() + 1;
            $receiptNo = 'RCT-' . date('Y') . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);

            /** @var Account|null $bankAccount */
            $bankAccount = Account::query()->find($request->bank_account_id);
            $method = (isset($bankAccount->type) && $bankAccount->type == 'cash') ? 'Cash' : 'Bank Transfer';

            $payment = PaymentIn::query()->create([
                'receipt_no' => $receiptNo,
                'customer_id' => $request->customer_id,
                'bank_account_id' => $request->bank_account_id,
                'invoice_no' => $request->invoice_no,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'payment_method' => $method,
                'notes' => $request->notes,
                'status' => 'completed',
                'type' => $isRefund ? 'refund' : 'receipt',
                'created_by' => Auth::id(),
            ]);

            // Update Customer Balance — a receipt reduces what they owe us
            // (balance moves down); a refund reduces what we owe them
            // (balance moves up, back toward zero).
            /** @var Customer|null $customer */
            $customer = Customer::query()->find($request->customer_id);
            if ($customer) {
                $customer->amount_balance += $isRefund ? $request->amount : -$request->amount;
                $customer->save();
            }

            // Accounting - Journal Entry
            $this->createAccountingEntry($payment);
        });

        return redirect()->back()->with('success', $isRefund ? 'Refund paid to customer successfully.' : 'Payment received successfully.');
    }

    private function createAccountingEntry($payment)
    {
        // 1. Find Asset Account from the selected bank_account_id
        /** @var Account|null $assetAccount */
        $assetAccount = Account::query()->find($payment->bank_account_id);
        
        if (!$assetAccount) {
            // Fallback for old records
            if (in_array($payment->payment_method, ['Cash'])) {
                $assetAccount = Account::query()->where('code', '1010')->first() ?: Account::query()->where('type', 'cash')->first();
            } else {
                $assetAccount = Account::query()->where('type', 'bank')->first() ?: Account::query()->where('name', 'like', '%Bank%')->first();
            }
        }

        $isRefund = $payment->type === 'refund';

        // Accounts Receivable (normal receipt) or Customer Refunds Payable
        // (paying out a refund — see SalesReturnController, which is what
        // books money into this account in the first place).
        $receivableAccount = $isRefund
            ? (Account::query()->where('code', '2160')->first() ?: Account::query()->where('name', 'Customer Refunds Payable')->first())
            : (Account::query()->where('code', '1030')->first() ?: Account::query()->where('name', 'like', '%Receivable%')->first());

        if ($assetAccount && $receivableAccount) {
            $companyId = $payment->company_id ?? auth()->user()->company_id;

            $entry = JournalEntry::query()->create([
                'company_id'   => $companyId,
                'entry_number' => 'JE-PAY-' . date('Ymd') . '-' . str_pad($payment->id, 4, '0', STR_PAD_LEFT),
                'date'         => $payment->payment_date,
                'reference'    => $payment->receipt_no,
                'description'  => ($isRefund ? 'Refund paid to ' : 'Payment received from ') . $payment->customer->name,
                'status'       => 'posted',
                'total_amount' => $payment->amount,
                'created_by'   => Auth::id(),
            ]);

            if ($isRefund) {
                // Dr Customer Refunds Payable (settles the liability)
                JournalItem::query()->create([
                    'company_id'       => $companyId,
                    'journal_entry_id' => $entry->id,
                    'account_id'       => $receivableAccount->id,
                    'debit'            => $payment->amount,
                    'credit'           => 0,
                    'description'      => 'Refund ' . $payment->receipt_no,
                ]);

                // Cr Cash/Bank (money paid out)
                JournalItem::query()->create([
                    'company_id'       => $companyId,
                    'journal_entry_id' => $entry->id,
                    'account_id'       => $assetAccount->id,
                    'debit'            => 0,
                    'credit'           => $payment->amount,
                    'description'      => 'Refund ' . $payment->receipt_no,
                ]);
            } else {
                JournalItem::query()->create([
                    'company_id'       => $companyId,
                    'journal_entry_id' => $entry->id,
                    'account_id'       => $assetAccount->id,
                    'debit'            => $payment->amount,
                    'credit'           => 0,
                    'description'      => 'Receipt ' . $payment->receipt_no,
                ]);

                JournalItem::query()->create([
                    'company_id'       => $companyId,
                    'journal_entry_id' => $entry->id,
                    'account_id'       => $receivableAccount->id,
                    'debit'            => 0,
                    'credit'           => $payment->amount,
                    'description'      => 'Receipt ' . $payment->receipt_no,
                ]);
            }
        } else {
            Log::error("Accounting failed for PaymentIn {$payment->id}: Asset or Receivable/Refunds Payable account missing.");
            throw new \RuntimeException("Required accounts missing — payment journal entry could not be created.");
        }
    }

    public function view($id)
    {
        /** @var PaymentIn $payment */
        $payment = PaymentIn::query()->with('customer', 'creator')->findOrFail($id);
        /** @var Company|null $company */
        $company = Company::find(auth()->user()->company_id);
        return view('frontend.sales.pdf_receipt', compact('payment', 'company'));
    }

    public function download($id)
    {
        /** @var PaymentIn $payment */
        $payment = PaymentIn::query()->with('customer', 'creator')->findOrFail($id);
        /** @var Company|null $company */
        $company = Company::find(auth()->user()->company_id);
        
        $pdf = Pdf::loadView('frontend.sales.pdf_receipt', compact('payment', 'company'));
        return $pdf->download('Receipt_' . $payment->receipt_no . '.pdf');
    }

    public function updateStatus(Request $request, $id)
    {
        /** @var PaymentIn $payment */
        $payment = PaymentIn::query()->findOrFail($id);

        $wasCompleted = $payment->status === 'completed';
        $becomingVoid = in_array($request->status, ['failed', 'void', 'cancelled']);

        DB::transaction(function () use ($payment, $request, $wasCompleted, $becomingVoid) {
            $payment->status = $request->status;
            $payment->save();

            // Reverse GL and customer balance if a completed payment is being voided
            if ($wasCompleted && $becomingVoid) {
                /** @var Customer|null $customer */
                $customer = Customer::query()->find($payment->customer_id);
                if ($customer) {
                    $customer->amount_balance += $payment->type === 'refund' ? -$payment->amount : $payment->amount;
                    $customer->save();
                }
                $this->reverseAccountingEntry($payment);
            }
        });

        return response()->json(['success' => true]);
    }

    public function delete($id)
    {
        /** @var PaymentIn $payment */
        $payment = PaymentIn::query()->findOrFail($id);
        
        DB::transaction(function () use ($payment) {
            // Reverse Customer Balance
            /** @var Customer|null $customer */
            $customer = Customer::query()->find($payment->customer_id);
            if ($customer) {
                $customer->amount_balance += $payment->type === 'refund' ? -$payment->amount : $payment->amount;
                $customer->save();
            }

            // Reverse Accounting
            $this->reverseAccountingEntry($payment);

            $payment->delete();
        });

        return redirect()->back()->with('success', 'Payment deleted and balances reversed.');
    }

    private function reverseAccountingEntry($payment)
    {
        /** @var JournalEntry|null $entry */
        $entry = JournalEntry::query()->where('reference', $payment->receipt_no)->first();
        if ($entry) {
            // Balance reversals are automatically handled by JournalItem's deleted observer
            foreach ($entry->items as $item) {
                /** @var JournalItem $item */
                $item->delete();
            }
            $entry->delete();
        }
    }
}
