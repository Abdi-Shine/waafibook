@extends('admin.admin_master')
@section('page_title', 'Company Structure')


@section('admin')
<div class="px-4 py-8 md:px-8 md:py-10 bg-[#f8fafc] min-h-screen font-inter">

    {{-- Flash --}}
    @if(session('success'))
    <div class="mb-5 px-4 py-3 bg-accent/10 border border-accent/20 text-accent rounded-xl flex items-center gap-3 text-sm font-semibold">
        <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
    </div>
    @endif

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Company Structure</h1>
            <p class="text-[12px] text-gray-500 font-medium mt-1">Manage your branch network</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="openBranchModal('add')"
                class="flex items-center gap-2 px-5 py-2.5 btn-brand-gradient text-white font-semibold rounded-[0.5rem] hover:opacity-90 transition-all shadow-sm text-sm group">
                <i class="bi bi-diagram-3 group-hover:scale-110 transition-transform duration-300"></i>
                New Branch
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <!-- Company Name -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[13px] font-medium text-gray-400 mb-1">Company Entity</p>
                <h3 class="text-[24px] font-black text-primary">{{ $company->name ?? 'Company' }}</h3>
                <p class="text-[12px] font-bold text-primary mt-1.5 flex items-center gap-1">
                    <i class="bi bi-check-circle-fill text-[10px]"></i> Active Operations
                </p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary">
                <i class="bi bi-building text-lg"></i>
            </div>
        </div>

        <!-- Total Branches -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[13px] font-medium text-gray-400 mb-1">Total Branches</p>
                <h3 class="text-[24px] font-black text-primary">{{ $branches->count() }}</h3>
                <p class="text-[12px] font-bold text-primary mt-1.5 flex items-center gap-1">
                    <i class="bi bi-arrow-up text-[10px]"></i> Growing Network
                </p>
            </div>
            <div class="w-11 h-11 bg-accent/20 rounded-[0.6rem] flex items-center justify-center text-accent">
                <i class="bi bi-diagram-3 text-lg"></i>
            </div>
        </div>

        <!-- System Status -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[13px] font-medium text-gray-400 mb-1">System Status</p>
                <h3 class="text-[24px] font-black text-primary">Live</h3>
                <p class="text-[12px] font-bold text-primary mt-1.5 flex items-center gap-1">
                    <i class="bi bi-shield-check text-[10px]"></i> Fully Operational
                </p>
            </div>
            <div class="w-11 h-11 bg-accent/20 rounded-[0.6rem] flex items-center justify-center text-accent">
                <i class="bi bi-hdd-network text-lg"></i>
            </div>
        </div>
    </div>

    {{-- Branches Content --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100">
            <h3 class="text-sm font-black font-poppins text-primary flex items-center gap-2">
                <i class="bi bi-diagram-3 text-primary"></i> Branch Network Overview
                <span class="px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded-full text-[10px] font-bold">({{ $branches->count() }})</span>
            </h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($branches as $br)
            <div class="branch-card">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center shrink-0">
                            <i class="bi bi-building text-primary text-sm"></i>
                        </div>
                        <div>
                            <h4 class="font-black text-primary font-poppins text-[12px] leading-tight">{{ $br->name }}</h4>
                            <div class="text-[9px] text-gray-400 font-mono font-bold">{{ $br->code }}</div>
                        </div>
                    </div>
                    <span class="px-1.5 py-0.5 {{ $br->is_active ? 'bg-accent/10 text-accent' : 'bg-gray-100 text-gray-500' }} rounded text-[8px] font-black uppercase shrink-0">
                        {{ $br->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <div class="space-y-1 mb-2">
                    <div class="flex items-center gap-1.5 text-xs font-bold text-primary-dark leading-tight">
                        <i class="bi bi-geo-alt text-primary" class="icon-xs"></i>
                        <span class="truncate">{{ $br->district ?: $br->address ?: 'No address' }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-bold text-primary-dark leading-tight">
                        <i class="bi bi-person-badge text-primary" class="icon-xs"></i>
                        <span class="truncate">{{ $br->manager_name ?: 'No manager' }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-bold text-primary-dark leading-tight">
                        <i class="bi bi-bank text-primary" class="icon-xs"></i>
                        <span class="truncate">{{ $br->bank_name ?: 'No bank' }}</span>
                    </div>
                </div>
                <div class="flex items-center justify-end pt-2 border-t border-gray-100">
                    <div class="flex items-center gap-1.5">
                        <button onclick='openBranchModal("edit", @json($br))' class="w-7 h-7 bg-primary text-white rounded-md hover:bg-primary/90 transition-all flex items-center justify-center">
                            <i class="bi bi-pencil-fill" class="icon-xs"></i>
                        </button>
                        <form action="{{ route('branch.delete', $br->id) }}" method="POST" id="delete-branch-{{ $br->id }}" class="inline">
                            @csrf @method('DELETE')
                            <button type="button" onclick="confirmDelete('delete-branch-{{ $br->id }}', 'Delete branch {{ $br->name }}?')" class="w-7 h-7 bg-primary/10 text-primary hover:bg-primary/10 hover:text-white rounded-md transition-all flex items-center justify-center">
                                 <i class="bi bi-trash3" class="icon-xs"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-3 text-center py-16 text-gray-400">
                <i class="bi bi-diagram-3 text-5xl mb-3 block opacity-20"></i>
                <p class="font-bold text-sm">No branches yet. Add your first branch!</p>
            </div>
            @endforelse
        </div>
    </div>

</div>{{-- end main --}}


{{-- ===== BRANCH MODAL ===== --}}
<div id="branchModal" class="cs-modal-overlay" onclick="if(event.target===this)closeBranchModal()">
    <div class="cs-modal-box">
        <div class="cs-modal-header">
            <div>
                <h3 id="branchModalTitle" class="text-base font-black text-white font-poppins">Add New Branch</h3>
                <p class="text-[10px] font-bold text-accent uppercase tracking-widest mt-0.5">Branch Management</p>
            </div>
            <button onclick="closeBranchModal()" class="w-9 h-9 rounded-xl bg-white/10 text-white hover:bg-white/20 transition-all flex items-center justify-center">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </div>
        <div class="cs-modal-body cs-scrollbar">
            <form id="branchForm" method="POST">
                @csrf
                <input type="hidden" name="_method" id="branchMethod" value="POST">
                <input type="hidden" name="company_id" value="{{ $company->id ?? 1 }}">
                <input type="hidden" name="branch_id_hidden" id="branchIdHidden">
                <div class="space-y-4">
                    <div class="cs-grid-3">
                        <div>
                            <label class="cs-label">Branch Name</label>
                            <input type="text" name="name" id="b_name" required class="cs-input" placeholder="e.g. Main Branch">
                        </div>
                        <div>
                            <label class="cs-label">Code</label>
                            <input type="text" name="code" id="b_code" required class="cs-input" placeholder="BR-001">
                        </div>
                        <div>
                            <label class="cs-label">Status</label>
                            <select name="is_active" id="b_status" class="cs-input">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="cs-grid-3">
                        <div>
                            <label class="cs-label">Manager Name</label>
                            <input type="text" name="manager_name" id="b_mgr" class="cs-input">
                        </div>
                        <div>
                            <label class="cs-label">Manager Phone</label>
                            <input type="text" name="manager_phone" id="b_phone" class="cs-input" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                        <div>
                            <label class="cs-label">District</label>
                            <input type="text" name="district" id="b_district" class="cs-input">
                        </div>
                    </div>
                    <div class="cs-grid-3">
                        <div>
                            <label class="cs-label">Bank Name</label>
                            <input type="text" name="bank_name" id="b_bank" class="cs-input" readonly placeholder="Auto-filled">
                        </div>
                        <div>
                            <label class="cs-label">Account Name / Number</label>
                            <select name="account_id" id="b_account" class="cs-input" onchange="document.getElementById('b_bank').value = this.options[this.selectedIndex].getAttribute('data-bank') || '';">
                                <option value="">Select Account</option>
                                @foreach($bankAccounts as $acc)
                                    <option value="{{ $acc->id }}"
                                            data-bank="{{ $acc->bank_name ?: 'N/A' }}"
                                            data-branch="{{ $acc->branch_id }}">
                                        {{ $acc->name }} {{ $acc->account_number ? '('.$acc->account_number.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="cs-label">Address</label>
                            <input type="text" name="address" id="b_address" class="cs-input">
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="cs-modal-footer">
            <button onclick="closeBranchModal()" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-600 font-semibold rounded-[0.5rem] hover:bg-gray-50 transition-all text-sm">Cancel</button>
            <button onclick="document.getElementById('branchForm').submit()" class="px-7 py-2.5 btn-brand-gradient text-white font-bold rounded-[0.5rem] hover:opacity-90 transition-all text-sm flex items-center gap-2 shadow-sm">
                <i class="bi bi-check2-circle"></i>
                <span id="branchSubmitLabel">Save Branch</span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
// ---- Branch Modal ----
function openBranchModal(mode, data) {
    data = data || null;
    var isEdit = (mode === 'edit') && data;
    document.getElementById('branchModalTitle').textContent = isEdit ? 'Edit Branch' : 'Add New Branch';
    document.getElementById('branchSubmitLabel').textContent = isEdit ? 'Update Branch' : 'Save Branch';

    if (isEdit) {
        document.getElementById('branchForm').action = '{{ url("branch/update") }}/' + data.id;
        document.getElementById('branchMethod').value = 'PUT';
        document.getElementById('b_name').value = data.name || '';
        document.getElementById('b_code').value = data.code || '';
        document.getElementById('b_status').value = data.is_active ? '1' : '0';
        document.getElementById('b_mgr').value = data.manager_name || '';
        document.getElementById('b_phone').value = data.manager_phone || '';
        document.getElementById('b_district').value = data.district || '';
        document.getElementById('b_bank').value = data.bank_name || '';

        // Find account associated with this branch among the options
        let accSelect = document.getElementById('b_account');
        accSelect.value = ''; // default
        for (let i = 0; i < accSelect.options.length; i++) {
            if (accSelect.options[i].getAttribute('data-branch') == data.id) {
                accSelect.value = accSelect.options[i].value;
                break;
            }
        }
        // Trigger bank name update
        if(accSelect.selectedIndex > 0) {
            document.getElementById('b_bank').value = accSelect.options[accSelect.selectedIndex].getAttribute('data-bank') || '';
        }

        document.getElementById('b_address').value = data.address || '';
    } else {
        document.getElementById('branchForm').action = '{{ route("branch.store") }}';
        document.getElementById('branchMethod').value = 'POST';
        document.getElementById('branchForm').reset();
    }
    document.getElementById('branchModal').classList.add('open');
}
function closeBranchModal() {
    document.getElementById('branchModal').classList.remove('open');
}

// Close modal on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeBranchModal();
    }
});

function confirmDelete(formId, title, text) {
    const form = document.getElementById(formId);
    const label = (title || 'this record').replace(/^Delete\s+/i, '').replace(/\?$/, '');
    deleteRecordWithPassword(form.action, label, {
        title: title || 'Are you sure?',
        text: text || "You won't be able to revert this!"
    });
}
</script>
@endpush

@endsection
