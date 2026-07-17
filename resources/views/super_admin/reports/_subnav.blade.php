@php
$rNav = [
    ['route'=>'host.reports',               'label'=>'Overview',      'icon'=>'bi-grid'],
    ['route'=>'host.reports.companies',     'label'=>'Companies',     'icon'=>'bi-building'],
    ['route'=>'host.reports.subscriptions', 'label'=>'Subscriptions', 'icon'=>'bi-credit-card'],
    ['route'=>'host.reports.revenue',       'label'=>'Revenue',       'icon'=>'bi-currency-dollar'],
    ['route'=>'host.reports.users',         'label'=>'Users',         'icon'=>'bi-people'],
];
@endphp
<div class="d-flex flex-wrap gap-1 mb-4 p-1 rounded-3" style="background:rgba(0,65,97,.06);border:1px solid rgba(0,65,97,.1);">
    @foreach($rNav as $n)
    @php
        $isActive = request()->routeIs($n['route']) && !($n['route']==='host.reports' && request()->routeIs('host.reports.*'));
    @endphp
    <a href="{{ route($n['route']) }}"
       style="{{ $isActive ? 'background:#004161;color:#fff;' : 'color:#374151;' }} display:inline-flex;align-items:center;gap:.4rem;padding:.45rem .85rem;border-radius:7px;font-size:.8rem;font-weight:600;text-decoration:none;transition:all .15s;">
        <i class="bi {{ $n['icon'] }}" style="font-size:.85rem;"></i> {{ $n['label'] }}
    </a>
    @endforeach
</div>
