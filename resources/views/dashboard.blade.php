<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-brand-text leading-tight">
            {{ __('Dashboard Overview') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <x-kpi-card 
                    label="Total Revenue" 
                    value="$45,231.89" 
                    trend="+12.5%" 
                    trendType="up" 
                />
                <x-kpi-card 
                    label="Active Projects" 
                    value="12" 
                    trend="+2" 
                    trendType="up" 
                />
                <x-kpi-card 
                    label="Outstanding Invoices" 
                    value="4" 
                    trend="-1" 
                    trendType="down" 
                />
            </div>

            <!-- Main Content Area -->
            <div class="bg-brand-surface overflow-hidden shadow-sm sm:rounded-xl border border-brand-border">
                <div class="p-8">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-brand-text">Recent Activity</h3>
                        <x-primary-button>
                            {{ __('View All Reports') }}
                        </x-primary-button>
                    </div>

                    <div class="text-gray-500 text-sm">
                        {{ __("You're logged in!") }} Welcome to the Waafibook Management System.
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
