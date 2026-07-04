@extends('admin.admin_master')
@section('page_title', isset($schedule) ? 'Edit Schedule' : 'New Schedule')
@section('admin')

@php $editMode = isset($schedule); @endphp

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('service-schedules.index') }}" class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-primary">
            <i class="bi bi-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-[18px] font-bold text-primary-dark">{{ $editMode ? 'Edit Schedule' : 'New Recurring Schedule' }}</h1>
            <p class="text-[12px] text-gray-400">Automatically generate service orders at the chosen interval</p>
        </div>
    </div>

    <form method="POST" action="{{ $editMode ? route('service-schedules.update', $schedule->id) : route('service-schedules.store') }}"
          x-data="{
            items: {{ $editMode ? json_encode($schedule->template_items ?? []) : '[]' }},
            addItem() { this.items.push({description:'',quantity:1,unit_price:0}); },
            removeItem(i) { this.items.splice(i,1); }
          }">
        @csrf
        @if($editMode) @method('PUT') @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-4">

                <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5">
                    <h2 class="text-[13px] font-black uppercase tracking-wider text-gray-500 mb-4">Schedule Details</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[11px] font-bold text-gray-600 uppercase tracking-wider block mb-1">Customer <span class="text-red-400">*</span></label>
                            <select name="customer_id" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
                                <option value="">Select Customer</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}" {{ ($editMode && $schedule->customer_id == $c->id) ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-gray-600 uppercase tracking-wider block mb-1">Schedule Title <span class="text-red-400">*</span></label>
                            <input type="text" name="title" value="{{ $editMode ? $schedule->title : old('title') }}" required
                                placeholder="e.g. Monthly AC Maintenance"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-gray-600 uppercase tracking-wider block mb-1">Frequency <span class="text-red-400">*</span></label>
                            <select name="frequency" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
                                @foreach(['daily'=>'Daily','weekly'=>'Weekly','monthly'=>'Monthly','quarterly'=>'Quarterly','yearly'=>'Yearly'] as $val => $label)
                                    <option value="{{ $val }}" {{ ($editMode && $schedule->frequency === $val) ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-gray-600 uppercase tracking-wider block mb-1">Status</label>
                            <select name="status" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
                                @foreach(['active','paused','ended'] as $s)
                                    <option value="{{ $s }}" {{ ($editMode && $schedule->status === $s) ? 'selected' : ($s === 'active' && !$editMode ? 'selected' : '') }}>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-gray-600 uppercase tracking-wider block mb-1">Start Date <span class="text-red-400">*</span></label>
                            <input type="date" name="start_date" required value="{{ $editMode ? $schedule->start_date->format('Y-m-d') : '' }}"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-gray-600 uppercase tracking-wider block mb-1">End Date</label>
                            <input type="date" name="end_date" value="{{ $editMode ? $schedule->end_date?->format('Y-m-d') : '' }}"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-gray-600 uppercase tracking-wider block mb-1">Next Due Date</label>
                            <input type="date" name="next_due_date" value="{{ $editMode ? $schedule->next_due_date?->format('Y-m-d') : '' }}"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
                        </div>
                        <div class="flex items-center gap-3 mt-2">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="auto_invoice" value="1" {{ ($editMode && $schedule->auto_invoice) ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-10 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-primary/20 rounded-full peer peer-checked:bg-accent peer-checked:after:translate-x-4 after:content-[''] after:absolute after:top-1 after:left-1 after:bg-white after:w-4 after:h-4 after:rounded-full after:transition-transform"></div>
                            </label>
                            <div>
                                <p class="text-[13px] font-bold text-gray-700">Auto-Invoice</p>
                                <p class="text-[11px] text-gray-400">Generate invoice automatically when order is created</p>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-[11px] font-bold text-gray-600 uppercase tracking-wider block mb-1">Notes</label>
                            <textarea name="notes" rows="2" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none resize-none">{{ $editMode ? $schedule->notes : '' }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-[13px] font-black uppercase tracking-wider text-gray-500">Template Items</h2>
                            <p class="text-[11px] text-gray-400 mt-0.5">These items will be used when auto-generating service orders</p>
                        </div>
                        <button type="button" @click="addItem()" class="flex items-center gap-1.5 px-3 py-1.5 bg-primary text-white rounded-lg text-[12px] font-bold">
                            <i class="bi bi-plus-lg"></i> Add Line
                        </button>
                    </div>
                    <table class="w-full text-[13px]">
                        <thead><tr class="border-b border-gray-100">
                            <th class="pb-2 text-left text-[10px] font-bold text-gray-400 uppercase">Description</th>
                            <th class="pb-2 text-center text-[10px] font-bold text-gray-400 uppercase w-20">Qty</th>
                            <th class="pb-2 text-center text-[10px] font-bold text-gray-400 uppercase w-28">Unit Price</th>
                            <th class="pb-2 w-8"></th>
                        </tr></thead>
                        <tbody>
                            <template x-for="(item,index) in items" :key="index">
                                <tr class="border-b border-gray-50">
                                    <td class="py-2 pr-2">
                                        <input type="text" :name="'template_items['+index+'][description]'" x-model="item.description" required
                                            class="w-full px-2 py-1.5 border border-gray-200 rounded text-[13px] focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none">
                                    </td>
                                    <td class="py-2 px-1">
                                        <input type="number" :name="'template_items['+index+'][quantity]'" x-model="item.quantity" min="0.01" step="0.01"
                                            class="w-full px-2 py-1.5 border border-gray-200 rounded text-[13px] text-center focus:ring-1 focus:ring-primary/20 outline-none">
                                    </td>
                                    <td class="py-2 px-1">
                                        <input type="number" :name="'template_items['+index+'][unit_price]'" x-model="item.unit_price" min="0" step="0.01"
                                            class="w-full px-2 py-1.5 border border-gray-200 rounded text-[13px] text-center focus:ring-1 focus:ring-primary/20 outline-none">
                                    </td>
                                    <td class="py-2 pl-1">
                                        <button type="button" @click="removeItem(index)" class="w-7 h-7 flex items-center justify-center rounded text-red-400 hover:bg-red-50">
                                            <i class="bi bi-trash3 text-xs"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="items.length===0">
                                <td colspan="4" class="py-6 text-center text-[12px] text-gray-400">Click "Add Line" to add template items</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

            <div class="space-y-4">
                <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5 flex flex-col gap-3">
                    <button type="submit" class="w-full py-3 bg-primary text-white rounded-xl font-bold text-[14px]">
                        {{ $editMode ? 'Update Schedule' : 'Save Schedule' }}
                    </button>
                    <a href="{{ route('service-schedules.index') }}" class="w-full py-3 bg-accent text-primary rounded-xl font-bold text-[14px] text-center">Cancel</a>
                </div>

                <div class="bg-blue-50 border border-blue-100 rounded-[1rem] p-4">
                    <h3 class="text-[12px] font-black text-blue-700 mb-2"><i class="bi bi-info-circle me-1"></i> How It Works</h3>
                    <ul class="text-[11px] text-blue-600 space-y-1.5 list-disc list-inside">
                        <li>Scheduler runs daily at 06:00</li>
                        <li>When next due date is reached, a service order is auto-created from template items</li>
                        <li>Next due date advances by the frequency</li>
                        <li>Enable Auto-Invoice to immediately invoice the generated order</li>
                    </ul>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
