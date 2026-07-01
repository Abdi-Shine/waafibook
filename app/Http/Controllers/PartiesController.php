<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Supplier;
use App\Models\SalesOrder;
use App\Models\PaymentIn;
use App\Models\SalesReturn;
use App\Models\PurchaseBill;
use App\Models\SupplierPayment;
use App\Models\PurchaseReturn;
use App\Models\JournalEntry;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PartiesController extends Controller
{
    public function ledgerView(Request $request)
    {
        $parties = $this->allParties();

        $selectedType = $request->input('type', $parties->first()['type'] ?? null);
        $selectedId   = $request->input('id', $parties->first()['id'] ?? null);

        $ledger = ($selectedType && $selectedId) ? $this->buildPartyLedger($selectedType, $selectedId) : null;

        return view('frontend.parties.ledger', compact('parties', 'selectedType', 'selectedId', 'ledger'));
    }

    public function ledgerData($type, $id)
    {
        return response()->json($this->buildPartyLedger($type, $id));
    }

    // Deletes a party's Opening Balance entry from the ledger: removes the
    // journal entry created when that opening balance was set, and backs
    // the amount it represented out of the party's current balance. Like
    // Product's Opening Stock, there's no separate model for this — it's
    // just the journal entry posted alongside the customer/supplier.
    public function deleteOpeningBalance($type, $id)
    {
        $isSupplier = $type === 'supplier';
        /** @var Customer|Supplier $party */
        $party = $isSupplier ? Supplier::query()->findOrFail($id) : Customer::query()->findOrFail($id);
        $code  = $isSupplier ? $party->supplier_code : $party->customer_code;

        $entry = JournalEntry::query()
            ->where('reference', $code)
            ->where('company_id', $party->company_id)
            ->where('description', 'like', 'Opening balance for%')
            ->first();

        if (!$entry) {
            return response()->json(['message' => 'No opening balance transaction found for this party.'], 404);
        }

        $ledger = $this->buildPartyLedger($type, $id);
        $openingTxn = $ledger['transactions']->firstWhere('type', 'Opening Balance');
        $openingAmount = $openingTxn['balance'] ?? 0;

        DB::transaction(function () use ($entry, $party, $openingAmount) {
            $entry->items()->delete();
            $entry->delete();

            if ($openingAmount != 0) {
                $party->amount_balance -= $openingAmount;
                $party->save();
            }
        });

        AuditLog::log('Parties', "Deleted opening balance for: {$party->name}", 'DELETE', 'warning');

        return redirect()->back()->with('success', 'Opening balance transaction deleted successfully.');
    }

    private function allParties()
    {
        $customers = Customer::query()->orderBy('name')->get(['id', 'name', 'amount_balance'])
            ->map(fn($c) => ['type' => 'customer', 'id' => $c->id, 'name' => $c->name, 'amount' => (float) $c->amount_balance]);

        $suppliers = Supplier::query()->orderBy('name')->get(['id', 'name', 'amount_balance'])
            ->map(fn($s) => ['type' => 'supplier', 'id' => $s->id, 'name' => $s->name, 'amount' => (float) $s->amount_balance]);

        return $customers->concat($suppliers)->sortBy('name')->values();
    }

    private function buildPartyLedger($type, $id)
    {
        return $type === 'supplier' ? $this->buildSupplierLedger($id) : $this->buildCustomerLedger($id);
    }

    private function buildCustomerLedger($id)
    {
        /** @var Customer $customer */
        $customer = Customer::query()->findOrFail($id);
        $statusLabels = ['completed' => 'Paid', 'partial' => 'Partial', 'pending' => 'Unpaid'];

        $sales = SalesOrder::query()->where('customer_id', $id)->get()->map(fn($o) => [
            'id'         => $o->id,
            'type'       => 'Sale',
            'type_color' => 'bg-emerald-500',
            'number'     => $o->invoice_no,
            'date'       => $o->invoice_date ? $o->invoice_date->format('d/m/Y') : '-',
            'sort_date'  => $o->invoice_date ? $o->invoice_date->timestamp : 0,
            'total'      => (float) $o->total_amount,
            'balance'    => (float) $o->due_amount,
            'status'     => $statusLabels[$o->status] ?? ucfirst($o->status),
        ]);

        $payments = PaymentIn::query()->where('customer_id', $id)->get()->map(function($p) {
            $date = $p->payment_date ? Carbon::parse($p->payment_date) : null;
            return [
                'id'         => $p->id,
                'type'       => 'Payment',
                'type_color' => 'bg-blue-500',
                'number'     => $p->receipt_no,
                'date'       => $date ? $date->format('d/m/Y') : '-',
                'sort_date'  => $date ? $date->timestamp : 0,
                'total'      => (float) $p->amount,
                'balance'    => 0,
                'status'     => ucfirst($p->status),
            ];
        });

        $returns = SalesReturn::query()->where('customer_id', $id)->get()->map(function($r) {
            $date = $r->return_date ? Carbon::parse($r->return_date) : null;
            return [
                'id'         => $r->id,
                'type'       => 'Credit Note',
                'type_color' => 'bg-orange-500',
                'number'     => $r->credit_note_no,
                'date'       => $date ? $date->format('d/m/Y') : '-',
                'sort_date'  => $date ? $date->timestamp : 0,
                'total'      => (float) $r->amount,
                'balance'    => (float) $r->amount,
                'status'     => ucfirst($r->status),
            ];
        });

        $openingBalance = (float) $customer->amount_balance
            - $sales->sum('total') + $payments->sum('total') + $returns->sum('total');

        $opening = collect();
        if (abs($openingBalance) > 0.01) {
            $opening->push([
                'type'       => 'Opening Balance',
                'type_color' => 'bg-gray-800',
                'number'     => null,
                'date'       => $customer->created_at ? $customer->created_at->format('d/m/Y') : '-',
                'sort_date'  => $customer->created_at ? $customer->created_at->timestamp : 0,
                'total'      => abs($openingBalance),
                'balance'    => $openingBalance,
                'status'     => $openingBalance > 0 ? 'Unpaid' : 'Paid',
            ]);
        }

        $transactions = $sales->concat($payments)->concat($returns)->concat($opening)->sortByDesc('sort_date')->values();

        return [
            'party' => [
                'type'         => 'customer',
                'id'           => $customer->id,
                'name'         => $customer->name,
                'phone'        => $customer->phone,
                'amount'       => (float) $customer->amount_balance,
                'credit_limit' => $customer->credit_limit ? (float) $customer->credit_limit : null,
            ],
            'transactions' => $transactions,
        ];
    }

    private function buildSupplierLedger($id)
    {
        /** @var Supplier $supplier */
        $supplier = Supplier::query()->findOrFail($id);
        $statusLabels = ['paid' => 'Paid', 'partial' => 'Partial', 'pending' => 'Unpaid', 'draft' => 'Draft'];

        $purchases = PurchaseBill::query()->where('supplier_id', $id)->get()->map(function($b) use ($statusLabels) {
            $date = $b->bill_date ? Carbon::parse($b->bill_date) : null;
            return [
                'id'         => $b->id,
                'type'       => 'Purchase',
                'type_color' => 'bg-emerald-500',
                'number'     => $b->bill_number,
                'date'       => $date ? $date->format('d/m/Y') : '-',
                'sort_date'  => $date ? $date->timestamp : 0,
                'total'      => (float) $b->total_amount,
                'balance'    => (float) $b->balance_amount,
                'status'     => $statusLabels[$b->status] ?? ucfirst($b->status),
            ];
        });

        $payments = SupplierPayment::query()->where('supplier_id', $id)->get()->map(function($p) {
            $date = $p->payment_date ? Carbon::parse($p->payment_date) : null;
            return [
                'id'         => $p->id,
                'type'       => 'Payment',
                'type_color' => 'bg-blue-500',
                'number'     => $p->voucher_no,
                'date'       => $date ? $date->format('d/m/Y') : '-',
                'sort_date'  => $date ? $date->timestamp : 0,
                'total'      => (float) $p->amount,
                'balance'    => 0,
                'status'     => ucfirst($p->status),
            ];
        });

        $returns = PurchaseReturn::query()->where('supplier_id', $id)->get()->map(function($r) {
            $date = $r->return_date ? Carbon::parse($r->return_date) : null;
            return [
                'id'         => $r->id,
                'type'       => 'Debit Note',
                'type_color' => 'bg-orange-500',
                'number'     => $r->return_number,
                'date'       => $date ? $date->format('d/m/Y') : '-',
                'sort_date'  => $date ? $date->timestamp : 0,
                'total'      => (float) $r->total_amount,
                'balance'    => (float) $r->total_amount,
                'status'     => ucfirst($r->status),
            ];
        });

        $openingBalance = (float) $supplier->amount_balance
            - $purchases->sum('total') + $payments->sum('total') + $returns->sum('total');

        $opening = collect();
        if (abs($openingBalance) > 0.01) {
            $opening->push([
                'type'       => 'Opening Balance',
                'type_color' => 'bg-gray-800',
                'number'     => null,
                'date'       => $supplier->created_at ? $supplier->created_at->format('d/m/Y') : '-',
                'sort_date'  => $supplier->created_at ? $supplier->created_at->timestamp : 0,
                'total'      => abs($openingBalance),
                'balance'    => $openingBalance,
                'status'     => $openingBalance > 0 ? 'Unpaid' : 'Paid',
            ]);
        }

        $transactions = $purchases->concat($payments)->concat($returns)->concat($opening)->sortByDesc('sort_date')->values();

        return [
            'party' => [
                'type'         => 'supplier',
                'id'           => $supplier->id,
                'name'         => $supplier->name,
                'phone'        => $supplier->phone,
                'amount'       => (float) $supplier->amount_balance,
                'credit_limit' => null,
            ],
            'transactions' => $transactions,
        ];
    }
}
