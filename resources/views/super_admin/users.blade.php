@extends('super_admin.layouts.master')
@section('page_title', 'Users')
@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div>
        <h4 style="font-weight:800;color:#111827;margin:0;">Users Management</h4>
        <p style="color:#6b7280;font-size:.875rem;margin:.2rem 0 0;">Every user across every tenant company, in one place.</p>
    </div>
    <span class="sa-badge sa-badge-blue">{{ $users->total() }} total</span>
</div>

<div class="sa-card">
    <div class="p-3 border-bottom" style="background:#fafafa;">
        <form method="GET" class="d-flex gap-2 align-items-center m-0">
            <div class="position-relative flex-fill" style="min-width:160px;">
                <i class="bi bi-search position-absolute" style="left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:.85rem;"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control w-100" style="padding-left:34px;" placeholder="Search by name or email...">
            </div>
            <select name="company_id" class="form-select flex-fill" style="min-width:120px;" onchange="this.form.submit()">
                <option value="">All Companies</option>
                @foreach($companies as $c)
                    <option value="{{ $c->id }}" {{ request('company_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
            <select name="role" class="form-select flex-fill" style="min-width:110px;" onchange="this.form.submit()">
                <option value="">All Roles</option>
                @foreach(['admin','Manager','Cashier','Accountant'] as $r)
                    <option value="{{ $r }}" {{ request('role') === $r ? 'selected' : '' }}>{{ $r }}</option>
                @endforeach
            </select>
            <select name="status" class="form-select flex-fill" style="min-width:110px;" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
            </select>
            @if(request()->anyFilled(['search','company_id','role','status']))
                <a href="{{ route('host.users') }}" class="btn btn-outline-secondary">Clear</a>
            @endif
        </form>
    </div>
    <div class="table-responsive">
    <table class="sa-table">
        <thead>
            <tr>
                <th>User</th>
                <th>Company</th>
                <th>Role</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:34px;height:34px;font-size:.75rem;background:var(--accent);color:var(--primary-dark);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                        <div>
                            <div class="fw-semibold" style="font-size:.85rem;">{{ $user->name }}</div>
                            <div class="text-muted" style="font-size:.75rem;">{{ $user->email }}</div>
                        </div>
                    </div>
                </td>
                <td>{{ $user->company->name ?? '—' }}</td>
                <td>
                    @php
                        $roleColors = [
                            'admin'       => 'sa-badge-blue',
                            'Super Admin' => 'sa-badge-red',
                            'Manager'     => 'sa-badge-green',
                            'Cashier'     => 'sa-badge-yellow',
                        ];
                    @endphp
                    <span class="sa-badge {{ $roleColors[$user->role] ?? 'sa-badge-gray' }}">{{ $user->role }}</span>
                </td>
                <td>
                    @if($user->status === 'suspended')
                        <span class="sa-badge sa-badge-red"><i class="bi bi-slash-circle"></i> Suspended</span>
                    @else
                        <span class="sa-badge sa-badge-green"><i class="bi bi-check-circle"></i> Active</span>
                    @endif
                </td>
                <td>{{ $user->created_at->format('d M, Y') }}</td>
                <td>
                    <div class="sa-row-actions">
                        <form method="POST" action="{{ route('host.users.reset-password', $user->id) }}" class="d-inline"
                              onsubmit="return confirm('Send a password reset link to {{ addslashes($user->email) }}?');">
                            @csrf
                            <button type="submit" class="sa-btn-icon" data-bs-toggle="tooltip" title="Reset Password"><i class="bi bi-key"></i></button>
                        </form>
                        <form method="POST" action="{{ route('host.users.toggle-status', $user->id) }}" class="d-inline"
                              onsubmit="return confirm('{{ $user->status === 'suspended' ? 'Reactivate' : 'Suspend' }} {{ addslashes($user->name) }}?');">
                            @csrf
                            @method('PATCH')
                            @if($user->status === 'suspended')
                                <button type="submit" class="sa-btn-icon ok" data-bs-toggle="tooltip" title="Reactivate"><i class="bi bi-play-circle"></i></button>
                            @else
                                <button type="submit" class="sa-btn-icon warn" data-bs-toggle="tooltip" title="Suspend"><i class="bi bi-pause-circle"></i></button>
                            @endif
                        </form>
                        <form method="POST" action="{{ route('host.users.destroy', $user->id) }}" class="d-inline"
                              onsubmit="return confirm('Permanently delete {{ addslashes($user->name) }}? This cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="sa-btn-icon danger" data-bs-toggle="tooltip" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center py-5 text-muted">No users found</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

@if($users->hasPages())
<div class="mt-4 d-flex justify-content-center">{{ $users->links() }}</div>
@endif
@endsection
