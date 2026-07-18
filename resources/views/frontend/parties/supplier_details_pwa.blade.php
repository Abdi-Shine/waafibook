@extends('admin.admin_master')
@section('page_title', 'Suppliers')

@section('admin')
<div class="pb-28 bg-background min-h-screen" x-data="{
    search: '',
    paymentFilter: '',
    showAddModal: false,
    saving: false,
    editingId: null,
    formErrors: {},
    form: { name: '', phone: '', supplier_type: 'company', email: '', address: '', amount_balance: '' },
    suppliers: @js($suppliers->map(fn($s) => [
        'id'      => $s->id,
        'name'    => $s->name,
        'phone'   => $s->phone,
        'email'   => $s->email,
        'address' => $s->address,
        'supplier_type' => $s->supplier_type,
        'amount'  => abs($s->amount_balance),
        'balance' => (float) $s->amount_balance,
        'date'    => $s->latest_date ? \Carbon\Carbon::parse($s->latest_date)->format('d M Y') : '—',
        'label'   => $s->amount_balance > 0 ? 'You\'ll Pay' : ($s->amount_balance < 0 ? 'You\'ll Get' : 'Settled'),
        'labelColor' => $s->amount_balance > 0 ? 'text-red-500' : ($s->amount_balance < 0 ? 'text-accent' : 'text-gray-400'),
        'url'     => route('parties.ledger', ['type' => 'supplier', 'id' => $s->id]),
    ])),
    get filtered() {
        let list = this.suppliers;
        if (this.paymentFilter === 'paid') list = list.filter(s => s.balance === 0);
        if (this.paymentFilter === 'unpaid') list = list.filter(s => s.balance !== 0);
        if (this.search) {
            const q = this.search.toLowerCase();
            list = list.filter(s => s.name.toLowerCase().includes(q));
        }
        return list;
    },
    openAddModal() {
        this.editingId = null;
        this.form = { name: '', phone: '', supplier_type: 'company', email: '', address: '', amount_balance: '' };
        this.formErrors = {};
        this.showAddModal = true;
    },
    openEditModal(supplier) {
        this.editingId = supplier.id;
        this.form = {
            name: supplier.name,
            phone: supplier.phone || '',
            supplier_type: supplier.supplier_type || 'company',
            email: supplier.email || '',
            address: supplier.address || '',
            amount_balance: supplier.balance ?? 0,
        };
        this.formErrors = {};
        this.showAddModal = true;
    },
    async submitSupplier() {
        this.saving = true;
        this.formErrors = {};
        try {
            const payload = {
                ...this.form,
                email: this.form.email || null,
                address: this.form.address || null,
                amount_balance: this.form.amount_balance === '' ? 0 : this.form.amount_balance,
            };
            const url = this.editingId
                ? '{{ url('/suppliers') }}/' + this.editingId
                : '{{ route('supplier.store') }}';
            const response = await fetch(url, {
                method: this.editingId ? 'PUT' : 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content,
                },
                body: JSON.stringify(payload),
            });
            const data = await response.json();
            if (response.status === 422) {
                this.formErrors = data.errors || {};
                return;
            }
            if (!response.ok) {
                Swal.fire({ icon: 'error', title: 'Something went wrong', text: data.message || 'Please try again.' });
                return;
            }
            const balance = parseFloat(data.amount_balance) || 0;
            const row = {
                id: data.id,
                name: data.name,
                phone: data.phone,
                email: data.email,
                address: data.address,
                supplier_type: data.supplier_type,
                amount: Math.abs(balance),
                balance: balance,
                label: balance > 0 ? 'You\'ll Pay' : (balance < 0 ? 'You\'ll Get' : 'Settled'),
                labelColor: balance > 0 ? 'text-red-500' : (balance < 0 ? 'text-accent' : 'text-gray-400'),
                url: '{{ url('/parties/ledger') }}?type=supplier&id=' + data.id,
            };
            if (this.editingId) {
                row.date = this.suppliers.find(s => s.id === this.editingId)?.date ?? '—';
                const idx = this.suppliers.findIndex(s => s.id === this.editingId);
                if (idx !== -1) this.suppliers[idx] = row;
                this.showAddModal = false;
                Swal.fire({ toast: true, position: 'top', icon: 'success', title: data.name + ' updated', timer: 1500, showConfirmButton: false });
            } else {
                row.date = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                this.suppliers.unshift(row);
                this.form = { name: '', phone: '', supplier_type: 'company', email: '', address: '', amount_balance: '' };
                Swal.fire({ toast: true, position: 'top', icon: 'success', title: data.name + ' registered', timer: 1500, showConfirmButton: false });
            }
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Network error', text: 'Could not save supplier. Please try again.' });
        } finally {
            this.saving = false;
        }
    }
}">
    <div class="flex items-center justify-between gap-3 px-5 pt-4">
        <h1 class="text-[16px] font-black text-primary-dark">Supplier Management</h1>
        <button @click="openAddModal()"
           class="flex items-center gap-1 px-3 py-2 bg-accent text-primary font-bold rounded-xl text-[13px] shrink-0 whitespace-nowrap">
            <i class="bi bi-plus-lg text-base"></i> Add Supplier
        </button>
    </div>

    <div class="flex gap-3 px-5 pt-4 overflow-x-auto no-scrollbar">
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[120px]">
            <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center text-accent mb-2">
                <i class="bi bi-truck text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Total Suppliers</p>
            <p class="text-[16px] font-black text-primary">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[120px]">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-building-check text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1">Active</p>
            <p class="text-[16px] font-black text-primary">{{ number_format($stats['active']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[120px]">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-cash-stack text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Total Payables</p>
            <p class="text-[16px] font-black text-primary">${{ number_format($stats['payables'], 0) }}</p>
        </div>
    </div>

    <div class="flex items-center gap-2 px-5 pt-4 mb-2">
        <div class="flex-1 flex items-center gap-2 px-3 py-2.5 bg-gray-100 rounded-xl">
            <i class="bi bi-search text-gray-400 text-sm"></i>
            <input type="text" x-model="search" placeholder="SEARCH PARTY"
                class="flex-1 text-[13px] text-gray-700 font-medium tracking-wide placeholder-gray-400 outline-none border-none ring-0 bg-transparent"
                autocomplete="off">
            <button x-show="search" @click="search = ''" class="text-gray-400 hover:text-gray-600">
                <i class="bi bi-x text-base"></i>
            </button>
        </div>
        <div class="relative shrink-0">
            <select x-model="paymentFilter"
                class="pl-3 pr-8 py-2.5 bg-gray-100 border-none rounded-xl text-[13px] font-medium text-gray-700 outline-none appearance-none max-w-[110px]">
                <option value="">All</option>
                <option value="paid">Paid</option>
                <option value="unpaid">Unpaid</option>
            </select>
            <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
        </div>
    </div>

    <div class="bg-white border-t border-b border-gray-100">
        <template x-for="supplier in filtered" :key="supplier.id">
            <a :href="supplier.url"
               class="flex items-center justify-between px-5 py-4 border-b border-gray-100 last:border-0 active:bg-gray-50 transition-colors">
                <div class="min-w-0 pr-3">
                    <p class="text-[15px] font-black text-text-primary leading-tight truncate" x-text="supplier.name.toUpperCase()"></p>
                    <p class="text-xs text-text-secondary mt-0.5" x-text="supplier.date"></p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <div class="text-right">
                        <p class="text-[15px] font-black text-text-primary" x-text="'$ ' + parseFloat(supplier.amount).toFixed(2)"></p>
                        <p class="text-xs font-bold mt-0.5" :class="supplier.labelColor" x-text="supplier.label"></p>
                    </div>
                    <button type="button" @click.stop.prevent="openEditModal(supplier)"
                        class="w-8 h-8 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 active:bg-gray-100">
                        <i class="bi bi-pencil text-xs"></i>
                    </button>
                </div>
            </a>
        </template>
        <template x-if="!filtered.length">
            <div class="py-10 text-center">
                <i class="bi bi-truck text-3xl text-gray-300"></i>
                <p class="text-sm text-text-secondary mt-2 font-semibold" x-text="search ? 'No suppliers match your search' : 'No suppliers yet'"></p>
            </div>
        </template>
    </div>

    {{-- Add Supplier — mobile bottom sheet --}}
    <div x-show="showAddModal" x-cloak x-transition.opacity
        class="fixed inset-0 z-[70] bg-slate-900/40" @click.self="showAddModal = false">
        <div x-show="showAddModal" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[1.5rem] max-h-[90vh] overflow-y-auto">

            <div class="px-5 py-4 bg-primary flex items-center justify-between sticky top-0 z-10">
                <h2 class="text-white font-bold text-[16px]" x-text="editingId ? 'Edit Supplier' : 'Register Supplier'"></h2>
                <button @click="showAddModal = false" class="w-8 h-8 bg-white/10 rounded-lg text-white flex items-center justify-center">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>

            <form @submit.prevent="submitSupplier()" class="p-5 flex flex-col gap-4">
                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Full Name <span class="text-primary">*</span></label>
                    <input type="text" x-model="form.name" required placeholder="Enter supplier name"
                        :class="formErrors.name ? 'border-red-400' : 'border-gray-200'"
                        class="w-full px-4 py-2.5 bg-gray-50 border rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                    <p x-show="formErrors.name" x-text="formErrors.name?.[0]" class="text-red-500 font-bold text-[11px] mt-1"></p>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Phone Number <span class="text-primary">*</span></label>
                    <input type="text" x-model="form.phone" required inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="e.g. 252612345678"
                        :class="formErrors.phone ? 'border-red-400' : 'border-gray-200'"
                        class="w-full px-4 py-2.5 bg-gray-50 border rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                    <p x-show="formErrors.phone" x-text="formErrors.phone?.[0]" class="text-red-500 font-bold text-[11px] mt-1"></p>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Supplier Type</label>
                    <div class="relative">
                        <select x-model="form.supplier_type"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            <option value="individual">Individual</option>
                            <option value="company">Company</option>
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Email</label>
                    <input type="email" x-model="form.email" placeholder="Optional"
                        :class="formErrors.email ? 'border-red-400' : 'border-gray-200'"
                        class="w-full px-4 py-2.5 bg-gray-50 border rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                    <p x-show="formErrors.email" x-text="formErrors.email?.[0]" class="text-red-500 font-bold text-[11px] mt-1"></p>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Address</label>
                    <input type="text" x-model="form.address" placeholder="Optional"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Opening Balance</label>
                    <input type="number" step="0.01" x-model="form.amount_balance" placeholder="0.00"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div class="flex gap-3 mt-2">
                    <button type="button" @click="showAddModal = false"
                        class="flex-1 py-3.5 bg-accent text-primary font-bold rounded-xl text-[13px] uppercase tracking-wide">
                        Cancel
                    </button>
                    <button type="submit" :disabled="saving"
                        class="flex-1 py-3.5 bg-primary text-white font-bold rounded-xl text-[13px] uppercase tracking-wide flex items-center justify-center gap-2"
                        :class="saving ? 'opacity-60' : ''">
                        <i class="bi" :class="saving ? 'bi-arrow-repeat animate-spin' : 'bi-check2-circle'"></i>
                        <span x-text="saving ? 'Saving...' : (editingId ? 'Save Changes' : 'Save & New')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
