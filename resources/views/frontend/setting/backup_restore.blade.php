@extends('admin.admin_master')
@section('page_title', 'Backup & Restore')

@section('admin')
<meta name="csrf-token" content="{{ csrf_token() }}">



<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen font-inter" x-data="backupManager">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center text-primary text-xl shadow-sm border border-primary/5">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h1 class="text-[22px] font-bold text-primary-dark tracking-tight">System Backup & Security</h1>
        </div>
        <div class="flex items-center gap-3">
            <button @click="createBackup()" class="flex items-center gap-2 px-5 py-2.5 bg-primary text-white font-semibold rounded-[0.5rem] hover:bg-primary/90 transition-all shadow-sm text-sm group">
                <i class="bi bi-cloud-arrow-up group-hover:-translate-y-1 transition-transform duration-300"></i>
                Immediate Backup
            </button>
        </div>
    </div>

    <!-- Alert Banner -->
    <div class="mb-6 bg-primary/10 border border-primary/20 rounded-[1rem] p-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-8 h-8 bg-primary/10 text-white rounded-full flex items-center justify-center text-sm shadow-sm ring-4 ring-blue-50">
                <i class="bi bi-info"></i>
            </div>
            <div class="flex flex-col">
                <span class="text-xs font-bold text-primary-dark">Dynamic Snapshot Protection Active</span>
                <span class="text-[10px] text-slate-500 font-medium italic">Last backup verified {{ $backups->first() ? \Carbon\Carbon::parse($backups->first()->created_at)->diffForHumans() : 'No recent entries' }}</span>
            </div>
        </div>
        <div class="hidden md:flex items-center gap-6">
            <div class="flex flex-col items-end">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Next Scheduled Window</span>
                <span class="text-[11px] font-bold text-primary-dark" x-text="'Daily at ' + formatTime(switches.backup_time)"></span>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total Backups -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[13px] font-medium text-gray-400 mb-1">Archived States</p>
                <h3 class="text-[24px] font-black text-primary">{{ number_format($stats['total_backups'] ?? 0) }}</h3>
                <p class="text-[12px] font-bold text-primary mt-1.5 flex items-center gap-1">Available Restores</p>
            </div>
            <div class="w-11 h-11 bg-slate-200 rounded-[0.6rem] flex items-center justify-center text-primary-dark">
                <i class="bi bi-hdd-stack text-lg"></i>
            </div>
        </div>
        
        <!-- Success Rate -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[13px] font-medium text-gray-400 mb-1">Success Rate</p>
                <h3 class="text-[24px] font-black text-primary">{{ $stats['success_rate'] ?? '100%' }}</h3>
                <p class="text-[12px] font-bold text-primary mt-1.5 flex items-center gap-1"><i class="bi bi-check2-circle text-[10px]"></i> Operational</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary">
                <i class="bi bi-shield-check text-lg"></i>
            </div>
        </div>
        
        <!-- Data Size -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[13px] font-medium text-gray-400 mb-1">Vault Volume</p>
                <h3 class="text-[24px] font-black text-primary">{{ $dbSize }} MB</h3>
                <p class="text-[12px] font-bold text-primary mt-1.5">Database Integrity</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary">
                <i class="bi bi-database-fill text-lg"></i>
            </div>
        </div>
        
        <!-- Retention -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[13px] font-medium text-gray-400 mb-1">Retention Policy</p>
                <h3 class="text-[24px] font-black text-primary">{{ $settings['backup_retention'] ?? '30' }} Days</h3>
                <p class="text-[12px] font-bold text-primary mt-1.5 flex items-center gap-1">Auto-Cleanup Active</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary">
                <i class="bi bi-clock-history text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Management Tools & Settings -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10">
        <!-- History Table -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-[1rem] border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-white">
                    <div class="flex items-center gap-2">
                        <i class="bi bi-clock-history text-primary-dark text-sm"></i>
                        <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Historical Snapshot Ledger</h2>
                    </div>
                    <button class="text-[11px] font-bold text-accent hover:underline uppercase tracking-widest">View Archives</button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full whitespace-nowrap text-left text-[13px]">
                        <thead>
                            <tr class="bg-background/50 border-b border-gray-100">
                                <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Date & Time</th>
                                <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Type</th>
                                <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Volume</th>
                                <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Backup Status</th>
                                <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Restore Status</th>
                                <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($backups->take(5) as $backup)
                            <tr class="hover:bg-gray-50/50 transition-colors bg-white group">
                                <td class="px-5 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-primary-dark leading-tight">{{ \Carbon\Carbon::parse($backup->created_at)->format('d M, Y') }}</span>
                                        <span class="text-[11px] text-gray-500 font-bold leading-tight">{{ \Carbon\Carbon::parse($backup->created_at)->format('h:i A') }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    @if($backup->type == 'auto')
                                        <span class="px-2 py-0.5 bg-accent/10 text-accent rounded text-[10px] font-bold uppercase tracking-tighter">
                                            <i class="bi bi-robot text-[8px] mr-1"></i>Automatic
                                        </span>
                                    @elseif($backup->type == 'gmail')
                                        <span class="px-2 py-0.5 bg-primary/10 text-primary rounded text-[10px] font-bold uppercase tracking-tighter">
                                            <i class="bi bi-envelope-at text-[8px] mr-1"></i>Gmail
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-[10px] font-bold uppercase tracking-tighter">
                                            <i class="bi bi-hand-index-thumb text-[8px] mr-1"></i>Manual
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-xs font-bold text-primary-dark leading-tight italic">
                                        {{ $backup->size ? number_format($backup->size / 1024 / 1024, 2) . ' MB' : '—' }}
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-xs font-bold text-primary-dark leading-tight">
                                        Verified
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-xs font-bold text-primary-dark leading-tight">
                                        @if($backup->restore_status == 'restored')
                                            Restored ({{ \Carbon\Carbon::parse($backup->restored_at)->format('d M') }})
                                        @elseif($backup->restore_status == 'failed')
                                            Failed
                                        @else
                                            —
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click="restoreBackup('{{ $backup->id }}')" class="w-7 h-7 rounded-md bg-white border border-gray-200 text-gray-400 hover:text-primary hover:border-primary transition-all flex items-center justify-center shadow-sm" title="Restore">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                        <a href="{{ route('backup.download', $backup->id) }}" class="w-7 h-7 rounded-md bg-white border border-gray-200 text-gray-400 hover:text-primary hover:border-primary/20 transition-all flex items-center justify-center shadow-sm" title="Download">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center">
                                    <div class="flex flex-col items-center gap-2 opacity-30 grayscale">
                                        <i class="bi bi-database-exclamation text-3xl"></i>
                                        <p class="text-[11px] font-black uppercase tracking-widest">No archival record discovered</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-[1rem] border border-gray-200 shadow-sm p-6 overflow-hidden relative group">
                <div class="flex items-start justify-between relative z-10">
                    <div class="flex flex-col gap-1">
                        <h4 class="text-sm font-bold text-primary-dark tracking-tight">Cloud Synchronizer</h4>
                        <p class="text-[11px] text-slate-500 leading-relaxed font-medium">Automatic multi-point data mirroring to off-site secure vaults.</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex -space-x-2">
                            <div class="w-8 h-8 rounded-full border-2 border-white bg-primary/10 flex items-center justify-center text-primary text-xs shadow-sm">
                                <i class="bi bi-google"></i>
                            </div>
                        </div>
                        <button @click="backupToGmail()" class="px-4 py-1.5 bg-accent text-primary-dark text-[10px] font-black uppercase tracking-widest rounded-lg shadow-sm hover:opacity-90 transition-all">Backup Now</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Settings -->
        <div class="space-y-6">
            <div class="bg-primary-dark rounded-[1.25rem] p-6 text-white shadow-xl relative overflow-hidden">
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/5 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-accent/5 rounded-full blur-3xl"></div>
                
                <div class="flex items-center justify-between mb-4 relative z-10">
                    <h4 class="text-sm font-bold flex items-center gap-2">
                        <i class="bi bi-gear-fill text-accent"></i>
                        Control Panel
                    </h4>
                    <div x-show="isSaving" x-cloak class="flex items-center gap-1.5 px-2 py-0.5 bg-accent/10 border border-accent/20 rounded-full">
                        <i class="bi bi-arrow-repeat animate-spin text-[10px] text-accent"></i>
                        <span class="text-[8px] font-black text-accent uppercase tracking-widest">Syncing</span>
                    </div>
                </div>

                <div class="space-y-1 relative z-10">
                    <div class="flex items-center justify-between p-3 rounded-xl hover:bg-white/5 transition-colors group">
                        <div class="flex flex-col">
                            <span class="text-[11px] font-bold text-white tracking-wide">Automated Protocol</span>
                            <span class="text-[9px] text-white/50 italic" x-text="switches.automated ? 'Daily at ' + formatTime(switches.backup_time) : 'Protocol Disabled'"></span>
                        </div>
                        <div @click="switches.automated = !switches.automated; commitChanges()" class="w-8 h-4 rounded-full relative flex items-center px-0.5 cursor-pointer transition-all duration-300" :class="switches.automated ? 'bg-accent' : 'bg-white/20'">
                            <div class="w-3 h-3 bg-primary-dark rounded-full transition-transform duration-300 shadow-sm" :class="switches.automated ? 'translate-x-4' : 'translate-x-0'"></div>
                        </div>
                    </div>

                    <!-- System Time Indicator -->
                    <div class="px-3 py-1 bg-white/5 rounded-lg flex items-center justify-between mx-3 mt-1">
                        <span class="text-[8px] font-black text-white/30 uppercase">Internal System Clock</span>
                        <span class="text-[9px] font-bold text-accent" x-text="currentTime"></span>
                    </div>

                    <div class="p-3 bg-white/5 rounded-xl space-y-3" x-show="switches.automated" x-transition>
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] uppercase font-bold text-white/70">Execution Time</span>
                            <input type="time" x-model="switches.backup_time" @change="commitChanges()" class="bg-primary-dark border border-white/20 rounded-md text-[10px] px-2 py-1 focus:outline-none focus:border-accent text-white">
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] uppercase font-bold text-white/70">Retention (Days)</span>
                            <input type="number" x-model="switches.retention" @change="commitChanges()" class="w-16 bg-primary-dark border border-white/20 rounded-md text-[10px] px-2 py-1 focus:outline-none focus:border-accent text-white" min="1">
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-xl hover:bg-white/5 transition-colors">
                        <div class="flex flex-col">
                            <span class="text-[11px] font-bold text-white tracking-wide">Data Compression</span>
                            <span class="text-[9px] text-white/50 italic">Optimized Storage</span>
                        </div>
                        <div @click="switches.compression = !switches.compression; commitChanges()" class="w-8 h-4 rounded-full relative flex items-center px-0.5 cursor-pointer transition-all duration-300" :class="switches.compression ? 'bg-accent' : 'bg-white/20'">
                            <div class="w-3 h-3 bg-primary-dark rounded-full transition-transform duration-300 shadow-sm" :class="switches.compression ? 'translate-x-4' : 'translate-x-0'"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-xl hover:bg-white/5 transition-colors">
                        <div class="flex flex-col">
                            <span class="text-[11px] font-bold text-white tracking-wide">High Encryption</span>
                            <span class="text-[9px] text-white/50 italic">AES-256 Grade</span>
                        </div>
                        <div @click="switches.encryption = !switches.encryption; commitChanges()" class="w-8 h-4 rounded-full relative flex items-center px-0.5 cursor-pointer transition-all duration-300" :class="switches.encryption ? 'bg-accent' : 'bg-white/20'">
                            <div class="w-3 h-3 bg-primary-dark rounded-full transition-transform duration-300 shadow-sm" :class="switches.encryption ? 'translate-x-4' : 'translate-x-0'"></div>
                        </div>
                    </div>
                </div>

        </div>
    </div>

    <!-- Backup Progress Overlay (Alpine) -->
    <div x-show="isBackingUp" x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/40 backdrop-blur-md">
        <div class="w-full max-w-md bg-white rounded-[1.50rem] p-8 shadow-2xl flex flex-col items-center gap-6 animate-in zoom-in duration-300">
            <div class="relative">
                <svg class="w-32 h-32 transform -rotate-90">
                    <circle cx="64" cy="64" r="56" class="text-slate-100 stroke-current" stroke-width="8" fill="transparent" />
                    <circle cx="64" cy="64" r="56" class="text-primary stroke-current transition-all duration-300" 
                        stroke-width="8" fill="transparent"
                        :stroke-dasharray="351.8"
                        :stroke-dashoffset="351.8 - (351.8 * backupProgress / 100)"
                        stroke-linecap="round" />
                </svg>
                <div class="absolute inset-0 flex items-center justify-center flex-col">
                    <span class="text-2xl font-black text-primary-dark" x-text="backupProgress + '%'"></span>
                    <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Syncing</span>
                </div>
            </div>
            <div class="flex flex-col items-center gap-1">
                <h3 class="text-lg font-bold text-primary-dark">System Snapshot in Progress</h3>
                <p class="text-[11px] text-slate-500 font-medium italic">Encrypting vault data elements...</p>
            </div>
            <div class="w-full flex items-center gap-2 p-3 bg-slate-50 rounded-xl border border-slate-100 items-center justify-center">
                <div class="flex gap-1">
                    <template x-for="i in 3">
                        <div class="w-1 h-1 bg-primary rounded-full animate-bounce" :style="'animation-delay: ' + (i*100) + 'ms'"></div>
                    </template>
                </div>
                <span class="text-[10px] font-bold text-primary uppercase tracking-widest">Active Link</span>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('backupManager', () => ({
        activeModal: null,
        backupProgress: 0,
        isBackingUp: false,
        isSaving: false,
        serverDate: new Date('{{ $settings["server_time"] }}'.replace(/-/g, '/')),
        currentTime: '',
        switches: {
            automated: {{ $settings['auto_backup_enabled'] ? 'true' : 'false' }},
            compression: true,
            encryption: true,
            retention: {{ $settings['backup_retention'] }},
            backup_time: '{{ $settings['backup_time'] }}'
        },

        init() {
            this.updateClock();
            setInterval(() => {
                this.serverDate.setSeconds(this.serverDate.getSeconds() + 1);
                this.updateClock();
            }, 1000);

            // Check every 60 seconds if a scheduled backup should run
            this.checkScheduledBackup();
            setInterval(() => this.checkScheduledBackup(), 60000);
        },

        updateClock() {
            let hours = this.serverDate.getHours();
            let minutes = this.serverDate.getMinutes();
            let seconds = this.serverDate.getSeconds();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12;
            
            hours = hours < 10 ? '0' + hours : hours;
            minutes = minutes < 10 ? '0' + minutes : minutes;
            seconds = seconds < 10 ? '0' + seconds : seconds;
            
            this.currentTime = hours + ':' + minutes + ':' + seconds + ' ' + ampm;
        },

        formatTime(timeStr) {
            if (!timeStr) return '--:--';
            const [hours, minutes] = timeStr.split(':');
            const h = parseInt(hours);
            const ampm = h >= 12 ? 'PM' : 'AM';
            const h12 = h % 12 || 12;
            return `${h12}:${minutes} ${ampm}`;
        },

        commitChanges() {
            this.isSaving = true;
            console.log('Synchronizing Security Protocols...', this.switches);

            fetch('{{ route('backup.settings.update') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    automated: this.switches.automated,
                    retention: this.switches.retention,
                    backup_time: this.switches.backup_time
                })
            })
            .then(async response => {
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Transmission Interrupted');
                return data;
            })
            .then(data => {
                if (data.success) {
                    // Standard success notification replaced by subtle 'Syncing' indicator
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Security Protocol Error:', error);
                Swal.fire({
                    title: 'Sync Failed',
                    text: error.message,
                    icon: 'error',
                    confirmButtonColor: '#004161'
                });
            })
            .finally(() => {
                this.isSaving = false;
            });
        },

        createBackup() {
            Swal.fire({
                title: 'Confirm System Backup?',
                text: 'This will generate a full snapshot of your database and localized unit data.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#004161',
                cancelButtonColor: '#99CC33',
                confirmButtonText: 'Start Backup',
                customClass: {
                    popup: 'rounded-[1.25rem]',
                    confirmButton: 'rounded-lg px-6 py-2.5 text-xs font-bold uppercase tracking-widest'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    this.isBackingUp = true;
                    this.backupProgress = 10;
                    
                    fetch('{{ route('backup.create') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.backupProgress = 100;
                            setTimeout(() => {
                                this.isBackingUp = false;
                                this.backupProgress = 0;
                                Swal.fire({
                                    title: 'Backup Successful',
                                    text: data.message,
                                    icon: 'success',
                                    confirmButtonColor: '#004161'
                                }).then(() => location.reload());
                            }, 500);
                        } else {
                            throw new Error(data.message);
                        }
                    })
                    .catch(error => {
                        this.isBackingUp = false;
                        this.backupProgress = 0;
                        Swal.fire({
                            title: 'Backup Failed',
                            text: error.message,
                            icon: 'error',
                            confirmButtonColor: '#004161'
                        });
                    });
                }
            });
        },

        restoreBackup(id) {
            Swal.fire({
                title: 'Confirm System Restore?',
                text: 'WARNING: This will overwrite your current database. This action is irreversible!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#99CC33',
                confirmButtonText: 'Yes, Restore System',
                customClass: {
                    popup: 'rounded-[1.25rem]'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Restoring System...',
                        html: 'Please do not close this window.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                            fetch(`{{ url('/backup/restore') }}/${id}`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                                    'Accept': 'application/json'
                                }
                            })
                            .then(async response => {
                                const data = await response.json();
                                return data;
                            })
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: 'Restored!',
                                        text: data.message,
                                        icon: 'success',
                                        confirmButtonColor: '#004161'
                                    }).then(() => location.reload());
                                } else {
                                    Swal.fire({
                                        title: 'Restore Failed',
                                        text: data.message,
                                        icon: 'error',
                                        confirmButtonColor: '#dc2626'
                                    }).then(() => location.reload()); // Reload anyway to update status badge
                                }
                            })
                            .catch(error => {
                                Swal.fire({
                                    title: 'Error!',
                                    text: error.message,
                                    icon: 'error',
                                    confirmButtonColor: '#dc2626'
                                }).then(() => location.reload());
                            });
                        }
                    });
                }
            });
        },

        deleteBackup(id) {
            Swal.fire({
                title: 'Delete Backup?',
                text: 'This file will be permanently removed from disk.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#99CC33',
                confirmButtonText: 'Delete Now'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/backup/delete/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Deleted!', data.message, 'success').then(() => location.reload());
                        }
                    });
                }
            });
        },

        checkScheduledBackup() {
            if (!this.switches.automated) return;
            fetch('{{ route('backup.scheduled.trigger') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ automated: true, backup_time: this.switches.backup_time })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) location.reload();
            })
            .catch(() => {});
        },

        backupToGmail() {
            Swal.fire({
                title: 'Gmail Security Vault',
                text: 'Enter the destination email for the encrypted system snapshot:',
                input: 'email',
                inputPlaceholder: 'admin@example.com',
                showCancelButton: true,
                confirmButtonColor: '#004161',
                confirmButtonText: 'Encrypt & Send',
                customClass: {
                    popup: 'rounded-[1.25rem]'
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    this.isBackingUp = true;
                    this.backupProgress = 20;

                    fetch("{{ route('backup.gmail') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ email: result.value })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.backupProgress = 100;
                            setTimeout(() => {
                                this.isBackingUp = false;
                                this.backupProgress = 0;
                                Swal.fire('Sent!', data.message, 'success').then(() => location.reload());
                            }, 500);
                        } else {
                            throw new Error(data.message);
                        }
                    })
                    .catch(error => {
                        this.isBackingUp = false;
                        this.backupProgress = 0;
                        Swal.fire('Failed!', error.message, 'error');
                    });
                }
            });
        }
    }));
});
</script>
@endpush


