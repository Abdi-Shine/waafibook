<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Product;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\ServiceQuotation;
use App\Models\ServiceQuotationItem;
use App\Models\ServiceSchedule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceQuotationController extends Controller
{
    public function index(Request $request)
    {
        $query = ServiceQuotation::query()->with('customer');

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('quote_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($s) => $s->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $quotations  = $query->latest()->paginate(20);
        $customers   = Customer::orderBy('name')->get();
        $totalDraft  = ServiceQuotation::where('status', 'draft')->count();
        $totalSent   = ServiceQuotation::where('status', 'sent')->count();
        $totalAccepted  = ServiceQuotation::where('status', 'accepted')->count();
        $totalConverted = ServiceQuotation::where('status', 'converted')->count();

        return view('frontend.services.service_quotations', compact(
            'quotations', 'customers', 'totalDraft', 'totalSent', 'totalAccepted', 'totalConverted'
        ));
    }

    public function create()
    {
        $customers   = Customer::orderBy('name')->get();
        $services    = Product::where('product_type', 'service')->orderBy('product_name')->get();
        $quoteNumber = ServiceQuotation::nextQuoteNumber(Auth::user()->company_id);

        return view('frontend.services.add_service_quotation', compact('customers', 'services', 'quoteNumber'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id'         => 'required|exists:customers,id',
            'items'               => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity'    => 'required|numeric|min:0.01',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            [$subtotal, $discount, $tax, $total] = $this->calcTotals($request->items, $request);

            $quotation = ServiceQuotation::create([
                'quote_number'    => ServiceQuotation::nextQuoteNumber(Auth::user()->company_id),
                'customer_id'     => $request->customer_id,
                'status'          => $request->status ?? 'draft',
                'title'           => $request->title,
                'valid_until'     => $request->valid_until,
                'subtotal'        => $subtotal,
                'discount_amount' => $discount,
                'tax_amount'      => $tax,
                'total_amount'    => $total,
                'notes'           => $request->notes,
                'created_by'      => Auth::id(),
            ]);

            $this->saveItems($quotation->id, $request->items);
            AuditLog::log('Services', "Created quotation #{$quotation->quote_number}", 'CREATE');
        });

        return redirect()->route('service-quotations.index')->with('success', 'Quotation created.');
    }

    public function show($id)
    {
        $quotation = ServiceQuotation::with('customer', 'items.product', 'creator', 'convertedOrder')->findOrFail($id);
        return view('frontend.services.service_quotation_detail', compact('quotation'));
    }

    public function edit($id)
    {
        $quotation = ServiceQuotation::with('items')->findOrFail($id);
        if (in_array($quotation->status, ['converted'])) {
            return back()->with('error', 'Converted quotations cannot be edited.');
        }
        $customers   = Customer::orderBy('name')->get();
        $services    = Product::where('product_type', 'service')->orderBy('product_name')->get();

        return view('frontend.services.add_service_quotation', compact('quotation', 'customers', 'services'));
    }

    public function update(Request $request, $id)
    {
        $quotation = ServiceQuotation::findOrFail($id);
        $request->validate([
            'customer_id'         => 'required|exists:customers,id',
            'items'               => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity'    => 'required|numeric|min:0.01',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $quotation) {
            [$subtotal, $discount, $tax, $total] = $this->calcTotals($request->items, $request);

            $quotation->update([
                'customer_id'     => $request->customer_id,
                'status'          => $request->status ?? $quotation->status,
                'title'           => $request->title,
                'valid_until'     => $request->valid_until,
                'subtotal'        => $subtotal,
                'discount_amount' => $discount,
                'tax_amount'      => $tax,
                'total_amount'    => $total,
                'notes'           => $request->notes,
            ]);

            $quotation->items()->delete();
            $this->saveItems($quotation->id, $request->items);
        });

        return redirect()->route('service-quotations.show', $id)->with('success', 'Quotation updated.');
    }

    public function destroy($id)
    {
        $quotation = ServiceQuotation::findOrFail($id);

        if ($quotation->status === 'converted') {
            return back()->with('error', 'Cannot delete a converted quotation.');
        }

        DB::transaction(function () use ($quotation) {
            AuditLog::log('Services', "Deleted quotation #{$quotation->quote_number}", 'DELETE');
            $quotation->items()->delete();
            $quotation->delete();
        });

        return redirect()->route('service-quotations.index')->with('success', 'Quotation deleted.');
    }

    public function convertToOrder($id)
    {
        $quotation = ServiceQuotation::with('items')->findOrFail($id);

        if ($quotation->status === 'converted') {
            return back()->with('error', 'Already converted.');
        }

        DB::transaction(function () use ($quotation) {
            $order = ServiceOrder::create([
                'order_number'    => ServiceOrder::nextOrderNumber(Auth::user()->company_id),
                'customer_id'     => $quotation->customer_id,
                'quotation_id'    => $quotation->id,
                'status'          => 'confirmed',
                'priority'        => 'normal',
                'title'           => $quotation->title ?? "From {$quotation->quote_number}",
                'subtotal'        => $quotation->subtotal,
                'discount_amount' => $quotation->discount_amount,
                'tax_amount'      => $quotation->tax_amount,
                'total_amount'    => $quotation->total_amount,
                'notes'           => $quotation->notes,
                'created_by'      => Auth::id(),
            ]);

            foreach ($quotation->items as $item) {
                ServiceOrderItem::create([
                    'service_order_id' => $order->id,
                    'product_id'       => $item->product_id,
                    'description'      => $item->description,
                    'quantity'         => $item->quantity,
                    'unit_price'       => $item->unit_price,
                    'discount_pct'     => $item->discount_pct,
                    'total'            => $item->total,
                ]);
            }

            $quotation->update(['status' => 'converted', 'converted_order_id' => $order->id]);
            AuditLog::log('Services', "Quotation #{$quotation->quote_number} converted to order #{$order->order_number}", 'CREATE');

            session()->flash('redirect_order_id', $order->id);
        });

        $orderId = session('redirect_order_id');
        return redirect()->route('service-orders.show', $orderId)->with('success', 'Quotation converted to Service Order.');
    }

    public function pdf($id)
    {
        $quotation = ServiceQuotation::with('customer', 'items.product', 'creator')->findOrFail($id);
        $company   = Auth::user()->company;
        $symbol    = '$';

        $pdf = Pdf::loadView('frontend.services.service_quotation_pdf', compact('quotation', 'company', 'symbol'))
                  ->setPaper('a4', 'portrait');

        return $pdf->download("quotation-{$quotation->quote_number}.pdf");
    }

    public function publicPdf($id)
    {
        $quotation = ServiceQuotation::withoutGlobalScopes()->with('customer', 'items.product')->findOrFail($id);
        $company   = \App\Models\Company::find($quotation->company_id);
        $symbol    = '$';

        $pdf = Pdf::loadView('frontend.services.service_quotation_pdf', compact('quotation', 'company', 'symbol'))
                  ->setPaper('a4', 'portrait');

        return $pdf->stream("quotation-{$quotation->quote_number}.pdf");
    }

    // ─── Schedule CRUD ────────────────────────────────────────────────────────

    public function schedules(Request $request)
    {
        $query = ServiceSchedule::with(['customer', 'serviceOrders']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhereHas('customer', fn($q2) => $q2->where('name', 'like', "%{$request->search}%"));
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->frequency) {
            $query->where('frequency', $request->frequency);
        }

        $schedules    = $query->latest()->paginate(20);
        $totalActive  = ServiceSchedule::where('status', 'active')->count();
        $totalPaused  = ServiceSchedule::where('status', 'paused')->count();
        $totalEnded   = ServiceSchedule::where('status', 'ended')->count();
        $dueToday     = ServiceSchedule::where('status', 'active')->whereDate('next_due_date', today())->count();

        return view('frontend.services.service_schedules', compact(
            'schedules', 'totalActive', 'totalPaused', 'totalEnded', 'dueToday'
        ));
    }

    public function createSchedule()
    {
        $customers = Customer::orderBy('name')->get();
        $services  = Product::where('product_type', 'service')->orderBy('product_name')->get();
        return view('frontend.services.add_service_schedule', compact('customers', 'services'));
    }

    public function storeSchedule(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'title'       => 'required|string|max:255',
            'frequency'   => 'required|in:daily,weekly,monthly,quarterly,yearly',
            'start_date'  => 'required|date',
        ]);

        $items = [];
        if ($request->has('template_items')) {
            foreach ($request->template_items as $item) {
                if (!empty($item['description'])) {
                    $items[] = [
                        'description' => $item['description'],
                        'quantity'    => floatval($item['quantity'] ?? 1),
                        'unit_price'  => floatval($item['unit_price'] ?? 0),
                    ];
                }
            }
        }

        ServiceSchedule::create([
            'customer_id'    => $request->customer_id,
            'title'          => $request->title,
            'frequency'      => $request->frequency,
            'start_date'     => $request->start_date,
            'end_date'       => $request->end_date ?: null,
            'next_due_date'  => $request->next_due_date ?: $request->start_date,
            'status'         => $request->status ?? 'active',
            'template_items' => $items,
            'auto_invoice'   => $request->has('auto_invoice'),
            'notes'          => $request->notes,
            'created_by'     => Auth::id(),
        ]);

        return redirect()->route('service-schedules.index')->with('success', 'Recurring schedule created.');
    }

    public function editSchedule($id)
    {
        $schedule  = ServiceSchedule::findOrFail($id);
        $customers = Customer::orderBy('name')->get();
        return view('frontend.services.add_service_schedule', compact('schedule', 'customers'));
    }

    public function updateSchedule(Request $request, $id)
    {
        $schedule = ServiceSchedule::findOrFail($id);

        $items = [];
        if ($request->has('template_items')) {
            foreach ($request->template_items as $item) {
                if (!empty($item['description'])) {
                    $items[] = [
                        'description' => $item['description'],
                        'quantity'    => floatval($item['quantity'] ?? 1),
                        'unit_price'  => floatval($item['unit_price'] ?? 0),
                    ];
                }
            }
        }

        $schedule->update([
            'customer_id'    => $request->customer_id ?? $schedule->customer_id,
            'title'          => $request->title ?? $schedule->title,
            'frequency'      => $request->frequency ?? $schedule->frequency,
            'start_date'     => $request->start_date ?? $schedule->start_date,
            'end_date'       => $request->end_date ?: null,
            'next_due_date'  => $request->next_due_date ?: $schedule->next_due_date,
            'status'         => $request->status ?? $schedule->status,
            'template_items' => $items,
            'auto_invoice'   => $request->has('auto_invoice'),
            'notes'          => $request->notes,
        ]);

        return redirect()->route('service-schedules.index')->with('success', 'Schedule updated.');
    }

    public function destroySchedule($id)
    {
        ServiceSchedule::findOrFail($id)->delete();
        return redirect()->route('service-schedules.index')->with('success', 'Schedule deleted.');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function calcTotals(array $items, Request $request): array
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $disc     = (float)($item['discount_pct'] ?? 0);
            $subtotal += (float)$item['quantity'] * (float)$item['unit_price'] * (1 - $disc / 100);
        }
        $discount = (float)($request->discount_amount ?? 0);
        $tax      = (float)($request->tax_amount ?? 0);
        $total    = $subtotal - $discount + $tax;
        return [$subtotal, $discount, $tax, $total];
    }

    private function saveItems(int $quotationId, array $items): void
    {
        foreach ($items as $item) {
            $disc      = (float)($item['discount_pct'] ?? 0);
            $itemTotal = (float)$item['quantity'] * (float)$item['unit_price'] * (1 - $disc / 100);
            ServiceQuotationItem::create([
                'service_quotation_id' => $quotationId,
                'product_id'           => $item['product_id'] ?? null,
                'description'          => $item['description'],
                'quantity'             => $item['quantity'],
                'unit_price'           => $item['unit_price'],
                'discount_pct'         => $disc,
                'total'                => $itemTotal,
            ]);
        }
    }
}
