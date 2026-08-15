<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Support\DocumentNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JournalEntryController extends Controller
{
    public function index()
    {
        $cid = Auth::user()->company_id;

        $entries = JournalEntry::query()->with(['items.account', 'creator'])
            ->where('company_id', $cid)
            ->orderBy('date', 'desc')
            ->orderBy('entry_number', 'desc')
            ->get();

        $accounts = Account::query()->where('company_id', $cid)->orderBy('code')->get();

        if ($entries->isEmpty()) {
            $this->seedDefaultEntries();
            $entries = JournalEntry::query()->with(['items.account', 'creator'])
                ->where('company_id', $cid)->get();
        }

        return view('frontend.account.journal_entry', compact('entries', 'accounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'description' => 'required',
            'items' => 'required|array|min:2',
            'items.*.account_id' => 'required|exists:chart_of_accounts,id',
            'items.*.debit' => 'required|numeric|min:0',
            'items.*.credit' => 'required|numeric|min:0',
        ]);

        // Validate debits equal credits
        $totalDebit = collect($request->items)->sum('debit');
        $totalCredit = collect($request->items)->sum('credit');

        if (abs($totalDebit - $totalCredit) > 0.001) {
            return redirect()->back()->with('error', 'Debits must equal Credits (Total: ' . $totalDebit . ')');
        }

        $cid = Auth::user()->company_id;

        DB::transaction(function () use ($request, $totalDebit, $cid) {
            $entry = JournalEntry::query()->create([
                'entry_number' => DocumentNumber::next(JournalEntry::query(), 'entry_number', 'JE-' . date('Ymd') . '-', 4),
                'date'         => $request->date,
                'reference'    => $request->reference,
                'description'  => $request->description,
                'status'       => 'posted',
                'total_amount' => $totalDebit,
                'company_id'   => $cid,
                'created_by'   => Auth::id(),
            ]);

            foreach ($request->items as $item) {
                /** @var array $item */
                if ($item['debit'] > 0 || $item['credit'] > 0) {
                    JournalItem::query()->create([
                        'journal_entry_id' => $entry->id,
                        'account_id'       => $item['account_id'],
                        'company_id'       => $cid,
                        'description'      => $item['description'] ?? $request->description,
                        'debit'            => $item['debit'],
                        'credit'           => $item['credit'],
                    ]);
                }
            }
        });

        return redirect()->back()->with('success', 'Journal Entry created and posted.');
    }

    public function destroy($id)
    {
        /** @var JournalEntry|null $entry */
        $entry = JournalEntry::query()->findOrFail($id);
        
        DB::transaction(function () use ($entry) {
            // Balances are automatically reversed by JournalItem's deleted observer
            foreach ($entry->items as $item) {
                /** @var JournalItem $item */
                $item->delete();
            }
            $entry->delete();
        });

        return redirect()->back()->with('success', 'Journal Entry deleted and balances reversed.');
    }

    private function seedDefaultEntries()
    {
        $cid = Auth::user()->company_id;

        $cash    = Account::query()->where('company_id', $cid)->where('code', '1010')->first();
        $sales   = Account::query()->where('company_id', $cid)->where('code', '4000')->first();
        $expense = Account::query()->where('company_id', $cid)->where('code', '5000')->first();

        if (!$cash || !$sales || !$expense) return;

        DB::transaction(function () use ($cash, $sales, $expense, $cid) {
            $e1 = JournalEntry::query()->create([
                'entry_number' => 'JE-2026-0001',
                'date'         => date('Y-m-d'),
                'description'  => 'Initial Sales Recording',
                'status'       => 'posted',
                'total_amount' => 1000,
                'company_id'   => $cid,
                'created_by'   => Auth::id() ?? 1,
            ]);

            JournalItem::query()->create(['journal_entry_id' => $e1->id, 'account_id' => $cash->id,  'company_id' => $cid, 'debit' => 1000, 'credit' => 0]);
            JournalItem::query()->create(['journal_entry_id' => $e1->id, 'account_id' => $sales->id, 'company_id' => $cid, 'debit' => 0,    'credit' => 1000]);

            $e2 = JournalEntry::query()->create([
                'entry_number' => 'JE-2026-0002',
                'date'         => date('Y-m-d'),
                'description'  => 'Office Supplies Expense',
                'status'       => 'posted',
                'total_amount' => 200,
                'company_id'   => $cid,
                'created_by'   => Auth::id() ?? 1,
            ]);

            JournalItem::query()->create(['journal_entry_id' => $e2->id, 'account_id' => $expense->id, 'company_id' => $cid, 'debit' => 200, 'credit' => 0]);
            JournalItem::query()->create(['journal_entry_id' => $e2->id, 'account_id' => $cash->id,    'company_id' => $cid, 'debit' => 0,   'credit' => 200]);
        });
    }
}
