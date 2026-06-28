@extends('super_admin.layouts.master')
@section('page_title', 'Companies')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 style="font-weight:800;color:#111827;margin:0;">Companies</h4>
            <p style="color:#6b7280;font-size:.875rem;margin:.2rem 0 0;">
                {{ $companies->total() }} registered {{ Str::plural('tenant', $companies->total()) }} on the platform
            </p>
        </div>
    </div>

    <div class="sa-card">
        <div class="table-responsive">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Company</th>
                        <th>Plan</th>
                        <th>Subscription</th>
                        <th>Expiry</th>
                        <th>Country</th>
                        <th>Joined</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($companies as $company)
                    <tr>
                        <td style="color:#9ca3af;font-size:.78rem;">{{ $companies->firstItem() + $loop->index }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if($company->logo)
                                    <img src="{{ asset($company->logo) }}" style="width:34px;height:34px;border-radius:8px;object-fit:contain;background:#f9fafb;padding:3px;">
                                @else
                                    <div style="width:34px;height:34px;border-radius:8px;background:#dbeafe;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.72rem;color:#1d4ed8;flex-shrink:0;">
                                        {{ strtoupper(substr($company->name, 0, 2)) }}
                                    </div>
                                @endif
                                <div>
                                    <div style="font-weight:600;font-size:.85rem;color:#111827;">{{ $company->name }}</div>
                                    @if($company->email)
                                    <div style="font-size:.72rem;color:#9ca3af;">{{ $company->email }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($company->subscription?->plan)
                                <span class="sa-badge sa-badge-blue">{{ $company->subscription->plan->name }}</span>
                            @else
                                <span class="sa-badge sa-badge-gray">—</span>
                            @endif
                        </td>
                        <td>
                            @php $sub = $company->subscription; @endphp
                            @if(!$sub)
                                <span class="sa-badge sa-badge-gray">None</span>
                            @elseif($sub->status === 'active')
                                <span class="sa-badge sa-badge-green"><i class="bi bi-check-circle"></i> Active</span>
                            @elseif($sub->status === 'expired')
                                <span class="sa-badge sa-badge-red"><i class="bi bi-x-circle"></i> Expired</span>
                            @else
                                <span class="sa-badge sa-badge-yellow">{{ ucfirst($sub->status) }}</span>
                            @endif
                        </td>
                        <td style="font-size:.8rem;color:#6b7280;">
                            @if($company->subscription?->expiry_date)
                                @php
                                    $expiry   = \Carbon\Carbon::parse($company->subscription->expiry_date);
                                    $warning  = $expiry->isFuture() && $expiry->diffInDays(now()) <= 7;
                                @endphp
                                <span style="{{ $warning ? 'color:#d97706;font-weight:600;' : '' }}">
                                    {{ $expiry->format('d M Y') }}
                                    @if($warning) <i class="bi bi-exclamation-circle"></i>@endif
                                </span>
                            @else
                                —
                            @endif
                        </td>
                        <td style="font-size:.82rem;">{{ $company->country ?? '—' }}</td>
                        <td style="font-size:.8rem;color:#6b7280;">{{ $company->created_at->format('d M Y') }}</td>
                        <td>
                            @if($company->status === 'suspended')
                                <span class="sa-badge sa-badge-red"><i class="bi bi-slash-circle"></i> Suspended</span>
                            @else
                                <span class="sa-badge sa-badge-green"><i class="bi bi-check-circle"></i> Active</span>
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('host.companies.toggle-status', $company->id) }}"
                                  onsubmit="return confirm('{{ $company->status === 'suspended' ? 'Reactivate' : 'Suspend' }} {{ $company->name }}?');">
                                @csrf
                                @method('PATCH')
                                @if($company->status === 'suspended')
                                    <button type="submit" class="btn btn-sm" style="background:#dcfce7;color:#15803d;border:none;font-weight:600;font-size:.78rem;padding:.3rem .7rem;border-radius:6px;">Activate</button>
                                @else
                                    <button type="submit" class="btn btn-sm" style="background:#fee2e2;color:#b91c1c;border:none;font-weight:600;font-size:.78rem;padding:.3rem .7rem;border-radius:6px;">Suspend</button>
                                @endif
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5" style="color:#9ca3af;">No companies registered yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($companies->hasPages())
        <div class="px-4 py-3 border-top" style="background:#fafafa;">
            {{ $companies->links() }}
        </div>
        @endif
    </div>

@endsection
