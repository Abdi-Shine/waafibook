<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Category;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\ProductStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class SalesController extends Controller
{
    // ─── INDEX ───────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = SalesOrder::query()->with('customer', 'branch');
        $userBranchId = Auth::user()->getAssignedBranchId();

        if ($userBranchId) {
            $query->where('branch_id', $userBranchId);
        }

        if ($request->search) {
            $search = $request->search;
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            /** @disregard P0406 */
            $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn(\Illuminate\Database\Eloquent\Builder $s) => $s->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->date_from) {
            $query->whereDate('invoice_date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('invoice_date', '<=', $request->date_to);
        }

        $orders = $query->latest()->get();

        // Stats — always from the full dataset, not the filtered $query
        // Stats filtered by branch if not admin
        $totalInvoices    = SalesOrder::query()->when($userBranchId, fn(\Illuminate\Database\Eloquent\Builder $q) => $q->where('branch_id', $userBranchId))->count();
        $totalRevenue     = SalesOrder::query()->when($userBranchId, fn($q) => $q->where('branch_id', $userBranchId))->sum('paid_amount');
        $totalOutstanding = SalesOrder::query()->whereIn('status', ['pending', 'partial'])
                                        ->when($userBranchId, fn(\Illuminate\Database\Eloquent\Builder $q) => $q->where('branch_id', $userBranchId))
                                        ->sum('due_amount');
        $todaySales       = SalesOrder::query()->whereDate('invoice_date', now())
                                        ->when($userBranchId, fn(\Illuminate\Database\Eloquent\Builder $q) => $q->where('branch_id', $userBranchId))
                                        ->sum('total_amount');

        $customers = Customer::query()->orderBy('name')->get();
        $branches  = Branch::query()->when($userBranchId, fn(\Illuminate\Database\Eloquent\Builder $q) => $q->where('id', $userBranchId))->orderBy('name')->get();
        /** @var Company|null $company */
        $company   = Company::find(auth()->user()->company_id);

        $isMobile = (bool) preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|Windows Phone/i', $request->userAgent() ?? '')
            || $request->header('Sec-CH-UA-Mobile') === '?1'
            || $request->boolean('mobile');

        if ($isMobile) {
            return view('frontend.sales.sales_invoice_pwa', compact(
                'orders', 'totalInvoices', 'totalRevenue', 'totalOutstanding',
                'todaySales', 'customers', 'branches', 'company'
            ));
        }

        return view('frontend.sales.sales_invoice', compact(
            'orders', 'totalInvoices', 'totalRevenue', 'totalOutstanding',
            'todaySales', 'customers', 'branches', 'company'
        ));
    }

    // ─── CREATE ──────────────────────────────────────────────────────────────

    public function create(Request $request)
    {
        $userBranchId = Auth::user()->getAssignedBranchId();
        $customers  = Customer::query()->orderBy('name')->get();
        
        $productsQuery = Product::query()->with('category')
                               ->orderBy('product_name');

        if ($userBranchId) {
            $productsQuery->whereHas('stocks', function(\Illuminate\Database\Eloquent\Builder $q) use ($userBranchId) {
                $q->where('branch_id', $userBranchId);
            });
        }

        $products = $productsQuery->withSum(['stocks' => function(\Illuminate\Database\Eloquent\Builder $q) use ($userBranchId) {
            if ($userBranchId) $q->where('branch_id', $userBranchId);
        }], 'quantity')->get();
        $categories = Category::query()->orderBy('name')->get();
        $branches   = Branch::query()->when($userBranchId, fn(\Illuminate\Database\Eloquent\Builder $q) => $q->where('id', $userBranchId))->orderBy('name')->get();
        /** @var Company|null $company */
        $company    = Company::find(auth()->user()->company_id);

        $invoiceNo = $this->nextInvoiceNumber();

        $accounts = Account::query()
            ->whereIn('type', ['cash', 'bank'])
            ->when($userBranchId, fn($q) => $q->where('branch_id', $userBranchId))
            ->orderBy('name')
            ->get();

        $isMobile = (bool) preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|Windows Phone/i', $request->userAgent() ?? '')
            || $request->header('Sec-CH-UA-Mobile') === '?1'
            || $request->boolean('mobile');

        if ($isMobile) {
            return view('frontend.sales.add_invoice_sales_pwa', compact(
                'customers', 'products', 'categories', 'branches', 'company', 'invoiceNo', 'accounts'
            ));
        }

        return view('frontend.sales.add_invoice_sales', compact(
            'customers', 'products', 'categories', 'branches', 'company', 'invoiceNo', 'accounts'
        ));
    }

    public function pos()
    {
        $userBranchId = Auth::user()->getAssignedBranchId();
        $customers  = Customer::query()->orderBy('name')->get();
        
        $productsQuery = Product::query()->with('category')
                               ->orderBy('product_name');

        if ($userBranchId) {
            $productsQuery->whereHas('stocks', function(\Illuminate\Database\Eloquent\Builder $q) use ($userBranchId) {
                $q->where('branch_id', $userBranchId);
            });
        }

        $products = $productsQuery->withSum(['stocks' => function(\Illuminate\Database\Eloquent\Builder $q) use ($userBranchId) {
            if ($userBranchId) $q->where('branch_id', $userBranchId);
        }], 'quantity')->get();
        
        $categories = Category::query()->orderBy('name')->get();
        $branches   = Branch::query()->when($userBranchId, fn($q) => $q->where('id', $userBranchId))->orderBy('name')->get();
        /** @var Company|null $company */
        $company    = Company::find(auth()->user()->company_id);

        $invoiceNo = $this->nextInvoiceNumber();

        $accounts = Account::query()
            ->where(function($q) {
                $q->where('type', 'cash')
                  ->orWhere('name', 'like', '%Cash%');
            })
            ->where('type', '!=', 'parent')
            ->where('name', 'not like', '%Petty%')
            ->when($userBranchId, fn($q) => $q->where('branch_id', $userBranchId))
            ->orderByRaw("CASE WHEN LOWER(name) LIKE '%cash on hand%' OR LOWER(name) LIKE '%cash in hand%' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get();

        $defaultPaymentAccountId = $accounts->first()->id ?? null;

        return view('frontend.sales.sales_pos', compact(
            'customers', 'products', 'categories', 'branches', 'company', 'invoiceNo', 'accounts', 'defaultPaymentAccountId'
        ));
    }

    // ─── QUICK STORE CUSTOMER (AJAX) ─────────────────────────────────────────

    public function quickStoreCustomer(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'required|string|max:20',
        ]);

        /** @var Account|null $account */
        $account = Account::query()->where('name', 'like', '%Receivable%')->first();
        $count   = Customer::query()->count() + 1;

        $customer = Customer::query()->create([
            'name'          => $request->name,
            'phone'         => $request->phone,
            'email'         => $request->email ?? null,
            'customer_type' => 'individual',
            'amount_balance'=> $request->amount_balance ?? 0,
            'account_id'    => $account?->id,
            'account_type'  => $account?->type,
            'account_code'  => $account?->code,
            'customer_code' => 'CUS-' . date('Y') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT),
        ]);

        if ($account && ($request->amount_balance ?? 0) > 0) {
            // Opening Balance logic could go here as a Journal Entry if needed,
            // but for now we just rely on the manual update if no JE is created.
            // However, it's better to keep it consistent.
            $account->balance += $request->amount_balance;
            $account->save();
        }

        return response()->json([
            'id'      => $customer->id,
            'name'    => $customer->name,
            'phone'   => $customer->phone,
            'balance' => $customer->amount_balance ?? 0,
        ]);
    }

    // ─── STORE ───────────────────────────────────────────────────────────────

    public function storeDraft(Request $request)
    {
        try {
            $subtotal  = 0;
            $items     = $request->items ?? [];
            foreach ($items as $item) {
                $subtotal += (($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0)) - ($item['discount'] ?? 0);
            }

            $total     = $subtotal - ($request->discount ?? 0) + ($request->tax ?? 0);
            $invoiceNo = 'DRAFT-' . date('YmdHis');

            $order = SalesOrder::query()->create([
                'invoice_no'         => $invoiceNo,
                'invoice_date'       => $request->invoice_date ?: now()->toDateString(),
                'due_date'           => $request->due_date ?: null,
                'customer_id'        => $request->customer_id ?: null,
                'branch_id'          => $request->branch_id ?: null,
                'subtotal'           => $subtotal,
                'discount'           => $request->discount ?? 0,
                'tax'                => $request->tax ?? 0,
                'total_amount'       => $total,
                'paid_amount'        => 0,
                'due_amount'         => $total,
                'payment_method'     => $request->payment_method ?? 'Cash',
                'payment_account_id' => $request->payment_account_id ?: null,
                'status'             => 'draft',
                'notes'              => $request->notes,
                'created_by'         => Auth::id(),
            ]);

            foreach ($items as $item) {
                if (empty($item['product_name']) && empty($item['quantity'])) continue;
                SalesOrderItem::query()->create([
                    'sales_order_id' => $order->id,
                    'product_id'     => isset($item['product_id']) && is_numeric($item['product_id']) ? $item['product_id'] : null,
                    'product_name'   => $item['product_name'] ?? 'Product',
                    'product_code'   => $item['product_code'] ?? null,
                    'unit_price'     => $item['unit_price'] ?? 0,
                    'quantity'       => $item['quantity'] ?? 0,
                    'discount'       => $item['discount'] ?? 0,
                    'total_amount'   => (($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0)) - ($item['discount'] ?? 0),
                ]);
            }

            return response()->json([
                'status'   => 'success',
                'message'  => 'Draft saved successfully.',
                'redirect' => route('sales.invoice.view'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    // count()+1 breaks the moment any sales order is ever deleted (the count
    // drops but the highest invoice_no already issued doesn't), producing a
    // number that collides with an existing invoice and crashing the whole
    // sale with a raw duplicate-key SQL error. Base it on the highest number
    // actually used instead, which survives gaps from deletions.
    private function nextInvoiceNumber(): string
    {
        $lastNumber = (int) SalesOrder::query()
            ->selectRaw("MAX(CAST(SUBSTRING(invoice_no, 5) AS UNSIGNED)) as n")
            ->value('n');

        return 'INV-' . str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
    }

    public function store(Request $request)
    {
        $cid = auth()->user()->company_id;

        $request->validate([
            'customer_id'            => 'nullable|exists:customers,id',
            'branch_id'              => ['required', \Illuminate\Validation\Rule::exists('branches', 'id')->where('company_id', $cid)],
            'invoice_date'           => 'required|date',
            'items'                  => 'required|array|min:1',
            'items.*.product_name'   => 'required|string',
            'items.*.quantity'       => 'required|numeric|min:0.01',
            'items.*.unit_price'     => 'required|numeric|min:0',
        ]);

        $orderId = DB::transaction(function () use ($request) {
            // Subtotal = sum of (qty × price) per line — per-item discounts already deducted in JS
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
            }

            // Invoice-level discount (separate from per-item discounts)
            $discount = $request->discount ?? 0;
            $tax      = $request->tax ?? 0;
            $total    = $subtotal - $discount + $tax;
            $paid     = $request->paid_amount ?? 0;
            $due      = max(0, $total - $paid);

            $status = 'pending';
            if ($paid >= $total) {
                $status = 'completed';
                $due    = 0;
            } elseif ($paid > 0) {
                $status = 'partial';
            }

            $invoiceNo = $this->nextInvoiceNumber();

            // Resolve payment account — JS sends correct ID for both cash and credit mode
            $paymentAccountId = $request->payment_account_id ?: null;

            $order = SalesOrder::query()->create([
                'invoice_no'         => $invoiceNo,
                'invoice_date'       => $request->invoice_date,
                'due_date'           => $request->due_date,
                'customer_id'        => $request->customer_id,
                'branch_id'          => $request->branch_id,
                'subtotal'           => $subtotal,
                'discount'           => $discount,
                'tax'                => $tax,
                'total_amount'       => $total,
                'paid_amount'        => $paid,
                'due_amount'         => $due,
                'payment_method'     => $request->payment_method ?? 'Cash',
                'payment_account_id' => $paymentAccountId,
                'status'             => $status,
                'notes'              => $request->notes,
                'created_by'         => Auth::id(),
            ]);

            foreach ($request->items as $item) {
                SalesOrderItem::query()->create([
                    'sales_order_id' => $order->id,
                    'product_id'     => $item['product_id'] ?? null,
                    'product_name'   => $item['product_name'],
                    'product_code'   => $item['product_code'] ?? null,
                    'unit_price'     => $item['unit_price'],
                    'quantity'       => $item['quantity'],
                    'unit'           => $item['unit'] ?? 'Piece',
                    'discount'       => $item['discount'] ?? 0,
                    'total_price'    => ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0),
                ]);

                // Reduce Stock from Branch — services aren't stocked, so skip entirely
                if (!empty($item['product_id']) && optional(Product::find($item['product_id']))->product_type !== 'service') {
                    $stock = ProductStock::query()->where('product_id', $item['product_id'])
                        ->where('branch_id', $order->branch_id)
                        ->first();

                    if ($stock) {
                        $stock->decrement('quantity', $item['quantity']);
                    } else {
                        // Create record if none exists for this branch/product
                        ProductStock::query()->create([
                            'product_id' => $item['product_id'],
                            'branch_id'  => $order->branch_id,
                            'quantity'   => -$item['quantity']
                        ]);
                    }
                }
            }

            // Update customer balance
            /** @var Customer|null $customer */
            $customer = Customer::query()->find($request->customer_id);
            if ($customer && $due > 0) {
                $customer->amount_balance = ($customer->amount_balance ?? 0) + $due;
                $customer->save();
            }

            // Accounting journal entry
            $this->createAccountingEntry($order);

            return $order->id;
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Invoice created successfully.',
                'redirect' => route('sales.invoice.view'),
                'order_id' => $orderId,
            ]);
        }

        return redirect()->route('sales.invoice.view')->with('success', 'Invoice created successfully.');
    }

    // ─── SHOW (Invoice Detail) ────────────────────────────────────────────────

    public function show($id)
    {
        /** @var SalesOrder|null $order */
        $order   = SalesOrder::query()->with('customer', 'items', 'creator')->findOrFail($id);
        /** @var Company|null $company */
        $company = Company::find(auth()->user()->company_id);

        return view('frontend.sales.sales_invoice_detail', compact('order', 'company'));
    }

    // ─── EDIT ────────────────────────────────────────────────────────────────

    public function edit($id)
    {
        $userBranchId = Auth::user()->getAssignedBranchId();
        /** @var SalesOrder|null $order */
        $order      = SalesOrder::query()->with('items')->findOrFail($id);
        $customers  = Customer::query()->orderBy('name')->get();
        
        $productsQuery = Product::query()->with('category')->orderBy('product_name');
        
        if ($userBranchId) {
            $productsQuery->whereHas('stocks', function($q) use ($userBranchId) {
                $q->where('branch_id', $userBranchId);
            });
        }

        $products = $productsQuery->withSum(['stocks' => function($q) use ($userBranchId) {
            if ($userBranchId) /** @var \Illuminate\Database\Eloquent\Builder $q */ $q->where('branch_id', $userBranchId);
        }], 'quantity')->get();

        $categories = Category::query()->orderBy('name')->get();
        $branches   = Branch::query()->when($userBranchId, fn($q) => $q->where('id', $userBranchId))->orderBy('name')->get();
        /** @var Company|null $company */
        $company    = Company::find(auth()->user()->company_id);

        return view('frontend.sales.sales_edit', compact('order', 'customers', 'products', 'categories', 'branches', 'company'));
    }

    // ─── UPDATE ──────────────────────────────────────────────────────────────

    public function update(Request $request, $id)
    {
        // ... validation ...
        $order = SalesOrder::query()->findOrFail($id);

        DB::transaction(function () use ($request, $order) {
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
            }

            $discount = $request->discount ?? 0;
            $tax      = $request->tax ?? 0;
            $total    = $subtotal - $discount + $tax;
            $paid     = $request->paid_amount ?? 0;
            $due      = $total - $paid;

            $status = 'pending';
            if ($paid >= $total) {
                $status = 'completed';
                $due    = 0;
            } elseif ($paid > 0) {
                $status = 'partial';
            }

            // Reverse old customer balance (always, regardless of whether due was 0)
            if ($order->customer_id && $order->due_amount != 0) {
                /** @var Customer|null $oldCustomer */
                $oldCustomer = Customer::query()->find($order->customer_id);
                if ($oldCustomer) {
                    $oldCustomer->amount_balance = ($oldCustomer->amount_balance ?? 0) - $order->due_amount;
                    $oldCustomer->save();
                }
            }

            // Captured before $order->update() overwrites branch_id below — the
            // old items' stock was decremented at THIS branch, so it has to be
            // reversed here too, not at whatever branch the order is being
            // moved to. Reversing at the new branch instead left the original
            // branch permanently short and credited stock to a branch that
            // never actually held it.
            $oldBranchId = $order->branch_id;

            $order->update([
                'invoice_date'   => $request->invoice_date,
                'due_date'       => $request->due_date,
                'customer_id'    => $request->customer_id,
                'branch_id'      => $request->branch_id,
                'subtotal'       => $subtotal,
                'discount'       => $discount,
                'tax'            => $tax,
                'total_amount'   => $total,
                'paid_amount'    => $paid,
                'due_amount'     => $due,
                'payment_method' => $request->payment_method ?? 'Cash',
                'status'         => $status,
                'notes'          => $request->notes,
            ]);

            // Reverse Stock for old items
            foreach ($order->items as $oldItem) {
                if ($oldItem->product_id) {
                    $oldStock = ProductStock::query()->where('product_id', $oldItem->product_id)
                        ->where('branch_id', $oldBranchId)
                        ->first();
                    if ($oldStock) $oldStock->increment('quantity', $oldItem->quantity);
                }
            }

            // Replace items
            $order->items()->delete();
            foreach ($request->items as $item) {
                SalesOrderItem::query()->create([
                    'sales_order_id' => $order->id,
                    'product_id'     => $item['product_id'] ?? null,
                    'product_name'   => $item['product_name'],
                    'product_code'   => $item['product_code'] ?? null,
                    'unit_price'     => $item['unit_price'],
                    'quantity'       => $item['quantity'],
                    'unit'           => $item['unit'] ?? 'Piece',
                    'discount'       => $item['discount'] ?? 0,
                    'total_price'    => ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0),
                ]);

                // Deduct updated stock
                if (!empty($item['product_id'])) {
                    $newStock = ProductStock::query()->where('product_id', $item['product_id'])
                        ->where('branch_id', $request->branch_id)
                        ->first();
                    if ($newStock) {
                        $newStock->decrement('quantity', $item['quantity']);
                    } else {
                        ProductStock::query()->create([
                            'product_id' => $item['product_id'],
                            'branch_id'  => $request->branch_id,
                            'quantity'   => -$item['quantity']
                        ]);
                    }
                }
            }

            // Update new balance
            if ($request->customer_id) {
                /** @var Customer|null $newCustomer */
                $newCustomer = Customer::query()->find($request->customer_id);
                if ($newCustomer && $due > 0) {
                    $newCustomer->amount_balance = ($newCustomer->amount_balance ?? 0) + $due;
                    $newCustomer->save();
                }
            }

            // Reverse and redo Accounting Journal
            // Eager-load items so the JournalItem observer fires for each deletion (balance reversal)
            /** @var JournalEntry|null $oldEntry */
            $oldEntry = JournalEntry::query()->with('items')->where('reference', $order->invoice_no)->first();
            if ($oldEntry) {
                foreach ($oldEntry->items as $journalItem) $journalItem->delete();
                $oldEntry->delete();
            }
            $this->createAccountingEntry($order);
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Invoice updated successfully.',
                'redirect' => route('sales.invoice.view'),
            ]);
        }

        return redirect()->route('sales.invoice.view')->with('success', 'Invoice updated successfully.');
    }

    // ─── DELETE ──────────────────────────────────────────────────────────────

    public function destroy($id)
    {
        $order = SalesOrder::query()->findOrFail($id);

        DB::transaction(function () use ($order) {
            // Reverse customer balance
            if ($order->customer_id) {
                /** @var Customer|null $customer */
                $customer = Customer::query()->find($order->customer_id);
                if ($customer && $order->due_amount > 0) {
                    $customer->amount_balance = ($customer->amount_balance ?? 0) - $order->due_amount;
                    $customer->save();
                }
            }

            // Restore Stock
            foreach ($order->items as $item) {
                if ($item->product_id) {
                    $stock = ProductStock::query()->where('product_id', $item->product_id)
                        ->where('branch_id', $order->branch_id)
                        ->first();
                    if ($stock) {
                        $stock->increment('quantity', $item->quantity);
                    }
                }
            }

            // Reverse Accounting Journal — eager-load items so observer fires for each (balance reversal)
            /** @var JournalEntry|null $oldEntry */
            $oldEntry = JournalEntry::query()->with('items')->where('reference', $order->invoice_no)->first();
            if ($oldEntry) {
                foreach ($oldEntry->items as $journalItem) $journalItem->delete();
                $oldEntry->delete();
            }

            $order->items()->delete();
            $order->delete();
        });

        return redirect()->route('sales.invoice.view')->with('success', 'Invoice deleted successfully.');
    }

    // ─── PDF ─────────────────────────────────────────────────────────────────

    public function pdf($id)
    {
        /** @var SalesOrder|null $order */
        $order   = SalesOrder::query()->with('customer', 'items')->findOrFail($id);
        /** @var Company|null $company */
        $company = Company::find(auth()->user()->company_id);

        $pdf = Pdf::loadView('frontend.sales.pos_invoice_pdf', compact('order', 'company'))
                  ->setPaper('a4', 'portrait');

        return $pdf->stream('Invoice_' . $order->invoice_no . '.pdf');
    }

    // POS / cash-sale receipt — a simpler classic invoice layout (item table +
    // amount in words + received/balance + signature box), distinct from the
    // "Tax Invoice" style sales_invoice_pdf used elsewhere.
    public function posInvoicePdf($id)
    {
        /** @var SalesOrder|null $order */
        $order   = SalesOrder::query()->with('customer', 'items')->findOrFail($id);
        /** @var Company|null $company */
        $company = Company::find(auth()->user()->company_id);

        $pdf = Pdf::loadView('frontend.sales.pos_invoice_pdf', compact('order', 'company'))
                  ->setPaper('a4', 'portrait');

        return $pdf->stream('Invoice_' . $order->invoice_no . '.pdf');
    }

    // Converts a whole-dollar amount into words, e.g. 1150 -> "One Thousand
    // One Hundred Fifty". No existing package/helper for this in the app, and
    // pulling in a dependency for one phrase on one receipt isn't worth it.
    public static function numberToWords(float $amount): string
    {
        $dollars = (int) floor($amount);
        $cents   = (int) round(($amount - $dollars) * 100);

        $words = $dollars === 0 ? 'Zero' : self::wordsForInteger($dollars);
        $words .= ' Dollars';

        if ($cents > 0) {
            $words .= ' and ' . self::wordsForInteger($cents) . ' Cents';
        }

        return $words . ' only';
    }

    private static function wordsForInteger(int $number): string
    {
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
                 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
                 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens  = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        if ($number === 0) return '';
        if ($number < 20) return $ones[$number];
        if ($number < 100) {
            return trim($tens[intdiv($number, 10)] . ' ' . $ones[$number % 10]);
        }
        if ($number < 1000) {
            return trim($ones[intdiv($number, 100)] . ' Hundred ' . self::wordsForInteger($number % 100));
        }
        if ($number < 1000000) {
            return trim(self::wordsForInteger(intdiv($number, 1000)) . ' Thousand ' . self::wordsForInteger($number % 1000));
        }
        return trim(self::wordsForInteger(intdiv($number, 1000000)) . ' Million ' . self::wordsForInteger($number % 1000000));
    }

    // Public, signed-URL version so the PDF can be opened by a customer (e.g. via WhatsApp)
    // without needing to log in. The signature prevents guessing/enumerating other invoices.
    public function publicPdf($id)
    {
        /** @var SalesOrder|null $order */
        $order   = SalesOrder::withoutGlobalScopes()->with('customer', 'items')->findOrFail($id);
        /** @var Company|null $company */
        $company = Company::find($order->company_id);

        $pdf = Pdf::loadView('frontend.sales.pos_invoice_pdf', compact('order', 'company'))
                  ->setPaper('a4', 'portrait');

        return $pdf->stream('Invoice_' . $order->invoice_no . '.pdf');
    }

    // ─── UPDATE STATUS ───────────────────────────────────────────────────────

    public function updateStatus(Request $request, $id)
    {
        /** @var SalesOrder|null $order */
        $order = SalesOrder::query()->findOrFail($id);
        $order->status = $request->status;
        $order->save();

        return response()->json(['success' => true]);
    }

    // ─── ACCOUNTING ──────────────────────────────────────────────────────────

    public function createAccountingEntry(SalesOrder $order)
    {
        // 1. Ensure essential accounts exist (Standardized Codes matching COA service)
        $comp = $order->company_id;
        
        // Find Receivables (1140)
        $receivableAccount = Account::query()->where('company_id', $comp)->where('code', '1140')->first() 
                          ?: Account::query()->where('company_id', $comp)->where('name', 'like', '%Receivable%')->first();
        if (!$receivableAccount) {
            $receivableAccount = Account::query()->create([
                'company_id' => $comp,
                'code'       => '1140',
                'name'       => 'Accounts Receivable',
                'category'   => 'assets',
                'type'       => 'receivable',
                'balance'    => 0
            ]);
        }

        // Find Revenue (4110)
        $revenueAccount = Account::query()->where('company_id', $comp)->where('code', '4110')->first() 
                        ?: Account::query()->where('company_id', $comp)->where('name', 'like', '%Revenue%')->first() 
                        ?? Account::query()->where('company_id', $comp)->where('category', 'revenue')->where('type', '!=', 'parent')->first();
        if (!$revenueAccount) {
            $revenueAccount = Account::query()->create([
                'company_id' => $comp,
                'code'       => '4110',
                'name'       => 'Sales Revenue',
                'category'   => 'revenue',
                'type'       => 'revenue',
                'balance'    => 0
            ]);
        }
        
        // Find Inventory (1150)
        $inventoryAccount = Account::query()->where('company_id', $comp)->where('code', '1150')->first() 
                         ?: Account::query()->where('company_id', $comp)->where('name', 'like', '%Inventory%')->first();
        
        // Find COGS (5110 = Product Cost per COA seeder; 5100 = COGS parent fallback)
        $cogsAccount = Account::query()->where('company_id', $comp)->where('code', '5110')->first()
                    ?: Account::query()->where('company_id', $comp)->where('code', '5100')->first()
                    ?: Account::query()->where('company_id', $comp)->where('category', 'expenses')->where('name', 'like', '%Cost%')->where('type', '!=', 'parent')->first();

        // Find Discount & Tax
        $discountAccount = Account::query()->where('company_id', $comp)->where('name', 'like', '%Discount%')->first();
        if (!$discountAccount && $order->discount > 0) {
            $discountAccount = Account::query()->create([
                'company_id' => $comp,
                'code'       => '4190',
                'name'       => 'Sales Discount',
                'category'   => 'revenue',
                'type'       => 'contra_revenue',
                'balance'    => 0,
            ]);
        }
        /** @var Account|null $taxAccount */
        $taxAccount = Account::query()->where('company_id', $comp)->where(fn($q)=>$q->where('name', 'like', '%VAT%')->orWhere('name', 'like', '%Tax%'))->first();

        // 2. Find suitable cash/bank account for payment
        /** @var Account|null $paymentAccount */
        $paymentAccount = null;
        if ($order->paid_amount > 0) {
            if ($order->payment_account_id) {
                $paymentAccount = Account::query()->where('company_id', $comp)->find($order->payment_account_id);
            }
            if (!$paymentAccount) {
                if (stripos($order->payment_method, 'Bank') !== false) {
                    $paymentAccount = Account::query()->where('company_id', $comp)->where('name', 'like', '%Bank%')->where('category', 'assets')->first();
                }
                if (!$paymentAccount) {
                    $paymentAccount = Account::query()->where('company_id', $comp)->where('code', '1110')->first() ?: Account::query()->where('company_id', $comp)->where('name', 'like', '%Cash%')->first();
                }
            }
        }

        // 3. Create Journal Entry
        $entry = JournalEntry::query()->create([
            'company_id'   => $comp,
            'branch_id'    => $order->branch_id,
            'entry_number' => 'JE-SALE-' . $order->id . '-' . strtoupper(substr(uniqid(), -5)),
            'date'         => $order->invoice_date,
            'reference'    => $order->invoice_no,
            'description'  => 'Sale to ' . ($order->customer->name ?? 'Walk-in'),
            'status'       => 'posted',
            'total_amount' => $order->total_amount,
            'created_by'   => Auth::id() ?? $order->created_by,
        ]);

        // 4. DEBITS (What you received)
        // NOTE: JournalItem observer (modifyAccountBalance) auto-updates account balances on create.
        //       Do NOT call increment/decrement manually — it would double-apply every amount.
        if ($order->paid_amount > 0 && $paymentAccount) {
            JournalItem::query()->create([
                'company_id'       => $comp,
                'journal_entry_id' => $entry->id,
                'account_id'       => $paymentAccount->id,
                'debit'            => $order->paid_amount,
                'credit'           => 0,
                'description'      => 'Payment for ' . $order->invoice_no,
            ]);
        }
        if ($order->due_amount > 0 && $receivableAccount) {
            JournalItem::query()->create([
                'company_id'       => $comp,
                'journal_entry_id' => $entry->id,
                'account_id'       => $receivableAccount->id,
                'debit'            => $order->due_amount,
                'credit'           => 0,
                'description'      => 'Due for ' . $order->invoice_no,
            ]);
        }
        if ($order->discount > 0 && $discountAccount) {
            JournalItem::query()->create([
                'company_id'       => $comp,
                'journal_entry_id' => $entry->id,
                'account_id'       => $discountAccount->id,
                'debit'            => $order->discount,
                'credit'           => 0,
                'description'      => 'Discount for ' . $order->invoice_no,
            ]);
        }

        // 5. CREDITS (What you earned) — grouped by per-product Sales Revenue account
        //    Use total_price (net of per-item discounts) so revenue matches what was actually charged
        $order->load('items.product');
        $revenueByAccount = [];
        foreach ($order->items as $item) {
            $revAccId = $revenueAccount->id ?? null;
            if ($revAccId) {
                $revenueByAccount[$revAccId] = ($revenueByAccount[$revAccId] ?? 0) + (float)$item->total_price;
            }
        }
        foreach ($revenueByAccount as $accId => $amount) {
            JournalItem::query()->create([
                'company_id'       => $comp,
                'journal_entry_id' => $entry->id,
                'account_id'       => $accId,
                'debit'            => 0,
                'credit'           => $amount,
                'description'      => 'Gross Sale ' . $order->invoice_no,
            ]);
        }

        if ($order->tax > 0 && $taxAccount) {
            JournalItem::query()->create([
                'company_id'       => $comp,
                'journal_entry_id' => $entry->id,
                'account_id'       => $taxAccount->id,
                'debit'            => 0,
                'credit'           => $order->tax,
                'description'      => 'Tax for ' . $order->invoice_no,
            ]);
        }

        // 6. INVENTORY REDUCTION & COST OF GOODS SOLD — grouped by per-product accounts
        //    Both COGS debit and Inventory credit must exist; skip the pair if either account is missing
        $cogsByAccount = [];
        $invByAccount  = [];
        foreach ($order->items as $item) {
            if (!$item->product_id || !$item->product) continue;
            $purchasePrice = (float) ($item->product->purchase_price ?? 0);
            if ($purchasePrice <= 0) continue;
            $itemCogs = $item->quantity * $purchasePrice;
            if ($itemCogs <= 0) continue;

            $cogsAccId = $cogsAccount->id ?? null;
            $invAccId  = $inventoryAccount->id ?? null;

            // Only post when both sides of the COGS entry are resolvable
            if ($cogsAccId && $invAccId) {
                $cogsByAccount[$cogsAccId] = ($cogsByAccount[$cogsAccId] ?? 0) + $itemCogs;
                $invByAccount[$invAccId]   = ($invByAccount[$invAccId] ?? 0) + $itemCogs;
            }
        }

        foreach ($cogsByAccount as $accId => $amount) {
            JournalItem::query()->create([
                'company_id'       => $comp,
                'journal_entry_id' => $entry->id,
                'account_id'       => $accId,
                'debit'            => $amount,
                'credit'           => 0,
                'description'      => 'Cost of Goods Sold for ' . $order->invoice_no,
            ]);
        }

        foreach ($invByAccount as $accId => $amount) {
            JournalItem::query()->create([
                'company_id'       => $comp,
                'journal_entry_id' => $entry->id,
                'account_id'       => $accId,
                'debit'            => 0,
                'credit'           => $amount,
                'description'      => 'Inventory reduction for ' . $order->invoice_no,
            ]);
        }
    }
}
