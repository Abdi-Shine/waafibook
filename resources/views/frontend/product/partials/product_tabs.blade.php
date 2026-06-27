@php
    $tabs = [
        'products' => ['label' => 'Products', 'url' => route('product.ledger')],
        'services' => ['label' => 'Services', 'url' => route('product.ledger', ['type' => 'service'])],
        'category' => ['label' => 'Category', 'url' => route('category.index')],
        'units'    => ['label' => 'Units', 'url' => route('units.index')],
    ];
@endphp

<div class="flex items-center gap-8 border-b border-gray-200 mb-6 overflow-x-auto custom-scrollbar">
    @foreach($tabs as $key => $tab)
        <a href="{{ $tab['url'] }}"
            class="pb-3 -mb-px text-[13px] font-black uppercase tracking-wider whitespace-nowrap border-b-2 transition-colors {{ $active === $key ? 'text-primary border-primary' : 'text-gray-400 border-transparent hover:text-primary-dark' }}">
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>
