<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\ProductStock;
use App\Models\SalesOrder;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesReturnController extends Controller
{
    public function viewSalesReturn(Request $request)
    {
        $query = SalesReturn::query()->with('customer', 'invoice', 'items.product')->latest();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('credit_note_no', 'like', '%' . $request->search . '%')
                  ->orWhereHas('customer', fn($s) => $s->where('name', 'like', '%' . $request->search . '%'));
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $returns   = $query->paginate(10)->withQueryString();
        $customers = Customer::query()->orderBy('name')->get();
        $invoices  = SalesOrder::query()->whereIn('status', ['completed', 'partial'])
            ->with(['items.product', 'items.returnItems'])
            ->latest()->get();

        $stats = [
            'total_returns'  => SalesReturn::query()->whereMonth('return_date', now()->month)->count(),
            'return_value'   => SalesReturn::query()->whereMonth('return_date', now()->month)->sum('amount'),
            'credit_notes'   => SalesReturn::query()->where('status', 'approved')->count(),
            'pending'        => SalesReturn::query()->where('status', 'pending')->count(),
        ];

        return view('frontend.sales.sales_return_credit_note', compact('returns', 'customers', 'invoices', 'stats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id'  => 'required|exists:customers,id',
            'invoice_id'   => 'nullable|exists:sales_orders,id',
            'reason'       => 'required|string|max:255',
            'return_date'  => 'required|date',
            'notes'        => 'nullable|string',
            'items'                  => 'nullable|array|min:1',
            'items.*.order_item_id'  => 'nullable|exists:sales_order_items,id',
            'items.*.product_id'     => 'required_with:items|exists:products,id',
            'items.*.quantity'       => 'required_with:items|numeric|min:0.01',
            'items.*.unit_price'     => 'required_with:items|numeric|min:0',
            // Only required when no items are selected (e.g. a goodwill
            // credit not tied to specific returned goods) — when items are
            // present the amount is derived from them instead.
            'amount'       => 'required_without:items|nullable|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($request) {
            $companyId = auth()->user()->company_id;
            $items = $request->items ?? [];

            // Restocking needs to know which branch the goods are going back
            // to — only knowable when the return is tied to the original
            // invoice. A return with no invoice (e.g. a goodwill credit) is
            // financial-only: no items, no stock impact.
            $branchId = null;
            $outstandingDue = 0;
            if ($request->invoice_id) {
                $invoiceOrder = SalesOrder::query()->find($request->invoice_id);
                $branchId = $invoiceOrder?->branch_id;
                $outstandingDue = (float) ($invoiceOrder?->due_amount ?? 0);
            }

            $amount = count($items) > 0
                ? collect($items)->sum(fn(array $i) => $i['quantity'] * $i['unit_price'])
                : (float) $request->amount;

            $count = SalesReturn::query()->count() + 1;
            $creditNoteNo = 'CN-' . date('Y') . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);

            $return = SalesReturn::query()->create([
                'company_id'     => $companyId,
                'credit_note_no' => $creditNoteNo,
                'customer_id'    => $request->customer_id,
                'invoice_id'     => $request->invoice_id ?: null,
                'branch_id'      => $branchId,
                'reason'         => $request->reason,
                'return_date'    => $request->return_date,
                'amount'         => $amount,
                'notes'          => $request->notes,
                'status'         => 'approved',
                'created_by'     => Auth::id(),
            ]);

            // Increase customer balance (they are owed this amount)
            $customer = Customer::query()->find($request->customer_id);
            if ($customer) {
                $customer->amount_balance = ($customer->amount_balance ?? 0) - $amount;
                $customer->save();
            }

            // Journal Entry: Dr Sales Revenue / Cr Accounts Receivable
            // (capped at what was actually still owed on the invoice — a
            // refund payable for the rest, see createReturnJournalEntry),
            // plus Dr Inventory / Cr COGS for any returned items — the
            // exact reversal of what the original sale posted, so a
            // restocked return puts the goods' value back on the books
            // instead of just bumping the physical quantity.
            $this->createReturnJournalEntry($return, $companyId, $branchId ? $items : [], $outstandingDue);

            // Restock returned goods at the branch they were originally sold from
            if ($branchId) {
                foreach ($items as $itemData) {
                    $returnItem = SalesReturnItem::query()->create([
                        'sales_return_id'     => $return->id,
                        'sales_order_item_id' => $itemData['order_item_id'] ?? null,
                        'product_id'          => $itemData['product_id'],
                        'quantity'            => $itemData['quantity'],
                        'unit_price'          => $itemData['unit_price'],
                        'subtotal'            => $itemData['quantity'] * $itemData['unit_price'],
                    ]);

                    $stock = ProductStock::query()->firstOrNew([
                        'product_id' => $itemData['product_id'],
                        'branch_id'  => $branchId,
                    ]);
                    $stock->quantity = ($stock->exists ? $stock->quantity : 0) + $itemData['quantity'];
                    $stock->save();

                    StockMovement::query()->create([
                        'product_id'     => $itemData['product_id'],
                        'branch_id'      => $branchId,
                        'quantity'       => $itemData['quantity'],
                        'type'           => 'sales_return',
                        'reference_id'   => $returnItem->id,
                        'reference_type' => SalesReturnItem::class,
                        'balance_after'  => $stock->quantity,
                        'created_by'     => Auth::id(),
                    ]);
                }
            }
        });

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Credit note issued successfully.']);
        }

        return redirect()->back()->with('success', 'Credit note issued successfully.');
    }

    public function destroy($id)
    {
        /** @var SalesReturn $return */
        $return = SalesReturn::query()->with('items')->findOrFail($id);

        DB::transaction(function () use ($return) {
            // Reverse customer balance
            $customer = Customer::query()->find($return->customer_id);
            if ($customer) {
                $customer->amount_balance = ($customer->amount_balance ?? 0) + $return->amount;
                $customer->save();
            }

            // Reverse journal entry
            $entry = JournalEntry::query()->where('reference', $return->credit_note_no)->first();
            if ($entry) {
                foreach ($entry->items as $item) {
                    $item->delete();
                }
                $entry->delete();
            }

            // Reverse stock: goods that were put back on the shelf when this
            // return was created have to come back out again on delete.
            if ($return->branch_id) {
                foreach ($return->items as $item) {
                    $stock = ProductStock::query()->where('product_id', $item->product_id)
                        ->where('branch_id', $return->branch_id)
                        ->first();
                    if ($stock) {
                        $stock->decrement('quantity', $item->quantity);
                    }
                }
            }

            $return->delete();
        });

        return redirect()->back()->with('success', 'Credit note reversed and deleted.');
    }

    private function createReturnJournalEntry(SalesReturn $return, $companyId, array $items = [], float $outstandingDue = 0)
    {
        // Dr Sales Revenue (reverses earned revenue)
        $revenueAccount = Account::query()->where('company_id', $companyId)->where('code', '4110')->first()
                       ?: Account::query()->where('company_id', $companyId)->where('name', 'like', '%Revenue%')->first();

        // Cr Accounts Receivable — but only up to what was actually still
        // owed on the invoice. A fully-paid invoice has no AR balance to
        // reduce, so crediting the whole return amount there would just
        // push Accounts Receivable negative. Anything beyond the
        // outstanding due is money the business now owes the customer
        // back, which belongs in a refund liability instead.
        $receivableAccount = Account::query()->where('company_id', $companyId)->where('code', '1140')->first()
                          ?: Account::query()->where('company_id', $companyId)->where('name', 'like', '%Receivable%')->first();

        if (!$revenueAccount || !$receivableAccount) {
            return;
        }

        $toReceivable = min($return->amount, max($outstandingDue, 0));
        $toRefundPayable = $return->amount - $toReceivable;

        $refundPayableAccount = null;
        if ($toRefundPayable > 0) {
            $refundPayableAccount = Account::query()->where('company_id', $companyId)->where('code', '2160')->first()
                                 ?: Account::query()->where('company_id', $companyId)->where('name', 'Customer Refunds Payable')->first()
                                 ?: Account::query()->create([
                                        'company_id' => $companyId,
                                        'code'       => '2160',
                                        'name'       => 'Customer Refunds Payable',
                                        'category'   => 'liabilities',
                                        'type'       => 'current_liability',
                                        'balance'    => 0,
                                    ]);
        }

        // Mirror SalesController's COGS posting (code 1150 Inventory / 5110
        // Product Cost), but reversed: the goods are physically back in
        // stock, so debit Inventory and credit COGS for the same amount
        // the original sale recorded.
        $inventoryAccount = Account::query()->where('company_id', $companyId)->where('code', '1150')->first()
                         ?: Account::query()->where('company_id', $companyId)->where('name', 'like', '%Inventory%')->first();
        $cogsAccount = Account::query()->where('company_id', $companyId)->where('code', '5110')->first()
                    ?: Account::query()->where('company_id', $companyId)->where('code', '5100')->first()
                    ?: Account::query()->where('company_id', $companyId)->where('category', 'expenses')->where('name', 'like', '%Cost%')->where('type', '!=', 'parent')->first();

        $inventoryReversal = 0;
        if ($inventoryAccount && $cogsAccount) {
            foreach ($items as $itemData) {
                $product = \App\Models\Product::query()->find($itemData['product_id']);
                $purchasePrice = (float) ($product->purchase_price ?? 0);
                if ($purchasePrice <= 0) continue;
                $inventoryReversal += $itemData['quantity'] * $purchasePrice;
            }
        }

        $entry = JournalEntry::query()->create([
            'company_id'   => $companyId,
            'entry_number' => 'JE-CN-' . $return->id . '-' . strtoupper(substr(uniqid(), -5)),
            'date'         => $return->return_date,
            'reference'    => $return->credit_note_no,
            'description'  => 'Sales return / credit note for ' . ($return->customer->name ?? ''),
            'status'       => 'posted',
            'total_amount' => $return->amount,
            'created_by'   => Auth::id(),
        ]);

        // Dr Revenue (reduces revenue)
        JournalItem::query()->create([
            'company_id'       => $companyId,
            'journal_entry_id' => $entry->id,
            'account_id'       => $revenueAccount->id,
            'debit'            => $return->amount,
            'credit'           => 0,
            'description'      => 'Return: ' . $return->credit_note_no,
        ]);

        // Cr Receivable (reduces AR balance, capped at what was actually due)
        if ($toReceivable > 0) {
            JournalItem::query()->create([
                'company_id'       => $companyId,
                'journal_entry_id' => $entry->id,
                'account_id'       => $receivableAccount->id,
                'debit'            => 0,
                'credit'           => $toReceivable,
                'description'      => 'Return: ' . $return->credit_note_no,
            ]);
        }

        // Cr Customer Refunds Payable (money owed back on an already-paid invoice)
        if ($toRefundPayable > 0 && $refundPayableAccount) {
            JournalItem::query()->create([
                'company_id'       => $companyId,
                'journal_entry_id' => $entry->id,
                'account_id'       => $refundPayableAccount->id,
                'debit'            => 0,
                'credit'           => $toRefundPayable,
                'description'      => 'Refund owed for: ' . $return->credit_note_no,
            ]);
        }

        if ($inventoryReversal > 0) {
            // Dr Inventory (goods are back in stock)
            JournalItem::query()->create([
                'company_id'       => $companyId,
                'journal_entry_id' => $entry->id,
                'account_id'       => $inventoryAccount->id,
                'debit'            => $inventoryReversal,
                'credit'           => 0,
                'description'      => 'Inventory restored from return: ' . $return->credit_note_no,
            ]);

            // Cr COGS (reverses the cost recorded at sale time)
            JournalItem::query()->create([
                'company_id'       => $companyId,
                'journal_entry_id' => $entry->id,
                'account_id'       => $cogsAccount->id,
                'debit'            => 0,
                'credit'           => $inventoryReversal,
                'description'      => 'Cost of Goods Sold reversal for return: ' . $return->credit_note_no,
            ]);
        }
    }
}
