@extends('admin.admin_master')
@section('page_title', 'Customers')

@section('admin')
<div class="pb-28 bg-background min-h-screen" x-data="{
    search: '',
    showAddModal: false,
    saving: false,
    editingId: null,
    formErrors: {},
    form: { name: '', phone: '', customer_type: 'individual', email: '', address: '', amount_balance: '' },
    customers: @js($customers->map(fn($c) => [
        'id'      => $c->id,
        'name'    => $c->name,
        'phone'   => $c->phone,
        'email'   => $c->email,
        'address' => $c->address,
        'customer_type' => $c->customer_type,
        'amount'  => abs($c->amount_balance),
        'balance' => (float) $c->amount_balance,
        'date'    => $c->latest_date ? \Carbon\Carbon::parse($c->latest_date)->format('d M Y') : '—',
        'label'   => $c->amount_balance > 0 ? 'You\'ll Get' : ($c->amount_balance < 0 ? 'You\'ll Pay' : 'Settled'),
        'labelColor' => $c->amount_balance > 0 ? 'text-accent' : ($c->amount_balance < 0 ? 'text-red-500' : 'text-gray-400'),
        'url'     => route('parties.ledger', ['type' => 'customer', 'id' => $c->id]),
    ])),
    get filtered() {
        if (!this.search) return this.customers;
        const q = this.search.toLowerCase();
        return this.customers.filter(c => c.name.toLowerCase().includes(q));
    },
    openAddModal() {
        this.editingId = null;
        this.form = { name: '', phone: '', customer_type: 'individual', email: '', address: '', amount_balance: '' };
        this.formErrors = {};
        this.showAddModal = true;
    },
    openEditModal(customer) {
        this.editingId = customer.id;
        this.form = {
            name: customer.name,
            phone: customer.phone || '',
            customer_type: customer.customer_type || 'individual',
            email: customer.email || '',
            address: customer.address || '',
            amount_balance: customer.balance ?? 0,
        };
        this.formErrors = {};
        this.showAddModal = true;
    },
    async submitCustomer() {
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
                ? '{{ url('/customers') }}/' + this.editingId
                : '{{ route('customer.store') }}';
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
            const c = data.customer || data;
            const balance = parseFloat(c.amount_balance) || 0;
            const row = {
                id: c.id,
                name: c.name,
                phone: c.phone,
                email: c.email,
                address: c.address,
                customer_type: c.customer_type,
                amount: Math.abs(balance),
                balance: balance,
                label: balance > 0 ? 'You\'ll Get' : (balance < 0 ? 'You\'ll Pay' : 'Settled'),
                labelColor: balance > 0 ? 'text-accent' : (balance < 0 ? 'text-red-500' : 'text-gray-400'),
                url: '{{ url('/parties/ledger') }}?type=customer&id=' + c.id,
            };
            if (this.editingId) {
                row.date = this.customers.find(x => x.id === this.editingId)?.date ?? '—';
                const idx = this.customers.findIndex(x => x.id === this.editingId);
                if (idx !== -1) this.customers[idx] = row;
                this.showAddModal = false;
                Swal.fire({ toast: true, position: 'top', icon: 'success', title: c.name + ' updated', timer: 1500, showConfirmButton: false });
            } else {
                row.date = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                this.customers.unshift(row);
                this.form = { name: '', phone: '', customer_type: 'individual', email: '', address: '', amount_balance: '' };
                Swal.fire({ toast: true, position: 'top', icon: 'success', title: c.name + ' registered', timer: 1500, showConfirmButton: false });
            }
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Network error', text: 'Could not save customer. Please try again.' });
        } finally {
            this.saving = false;
        }
    }
}">
    <div class="flex gap-3 px-5 pt-4 overflow-x-auto no-scrollbar">
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[120px]">
            <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center text-accent mb-2">
                <i class="bi bi-people text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Total Customers</p>
            <p class="text-[16px] font-black text-primary">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[120px]">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-person-check-fill text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1">Active</p>
            <p class="text-[16px] font-black text-primary">{{ number_format($stats['active']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[120px]">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-wallet2 text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Receivables</p>
            <p class="text-[16px] font-black text-primary">${{ number_format($stats['receivables'], 0) }}</p>
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
        <button @click="openAddModal()"
           class="flex items-center gap-1 px-3 py-2.5 bg-accent text-primary font-bold rounded-xl text-[13px] shrink-0 whitespace-nowrap">
            <i class="bi bi-plus-lg text-base"></i> New Party
        </button>
    </div>

    <div class="bg-white border-t border-b border-gray-100">
        <template x-for="customer in filtered" :key="customer.id">
            <a :href="customer.url"
               class="flex items-center justify-between px-5 py-4 border-b border-gray-100 last:border-0 active:bg-gray-50 transition-colors">
                <div class="min-w-0 pr-3">
                    <p class="text-[15px] font-black text-text-primary leading-tight truncate" x-text="customer.name.toUpperCase()"></p>
                    <p class="text-xs text-text-secondary mt-0.5" x-text="customer.date"></p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <div class="text-right">
                        <p class="text-[15px] font-black text-text-primary" x-text="'$ ' + parseFloat(customer.amount).toFixed(2)"></p>
                        <p class="text-xs font-bold mt-0.5" :class="customer.labelColor" x-text="customer.label"></p>
                    </div>
                    <button type="button" @click.stop.prevent="openEditModal(customer)"
                        class="w-8 h-8 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 active:bg-gray-100">
                        <i class="bi bi-pencil text-xs"></i>
                    </button>
                </div>
            </a>
        </template>
        <template x-if="!filtered.length">
            <div class="py-10 text-center">
                <i class="bi bi-people text-3xl text-gray-300"></i>
                <p class="text-sm text-text-secondary mt-2 font-semibold" x-text="search ? 'No customers match your search' : 'No customers yet'"></p>
            </div>
        </template>
    </div>

    {{-- Add/Edit Customer — mobile bottom sheet --}}
    <div x-show="showAddModal" x-cloak x-transition.opacity
        class="fixed inset-0 z-[70] bg-slate-900/40" @click.self="showAddModal = false">
        <div x-show="showAddModal" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[1.5rem] max-h-[90vh] overflow-y-auto">

            <div class="px-5 py-4 bg-primary flex items-center justify-between sticky top-0 z-10">
                <h2 class="text-white font-bold text-[16px]" x-text="editingId ? 'Edit Customer' : 'Register Customer'"></h2>
                <button @click="showAddModal = false" class="w-8 h-8 bg-white/10 rounded-lg text-white flex items-center justify-center">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>

            <form @submit.prevent="submitCustomer()" class="p-5 flex flex-col gap-4">
                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Full Name <span class="text-primary">*</span></label>
                    <input type="text" x-model="form.name" required placeholder="Enter customer name"
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
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Customer Type</label>
                    <div class="relative">
                        <select x-model="form.customer_type"
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
