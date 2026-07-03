<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PayrollController extends Controller
{
    private function currencySymbol(): string
    {
        $company = Company::find(Auth::user()->company_id);
        $map = ['SAR' => '﷼', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AED' => 'د.إ', 'KWD' => 'د.ك'];
        return $map[$company->currency ?? ''] ?? ($company->currency ?? '$');
    }

    // Single payroll page — shows all salary records + form to add new ones
    public function index()
    {
        $items     = PayrollItem::with(['employee', 'payroll'])
                        ->orderBy('created_at', 'desc')
                        ->get();
        $employees = Employee::where('status', 'active')->get();
        $currency  = $this->currencySymbol();

        $currentMonth      = now()->format('Y-m');
        $paidThisMonth     = $items->filter(fn($i) => optional($i->payroll)->month_year === $currentMonth)->sum('net_salary');
        $countThisMonth    = $items->filter(fn($i) => optional($i->payroll)->month_year === $currentMonth)->pluck('employee_id')->unique()->count();
        $ytdTotal          = $items->sum('net_salary');

        return view('frontend.expense.payroll_list', compact(
            'items', 'employees', 'currency',
            'paidThisMonth', 'countThisMonth', 'ytdTotal'
        ));
    }

    // Record a single employee's monthly salary
    public function store(Request $request)
    {
        $request->validate([
            'month_year'   => 'required|string|regex:/^\d{4}-\d{2}$/',
            'employee_id'  => 'required|exists:employees,id',
            'basic_salary' => 'required|numeric|min:0',
            'bonus'        => 'nullable|numeric|min:0',
            'overtime'     => 'nullable|numeric|min:0',
            'deductions'   => 'nullable|numeric|min:0',
        ]);

        $cid        = Auth::user()->company_id;
        $basic      = (float) $request->basic_salary;
        $bonus      = (float) ($request->bonus    ?? 0);
        $ot         = (float) ($request->overtime ?? 0);
        $deductions = (float) ($request->deductions ?? 0);
        $gross      = $basic + $bonus + $ot;
        $net        = $gross - $deductions;

        // Prevent duplicate: same employee + same month
        $duplicate = PayrollItem::whereHas('payroll', fn($q) =>
                $q->where('month_year', $request->month_year)->where('company_id', $cid)
            )->where('employee_id', $request->employee_id)->exists();

        if ($duplicate) {
            return back()->withInput()
                ->with('error', 'A salary record for this employee in ' . $request->month_year . ' already exists.');
        }

        try {
            DB::beginTransaction();

            // Find or create the monthly Payroll batch header
            $payroll = Payroll::firstOrCreate(
                ['month_year' => $request->month_year, 'company_id' => $cid, 'branch_id' => null],
                ['total_employees' => 0, 'total_gross' => 0, 'total_deductions' => 0, 'total_net' => 0, 'status' => 'Paid', 'approved_by' => Auth::id(), 'paid_date' => now()]
            );

            // Create the individual item
            PayrollItem::create([
                'payroll_id'   => $payroll->id,
                'employee_id'  => $request->employee_id,
                'basic_salary' => $basic,
                'bonus'        => $bonus,
                'overtime'     => $ot,
                'deductions'   => $deductions,
                'gross_salary' => $gross,
                'net_salary'   => $net,
                'status'       => 'Paid',
                'payment_date' => now(),
                'company_id'   => $cid,
            ]);

            // Update batch totals
            $payroll->increment('total_employees');
            $payroll->increment('total_gross',      $gross);
            $payroll->increment('total_deductions', $deductions);
            $payroll->increment('total_net',        $net);

            // GL: Dr Salary Expense / Cr Cash on Hand
            $salaryAccount = Account::where('company_id', $cid)
                ->where('category', 'expenses')
                ->where(function ($q) {
                    $q->where('code', '5210')->orWhere('code', '5100')
                      ->orWhere('name', 'like', '%Salaries%')->orWhere('name', 'like', '%Wages%');
                })->first();

            $cashAccount = Account::where('company_id', $cid)
                ->where(function ($q) {
                    $q->where('code', '1110')
                      ->orWhere('type', 'cash')
                      ->orWhere('name', 'like', '%Cash on Hand%');
                })->orderByRaw("CASE WHEN code='1110' THEN 0 ELSE 1 END")
                ->first();

            if ($salaryAccount && $cashAccount) {
                $employee = Employee::find($request->employee_id);
                $entry = JournalEntry::create([
                    'entry_number' => 'JE-SAL-' . date('Ymd') . '-' . str_pad($payroll->id, 4, '0', STR_PAD_LEFT),
                    'date'         => now()->toDateString(),
                    'reference'    => 'SAL-' . $request->month_year . '-EMP' . $request->employee_id,
                    'description'  => 'Salary: ' . ($employee->full_name ?? '') . ' — ' . $request->month_year,
                    'status'       => 'posted',
                    'total_amount' => $net,
                    'company_id'   => $cid,
                    'created_by'   => Auth::id(),
                ]);
                JournalItem::create(['journal_entry_id' => $entry->id, 'account_id' => $salaryAccount->id, 'company_id' => $cid, 'description' => 'Salary expense', 'debit' => $net, 'credit' => 0]);
                JournalItem::create(['journal_entry_id' => $entry->id, 'account_id' => $cashAccount->id,   'company_id' => $cid, 'description' => 'Salary paid',    'debit' => 0, 'credit' => $net]);
            }

            DB::commit();
            return back()->with('success', 'Salary payment recorded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    // Delete a single PayrollItem (individual salary record)
    public function destroyItem($id)
    {
        try {
            DB::beginTransaction();

            $item = PayrollItem::where('company_id', Auth::user()->company_id)->findOrFail($id);
            $payroll = $item->payroll;

            $item->delete();

            // Adjust batch totals
            if ($payroll) {
                $payroll->decrement('total_employees');
                $payroll->decrement('total_gross',      $item->gross_salary);
                $payroll->decrement('total_deductions', $item->deductions);
                $payroll->decrement('total_net',        $item->net_salary);

                // Remove empty batch
                if ($payroll->items()->count() === 0) {
                    $payroll->delete();
                }
            }

            DB::commit();
            return back()->with('success', 'Salary record deleted.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    // Print receipt / payslip for a single PayrollItem
    public function receipt($id)
    {
        $item = PayrollItem::with(['employee', 'payroll'])
                    ->where('company_id', Auth::user()->company_id)
                    ->findOrFail($id);
        $currency = $this->currencySymbol();
        $company  = Company::find(Auth::user()->company_id);
        return view('frontend.expense.payroll_receipt', compact('item', 'currency', 'company'));
    }

    // Update a single PayrollItem's salary amount
    public function updateItem(Request $request, $id)
    {
        $request->validate([
            'basic_salary' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $item    = PayrollItem::where('company_id', Auth::user()->company_id)->findOrFail($id);
            $payroll = $item->payroll;
            $oldNet  = $item->net_salary;
            $newNet  = (float) $request->basic_salary;

            // Adjust batch totals
            if ($payroll) {
                $diff = $newNet - $oldNet;
                $payroll->increment('total_gross', $diff);
                $payroll->increment('total_net',   $diff);
            }

            $item->update([
                'basic_salary' => $newNet,
                'gross_salary' => $newNet,
                'net_salary'   => $newNet,
            ]);

            DB::commit();
            return back()->with('success', 'Salary record updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    // ── Legacy methods kept for any existing links ──────────────────────────

    public function create()   { return redirect()->route('payroll.index'); }
    public function show($id)  { return redirect()->route('payroll.index'); }
    public function approve($id) { return redirect()->route('payroll.index'); }
    public function markAsPaid($id) { return redirect()->route('payroll.index'); }
    public function repostJournal($id) { return redirect()->route('payroll.index'); }
    public function destroy($id) { return redirect()->route('payroll.index'); }
}
