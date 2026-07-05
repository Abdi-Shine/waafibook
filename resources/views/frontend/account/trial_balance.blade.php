@extends('admin.admin_master')
@section('page_title', 'Trial Balance')



@section('admin')

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen overflow-x-hidden">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
        <div>
            <h1 class="text-[16px] font-bold text-primary-dark">Trial Balance</h1>
        </div>
        
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="flex items-center gap-2 px-5 py-2.5 bg-primary text-white font-semibold rounded-[0.5rem] hover:bg-primary/90 transition-all shadow-sm text-sm group">
                <i class="bi bi-printer group-hover:scale-110 transition-transform duration-300"></i>
                Export Report
            </button>
        </div>
    </div>

    <!-- Summary KPI Row (Matching Customer Design) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between group hover:border-primary/20 transition-all">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Debit Balance</p>
                <h3 class="text-[18px] font-black text-primary">$ {{ number_format($totalDebit, 2) }}</h3>
                <p class="text-[11px] text-accent mt-1.5 flex items-center gap-1 font-medium italic"><i class="bi bi-journal-text"></i> Consolidated Ledger</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-all">
                <i class="bi bi-box-arrow-in-down text-lg"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between group hover:border-accent/20 transition-all">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Credit Balance</p>
                <h3 class="text-[18px] font-black text-accent">$ {{ number_format($totalCredit, 2) }}</h3>
                <p class="text-[11px] text-accent mt-1.5 font-medium flex items-center gap-1 font-medium italic"><i class="bi bi-check2-circle"></i> Equilibrium Status</p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent group-hover:bg-accent/10 group-hover:text-white transition-all">
                <i class="bi bi-box-arrow-up text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Trial Balance Table -->
    <div class="bg-white rounded-[1rem] border border-gray-200 shadow-sm overflow-hidden animate-in fade-in duration-500">
        <div class="px-5 py-3 flex items-center justify-between border-b border-gray-100 bg-background/50">
            <div class="flex items-center gap-2">
                <i class="bi bi-list-ul text-primary-dark text-sm"></i>
                <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Account Equilibrium Listing</h2>
            </div>
            <div class="flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest transition-all {{ abs($totalDebit - $totalCredit) < 0.01 ? 'bg-accent/10 text-accent' : 'bg-primary/10 text-primary' }}">
                <i class="bi bi-{{ abs($totalDebit - $totalCredit) < 0.01 ? 'check-circle-fill' : 'exclamation-triangle-fill' }}"></i>
                {{ abs($totalDebit - $totalCredit) < 0.01 ? 'Balanced' : 'Out of Balance' }}
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left whitespace-nowrap">
                <thead>
                    <tr class="bg-white border-b border-gray-100">
                        <th class="px-5 py-4 text-[10px] font-black text-primary-dark uppercase tracking-wider w-32 border-r border-gray-50/50">Account Code</th>
                        <th class="px-5 py-4 text-[10px] font-black text-primary-dark uppercase tracking-wider">Account Name</th>
                        <th class="px-5 py-4 text-[10px] font-black text-primary-dark uppercase tracking-wider">Category</th>
                        <th class="px-5 py-4 text-[10px] font-black text-primary-dark uppercase tracking-wider text-right">Debit Balance</th>
                        <th class="px-5 py-4 text-[10px] font-black text-primary-dark uppercase tracking-wider text-right">Credit Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-bold">
                    @foreach($accounts->groupBy('category') as $category => $categoryAccounts)
                        <tr class="bg-gray-50/30">
                            <td colspan="5" class="px-5 py-2.5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] italic">{{ $category }}</td>
                        </tr>
                        @foreach($categoryAccounts as $acc)
                            @php
                                $isAssetOrExpense = in_array($acc->category, ['assets', 'expenses']);
                                $debit = 0;
                                $credit = 0;
                                if ($isAssetOrExpense) {
                                    if ($acc->balance >= 0) $debit = $acc->balance;
                                    else $credit = abs($acc->balance);
                                } else {
                                    if ($acc->balance >= 0) $credit = $acc->balance;
                                    else $debit = abs($acc->balance);
                                }
                            @endphp
                            <tr class="hover:bg-gray-50/60 transition-colors bg-white group">
                                <td class="px-5 py-4 border-r border-gray-50/50">
                                    <span class="text-[11px] font-black text-primary bg-primary/5 px-2.5 py-1 rounded border border-primary/10">
                                        {{ $acc->code }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-[13px] font-bold text-primary-dark">{{ $acc->name }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-[10px] font-bold uppercase tracking-tight text-gray-400 px-2 py-0.5 bg-gray-50 rounded">{{ $acc->type }}</span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <span class="text-[13px] font-bold text-gray-700">
                                        {{ $debit > 0 ? number_format($debit, 2) : '-' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <span class="text-[13px] font-bold text-gray-700">
                                        {{ $credit > 0 ? number_format($credit, 2) : '-' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50/50">
                    <tr class="border-t-2 border-primary-dark/5">
                        <td colspan="3" class="px-5 py-5 text-[10px] font-black text-primary-dark uppercase tracking-widest">Grand Total Equilibrium</td>
                        <td class="px-5 py-5 text-[18px] font-black text-right text-primary-dark">$ {{ number_format($totalDebit, 2) }}</td>
                        <td class="px-5 py-5 text-[18px] font-black text-right text-accent">$ {{ number_format($totalCredit, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@endsection


