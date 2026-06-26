<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\Account;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\AuditLog;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query()->latest();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('customer_code', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('customer_type', $request->type);
        }

        $customers = $query->paginate(10)->withQueryString();

        $stats = [
            'total'          => Customer::query()->count(),
            'active'         => Customer::query()->where('status', 'active')->count(),
            'receivables'    => Customer::query()->sum('amount_balance'),
            'new_this_month' => Customer::query()->where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        $accounts = Account::query()->where('is_active', 1)->get();
        return view('frontend.parties.customer', compact('customers', 'accounts', 'stats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'nullable|email|max:255|unique:customers,email',
            'phone'          => 'required|string|max:20',
            'customer_type'  => 'required|in:individual,company',
            'address'        => 'nullable|string',
            'account_id'     => 'nullable|integer',
            'amount_balance' => 'nullable|numeric',
        ]);

        $customer = DB::transaction(function () use ($validated) {
            $account = Account::query()->where('name', 'like', '%Receivable%')->first();
            if ($account) {
                $validated['account_id']   = $account->id;
                $validated['account_type'] = $account->type;
                $validated['account_code'] = $account->code;

                if (!empty($validated['amount_balance'])) {
                    $account->increment('balance', $validated['amount_balance']);
                }
            }

            $lastCode = Customer::query()->max('id') ?? 0;
            $validated['customer_code'] = 'CUS-' . date('Y') . '-' . str_pad($lastCode + 1, 3, '0', STR_PAD_LEFT);

            $customer = Customer::query()->create($validated);

            if (!empty($validated['amount_balance'])) {
                $this->postOpeningBalanceEntry($customer, (float) $validated['amount_balance']);
            }

            return $customer;
        });

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'customer' => $customer]);
        }

        return redirect()->back()->with('success', 'Customer created successfully');
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

            $account = Account::query()->where('name', 'like', '%Receivable%')->first();
            
            $totalBalance = 0;
            $nextId = (Customer::query()->max('id') ?? 0) + 1;

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $name = trim($data[0] ?? '');
                if (empty($name)) continue;

                $balance = isset($data[5]) ? (float) $data[5] : 0;

                $customer = Customer::query()->create([
                    'name'          => $name,
                    'email'         => $data[1] ?? null,
                    'phone'         => $data[2] ?? null,
                    'customer_type' => strtolower($data[3] ?? 'individual') === 'business' ? 'company' : strtolower($data[3] ?? 'individual'),
                    'address'       => $data[4] ?? null,
                    'amount_balance' => $balance,
                    'account_id'    => $account?->id,
                    'account_type'  => $account?->type,
                    'account_code'  => $account?->code,
                    'customer_code' => 'CUS-' . date('Y') . '-' . str_pad($nextId++, 3, '0', STR_PAD_LEFT),
                ]);

                if ($balance != 0) {
                    $this->postOpeningBalanceEntry($customer, $balance);
                }

                $totalBalance += $balance;
            }
            fclose($handle);

            if ($account && $totalBalance > 0) {
                $account->increment('balance', $totalBalance);
            }

            return redirect()->back()->with('success', 'Customers imported successfully!');
        }

        return redirect()->back()->with('error', 'Please upload a valid CSV file.');
    }

    public function export()
    {
        $fileName = 'customers_export_' . date('Y-m-d_H:i:s') . '.csv';
        $customers = Customer::query()->get();

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('Full Name', 'Email', 'Phone', 'Type', 'Address', 'Balance');

        $callback = function() use($customers, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($customers as $customer) {
                $row['Full Name']  = $customer->name;
                $row['Email']      = $customer->email ?? '';
                $row['Phone']      = $customer->phone ?? '';
                $row['Type']       = $customer->customer_type === 'company' ? 'business' : 'individual';
                $row['Address']    = $customer->address ?? '';
                $row['Balance']    = $customer->amount_balance ?? 0;

                fputcsv($file, array($row['Full Name'], $row['Email'], $row['Phone'], $row['Type'], $row['Address'], $row['Balance']));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function statement($id)
    {
        $customer = Customer::query()->findOrFail($id);
        
        // Assuming Order and Payment models are connected to the customer
        // We'll load them if the relations exist, otherwise fallback to empty collections
        if(method_exists($customer, 'orders')) {
            $customer->load('orders');
        } else {
            $customer->setRelation('orders', collect());
        }

        if(method_exists($customer, 'payments')) {
            $customer->load('payments');
        } else {
            $customer->setRelation('payments', collect());
        }

        $company_profile = Company::find(auth()->user()->company_id);

        return view('frontend.parties.customer_statement', compact('customer', 'company_profile'));
    }

    public function downloadStatement($id)
    {
        $customer = Customer::query()->findOrFail($id);
        
        if(method_exists($customer, 'orders')) {
            $customer->load('orders');
        } else {
            $customer->setRelation('orders', collect());
        }

        if(method_exists($customer, 'payments')) {
            $customer->load('payments');
        } else {
            $customer->setRelation('payments', collect());
        }

        $company_profile = Company::find(auth()->user()->company_id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('frontend.parties.pdf_customer_statement', [
            'customer' => $customer,
            'company_profile' => $company_profile
        ]);

        $fileName = 'Statement_' . $customer->name . '_' . date('Y-m-d') . '.pdf';

        return $pdf->download($fileName);
    }

    // Public, signed-URL version so the statement can be opened by a customer (e.g. via WhatsApp)
    // without needing to log in. The signature prevents guessing/enumerating other customers' statements.
    public function publicStatement($id)
    {
        $customer = Customer::withoutGlobalScopes()->findOrFail($id);

        if (method_exists($customer, 'orders')) {
            $customer->load('orders');
        } else {
            $customer->setRelation('orders', collect());
        }

        if (method_exists($customer, 'payments')) {
            $customer->load('payments');
        } else {
            $customer->setRelation('payments', collect());
        }

        $company_profile = Company::find($customer->company_id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('frontend.parties.pdf_customer_statement', [
            'customer' => $customer,
            'company_profile' => $company_profile
        ]);

        return $pdf->stream('Statement_' . $customer->name . '_' . date('Y-m-d') . '.pdf');
    }

    public function emailStatement($id)
    {
        return response()->json([
            'success' => false,
            'message' => 'Emailing statements is not configured yet.'
        ]);
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::query()->findOrFail($id);

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'nullable|email|max:255|unique:customers,email,' . $id,
            'phone'          => 'required|string|max:20',
            'customer_type'  => 'required|in:individual,company',
            'address'        => 'nullable|string',
            'account_id'     => 'nullable|integer',
            'amount_balance' => 'nullable|numeric',
        ]);

        DB::transaction(function () use ($customer, $validated) {
            $old_balance = (float) ($customer->amount_balance ?? 0);
            $new_balance = (float) ($validated['amount_balance'] ?? 0);

            $account = Account::query()->where('name', 'like', '%Receivable%')->first();
            if ($account) {
                $validated['account_id']   = $account->id;
                $validated['account_type'] = $account->type;
                $validated['account_code'] = $account->code;

                $diff = $new_balance - $old_balance;
                if ($diff != 0) {
                    $account->increment('balance', $diff);
                    $this->postOpeningBalanceEntry($customer, $diff);
                }
            }

            $customer->update($validated);
        });

        return redirect()->back()->with('success', 'Customer updated successfully');
    }

    // A customer is considered "in use" once it has any real transaction
    // against it — deleting it at that point would cascade-delete those
    // records (see the FKs on sales_orders/payment_ins/sales_returns),
    // silently wiping out financial history. Deactivating instead keeps
    // the records intact.
    private function hasTransactions($customerId): bool
    {
        return DB::table('sales_orders')->where('customer_id', $customerId)->exists()
            || DB::table('payment_ins')->where('customer_id', $customerId)->exists()
            || DB::table('sales_returns')->where('customer_id', $customerId)->exists();
    }

    public function checkDeletable($id)
    {
        Customer::query()->findOrFail($id);
        return response()->json(['has_transactions' => $this->hasTransactions($id)]);
    }

    public function deactivate($id)
    {
        $customer = Customer::query()->findOrFail($id);
        $customer->update(['status' => 'inactive']);
        AuditLog::log('Parties', "Deactivated customer: {$customer->name}", 'UPDATE');
        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $customer = Customer::query()->findOrFail($id);

        if ($this->hasTransactions($id)) {
            return response()->json([
                'message' => 'This party cannot be deleted as it is already used in transactions. Please delete all transactions before deleting the party.',
                'has_transactions' => true,
            ], 409);
        }

        $balance = (float) ($customer->amount_balance ?? 0);

        if ($balance != 0) {
            $account = Account::query()->find($customer->account_id);
            if (!$account) {
                $account = Account::query()->where('name', 'like', '%Receivable%')->first();
            }
            if ($account) {
                $account->balance -= $balance;
                $account->save();
            }

            // Reverses whatever net opening-balance entries this customer
            // accumulated (initial + any later adjustments), rather than
            // hunting down every prior entry individually.
            $this->postOpeningBalanceEntry($customer, -$balance);
        }

        $customer->delete();

        return redirect()->back()->with('success', 'Customer deleted successfully');
    }

    // A customer's opening balance previously only ever bumped
    // chart_of_accounts.balance directly — a cached column the Balance
    // Sheet / Trial Balance reports deliberately don't read (they recompute
    // live from journal_items instead, so they stay correct after any
    // edit/reversal elsewhere). With no real journal entry ever posted, that
    // balance was invisible to those reports — Accounts Receivable could
    // look right on the customer list yet be wrong everywhere it actually
    // mattered. This posts the real entry: Dr Accounts Receivable (debit
    // balance = customer owes us) or Cr (credit balance = customer is
    // prepaid), against Opening Balance Equity — mirroring
    // ProductController::createInitialInventoryEntry's pattern for opening
    // stock. $amount may be negative to record/reverse a credit balance.
    private function postOpeningBalanceEntry(Customer $customer, float $amount, ?string $date = null): void
    {
        if ($amount == 0) {
            return;
        }

        $companyId = $customer->company_id ?? Auth::user()->company_id;

        $receivableAccount = Account::query()->find($customer->account_id)
            ?: Account::query()->where('company_id', $companyId)->where('name', 'like', '%Receivable%')->first();
        $equityAccount = Account::query()->where('company_id', $companyId)->where('code', '3300')->first()
            ?: Account::query()->where('company_id', $companyId)->where('name', 'like', '%Opening Balance%')->first();

        if (!$receivableAccount || !$equityAccount) {
            return;
        }

        $entry = JournalEntry::query()->create([
            'company_id'   => $companyId,
            // Placeholder — overwritten right after using the entry's own
            // (now-known) id, which is guaranteed unique even if this runs
            // for the same customer more than once on the same day (e.g.
            // two balance edits today), unlike a customer_id+date combo.
            'entry_number' => 'JE-CUST-PENDING-' . $customer->id . '-' . microtime(true),
            'date'         => $date ?: now()->toDateString(),
            'reference'    => $customer->customer_code,
            'description'  => 'Opening balance for ' . $customer->name,
            'status'       => 'posted',
            'total_amount' => abs($amount),
            'created_by'   => Auth::id(),
        ]);
        $entry->update(['entry_number' => 'JE-CUST-' . date('Ymd') . '-' . str_pad($entry->id, 6, '0', STR_PAD_LEFT)]);

        JournalItem::query()->create([
            'company_id'       => $companyId,
            'journal_entry_id' => $entry->id,
            'account_id'       => $receivableAccount->id,
            'debit'            => $amount > 0 ? $amount : 0,
            'credit'           => $amount < 0 ? abs($amount) : 0,
            'description'      => 'Opening balance for ' . $customer->name,
        ]);

        JournalItem::query()->create([
            'company_id'       => $companyId,
            'journal_entry_id' => $entry->id,
            'account_id'       => $equityAccount->id,
            'debit'            => $amount < 0 ? abs($amount) : 0,
            'credit'           => $amount > 0 ? $amount : 0,
            'description'      => 'Opening balance for ' . $customer->name,
        ]);
    }
}
