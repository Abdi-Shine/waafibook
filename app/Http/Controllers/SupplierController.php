<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Supplier;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::query()->latest();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('supplier_code', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('supplier_type', $request->type);
        }

        $isMobile = (bool) preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|Windows Phone/i', $request->userAgent() ?? '')
            || $request->header('Sec-CH-UA-Mobile') === '?1'
            || $request->boolean('mobile');

        if ($isMobile) {
            $suppliers = $query->addSelect([
                'latest_txn_date' => \App\Models\PurchaseBill::select('bill_date')
                    ->whereColumn('supplier_id', 'suppliers.id')
                    ->latest('bill_date')
                    ->limit(1),
            ])->get()->map(fn ($s) => (object) [
                'id'             => $s->id,
                'name'           => $s->name,
                'amount_balance' => (float) $s->amount_balance,
                'latest_date'    => $s->latest_txn_date ?? optional($s->created_at)->toDateString(),
            ]);

            return view('frontend.parties.supplier_details_pwa', compact('suppliers'));
        }

        $suppliers = $query->get();

        $stats = [
            'total'              => Supplier::query()->count(),
            'active'             => Supplier::query()->where('status', 'active')->count(),
            'payables'           => Supplier::query()->sum('amount_balance'),
            'company_suppliers'  => Supplier::query()->where('supplier_type', 'company')->count(),
        ];

        return view('frontend.parties.supplier', compact('suppliers', 'stats'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt|max:2048',
        ]);

        if ($request->hasFile('csv_file')) {
            $file = $request->file('csv_file');
            $handle = fopen($file->getPathname(), "r");
            
            // Read header row
            $header = fgetcsv($handle, 1000, ",");

            $account = Account::query()->where('name', 'like', '%Payable%')->first();
            
            $totalBalance = 0;
            $nextId = (Supplier::query()->max('id') ?? 0) + 1;

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $name = trim($data[0] ?? '');
                if (empty($name)) continue;

                $balance = isset($data[5]) ? (float) $data[5] : 0;

                $supplier = Supplier::query()->create([
                    'name'           => $name,
                    'email'          => $data[1] ?? null,
                    'phone'          => $data[2] ?? null,
                    'supplier_type'  => strtolower($data[3] ?? 'individual') === 'business' ? 'company' : strtolower($data[3] ?? 'individual'),
                    'address'        => $data[4] ?? null,
                    'amount_balance' => $balance,
                    'account_id'     => $account?->id,
                    'account_type'   => $account?->type,
                    'account_code'   => $account?->code,
                    'supplier_code'  => 'SUP-' . date('Y') . '-' . str_pad($nextId++, 3, '0', STR_PAD_LEFT),
                ]);

                if ($balance != 0) {
                    $this->postOpeningBalanceEntry($supplier, $balance);
                }

                $totalBalance += $balance;
            }
            fclose($handle);

            if ($account && $totalBalance > 0) {
                $account->increment('balance', $totalBalance);
            }

            return redirect()->back()->with('success', 'Suppliers imported successfully!');
        }

        return redirect()->back()->with('error', 'Please upload a valid CSV file.');
    }

    public function export()
    {
        $fileName = 'suppliers_export_' . date('Y-m-d_H:i:s') . '.csv';
        $suppliers = Supplier::query()->get();

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('Full Name', 'Email', 'Phone', 'Type', 'Address', 'Balance');

        $callback = function() use($suppliers, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($suppliers as $supplier) {
                $row['Full Name']  = $supplier->name;
                $row['Email']      = $supplier->email ?? '';
                $row['Phone']      = $supplier->phone ?? '';
                $row['Type']       = $supplier->supplier_type === 'company' ? 'business' : 'individual';
                $row['Address']    = $supplier->address ?? '';
                $row['Balance']    = $supplier->amount_balance ?? 0;

                fputcsv($file, array($row['Full Name'], $row['Email'], $row['Phone'], $row['Type'], $row['Address'], $row['Balance']));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'phone'          => 'required|string|max:20',
            'email'          => 'nullable|email|max:255|unique:suppliers,email',
            'supplier_type'  => 'required|in:individual,company',
            'address'        => 'nullable|string',
            'amount_balance' => 'nullable|numeric',
        ]);

        $supplier = DB::transaction(function () use ($validated) {
            $account = Account::query()->where('name', 'like', '%Payable%')->first();
            if ($account) {
                $validated['account_id']   = $account->id;
                $validated['account_type'] = $account->type;
                $validated['account_code'] = $account->code;

                if (!empty($validated['amount_balance'])) {
                    $account->increment('balance', $validated['amount_balance']);
                }
            }

            $lastId = Supplier::query()->max('id') ?? 0;
            $validated['supplier_code'] = 'SUP-' . date('Y') . '-' . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);

            $supplier = Supplier::query()->create($validated);

            if (!empty($validated['amount_balance'])) {
                $this->postOpeningBalanceEntry($supplier, (float) $validated['amount_balance']);
            }

            return $supplier;
        });

        if ($request->expectsJson()) {
            return response()->json($supplier);
        }

        return redirect()->back()->with('success', 'Supplier created successfully');
    }

    public function update(Request $request, $id)
    {
        $supplier = Supplier::query()->findOrFail($id);

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'phone'          => 'required|string|max:20',
            'email'          => 'nullable|email|max:255|unique:suppliers,email,' . $id,
            'supplier_type'  => 'required|in:individual,company',
            'address'        => 'nullable|string',
            'amount_balance' => 'nullable|numeric',
        ]);

        DB::transaction(function () use ($supplier, $validated) {
            $old_balance = (float) ($supplier->amount_balance ?? 0);
            $new_balance = (float) ($validated['amount_balance'] ?? 0);

            $account = Account::query()->where('name', 'like', '%Payable%')->first();
            if ($account) {
                $validated['account_id']   = $account->id;
                $validated['account_type'] = $account->type;
                $validated['account_code'] = $account->code;

                $diff = $new_balance - $old_balance;
                if ($diff != 0) {
                    $account->increment('balance', $diff);
                    $this->postOpeningBalanceEntry($supplier, $diff);
                }
            }

            $supplier->update($validated);
        });

        return redirect()->back()->with('success', 'Supplier updated successfully');
    }

    public function statement($id)
    {
        $supplier = Supplier::query()->findOrFail($id);

        if (method_exists($supplier, 'purchases')) {
            $supplier->load('purchases');
        } else {
            $supplier->setRelation('purchases', collect());
        }
        if (method_exists($supplier, 'payments')) {
            $supplier->load('payments');
        } else {
            $supplier->setRelation('payments', collect());
        }

        $company_profile = Company::find(auth()->user()->company_id);

        return view('frontend.parties.supplier_statement', compact('supplier', 'company_profile'));
    }

    public function downloadStatement($id)
    {
        $supplier = Supplier::query()->findOrFail($id);

        if (method_exists($supplier, 'purchases')) {
            $supplier->load('purchases');
        } else {
            $supplier->setRelation('purchases', collect());
        }
        if (method_exists($supplier, 'payments')) {
            $supplier->load('payments');
        } else {
            $supplier->setRelation('payments', collect());
        }

        $company_profile = Company::find(auth()->user()->company_id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('frontend.parties.pdf_supplier_statement', [
            'supplier' => $supplier,
            'company_profile' => $company_profile
        ]);

        return $pdf->download('Statement_'.$supplier->name.'_'.date('Y-m-d').'.pdf');
    }

    // Public, signed-URL version so the statement can be opened by a supplier (e.g. via WhatsApp)
    // without needing to log in. The signature prevents guessing/enumerating other suppliers' statements.
    public function publicStatement($id)
    {
        $supplier = Supplier::withoutGlobalScopes()->findOrFail($id);

        if (method_exists($supplier, 'purchases')) {
            $supplier->load('purchases');
        } else {
            $supplier->setRelation('purchases', collect());
        }
        if (method_exists($supplier, 'payments')) {
            $supplier->load('payments');
        } else {
            $supplier->setRelation('payments', collect());
        }

        $company_profile = Company::find($supplier->company_id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('frontend.parties.pdf_supplier_statement', [
            'supplier' => $supplier,
            'company_profile' => $company_profile
        ]);

        return $pdf->stream('Statement_'.$supplier->name.'_'.date('Y-m-d').'.pdf');
    }

    public function emailStatement($id)
    {
        return response()->json([
            'success' => false,
            'message' => 'Emailing supplier statements is not configured yet.'
        ]);
    }

    // A supplier is considered "in use" once it has any real transaction
    // against it — deleting it at that point would cascade-delete those
    // records (see the FKs on purchase_orders/purchase_bills/supplier_payments/
    // purchase_returns), silently wiping out financial history. Deactivating
    // instead keeps the records intact.
    private function hasTransactions($supplierId): bool
    {
        return DB::table('purchase_orders')->where('supplier_id', $supplierId)->exists()
            || DB::table('purchase_bills')->where('supplier_id', $supplierId)->exists()
            || DB::table('supplier_payments')->where('supplier_id', $supplierId)->exists()
            || DB::table('purchase_returns')->where('supplier_id', $supplierId)->exists();
    }

    public function deactivate($id)
    {
        $supplier = Supplier::query()->findOrFail($id);
        $supplier->update(['status' => 'inactive']);
        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $supplier = Supplier::query()->findOrFail($id);

        if ($this->hasTransactions($id)) {
            return response()->json([
                'message' => 'This party cannot be deleted as it is already used in transactions. Please delete all transactions before deleting the party.',
                'has_transactions' => true,
            ], 409);
        }

        $balance = (float) ($supplier->amount_balance ?? 0);

        if ($balance != 0) {
            $account = Account::query()->find($supplier->account_id);
            if (!$account) {
                $account = Account::query()->where('name', 'like', '%Payable%')->first();
            }
            if ($account) {
                $account->balance -= $balance;
                $account->save();
            }

            // Reverses whatever net opening-balance entries this supplier
            // accumulated (initial + any later adjustments), rather than
            // hunting down every prior entry individually.
            $this->postOpeningBalanceEntry($supplier, -$balance);
        }

        $supplier->delete();

        return redirect()->back()->with('success', 'Supplier deleted successfully');
    }

    // A supplier's opening balance previously only ever bumped
    // chart_of_accounts.balance directly — a cached column the Balance
    // Sheet / Trial Balance reports deliberately don't read (they recompute
    // live from journal_items instead). With no real journal entry ever
    // posted, that balance was invisible to those reports. This posts the
    // real entry: Cr Accounts Payable (positive amount = we owe them more)
    // against Opening Balance Equity — the liability-side mirror of
    // CustomerController::postOpeningBalanceEntry. $amount may be negative
    // to record/reverse an overpayment (we owe them less / they owe us).
    private function postOpeningBalanceEntry(Supplier $supplier, float $amount, ?string $date = null): void
    {
        if ($amount == 0) {
            return;
        }

        $companyId = $supplier->company_id ?? Auth::user()->company_id;

        $payableAccount = Account::query()->find($supplier->account_id)
            ?: Account::query()->where('company_id', $companyId)->where('name', 'like', '%Payable%')->first();
        $equityAccount = Account::query()->where('company_id', $companyId)->where('code', '3300')->first()
            ?: Account::query()->where('company_id', $companyId)->where('name', 'like', '%Opening Balance%')->first();

        if (!$payableAccount || !$equityAccount) {
            return;
        }

        $entry = JournalEntry::query()->create([
            'company_id'   => $companyId,
            // Placeholder — overwritten right after using the entry's own
            // (now-known) id, which is guaranteed unique even if this runs
            // for the same supplier more than once on the same day.
            'entry_number' => 'JE-SUPP-PENDING-' . $supplier->id . '-' . microtime(true),
            'date'         => $date ?: now()->toDateString(),
            'reference'    => $supplier->supplier_code,
            'description'  => 'Opening balance for ' . $supplier->name,
            'status'       => 'posted',
            'total_amount' => abs($amount),
            'created_by'   => Auth::id(),
        ]);
        $entry->update(['entry_number' => 'JE-SUPP-' . date('Ymd') . '-' . str_pad($entry->id, 6, '0', STR_PAD_LEFT)]);

        JournalItem::query()->create([
            'company_id'       => $companyId,
            'journal_entry_id' => $entry->id,
            'account_id'       => $equityAccount->id,
            'debit'            => $amount > 0 ? $amount : 0,
            'credit'           => $amount < 0 ? abs($amount) : 0,
            'description'      => 'Opening balance for ' . $supplier->name,
        ]);

        JournalItem::query()->create([
            'company_id'       => $companyId,
            'journal_entry_id' => $entry->id,
            'account_id'       => $payableAccount->id,
            'debit'            => $amount < 0 ? abs($amount) : 0,
            'credit'           => $amount > 0 ? $amount : 0,
            'description'      => 'Opening balance for ' . $supplier->name,
        ]);
    }
}
