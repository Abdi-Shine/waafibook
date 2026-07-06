<?php

namespace App\Http\Controllers;
use App\Models\Company;
use App\Models\Category;

use Illuminate\Http\Request;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\Supplier;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Account;
use App\Models\PurchaseExpense;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\ProductStock;
use App\Models\SupplierPayment;
use App\Models\SupplierPaymentDetail;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseOrder::query()->with(['supplier', 'branch', 'items.product'])->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%$search%")
                  ->orWhereHas('supplier', fn($s) => $s->where('name', 'like', "%$search%"));
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $purchaseOrders = $query->get();
        $suppliers      = Supplier::query()->where('status', 'active')->get();
        $filters        = $request->only(['search', 'status', 'supplier_id']);

        /** @var Company|null $company */
        $company = Company::find(auth()->user()->company_id);
        $currSymbols = ['SAR' => '﷼', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AED' => 'د.إ', 'KWD' => 'د.ك'];
        $sym  = $currSymbols[$company->currency ?? ''] ?? ($company->currency ?? '$');
        $curr = $sym;

        return view('frontend.purchase.purchase_order', compact('purchaseOrders', 'suppliers', 'filters', 'sym', 'curr'));
    }

    public function create()
    {
        $suppliers  = Supplier::query()->where('status', 'active')->get();
        $branches   = Branch::query()->get();
        $products   = Product::query()->get();
        $categories = Category::query()->get();

        /** @var Company|null $company */
        $company = Company::find(auth()->user()->company_id);
        $currSymbols = ['SAR' => '﷼', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AED' => 'د.إ', 'KWD' => 'د.ك'];
        $sym  = $currSymbols[$company->currency ?? ''] ?? ($company->currency ?? '$');
        $curr = $sym;

        $lastPO = PurchaseOrder::query()->latest()->first();
        $nextId = $lastPO ? ($lastPO->id + 1) : 1;
        $poNo   = 'PO-' . date('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        return view('frontend.purchase.create_purchase_order', compact('suppliers', 'branches', 'products', 'categories', 'poNo', 'sym', 'curr'));
    }

    public function billIndex(Request $request)
    {
        /** @var PurchaseBill|null $lastBill */
        $lastBill = PurchaseBill::query()->where('bill_number', 'like', 'PB-%')->latest('id')->first();
        $nextId = $lastBill ? ((int) str_replace('PB-' . date('Y') . '-', '', $lastBill->bill_number) + 1) : 1;
        $purchase_no = 'PB-' . date('Y') . '-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

        $query = PurchaseBill::query()->with(['supplier', 'branch', 'items']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('bill_number', 'like', "%$search%")
                  ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%$search%"));
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $billStats = [
            'count'       => (clone $query)->count(),
            'totalPaid'   => (clone $query)->sum('paid_amount'),
            'totalAmount' => (clone $query)->sum('total_amount'),
            'vouchers'    => (clone $query)->whereNotNull('supplier_invoice_no')->count(),
        ];
        $purchaseBills = $query->latest()->get();

        $suppliers = Supplier::query()->where('status', 'active')->get();
        $branches = Branch::query()->get();
        $products = Product::query()->get();
        $categories = Category::query()->get();

        /** @var Company|null $company */
        $company = Company::find(auth()->user()->company_id);
        $currencySymbols = ['SAR' => '﷼', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AED' => 'د.إ', 'KWD' => 'د.ك'];
        $sym = $currencySymbols[$company->currency ?? ''] ?? ($company->currency ?? '$');
        $curr = $sym;
        $filters = $request->only(['search', 'status', 'supplier_id']);

        /** @var PurchaseBill|null $lastVch */
        $lastVch = PurchaseBill::query()->where('supplier_invoice_no', 'like', 'VCH-%')->latest('id')->first();
        $nextVId = $lastVch ? ((int) str_replace('VCH-' . date('Y') . '-', '', $lastVch->supplier_invoice_no) + 1) : 1;
        $voucher_no = 'VCH-' . date('Y') . '-' . str_pad($nextVId, 5, '0', STR_PAD_LEFT);

        return view('frontend.purchase.purchase_bill', compact('purchaseBills', 'billStats', 'suppliers', 'branches', 'products', 'sym', 'curr', 'purchase_no', 'voucher_no', 'categories', 'filters'));
    }

    public function store(Request $request)
    {
        $supplier_id = $request->supplier_id ?? $request->vendor_id;

        if (!$supplier_id || !is_numeric($supplier_id)) {
            return response()->json(['success' => false, 'message' => 'Supplier Identification is invalid or empty. Value: ' . var_export($supplier_id, true)]);
        }

        DB::beginTransaction();
        try {
            $po = new PurchaseOrder();
            $po->po_number         = $request->po_number;
            $po->supplier_id       = (int) $supplier_id;
            $po->branch_id         = $request->branch_id ?: null;
            $po->order_date        = $request->order_date;
            $po->expected_delivery = $request->expected_delivery;
            $po->payment_terms     = $request->payment_terms;
            $po->subtotal          = $request->subtotal      ?? 0;
            $po->vat               = $request->vat           ?? 0;
            $po->total_amount      = $request->total_amount  ?? 0;
            $po->notes             = $request->notes;
            $po->status            = 'pending';
            $po->save();

            if ($request->items && is_array($request->items)) {
                foreach ($request->items as $item) {
                    PurchaseOrderItem::query()->create([
                        'purchase_order_id' => $po->id,
                        'product_id'        => is_numeric($item['product_id']) ? $item['product_id'] : null,
                        'product_name'      => $item['product_name'] ?? null,
                        'quantity'          => $item['quantity'],
                        'unit'              => $item['unit'] ?? 'Piece',
                        'unit_price'        => $item['unit_price'],
                        'total_amount'      => $item['total_amount'],
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Purchase Order created successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create Purchase Order: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        /** @var PurchaseOrder|null $po */
        $po = PurchaseOrder::query()->findOrFail($id);
        $po->delete();
        return response()->json(['success' => true, 'message' => 'Purchase Order deleted successfully.']);
    }

    public function show($id)
    {
        /** @var PurchaseOrder|null $po */
        $po = PurchaseOrder::query()->with(['supplier', 'branch', 'items.product'])->findOrFail($id);
        return response()->json($po);
    }

    public function update(Request $request, $id)
    {
        $supplier_id = $request->supplier_id ?? $request->vendor_id;

        if (!$supplier_id || !is_numeric($supplier_id)) {
            return response()->json(['success' => false, 'message' => 'Supplier Identification is invalid or empty.']);
        }

        DB::beginTransaction();
        try {
            /** @var PurchaseOrder $po */
            $po = PurchaseOrder::query()->with('items')->findOrFail($id);

            // 1. Update basic fields
            $po->supplier_id       = (int) $supplier_id;
            $po->branch_id         = $request->branch_id ?: null;
            $po->order_date        = $request->order_date;
            $po->expected_delivery = $request->expected_delivery;
            $po->payment_terms     = $request->payment_terms;
            $po->subtotal          = $request->subtotal      ?? 0;
            $po->vat               = $request->vat           ?? 0;
            $po->total_amount      = $request->total_amount  ?? 0;
            $po->notes             = $request->notes;
            $po->save();

            // 2. Re-sync items: simplest via replace
            $po->items()->delete();
            if ($request->items && is_array($request->items)) {
                foreach ($request->items as $item) {
                    PurchaseOrderItem::query()->create([
                        'purchase_order_id' => $po->id,
                        'product_id'        => (isset($item['product_id']) && is_numeric($item['product_id'])) ? $item['product_id'] : null,
                        'product_name'      => $item['product_name'] ?? null,
                        'quantity'          => $item['quantity'],
                        'unit'              => $item['unit'] ?? 'Piece',
                        'unit_price'        => $item['unit_price'],
                        'total_amount'      => $item['total_amount'],
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Purchase Order updated successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to update Purchase Order: ' . $e->getMessage()]);
        }
    }

    public function printOrder($id)
    {
        /** @var PurchaseOrder|null $po */
        $po = PurchaseOrder::query()->with(['supplier', 'branch', 'items.product'])->findOrFail($id);
        /** @var Company|null $company */
        $company = Company::find(auth()->user()->company_id);
        return view('frontend.purchase.purchase_order_pdf', compact('po', 'company'));
    }

    public function downloadOrderPdf($id)
    {
        /** @var PurchaseOrder|null $po */
        $po = PurchaseOrder::query()->with(['supplier', 'branch', 'items.product'])->findOrFail($id);
        /** @var Company|null $company */
        $company = Company::find(auth()->user()->company_id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('frontend.purchase.pdf_purchase_order', compact('po', 'company'));
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('Purchase-Order-' . $po->po_number . '.pdf');
    }

    public function updateStatus(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            /** @var PurchaseOrder|null $po */
            $po = PurchaseOrder::query()->with('items')->findOrFail($id);
            
            // Goods Receive (Stock Increases) logic
            if ($request->status == 'received' && $po->status != 'received') {
                foreach ($po->items as $item) {
                    if ($item->product_id) {
                        $stock = ProductStock::query()->firstOrNew([
                            'product_id' => $item->product_id,
                            'branch_id' => $po->branch_id,
                        ]);
                        $stock->quantity += $item->quantity;
                        $stock->save();
                    }
                }
            }

            $po->status = $request->status;
            $po->save();
            
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // Purchase Bill logic
    public function emailOrder($id)
    {
        $po = PurchaseOrder::with(['supplier', 'items'])->findOrFail($id);

        if (empty($po->supplier->email)) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier does not have an email address on file.',
            ], 422);
        }

        try {
            \Illuminate\Support\Facades\Mail::to($po->supplier->email)
                ->send(new \App\Mail\PurchaseOrderMail($po));

            return response()->json([
                'success' => true,
                'message' => 'Purchase Order ' . $po->po_number . ' has been sent to ' . $po->supplier->email,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function createBill()
    {
        /** @var PurchaseBill|null $lastBill */
        $lastBill = PurchaseBill::query()->where('bill_number', 'like', 'PB-%')->latest('id')->first();
        $nextId = $lastBill ? ((int) str_replace('PB-' . date('Y') . '-', '', $lastBill->bill_number) + 1) : 1;
        $purchase_no = 'PB-' . date('Y') . '-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

        $purchaseBills = PurchaseBill::query()->with(['supplier', 'branch', 'items'])
            ->latest()
            ->get();

        $suppliers = Supplier::query()->where('status', 'active')->get();
        $branches = Branch::query()->get();
        $products = Product::query()->withSum('stocks', 'quantity')->get();
        $categories = Category::query()->get();
        /** @var Company|null $company */
        $company = Company::find(auth()->user()->company_id);
        $currSymbols = ['SAR' => '﷼', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AED' => 'د.إ', 'KWD' => 'د.ك'];
        $sym = $currSymbols[$company->currency ?? ''] ?? ($company->currency ?? '$');
        $curr = $sym;

        /** @var PurchaseBill|null $lastVch */
        $lastVch = PurchaseBill::query()->where('supplier_invoice_no', 'like', 'VCH-%')->latest('id')->first();
        $nextVId = $lastVch ? ((int) str_replace('VCH-' . date('Y') . '-', '', $lastVch->supplier_invoice_no) + 1) : 1;
        $voucher_no = 'VCH-' . date('Y') . '-' . str_pad($nextVId, 5, '0', STR_PAD_LEFT);

        $accounts = Account::query()->where('is_active', 1)->get();

        return view('frontend.purchase.add_purchase_bill', compact('purchase_no', 'voucher_no', 'purchaseBills', 'suppliers', 'branches', 'products', 'categories', 'company', 'sym', 'curr', 'accounts'));
    }

    public function editBill($id)
    {
        /** @var PurchaseBill $bill */
        $bill = PurchaseBill::query()->with(['items', 'supplier', 'branch'])->findOrFail($id);
        
        $purchase_no = $bill->bill_number;
        $voucher_no = $bill->supplier_invoice_no;

        $suppliers = Supplier::query()->where('status', 'active')->get();
        $branches = Branch::query()->get();
        $products = Product::query()->withSum('stocks', 'quantity')->get();
        $categories = Category::query()->get();
        
        /** @var Company|null $company */
        $company = Company::find(auth()->user()->company_id);
        $currSymbols = ['SAR' => '﷼', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AED' => 'د.إ', 'KWD' => 'د.ك'];
        $sym = $currSymbols[$company->currency ?? ''] ?? ($company->currency ?? '$');
        $curr = $sym;
        $accounts = Account::query()->where('is_active', 1)->get();

        return view('frontend.purchase.edit_purchase_bill', compact('bill', 'purchase_no', 'voucher_no', 'suppliers', 'branches', 'products', 'categories', 'company', 'sym', 'curr', 'accounts'));
    }

    public function updateBill(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            /** @var PurchaseBill $bill */
            $bill = PurchaseBill::query()->with('items')->findOrFail($id);

            /** @var Supplier $supplier */
            $supplier = Supplier::query()->findOrFail($request->supplier_id);

            // 0. Capture old supplier balance impact before any changes
            $oldNetBalance = $bill->total_amount - $bill->paid_amount;
            $oldPaidAmount = $bill->paid_amount;

            // 1. Revert Stock Changes from old items
            foreach ($bill->items as $oldItem) {
                if ($oldItem->product_id) {
                    $stock = ProductStock::query()->where('product_id', $oldItem->product_id)
                        ->where('branch_id', $bill->branch_id)
                        ->first();
                    if ($stock) {
                        $stock->decrement('quantity', $oldItem->quantity);
                    }
                }
            }

            // 2. Delete old items
            $bill->items()->delete();

            // 3. Update Bill info
            $total_amount = $request->total_amount ?? 0;
            $paid_amount = $request->paid_amount ?? 0;
            $subtotal = $request->subtotal ?? 0;
            $vat = $request->vat ?? 0;
            
            $bill->update([
                'supplier_invoice_no' => $request->supplier_invoice_no,
                'supplier_id'       => $request->supplier_id,
                'branch_id'         => $request->branch_id,
                'bill_date'         => $request->purchase_date,
                'due_date'          => $request->expected_delivery,
                'subtotal'          => $subtotal,
                'vat'               => $vat,
                'total_amount'      => $total_amount,
                'paid_amount'       => $paid_amount,
                'balance_amount'    => $total_amount - $paid_amount,
                'notes'             => $request->notes,
                'status'            => ($total_amount - $paid_amount <= 0) ? 'paid' : ($paid_amount > 0 ? 'partial' : 'pending'),
                'payment_account_id' => $request->payment_account_id,
            ]);

            // 3b. Adjust supplier balance: remove old impact, apply new impact
            $newNetBalance = $total_amount - $paid_amount;
            $balanceDiff = $newNetBalance - $oldNetBalance;
            if ($balanceDiff > 0) {
                $supplier->increment('amount_balance', $balanceDiff);
            } elseif ($balanceDiff < 0) {
                $supplier->decrement('amount_balance', abs($balanceDiff));
            }

            // 4. Create new items and update stock
            if ($request->items) {
                foreach ($request->items as $item) {
                    $productId = $item['product_id'] ?? null;

                    // Same gap as storeBill(): a free-typed item name with no
                    // matching product would otherwise be saved with product_id
                    // null and silently skip price/stock updates below.
                    if (!is_numeric($productId) && !empty($item['product_name'])) {
                        $matchedProduct = Product::query()->where('product_name', $item['product_name'])->first();
                        if (!$matchedProduct) {
                            $matchedProduct = Product::query()->create([
                                'product_name'   => $item['product_name'],
                                'product_code'   => $item['product_code'] ?? null,
                                'unit'           => $item['unit'] ?? 'Piece',
                                'purchase_price' => $item['unit_price'] ?? 0,
                                'selling_price'  => $item['unit_price'] ?? 0,
                            ]);
                        }
                        $productId = $matchedProduct->id;
                    }

                    PurchaseBillItem::query()->create([
                        'purchase_bill_id'  => $bill->id,
                        'product_id'        => is_numeric($productId) ? $productId : null,
                        'product_name'      => $item['product_name'] ?? 'Product',
                        'product_code'      => $item['product_code'] ?? null,
                        'quantity'          => $item['quantity'],
                        'unit'              => $item['unit'] ?? 'Piece',
                        'unit_price'        => $item['unit_price'],
                        'discount'          => $item['discount'] ?? 0,
                        'total_amount'      => $item['total_amount'],
                    ]);

                    if (is_numeric($productId)) {
                        /** @var Product|null $product */
                        $product = Product::query()->find($productId);
                        if ($product) {
                            $product->purchase_price = $item['unit_price'];
                            $product->save();
                        }

                        // Read-then-write here ("$stock->quantity = old + new; save()")
                        // raced under concurrent requests — two simultaneous edits
                        // could both read the same starting quantity and one
                        // update would silently overwrite the other's. increment()
                        // issues an atomic "UPDATE ... SET quantity = quantity + ?"
                        // instead.
                        $stock = ProductStock::query()->firstOrCreate(
                            ['product_id' => $productId, 'branch_id' => $request->branch_id],
                            ['quantity' => 0]
                        );
                        $stock->increment('quantity', $item['quantity'] ?? 0);
                    }
                }
            }

            // 5. Re-sync Journal Entry and Items
            $cid = Auth::user()->company_id;
            $journal = JournalEntry::query()->where('reference', $bill->bill_number)->first();
            if ($journal) {
                $journal->update([
                    'date'         => $request->purchase_date,
                    'description'  => 'Purchase Bill from ' . $supplier->name . ($request->supplier_invoice_no ? ' (Inv: ' . $request->supplier_invoice_no . ')' : ''),
                    'total_amount' => $total_amount,
                    'company_id'   => $cid,
                ]);

                // Delete old items individually so the observer reverses account balances
                foreach ($journal->items as $oldItem) {
                    $oldItem->delete();
                }

                $inventoryAccount = Account::query()->where('company_id', $cid)->where('code', '1150')->first()
                                 ?: Account::query()->where('company_id', $cid)->where('type', 'inventory')->first();
                $payableAccount   = Account::query()->where('company_id', $cid)->where('code', '2110')->first()
                                 ?: Account::query()->where('company_id', $cid)->where('name', 'like', '%Accounts Payable%')->first();

                if ($inventoryAccount) {
                    JournalItem::query()->create([
                        'journal_entry_id' => $journal->id,
                        'account_id'       => $inventoryAccount->id,
                        'company_id'       => $cid,
                        'description'      => 'Stock value increase (' . $bill->bill_number . ')',
                        'debit'            => $subtotal,
                        'credit'           => 0,
                    ]);
                    if ((float) $vat > 0) {
                        JournalItem::query()->create([
                            'journal_entry_id' => $journal->id,
                            'account_id'       => $inventoryAccount->id,
                            'company_id'       => $cid,
                            'description'      => 'VAT on purchase (' . $bill->bill_number . ')',
                            'debit'            => (float) $vat,
                            'credit'           => 0,
                        ]);
                    }
                }
                if ($payableAccount) {
                    JournalItem::query()->create([
                        'journal_entry_id' => $journal->id,
                        'account_id'       => $payableAccount->id,
                        'company_id'       => $cid,
                        'description'      => 'Liability to Supplier ' . $supplier->name,
                        'debit'            => 0,
                        'credit'           => $total_amount,
                    ]);
                }
            }

            // 6. Re-sync the immediate-payment side effect (SupplierPayment +
            // its own Dr Payable / Cr Cash journal entry, created in storeBill
            // when paid_amount > 0). Previously only the supplier's running
            // balance was adjusted on edit — the cash/bank account itself, and
            // the payment record, were left stale, so editing a bill's paid
            // amount silently desynced the cash ledger from reality.
            if ((float) $paid_amount !== (float) $oldPaidAmount) {
                $oldPaymentJournal = JournalEntry::query()->where('reference', 'PAY-' . $bill->bill_number)->first();
                if ($oldPaymentJournal) {
                    foreach ($oldPaymentJournal->items as $oldPayItem) {
                        $oldPayItem->delete();
                    }
                    $oldPaymentJournal->delete();
                }

                $oldPaymentDetail = SupplierPaymentDetail::query()->where('purchase_bill_id', $bill->id)->first();
                if ($oldPaymentDetail) {
                    /** @var SupplierPayment|null $oldPayment */
                    $oldPayment = $oldPaymentDetail->payment;
                    $oldPaymentDetail->delete();
                    $oldPayment?->delete();
                }

                if ($paid_amount > 0) {
                    $newPayment = SupplierPayment::query()->create([
                        'voucher_no'      => 'PAY-' . $bill->bill_number,
                        'payment_date'    => $request->purchase_date,
                        'supplier_id'     => $supplier->id,
                        'bank_account_id' => $request->payment_account_id ?: null,
                        'payment_method'  => 'Cash',
                        'amount'          => $paid_amount,
                        'reference'       => 'PAY-' . $bill->bill_number,
                        'notes'           => 'Immediate payment for ' . $bill->bill_number,
                        'status'          => 'completed',
                        'created_by'      => Auth::id(),
                    ]);

                    SupplierPaymentDetail::query()->create([
                        'supplier_payment_id' => $newPayment->id,
                        'purchase_bill_id'    => $bill->id,
                        'amount'              => $paid_amount,
                    ]);

                    $cashAccount = Account::query()->find($request->payment_account_id)
                                ?: Account::query()->where('company_id', $cid)->where('code', '1010')->first()
                                ?: Account::query()->where('company_id', $cid)->where('name', 'like', '%Cash%')->first();

                    if ($payableAccount && $cashAccount) {
                        $payJournal = JournalEntry::query()->create([
                            'entry_number' => 'EN-PAY-' . date('Y') . '-' . str_pad($newPayment->id, 6, '0', STR_PAD_LEFT),
                            'date'         => $request->purchase_date,
                            'reference'    => 'PAY-' . $bill->bill_number,
                            'description'  => 'Payment to ' . $supplier->name,
                            'total_amount' => $paid_amount,
                            'status'       => 'posted',
                            'company_id'   => $cid,
                            'created_by'   => Auth::id(),
                        ]);
                        JournalItem::query()->create(['journal_entry_id' => $payJournal->id, 'account_id' => $payableAccount->id, 'company_id' => $cid, 'description' => 'Payment to Supplier ' . $supplier->name, 'debit' => $paid_amount, 'credit' => 0]);
                        JournalItem::query()->create(['journal_entry_id' => $payJournal->id, 'account_id' => $cashAccount->id, 'company_id' => $cid, 'description' => 'Cash outflow', 'debit' => 0, 'credit' => $paid_amount]);
                    }
                }
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Purchase Bill updated successfully.', 'redirect' => route('purchase.bill')]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function storeDraftBill(Request $request)
    {
        try {
            $bill = PurchaseBill::query()->create([
                'bill_number'         => $request->purchase_no ?: ('DRAFT-' . date('YmdHis')),
                'supplier_invoice_no' => $request->supplier_invoice_no,
                'supplier_id'         => $request->supplier_id ?: null,
                'branch_id'           => $request->branch_id ?: null,
                'bill_date'           => $request->purchase_date ?: now()->toDateString(),
                'due_date'            => $request->expected_delivery ?: null,
                'payment_terms'       => $request->payment_terms,
                'subtotal'            => $request->subtotal ?? 0,
                'vat'                 => $request->vat ?? 0,
                'total_amount'        => $request->total_amount ?? 0,
                'paid_amount'         => 0,
                'balance_amount'      => $request->total_amount ?? 0,
                'notes'               => $request->notes,
                'status'              => 'draft',
                'payment_account_id'  => $request->payment_account_id ?: null,
                'created_by'          => Auth::id(),
            ]);

            if ($request->items) {
                foreach ($request->items as $item) {
                    if (empty($item['product_name']) && empty($item['quantity'])) continue;
                    PurchaseBillItem::query()->create([
                        'purchase_bill_id' => $bill->id,
                        'product_id'       => isset($item['product_id']) && is_numeric($item['product_id']) ? $item['product_id'] : null,
                        'product_name'     => $item['product_name'] ?? 'Product',
                        'product_code'     => $item['product_code'] ?? null,
                        'quantity'         => $item['quantity'] ?? 0,
                        'unit'             => $item['unit'] ?? 'Piece',
                        'unit_price'       => $item['unit_price'] ?? 0,
                        'discount'         => $item['discount'] ?? 0,
                        'total_amount'     => $item['total_amount'] ?? 0,
                    ]);
                }
            }

            return response()->json([
                'status'   => 'success',
                'message'  => 'Draft saved successfully.',
                'redirect' => route('purchase.bill'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function updateDraftBill(Request $request, $id)
    {
        try {
            $bill = PurchaseBill::query()->findOrFail($id);

            $bill->update([
                'bill_number'         => $request->purchase_no ?: $bill->bill_number,
                'supplier_invoice_no' => $request->supplier_invoice_no,
                'supplier_id'         => $request->supplier_id ?: null,
                'branch_id'           => $request->branch_id ?: null,
                'bill_date'           => $request->purchase_date ?: now()->toDateString(),
                'due_date'            => $request->expected_delivery ?: null,
                'payment_terms'       => $request->payment_terms,
                'subtotal'            => $request->subtotal ?? 0,
                'vat'                 => $request->vat ?? 0,
                'total_amount'        => $request->total_amount ?? 0,
                'paid_amount'         => 0,
                'balance_amount'      => $request->total_amount ?? 0,
                'notes'               => $request->notes,
                'status'              => 'draft',
                'payment_account_id'  => $request->payment_account_id ?: null,
            ]);

            // Replace items
            $bill->items()->delete();
            if ($request->items) {
                foreach ($request->items as $item) {
                    if (empty($item['product_name']) && empty($item['quantity'])) continue;
                    PurchaseBillItem::query()->create([
                        'purchase_bill_id' => $bill->id,
                        'product_id'       => isset($item['product_id']) && is_numeric($item['product_id']) ? $item['product_id'] : null,
                        'product_name'     => $item['product_name'] ?? 'Product',
                        'product_code'     => $item['product_code'] ?? null,
                        'quantity'         => $item['quantity'] ?? 0,
                        'unit'             => $item['unit'] ?? 'Piece',
                        'unit_price'       => $item['unit_price'] ?? 0,
                        'discount'         => $item['discount'] ?? 0,
                        'total_amount'     => $item['total_amount'] ?? 0,
                    ]);
                }
            }

            return response()->json([
                'status'   => 'success',
                'message'  => 'Draft updated successfully.',
                'redirect' => route('purchase.bill'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function storeBill(Request $request)
    {
        DB::beginTransaction();
        try {
            /** @var Supplier|null $supplier */
            $supplier = Supplier::query()->findOrFail($request->supplier_id);
            /** @var Supplier $supplier */
            
            $total_amount = $request->total_amount ?? 0;
            $paid_amount = $request->paid_amount ?? 0;
            $subtotal = $request->subtotal ?? 0;
            $vat = $request->vat ?? 0;
            
            $branch_id = $request->branch_id ?: null;

            // The form suggests a bill number once on page load; if it's gone stale
            // (e.g. a browser back-button resubmission, or another bill was created
            // in the meantime) and already exists for this company, self-heal by
            // assigning the next available number instead of failing the whole bill.
            $billNumber = $request->purchase_no;
            $cidForNumber = Auth::user()->company_id;
            if (PurchaseBill::query()->where('company_id', $cidForNumber)->where('bill_number', $billNumber)->exists()) {
                $lastBill = PurchaseBill::query()->where('company_id', $cidForNumber)
                    ->where('bill_number', 'like', 'PB-%')
                    ->latest('id')->first();
                $nextId = $lastBill ? ((int) str_replace('PB-' . date('Y') . '-', '', $lastBill->bill_number) + 1) : 1;
                $billNumber = 'PB-' . date('Y') . '-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
            }

            // 1. Create Purchase Bill record
            $bill = PurchaseBill::query()->create([
                'bill_number'       => $billNumber,
                'supplier_invoice_no' => $request->supplier_invoice_no,
                'supplier_id'       => $request->supplier_id,
                'branch_id'         => $branch_id,
                'bill_date'         => $request->purchase_date,
                'due_date'          => $request->expected_delivery,
                'payment_terms'     => $request->payment_terms,
                'subtotal'          => $subtotal,
                'vat'               => $vat,
                'total_amount'      => $total_amount,
                'paid_amount'       => $paid_amount,
                'balance_amount'    => $total_amount - $paid_amount,
                'notes'             => $request->notes,
                'status'            => ($total_amount - $paid_amount <= 0) ? 'paid' : ($paid_amount > 0 ? 'partial' : 'pending'),
                'payment_account_id' => $request->payment_account_id,
                'created_by'        => Auth::id(),
            ]);

            if ($request->items) {
                foreach ($request->items as $item) {
                    PurchaseBillItem::query()->create([
                        'purchase_bill_id'  => $bill->id,
                        'product_id'        => is_numeric($item['product_id']) ? $item['product_id'] : null,
                        'product_name'      => $item['product_name'] ?? 'Product',
                        'product_code'      => $item['product_code'] ?? null,
                        'quantity'          => $item['quantity'],
                        'unit'              => $item['unit'] ?? 'Piece',
                        'unit_price'        => $item['unit_price'],
                        'discount'          => $item['discount'] ?? 0,
                        'total_amount'      => $item['total_amount'],
                    ]);
                }
            }

            // 2. Accounting Entry (Purchase Bill)
            $cid = Auth::user()->company_id;

            // A "Return" bill sends goods back to the supplier, which is the
            // exact opposite of a normal purchase: inventory goes DOWN and
            // what you owe the supplier goes DOWN too, so every debit/credit
            // below — and the supplier balance update — has to flip direction
            // instead of reusing the normal-purchase entries as-is.
            $purchase_type = $request->purchase_type ?? 'Purchase';
            $isReturn = $purchase_type === 'Return';

            /** @var Account|null $inventoryAccount */
            $inventoryAccount = Account::query()->where('company_id', $cid)->where('code', '1150')->first()
                             ?: Account::query()->where('company_id', $cid)->where('type', 'inventory')->first()
                             ?: Account::query()->where('company_id', $cid)->where('name', 'like', '%Inventory%')->first();
            /** @var Account|null $payableAccount */
            $payableAccount = Account::query()->where('company_id', $cid)->where('code', '2110')->first()
                           ?: Account::query()->where('company_id', $cid)->where('name', 'like', '%Accounts Payable%')->first();

            // Derived from the bill's own (globally unique) primary key rather
            // than "last journal entry id + 1" — the latter races under
            // concurrent requests and can collide across companies sharing
            // this database, causing a journal_entries_entry_number_unique
            // violation (e.g. two companies' first bill both computing "1").
            $entry_no = 'EN-PB-' . date('Y') . '-' . str_pad($bill->id, 6, '0', STR_PAD_LEFT);

            $journal = JournalEntry::query()->create([
                'entry_number' => $entry_no,
                'date'         => $request->purchase_date,
                'reference'    => $billNumber,
                'description'  => 'Purchase Bill from ' . $supplier->name . ($request->supplier_invoice_no ? ' (Inv: ' . $request->supplier_invoice_no . ')' : ''),
                'total_amount' => $total_amount,
                'status'       => 'posted',
                'company_id'   => $cid,
                'created_by'   => Auth::id(),
            ]);

            // Normal purchase: Dr Inventory = subtotal; Dr VAT Input = vat; Cr Accounts Payable = total_amount
            // Return to supplier: same accounts, but debit/credit swapped (see comment above)
            if ($inventoryAccount) {
                JournalItem::query()->create([
                    'journal_entry_id' => $journal->id,
                    'account_id'       => $inventoryAccount->id,
                    'company_id'       => $cid,
                    'description'      => ($isReturn ? 'Stock value decrease (return) ' : 'Stock value increase (') . $billNumber . ')',
                    'debit'            => $isReturn ? 0 : $subtotal,
                    'credit'           => $isReturn ? $subtotal : 0,
                ]);
            }
            if ((float) $vat > 0 && $inventoryAccount) {
                JournalItem::query()->create([
                    'journal_entry_id' => $journal->id,
                    'account_id'       => $inventoryAccount->id,
                    'company_id'       => $cid,
                    'description'      => 'VAT on purchase (' . $billNumber . ')',
                    'debit'            => $isReturn ? 0 : (float) $vat,
                    'credit'           => $isReturn ? (float) $vat : 0,
                ]);
            }
            if ($payableAccount) {
                JournalItem::query()->create([
                    'journal_entry_id' => $journal->id,
                    'account_id'       => $payableAccount->id,
                    'company_id'       => $cid,
                    'description'      => ($isReturn ? 'Reduced liability to Supplier ' : 'Liability to Supplier ') . $supplier->name,
                    'debit'            => $isReturn ? $total_amount : 0,
                    'credit'           => $isReturn ? 0 : $total_amount,
                ]);
            }

            // Update supplier balance atomically (return reduces what's owed)
            if ($isReturn) {
                $supplier->decrement('amount_balance', $total_amount);
            } else {
                $supplier->increment('amount_balance', $total_amount);
            }

            // Supplier Payment (immediate)
            if ($paid_amount > 0) {
                $payment = SupplierPayment::query()->create([
                    'voucher_no'      => 'PAY-' . $billNumber,
                    'payment_date'    => $request->purchase_date,
                    'supplier_id'     => $supplier->id,
                    'bank_account_id' => $request->payment_account_id ?: null,
                    'payment_method'  => 'Cash',
                    'amount'          => $paid_amount,
                    'reference'       => 'PAY-' . $billNumber,
                    'notes'           => 'Immediate payment for ' . $billNumber,
                    'status'          => 'completed',
                    'created_by'      => Auth::id(),
                ]);

                SupplierPaymentDetail::query()->create([
                    'supplier_payment_id' => $payment->id,
                    'purchase_bill_id'    => $bill->id,
                    'amount'              => $paid_amount,
                ]);

                $supplier->decrement('amount_balance', $paid_amount);

                $cashAccount = Account::query()->find($request->payment_account_id)
                            ?: Account::query()->where('company_id', $cid)->where('code', '1010')->first()
                            ?: Account::query()->where('company_id', $cid)->where('name', 'like', '%Cash%')->first();

                if ($payableAccount && $cashAccount) {
                    $payJournal = JournalEntry::query()->create([
                        'entry_number' => 'EN-PAY-' . date('Y') . '-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT),
                        'date'         => $request->purchase_date,
                        'reference'    => 'PAY-' . $billNumber,
                        'description'  => 'Payment to ' . $supplier->name,
                        'total_amount' => $paid_amount,
                        'status'       => 'posted',
                        'company_id'   => $cid,
                        'created_by'   => Auth::id(),
                    ]);
                    JournalItem::query()->create(['journal_entry_id' => $payJournal->id, 'account_id' => $payableAccount->id, 'company_id' => $cid, 'description' => 'Payment to Supplier ' . $supplier->name, 'debit' => $paid_amount, 'credit' => 0]);
                    JournalItem::query()->create(['journal_entry_id' => $payJournal->id, 'account_id' => $cashAccount->id, 'company_id' => $cid, 'description' => 'Cash outflow', 'debit' => 0, 'credit' => $paid_amount]);
                }
            }

            // Goods Receive (Stock update for bill items)
            if ($request->items && !$isReturn) {
                foreach ($request->items as $item) {
                    $productId = $item['product_id'] ?? null;

                    // Item was typed as a free-text name instead of picked from the
                    // existing-product dropdown — match it by name, or create it,
                    // so its price/stock still get updated below instead of being
                    // silently lost (matches what the "new tag" UI implies it does).
                    if (!is_numeric($productId) && !empty($item['product_name'])) {
                        $matchedProduct = Product::query()->where('product_name', $item['product_name'])->first();
                        if (!$matchedProduct) {
                            $matchedProduct = Product::query()->create([
                                'product_name'   => $item['product_name'],
                                'product_code'   => $item['product_code'] ?? null,
                                'unit'           => $item['unit'] ?? 'Piece',
                                'purchase_price' => $item['unit_price'] ?? 0,
                                'selling_price'  => $item['unit_price'] ?? 0,
                            ]);
                        }
                        $productId = $matchedProduct->id;
                        $item['product_id'] = $productId;
                    }

                    if (is_numeric($productId)) {
                        // 1. Update purchase price on Product table (AVCO or latest)
                        /** @var Product|null $product */
                        $product = Product::query()->find($productId);
                        if ($product) {
                            $costingMethod = Company::find(auth()->user()->company_id)?->costing_method ?? 'FIFO';
                            if ($costingMethod === 'AVCO') {
                                $currentQty   = ProductStock::query()->where('product_id', $product->id)->sum('quantity');
                                $currentCost  = (float) ($product->purchase_price ?? 0);
                                $newQty       = (float) ($item['quantity'] ?? 0);
                                $newPrice     = (float) ($item['unit_price'] ?? 0);
                                $totalQty     = $currentQty + $newQty;
                                $product->purchase_price = $totalQty > 0
                                    ? round((($currentQty * $currentCost) + ($newQty * $newPrice)) / $totalQty, 4)
                                    : $newPrice;
                            } else {
                                // FIFO / LIFO: use latest purchase price
                                $product->purchase_price = $item['unit_price'];
                            }
                            $product->save();
                        }

                        // 2. Update Branch/Store Stock — atomic increment (see
                        // updateBill's matching comment on why read-then-save
                        // races under concurrent requests)
                        $stock = ProductStock::query()->firstOrCreate(
                            ['product_id' => $item['product_id'], 'branch_id' => $branch_id],
                            ['quantity' => 0]
                        );
                        $stock->increment('quantity', $item['quantity'] ?? 0);
                    }
                }
            } elseif ($request->items && $purchase_type === 'Return') {
                // Purchase Return logic
                foreach ($request->items as $item) {
                    if (isset($item['product_id']) && is_numeric($item['product_id'])) {
                        $stock = ProductStock::query()->firstOrCreate(
                            ['product_id' => $item['product_id'], 'branch_id' => $branch_id],
                            ['quantity' => 0]
                        );
                        $stock->decrement('quantity', $item['quantity'] ?? 0);
                    }
                }
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Purchase Bill recorded successfully.', 'redirect' => route('purchase.bill')]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function returns()
    {
        $bills = PurchaseBill::query()->with(['supplier', 'branch', 'items.product.stocks', 'items.returnItems'])->latest()->get();
        $returns = PurchaseReturn::query()->with(['bill', 'supplier', 'items.product'])->latest()->get();
        /** @var Company|null $company */
        $company = Company::find(auth()->user()->company_id);
        $currSymbols = ['SAR' => '﷼', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AED' => 'د.إ', 'KWD' => 'د.ك'];
        $currency = $currSymbols[$company->currency ?? ''] ?? ($company->currency ?? '$');
        $suppliers = Supplier::where('status', 'active')->get();
        $branches = Branch::all();
        $cashAccounts = Account::query()
            ->whereIn('type', ['cash', 'bank'])
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return view('frontend.purchase.purchase_return', compact('bills', 'returns', 'currency', 'suppliers', 'branches', 'cashAccounts'));
    }

    public function storeReturn(Request $request)
    {
        $request->validate([
            'purchase_bill_id'  => 'required|exists:purchase_bills,id',
            'return_date'       => 'required|date',
            'reason'            => 'required|string',
            'return_type'       => 'required|in:credit,cash',
            'refund_account_id' => 'required_if:return_type,cash|nullable|exists:chart_of_accounts,id',
            'items'             => 'required|array|min:1',
            'items.*.product_id'  => 'required|exists:products,id',
            'items.*.quantity'    => 'required|numeric|min:0.01',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            /** @var PurchaseBill|null $bill */
            /** @var PurchaseBill $bill */
            $bill = PurchaseBill::query()->with('supplier')->findOrFail($request->purchase_bill_id);
            
            // Generate Return Number: PR-2024-00001
            $year = date('Y', strtotime($request->return_date));
            /** @var PurchaseReturn|null $lastReturn */
            $lastReturn = PurchaseReturn::query()->whereYear('return_date', $year)->latest()->first();
            $nextNumber = $lastReturn ? ((int)substr($lastReturn->return_number, -5) + 1) : 1;
            $returnNumber = 'PR-' . $year . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

            $subtotal = collect($request->items)->sum(fn(array $i) => $i['quantity'] * $i['unit_price']);

            // 1. Create Purchase Return
            $purchaseReturn = PurchaseReturn::query()->create([
                'return_number'     => $returnNumber,
                'purchase_bill_id'  => $bill->id,
                'supplier_id'       => $bill->supplier_id,
                'branch_id'         => $bill->branch_id,
                'return_date'       => $request->return_date,
                'reason'            => $request->reason,
                'subtotal'          => $subtotal,
                'tax'               => 0,
                'total_amount'      => $subtotal,
                'status'            => 'approved',
                'return_type'       => $request->return_type,
                'refund_account_id' => $request->return_type === 'cash' ? $request->refund_account_id : null,
                'created_by'        => Auth::id(),
            ]);

            // 2. Create Journal Entry (Debit Note Logic) — derived from the
            // return's own unique id to avoid racing/colliding with other
            // concurrent or cross-company inserts (see storeBill's note above).
            $entryNumber = 'EN-PR-' . date('Y') . '-' . str_pad($purchaseReturn->id, 6, '0', STR_PAD_LEFT);

            $cid = Auth::user()->company_id;

            $entry = JournalEntry::query()->create([
                'entry_number' => $entryNumber,
                'date' => $request->return_date,
                'reference' => $returnNumber,
                'description' => "Purchase Return for Bill #" . $bill->bill_number,
                'total_amount' => $subtotal,
                'status' => 'posted',
                'company_id' => $cid,
                'branch_id' => $bill->branch_id,
                'created_by' => Auth::id(),
            ]);

            // Credit Inventory (Decrease asset on return) — common to both return types
            $inventoryAccount = Account::query()->where('company_id', $cid)->where('code', '1150')->first()
                ?: Account::query()->where('company_id', $cid)->where('type', 'inventory')->first()
                ?: Account::query()->where('company_id', $cid)->where('name', 'like', '%Inventory%')->first();

            if ($inventoryAccount) {
                JournalItem::query()->create([
                    'journal_entry_id' => $entry->id,
                    'account_id'       => $inventoryAccount->id,
                    'company_id'       => $cid,
                    'debit'            => 0,
                    'credit'           => $subtotal,
                    'description'      => 'Inventory credit on purchase return ' . $returnNumber,
                ]);
            }

            if ($request->return_type === 'credit') {
                // CREDIT RETURN (Debit Note): Debit AP → reduces what we owe the supplier
                $apAccount = ($bill->supplier && $bill->supplier->account_id)
                    ? Account::query()->find($bill->supplier->account_id)
                    : (Account::query()->where('company_id', $cid)->where('code', '2110')->first()
                       ?: Account::query()->where('company_id', $cid)->where('name', 'like', '%Accounts Payable%')->first());

                if ($apAccount) {
                    JournalItem::query()->create([
                        'journal_entry_id' => $entry->id,
                        'account_id'       => $apAccount->id,
                        'company_id'       => $cid,
                        'debit'            => $subtotal,
                        'credit'           => 0,
                        'description'      => 'AP debit on purchase return ' . $returnNumber,
                    ]);
                }

                // Reduce supplier outstanding balance
                if ($bill->supplier) {
                    $bill->supplier->decrement('amount_balance', $subtotal);
                }
            } else {
                // CASH REFUND: Debit Cash/Bank → supplier pays cash back to us
                $refundAccount = Account::query()->find($request->refund_account_id);

                if ($refundAccount) {
                    JournalItem::query()->create([
                        'journal_entry_id' => $entry->id,
                        'account_id'       => $refundAccount->id,
                        'company_id'       => $cid,
                        'debit'            => $subtotal,
                        'credit'           => 0,
                        'description'      => 'Cash refund received on purchase return ' . $returnNumber,
                    ]);
                }
                // Supplier balance unchanged: the original bill was already paid in cash
            }

            foreach ($request->items as $itemData) {
                // 3. Create Return Items
                $returnItem = PurchaseReturnItem::query()->create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'purchase_bill_item_id' => $itemData['bill_item_id'] ?? null,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'subtotal' => $itemData['quantity'] * $itemData['unit_price'],
                ]);

                // 4. Update Stock & Record Movement
                $stock = ProductStock::query()->where('product_id', $itemData['product_id'])
                    ->where('branch_id', $bill->branch_id)
                    ->first();

                if ($stock) {
                    $stock->decrement('quantity', $itemData['quantity']);
                    
                    StockMovement::query()->create([
                        'product_id' => $itemData['product_id'],
                        'branch_id' => $bill->branch_id,
                        'quantity' => -$itemData['quantity'],
                        'type' => 'purchase_return',
                        'reference_id' => $returnItem->id,
                        'reference_type' => PurchaseReturnItem::class,
                        'balance_after' => $stock->quantity,
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Return processed successfully', 'return_number' => $returnNumber]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Processing Error: ' . $e->getMessage()], 500);
        }
    }

    public function viewReturn($id)
    {
        $return = PurchaseReturn::query()->with(['supplier', 'bill', 'items.product', 'branch', 'user'])->findOrFail($id);
        /** @var Company|null $company */
        $company = Company::find(auth()->user()->company_id);
        return \View::file(
            resource_path('views/frontend/purchase/purchase_return.pdf.blade.php'),
            compact('return', 'company')
        );
    }

    public function downloadReturnPdf($id)
    {
        /** @var PurchaseReturn $return */
        $return = PurchaseReturn::query()->with(['supplier', 'bill', 'items.product', 'branch', 'user'])->findOrFail($id);
        /** @var Company|null $company */
        $company = Company::find(auth()->user()->company_id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('frontend.purchase.pdf_purchase_return', compact('return', 'company'));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('Purchase-Return-' . $return->return_number . '.pdf');
    }

    public function destroyReturn($id)
    {
        try {
            DB::beginTransaction();
            /** @var PurchaseReturn $return */
            $return = PurchaseReturn::query()->with(['items', 'supplier'])->findOrFail($id);

            // 1. Restore stock
            foreach ($return->items as $item) {
                $stock = ProductStock::query()->where('product_id', $item->product_id)
                    ->where('branch_id', $return->branch_id)
                    ->first();

                if ($stock) {
                    $stock->increment('quantity', $item->quantity);
                }
            }

            // 2. Reverse supplier balance — only for credit returns (cash refunds never touched it)
            if (($return->return_type ?? 'credit') === 'credit' && $return->supplier) {
                $return->supplier->increment('amount_balance', $return->total_amount);
            }

            // 3. Reverse journal entry (delete items first so observer fires)
            $entry = JournalEntry::query()->where('reference', $return->return_number)->first();
            if ($entry) {
                foreach ($entry->items as $jitem) {
                    $jitem->delete();
                }
                $entry->delete();
            }

            $return->delete();
            DB::commit();
            return redirect()->back()->with('success', 'Return record deleted and balances reversed.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error deleting return: ' . $e->getMessage());
        }
    }

    public function updateReturn(Request $request, $id)
    {
        $request->validate([
            'return_date' => 'required|date',
            'reason'      => 'required|string|max:500',
            'status'      => 'required|in:pending,approved,refunded,rejected',
            'due_date'    => 'nullable|date',
            'notes'       => 'nullable|string|max:1000',
        ]);

        try {
            /** @var PurchaseReturn $return */
            $return = PurchaseReturn::query()->findOrFail($id);
            $return->update([
                'return_date' => $request->return_date,
                'reason'      => $request->reason,
                'status'      => $request->status,
                'due_date'    => $request->due_date,
                'notes'       => $request->notes,
            ]);
            return response()->json(['success' => true, 'message' => 'Return updated successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function expense(Request $request)
    {
        /** @var Company|null $company */
        $company = Company::find(auth()->user()->company_id);
        $currSymbols = ['SAR' => '﷼', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AED' => 'د.إ', 'KWD' => 'د.ك'];
        $sym = $currSymbols[$company->currency ?? ''] ?? ($company->currency ?? '$');
        $curr = $sym;
        $suppliers = Supplier::query()->get();
        $expenseAccounts = Account::query()->where('category', 'expenses')
                                               ->where('type', '!=', 'parent')
                                               ->orderBy('name')
                                               ->get();

        $query = PurchaseExpense::query()->with(['account', 'supplier', 'branch', 'purchase', 'bankAccount']);

        // Quick Search Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('expense_name', 'like', "%$search%")
                  ->orWhereHas('purchase', function($pq) use ($search) {
                      $pq->where('bill_number', 'like', "%$search%");
                  })
                  ->orWhereHas('supplier', function($sq) use ($search) {
                      $sq->where('name', 'like', "%$search%");
                  });
            });
        }

        // Date Range Filters
        if ($request->filled('from_date')) {
            $query->whereDate('expense_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('expense_date', '<=', $request->to_date);
        }

        // Dropdown Filters
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $expenses = $query->orderBy('expense_date', 'desc')->paginate(10);

        $bills = PurchaseBill::query()->get();
        $branches = Branch::query()->get();
        $bankAccounts = Account::query()->where('type', 'cash')
                                           ->where(fn($q) => $q->where('name', 'like', '%Cash on Hand%')
                                                               ->orWhere('name', 'like', '%Cash in Hand%'))
                                           ->where('is_active', 1)
                                           ->orderBy('name')
                                           ->get(['id', 'name', 'code', 'branch_id']);

        return view('frontend.purchase.purchase_expenses', compact('curr', 'suppliers', 'expenseAccounts', 'expenses', 'bills', 'branches', 'bankAccounts'));
    }

    public function destroyBill($id)
    {
        DB::beginTransaction();
        try {
            /** @var PurchaseBill $bill */
            $bill = PurchaseBill::query()->with('items')->findOrFail($id);

            // 1. Reverse Stock
            foreach ($bill->items as $item) {
                if ($item->product_id) {
                    $stock = ProductStock::query()->where([
                        'product_id' => $item->product_id,
                        'branch_id' => $bill->branch_id,
                    ])->first();
                    if ($stock) {
                        $stock->decrement('quantity', $item->quantity);
                    }
                }
            }

            // 2. Reverse Supplier Balance (net outstanding that was added on bill creation)
            if ($bill->supplier) {
                $bill->supplier->decrement('amount_balance', $bill->total_amount - $bill->paid_amount);
            }

            // 3. Reverse Accounting
            $entry = JournalEntry::query()->where('reference', $bill->bill_number)->first();
            if ($entry) {
                foreach ($entry->items as $jitem) {
                    $jitem->delete();
                }
                $entry->delete();
            }

            // 3b. Reverse the immediate-payment side effect (see updateBill's
            // matching comment) — deleting a bill that had a payment recorded
            // against it must also undo that payment's own cash/bank journal
            // entry, not just the bill's own inventory/payable entry.
            $paymentEntry = JournalEntry::query()->where('reference', 'PAY-' . $bill->bill_number)->first();
            if ($paymentEntry) {
                foreach ($paymentEntry->items as $payItem) {
                    $payItem->delete();
                }
                $paymentEntry->delete();
            }

            $paymentDetail = SupplierPaymentDetail::query()->where('purchase_bill_id', $bill->id)->first();
            if ($paymentDetail) {
                /** @var SupplierPayment|null $payment */
                $payment = $paymentDetail->payment;
                $paymentDetail->delete();
                $payment?->delete();
            }

            // 4. Delete Bill Items
            $bill->items()->delete();
            
            // 5. Delete Bill
            $bill->delete();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Purchase Bill deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to delete bill: ' . $e->getMessage()]);
        }
    }

    public function showBill($id)
    {
        /** @var PurchaseBill|null $bill */
        $bill = PurchaseBill::query()->with(['supplier', 'branch', 'items.product'])->findOrFail($id);
        return response()->json($bill);
    }

    public function exportBill()
    {
        $fileName = 'purchase_bills_export_' . date('Y-m-d_H:i:s') . '.csv';
        $bills = PurchaseBill::query()->with('supplier')->latest()->get();

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('Bill Number', 'Date', 'Supplier', 'Items', 'Subtotal', 'VAT', 'Total Amount', 'Paid Amount', 'Status');

        $callback = function() use($bills, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($bills as $bill) {
                fputcsv($file, array(
                    $bill->bill_number,
                    $bill->bill_date,
                    $bill->supplier->name ?? '-',
                    $bill->items->count(),
                    number_format($bill->subtotal, 2, '.', ''),
                    number_format($bill->vat, 2, '.', ''),
                    number_format($bill->total_amount, 2, '.', ''),
                    number_format($bill->paid_amount, 2, '.', ''),
                    $bill->status
                ));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function storeExpense(Request $request)
    {
        $request->validate([
            'purchase_id'        => 'nullable|exists:purchase_bills,id',
            'expense_account_id' => 'required|exists:chart_of_accounts,id',
            'expense_name'       => 'required|string|max:255',
            'amount'             => 'required|numeric|min:0',
            'expense_date'       => 'required|date',
            'supplier_id'        => 'nullable|exists:suppliers,id',
            'branch_id'          => 'nullable|exists:branches,id',
            'bank_account_id'    => 'required|exists:chart_of_accounts,id',
        ]);

        try {
            DB::beginTransaction();

            $data = $request->all();
            $data['created_by'] = auth()->id();
            $data['status'] = 'paid';

            $expense = PurchaseExpense::query()->create($data);

            // Create Journal Entry for the expense
            $cid = auth()->user()->company_id;
            /** @var JournalEntry $journalEntry */
            $journalEntry = JournalEntry::query()->create([
                // Derived from the expense's own unique id rather than a
                // count-based sequence, which races under concurrent
                // requests and isn't scoped per company.
                'entry_number' => 'JE-EXP-' . date('Ymd') . '-' . str_pad($expense->id, 4, '0', STR_PAD_LEFT),
                'date' => $request->expense_date,
                // "PE" (Purchase Expense) distinguishes this from the general
                // Expense model's "EXP-GE-<id>" reference — both models start
                // their own id sequence from 1, so a plain "EXP-<id>" lookup
                // could otherwise match the wrong expense's journal entry.
                'reference' => 'EXP-PE-' . $expense->id,
                'description' => 'Purchase Expense: ' . $request->expense_name,
                'status' => 'posted',
                'total_amount' => $request->amount,
                'company_id' => $cid,
                'created_by' => auth()->id(),
                'branch_id' => $request->branch_id,
            ]);

            // Item 1: Debit Expense Account
            JournalItem::query()->create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $request->expense_account_id,
                'company_id' => $cid,
                'description' => $request->expense_name,
                'debit' => $request->amount,
                'credit' => 0,
            ]);

            // Item 2: Credit Bank/Cash Account
            JournalItem::query()->create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $request->bank_account_id,
                'company_id' => $cid,
                'description' => $request->expense_name,
                'debit' => 0,
                'credit' => $request->amount,
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Purchase expense recorded and accounts updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error recording expense: ' . $e->getMessage());
        }
    }

    public function viewExpense($id)
    {
        $expense = PurchaseExpense::with(['account', 'supplier', 'branch', 'bankAccount', 'purchase', 'creator'])
                    ->findOrFail($id);

        $company_profile = Company::find(auth()->user()->company_id);

        return view('frontend.purchase.purchase_expense_pdf_receipt', compact('expense', 'company_profile'));
    }

    public function downloadExpensePdf($id)
    {
        $expense = PurchaseExpense::with(['account', 'supplier', 'branch', 'bankAccount', 'purchase', 'creator'])
                    ->findOrFail($id);

        $company_profile = Company::find(auth()->user()->company_id);

        $pdf = Pdf::loadView('frontend.expense.pdf_expense_receipt_v2', compact('expense', 'company_profile'))
                  ->setPaper('a4', 'portrait');

        return $pdf->stream('Expense_Receipt_EXP-' . str_pad($expense->id, 4, '0', STR_PAD_LEFT) . '.pdf');
    }

    public function updateExpense(Request $request, $id)
    {
        $request->validate([
            'expense_name'       => 'required|string|max:255',
            'purchase_id'        => 'required|exists:purchase_bills,id',
            'expense_account_id' => 'required|exists:chart_of_accounts,id',
            'amount'             => 'required|numeric|min:0',
            'expense_date'       => 'required|date',
            'supplier_id'        => 'required|exists:suppliers,id',
            'branch_id'          => 'required|exists:branches,id',
            'bank_account_id'    => 'required|exists:chart_of_accounts,id',
        ]);

        try {
            DB::beginTransaction();

            $expense = PurchaseExpense::findOrFail($id);

            // Reverse old journal entries (delete items individually so observer fires)
            $oldEntry = JournalEntry::query()->where('reference', 'EXP-PE-' . $id)->first();
            if ($oldEntry) {
                foreach ($oldEntry->items as $oldItem) {
                    $oldItem->delete();
                }
                $oldEntry->delete();
            }

            $expense->update([
                'expense_name'       => $request->expense_name,
                'purchase_id'        => $request->purchase_id,
                'expense_account_id' => $request->expense_account_id,
                'amount'             => $request->amount,
                'expense_date'       => $request->expense_date,
                'supplier_id'        => $request->supplier_id,
                'branch_id'          => $request->branch_id,
                'bank_account_id'    => $request->bank_account_id,
                'description'        => $request->description,
            ]);

            // Re-create journal entry
            $cid = auth()->user()->company_id;
            /** @var JournalEntry $journalEntry */
            $journalEntry = JournalEntry::query()->create([
                'entry_number' => 'JE-EXP-' . date('Ymd') . '-' . str_pad($expense->id, 4, '0', STR_PAD_LEFT),
                'date'         => $request->expense_date,
                'reference'    => 'EXP-PE-' . $id,
                'description'  => 'Purchase Expense: ' . $request->expense_name,
                'status'       => 'posted',
                'total_amount' => $request->amount,
                'company_id'   => $cid,
                'created_by'   => auth()->id(),
                'branch_id'    => $request->branch_id,
            ]);

            // Debit Expense Account
            JournalItem::query()->create([
                'journal_entry_id' => $journalEntry->id,
                'account_id'       => $request->expense_account_id,
                'company_id'       => $cid,
                'description'      => $request->expense_name,
                'debit'            => $request->amount,
                'credit'           => 0,
            ]);

            // Credit Bank/Cash Account
            JournalItem::query()->create([
                'journal_entry_id' => $journalEntry->id,
                'account_id'       => $request->bank_account_id,
                'company_id'       => $cid,
                'description'      => $request->expense_name,
                'debit'            => 0,
                'credit'           => $request->amount,
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Expense updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error updating expense: ' . $e->getMessage());
        }
    }

    public function destroyExpense($id)
    {
        try {
            DB::beginTransaction();

            $expense = PurchaseExpense::findOrFail($id);

            // Delete journal items individually so the observer reverses account balances
            $entry = JournalEntry::query()->where('reference', 'EXP-PE-' . $id)->first();
            if ($entry) {
                foreach ($entry->items as $item) {
                    $item->delete();
                }
                $entry->delete();
            }

            $expense->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Expense deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error deleting expense: ' . $e->getMessage());
        }
    }
}
