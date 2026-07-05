@extends('admin.admin_master')
@section('page_title', 'General Ledger')



@section('admin')

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen overflow-x-hidden">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
        <div>
            <h1 class="text-[16px] font-bold text-primary-dark">General Ledger</h1>
        </div>

        <form action="{{ route('account.ledger') }}" method="GET" class="flex items-center gap-3 w-full md:w-auto">
            <div class="relative group flex-1 md:min-w-[320px]">
                <select name="account_id" onchange="this.form.submit()" class="w-full pl-4 pr-10 py-2.5 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                    <option value="">Select Account...</option>
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" {{ isset($selectedAccount) && $selectedAccount->id == $acc->id ? 'selected' : '' }}>
                            [{{ $acc->code }}] {{ $acc->name }}
                        </option>
                    @endforeach
                </select>
                <i class="bi bi-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-primary text-white font-semibold rounded-[0.5rem] hover:bg-primary/90 transition-all shadow-sm text-sm">
                Filter
            </button>
        </form>
    </div>

    @if(isset($selectedAccount))
    <!-- Account Summary Card -->
    <!-- Account Summary Cards (Matching Customer Design) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between group hover:border-primary/20 hover:-translate-y-0.5 transition-all transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Account Detail</p>
                <h3 class="text-[18px] font-black text-primary">{{ $selectedAccount->name }}</h3>
                <p class="text-[11px] text-accent mt-1.5 flex items-center gap-1 font-medium italic"><i class="bi bi-tag"></i> {{ $selectedAccount->code }}</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-all">
                <i class="bi bi-person-badge text-lg"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between group hover:border-primary/20 hover:-translate-y-0.5 transition-all transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Classification</p>
                <h3 class="text-[18px] font-black text-primary capitalize">{{ $selectedAccount->type }}</h3>
                <p class="text-[11px] text-accent mt-1.5 font-medium flex items-center gap-1 uppercase tracking-tighter"><i class="bi bi-grid-3x3-gap"></i> {{ $selectedAccount->category }}</p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent">
                <i class="bi bi-layers text-lg"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between group hover:border-accent/20 hover:-translate-y-0.5 transition-all transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Current Valuation</p>
                <h3 class="text-[18px] font-black text-primary">$ {{ number_format($selectedAccount->balance, 2) }}</h3>
                <p class="text-[11px] text-accent mt-1.5 font-medium italic">Net Account Worth</p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent">
                <i class="bi bi-currency-dollar text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Transaction History Table -->
    <div class="bg-white rounded-[1rem] border border-gray-200 shadow-sm overflow-hidden animate-in fade-in duration-500">
        <div class="px-5 py-3 flex items-center justify-between border-b border-gray-100 bg-background/50">
            <div class="flex items-center gap-2">
                <i class="bi bi-list-ul text-primary-dark text-sm"></i>
                <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Transaction History</h2>
            </div>
            <button onclick="window.print()" class="w-8 h-8 rounded-md bg-white border border-gray-200 text-gray-400 hover:text-primary hover:border-primary transition-all flex items-center justify-center text-sm shadow-sm">
                <i class="bi bi-printer"></i>
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left whitespace-nowrap">
                <thead>
                    <tr class="bg-white border-b border-gray-100">
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-32 border-r border-gray-50/50">Date</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Reference</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Description</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Debit</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Credit</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Running Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php
                        $runningBalance = 0;
                        // For assets/expenses: Debit increases, Credit decreases
                        // For others: Credit increases, Debit decreases
                        $isAssetOrExpense = in_array($selectedAccount->category, ['assets', 'expenses']);
                    @endphp

                    @forelse($items as $item)
                        @php
                            if ($isAssetOrExpense) {
                                $runningBalance += ($item->debit - $item->credit);
                            } else {
                                $runningBalance += ($item->credit - $item->debit);
                            }
                        @endphp
                        <tr class="hover:bg-gray-50/60 transition-colors bg-white group">
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark border-r border-gray-50/50">
                                <span class="text-[13px] font-bold text-primary-dark">{{ \Carbon\Carbon::parse($item->entry->date)->format('d M, Y') }}</span>
                            </td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark">
                                <span class="text-[11px] font-black text-primary bg-primary/5 px-2 py-0.5 rounded border border-primary/10">{{ $item->entry->entry_number }}</span>
                            </td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark">
                                <div class="flex flex-col">
                                    <span class="text-[12px] font-bold text-gray-700">{{ $item->description }}</span>
                                    <span class="text-[10px] text-gray-400 italic">Ref: {{ $item->entry->reference ?: 'N/A' }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-right">
                                <span class="text-[13px] font-bold text-gray-700">
                                    {{ $item->debit > 0 ? number_format($item->debit, 2) : '-' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-right">
                                <span class="text-[13px] font-bold text-gray-700">
                                    {{ $item->credit > 0 ? number_format($item->credit, 2) : '-' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-right">
                                <span class="text-[13px] font-black {{ $runningBalance >= 0 ? 'text-accent' : 'text-primary' }}">
                                    $ {{ number_format($runningBalance, 2) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-8 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-200 mb-4">
                                        <i class="bi bi-inbox text-4xl"></i>
                                    </div>
                                    <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">No transactions found for this account</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($items->isNotEmpty())
                <tfoot class="bg-gray-50/50">
                    <tr class="border-t border-gray-100">
                        <td colspan="3" class="px-5 py-4 text-[10px] font-black text-primary-dark uppercase tracking-widest">Total Period Movement</td>
                        <td class="px-5 py-4 text-[13px] font-bold text-right text-primary-dark">$ {{ number_format($items->sum('debit'), 2) }}</td>
                        <td class="px-5 py-4 text-[13px] font-bold text-right text-primary-dark">$ {{ number_format($items->sum('credit'), 2) }}</td>
                        <td class="px-5 py-4 text-[13px] font-black text-right border-l border-gray-100 {{ $runningBalance >= 0 ? 'text-accent' : 'text-primary' }}">
                            $ {{ number_format($runningBalance, 2) }}
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
    @else
    <!-- Empty State -->
    <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-16 text-center animate-in fade-in zoom-in-95 duration-700">
        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center text-gray-200 mx-auto mb-6">
            <i class="bi bi-search text-4xl"></i>
        </div>
        <h2 class="text-lg font-black text-primary-dark uppercase tracking-tighter mb-2">Select an Account to View Ledger</h2>
        <p class="text-[13px] text-gray-400 font-medium max-w-sm mx-auto">Choose a chart of account from the dropdown above to audit all related journal entries and running balances.</p>
    </div>
    @endif
</div>

@endsection


