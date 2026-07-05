<?php

namespace App\Http\Controllers;
use App\Models\SalesOrderItem;

use Illuminate\Http\Request;
use App\Models\SalesOrder;
use App\Models\PurchaseBill;
use App\Models\Expense;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Role;
use App\Models\Account;
use App\Models\Category;
use App\Models\JournalItem;
use App\Models\PaymentIn;
use App\Models\SupplierPayment;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function dashboard()
    {
        $cid            = auth()->user()->company_id;
        $totalSales     = SalesOrder::query()->where('company_id', $cid)->sum('total_amount');
        $totalPurchases = PurchaseBill::query()->where('company_id', $cid)->sum('total_amount');
        
        // Calculate COGS from ledger
        $totalCogs = DB::table('journal_items')
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.company_id', auth()->user()->company_id)
            ->whereIn('journal_items.account_id', function($q) {
                $q->select('id')->from('chart_of_accounts')
                  ->whereIn('code', ['5020', '5100', '5110', '5120', '5130'])
                  ->orWhere('name', 'LIKE', '%Cost%Goods%')
                  ->orWhere('name', 'LIKE', '%Product Cost%');
            })
            ->sum('journal_items.debit');

        $totalExpenses  = Expense::query()->where('company_id', $cid)->sum('amount');
        $netProfit      = $totalSales - $totalCogs - $totalExpenses;
        $reportsCount   = AuditLog::query()->where('module', 'Reports')->count() ?: 0;

        return view('frontend.report.all_reports', compact(
            'totalSales', 'totalPurchases', 'netProfit', 'reportsCount'
        ));
    }

    public function salesReport(Request $request)
    {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = SalesOrder::query()->with(['customer', 'items.product']);

        if ($request->from_date)   $query->whereDate('invoice_date', '>=', $request->from_date);
        if ($request->to_date)     $query->whereDate('invoice_date', '<=', $request->to_date);
        if ($request->customer_id) $query->where('customer_id', $request->customer_id);
        if ($request->status)      $query->where('status', $request->status);
        if ($request->branch_id)   $query->where('branch_id', $request->branch_id);

        if ($request->search) {
            $searchTerm = $request->search;
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            /** @disregard P0406 */
            $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($searchTerm) {
                $q->where('invoice_no', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('customer', fn(\Illuminate\Database\Eloquent\Builder $cq) => $cq->where('name', 'like', '%' . $searchTerm . '%'));

                if (stripos('Walk-in Customer', $searchTerm) !== false || stripos('Cash', $searchTerm) !== false) {
                    $q->orWhereNull('customer_id');
                }
            });
        }

        $totalsQuery    = clone $query;
        $totalSales     = $totalsQuery->sum('total_amount');
        $totalTax       = $totalsQuery->sum('tax');
        $totalInvoices  = $totalsQuery->count();
        $avgInvoice     = $totalInvoices > 0 ? $totalSales / $totalInvoices : 0;
        $totalOutstanding = $totalsQuery->sum('due_amount');
        $paidAmount       = $totalsQuery->sum('paid_amount');
        $totalCustomers   = $totalsQuery->distinct('customer_id')->count('customer_id');

        $totalItems = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->whereIn('sales_order_items.sales_order_id', (clone $totalsQuery)->pluck('id'))
            ->where('sales_orders.company_id', auth()->user()->company_id)
            ->sum('sales_order_items.quantity');

        $sales     = $query->latest()->paginate(10);
        $customers = Customer::query()->orderBy('name')->get();
        $products  = Product::query()->orderBy('product_name')->get();
        $branches  = Branch::query()->orderBy('name')->get();

        return view('frontend.report.sale_reports', compact(
            'sales', 'totalSales', 'totalTax', 'totalInvoices', 'avgInvoice',
            'totalOutstanding', 'paidAmount', 'totalCustomers', 'totalItems',
            'customers', 'products', 'branches'
        ));
    }

    public function purchaseReport(Request $request)
    {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = PurchaseBill::query()->with(['supplier', 'items', 'branch']);

        if ($request->from_date)   $query->whereDate('bill_date', '>=', $request->from_date);
        if ($request->to_date)     $query->whereDate('bill_date', '<=', $request->to_date);
        if ($request->supplier_id) $query->where('supplier_id', $request->supplier_id);
        if ($request->status)      $query->where('status', $request->status);
        if ($request->branch_id)   $query->where('branch_id', $request->branch_id);

        if ($request->search) {
            $searchTerm = $request->search;
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            /** @disregard P0406 */
            $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($searchTerm) {
                $q->where('bill_number', 'like', '%' . $searchTerm . '%')
                  ->orWhere('supplier_invoice_no', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('supplier', fn(\Illuminate\Database\Eloquent\Builder $cq) => $cq->where('name', 'like', '%' . $searchTerm . '%'));
            });
        }

        $totalsQuery      = clone $query;
        $totalPurchases   = $totalsQuery->sum('total_amount');
        $totalVat         = $totalsQuery->sum('vat');
        $totalBills       = $totalsQuery->count();
        $totalOutstanding = $totalsQuery->sum('balance_amount');
        $paidAmount       = $totalsQuery->sum('paid_amount');
        $totalSuppliers   = $totalsQuery->distinct('supplier_id')->count('supplier_id');

        $totalItems = DB::table('purchase_bill_items')
            ->join('purchase_bills', 'purchase_bills.id', '=', 'purchase_bill_items.purchase_bill_id')
            ->whereIn('purchase_bill_items.purchase_bill_id', (clone $totalsQuery)->pluck('id'))
            ->where('purchase_bills.company_id', auth()->user()->company_id)
            ->sum('purchase_bill_items.quantity');

        $purchases = $query->latest()->paginate(10);
        $suppliers = Supplier::query()->orderBy('name')->get();
        $branches  = Branch::query()->orderBy('name')->get();

        return view('frontend.report.purchase_reports', compact(
            'purchases', 'totalPurchases', 'totalVat', 'totalBills',
            'totalOutstanding', 'paidAmount', 'totalSuppliers', 'totalItems',
            'suppliers', 'branches'
        ));
    }

    // ─── Purchase Exports ──────────────────────────────────────────────────────

    private function buildPurchasesQuery(Request $request)
    {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = PurchaseBill::query()->with(['supplier', 'items.product']);
        if ($request->from_date)    $query->whereDate('bill_date', '>=', $request->from_date);
        if ($request->to_date)      $query->whereDate('bill_date', '<=', $request->to_date);
        if ($request->supplier_id)  $query->where('supplier_id', $request->supplier_id);
        if ($request->status)       $query->where('status', $request->status);
        if ($request->branch_id)    $query->where('branch_id', $request->branch_id);
        if ($request->search) {
            $s = $request->search;
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            /** @disregard P0406 */
            $query->where(fn(\Illuminate\Database\Eloquent\Builder $q) =>
                $q->where('bill_number', 'like', "%$s%")
                  ->orWhereHas('supplier', fn(\Illuminate\Database\Eloquent\Builder $c) => $c->where('name', 'like', "%$s%"))
            );
        }
        return $query;
    }

    public function exportPurchasesPdf(Request $request)
    {
        $query        = $this->buildPurchasesQuery($request);
        $totalsQuery  = clone $query;
        $purchases    = $query->latest()->get();
        /** @var Company|null $company */
        $company      = Company::find(auth()->user()->company_id);

        $totalPurchases   = $totalsQuery->sum('total_amount');
        $totalBills       = $totalsQuery->count();
        $totalOutstanding = $totalsQuery->sum('balance_amount');
        $paidAmount       = $totalsQuery->sum('paid_amount');
        $totalItems       = DB::table('purchase_bill_items')
                              ->join('purchase_bills', 'purchase_bills.id', '=', 'purchase_bill_items.purchase_bill_id')
                              ->whereIn('purchase_bill_items.purchase_bill_id', (clone $totalsQuery)->pluck('id'))
                              ->where('purchase_bills.company_id', auth()->user()->company_id)
                              ->sum('purchase_bill_items.quantity');

        $filters = $request->only(['search', 'from_date', 'to_date', 'status', 'branch_id', 'supplier_id']);

        $cols = collect($request->input('cols', [
            'date', 'invoice_no', 'party_name', 'total', 'payment_type', 'received', 'balance'
        ]));

        $pdf = Pdf::loadView('frontend.report.purchase_reports_pdf', compact(
            'purchases', 'totalPurchases', 'totalBills', 'totalOutstanding',
            'paidAmount', 'totalItems', 'company', 'filters'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('purchase-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportPurchasesExcel(Request $request)
    {
        $purchases = $this->buildPurchasesQuery($request)->latest()->get();

        $cols = collect($request->input('cols', [
            'date', 'invoice_no', 'party_name', 'total', 'payment_type', 'received', 'balance'
        ]));

        $filename = 'purchase-report-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($purchases, $cols) {
            $handle = fopen('php://output', 'w');

            $headerRow = ['#'];
            if ($cols->contains('invoice_no'))    $headerRow[] = 'Bill #';
            if ($cols->contains('date'))          $headerRow[] = 'Date';
            if ($cols->contains('party_name'))    $headerRow[] = 'Supplier';
            if ($cols->contains('phone'))         $headerRow[] = "Phone No.";
            if ($cols->contains('order_number'))  $headerRow[] = 'Order Number';
            if ($cols->contains('item_details'))  $headerRow[] = 'Total Qty';
            if ($cols->contains('description'))   $headerRow[] = 'Description';
            if ($cols->contains('payment_type'))  $headerRow[] = 'Payment Type';
            if ($cols->contains('total'))         $headerRow[] = 'Total Amount';
            if ($cols->contains('received'))      $headerRow[] = 'Paid';
            if ($cols->contains('balance'))       $headerRow[] = 'Balance Due';
            if ($cols->contains('payment_status'))$headerRow[] = 'Status';
            fputcsv($handle, $headerRow);

            foreach ($purchases as $i => $purchase) {
                $row = [str_pad($i + 1, 2, '0', STR_PAD_LEFT)];
                if ($cols->contains('invoice_no'))    $row[] = $purchase->bill_number;
                if ($cols->contains('date'))          $row[] = \Carbon\Carbon::parse($purchase->bill_date)->format('d-M-Y');
                if ($cols->contains('party_name'))    $row[] = $purchase->supplier->name ?? 'Unknown';
                if ($cols->contains('phone'))         $row[] = $purchase->supplier->phone ?? '—';
                if ($cols->contains('order_number'))  $row[] = $purchase->id;
                if ($cols->contains('item_details'))  $row[] = $purchase->items->sum('quantity');
                if ($cols->contains('description'))   $row[] = $purchase->notes ?? '—';
                if ($cols->contains('payment_type'))  $row[] = $purchase->payment_method ?? 'Cash';
                if ($cols->contains('total'))         $row[] = number_format($purchase->total_amount, 2);
                if ($cols->contains('received'))      $row[] = number_format($purchase->paid_amount, 2);
                if ($cols->contains('balance'))       $row[] = number_format($purchase->balance_amount, 2);
                if ($cols->contains('payment_status'))$row[] = ucfirst($purchase->status);
                fputcsv($handle, $row);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ─── Daybook Report ────────────────────────────────────────────────────────

    private function buildDaybookItemsQuery(Request $request, $onlyParties = false)
    {
        $fromDate = $request->from_date ?? now()->format('Y-m-d');
        $toDate   = $request->to_date   ?? now()->format('Y-m-d');

        $query = DB::table('journal_items')
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->leftJoin('chart_of_accounts', 'journal_items.account_id', '=', 'chart_of_accounts.id')
            ->leftJoin('customers', 'journal_items.account_id', '=', 'customers.account_id')
            ->leftJoin('suppliers', 'journal_items.account_id', '=', 'suppliers.account_id')
            ->select(
                'journal_items.id',
                'journal_items.debit',
                'journal_items.credit',
                'journal_items.description as item_description',
                'journal_entries.date       as entry_date',
                'journal_entries.entry_number',
                'journal_entries.reference  as entry_reference',
                'journal_entries.description as entry_description',
                'journal_entries.status',
                'journal_entries.branch_id',
                'chart_of_accounts.name     as account_name',
                'customers.name as customer_name',
                'suppliers.name as supplier_name'
            )
            ->whereDate('journal_entries.date', '>=', $fromDate)
            ->whereDate('journal_entries.date', '<=', $toDate)
            ->where('journal_items.company_id', auth()->user()->company_id);

        if ($request->branch_id)   $query->where('journal_entries.branch_id', $request->branch_id);
        if ($request->account_id)   $query->where('journal_items.account_id', $request->account_id);

        // Transaction Type filter (matched by reference prefix)
        if ($request->tx_type) {
            $prefixMap = [
                'receipt'  => 'RCP',
                'sale'     => 'INV',
                'payment'  => 'PAY',
                'purchase' => 'PO',
                'expense'  => 'EXP',
                'transfer' => 'TRF',
            ];
            if ($request->tx_type === 'journal') {
                // Journal = entries whose reference does NOT start with any known prefix
                /** @disregard P0406 */
                $query->where(function (\Illuminate\Database\Query\Builder $q) {
                    $q->whereRaw("UPPER(COALESCE(journal_entries.reference, journal_entries.entry_number)) NOT LIKE 'RCP%'")
                      ->whereRaw("UPPER(COALESCE(journal_entries.reference, journal_entries.entry_number)) NOT LIKE 'INV%'")
                      ->whereRaw("UPPER(COALESCE(journal_entries.reference, journal_entries.entry_number)) NOT LIKE 'PAY%'")
                      ->whereRaw("UPPER(COALESCE(journal_entries.reference, journal_entries.entry_number)) NOT LIKE 'PO%'")
                      ->whereRaw("UPPER(COALESCE(journal_entries.reference, journal_entries.entry_number)) NOT LIKE 'EXP%'")
                      ->whereRaw("UPPER(COALESCE(journal_entries.reference, journal_entries.entry_number)) NOT LIKE 'TRF%'");
                });
            } elseif (isset($prefixMap[$request->tx_type])) {
                $prefix = $prefixMap[$request->tx_type];
                $query->whereRaw("UPPER(COALESCE(journal_entries.reference, journal_entries.entry_number)) LIKE '{$prefix}%'");
            }
        }

        // Amount Range filter (checks debit OR credit)
        if ($request->amount_range) {
            $range = $request->amount_range;
            if ($range === '50000+') {
                /** @disregard P0406 */
                $query->where(fn($q) => $q->where('journal_items.debit', '>', 50000)->orWhere('journal_items.credit', '>', 50000));
            } elseif (str_contains($range, '-')) {
                [$min, $max] = explode('-', $range);
                /** @disregard P0406 */
                $query->where(function (\Illuminate\Database\Query\Builder $q) use ($min, $max) {
                    $q->whereBetween('journal_items.debit',  [(float)$min, (float)$max])
                      ->orWhereBetween('journal_items.credit', [(float)$min, (float)$max]);
                });
            }
        }

        if ($onlyParties) {
            /** @disregard P0406 */
            $query->where(function($q) {
                // Include if linked to a Customer or Supplier
                $q->whereNotNull('customers.id')
                  ->orWhereNotNull('suppliers.id')
                  // OR if it's a Sale (INV) or Purchase (PO) across the entire system
                  ->orWhereRaw("UPPER(COALESCE(journal_entries.reference, journal_entries.entry_number)) LIKE 'INV%'")
                  ->orWhereRaw("UPPER(COALESCE(journal_entries.reference, journal_entries.entry_number)) LIKE 'PO%'");
            });
        }

        return $query->orderBy('journal_entries.date')->orderBy('journal_items.id');
    }

    public function daybookReport(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $branches = Branch::query()->get();
        $accounts = DB::table('chart_of_accounts')->where('company_id', $id)->where('is_active', 1)->orderBy('name')->get();

        $fromDate = $request->from_date ?? now()->format('Y-m-d');

        // Opening balance: net of ALL items strictly before from_date
        $openingBalance = (float) DB::table('journal_items')
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->whereDate('journal_entries.date', '<', $fromDate)
            ->where('journal_items.company_id', auth()->user()->company_id)
            ->selectRaw('SUM(debit) - SUM(credit) as balance')
            ->value('balance');

        $baseQuery   = $this->buildDaybookItemsQuery($request);
        $totalDebit  = (clone $baseQuery)->sum('journal_items.debit');
        $totalCredit = (clone $baseQuery)->sum('journal_items.credit');
        $netBalance  = $totalDebit - $totalCredit;
        $closingBalance = $openingBalance + $netBalance;

        // Compute running balance across all rows
        $allRows = (clone $baseQuery)->get();
        $running = $openingBalance;
        $items   = $allRows->map(function ($row) use (&$running) {
            $running += ($row->debit - $row->credit);
            $row->running_balance = $running;
            return $row;
        });

        return view('frontend.report.daybook_reports', compact(
            'items', 'openingBalance', 'totalDebit', 'totalCredit',
            'netBalance', 'closingBalance', 'company', 'branches', 'accounts'
        ));
    }

    public function exportDaybookPdf(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $filters  = $request->only(['tx_type', 'account_id', 'amount_range', 'from_date', 'to_date', 'branch_id']);
        $fromDate = $request->from_date ?? now()->format('Y-m-d');

        $openingBalance = (float) DB::table('journal_items')
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->whereDate('journal_entries.date', '<', $fromDate)
            ->where('journal_items.company_id', auth()->user()->company_id)
            ->selectRaw('SUM(debit) - SUM(credit) as balance')
            ->value('balance');

        $allRows     = $this->buildDaybookItemsQuery($request)->get();
        $totalDebit  = $allRows->sum('debit');
        $totalCredit = $allRows->sum('credit');
        $netBalance  = $totalDebit - $totalCredit;
        $closingBalance = $openingBalance + $netBalance;

        $running = $openingBalance;
        $items   = $allRows->map(function ($row) use (&$running) {
            $running += ($row->debit - $row->credit);
            $row->running_balance = $running;
            return $row;
        });

        $pdf = Pdf::loadView('frontend.report.daybook_reports_pdf', compact(
            'items', 'openingBalance', 'totalDebit', 'totalCredit',
            'netBalance', 'closingBalance', 'company', 'filters'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('daybook-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportDaybookExcel(Request $request)
    {
        $fromDate = $request->from_date ?? now()->format('Y-m-d');

        $openingBalance = (float) DB::table('journal_items')
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->whereDate('journal_entries.date', '<', $fromDate)
            ->where('journal_items.company_id', auth()->user()->company_id)
            ->selectRaw('SUM(debit) - SUM(credit) as balance')
            ->value('balance');

        $allRows = $this->buildDaybookItemsQuery($request)->get();
        $cols     = collect($request->input('cols', ['type','ref_no','description','account','debit','credit','balance']));
        $filename = 'daybook-report-' . now()->format('Y-m-d') . '.csv';
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"$filename\""];

        $callback = function () use ($allRows, $cols, $openingBalance) {
            $handle = fopen('php://output', 'w');

            $headerRow = ['Date'];
            if ($cols->contains('type'))        $headerRow[] = 'Type';
            if ($cols->contains('ref_no'))      $headerRow[] = 'Reference #';
            if ($cols->contains('account'))     $headerRow[] = 'Party';
            if ($cols->contains('description')) $headerRow[] = 'Description';
            if ($cols->contains('debit'))       $headerRow[] = 'Debit';
            if ($cols->contains('credit'))      $headerRow[] = 'Credit';
            if ($cols->contains('balance'))     $headerRow[] = 'Amount';
            fputcsv($handle, $headerRow);

            foreach ($allRows as $i => $item) {
                $ref    = $item->entry_reference ?? $item->entry_number ?? '—';
                $refUp  = strtoupper($ref);
                $type   = match(true) {
                    str_starts_with($refUp, 'RCP') => 'Receipt',
                    str_starts_with($refUp, 'INV') => 'Sale',
                    str_starts_with($refUp, 'PAY') => 'Payment',
                    str_starts_with($refUp, 'PO')  => 'Purchase',
                    str_starts_with($refUp, 'EXP') => 'Expense',
                    str_starts_with($refUp, 'TRF') => 'Transfer',
                    default                         => 'Journal',
                };
                $row = [\Carbon\Carbon::parse($item->entry_date)->format('Y-m-d')];
                if ($cols->contains('type'))        $row[] = $type;
                if ($cols->contains('ref_no'))      $row[] = $ref;
                if ($cols->contains('account'))     $row[] = ($item->customer_name ?: ($item->supplier_name ?: $item->account_name)) ?? '—';
                if ($cols->contains('description')) $row[] = $item->item_description ?: ($item->entry_description ?? '—');
                if ($cols->contains('debit'))       $row[] = $item->debit > 0 ? number_format($item->debit, 2) : '—';
                if ($cols->contains('credit'))      $row[] = $item->credit > 0 ? number_format($item->credit, 2) : '—';
                if ($cols->contains('balance'))     $row[] = number_format(max($item->debit, $item->credit), 2);
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function transactionReport(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $branches = Branch::query()->get();
        $accounts  = DB::table('chart_of_accounts')->where('company_id', $id)->where('is_active', 1)->orderBy('name')->get();
        $customers = DB::table('customers')->select('id', 'name', 'account_id')->where('company_id', auth()->user()->company_id)->get();
        $suppliers = DB::table('suppliers')->select('id', 'name', 'account_id')->where('company_id', auth()->user()->company_id)->get();

        $partyAccountIds = array_merge(
            $customers->pluck('account_id')->toArray(),
            $suppliers->pluck('account_id')->toArray()
        );

        $fromDate = $request->from_date ?? now()->format('Y-m-d');

        $openingBalance = (float) DB::table('journal_items')
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->whereDate('journal_entries.date', '<', $fromDate)
            ->where('journal_items.company_id', auth()->user()->company_id)
            ->selectRaw('SUM(debit) - SUM(credit) as balance')
            ->value('balance');

        $baseQuery   = $this->buildDaybookItemsQuery($request, true);
        $totalDebit  = (clone $baseQuery)->sum('journal_items.debit');
        $totalCredit = (clone $baseQuery)->sum('journal_items.credit');
        $netBalance  = $totalDebit - $totalCredit;
        $closingBalance = $openingBalance + $netBalance;

        $allRows = (clone $baseQuery)->get();
        $running = $openingBalance;
        $items   = $allRows->map(function ($row) use (&$running) {
            $running += ($row->debit - $row->credit);
            $row->running_balance = $running;
            return $row;
        });

        return view('frontend.report.transaction_reports', compact(
            'items', 'openingBalance', 'totalDebit', 'totalCredit',
            'netBalance', 'closingBalance', 'company', 'branches', 'accounts',
            'customers', 'suppliers', 'partyAccountIds'
        ));
    }

    public function exportTransactionPdf(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $filters  = $request->only(['tx_type', 'account_id', 'amount_range', 'from_date', 'to_date', 'branch_id']);
        $fromDate = $request->from_date ?? now()->format('Y-m-d');

        $openingBalance = (float) DB::table('journal_items')
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->whereDate('journal_entries.date', '<', $fromDate)
            ->where('journal_items.company_id', auth()->user()->company_id)
            ->selectRaw('SUM(debit) - SUM(credit) as balance')
            ->value('balance');

        $allRows     = $this->buildDaybookItemsQuery($request, true)->get();
        $totalDebit  = $allRows->sum('debit');
        $totalCredit = $allRows->sum('credit');
        $netBalance  = $totalDebit - $totalCredit;
        $closingBalance = $openingBalance + $netBalance;

        $running = $openingBalance;
        $items   = $allRows->map(function ($row) use (&$running) {
            $running += ($row->debit - $row->credit);
            $row->running_balance = $running;
            return $row;
        });

        $pdf = Pdf::loadView('frontend.report.transaction_reports_pdf', compact(
            'items', 'openingBalance', 'totalDebit', 'totalCredit',
            'netBalance', 'closingBalance', 'company', 'filters'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('transaction-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportTransactionExcel(Request $request)
    {
        $fromDate = $request->from_date ?? now()->format('Y-m-d');

        $openingBalance = (float) DB::table('journal_items')
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->whereDate('journal_entries.date', '<', $fromDate)
            ->where('journal_items.company_id', auth()->user()->company_id)
            ->selectRaw('SUM(debit) - SUM(credit) as balance')
            ->value('balance');

        $allRows = $this->buildDaybookItemsQuery($request, true)->get();
        $cols     = collect($request->input('cols', ['type','ref_no','description','account','debit','credit','balance']));
        $filename = 'transaction-report-' . now()->format('Y-m-d') . '.csv';
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"$filename\""];

        $callback = function () use ($allRows, $cols, $openingBalance) {
            $handle = fopen('php://output', 'w');

            $headerRow = ['Date'];
            if ($cols->contains('type'))        $headerRow[] = 'Type';
            if ($cols->contains('ref_no'))      $headerRow[] = 'Reference #';
            if ($cols->contains('account'))     $headerRow[] = 'Party';
            if ($cols->contains('description')) $headerRow[] = 'Description';
            if ($cols->contains('debit'))       $headerRow[] = 'Debit';
            if ($cols->contains('credit'))      $headerRow[] = 'Credit';
            if ($cols->contains('balance'))     $headerRow[] = 'Amount';
            fputcsv($handle, $headerRow);

            $running = $openingBalance;
            foreach ($allRows as $i => $item) {
                $running += ($item->debit - $item->credit);
                $ref    = $item->entry_reference ?? $item->entry_number ?? '—';
                $refUp  = strtoupper($ref);
                $type   = match(true) {
                    str_starts_with($refUp, 'RCP') => 'Receipt',
                    str_starts_with($refUp, 'INV') => 'Sale',
                    str_starts_with($refUp, 'PAY') => 'Payment',
                    str_starts_with($refUp, 'PO')  => 'Purchase',
                    str_starts_with($refUp, 'EXP') => 'Expense',
                    default                         => 'Journal',
                };
                $row = [\Carbon\Carbon::parse($item->entry_date)->format('Y-m-d')];
                if ($cols->contains('type'))        $row[] = $type;
                if ($cols->contains('ref_no'))      $row[] = $ref;
                if ($cols->contains('account'))     $row[] = ($item->customer_name ?: ($item->supplier_name ?: $item->account_name)) ?? '—';
                if ($cols->contains('description')) $row[] = $item->item_description ?: ($item->entry_description ?? '—');
                if ($cols->contains('debit'))       $row[] = $item->debit > 0 ? number_format($item->debit, 2) : '—';
                if ($cols->contains('credit'))      $row[] = $item->credit > 0 ? number_format($item->credit, 2) : '—';
                if ($cols->contains('balance'))     $row[] = number_format(max($item->debit, $item->credit), 2);
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }


    // ─── Sales Exports ─────────────────────────────────────────────────────────

    private function buildSalesQuery(Request $request)
    {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = SalesOrder::query()->with(['customer', 'items.product']);
        if ($request->from_date)   $query->whereDate('invoice_date', '>=', $request->from_date);
        if ($request->to_date)     $query->whereDate('invoice_date', '<=', $request->to_date);
        if ($request->customer_id) $query->where('customer_id', $request->customer_id);
        if ($request->status)      $query->where('status', $request->status);
        if ($request->branch_id)   $query->where('branch_id', $request->branch_id);
        if ($request->search) {
            $s = $request->search;
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            /** @disregard P0406 */
            $query->where(fn(\Illuminate\Database\Eloquent\Builder $q) =>
                $q->where('invoice_no', 'like', "%$s%")
                  ->orWhereHas('customer', fn(\Illuminate\Database\Eloquent\Builder $c) => $c->where('name', 'like', "%$s%"))
            );
        }
        return $query;
    }

    public function exportSalesPdf(Request $request)
    {
        $query        = $this->buildSalesQuery($request);
        $totalsQuery  = clone $query;
        $sales        = $query->latest()->get();
        /** @var Company|null $company */
        $company      = Company::find(auth()->user()->company_id);

        $totalSales       = $totalsQuery->sum('total_amount');
        $totalInvoices    = $totalsQuery->count();
        $totalOutstanding = $totalsQuery->sum('due_amount');
        $paidAmount       = $totalsQuery->sum('paid_amount');
        $totalItems       = DB::table('sales_order_items')
                              ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
                              ->whereIn('sales_order_items.sales_order_id', (clone $totalsQuery)->pluck('id'))
                              ->where('sales_orders.company_id', auth()->user()->company_id)
                              ->sum('sales_order_items.quantity');

        $filters = $request->only(['search', 'from_date', 'to_date', 'status', 'branch_id', 'customer_id']);

        // Default columns match modal pre-checked defaults
        $cols = collect($request->input('cols', [
            'date', 'invoice_no', 'party_name', 'total', 'payment_type', 'received', 'balance'
        ]));

        $pdf = Pdf::loadView('frontend.report.sale_reports_pdf', compact(
            'sales', 'totalSales', 'totalInvoices', 'totalOutstanding',
            'paidAmount', 'totalItems', 'company', 'filters'
        ))->setPaper('a4', 'landscape'); // A4 landscape explicit pts

        return $pdf->download('sales-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportSalesExcel(Request $request)
    {
        $sales = $this->buildSalesQuery($request)->latest()->get();

        // Default columns match the pre-checked modal defaults
        $cols = collect($request->input('cols', [
            'date', 'invoice_no', 'party_name', 'total', 'payment_type', 'received', 'balance'
        ]));

        $filename = 'sales-report-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($sales, $cols) {
            $handle = fopen('php://output', 'w');

            // ── Build header row dynamically ──────────────────────────────────
            $headerRow = ['#'];
            if ($cols->contains('invoice_no'))    $headerRow[] = 'Invoice #';
            if ($cols->contains('date'))          $headerRow[] = 'Date';
            if ($cols->contains('party_name'))    $headerRow[] = 'Party Name';
            if ($cols->contains('phone'))         $headerRow[] = "Party's Phone No.";
            if ($cols->contains('order_number'))  $headerRow[] = 'Order Number';
            if ($cols->contains('item_details'))  $headerRow[] = 'Total Qty';
            if ($cols->contains('description'))   $headerRow[] = 'Description';
            if ($cols->contains('payment_type'))  $headerRow[] = 'Payment Type';
            if ($cols->contains('total'))         $headerRow[] = 'Total Amount';
            if ($cols->contains('received'))      $headerRow[] = 'Received/Paid';
            if ($cols->contains('balance'))       $headerRow[] = 'Balance Due';
            if ($cols->contains('payment_status'))$headerRow[] = 'Payment Status';
            fputcsv($handle, $headerRow);

            // ── Build data rows dynamically ───────────────────────────────────
            foreach ($sales as $i => $sale) {
                $row = [str_pad($i + 1, 2, '0', STR_PAD_LEFT)];
                if ($cols->contains('invoice_no'))    $row[] = $sale->invoice_no;
                if ($cols->contains('date'))          $row[] = $sale->invoice_date->format('d-M-Y');
                if ($cols->contains('party_name'))    $row[] = $sale->customer->name ?? 'Walk-in Customer';
                if ($cols->contains('phone'))         $row[] = $sale->customer->phone ?? '—';
                if ($cols->contains('order_number'))  $row[] = $sale->id;
                if ($cols->contains('item_details'))  $row[] = $sale->items->sum('quantity');
                if ($cols->contains('description'))   $row[] = $sale->notes ?? '—';
                if ($cols->contains('payment_type'))  $row[] = $sale->payment_method ?? 'Cash';
                if ($cols->contains('total'))         $row[] = number_format($sale->total_amount, 2);
                if ($cols->contains('received'))      $row[] = number_format($sale->paid_amount, 2);
                if ($cols->contains('balance'))       $row[] = number_format($sale->total_amount - $sale->paid_amount, 2);
                if ($cols->contains('payment_status'))$row[] = $sale->status_label ?? ucfirst($sale->status);
                fputcsv($handle, $row);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
    public function profitLossReport(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $fromDate = $request->from_date ?? now()->startOfMonth()->format('Y-m-d');
        $toDate   = $request->to_date   ?? now()->format('Y-m-d');

        // Ledger-based Revenue (Account 4110 or Revenue Category)
        $totalRevenue = DB::table('journal_items')
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->join('chart_of_accounts', 'journal_items.account_id', '=', 'chart_of_accounts.id')
            ->where('journal_entries.company_id', $id)
            ->whereBetween('journal_entries.date', [$fromDate, $toDate])
            ->where('chart_of_accounts.category', 'revenue')
            ->sum('journal_items.credit');

        // Ledger-based COGS (codes 5100, 5110, 5120, 5130 = COGS category in seeder)
        $totalCogs = DB::table('journal_items')
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->join('chart_of_accounts', 'journal_items.account_id', '=', 'chart_of_accounts.id')
            ->where('journal_entries.company_id', $id)
            ->whereBetween('journal_entries.date', [$fromDate, $toDate])
            ->where(function($q) {
                $q->whereIn('chart_of_accounts.code', ['5020', '5100', '5110', '5120', '5130'])
                  ->orWhere('chart_of_accounts.name', 'LIKE', '%Cost%Goods%')
                  ->orWhere('chart_of_accounts.name', 'LIKE', '%Product Cost%');
            })
            ->sum('journal_items.debit');

        $grossProfit  = $totalRevenue - $totalCogs;

        $operatingExpenses = Expense::query()->with('account')
            ->where('company_id', $id)
            ->whereBetween('expense_date', [$fromDate, $toDate])
            ->get()
            ->groupBy('expense_account_id');

        $expenseDetails = [];
        $totalExpenses = 0;
        foreach ($operatingExpenses as $accountId => $group) {
            $account = $group->first()->account;
            $amount = $group->sum('amount');
            $expenseDetails[] = (object)[
                'name'   => $account->name ?? 'Uncategorized',
                'amount' => $amount,
                'percent' => $totalRevenue > 0 ? ($amount / $totalRevenue) * 100 : 0
            ];
            $totalExpenses += $amount;
        }

        $netProfit = $grossProfit - $totalExpenses;
        $filters = ['from_date' => $fromDate, 'to_date' => $toDate];

        return view('frontend.report.profit_loss_reports', compact(
            'totalRevenue', 'totalCogs', 'grossProfit', 'expenseDetails',
            'totalExpenses', 'netProfit', 'company', 'filters'
        ));
    }

    public function exportProfitLossPdf(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $fromDate = $request->from_date ?? now()->startOfMonth()->format('Y-m-d');
        $toDate   = $request->to_date   ?? now()->format('Y-m-d');

        // Same journal-based calculation as the web view — ensures PDF matches screen
        $totalRevenue = DB::table('journal_items')
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->join('chart_of_accounts', 'journal_items.account_id', '=', 'chart_of_accounts.id')
            ->where('journal_entries.company_id', $id)
            ->whereBetween('journal_entries.date', [$fromDate, $toDate])
            ->where('chart_of_accounts.category', 'revenue')
            ->sum('journal_items.credit');

        $totalCogs = DB::table('journal_items')
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->join('chart_of_accounts', 'journal_items.account_id', '=', 'chart_of_accounts.id')
            ->where('journal_entries.company_id', $id)
            ->whereBetween('journal_entries.date', [$fromDate, $toDate])
            ->where(function ($q) {
                $q->whereIn('chart_of_accounts.code', ['5020', '5100', '5110', '5120', '5130'])
                  ->orWhere('chart_of_accounts.name', 'LIKE', '%Cost%Goods%')
                  ->orWhere('chart_of_accounts.name', 'LIKE', '%Product Cost%');
            })
            ->sum('journal_items.debit');

        $grossProfit = $totalRevenue - $totalCogs;

        $operatingExpenses = Expense::query()->with('account')
            ->where('company_id', $id)
            ->whereBetween('expense_date', [$fromDate, $toDate])
            ->get()
            ->groupBy('expense_account_id');

        $expenseDetails = [];
        $totalExpenses  = 0;
        foreach ($operatingExpenses as $group) {
            $account  = $group->first()->account;
            $amount   = $group->sum('amount');
            $expenseDetails[] = (object)[
                'name'    => $account->name ?? 'Uncategorized',
                'amount'  => $amount,
                'percent' => $totalRevenue > 0 ? ($amount / $totalRevenue) * 100 : 0,
            ];
            $totalExpenses += $amount;
        }

        $netProfit = $grossProfit - $totalExpenses;
        $filters   = ['from_date' => $fromDate, 'to_date' => $toDate];

        $pdf = Pdf::loadView('frontend.report.profit_loss_reports_pdf', compact(
            'totalRevenue', 'totalCogs', 'grossProfit', 'expenseDetails',
            'totalExpenses', 'netProfit', 'company', 'filters'
        ));

        return $pdf->download('profit-loss-report-' . now()->format('Y-m-d') . '.pdf');
    }
    public function billWiseProfitReport(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $fromDate = $request->from_date ?? now()->startOfMonth()->format('Y-m-d');
        $toDate   = $request->to_date   ?? now()->format('Y-m-d');
        $branchId = $request->branch_id;
        $customerId = $request->customer_id;
        $marginFilter = $request->margin_filter;
        $sortBy = $request->sort_by ?? 'date_desc';

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = SalesOrder::query()->with(['customer', 'items.product', 'branch'])
            ->whereBetween('invoice_date', [$fromDate, $toDate]);

        if ($branchId)   $query->where('branch_id', $branchId);
        if ($customerId) $query->where('customer_id', $customerId);

        if ($request->search) {
            $searchTerm = $request->search;
            /** @disregard P0406 */
            $query->where(function(\Illuminate\Database\Eloquent\Builder $q) use ($searchTerm) {
                $q->where('invoice_no', 'like', '%' . $searchTerm . '%');
            });
        }

        $orders = $query->get();

        $invoiceNumbers = $orders->pluck('invoice_no')->toArray();
        /** @var \Illuminate\Database\Eloquent\Builder $expensesQuery */
        $expensesQuery = Expense::query();
        /** @disregard P0406 */
        $relatedExpenses = $expensesQuery->where(function(\Illuminate\Database\Eloquent\Builder $q) use ($invoiceNumbers) {
            foreach($invoiceNumbers as $inv) {
                $q->orWhere('description', 'like', '%' . $inv . '%')
                  ->orWhere('reference_no', 'like', '%' . $inv . '%');
            }
        })->get();

        $reportData = $orders->map(function (SalesOrder $order) use ($relatedExpenses) {
            $totalSale = (float)$order->total_amount;
            $totalCost = (float)$order->items->sum(function (SalesOrderItem $item) {
                return ($item->product->purchase_price ?? 0) * $item->quantity;
            });

            $invoiceExpenses = $relatedExpenses->filter(function(Expense $exp) use ($order) {
                return (strpos($exp->description, $order->invoice_no) !== false) || 
                       (strpos($exp->reference_no, $order->invoice_no) !== false);
            })->sum('amount');

            $grossProfit = $totalSale - $totalCost - (float)$invoiceExpenses;
            $margin = $totalSale > 0 ? ($grossProfit / $totalSale) * 100 : 0;

            $grade = 'Low';
            if ($margin >= 35) $grade = 'High';
            elseif ($margin >= 15) $grade = 'Medium';

            return (object)[
                'id'           => $order->id,
                'date'         => $order->invoice_date,
                'invoice_no'   => $order->invoice_no,
                'customer'     => $order->customer->name ?? 'Walk-in Customer',
                'customer_id'  => $order->customer_id,
                'branch'       => $order->branch->name ?? 'Main',
                'revenue'      => $totalSale,
                'cost'         => $totalCost,
                'expenses'     => (float)$invoiceExpenses,
                'profit'       => $grossProfit,
                'margin'       => $margin,
                'grade'        => $grade
            ];
        });

        // Filter by Margin
        if ($marginFilter) {
            $reportData = $reportData->filter(function($item) use ($marginFilter) {
                if ($marginFilter == 'high')   return $item->margin >= 35;
                if ($marginFilter == 'medium') return $item->margin >= 15 && $item->margin < 35;
                if ($marginFilter == 'low')    return $item->margin < 15;
                return true;
            });
        }

        // Sorting
        if ($sortBy == 'date_desc')   $reportData = $reportData->sortByDesc('date');
        elseif ($sortBy == 'date_asc') $reportData = $reportData->sortBy('date');
        elseif ($sortBy == 'profit_desc') $reportData = $reportData->sortByDesc('profit');
        elseif ($sortBy == 'margin_desc') $reportData = $reportData->sortByDesc('margin');

        $totals = (object)[
            'revenue'        => $reportData->sum('revenue'),
            'cost'           => $reportData->sum('cost'),
            'expenses'       => $reportData->sum('expenses'),
            'profit'         => $reportData->sum('profit'),
            'totalInvoices'  => $reportData->count(),
            'avgMargin'      => $reportData->count() > 0 ? $reportData->avg('margin') : 0,
            'bestMargin'     => $reportData->count() > 0 ? $reportData->max('margin') : 0,
        ];

        $branches = Branch::query()->get();
        $customers = Customer::query()->orderBy('name', 'asc')->get();
        $filters = [
            'from_date'     => $fromDate, 
            'to_date'       => $toDate, 
            'branch_id'     => $branchId, 
            'customer_id'   => $customerId,
            'margin_filter' => $marginFilter,
            'sort_by'       => $sortBy
        ];

        return view('frontend.report.bill_wise_profit_reports', compact(
            'reportData', 'totals', 'company', 'filters', 'branches', 'customers'
        ));
    }

    public function exportBillWiseProfitPdf(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $fromDate = $request->from_date ?? now()->startOfMonth()->format('Y-m-d');
        $toDate   = $request->to_date   ?? now()->format('Y-m-d');
        $branchId = $request->branch_id;
        $customerId = $request->customer_id;
        $marginFilter = $request->margin_filter;
        $sortBy = $request->sort_by ?? 'date_desc';

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = SalesOrder::query()->with(['customer', 'items.product', 'branch'])
            ->whereBetween('invoice_date', [$fromDate, $toDate]);

        if ($branchId)   $query->where('branch_id', $branchId);
        if ($customerId) $query->where('customer_id', $customerId);

        if ($request->search) {
            $searchTerm = $request->search;
            /** @disregard P0406 */
            $query->where(function(\Illuminate\Database\Eloquent\Builder $q) use ($searchTerm) {
                $q->where('invoice_no', 'like', '%' . $searchTerm . '%');
            });
        }

        $orders = $query->get();

        $invoiceNumbers = $orders->pluck('invoice_no')->toArray();
        /** @var \Illuminate\Database\Eloquent\Builder $expensesQueryPdf */
        $expensesQueryPdf = Expense::query();
        /** @disregard P0406 */
        $relatedExpenses = $expensesQueryPdf->where(function(\Illuminate\Database\Query\Builder $q) use ($invoiceNumbers) {
            foreach($invoiceNumbers as $inv) {
                $q->orWhere('description', 'like', '%' . $inv . '%')
                  ->orWhere('reference_no', 'like', '%' . $inv . '%');
            }
        })->get();

        $reportData = $orders->map(function (SalesOrder $order) use ($relatedExpenses) {
            $totalSale = (float)$order->total_amount;
            $totalCost = (float)$order->items->sum(function (SalesOrderItem $item) {
                return ($item->product->purchase_price ?? 0) * $item->quantity;
            });

            $invoiceExpenses = $relatedExpenses->filter(function(Expense $exp) use ($order) {
                return (strpos($exp->description, $order->invoice_no) !== false) || 
                       (strpos($exp->reference_no, $order->invoice_no) !== false);
            })->sum('amount');

            $grossProfit = $totalSale - $totalCost - (float)$invoiceExpenses;
            $margin = $totalSale > 0 ? ($grossProfit / $totalSale) * 100 : 0;

            $grade = 'Low';
            if ($margin >= 35) $grade = 'High';
            elseif ($margin >= 15) $grade = 'Medium';

            return (object)[
                'date'         => $order->invoice_date,
                'invoice_no'   => $order->invoice_no,
                'customer'     => $order->customer->name ?? 'Walk-in Customer',
                'branch'       => $order->branch->name ?? 'Main',
                'revenue'      => $totalSale,
                'cost'         => $totalCost,
                'expenses'     => (float)$invoiceExpenses,
                'profit'       => $grossProfit,
                'margin'       => $margin,
                'grade'        => $grade
            ];
        });

        // Filter by Margin
        if ($marginFilter) {
            $reportData = $reportData->filter(function($item) use ($marginFilter) {
                if ($marginFilter == 'high')   return $item->margin >= 35;
                if ($marginFilter == 'medium') return $item->margin >= 15 && $item->margin < 35;
                if ($marginFilter == 'low')    return $item->margin < 15;
                return true;
            });
        }

        // Sorting
        if ($sortBy == 'date_desc')   $reportData = $reportData->sortByDesc('date');
        elseif ($sortBy == 'date_asc') $reportData = $reportData->sortBy('date');
        elseif ($sortBy == 'profit_desc') $reportData = $reportData->sortByDesc('profit');
        elseif ($sortBy == 'margin_desc') $reportData = $reportData->sortByDesc('margin');

        $totals = (object)[
            'revenue'  => $reportData->sum('revenue'),
            'cost'     => $reportData->sum('cost'),
            'expenses' => $reportData->sum('expenses'),
            'profit'   => $reportData->sum('profit'),
        ];

        $filters = ['from_date' => $fromDate, 'to_date' => $toDate];

        $pdf = Pdf::loadView('frontend.report.bill_wise_profit_pdf_reports', compact(
            'reportData', 'totals', 'company', 'filters'
        ));

        return $pdf->download('bill-wise-profit-' . now()->format('Y-m-d') . '.pdf');
    }

    public function trialBalanceReport(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $fromDate = $request->from_date ?? now()->startOfMonth()->format('Y-m-d');
        $toDate   = $request->to_date   ?? now()->format('Y-m-d');

        $accounts = Account::query()->where('company_id', $id)->orderBy('code', 'asc')->get();
        $reportData = $accounts->map(function (Account $account) use ($fromDate, $toDate, $id) {
            /** @var Account $account */
            // Opening Balance: Sum of all journal items before fromDate
            /** @var object|null $openingData */
            $openingData = JournalItem::query()
                ->where('journal_items.company_id', $id)
                ->where('account_id', $account->id)
                ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.date', '<', $fromDate)
                ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->first();

            $openingDebit = $openingData->total_debit ?? 0;
            $openingCredit = $openingData->total_credit ?? 0;

            if (in_array($account->category, ['assets', 'expenses'])) {
                $openingBalance = $openingDebit - $openingCredit;
            } else {
                $openingBalance = $openingCredit - $openingDebit;
            }

            // Period Movements
            /** @var object|null $periodData */
            $periodData = JournalItem::query()
                ->where('journal_items.company_id', $id)
                ->where('account_id', $account->id)
                ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
                ->whereBetween('journal_entries.date', [$fromDate, $toDate])
                ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->first();

            $debit  = $periodData->total_debit ?? 0;
            $credit = $periodData->total_credit ?? 0;

            if (in_array($account->category, ['assets', 'expenses'])) {
                $closingBalance = $openingBalance + ($debit - $credit);
            } else {
                $closingBalance = $openingBalance + ($credit - $debit);
            }

            return (object)[
                'id'             => $account->id,
                'code'           => $account->code,
                'name'           => $account->name,
                'category'       => $account->category,
                'opening_balance'=> $openingBalance,
                'debit'          => $debit,
                'credit'         => $credit,
                'closing_balance'=> $closingBalance
            ];
        });

        $totals = (object)[
            'opening_balance' => $reportData->sum('opening_balance'),
            'debit'           => $reportData->sum('debit'),
            'credit'          => $reportData->sum('credit'),
            'closing_debit'   => $reportData->filter(function($item) { 
                return (in_array($item->category, ['assets', 'expenses']) && $item->closing_balance >= 0) || 
                       (!in_array($item->category, ['assets', 'expenses']) && $item->closing_balance < 0);
            })->map(function($item) {
                return abs($item->closing_balance);
            })->sum(),
            'closing_credit'  => $reportData->filter(function($item) { 
                return (!in_array($item->category, ['assets', 'expenses']) && $item->closing_balance >= 0) || 
                       (in_array($item->category, ['assets', 'expenses']) && $item->closing_balance < 0);
            })->map(function($item) {
                return abs($item->closing_balance);
            })->sum(),
        ];

        $filters = ['from_date' => $fromDate, 'to_date' => $toDate];
        return view('frontend.report.trial_balance_reports', compact('reportData', 'totals', 'company', 'filters'));
    }

    public function exportTrialBalancePdf(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $fromDate = $request->from_date ?? now()->startOfMonth()->format('Y-m-d');
        $toDate   = $request->to_date   ?? now()->format('Y-m-d');

        $accounts = Account::query()->where('company_id', $id)->orderBy('code', 'asc')->get();
        $reportData = $accounts->map(function (Account $account) use ($fromDate, $toDate, $id) {
            /** @var object|null $openingData */
            $openingData = JournalItem::query()
                ->where('journal_items.company_id', $id)
                ->where('account_id', $account->id)
                ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.date', '<', $fromDate)
                ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->first();

            $openingDebit = $openingData->total_debit ?? 0;
            $openingCredit = $openingData->total_credit ?? 0;

            if (in_array($account->category, ['assets', 'expenses'])) {
                $openingBalance = $openingDebit - $openingCredit;
            } else {
                $openingBalance = $openingCredit - $openingDebit;
            }

            /** @var object|null $periodData */
            $periodData = JournalItem::query()
                ->where('journal_items.company_id', $id)
                ->where('account_id', $account->id)
                ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
                ->whereBetween('journal_entries.date', [$fromDate, $toDate])
                ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->first();

            $debit  = $periodData->total_debit ?? 0;
            $credit = $periodData->total_credit ?? 0;

            if (in_array($account->category, ['assets', 'expenses'])) {
                $closingBalance = $openingBalance + ($debit - $credit);
            } else {
                $closingBalance = $openingBalance + ($credit - $debit);
            }

            return (object)[
                'code'           => $account->code,
                'name'           => $account->name,
                'category'       => $account->category,
                'opening_balance'=> $openingBalance,
                'debit'          => $debit,
                'credit'         => $credit,
                'closing_balance'=> $closingBalance
            ];
        });

        $totals = (object)[
            'opening_balance' => $reportData->sum('opening_balance'),
            'debit'           => $reportData->sum('debit'),
            'credit'          => $reportData->sum('credit'),
            'closing_balance' => $reportData->sum('closing_balance'),
        ];

        $filters = ['from_date' => $fromDate, 'to_date' => $toDate];
        $pdf = Pdf::loadView('frontend.report.trial_balance_pdf_reports', compact('reportData', 'totals', 'company', 'filters'));

        return $pdf->download('trial-balance-' . now()->format('Y-m-d') . '.pdf');
    }

    public function cashFlowReport(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $fromDate = $request->from_date ?? now()->startOfMonth()->format('Y-m-d');
        $toDate   = $request->to_date   ?? now()->format('Y-m-d');
        $accountId = $request->account_id;

        $accountsQuery = Account::query()->where('company_id', $id)->whereIn('type', ['cash', 'bank', 'salaam_bank'])
            ->orderBy('name', 'asc');

        $allCashAccounts = $accountsQuery->get();

        if ($accountId && $accountId != 'all') {
            $accountsQuery->where('id', $accountId);
        }

        $accounts = $accountsQuery->get();

        $reportData = $accounts->map(function (Account $account) use ($fromDate, $toDate) {
            $openingData = JournalItem::query()->where('account_id', $account->id)
                ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.date', '<', $fromDate)
                ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                /** @var object|null $openingData */
                ->first();

            /** @var object|null $openingData */
            $openingBalance = ($openingData->total_debit ?? 0) - ($openingData->total_credit ?? 0);

            $periodData = JournalItem::query()->where('account_id', $account->id)
                ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
                ->whereBetween('journal_entries.date', [$fromDate, $toDate])
                ->selectRaw('SUM(debit) as inflow, SUM(credit) as outflow')
                ->first();

            /** @var object|null $periodData */
            $inflow  = $periodData->inflow ?? 0;
            $outflow = $periodData->outflow ?? 0;
            $closingBalance = $openingBalance + $inflow - $outflow;

            return (object)[
                'id'              => $account->id,
                'name'            => $account->name,
                'type'            => $account->type,
                'opening_balance' => $openingBalance,
                'inflow'          => $inflow,
                'outflow'         => $outflow,
                'closing_balance' => $closingBalance
            ];
        });

        $totals = (object)[
            'opening_balance' => $reportData->sum('opening_balance'),
            'inflow'          => $reportData->sum('inflow'),
            'outflow'         => $reportData->sum('outflow'),
            'closing_balance' => $reportData->sum('closing_balance'),
        ];

        $filters = ['from_date' => $fromDate, 'to_date' => $toDate, 'account_id' => $accountId];
        return view('frontend.report.cash_flow_reports', compact('reportData', 'totals', 'company', 'filters', 'allCashAccounts'));
    }

    public function exportCashFlowPdf(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $fromDate = $request->from_date ?? now()->startOfMonth()->format('Y-m-d');
        $toDate   = $request->to_date   ?? now()->format('Y-m-d');
        $accountId = $request->account_id;

        $accountsQuery = Account::query()->where('company_id', $id)->whereIn('type', ['cash', 'bank', 'salaam_bank'])
            ->orderBy('name', 'asc');

        if ($accountId && $accountId != 'all') {
            $accountsQuery->where('id', $accountId);
        }

        $accounts = $accountsQuery->get();

        $reportData = $accounts->map(function (Account $account) use ($fromDate, $toDate) {
            /** @var object|null $openingData */
            $openingData = JournalItem::query()->where('account_id', $account->id)
                ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.date', '<', $fromDate)
                ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->first();

            $openingBalance = ($openingData->total_debit ?? 0) - ($openingData->total_credit ?? 0);

            /** @var object|null $periodData */
            $periodData = JournalItem::query()->where('account_id', $account->id)
                ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
                ->whereBetween('journal_entries.date', [$fromDate, $toDate])
                ->selectRaw('SUM(debit) as inflow, SUM(credit) as outflow')
                ->first();

            $inflow  = $periodData->inflow ?? 0;
            $outflow = $periodData->outflow ?? 0;
            $closingBalance = $openingBalance + $inflow - $outflow;

            return (object)[
                'name'            => $account->name,
                'type'            => $account->type,
                'opening_balance' => $openingBalance,
                'inflow'          => $inflow,
                'outflow'         => $outflow,
                'closing_balance' => $closingBalance
            ];
        });

        $filters = ['from_date' => $fromDate, 'to_date' => $toDate];
        $pdf = Pdf::loadView('frontend.report.cash_flow_pdf_reports', compact('reportData', 'company', 'filters'));

        return $pdf->download('cash-flow-' . now()->format('Y-m-d') . '.pdf');
    }

    public function balanceSheetReport(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $asOfDate = $request->as_of_date ?? now()->format('Y-m-d');

        $accounts = Account::query()->where('company_id', $id)->orderBy('code', 'asc')->get();
        $reportData = $accounts->map(function (Account $account) use ($asOfDate, $id) {
            $movementData = JournalItem::query()
                ->where('journal_items.company_id', $id)
                ->where('account_id', $account->id)
                ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.date', '<=', $asOfDate)
                ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                /** @var object|null $openingData */
                ->first();

            /** @var object|null $movementData */
            $debit = $movementData->total_debit ?? 0;
            $credit = $movementData->total_credit ?? 0;

            if (in_array($account->category, ['assets', 'expenses'])) {
                $balance = $debit - $credit;
            } else {
                $balance = $credit - $debit;
            }

            return (object)[
                'id'       => $account->id,
                'code'     => $account->code,
                'name'     => $account->name,
                'category' => $account->category,
                'type'     => $account->type,
                'balance'  => $balance
            ];
        });

        // 1. Assets side
        $assets = $reportData->where('category', 'assets');
        $totalAssets = $assets->sum('balance');
        $currentAssets = $assets->whereIn('type', ['cash', 'bank', 'receivable', 'inventory'])->sum('balance');

        // 2. Liabilities side
        $liabilities = $reportData->where('category', 'liabilities');
        $totalLiabilities = $liabilities->sum('balance');

        // 3. Equity side
        $equityAccounts = $reportData->where('category', 'equity');
        
        // 4. Net Profit/Loss (Current Year Earnings)
        $revenue  = $reportData->where('category', 'revenue')->sum('balance');
        $expenses = $reportData->where('category', 'expenses')->sum('balance');
        $netIncome = $revenue - $expenses;

        $totalEquity = $equityAccounts->sum('balance') + $netIncome;
        $totalLiabilitiesEquity = $totalLiabilities + $totalEquity;

        $filters = ['as_of_date' => $asOfDate];

        return view('frontend.report.Balance_sheet_reports', compact(
            'company', 
            'assets', 
            'totalAssets', 
            'currentAssets',
            'liabilities', 
            'totalLiabilities', 
            'equityAccounts', 
            'netIncome', 
            'totalEquity', 
            'totalLiabilitiesEquity',
            'filters'
        ));
    }

    public function exportBalanceSheetPdf(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $asOfDate = $request->as_of_date ?? now()->format('Y-m-d');

        $accounts = Account::query()->where('company_id', $id)->orderBy('code', 'asc')->get();
        $reportData = $accounts->map(function (Account $account) use ($asOfDate, $id) {
            /** @var object|null $movementData */
            $movementData = JournalItem::query()
                ->where('journal_items.company_id', $id)
                ->where('account_id', $account->id)
                ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_entries.date', '<=', $asOfDate)
                ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->first();

            $debit = $movementData->total_debit ?? 0;
            $credit = $movementData->total_credit ?? 0;

            if (in_array($account->category, ['assets', 'expenses'])) {
                $balance = $debit - $credit;
            } else {
                $balance = $credit - $debit;
            }

            return (object)[
                'code'     => $account->code,
                'name'     => $account->name,
                'category' => $account->category,
                'balance'  => $balance
            ];
        });

        $assets = $reportData->where('category', 'assets');
        $totalAssets = $assets->sum('balance');
        $liabilities = $reportData->where('category', 'liabilities');
        $totalLiabilities = $liabilities->sum('balance');
        $equityAccounts = $reportData->where('category', 'equity');
        $revenue  = $reportData->where('category', 'revenue')->sum('balance');
        $expenses = $reportData->where('category', 'expenses')->sum('balance');
        $netIncome = $revenue - $expenses;
        $totalEquity = $equityAccounts->sum('balance') + $netIncome;
        $totalLiabilitiesEquity = $totalLiabilities + $totalEquity;

        $filters = ['as_of_date' => $asOfDate];

        $pdf = Pdf::loadView('frontend.report.balance_sheet_pdf_reports', compact(
            'company', 'assets', 'totalAssets', 'liabilities', 'totalLiabilities', 
            'equityAccounts', 'netIncome', 'totalEquity', 'totalLiabilitiesEquity', 'filters'
        ));

        return $pdf->download('balance-sheet-' . $asOfDate . '.pdf');
    }

    private function partiesStatementData(Request $request)
    {
        $customers = Customer::query()->orderBy('name')->get();
        $suppliers = Supplier::query()->orderBy('name')->get();

        $totalReceivable = (float) Customer::query()->sum('amount_balance');
        $totalPayable    = (float) Supplier::query()->sum('amount_balance');
        $activeParties   = Customer::query()->where('status', 'active')->count()
                          + Supplier::query()->where('status', 'active')->count();

        $fromDate = $request->input('from_date') ?: now()->startOfYear()->toDateString();
        $toDate   = $request->input('to_date') ?: now()->toDateString();
        $partyKey = $request->input('party');

        $totalTransactions = SalesOrder::query()->whereBetween('invoice_date', [$fromDate, $toDate])->count()
            + PurchaseBill::query()->whereBetween('bill_date', [$fromDate, $toDate])->count()
            + PaymentIn::query()->whereBetween('payment_date', [$fromDate, $toDate])->count()
            + SupplierPayment::query()->whereBetween('payment_date', [$fromDate, $toDate])->count();

        $party  = null;
        $ledger = collect();

        if ($partyKey && str_contains($partyKey, '_')) {
            [$type, $partyId] = explode('_', $partyKey, 2);

            if ($type === 'customer') {
                $party = Customer::query()->find($partyId);
                if ($party) {
                    $increases = SalesOrder::query()->where('customer_id', $party->id)->get()->map(fn($o) => [
                        'date'        => $o->invoice_date ?? $o->created_at,
                        'type'        => 'Invoice',
                        'description' => 'Sales Invoice',
                        'reference'   => $o->invoice_no,
                        'debit'       => (float) $o->total_amount,
                        'credit'      => 0,
                    ]);
                    $decreases = PaymentIn::query()->where('customer_id', $party->id)->get()->map(fn($p) => [
                        'date'        => $p->payment_date,
                        'type'        => 'Payment',
                        'description' => 'Payment Received',
                        'reference'   => $p->receipt_no,
                        'debit'       => 0,
                        'credit'      => (float) $p->amount,
                    ]);
                    $ledger = $this->buildPartyStatementLedger($party, $increases, $decreases, $fromDate, $toDate);
                }
            } elseif ($type === 'supplier') {
                $party = Supplier::query()->find($partyId);
                if ($party) {
                    $increases = PurchaseBill::query()->where('supplier_id', $party->id)->get()->map(fn($b) => [
                        'date'        => $b->bill_date,
                        'type'        => 'Bill',
                        'description' => 'Purchase Bill',
                        'reference'   => $b->bill_number,
                        'debit'       => (float) $b->total_amount,
                        'credit'      => 0,
                    ]);
                    $decreases = SupplierPayment::query()->where('supplier_id', $party->id)->get()->map(fn($p) => [
                        'date'        => $p->payment_date,
                        'type'        => 'Payment',
                        'description' => 'Payment Made',
                        'reference'   => $p->voucher_no,
                        'debit'       => 0,
                        'credit'      => (float) $p->amount,
                    ]);
                    $ledger = $this->buildPartyStatementLedger($party, $increases, $decreases, $fromDate, $toDate);
                }
            }
        }

        return compact(
            'customers', 'suppliers', 'totalReceivable', 'totalPayable',
            'activeParties', 'totalTransactions', 'fromDate', 'toDate', 'partyKey', 'party', 'ledger'
        );
    }

    // Builds a running-balance ledger for one customer or supplier, anchored
    // to that party's current amount_balance (the authoritative subledger
    // total) rather than assuming every historical transaction is known —
    // mirrors the back-calculation already used in customer_statement.blade.php.
    // "Debit" here means "increases what's owed" (a new invoice/bill) and
    // "credit" means "decreases what's owed" (a payment), for both
    // customers and suppliers, so the table reads consistently either way.
    private function buildPartyStatementLedger($party, $increases, $decreases, $fromDate, $toDate)
    {
        $all = $increases->concat($decreases)
            ->filter(fn($t) => !empty($t['date']))
            ->sortBy(fn($t) => \Carbon\Carbon::parse($t['date']))
            ->values();

        $currentBalance = (float) ($party->amount_balance ?? 0);
        $balanceAtZero  = $currentBalance - $all->sum('debit') + $all->sum('credit');

        $from = \Carbon\Carbon::parse($fromDate)->startOfDay();
        $to   = \Carbon\Carbon::parse($toDate)->endOfDay();

        $opening = $balanceAtZero;
        foreach ($all as $t) {
            if (\Carbon\Carbon::parse($t['date'])->lt($from)) {
                $opening += $t['debit'] - $t['credit'];
            }
        }

        $periodTxns = $all->filter(function ($t) use ($from, $to) {
            $d = \Carbon\Carbon::parse($t['date']);
            return $d->gte($from) && $d->lte($to);
        })->values();

        $rows = collect();
        $running = $opening;
        $rows->push([
            'date' => $from, 'type' => 'Opening', 'description' => 'Opening Balance',
            'reference' => '---', 'debit' => 0, 'credit' => 0, 'balance' => $running,
        ]);

        foreach ($periodTxns as $t) {
            $running += $t['debit'] - $t['credit'];
            $rows->push([
                'date' => \Carbon\Carbon::parse($t['date']), 'type' => $t['type'],
                'description' => $t['description'], 'reference' => $t['reference'] ?: '---',
                'debit' => $t['debit'], 'credit' => $t['credit'], 'balance' => $running,
            ]);
        }

        return $rows;
    }

    public function partiesStatementReport(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();

        $data = $this->partiesStatementData($request);

        return view('frontend.report.Parties_Statement_reports', array_merge(['company' => $company], $data));
    }

    public function exportPartiesStatementPdf(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();

        $data = $this->partiesStatementData($request);

        $pdf = Pdf::loadView('frontend.report.Parties_statement_pdf_reports', array_merge(['company' => $company], $data));
        $pdf->setPaper('a4', 'landscape');
        return $pdf->download('party-statement.pdf');
    }

    public function partyWiseProfitLossReport(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();

        [$reportData, $totals, $filters] = $this->buildPartyWiseProfitLoss($request);

        return view('frontend.report.party_wise_profit_Loss_reports', compact('company', 'reportData', 'totals', 'filters'));
    }

    public function exportPartyWiseProfitLossPdf(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();

        [$reportData, $totals, $filters] = $this->buildPartyWiseProfitLoss($request);

        $pdf = Pdf::loadView('frontend.report.party_wise_profit_Loss_pdf_reports', compact('company', 'reportData', 'totals', 'filters'));
        return $pdf->download('party-wise-profit-loss.pdf');
    }

    private function buildPartyWiseProfitLoss(Request $request): array
    {
        $fromDate = $request->from_date ?? now()->startOfYear()->format('Y-m-d');
        $toDate   = $request->to_date   ?? now()->format('Y-m-d');

        $orders = SalesOrder::query()
            ->with(['customer', 'items.product'])
            ->whereIn('status', ['completed', 'partial', 'pending'])
            ->whereDate('invoice_date', '>=', $fromDate)
            ->whereDate('invoice_date', '<=', $toDate)
            ->get();

        $partyData = [];
        foreach ($orders as $order) {
            $key           = $order->customer_id ?? 'cash';
            $customerName  = $order->customer ? $order->customer->name : 'Cash Sale';
            $customerPhone = $order->customer ? ($order->customer->phone ?? '---') : '---';

            if (!isset($partyData[$key])) {
                $partyData[$key] = [
                    'name'    => $customerName,
                    'phone'   => $customerPhone,
                    'revenue' => 0.0,
                    'cogs'    => 0.0,
                ];
            }

            $partyData[$key]['revenue'] += (float) $order->total_amount;

            foreach ($order->items as $item) {
                $purchasePrice = (float) ($item->product->purchase_price ?? 0);
                if ($purchasePrice > 0) {
                    $partyData[$key]['cogs'] += $purchasePrice * (float) $item->quantity;
                }
            }
        }

        foreach ($partyData as &$party) {
            $party['profit'] = $party['revenue'] - $party['cogs'];
            $party['margin'] = $party['revenue'] > 0
                ? round(($party['profit'] / $party['revenue']) * 100, 1)
                : 0.0;
        }
        unset($party);

        // Apply party type filter: "customer" excludes the synthetic "Cash Sale" bucket
        if ($request->party_type === 'customer') {
            $partyData = array_filter($partyData, fn ($p, $key) => $key !== 'cash', ARRAY_FILTER_USE_BOTH);
        }

        // Apply search filter after grouping
        if ($request->search) {
            $search    = strtolower($request->search);
            $partyData = array_filter($partyData, function ($p) use ($search) {
                return str_contains(strtolower($p['name']), $search)
                    || str_contains(strtolower($p['phone']), $search);
            });
        }

        usort($partyData, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        $totalSales    = array_sum(array_column($partyData, 'revenue'));
        $totalCOGS     = array_sum(array_column($partyData, 'cogs'));
        $totalProfit   = array_sum(array_column($partyData, 'profit'));
        $activeParties = count($partyData);
        $overallMargin = $totalSales > 0 ? round(($totalProfit / $totalSales) * 100, 1) : 0.0;

        $topPartyArr = $activeParties > 0
            ? collect($partyData)->sortByDesc('profit')->first()
            : null;
        $topParty = $topPartyArr ? $topPartyArr['name'] : '—';

        $totals = [
            'totalSales'    => $totalSales,
            'totalCOGS'     => $totalCOGS,
            'totalProfit'   => $totalProfit,
            'activeParties' => $activeParties,
            'overallMargin' => $overallMargin,
            'topParty'      => $topParty,
        ];

        $filters = [
            'search'     => $request->search ?? '',
            'from_date'  => $fromDate,
            'to_date'    => $toDate,
            'party_type' => $request->party_type ?? '',
        ];

        return [array_values($partyData), $totals, $filters];
    }

    public function exportPartyWiseProfitLossExcel(Request $request)
    {
        [$reportData, $totals] = $this->buildPartyWiseProfitLoss($request);

        $filename = 'party-wise-profit-loss-' . now()->format('Y-m-d') . '.csv';
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"$filename\""];

        $callback = function () use ($reportData) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['#', 'Party Name', 'Phone No.', 'Total Sale Amount', 'Profit(+)/Loss(-)']);

            foreach ($reportData as $i => $party) {
                fputcsv($handle, [
                    $i + 1,
                    $party['name'],
                    $party['phone'],
                    number_format($party['revenue'], 2),
                    number_format($party['profit'], 2),
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function allPartiesReport(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        return view('frontend.report.all_parties_reports', compact('company'));
    }

    public function exportAllPartiesPdf(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $pdf = Pdf::loadView('frontend.report.all_parties_pdf_reports', compact('company'));
        $pdf->setPaper('a4', 'landscape');
        return $pdf->download('all-parties-status-report.pdf');
    }

    private function salesPurchaseByPartyData()
    {
        $id = auth()->user()->company_id;

        // Customers — sum sales_orders per customer for this company
        $customerRows = \Illuminate\Support\Facades\DB::table('customers as c')
            ->leftJoin('sales_orders as so', function ($j) use ($id) {
                $j->on('so.customer_id', '=', 'c.id')->where('so.company_id', $id);
            })
            ->where('c.company_id', $id)
            ->selectRaw('c.id, c.name, COALESCE(SUM(so.total_amount),0) as total_sales')
            ->groupBy('c.id', 'c.name')
            ->get();

        $customers = $customerRows->map(fn($c) => [
            'id'        => 'c-' . $c->id,
            'name'      => $c->name ?? 'Walk-in Customer',
            'type'      => 'customer',
            'sales'     => (float) $c->total_sales,
            'purchases' => 0,
        ]);

        // Suppliers — sum purchase_bills per supplier for this company
        $supplierRows = \Illuminate\Support\Facades\DB::table('suppliers as s')
            ->leftJoin('purchase_bills as pb', function ($j) use ($id) {
                $j->on('pb.supplier_id', '=', 's.id')->where('pb.company_id', $id);
            })
            ->where('s.company_id', $id)
            ->selectRaw('s.id, s.name, COALESCE(SUM(pb.total_amount),0) as total_purchases')
            ->groupBy('s.id', 's.name')
            ->get();

        $suppliers = $supplierRows->map(fn($s) => [
            'id'        => 's-' . $s->id,
            'name'      => $s->name,
            'type'      => 'supplier',
            'sales'     => 0,
            'purchases' => (float) $s->total_purchases,
        ]);

        // Merge: if same name appears as both customer and supplier, combine
        return $customers->concat($suppliers)
            ->groupBy('name')
            ->map(function ($group) {
                return [
                    'id'        => $group->first()['id'],
                    'name'      => $group->first()['name'],
                    'type'      => $group->count() > 1 ? 'hybrid' : $group->first()['type'],
                    'sales'     => $group->sum('sales'),
                    'purchases' => $group->sum('purchases'),
                ];
            })
            ->values()
            ->filter(fn($p) => $p['sales'] > 0 || $p['purchases'] > 0)
            ->values();
    }

    public function salesPurchaseByPartyReport(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();

        $parties = $this->salesPurchaseByPartyData();

        return view('frontend.report.sales_purchase_by_party_reports', compact('company', 'parties'));
    }

    public function exportSalesPurchaseByPartyPdf(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();

        $parties = $this->salesPurchaseByPartyData();

        $pdf = Pdf::loadView('frontend.report.sales_purchase_by_party_pdf_reports', compact('company', 'parties'));
        $pdf->setPaper('a4', 'landscape');
        return $pdf->download('sales-purchase-by-party-report.pdf');
    }

    public function salesPurchaseByPartyGroupReport(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        return view('frontend.report.sale_purchase_by_party_group_reports', compact('company'));
    }

    public function exportSalesPurchaseByPartyGroupPdf(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $pdf = Pdf::loadView('frontend.report.sale_purchase_by_party_group_pdf', compact('company'));
        $pdf->setPaper('a4', 'landscape');
        return $pdf->download('sales-purchase-by-party-group-report.pdf');
    }

    private function summaryStockProducts()
    {
        $userBranchId = auth()->user()->getAssignedBranchId();

        return Product::query()->with('category')
            ->withSum(['stocks' => function ($q) use ($userBranchId) {
                if ($userBranchId) $q->where('branch_id', $userBranchId);
            }], 'quantity')
            ->orderBy('product_name')
            ->get()
            ->map(function ($p) {
                return [
                    'id'            => $p->id,
                    'name'          => $p->product_name,
                    'category'      => $p->category->name ?? 'Uncategorized',
                    'salePrice'     => (float) $p->selling_price,
                    'purchasePrice' => (float) $p->purchase_price,
                    'qty'           => (int) ($p->stocks_sum_quantity ?? 0),
                ];
            })->values();
    }

    public function summaryStockReport(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();

        $products   = $this->summaryStockProducts();
        $categories = Category::query()->orderBy('name')->pluck('name');

        return view('frontend.report.summary_stock_reports', compact('company', 'products', 'categories'));
    }

    public function exportSummaryStockPdf(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();

        $products = $this->summaryStockProducts();

        $pdf = Pdf::loadView('frontend.report.summary_stock_pdf_reports', compact('company', 'products'));
        $pdf->setPaper('a4', 'landscape');
        return $pdf->download('summary-stock-report.pdf');
    }

    public function lowStockSummaryReport(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Product::query()->with(['category', 'stocks']);

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->search) {
            $search = $request->search;
            /** @disregard P0406 */
            $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($search) {
                $q->where('product_name', 'like', '%' . $search . '%')
                  ->orWhere('product_code', 'like', '%' . $search . '%');
            });
        }

        $allProducts = $query->get()->map(function (Product $p) {
            $stockQty = (float) $p->stocks->sum('quantity');
            $minStock = max((float) ($p->low_stock_threshold ?? 10), 1);
            $percentage = ($stockQty / $minStock) * 100;
            $value = $stockQty * (float) ($p->purchase_price ?? 0);

            if ($stockQty <= 0 || $percentage <= 25) {
                $status = 'critical';
            } elseif ($percentage <= 50) {
                $status = 'low';
            } elseif ($percentage <= 100) {
                $status = 'warning';
            } else {
                $status = 'normal';
            }

            $p->current_stock = $stockQty;
            $p->min_stock = $minStock;
            $p->stock_value = $value;
            $p->percentage = round($percentage);
            $p->stock_status = $status;
            return $p;
        });

        // Filter for items below minimum stock.
        $dataset = $allProducts->filter(function (Product $p) {
            return $p->stock_status !== 'normal';
        });

        if ($request->status && $request->status !== 'All') {
            $filterStatus = strtolower(explode(' ', $request->status)[0]); // 'critical', 'low', 'warning'
            $dataset = $dataset->filter(fn($p) => $p->stock_status === $filterStatus);
        }

        $criticalCount = $dataset->where('stock_status', 'critical')->count();
        $lowCount = $dataset->where('stock_status', 'low')->count();
        $warningCount = $dataset->where('stock_status', 'warning')->count();
        $totalItems = $dataset->count();
        $totalValueAtRisk = $dataset->sum('stock_value');

        // Category breakdown
        $categoryBreakdown = $dataset->groupBy('category_id')->map(function (\Illuminate\Support\Collection $items) {
            $cat = $items->first()->category;
            return (object)[
                'name' => $cat ? $cat->name : 'Uncategorized',
                'critical' => $items->where('stock_status', 'critical')->count(),
                'low' => $items->where('stock_status', 'low')->count(),
                'warning' => $items->where('stock_status', 'warning')->count(),
                'total' => $items->count(),
                'value' => $items->sum('stock_value')
            ];
        })->values();

        $categories = Category::query()->orderBy('name')->get();
        $filters = $request->only(['category_id', 'status', 'search']);

        return view('frontend.report.Low_stock_summary_reports', compact(
            'company', 'dataset', 'criticalCount', 'lowCount', 'warningCount', 'totalItems', 'totalValueAtRisk',
            'categoryBreakdown', 'categories', 'filters'
        ));
    }

    public function exportLowStockSummaryPdf(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Product::query()->with(['category', 'stocks']);

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->search) {
            $search = $request->search;
            /** @disregard P0406 */
            $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($search) {
                $q->where('product_name', 'like', '%' . $search . '%')
                  ->orWhere('product_code', 'like', '%' . $search . '%');
            });
        }

        $allProducts = $query->get()->map(function (Product $p) {
            $stockQty = (float) $p->stocks->sum('quantity');
            $minStock = max((float) ($p->low_stock_threshold ?? 10), 1);
            $percentage = ($stockQty / $minStock) * 100;
            $value = $stockQty * (float) ($p->purchase_price ?? 0);

            if ($stockQty <= 0 || $percentage <= 25) {
                $status = 'critical';
            } elseif ($percentage <= 50) {
                $status = 'low';
            } elseif ($percentage <= 100) {
                $status = 'warning';
            } else {
                $status = 'normal';
            }

            $p->current_stock = $stockQty;
            $p->min_stock = $minStock;
            $p->stock_value = $value;
            $p->percentage = round($percentage);
            $p->stock_status = $status;
            return $p;
        });

        $dataset = $allProducts->filter(function (Product $p) {
            return $p->stock_status !== 'normal';
        });

        if ($request->status && $request->status !== 'All') {
            $filterStatus = strtolower(explode(' ', $request->status)[0]);
            $dataset = $dataset->filter(fn($p) => $p->stock_status === $filterStatus);
        }

        $criticalCount = $dataset->where('stock_status', 'critical')->count();
        $lowCount = $dataset->where('stock_status', 'low')->count();
        $warningCount = $dataset->where('stock_status', 'warning')->count();
        $totalItems = $dataset->count();
        $totalValueAtRisk = $dataset->sum('stock_value');

        $categoryBreakdown = $dataset->groupBy('category_id')->map(function (\Illuminate\Support\Collection $items) {
            $cat = $items->first()->category;
            return (object)[
                'name' => $cat ? $cat->name : 'Uncategorized',
                'critical' => $items->where('stock_status', 'critical')->count(),
                'low' => $items->where('stock_status', 'low')->count(),
                'warning' => $items->where('stock_status', 'warning')->count(),
                'total' => $items->count(),
                'value' => $items->sum('stock_value')
            ];
        })->values();

        $filters = $request->only(['category_id', 'status', 'search']);

        $pdf = Pdf::loadView('frontend.report.Low_stock_summary_pdf_reports', compact(
            'company', 'dataset', 'criticalCount', 'lowCount', 'warningCount', 'totalItems', 'totalValueAtRisk',
            'categoryBreakdown', 'filters'
        ));
        $pdf->setPaper('a4', 'landscape');
        return $pdf->download('low-stock-summary-' . now()->format('Y-m-d') . '.pdf');
    }


    public function itemReportByParty(Request $request)
    {
        /** @var Company|null $company */
        $company   = Company::find(auth()->user()->company_id);
        $customers = Customer::query()->orderBy('name')->get();
        $suppliers = Supplier::query()->orderBy('name')->get();

        $items          = collect();
        $selectedParty  = null;
        $partyType      = null;
        $totalSaleQty   = 0;
        $totalSaleAmt   = 0;
        $totalPurchQty  = 0;
        $totalPurchAmt  = 0;

        $fromDate = $request->from_date ?? now()->startOfYear()->format('Y-m-d');
        $toDate   = $request->to_date   ?? now()->format('Y-m-d');

        if ($request->filled('party_type') && $request->filled('party_id')) {
            $partyType = $request->party_type; // 'customer' or 'supplier'
            $partyId   = $request->party_id;

            if ($partyType === 'customer') {
                $selectedParty = Customer::query()->find($partyId);

                // Sales for this customer grouped by product name
                $salesData = DB::table('sales_order_items')
                    ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
                    ->where('sales_orders.customer_id', $partyId)
                    ->whereBetween('sales_orders.invoice_date', [$fromDate, $toDate])
                    ->where('sales_orders.company_id', auth()->user()->company_id)
                    ->select(
                        DB::raw('COALESCE(sales_order_items.product_name, "Unknown") as product_name'),
                        DB::raw('SUM(sales_order_items.quantity) as sale_qty'),
                        DB::raw('SUM(sales_order_items.total_price) as sale_amount')
                    )
                    ->groupBy('product_name')
                    ->get()
                    ->keyBy('product_name');

                $items = $salesData->map(function (object $row) {
                    return (object)[
                        'name'            => $row->product_name,
                        'saleQty'         => (int) $row->sale_qty,
                        'saleAmount'      => (float) $row->sale_amount,
                        'purchaseQty'     => 0,
                        'purchaseAmount'  => 0,
                    ];
                });

            } elseif ($partyType === 'supplier') {
                $selectedParty = Supplier::query()->find($partyId);

                // Purchases for this supplier grouped by product name
                $purchaseData = DB::table('purchase_bill_items')
                    ->join('purchase_bills', 'purchase_bills.id', '=', 'purchase_bill_items.purchase_bill_id')
                    ->where('purchase_bills.supplier_id', $partyId)
                    ->whereBetween('purchase_bills.bill_date', [$fromDate, $toDate])
                    ->where('purchase_bills.company_id', auth()->user()->company_id)
                    ->select(
                        DB::raw('COALESCE(purchase_bill_items.product_name, "Unknown") as product_name'),
                        DB::raw('SUM(purchase_bill_items.quantity) as purchase_qty'),
                        DB::raw('SUM(purchase_bill_items.total_amount) as purchase_amount')
                    )
                    ->groupBy('product_name')
                    ->get()
                    ->keyBy('product_name');

                $items = $purchaseData->map(function (object $row) {
                    return (object)[
                        'name'            => $row->product_name,
                        'saleQty'         => 0,
                        'saleAmount'      => 0,
                        'purchaseQty'     => (int) $row->purchase_qty,
                        'purchaseAmount'  => (float) $row->purchase_amount,
                    ];
                });
            }

            $totalSaleQty  = $items->sum('saleQty');
            $totalSaleAmt  = $items->sum('saleAmount');
            $totalPurchQty = $items->sum('purchaseQty');
            $totalPurchAmt = $items->sum('purchaseAmount');
        }

        return view('frontend.report.Item_report_by_party', compact(
            'company', 'customers', 'suppliers',
            'items', 'selectedParty', 'partyType',
            'totalSaleQty', 'totalSaleAmt', 'totalPurchQty', 'totalPurchAmt',
            'fromDate', 'toDate'
        ));
    }

    public function exportItemReportByPartyPdf(Request $request)
    {
        /** @var Company|null $company */
        $company   = Company::find(auth()->user()->company_id);
        $fromDate = $request->from_date ?? now()->startOfYear()->format('Y-m-d');
        $toDate   = $request->to_date   ?? now()->format('Y-m-d');
        $partyType = $request->party_type;
        $partyId   = $request->party_id;

        $items         = collect();
        $selectedParty = null;

        if ($partyType === 'customer' && $partyId) {
            $selectedParty = Customer::query()->find($partyId);
            $salesData = DB::table('sales_order_items')
                ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
                ->where('sales_orders.customer_id', $partyId)
                ->whereBetween('sales_orders.invoice_date', [$fromDate, $toDate])
                ->where('sales_orders.company_id', auth()->user()->company_id)
                ->select(
                    DB::raw('COALESCE(sales_order_items.product_name, "Unknown") as product_name'),
                    DB::raw('SUM(sales_order_items.quantity) as sale_qty'),
                    DB::raw('SUM(sales_order_items.total_price) as sale_amount')
                )
                ->groupBy('product_name')->get();
            $items = $salesData->map(fn($r) => (object)['name' => $r->product_name, 'saleQty' => (int)$r->sale_qty, 'saleAmount' => (float)$r->sale_amount, 'purchaseQty' => 0, 'purchaseAmount' => 0]);

        } elseif ($partyType === 'supplier' && $partyId) {
            $selectedParty = Supplier::query()->find($partyId);
            $purchaseData = DB::table('purchase_bill_items')
                ->join('purchase_bills', 'purchase_bills.id', '=', 'purchase_bill_items.purchase_bill_id')
                ->where('purchase_bills.supplier_id', $partyId)
                ->whereBetween('purchase_bills.bill_date', [$fromDate, $toDate])
                ->where('purchase_bills.company_id', auth()->user()->company_id)
                ->select(
                    DB::raw('COALESCE(purchase_bill_items.product_name, "Unknown") as product_name'),
                    DB::raw('SUM(purchase_bill_items.quantity) as purchase_qty'),
                    DB::raw('SUM(purchase_bill_items.total_amount) as purchase_amount')
                )
                ->groupBy('product_name')->get();
            $items = $purchaseData->map(fn($r) => (object)['name' => $r->product_name, 'saleQty' => 0, 'saleAmount' => 0, 'purchaseQty' => (int)$r->purchase_qty, 'purchaseAmount' => (float)$r->purchase_amount]);
        }

        $totalSaleQty  = $items->sum('saleQty');
        $totalSaleAmt  = $items->sum('saleAmount');
        $totalPurchQty = $items->sum('purchaseQty');
        $totalPurchAmt = $items->sum('purchaseAmount');

        $pdf = Pdf::loadView('frontend.report.Item_report_by_party_pdf_reports', compact(
            'company', 'items', 'selectedParty', 'partyType',
            'totalSaleQty', 'totalSaleAmt', 'totalPurchQty', 'totalPurchAmt',
            'fromDate', 'toDate'
        ));
        $pdf->setPaper('a4', 'landscape');
        return $pdf->download('item-report-by-party.pdf');
    }

    public function itemWiseProfitLossReport(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $fromDate = $request->from_date ?? now()->startOfYear()->format('Y-m-d');
        $toDate   = $request->to_date   ?? now()->format('Y-m-d');
        $search   = $request->search;

        // Sales per product in period
        $salesData = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->whereBetween('sales_orders.invoice_date', [$fromDate, $toDate])
            ->where('sales_orders.company_id', auth()->user()->company_id)
            ->select(
                'sales_order_items.product_id',
                DB::raw('SUM(sales_order_items.quantity) as sale_qty'),
                DB::raw('SUM(sales_order_items.total_price) as sale_amount')
            )
            ->groupBy('sales_order_items.product_id')
            ->get()->keyBy('product_id');

        // Purchases per product in period
        $purchaseData = DB::table('purchase_bill_items')
            ->join('purchase_bills', 'purchase_bills.id', '=', 'purchase_bill_items.purchase_bill_id')
            ->whereBetween('purchase_bills.bill_date', [$fromDate, $toDate])
            ->where('purchase_bills.company_id', auth()->user()->company_id)
            ->select(
                'purchase_bill_items.product_id',
                DB::raw('SUM(purchase_bill_items.quantity) as purchase_qty'),
                DB::raw('SUM(purchase_bill_items.total_amount) as purchase_amount')
            )
            ->groupBy('purchase_bill_items.product_id')
            ->get()->keyBy('product_id');

        $productsQuery = DB::table('products')->where('company_id', auth()->user()->company_id)->orderBy('product_name');
        if ($search) {
            $productsQuery->where('product_name', 'like', '%' . $search . '%');
        }
        $products = $productsQuery->get();

        $items = $products->map(function (object $product) use ($salesData, $purchaseData) {
            $sale     = $salesData->get($product->id);
            $purchase = $purchaseData->get($product->id);
            $saleQty  = (float) ($sale->sale_qty ?? 0);
            $saleAmt  = (float) ($sale->sale_amount ?? 0);

            if ($saleQty > 0) {
                // COGS = units sold × unit cost price
                $unitCost    = (float) ($product->purchase_price ?? 0);
                $purchaseQty = $saleQty;
                $purchAmt    = round($saleQty * $unitCost, 2);
            } else {
                $purchaseQty = (float) ($purchase->purchase_qty ?? 0);
                $purchAmt    = (float) ($purchase->purchase_amount ?? 0);
            }

            return (object)[
                'name'            => $product->product_name,
                'code'            => $product->product_code,
                'saleQty'         => $saleQty,
                'saleAmount'      => $saleAmt,
                'purchaseQty'     => $purchaseQty,
                'purchaseAmount'  => $purchAmt,
                'netProfit'       => $saleAmt - $purchAmt,
            ];
        })->filter(fn(object $i) => $i->saleQty > 0 || $i->purchaseQty > 0 || !request('search'))->values();

        $totals = (object)[
            'saleQty'        => $items->sum('saleQty'),
            'saleAmount'     => $items->sum('saleAmount'),
            'purchaseQty'    => $items->sum('purchaseQty'),
            'purchaseAmount' => $items->sum('purchaseAmount'),
            'netProfit'      => $items->sum('netProfit'),
            'count'          => $items->count(),
        ];

        $filters = ['from_date' => $fromDate, 'to_date' => $toDate, 'search' => $search];

        return view('frontend.report.Item_wise_profit_Loss_report', compact(
            'company', 'items', 'totals', 'filters'
        ));
    }

    public function exportItemWiseProfitLossPdf(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $fromDate = $request->from_date ?? now()->startOfYear()->format('Y-m-d');
        $toDate   = $request->to_date   ?? now()->format('Y-m-d');

        $salesData = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->whereBetween('sales_orders.invoice_date', [$fromDate, $toDate])
            ->where('sales_orders.company_id', auth()->user()->company_id)
            ->select('sales_order_items.product_id',
                DB::raw('SUM(sales_order_items.quantity) as sale_qty'),
                DB::raw('SUM(sales_order_items.total_price) as sale_amount'))
            ->groupBy('sales_order_items.product_id')->get()->keyBy('product_id');

        $purchaseData = DB::table('purchase_bill_items')
            ->join('purchase_bills', 'purchase_bills.id', '=', 'purchase_bill_items.purchase_bill_id')
            ->whereBetween('purchase_bills.bill_date', [$fromDate, $toDate])
            ->where('purchase_bills.company_id', auth()->user()->company_id)
            ->select('purchase_bill_items.product_id',
                DB::raw('SUM(purchase_bill_items.quantity) as purchase_qty'),
                DB::raw('SUM(purchase_bill_items.total_amount) as purchase_amount'))
            ->groupBy('purchase_bill_items.product_id')->get()->keyBy('product_id');

        $products = DB::table('products')->where('company_id', auth()->user()->company_id)->orderBy('product_name')->get();
        $items = $products->map(function (object $product) use ($salesData, $purchaseData) {
            $sale     = $salesData->get($product->id);
            $purchase = $purchaseData->get($product->id);
            $saleQty  = (float) ($sale->sale_qty ?? 0);
            $saleAmt  = (float) ($sale->sale_amount ?? 0);

            if ($saleQty > 0) {
                $unitCost    = (float) ($product->purchase_price ?? 0);
                $purchaseQty = $saleQty;
                $purchAmt    = round($saleQty * $unitCost, 2);
            } else {
                $purchaseQty = (float) ($purchase->purchase_qty ?? 0);
                $purchAmt    = (float) ($purchase->purchase_amount ?? 0);
            }

            return (object)[
                'name'           => $product->product_name,
                'code'           => $product->product_code,
                'saleQty'        => $saleQty,
                'saleAmount'     => $saleAmt,
                'purchaseQty'    => $purchaseQty,
                'purchaseAmount' => $purchAmt,
                'netProfit'      => $saleAmt - $purchAmt,
            ];
        })->filter(fn($i) => $i->saleQty > 0 || $i->purchaseQty > 0)->values();

        $totals = (object)[
            'saleQty'        => $items->sum('saleQty'),
            'saleAmount'     => $items->sum('saleAmount'),
            'purchaseQty'    => $items->sum('purchaseQty'),
            'purchaseAmount' => $items->sum('purchaseAmount'),
            'netProfit'      => $items->sum('netProfit'),
        ];

        $filters = ['from_date' => $fromDate, 'to_date' => $toDate];
        $pdf = Pdf::loadView('frontend.report.Item_wise_profit_Loss_pdf_reports', compact(
            'company', 'items', 'totals', 'filters'
        ));
        $pdf->setPaper('a4', 'landscape');
        return $pdf->download('item-wise-profit-loss-' . now()->format('Y-m-d') . '.pdf');
    }

    public function itemCategoryWiseProfitLossReport(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $fromDate = $request->from_date ?? now()->startOfYear()->format('Y-m-d');
        $toDate   = $request->to_date   ?? now()->format('Y-m-d');
        $search   = $request->search;

        // Sales per product in period
        $salesData = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->whereBetween('sales_orders.invoice_date', [$fromDate, $toDate])
            ->where('sales_orders.company_id', auth()->user()->company_id)
            ->select(
                'sales_order_items.product_id',
                DB::raw('SUM(sales_order_items.quantity) as sale_qty'),
                DB::raw('SUM(sales_order_items.total_price) as sale_amount')
            )
            ->groupBy('sales_order_items.product_id')
            ->get()->keyBy('product_id');

        // Purchases per product in period
        $purchaseData = DB::table('purchase_bill_items')
            ->join('purchase_bills', 'purchase_bills.id', '=', 'purchase_bill_items.purchase_bill_id')
            ->whereBetween('purchase_bills.bill_date', [$fromDate, $toDate])
            ->where('purchase_bills.company_id', auth()->user()->company_id)
            ->select(
                'purchase_bill_items.product_id',
                DB::raw('SUM(purchase_bill_items.quantity) as purchase_qty'),
                DB::raw('SUM(purchase_bill_items.total_amount) as purchase_amount')
            )
            ->groupBy('purchase_bill_items.product_id')
            ->get()->keyBy('product_id');

        // Get Categories with Search filter
        $categoriesQuery = DB::table('categories')->where('company_id', auth()->user()->company_id)->orderBy('name');
        if ($search) {
            $categoriesQuery->where('name', 'like', '%' . $search . '%');
        }
        $categories = $categoriesQuery->get();

        // Get Products assigned to categories
        $products = DB::table('products')->where('company_id', auth()->user()->company_id)->whereNotNull('category_id')->get()->groupBy('category_id');

        $dataset = [];
        $grandTotals = [
            'saleQty' => 0, 'saleAmount' => 0,
            'purchaseQty' => 0, 'purchaseAmount' => 0,
            'netProfit' => 0, 'count' => 0
        ];

        foreach ($categories as $category) {
            $catProducts = $products->get($category->id, collect());
            
            $catItems = [];
            $catTotals = ['saleQty' => 0, 'saleAmount' => 0, 'purchaseQty' => 0, 'purchaseAmount' => 0, 'netProfit' => 0, 'count' => 0];

            foreach ($catProducts as $product) {
                $sale     = $salesData->get($product->id);
                $purchase = $purchaseData->get($product->id);
                $saleAmt  = (float) ($sale->sale_amount ?? 0);
                $purchAmt = (float) ($purchase->purchase_amount ?? 0);
                $saleQty  = (float) ($sale->sale_qty ?? 0);
                $purchQty = (float) ($purchase->purchase_qty ?? 0);
                $netProfit= $saleAmt - $purchAmt;

                if ($saleQty > 0 || $purchQty > 0 || !$search) {
                    $catItems[] = (object)[
                        'name'           => $product->product_name,
                        'code'           => $product->product_code,
                        'saleQty'        => $saleQty,
                        'saleAmount'     => $saleAmt,
                        'purchaseQty'    => $purchQty,
                        'purchaseAmount' => $purchAmt,
                        'netProfit'      => $netProfit,
                    ];

                    $catTotals['saleQty']        += $saleQty;
                    $catTotals['saleAmount']     += $saleAmt;
                    $catTotals['purchaseQty']    += $purchQty;
                    $catTotals['purchaseAmount'] += $purchAmt;
                    $catTotals['netProfit']      += $netProfit;
                    $catTotals['count']          += 1;
                }
            }

            if (count($catItems) > 0 || !$search) {
                $dataset[] = (object)[
                    'id'       => $category->id,
                    'name'     => $category->name,
                    'items'    => $catItems,
                    'totals'   => (object) $catTotals
                ];

                $grandTotals['saleQty']        += $catTotals['saleQty'];
                $grandTotals['saleAmount']     += $catTotals['saleAmount'];
                $grandTotals['purchaseQty']    += $catTotals['purchaseQty'];
                $grandTotals['purchaseAmount'] += $catTotals['purchaseAmount'];
                $grandTotals['netProfit']      += $catTotals['netProfit'];
                $grandTotals['count']          += 1; // Count categories
            }
        }

        $totals = (object) $grandTotals;
        $filters = ['from_date' => $fromDate, 'to_date' => $toDate, 'search' => $search];

        return view('frontend.report.Item_category_wise_profit_loss_reports', compact(
            'company', 'dataset', 'totals', 'filters'
        ));
    }

    public function exportItemCategoryWiseProfitLossPdf(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $fromDate = $request->from_date ?? now()->startOfYear()->format('Y-m-d');
        $toDate   = $request->to_date   ?? now()->format('Y-m-d');

        $salesData = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->whereBetween('sales_orders.invoice_date', [$fromDate, $toDate])
            ->where('sales_orders.company_id', auth()->user()->company_id)
            ->select('sales_order_items.product_id',
                DB::raw('SUM(sales_order_items.quantity) as sale_qty'),
                DB::raw('SUM(sales_order_items.total_price) as sale_amount'))
            ->groupBy('sales_order_items.product_id')->get()->keyBy('product_id');

        $purchaseData = DB::table('purchase_bill_items')
            ->join('purchase_bills', 'purchase_bills.id', '=', 'purchase_bill_items.purchase_bill_id')
            ->whereBetween('purchase_bills.bill_date', [$fromDate, $toDate])
            ->where('purchase_bills.company_id', auth()->user()->company_id)
            ->select('purchase_bill_items.product_id',
                DB::raw('SUM(purchase_bill_items.quantity) as purchase_qty'),
                DB::raw('SUM(purchase_bill_items.total_amount) as purchase_amount'))
            ->groupBy('purchase_bill_items.product_id')->get()->keyBy('product_id');

        $categories = DB::table('categories')->where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        $products = DB::table('products')->where('company_id', auth()->user()->company_id)->whereNotNull('category_id')->get()->groupBy('category_id');

        $dataset = [];
        $grandTotals = [
            'saleQty' => 0, 'saleAmount' => 0,
            'purchaseQty' => 0, 'purchaseAmount' => 0,
            'netProfit' => 0, 'count' => 0
        ];

        foreach ($categories as $category) {
            $catProducts = $products->get($category->id, collect());
            $catItems = [];
            $catTotals = ['saleQty' => 0, 'saleAmount' => 0, 'purchaseQty' => 0, 'purchaseAmount' => 0, 'netProfit' => 0];

            foreach ($catProducts as $product) {
                /** @var object $product */
                $sale     = $salesData->get($product->id);
                $purchase = $purchaseData->get($product->id);
                $saleAmt  = (float) ($sale->sale_amount ?? 0);
                $purchAmt = (float) ($purchase->purchase_amount ?? 0);
                $saleQty  = (float) ($sale->sale_qty ?? 0);
                $purchQty = (float) ($purchase->purchase_qty ?? 0);
                $netProfit= $saleAmt - $purchAmt;

                if ($saleQty > 0 || $purchQty > 0) {
                    $catItems[] = (object)[
                        'name'           => $product->product_name ?? 'N/A',
                        'code'           => $product->product_code ?? 'N/A',
                        'saleQty'        => $saleQty,
                        'saleAmount'     => $saleAmt,
                        'purchaseQty'    => $purchQty,
                        'purchaseAmount' => $purchAmt,
                        'netProfit'      => $netProfit,
                    ];
                    $catTotals['saleQty']        += $saleQty;
                    $catTotals['saleAmount']     += $saleAmt;
                    $catTotals['purchaseQty']    += $purchQty;
                    $catTotals['purchaseAmount'] += $purchAmt;
                    $catTotals['netProfit']      += $netProfit;
                }
            }

            if (count($catItems) > 0) {
                $dataset[] = (object)[
                    'name'     => $category->name,
                    'items'    => $catItems,
                    'totals'   => (object) $catTotals
                ];
                $grandTotals['saleQty']        += $catTotals['saleQty'];
                $grandTotals['saleAmount']     += $catTotals['saleAmount'];
                $grandTotals['purchaseQty']    += $catTotals['purchaseQty'];
                $grandTotals['purchaseAmount'] += $catTotals['purchaseAmount'];
                $grandTotals['netProfit']      += $catTotals['netProfit'];
                $grandTotals['count']          += 1;
            }
        }

        $totals = (object) $grandTotals;
        $filters = ['from_date' => $fromDate, 'to_date' => $toDate];

        $pdf = Pdf::loadView('frontend.report.Item_category_wise_profit_loss_pdf_reports', compact(
            'company', 'dataset', 'totals', 'filters'
        ));
        $pdf->setPaper('a4', 'landscape');
        return $pdf->download('item-category-wise-profit-loss-' . now()->format('Y-m-d') . '.pdf');
    }
    public function stockDetailsReport(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $fromDate = $request->from_date ?? now()->startOfYear()->format('Y-m-d');
        $toDate   = $request->to_date   ?? now()->format('Y-m-d');
        $search   = $request->search;
        $category_id = $request->category_id;

        /** @var \Illuminate\Database\Eloquent\Builder $productsQuery */
        $productsQuery = Product::query()->with(['category', 'stocks']);
        if ($category_id) {
            $productsQuery->where('category_id', $category_id);
        }
        if ($search) {
            $productsQuery->where('product_name', 'like', '%' . $search . '%');
        }
        $products = $productsQuery->orderBy('product_name')->get();

        // Stock IN (Purchases)
        $stockIn = DB::table('purchase_bill_items')
            ->join('purchase_bills', 'purchase_bills.id', '=', 'purchase_bill_items.purchase_bill_id')
            ->where('purchase_bills.company_id', auth()->user()->company_id)
            ->select('purchase_bill_items.product_id',
                DB::raw('SUM(CASE WHEN bill_date BETWEEN "' . $fromDate . '" AND "' . $toDate . '" THEN purchase_bill_items.quantity ELSE 0 END) as qty_period'),
                DB::raw('SUM(CASE WHEN bill_date BETWEEN "' . $fromDate . '" AND "' . $toDate . '" THEN purchase_bill_items.total_amount ELSE 0 END) as amount_period'),
                DB::raw('SUM(CASE WHEN bill_date > "' . $toDate . '" THEN purchase_bill_items.quantity ELSE 0 END) as qty_after')
            )
            ->groupBy('purchase_bill_items.product_id')->get()->keyBy('product_id');

        // Stock OUT (Sales)
        $stockOut = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->where('sales_orders.company_id', auth()->user()->company_id)
            ->select('sales_order_items.product_id',
                DB::raw('SUM(CASE WHEN invoice_date BETWEEN "' . $fromDate . '" AND "' . $toDate . '" THEN sales_order_items.quantity ELSE 0 END) as qty_period'),
                DB::raw('SUM(CASE WHEN invoice_date BETWEEN "' . $fromDate . '" AND "' . $toDate . '" THEN sales_order_items.total_price ELSE 0 END) as amount_period'),
                DB::raw('SUM(CASE WHEN invoice_date > "' . $toDate . '" THEN sales_order_items.quantity ELSE 0 END) as qty_after')
            )
            ->groupBy('sales_order_items.product_id')->get()->keyBy('product_id');

        $dataset = $products->map(function(Product $product) use ($stockIn, $stockOut) {
            $in  = $stockIn->get($product->id);
            $out = $stockOut->get($product->id);

            $currentStock = $product->stocks->sum('quantity');

            // Closing Stock = Current - (In after to_date) + (Out after to_date)
            $qtyInAfter  = (float) ($in->qty_after ?? 0);
            $qtyOutAfter = (float) ($out->qty_after ?? 0);
            $closingQty  = $currentStock - $qtyInAfter + $qtyOutAfter;

            // Beginning Stock = Closing - (In in period) + (Out in period)
            $qtyInPeriod  = (float) ($in->qty_period ?? 0);
            $qtyOutPeriod = (float) ($out->qty_period ?? 0);
            $beginningQty = $closingQty - $qtyInPeriod + $qtyOutPeriod;

            return (object)[
                'id'             => $product->id,
                'name'           => $product->product_name,
                'category'       => $product->category->name ?? 'Uncategorized',
                'beginningQty'   => $beginningQty,
                'qtyIn'          => $qtyInPeriod,
                'purchaseAmount' => (float) ($in->amount_period ?? 0),
                'qtyOut'         => $qtyOutPeriod,
                'saleAmount'     => (float) ($out->amount_period ?? 0),
                'closingQty'     => $closingQty
            ];
        });

        $totals = (object)[
            'beginningQty'   => $dataset->sum('beginningQty'),
            'qtyIn'          => $dataset->sum('qtyIn'),
            'purchaseAmount' => $dataset->sum('purchaseAmount'),
            'qtyOut'         => $dataset->sum('qtyOut'),
            'saleAmount'     => $dataset->sum('saleAmount'),
            'closingQty'     => $dataset->sum('closingQty'),
        ];

        $categories = Category::query()->orderBy('name')->get();
        $filters = ['from_date' => $fromDate, 'to_date' => $toDate, 'search' => $search, 'category_id' => $category_id];

        return view('frontend.report.stock_details_reports', compact(
            'company', 'dataset', 'totals', 'categories', 'filters'
        ));
    }

    public function exportStockDetailsPdf(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $fromDate = $request->from_date ?? now()->startOfYear()->format('Y-m-d');
        $toDate   = $request->to_date   ?? now()->format('Y-m-d');
        $search   = $request->search;
        $category_id = $request->category_id;

        /** @var \Illuminate\Database\Eloquent\Builder $productsQuery */
        $productsQuery = Product::query()->with(['category', 'stocks']);
        if ($category_id) $productsQuery->where('category_id', $category_id);
        if ($search) $productsQuery->where('product_name', 'like', '%' . $search . '%');
        
        $products = $productsQuery->orderBy('product_name')->get();

        $stockIn = DB::table('purchase_bill_items')
            ->join('purchase_bills', 'purchase_bills.id', '=', 'purchase_bill_items.purchase_bill_id')
            ->where('purchase_bills.company_id', auth()->user()->company_id)
            ->select('purchase_bill_items.product_id',
                DB::raw('SUM(CASE WHEN bill_date BETWEEN "' . $fromDate . '" AND "' . $toDate . '" THEN purchase_bill_items.quantity ELSE 0 END) as qty_period'),
                DB::raw('SUM(CASE WHEN bill_date BETWEEN "' . $fromDate . '" AND "' . $toDate . '" THEN purchase_bill_items.total_amount ELSE 0 END) as amount_period'),
                DB::raw('SUM(CASE WHEN bill_date > "' . $toDate . '" THEN purchase_bill_items.quantity ELSE 0 END) as qty_after')
            )
            ->groupBy('purchase_bill_items.product_id')->get()->keyBy('product_id');

        $stockOut = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->where('sales_orders.company_id', auth()->user()->company_id)
            ->select('sales_order_items.product_id',
                DB::raw('SUM(CASE WHEN invoice_date BETWEEN "' . $fromDate . '" AND "' . $toDate . '" THEN sales_order_items.quantity ELSE 0 END) as qty_period'),
                DB::raw('SUM(CASE WHEN invoice_date BETWEEN "' . $fromDate . '" AND "' . $toDate . '" THEN sales_order_items.total_price ELSE 0 END) as amount_period'),
                DB::raw('SUM(CASE WHEN invoice_date > "' . $toDate . '" THEN sales_order_items.quantity ELSE 0 END) as qty_after')
            )
            ->groupBy('sales_order_items.product_id')->get()->keyBy('product_id');

        $dataset = $products->map(function(Product $product) use ($stockIn, $stockOut) {
            $in  = $stockIn->get($product->id);
            $out = $stockOut->get($product->id);
            $currentStock = $product->stocks->sum('quantity');

            $closingQty  = $currentStock - (float)($in->qty_after ?? 0) + (float)($out->qty_after ?? 0);
            $beginningQty = $closingQty - (float)($in->qty_period ?? 0) + (float)($out->qty_period ?? 0);

            return (object)[
                'name'           => $product->product_name,
                'category'       => $product->category->name ?? 'Uncategorized',
                'beginningQty'   => $beginningQty,
                'qtyIn'          => (float)($in->qty_period ?? 0),
                'purchaseAmount' => (float)($in->amount_period ?? 0),
                'qtyOut'         => (float)($out->qty_period ?? 0),
                'saleAmount'     => (float)($out->amount_period ?? 0),
                'closingQty'     => $closingQty
            ];
        });

        $totals = (object)[
            'beginningQty'   => $dataset->sum('beginningQty'),
            'qtyIn'          => $dataset->sum('qtyIn'),
            'purchaseAmount' => $dataset->sum('purchaseAmount'),
            'qtyOut'         => $dataset->sum('qtyOut'),
            'saleAmount'     => $dataset->sum('saleAmount'),
            'closingQty'     => $dataset->sum('closingQty'),
        ];

        $filters = ['from_date' => $fromDate, 'to_date' => $toDate];
        $pdf = Pdf::loadView('frontend.report.stock_details_pdf_reports', compact('company', 'dataset', 'totals', 'filters'));
        $pdf->setPaper('a4', 'landscape');
        return $pdf->download('stock-details-' . now()->format('Y-m-d') . '.pdf');
    }
    public function itemsDetailsReport(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $fromDate = $request->from_date ?? now()->startOfYear()->format('Y-m-d');
        $toDate   = $request->to_date   ?? now()->format('Y-m-d');
        $productId = $request->product_id;

        $products = Product::query()->orderBy('product_name')->get();
        /** @var Product|null $selectedProduct */
        $selectedProduct = $productId ? Product::query()->with('category')->find($productId) : null;

        $dataset = collect();
        $totals = (object)['sales' => 0, 'purchases' => 0, 'adjustments' => 0, 'closing' => 0, 'opening' => 0];

        if ($selectedProduct) {
            // Purchases
            $purchQuery = DB::table('purchase_bill_items')
                ->join('purchase_bills', 'purchase_bills.id', '=', 'purchase_bill_items.purchase_bill_id')
                ->where('purchase_bill_items.product_id', $productId)
                ->where('purchase_bills.company_id', auth()->user()->company_id);

            $purchBefore = (float)$purchQuery->clone()->where('bill_date', '<', $fromDate)->sum('quantity');
            $purchPeriod = $purchQuery->clone()->whereBetween('bill_date', [$fromDate, $toDate])
                ->select('bill_date as date', 'quantity', DB::raw("'Purchase' as type"))->get();

            // Sales
            $saleQuery = DB::table('sales_order_items')
                ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
                ->where('sales_order_items.product_id', $productId)
                ->where('sales_orders.company_id', auth()->user()->company_id);

            $saleBefore = (float)$saleQuery->clone()->where('invoice_date', '<', $fromDate)->sum('quantity');
            $salePeriod = $saleQuery->clone()->whereBetween('invoice_date', [$fromDate, $toDate])
                ->select('invoice_date as date', 'quantity', DB::raw("'Sale' as type"))->get();

            // Adjustments or Stock Movements (generic)
            $moveQuery = DB::table('stock_movements')
                ->where('product_id', $productId)
                ->where('stock_movements.company_id', auth()->user()->company_id)
                ->whereNotIn('type', ['sale', 'purchase']); // Only non-sale, non-purchase if they are duplicated elsewhere

            $moveBefore = (float)$moveQuery->clone()->where('created_at', '<', $fromDate)->sum('quantity');
            $movePeriod = $moveQuery->clone()->whereBetween('created_at', [$fromDate, $toDate])
                ->select('created_at as date', 'quantity', 'type')->get();

            $openingStock = $purchBefore - $saleBefore + $moveBefore;
            $totals->opening = $openingStock;

            $allRecords = $purchPeriod->union($salePeriod)->union($movePeriod)->sortBy('date');

            $currentBalance = $openingStock;
            foreach ($allRecords as $record) {
                /** @var object $record */
                $qty = (float)$record->quantity;
                if ($record->type == 'Sale') {
                    $currentBalance -= $qty;
                    $totals->sales += $qty;
                } elseif ($record->type == 'Purchase') {
                    $currentBalance += $qty;
                    $totals->purchases += $qty;
                } else {
                    $currentBalance += $qty;
                    $totals->adjustments += $qty;
                }
                $record->closing = $currentBalance;
                $dataset->push($record);
            }
            $totals->closing = $currentBalance;
        }

        $filters = ['from_date' => $fromDate, 'to_date' => $toDate, 'product_id' => $productId];
        $categories = Category::query()->orderBy('name')->get();

        return view('frontend.report.Item_details_reports', compact(
            'company', 'products', 'selectedProduct', 'dataset', 'totals', 'filters', 'categories'
        ));
    }

    public function exportItemsDetailsPdf(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $fromDate = $request->from_date ?? now()->startOfYear()->format('Y-m-d');
        $toDate   = $request->to_date   ?? now()->format('Y-m-d');
        $productId = $request->product_id;

        /** @var Product|null $selectedProduct */
        $selectedProduct = $productId ? Product::query()->find($productId) : null;
        if (!$selectedProduct) return back();

        // Re-use logic for consistency
        $dataset = collect();
        $totals = (object)['sales' => 0, 'purchases' => 0, 'adjustments' => 0, 'closing' => 0, 'opening' => 0];

        $purchBefore = (float)DB::table('purchase_bill_items')->join('purchase_bills', 'purchase_bills.id', '=', 'purchase_bill_items.purchase_bill_id')->where('purchase_bill_items.product_id', $productId)->where('purchase_bills.company_id', auth()->user()->company_id)->where('bill_date', '<', $fromDate)->sum('quantity');
        $saleBefore = (float)DB::table('sales_order_items')->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')->where('sales_order_items.product_id', $productId)->where('sales_orders.company_id', auth()->user()->company_id)->where('invoice_date', '<', $fromDate)->sum('quantity');
        $moveBefore = (float)DB::table('stock_movements')->where('product_id', $productId)->where('company_id', auth()->user()->company_id)->whereNotIn('type', ['sale', 'purchase'])->where('created_at', '<', $fromDate)->sum('quantity');

        $openingStock = $purchBefore - $saleBefore + $moveBefore;
        $totals->opening = $openingStock;

        $purchPeriod = DB::table('purchase_bill_items')->join('purchase_bills', 'purchase_bills.id', '=', 'purchase_bill_items.purchase_bill_id')->where('purchase_bill_items.product_id', $productId)->where('purchase_bills.company_id', auth()->user()->company_id)->whereBetween('bill_date', [$fromDate, $toDate])->select('bill_date as date', 'quantity', DB::raw("'Purchase' as type"))->get();
        $salePeriod = DB::table('sales_order_items')->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')->where('sales_order_items.product_id', $productId)->where('sales_orders.company_id', auth()->user()->company_id)->whereBetween('invoice_date', [$fromDate, $toDate])->select('invoice_date as date', 'quantity', DB::raw("'Sale' as type"))->get();
        $movePeriod = DB::table('stock_movements')->where('product_id', $productId)->where('company_id', auth()->user()->company_id)->whereNotIn('type', ['sale', 'purchase'])->whereBetween('created_at', [$fromDate, $toDate])->select('created_at as date', 'quantity', 'type')->get();

        $allRecords = $purchPeriod->union($salePeriod)->union($movePeriod)->sortBy('date');

        $currentBalance = $openingStock;
        foreach ($allRecords as $record) {
            $qty = (float)$record->quantity;
            if ($record->type == 'Sale') {
                $currentBalance -= $qty;
                $totals->sales += $qty;
            } elseif ($record->type == 'Purchase') {
                $currentBalance += $qty;
                $totals->purchases += $qty;
            } else {
                $currentBalance += $qty;
                $totals->adjustments += $qty;
            }
            $record->closing = $currentBalance;
            $dataset->push($record);
        }
        $totals->closing = $currentBalance;

        $filters = ['from_date' => $fromDate, 'to_date' => $toDate];
        $pdf = Pdf::loadView('frontend.report.Item_details_pdf_reports', compact('company', 'selectedProduct', 'dataset', 'totals', 'filters'));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('item-details-' . ($selectedProduct->product_name) . '.pdf');
    }
    public function salePurchaseByItemCategoryReport(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $categories = Category::query()->orderBy('name')->get();
        
        $fromDate = $request->from_date ?? now()->startOfYear()->format('Y-m-d');
        $toDate = $request->to_date ?? now()->format('Y-m-d');
        $categoryId = $request->category_id;

        // Sales Data
        $salesQuery = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->join('products', 'products.id', '=', 'sales_order_items.product_id')
            ->whereBetween('sales_orders.invoice_date', [$fromDate, $toDate])
            ->where('sales_orders.company_id', auth()->user()->company_id);

        if ($categoryId) {
            $salesQuery->where('products.category_id', $categoryId);
        }

        $salesByCategory = $salesQuery->select(
            'products.category_id',
            DB::raw('SUM(sales_order_items.quantity) as total_qty'),
            DB::raw('SUM(sales_order_items.quantity * sales_order_items.unit_price) as total_amount')
        )->groupBy('products.category_id')->get()->keyBy('category_id');

        // Purchase Data
        $purchaseQuery = DB::table('purchase_bill_items')
            ->join('purchase_bills', 'purchase_bills.id', '=', 'purchase_bill_items.purchase_bill_id')
            ->join('products', 'products.id', '=', 'purchase_bill_items.product_id')
            ->whereBetween('purchase_bills.bill_date', [$fromDate, $toDate])
            ->where('purchase_bills.company_id', auth()->user()->company_id);

        if ($categoryId) {
            $purchaseQuery->where('products.category_id', $categoryId);
        }

        $purchasesByCategory = $purchaseQuery->select(
            'products.category_id',
            DB::raw('SUM(purchase_bill_items.quantity) as total_qty'),
            DB::raw('SUM(purchase_bill_items.quantity * purchase_bill_items.unit_price) as total_amount')
        )->groupBy('products.category_id')->get()->keyBy('category_id');

        // Cost Data for Profitability
        $costQuery = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->join('products', 'products.id', '=', 'sales_order_items.product_id')
            ->whereBetween('sales_orders.invoice_date', [$fromDate, $toDate])
            ->where('sales_orders.company_id', auth()->user()->company_id);

        if ($categoryId) {
            $costQuery->where('products.category_id', $categoryId);
        }

        $costByCategory = $costQuery->select(
            'products.category_id',
            DB::raw('SUM(sales_order_items.quantity * products.purchase_price) as total_cost')
        )->groupBy('products.category_id')->get()->keyBy('category_id');

        $dataset = $categories->map(function(object $cat) use ($salesByCategory, $purchasesByCategory, $costByCategory) {
            $sales = $salesByCategory->get($cat->id);
            $purchases = $purchasesByCategory->get($cat->id);
            $cost = $costByCategory->get($cat->id);

            $saleAmt = $sales->total_amount ?? 0;
            $saleQty = $sales->total_qty ?? 0;
            $purchAmt = $purchases->total_amount ?? 0;
            $purchQty = $purchases->total_qty ?? 0;
            $totalCost = $cost->total_cost ?? 0;

            $grossProfit = $saleAmt - $totalCost;
            $margin = $saleAmt > 0 ? ($grossProfit / $saleAmt) * 100 : 0;

            return (object)[
                'id' => $cat->id,
                'name' => $cat->name,
                'sale_qty' => $saleQty,
                'sale_amount' => $saleAmt,
                'purchase_qty' => $purchQty,
                'purchase_amount' => $purchAmt,
                'gross_profit' => $grossProfit,
                'margin' => $margin
            ];
        });

        // Filter if category_id provided in request
        if ($categoryId) {
            $dataset = $dataset->where('id', $categoryId);
        }

        $totals = (object)[
            'sale_qty' => $dataset->sum('sale_qty'),
            'sale_amount' => $dataset->sum('sale_amount'),
            'purchase_qty' => $dataset->sum('purchase_qty'),
            'purchase_amount' => $dataset->sum('purchase_amount'),
            'gross_profit' => $dataset->sum('gross_profit'),
        ];

        $topPerformer = $dataset->sortByDesc('margin')->first();
        $totalVolume = $totals->sale_qty;
        $topVolumeShare = ($totalVolume > 0 && $topPerformer) ? ($topPerformer->sale_qty / $totalVolume) * 100 : 0;

        $filters = ['from_date' => $fromDate, 'to_date' => $toDate, 'category_id' => $categoryId];

        return view('frontend.report.sale_purchase_item_category_reports', compact(
            'company', 'categories', 'dataset', 'totals', 'filters', 'topPerformer', 'topVolumeShare'
        ));
    }

    public function exportSalePurchaseByItemCategoryPdf(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $categories = Category::query()->orderBy('name')->get();
        
        $fromDate = $request->from_date ?? now()->startOfYear()->format('Y-m-d');
        $toDate = $request->to_date ?? now()->format('Y-m-d');
        $categoryId = $request->category_id;

        // Sales Data
        $salesQuery = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->join('products', 'products.id', '=', 'sales_order_items.product_id')
            ->whereBetween('sales_orders.invoice_date', [$fromDate, $toDate])
            ->where('sales_orders.company_id', auth()->user()->company_id);

        if ($categoryId) {
            $salesQuery->where('products.category_id', $categoryId);
        }

        $salesByCategory = $salesQuery->select(
            'products.category_id',
            DB::raw('SUM(sales_order_items.quantity) as total_qty'),
            DB::raw('SUM(sales_order_items.quantity * sales_order_items.unit_price) as total_amount')
        )->groupBy('products.category_id')->get()->keyBy('category_id');

        // Purchase Data
        $purchaseQuery = DB::table('purchase_bill_items')
            ->join('purchase_bills', 'purchase_bills.id', '=', 'purchase_bill_items.purchase_bill_id')
            ->join('products', 'products.id', '=', 'purchase_bill_items.product_id')
            ->whereBetween('purchase_bills.bill_date', [$fromDate, $toDate])
            ->where('purchase_bills.company_id', auth()->user()->company_id);

        if ($categoryId) {
            $purchaseQuery->where('products.category_id', $categoryId);
        }

        $purchasesByCategory = $purchaseQuery->select(
            'products.category_id',
            DB::raw('SUM(purchase_bill_items.quantity) as total_qty'),
            DB::raw('SUM(purchase_bill_items.quantity * purchase_bill_items.unit_price) as total_amount')
        )->groupBy('products.category_id')->get()->keyBy('category_id');

        $dataset = $categories->map(function(object $cat) use ($salesByCategory, $purchasesByCategory) {
            $sales = $salesByCategory->get($cat->id);
            $purchases = $purchasesByCategory->get($cat->id);

            $saleAmt = $sales->total_amount ?? 0;
            $saleQty = $sales->total_qty ?? 0;
            $purchAmt = $purchases->total_amount ?? 0;
            $purchQty = $purchases->total_qty ?? 0;

            return (object)[
                'name' => $cat->name,
                'sale_qty' => $saleQty,
                'sale_amount' => $saleAmt,
                'purchase_qty' => $purchQty,
                'purchase_amount' => $purchAmt,
            ];
        })->filter(fn($item) => $item->sale_qty > 0 || $item->purchase_qty > 0);

        $totals = (object)[
            'sale_qty' => $dataset->sum('sale_qty'),
            'sale_amount' => $dataset->sum('sale_amount'),
            'purchase_qty' => $dataset->sum('purchase_qty'),
            'purchase_amount' => $dataset->sum('purchase_amount'),
        ];

        $filters = ['from_date' => $fromDate, 'to_date' => $toDate];
        $pdf = Pdf::loadView('frontend.report.sale_purchase_item_category_pdf_reports', compact('company', 'dataset', 'totals', 'filters'));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('sale-purchase-category-' . now()->format('Y-m-d') . '.pdf');
    }
    public function itemWiseDiscountReport(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $fromDate = $request->from_date ?? now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? now()->format('Y-m-d');

        $query = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->whereBetween('sales_orders.invoice_date', [$fromDate, $toDate])
            ->where('sales_orders.company_id', auth()->user()->company_id);

        if ($request->search) {
            $s = $request->search;
            $query->where('sales_order_items.product_name', 'like', "%$s%");
        }

        $reportData = (clone $query)
            ->select(
                'sales_order_items.product_id',
                'sales_order_items.product_name',
                DB::raw('SUM(sales_order_items.quantity) as total_qty'),
                DB::raw('SUM(sales_order_items.quantity * sales_order_items.unit_price) as total_sale_amount'),
                DB::raw('SUM(sales_order_items.discount) as total_discount_amount')
            )
            ->groupBy('sales_order_items.product_id', 'sales_order_items.product_name')
            ->orderBy('total_discount_amount', 'desc')
            ->get();

        $totals = (object)[
            'sale_amount' => $reportData->sum('total_sale_amount'),
            'discount_amount' => $reportData->sum('total_discount_amount')
        ];

        $filters = ['from_date' => $fromDate, 'to_date' => $toDate, 'search' => $request->search];

        return view('frontend.report.Item_wise_discount_reports', compact('company', 'reportData', 'totals', 'filters'));
    }

    public function exportItemWiseDiscountPdf(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $fromDate = $request->from_date ?? now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? now()->format('Y-m-d');

        $query = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->whereBetween('sales_orders.invoice_date', [$fromDate, $toDate])
            ->where('sales_orders.company_id', auth()->user()->company_id);

        if ($request->search) {
            $query->where('sales_order_items.product_name', 'like', "%{$request->search}%");
        }

        $reportData = $query->select(
            'sales_order_items.product_id',
            'sales_order_items.product_name',
            DB::raw('SUM(sales_order_items.quantity) as total_qty'),
            DB::raw('SUM(sales_order_items.quantity * sales_order_items.unit_price) as total_sale_amount'),
            DB::raw('SUM(sales_order_items.discount) as total_discount_amount')
        )
        ->groupBy('sales_order_items.product_id', 'sales_order_items.product_name')
        ->orderBy('total_discount_amount', 'desc')
        ->get();

        $totals = (object)[
            'sale_amount' => $reportData->sum('total_sale_amount'),
            'discount_amount' => $reportData->sum('total_discount_amount')
        ];

        $filters = ['from_date' => $fromDate, 'to_date' => $toDate];
        $pdf = Pdf::loadView('frontend.report.Item_wise_discount_pdf_reports', compact('company', 'reportData', 'totals', 'filters'));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('item-wise-discount-' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportItemWiseDiscountExcel(Request $request)
    {
        $fromDate = $request->from_date ?? now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? now()->format('Y-m-d');

        $query = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->whereBetween('sales_orders.invoice_date', [$fromDate, $toDate])
            ->where('sales_orders.company_id', auth()->user()->company_id);

        if ($request->search) {
            $query->where('sales_order_items.product_name', 'like', "%{$request->search}%");
        }

        $reportData = $query->select(
            'sales_order_items.product_name',
            DB::raw('SUM(sales_order_items.quantity) as total_qty'),
            DB::raw('SUM(sales_order_items.quantity * sales_order_items.unit_price) as total_sale_amount'),
            DB::raw('SUM(sales_order_items.discount) as total_discount_amount')
        )
        ->groupBy('sales_order_items.product_id', 'sales_order_items.product_name')
        ->orderBy('total_discount_amount', 'desc')
        ->get();

        $filename = 'item-wise-discount-' . now()->format('Y-m-d') . '.csv';
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"$filename\""];

        $callback = function () use ($reportData) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['#', 'Item Name', 'Total Qty Sold', 'Total Sale Amount', 'Total Discount Amount', 'Avg. Discount (%)']);

            foreach ($reportData as $i => $row) {
                $avg = $row->total_sale_amount > 0 ? ($row->total_discount_amount / $row->total_sale_amount) * 100 : 0;
                fputcsv($handle, [
                    $i + 1,
                    $row->product_name,
                    $row->total_qty,
                    number_format($row->total_sale_amount, 2),
                    number_format($row->total_discount_amount, 2),
                    number_format($avg, 2) . '%'
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
    public function expenseCategoryReport(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $fromDate = $request->from_date ?? now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? now()->format('Y-m-d');
        $branchId = $request->branch_id;

        $query = DB::table('expenses')
            ->join('chart_of_accounts', 'expenses.expense_account_id', '=', 'chart_of_accounts.id')
            ->whereBetween('expenses.expense_date', [$fromDate, $toDate])
            ->where('expenses.company_id', auth()->user()->company_id);

        if ($branchId) {
            $query->where('expenses.branch_id', $branchId);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where('chart_of_accounts.name', 'like', "%$s%");
        }

        $reportData = (clone $query)
            ->select(
                'chart_of_accounts.name as category_name',
                'chart_of_accounts.code as category_code',
                DB::raw('COUNT(expenses.id) as transaction_count'),
                DB::raw('SUM(expenses.amount) as total_amount'),
                'chart_of_accounts.id as account_id'
            )
            ->groupBy('chart_of_accounts.id', 'chart_of_accounts.name', 'chart_of_accounts.code')
            ->orderBy('total_amount', 'desc')
            ->get();

        $totalExpense = $reportData->sum('total_amount');
        $branches = Branch::query()->orderBy('name')->get();
        $filters = ['from_date' => $fromDate, 'to_date' => $toDate, 'branch_id' => $branchId, 'search' => $request->search];

        return view('frontend.report.expense_category_report', compact('company', 'reportData', 'totalExpense', 'branches', 'filters'));
    }

    public function exportExpenseCategoryPdf(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $fromDate = $request->from_date ?? now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? now()->format('Y-m-d');

        $query = DB::table('expenses')
            ->join('chart_of_accounts', 'expenses.expense_account_id', '=', 'chart_of_accounts.id')
            ->whereBetween('expenses.expense_date', [$fromDate, $toDate])
            ->where('expenses.company_id', auth()->user()->company_id);

        if ($request->branch_id) {
            $query->where('expenses.branch_id', $request->branch_id);
        }

        $reportData = $query->select(
            'chart_of_accounts.name as category_name',
            'chart_of_accounts.code as category_code',
            DB::raw('COUNT(expenses.id) as transaction_count'),
            DB::raw('SUM(expenses.amount) as total_amount')
        )
        ->groupBy('chart_of_accounts.id', 'chart_of_accounts.name', 'chart_of_accounts.code')
        ->orderBy('total_amount', 'desc')
        ->get();

        $totalExpense = $reportData->sum('total_amount');
        $filters = ['from_date' => $fromDate, 'to_date' => $toDate];

        $pdf = Pdf::loadView('frontend.report.expense_category_pdf_reports', compact('company', 'reportData', 'totalExpense', 'filters'));
        return $pdf->download('expense-category-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportExpenseCategoryExcel(Request $request)
    {
        $fromDate = $request->from_date ?? now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? now()->format('Y-m-d');

        $query = DB::table('expenses')
            ->join('chart_of_accounts', 'expenses.expense_account_id', '=', 'chart_of_accounts.id')
            ->whereBetween('expenses.expense_date', [$fromDate, $toDate])
            ->where('expenses.company_id', auth()->user()->company_id);

        $reportData = $query->select(
            'chart_of_accounts.name as category_name',
            'chart_of_accounts.code as category_code',
            DB::raw('COUNT(expenses.id) as transaction_count'),
            DB::raw('SUM(expenses.amount) as total_amount')
        )
        ->groupBy('chart_of_accounts.id', 'chart_of_accounts.name', 'chart_of_accounts.code')
        ->orderBy('total_amount', 'desc')
        ->get();

        $filename = 'expense-category-report-' . now()->format('Y-m-d') . '.csv';
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"$filename\""];

        $callback = function () use ($reportData) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['#', 'Category Name', 'Category Code', 'Transactions', 'Total Amount']);

            foreach ($reportData as $i => $row) {
                fputcsv($handle, [
                    $i + 1,
                    $row->category_name,
                    $row->category_code,
                    $row->transaction_count,
                    number_format($row->total_amount, 2)
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
    public function expenseItemReport(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $fromDate = $request->from_date ?? now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? now()->format('Y-m-d');
        $branchId = $request->branch_id;

        $query = DB::table('expenses')
            ->whereBetween('expense_date', [$fromDate, $toDate])
            ->where('company_id', auth()->user()->company_id);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where('expense_name', 'like', "%$s%");
        }

        $reportData = (clone $query)
            ->select(
                'expense_name',
                DB::raw('COUNT(*) as total_count'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('AVG(amount) as avg_amount')
            )
            ->groupBy('expense_name')
            ->orderBy('total_amount', 'desc')
            ->get();

        $totalExpense = $reportData->sum('total_amount');
        $totalQuantity = $reportData->sum('total_count');
        $branches = Branch::query()->orderBy('name')->get();
        $filters = ['from_date' => $fromDate, 'to_date' => $toDate, 'branch_id' => $branchId, 'search' => $request->search];

        return view('frontend.report.expense_item_report', compact('company', 'reportData', 'totalExpense', 'totalQuantity', 'branches', 'filters'));
    }

    public function exportExpenseItemPdf(Request $request)
    {
        /** @var Company|null $company */
        $id = auth()->user()->company_id;
        $company = $id ? Company::find($id) : Company::first();
        $fromDate = $request->from_date ?? now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? now()->format('Y-m-d');

        $query = DB::table('expenses')
            ->whereBetween('expense_date', [$fromDate, $toDate])
            ->where('company_id', auth()->user()->company_id);

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        $reportData = $query->select(
            'expense_name',
            DB::raw('COUNT(*) as total_count'),
            DB::raw('SUM(amount) as total_amount'),
            DB::raw('AVG(amount) as avg_amount')
        )
        ->groupBy('expense_name')
        ->orderBy('total_amount', 'desc')
        ->get();

        $totalExpense = $reportData->sum('total_amount');
        $totalQuantity = $reportData->sum('total_count');
        $filters = ['from_date' => $fromDate, 'to_date' => $toDate];

        $pdf = Pdf::loadView('frontend.report.expense_item_pdf_reports', compact('company', 'reportData', 'totalExpense', 'totalQuantity', 'filters'));
        return $pdf->download('expense-item-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportExpenseItemExcel(Request $request)
    {
        $fromDate = $request->from_date ?? now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? now()->format('Y-m-d');

        $query = DB::table('expenses')
            ->whereBetween('expense_date', [$fromDate, $toDate])
            ->where('company_id', auth()->user()->company_id);

        $reportData = $query->select(
            'expense_name',
            DB::raw('COUNT(*) as total_count'),
            DB::raw('SUM(amount) as total_amount'),
            DB::raw('AVG(amount) as avg_amount')
        )
        ->groupBy('expense_name')
        ->orderBy('total_amount', 'desc')
        ->get();

        $filename = 'expense-item-report-' . now()->format('Y-m-d') . '.csv';
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"$filename\""];

        $callback = function () use ($reportData) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['#', 'Expense Item', 'Avg. Unit Price', 'Quantity', 'Total Amount']);

            foreach ($reportData as $i => $row) {
                fputcsv($handle, [
                    $i + 1,
                    $row->expense_name,
                    number_format($row->avg_amount, 2),
                    $row->total_count,
                    number_format($row->total_amount, 2)
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}

