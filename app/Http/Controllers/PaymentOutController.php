<?php

namespace App\Http\Controllers;

use App\Models\SupplierPayment;
use App\Models\SupplierPaymentDetail;
use App\Models\Supplier;
use App\Models\Company;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentOutController extends Controller
{
    public function index(Request $request)
    {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = SupplierPayment::query()->with('supplier', 'creator', 'bankAccount');
        
        if ($request->search) {
            $search = $request->search;
            /** @disregard P0406 */
            $query->where(function(\Illuminate\Database\Eloquent\Builder $q) use ($search) {
                $q->where('voucher_no', 'like', '%' . $search . '%')
                  ->orWhere('reference', 'like', '%' . $search . '%')
                  ->orWhereHas('supplier', function(\Illuminate\Database\Eloquent\Builder $sub) use ($search) {
                      $sub->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        if ($request->vendor_id) {
            $query->where('supplier_id', $request->vendor_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->input('method')) {
            $query->where('payment_method', $request->input('method'));
        }

        if ($request->date) {
            $query->whereDate('payment_date', $request->date);
        }

        $payments = $query->latest()->get();
        
        $todayPayments = SupplierPayment::query()->whereDate('payment_date', now())->sum('amount');
        $monthPayments = SupplierPayment::query()->whereMonth('payment_date', now()->month)->sum('amount');
        $pendingPayments = SupplierPayment::query()->where('status', 'pending')->sum('amount');
        $totalTransactions = SupplierPayment::query()->whereMonth('payment_date', now()->month)->count();

        $suppliers = Supplier::query()->orderBy('name')->get();
        /** @var Company|null $company */
        $company = Company::find(auth()->user()->company_id);
        $bankAccounts = Account::query()
            ->where('type', 'cash')
            ->where(fn($q) => $q->where('name', 'like', '%Cash on Hand%')->orWhere('name', 'like', '%Cash in Hand%'))
            ->where('is_active', 1)
            ->get();

        $suggestedVoucherNo = 'PV-' . date('Ymd') . '-' . str_pad(SupplierPayment::query()->count() + 1, 4, '0', STR_PAD_LEFT);

        return view('frontend.purchase.supplier_payment', compact(
            'payments', 'todayPayments', 'monthPayments', 'pendingPayments', 
            'totalTransactions', 'suppliers', 'company', 'suggestedVoucherNo', 'bankAccounts'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|exists:suppliers,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'bank_account_id' => 'required|exists:chart_of_accounts,id',
        ]);

        DB::transaction(function () use ($request) {
            $count = SupplierPayment::query()->count() + 1;
            $voucherNo = 'PV-' . date('Y') . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);

            // Fetch the bank account to get the payment method (Bank or Cash)
            /** @var Account|null $bankAccount */
            $bankAccount = Account::query()->find($request->bank_account_id);
            $method = (isset($bankAccount->type) && $bankAccount->type == 'cash') ? 'Cash' : 'Bank Transfer';

            $payment = SupplierPayment::query()->create([
                'voucher_no' => $voucherNo,
                'supplier_id' => $request->vendor_id,
                'bank_account_id' => $request->bank_account_id,
                'reference' => $request->reference,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'payment_method' => $method,
                'notes' => $request->notes,
                'status' => 'completed',
                'created_by' => Auth::id(),
            ]);

            // Update Supplier Balance
            /** @var Supplier|null $supplier */
            $supplier = Supplier::query()->find($request->vendor_id);
            if ($supplier) {
                $supplier->amount_balance -= $request->amount;
                $supplier->save();
            }

            // Accounting - Journal Entry
            $this->createAccountingEntry($payment);
        });

        return redirect()->back()->with('success', 'Payment processed successfully.');
    }

    private function createAccountingEntry($payment)
    {
        $cid = Auth::user()->company_id;

        // 1. Find Asset Account from the selected bank_account_id
        /** @var Account|null $assetAccount */
        $assetAccount = Account::query()->find($payment->bank_account_id);

        if (!$assetAccount) {
            $method = strtolower($payment->payment_method ?? '');
            if (str_contains($method, 'cash')) {
                $assetAccount = Account::query()->where('company_id', $cid)->where('code', '1010')->first()
                             ?: Account::query()->where('company_id', $cid)->where('name', 'like', '%Cash%')->first();
            } else {
                $assetAccount = Account::query()->where('company_id', $cid)->where('type', 'bank')->first()
                             ?: Account::query()->where('company_id', $cid)->where('name', 'like', '%Bank%')->first();
            }
        }

        // 2. Find Payable Account — use supplier's own GL account, else code 2110 (Accounts Payable)
        $payableAccount = ($payment->supplier && $payment->supplier->account_id)
            ? Account::query()->find($payment->supplier->account_id)
            : (Account::query()->where('company_id', $cid)->where('code', '2110')->first()
               ?: Account::query()->where('company_id', $cid)->where('name', 'like', '%Accounts Payable%')->first());

        // 3. Create Journal Entry
        if ($assetAccount && $payableAccount) {
            /** @var JournalEntry $entry */
            $entry = JournalEntry::query()->create([
                'entry_number' => 'VOUCH-' . date('Ymd') . '-' . str_pad($payment->id, 5, '0', STR_PAD_LEFT),
                'date'         => $payment->payment_date,
                'reference'    => $payment->voucher_no,
                'description'  => 'Payment to ' . ($payment->supplier->name ?? 'Supplier'),
                'status'       => 'posted',
                'total_amount' => $payment->amount,
                'company_id'   => $cid,
                'created_by'   => Auth::id(),
            ]);

            JournalItem::query()->create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $payableAccount->id,
                'company_id'       => $cid,
                'debit'            => $payment->amount,
                'credit'           => 0,
                'description'      => 'Voucher ' . $payment->voucher_no,
            ]);

            JournalItem::query()->create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $assetAccount->id,
                'company_id'       => $cid,
                'debit'            => 0,
                'credit'           => $payment->amount,
                'description'      => 'Voucher ' . $payment->voucher_no,
            ]);
        } else {
            throw new \RuntimeException("Accounting failed for PaymentOut {$payment->id}: Asset or Payable account missing.");
        }
    }

    public function view($id)
    {
        /** @var SupplierPayment $payment */
        $payment = SupplierPayment::query()->with('supplier', 'creator')->findOrFail($id);
        /** @var Company|null $company */
        $company = Company::find(auth()->user()->company_id);
        return view('frontend.purchase.pdf_payment_voucher', compact('payment', 'company'));
    }

    public function download($id)
    {
        /** @var SupplierPayment $payment */
        $payment = SupplierPayment::query()->with('supplier', 'creator')->findOrFail($id);
        /** @var Company|null $company */
        $company = Company::find(auth()->user()->company_id);
        
        $pdf = Pdf::loadView('frontend.purchase.pdf_payment_voucher', compact('payment', 'company'));
        return $pdf->download('Voucher_' . $payment->voucher_no . '.pdf');
    }

    public function updateStatus(Request $request, $id)
    {
        /** @var SupplierPayment $payment */
        $payment = SupplierPayment::query()->with('supplier')->findOrFail($id);

        $wasCompleted = $payment->status === 'completed';
        $newStatus    = $request->status;
        $becomingVoid = in_array($newStatus, ['void', 'cancelled', 'failed']);

        DB::transaction(function () use ($payment, $newStatus, $wasCompleted, $becomingVoid) {
            if ($wasCompleted && $becomingVoid) {
                // Restore supplier balance
                if ($payment->supplier) {
                    $payment->supplier->increment('amount_balance', $payment->amount);
                }
                // Reverse GL entries
                $this->reverseAccountingEntry($payment);
            }

            $payment->status = $newStatus;
            $payment->save();
        });

        return response()->json(['success' => true]);
    }

    public function delete($id)
    {
        /** @var SupplierPayment $payment */
        $payment = SupplierPayment::query()->findOrFail($id);
        
        DB::transaction(function () use ($payment) {
            // Reverse Supplier Balance
            /** @var Supplier|null $supplier */
            $supplier = Supplier::query()->find($payment->supplier_id);
            if ($supplier) {
                $supplier->amount_balance += $payment->amount;
                $supplier->save();
            }

            // Reverse Accounting
            $this->reverseAccountingEntry($payment);

            $payment->delete();
        });

        return redirect()->back()->with('success', 'Payment voucher deleted and balances reversed.');
    }

    private function reverseAccountingEntry($payment)
    {
        /** @var JournalEntry|null $entry */
        $entry = JournalEntry::query()->where('reference', $payment->voucher_no)->first();
        if ($entry) {
            // JournalItems will be deleted via cascading or manual deletion,
            // and their boot method will handle reversing the Account balances.
            foreach($entry->items as $item) {
                /** @var JournalItem $item */
                $item->delete();
            }
            $entry->delete();
        }
    }
}

