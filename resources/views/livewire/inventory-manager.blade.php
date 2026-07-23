<div x-data="{ 
        activeTab: @entangle('activeTab'),
        toast: false,
        toastMsg: '',
        showToast(msg) {
            this.toastMsg = msg;
            this.toast = true;
            setTimeout(() => { this.toast = false; }, 3500);
        }
     }"
     x-init="$watch('$wire.toastMessage', val => { if(val) showToast(val); })"
     class="w-full max-w-7xl mx-auto space-y-4">

    <!-- Toast Notification Overlay -->
    <div x-show="toast" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-6 right-6 z-50 bg-[#242424] text-white px-4 py-3 rounded-[6px] shadow-lg border border-[#7fba00] flex items-center gap-3"
         style="display: none;">
        <span class="text-lg">✅</span>
        <span class="text-xs font-semibold" x-text="toastMsg"></span>
        <button @click="toast = false" class="text-gray-400 hover:text-white text-sm ml-2">&times;</button>
    </div>

    <!-- Header & Action Ribbon -->
    <div class="bg-white border border-[#e0e0e0] rounded-[6px] p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-[6px] bg-amber-50 border border-amber-200 flex items-center justify-center text-xl">
                📱
            </div>
            <div>
                <h1 class="text-xl font-extrabold text-[#242424] tracking-tight">Screen Protector Inventory</h1>
                <p class="text-xs text-[#5c5c5c] font-medium">Real-time stock checking, quick intake & procurement list</p>
            </div>
        </div>

        <!-- Alpine.js Instant Client-Side Tab Navigation -->
        <div class="inline-flex p-1 bg-[#f3f3f3] border border-[#e0e0e0] rounded-[6px] gap-1">
            <button @click="activeTab = 'search'" 
                    :class="activeTab === 'search' ? 'bg-white text-[#00a4ef] font-bold shadow-xs' : 'text-[#5c5c5c] hover:text-[#242424] font-medium'"
                    class="px-3.5 py-1.5 text-xs rounded-[4px] transition-colors flex items-center gap-1.5">
                <span>🔍 Live Inventory</span>
                <span class="px-1.5 py-0.2 bg-[#00a4ef]/10 text-[#00a4ef] text-[10px] rounded-full font-bold" x-text="$wire.stocks ? $wire.stocks.total : ''"></span>
            </button>
            
            <button @click="activeTab = 'add'" 
                    :class="activeTab === 'add' ? 'bg-white text-[#008272] font-bold shadow-xs' : 'text-[#5c5c5c] hover:text-[#242424] font-medium'"
                    class="px-3.5 py-1.5 text-xs rounded-[4px] transition-colors flex items-center gap-1.5">
                <span>⚡ Quick Intake</span>
            </button>

            <button @click="activeTab = 'reorder'" 
                    :class="activeTab === 'reorder' ? 'bg-white text-[#f25022] font-bold shadow-xs' : 'text-[#5c5c5c] hover:text-[#242424] font-medium'"
                    class="px-3.5 py-1.5 text-xs rounded-[4px] transition-colors flex items-center gap-1.5 relative">
                <span>📦 Reorder List</span>
                @if(count($reorderItems) > 0)
                    <span class="px-1.5 py-0.2 bg-[#f25022] text-white text-[10px] rounded-full font-bold">{{ count($reorderItems) }}</span>
                @endif
            </button>
        </div>
    </div>

    <!-- TAB 1: QUICK INTAKE / RESTOCK FORM -->
    <div x-show="activeTab === 'add'" x-cloak class="space-y-4">
        <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-xs p-6 max-w-3xl mx-auto space-y-5">
            <div class="border-b border-[#e0e0e0] pb-3 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-[#242424]">⚡ Fast Stock Intake & Variant Restock</h2>
                    <p class="text-xs text-[#5c5c5c]">Add new protector models or restock existing variant quantities seamlessly.</p>
                </div>
                <span class="text-xs px-2.5 py-1 bg-[#008272]/10 text-[#008272] font-bold rounded-[4px]">Auto-Upsert Enabled</span>
            </div>

            <form wire:submit.prevent="saveStock" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Brand -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Brand *</label>
                        <select wire:model="brand" class="w-full px-2.5 py-1.5 text-sm font-medium border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                            <option value="Apple">Apple</option>
                            <option value="Samsung">Samsung</option>
                            <option value="Xiaomi">Xiaomi</option>
                            <option value="Google">Google Pixel</option>
                            <option value="OnePlus">OnePlus</option>
                            <option value="Huawei">Huawei</option>
                            <option value="Motorola">Motorola</option>
                            <option value="Other">Other Brand</option>
                        </select>
                        @error('brand') <span class="text-xs text-[#f25022] mt-0.5 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Model Name -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Phone Model *</label>
                        <input type="text" wire:model="model" placeholder="e.g. iPhone 15 Pro Max, S24" class="w-full px-2.5 py-1.5 text-sm font-medium border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                        @error('model') <span class="text-xs text-[#f25022] mt-0.5 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Glass Variant -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Glass Protector Type *</label>
                        <select wire:model="glass_type" class="w-full px-2.5 py-1.5 text-sm font-medium border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                            @foreach($glassTypes as $gt)
                                <option value="{{ $gt }}">{{ $gt }}</option>
                            @endforeach
                        </select>
                        @error('glass_type') <span class="text-xs text-[#f25022] mt-0.5 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Stock Qty with Quick Buttons -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Intake Quantity *</label>
                        <div class="flex items-center gap-1.5">
                            <input type="number" min="1" wire:model="stock_qty" class="w-full px-2.5 py-1.5 text-sm font-bold border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                            <button type="button" @click="$wire.stock_qty = parseInt($wire.stock_qty || 0) + 5" class="px-2 py-1 bg-[#f3f3f3] hover:bg-[#e8e8e8] text-xs font-bold rounded border border-[#e0e0e0]">+5</button>
                            <button type="button" @click="$wire.stock_qty = parseInt($wire.stock_qty || 0) + 10" class="px-2 py-1 bg-[#f3f3f3] hover:bg-[#e8e8e8] text-xs font-bold rounded border border-[#e0e0e0]">+10</button>
                        </div>
                        @error('stock_qty') <span class="text-xs text-[#f25022] mt-0.5 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Min Reorder Threshold -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Min Reorder Alert Threshold</label>
                        <input type="number" min="0" wire:model="min_threshold" class="w-full px-2.5 py-1.5 text-sm font-medium border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                        @error('min_threshold') <span class="text-xs text-[#f25022] mt-0.5 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Bin Location -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Shelf Bin Location</label>
                        <input type="text" wire:model="bin_location" placeholder="e.g. A-12-B" class="w-full px-2.5 py-1.5 text-sm font-medium border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                        @error('bin_location') <span class="text-xs text-[#f25022] mt-0.5 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="pt-3 border-t border-[#e0e0e0] flex items-center justify-end gap-3">
                    <button type="button" @click="activeTab = 'search'" class="px-4 py-2 bg-white border border-[#e0e0e0] hover:bg-[#f3f3f3] text-[#242424] text-xs font-bold uppercase rounded-[4px]">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2 bg-[#008272] hover:bg-[#006e60] text-white text-xs font-bold uppercase tracking-wider rounded-[4px] transition-colors shadow-xs">
                        ⚡ Save Stock Intake
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- TAB 2: LIVE INVENTORY & SEARCH -->
    <div x-show="activeTab === 'search'" x-cloak class="space-y-4">
        <!-- Live Search Bar & Filters -->
        <div class="bg-white border border-[#e0e0e0] rounded-[6px] p-4 shadow-xs flex flex-col md:flex-row items-center gap-3">
            <!-- Search Keyword -->
            <div class="relative flex-1 w-full">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">🔍</span>
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Type Brand, Model (e.g. iPhone 15 Pro, S24) or Bin Location..." 
                       class="w-full pl-9 pr-3 py-2 text-sm font-medium border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                @if($search)
                    <button wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs text-gray-400 hover:text-gray-600">&times;</button>
                @endif
            </div>

            <!-- Filter Brand -->
            <div class="w-full md:w-48">
                <select wire:model.live="selectedBrand" class="w-full px-2.5 py-2 text-xs font-semibold border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                    <option value="">All Brands</option>
                    @foreach($availableBrands as $b)
                        <option value="{{ $b }}">{{ $b }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Glass Variant -->
            <div class="w-full md:w-56">
                <select wire:model.live="selectedGlassType" class="w-full px-2.5 py-2 text-xs font-semibold border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                    <option value="">All Glass Variants</option>
                    @foreach($glassTypes as $gt)
                        <option value="{{ $gt }}">{{ $gt }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Inventory Data Table -->
        <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#fafafa] border-b border-[#e0e0e0] text-xs font-bold uppercase tracking-wider text-[#5c5c5c]">
                            <th class="px-3 py-2">Brand & Model</th>
                            <th class="px-3 py-2">Glass Type Variant</th>
                            <th class="px-3 py-2 text-center">Bin Location</th>
                            <th class="px-3 py-2 text-center">Stock Level</th>
                            <th class="px-3 py-2 text-center">Status</th>
                            <th class="px-3 py-2 text-right">Inline Quantity Adjust</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e0e0e0]">
                        @forelse($stocks as $item)
                            <tr x-data="{ localQty: {{ $item->stock_qty }} }" 
                                class="hover:bg-[#f9f9f9] transition-colors {{ $item->isLowStock() ? 'bg-red-50/40' : '' }}">
                                
                                <!-- Brand & Model -->
                                <td class="px-3 py-2">
                                    <div class="font-bold text-sm text-[#242424]">{{ $item->brand }} {{ $item->model }}</div>
                                </td>

                                <!-- Variant -->
                                <td class="px-3 py-2">
                                    <span class="inline-block px-2.5 py-0.5 bg-[#f3f3f3] text-[#242424] font-semibold text-xs rounded-[4px] border border-[#e0e0e0]">
                                        {{ $item->glass_type }}
                                    </span>
                                </td>

                                <!-- Bin Location -->
                                <td class="px-3 py-2 text-center">
                                    <code class="text-xs font-mono font-bold text-[#5c5c5c] bg-gray-100 px-2 py-0.5 rounded">
                                        {{ $item->bin_location ?: 'N/A' }}
                                    </code>
                                </td>

                                <!-- Stock Level (Optimistic Alpine UI) -->
                                <td class="px-3 py-2 text-center">
                                    <span class="text-base font-extrabold" 
                                          :class="localQty <= {{ $item->min_threshold }} ? 'text-[#f25022]' : 'text-[#7fba00]'" 
                                          x-text="localQty"></span>
                                    <span class="text-[10px] text-gray-400 block">(min: {{ $item->min_threshold }})</span>
                                </td>

                                <!-- Status Badge -->
                                <td class="px-3 py-2 text-center">
                                    <template x-if="localQty === 0">
                                        <span class="px-2 py-0.5 bg-red-100 text-[#f25022] text-[10px] font-bold uppercase rounded-full">Out of Stock</span>
                                    </template>
                                    <template x-if="localQty > 0 && localQty <= {{ $item->min_threshold }}">
                                        <span class="px-2 py-0.5 bg-amber-100 text-amber-800 text-[10px] font-bold uppercase rounded-full">Low Stock</span>
                                    </template>
                                    <template x-if="localQty > {{ $item->min_threshold }}">
                                        <span class="px-2 py-0.5 bg-green-100 text-[#7fba00] text-[10px] font-bold uppercase rounded-full">In Stock</span>
                                    </template>
                                </td>

                                <!-- Inline Quick Adjust Buttons (-5, -1, +1, +5) -->
                                <td class="px-3 py-2 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <button @click="localQty = Math.max(0, localQty - 5); $wire.updateQty({{ $item->id }}, -5)" 
                                                class="px-2 py-1 bg-white border border-[#e0e0e0] hover:bg-red-50 hover:text-[#f25022] hover:border-red-200 text-xs font-bold rounded transition-colors"
                                                title="Deduct 5 Units">-5</button>

                                        <button @click="localQty = Math.max(0, localQty - 1); $wire.updateQty({{ $item->id }}, -1)" 
                                                class="px-2 py-1 bg-white border border-[#e0e0e0] hover:bg-red-50 hover:text-[#f25022] hover:border-red-200 text-xs font-bold rounded transition-colors"
                                                title="Deduct 1 Unit">-1</button>

                                        <button @click="localQty = localQty + 1; $wire.updateQty({{ $item->id }}, 1)" 
                                                class="px-2 py-1 bg-white border border-[#e0e0e0] hover:bg-green-50 hover:text-[#7fba00] hover:border-green-200 text-xs font-bold rounded transition-colors"
                                                title="Add 1 Unit">+1</button>

                                        <button @click="localQty = localQty + 5; $wire.updateQty({{ $item->id }}, 5)" 
                                                class="px-2 py-1 bg-white border border-[#e0e0e0] hover:bg-green-50 hover:text-[#7fba00] hover:border-green-200 text-xs font-bold rounded transition-colors"
                                                title="Add 5 Units">+5</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-10 text-[#5c5c5c]">
                                    <span class="text-3xl block mb-2">🔍</span>
                                    <p class="text-sm font-bold text-[#242424]">No screen protector stock matched your query.</p>
                                    <p class="text-xs text-gray-500">Try adjusting your brand/model search term or switch to the Quick Intake tab to add new inventory.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            <div class="p-3 border-t border-[#e0e0e0] bg-[#fafafa]">
                {{ $stocks->links() }}
            </div>
        </div>
    </div>

    <!-- TAB 3: REORDER PROCUREMENT LIST -->
    <div x-show="activeTab === 'reorder'" x-cloak class="space-y-4">
        <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-xs p-6 space-y-4">
            <div class="border-b border-[#e0e0e0] pb-3 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-[#f25022]">📦 Low Stock Procurement Reorder List</h2>
                    <p class="text-xs text-[#5c5c5c]">Items automatically queued where current stock level is below minimum threshold (stock_qty &le; min_threshold).</p>
                </div>
                <button onclick="window.print()" class="px-3 py-1.5 bg-white border border-[#e0e0e0] hover:bg-[#f3f3f3] text-xs font-bold rounded-[4px] flex items-center gap-1.5">
                    🖨️ Print Reorder Sheet
                </button>
            </div>

            @if(count($reorderItems) === 0)
                <div class="text-center py-12 bg-[#fafafa] border border-[#e0e0e0] rounded-[4px]">
                    <span class="text-4xl block mb-2">🎉</span>
                    <h3 class="text-sm font-bold text-[#7fba00] mb-1">All Stock Thresholds Satisfied!</h3>
                    <p class="text-xs text-[#5c5c5c]">No glass protectors are currently low on stock.</p>
                </div>
            @else
                <div class="overflow-x-auto border border-[#e0e0e0] rounded-[4px]">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[#fafafa] border-b border-[#e0e0e0] text-xs font-bold uppercase tracking-wider text-[#5c5c5c]">
                                <th class="px-3 py-2">Brand & Model</th>
                                <th class="px-3 py-2">Glass Type Variant</th>
                                <th class="px-3 py-2 text-center">Bin Location</th>
                                <th class="px-3 py-2 text-center">Current Stock</th>
                                <th class="px-3 py-2 text-center">Min Threshold</th>
                                <th class="px-3 py-2 text-center">Suggested Order Qty</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#e0e0e0]">
                            @foreach($reorderItems as $item)
                                <tr class="hover:bg-[#f9f9f9]">
                                    <td class="px-3 py-2 font-bold text-sm text-[#242424]">{{ $item->brand }} {{ $item->model }}</td>
                                    <td class="px-3 py-2">
                                        <span class="px-2 py-0.5 bg-gray-100 border border-gray-200 text-xs font-semibold rounded">
                                            {{ $item->glass_type }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-center font-mono text-xs font-bold text-[#5c5c5c]">
                                        {{ $item->bin_location ?: 'N/A' }}
                                    </td>
                                    <td class="px-3 py-2 text-center font-bold text-sm text-[#f25022]">
                                        {{ $item->stock_qty }}
                                    </td>
                                    <td class="px-3 py-2 text-center font-bold text-xs text-[#5c5c5c]">
                                        {{ $item->min_threshold }}
                                    </td>
                                    <td class="px-3 py-2 text-center font-extrabold text-sm text-[#008272]">
                                        +{{ max(10, ($item->min_threshold * 3) - $item->stock_qty) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
