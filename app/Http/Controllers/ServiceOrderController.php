<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\ServiceSchedule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceOrderController extends Controller
{
    // ─── INDEX ──────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = ServiceOrder::query()->with('customer', 'employees');

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($s) => $s->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->priority) {
            $query->where('priority', $request->priority);
        }

        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->date_from) {
            $query->whereDate('scheduled_date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('scheduled_date', '<=', $request->date_to);
        }

        $orders = $query->latest()->paginate(20);

        $companyId = Auth::user()->company_id;
        $totalOpen      = ServiceOrder::whereNotIn('status', ['completed', 'cancelled'])->count();
        $completedMonth = ServiceOrder::where('status', 'completed')
                            ->whereMonth('completed_date', now()->month)
                            ->whereYear('completed_date', now()->year)
                            ->count();
        $overdueCount   = ServiceOrder::whereDate('scheduled_date', '<', now())
                            ->whereNotIn('status', ['completed', 'cancelled'])
                            ->count();
        $revenueMonth   = SalesOrder::whereHas('serviceOrder')
                            ->whereMonth('invoice_date', now()->month)
                            ->whereYear('invoice_date', now()->year)
                            ->sum('total_amount');

        $customers = Customer::orderBy('name')->get();

        return view('frontend.services.service_orders', compact(
            'orders', 'customers', 'totalOpen', 'completedMonth', 'overdueCount', 'revenueMonth'
        ));
    }

    // ─── CREATE ──────────────────────────────────────────────────────────────

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $employees = Employee::where('status', 'active')->orderBy('full_name')->get();
        $services  = Product::where('product_type', 'service')->orderBy('product_name')->get();
        $branches  = Branch::orderBy('name')->get();
        $orderNumber = ServiceOrder::nextOrderNumber(Auth::user()->company_id);

        return view('frontend.services.add_service_order', compact(
            'customers', 'employees', 'services', 'branches', 'orderNumber'
        ));
    }

    // ─── STORE ──────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate([
            'customer_id'    => 'required|exists:customers,id',
            'title'          => 'required|string|max:255',
            'items'          => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity'    => 'required|numeric|min:0.01',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $items    = $request->items ?? [];
            $subtotal = 0;
            foreach ($items as $item) {
                $disc = (float)($item['discount_pct'] ?? 0);
                $subtotal += (float)$item['quantity'] * (float)$item['unit_price'] * (1 - $disc / 100);
            }
            $discountAmount = (float)($request->discount_amount ?? 0);
            $taxAmount      = (float)($request->tax_amount ?? 0);
            $total          = $subtotal - $discountAmount + $taxAmount;

            $order = ServiceOrder::create([
                'order_number'    => ServiceOrder::nextOrderNumber(Auth::user()->company_id),
                'customer_id'     => $request->customer_id,
                'branch_id'       => $request->branch_id,
                'status'          => $request->status ?? 'pending',
                'priority'        => $request->priority ?? 'normal',
                'title'           => $request->title,
                'description'     => $request->description,
                'scheduled_date'  => $request->scheduled_date,
                'subtotal'        => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount'      => $taxAmount,
                'total_amount'    => $total,
                'notes'           => $request->notes,
                'created_by'      => Auth::id(),
            ]);

            foreach ($items as $item) {
                $disc  = (float)($item['discount_pct'] ?? 0);
                $itemTotal = (float)$item['quantity'] * (float)$item['unit_price'] * (1 - $disc / 100);
                ServiceOrderItem::create([
                    'service_order_id' => $order->id,
                    'product_id'       => $item['product_id'] ?? null,
                    'description'      => $item['description'],
                    'quantity'         => $item['quantity'],
                    'unit_price'       => $item['unit_price'],
                    'discount_pct'     => $disc,
                    'total'            => $itemTotal,
                ]);
            }

            if (!empty($request->employee_ids)) {
                foreach ($request->employee_ids as $empId) {
                    $order->employees()->attach($empId, ['role' => 'technician', 'assigned_at' => now()]);
                }
            }

            AuditLog::log('Services', "Created service order #{$order->order_number}", 'CREATE');
        });

        return redirect()->route('service-orders.index')->with('success', 'Service order created successfully.');
    }

    // ─── SHOW ────────────────────────────────────────────────────────────────

    public function show($id)
    {
        $order = ServiceOrder::with('customer', 'items.product', 'employees', 'salesOrder', 'creator', 'schedule')->findOrFail($id);
        return view('frontend.services.service_order_detail', compact('order'));
    }

    // ─── EDIT ────────────────────────────────────────────────────────────────

    public function edit($id)
    {
        $order     = ServiceOrder::with('items', 'employees')->findOrFail($id);
        $customers = Customer::orderBy('name')->get();
        $employees = Employee::where('status', 'active')->orderBy('full_name')->get();
        $services  = Product::where('product_type', 'service')->orderBy('product_name')->get();
        $branches  = Branch::orderBy('name')->get();

        return view('frontend.services.add_service_order', compact(
            'order', 'customers', 'employees', 'services', 'branches'
        ));
    }

    // ─── UPDATE ──────────────────────────────────────────────────────────────

    public function update(Request $request, $id)
    {
        $order = ServiceOrder::findOrFail($id);

        $request->validate([
            'customer_id'         => 'required|exists:customers,id',
            'title'               => 'required|string|max:255',
            'items'               => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity'    => 'required|numeric|min:0.01',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $order) {
            $items    = $request->items ?? [];
            $subtotal = 0;
            foreach ($items as $item) {
                $disc     = (float)($item['discount_pct'] ?? 0);
                $subtotal += (float)$item['quantity'] * (float)$item['unit_price'] * (1 - $disc / 100);
            }
            $discountAmount = (float)($request->discount_amount ?? 0);
            $taxAmount      = (float)($request->tax_amount ?? 0);
            $total          = $subtotal - $discountAmount + $taxAmount;

            $order->update([
                'customer_id'     => $request->customer_id,
                'branch_id'       => $request->branch_id,
                'priority'        => $request->priority ?? 'normal',
                'title'           => $request->title,
                'description'     => $request->description,
                'scheduled_date'  => $request->scheduled_date,
                'subtotal'        => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount'      => $taxAmount,
                'total_amount'    => $total,
                'notes'           => $request->notes,
            ]);

            $order->items()->delete();
            foreach ($items as $item) {
                $disc      = (float)($item['discount_pct'] ?? 0);
                $itemTotal = (float)$item['quantity'] * (float)$item['unit_price'] * (1 - $disc / 100);
                ServiceOrderItem::create([
                    'service_order_id' => $order->id,
                    'product_id'       => $item['product_id'] ?? null,
                    'description'      => $item['description'],
                    'quantity'         => $item['quantity'],
                    'unit_price'       => $item['unit_price'],
                    'discount_pct'     => $disc,
                    'total'            => $itemTotal,
                ]);
            }

            $order->employees()->sync([]);
            if (!empty($request->employee_ids)) {
                foreach ($request->employee_ids as $empId) {
                    $order->employees()->attach($empId, ['role' => 'technician', 'assigned_at' => now()]);
                }
            }

            AuditLog::log('Services', "Updated service order #{$order->order_number}", 'UPDATE');
        });

        return redirect()->route('service-orders.show', $id)->with('success', 'Service order updated.');
    }

    // ─── DESTROY ─────────────────────────────────────────────────────────────

    public function destroy($id)
    {
        $order = ServiceOrder::findOrFail($id);

        if ($order->sales_order_id) {
            return back()->with('error', 'Cannot delete an invoiced service order. Delete the invoice first.');
        }

        DB::transaction(function () use ($order) {
            AuditLog::log('Services', "Deleted service order #{$order->order_number}", 'DELETE');
            $order->employees()->detach();
            $order->items()->delete();
            $order->delete();
        });

        return redirect()->route('service-orders.index')->with('success', 'Service order deleted.');
    }

    // ─── STATUS ──────────────────────────────────────────────────────────────

    public function updateStatus(Request $request, $id)
    {
        $order = ServiceOrder::findOrFail($id);
        $request->validate(['status' => 'required|in:pending,confirmed,in_progress,completed,cancelled']);

        $data = ['status' => $request->status];
        if ($request->status === 'completed') {
            $data['completed_date'] = now()->toDateString();
        }

        $order->update($data);
        AuditLog::log('Services', "Status of #{$order->order_number} changed to {$request->status}", 'UPDATE');

        return back()->with('success', 'Status updated to ' . ucfirst(str_replace('_', ' ', $request->status)) . '.');
    }

    // ─── GENERATE INVOICE ────────────────────────────────────────────────────

    public function generateInvoice(Request $request, $id)
    {
        $order = ServiceOrder::with('items', 'customer')->findOrFail($id);

        if ($order->sales_order_id) {
            return back()->with('error', 'Invoice already generated for this order.');
        }

        if (!in_array($order->status, ['confirmed', 'in_progress', 'completed'])) {
            return back()->with('error', 'Only confirmed, in-progress, or completed orders can be invoiced.');
        }

        $request->validate([
            'payment_method'     => 'required|string',
            'payment_account_id' => 'nullable|exists:chart_of_accounts,id',
            'paid_amount'        => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $order) {
            $comp        = Auth::user()->company_id;
            $paidAmount  = (float)($request->paid_amount ?? 0);
            $dueAmount   = max(0, $order->total_amount - $paidAmount);
            $status      = $dueAmount <= 0 ? 'completed' : ($paidAmount > 0 ? 'partial' : 'pending');

            $invoiceNo = 'INV-SRV-' . strtoupper(str_pad($order->id, 5, '0', STR_PAD_LEFT));

            $salesOrder = SalesOrder::create([
                'company_id'         => $comp,
                'branch_id'          => $order->branch_id,
                'invoice_no'         => $invoiceNo,
                'invoice_date'       => now()->toDateString(),
                'customer_id'        => $order->customer_id,
                'subtotal'           => $order->subtotal,
                'discount'           => $order->discount_amount,
                'tax'                => $order->tax_amount,
                'total_amount'       => $order->total_amount,
                'paid_amount'        => $paidAmount,
                'due_amount'         => $dueAmount,
                'payment_method'     => $request->payment_method,
                'payment_account_id' => $request->payment_account_id,
                'status'             => $status,
                'notes'              => "Service Order #{$order->order_number}: {$order->title}",
                'created_by'         => Auth::id(),
            ]);

            foreach ($order->items as $item) {
                SalesOrderItem::create([
                    'company_id'     => $comp,
                    'sales_order_id' => $salesOrder->id,
                    'product_id'     => $item->product_id,
                    'product_name'   => $item->description,
                    'quantity'       => $item->quantity,
                    'unit_price'     => $item->unit_price,
                    'discount'       => $item->discount_pct,
                    'total'          => $item->total,
                    'purchase_price' => 0,
                ]);
            }

            // Update customer balance
            if ($dueAmount > 0 && $order->customer) {
                $order->customer->increment('amount_balance', $dueAmount);
            }

            // Post GL journal entry
            $this->createServiceAccountingEntry($salesOrder, $order);

            $order->update([
                'sales_order_id' => $salesOrder->id,
                'status'         => 'completed',
                'completed_date' => now()->toDateString(),
            ]);

            AuditLog::log('Services', "Invoice #{$invoiceNo} generated for service order #{$order->order_number}", 'CREATE');
        });

        return redirect()->route('service-orders.show', $id)->with('success', 'Invoice generated successfully.');
    }

    // ─── PDF ─────────────────────────────────────────────────────────────────

    public function pdf($id)
    {
        $order   = ServiceOrder::with('customer', 'items.product', 'employees', 'creator')->findOrFail($id);
        $company = Auth::user()->company;
        $symbol  = '$';

        $pdf = Pdf::loadView('frontend.services.service_order_pdf', compact('order', 'company', 'symbol'))
                  ->setPaper('a4', 'portrait');

        return $pdf->download("service-order-{$order->order_number}.pdf");
    }

    // ─── ACCOUNTING ──────────────────────────────────────────────────────────

    private function createServiceAccountingEntry(SalesOrder $salesOrder, ServiceOrder $serviceOrder)
    {
        $comp = $salesOrder->company_id;

        // Service Income (4120)
        $serviceIncomeAccount = Account::where('company_id', $comp)->where('code', '4120')->first()
            ?: Account::where('company_id', $comp)->where('name', 'like', '%Service Income%')->first();
        if (!$serviceIncomeAccount) {
            $serviceIncomeAccount = Account::create([
                'company_id' => $comp, 'code' => '4120', 'name' => 'Service Income',
                'category' => 'revenue', 'type' => 'revenue', 'balance' => 0,
            ]);
        }

        // Accounts Receivable (1140)
        $receivableAccount = Account::where('company_id', $comp)->where('code', '1140')->first()
            ?: Account::where('company_id', $comp)->where('name', 'like', '%Receivable%')->first();
        if (!$receivableAccount) {
            $receivableAccount = Account::create([
                'company_id' => $comp, 'code' => '1140', 'name' => 'Accounts Receivable',
                'category' => 'assets', 'type' => 'receivable', 'balance' => 0,
            ]);
        }

        // Tax account
        $taxAccount = Account::where('company_id', $comp)
            ->where(fn($q) => $q->where('name', 'like', '%VAT%')->orWhere('name', 'like', '%Tax%'))
            ->first();

        // Cash/Bank for payment
        $paymentAccount = null;
        if ($salesOrder->paid_amount > 0) {
            if ($salesOrder->payment_account_id) {
                $paymentAccount = Account::where('company_id', $comp)->find($salesOrder->payment_account_id);
            }
            if (!$paymentAccount) {
                $paymentAccount = Account::where('company_id', $comp)->where('code', '1110')->first()
                    ?: Account::where('company_id', $comp)->where('name', 'like', '%Cash%')->first();
            }
        }

        $entry = JournalEntry::create([
            'company_id'   => $comp,
            'branch_id'    => $salesOrder->branch_id,
            'entry_number' => 'JE-SRV-' . $salesOrder->id . '-' . strtoupper(substr(uniqid(), -5)),
            'date'         => $salesOrder->invoice_date,
            'reference'    => $salesOrder->invoice_no,
            'description'  => 'Service: ' . $serviceOrder->title . ' — ' . ($salesOrder->customer->name ?? 'Customer'),
            'status'       => 'posted',
            'total_amount' => $salesOrder->total_amount,
            'created_by'   => Auth::id(),
        ]);

        // DEBITS
        if ($salesOrder->paid_amount > 0 && $paymentAccount) {
            JournalItem::create([
                'company_id' => $comp, 'journal_entry_id' => $entry->id,
                'account_id' => $paymentAccount->id,
                'debit' => $salesOrder->paid_amount, 'credit' => 0,
                'description' => 'Payment received',
            ]);
        }

        if ($salesOrder->due_amount > 0) {
            JournalItem::create([
                'company_id' => $comp, 'journal_entry_id' => $entry->id,
                'account_id' => $receivableAccount->id,
                'debit' => $salesOrder->due_amount, 'credit' => 0,
                'description' => 'Amount receivable',
            ]);
        }

        // CREDITS
        $netRevenue = $salesOrder->total_amount - $salesOrder->tax;
        JournalItem::create([
            'company_id' => $comp, 'journal_entry_id' => $entry->id,
            'account_id' => $serviceIncomeAccount->id,
            'debit' => 0, 'credit' => $netRevenue,
            'description' => 'Service income: ' . $serviceOrder->title,
        ]);

        if ($salesOrder->tax > 0 && $taxAccount) {
            JournalItem::create([
                'company_id' => $comp, 'journal_entry_id' => $entry->id,
                'account_id' => $taxAccount->id,
                'debit' => 0, 'credit' => $salesOrder->tax,
                'description' => 'Tax on service',
            ]);
        }
    }
}
