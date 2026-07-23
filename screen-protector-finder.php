<?php
// screen-protector-finder.php
session_start();
require_once __DIR__ . '/db.php';

// Enforce login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$tenantDbConnected = ($db !== null);
$tenantDbName = $_SESSION['tenant_db_name'] ?? 'tenant_db';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Phone Screen Protector Finder - TechInbox</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/public/icons/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/svg+xml" href="/public/icons/icon.svg">
    <link rel="shortcut icon" href="/public/icons/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/public/icons/apple-touch-icon.png">
    <link rel="manifest" href="/public/icons/site.webmanifest">
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="flex flex-col min-h-screen bg-[#f3f3f3] text-[#242424] font-sans antialiased">

    <!-- Header Navigation -->
    <?php require_once __DIR__ . '/header.php'; ?>

    <script>
    function screenProtectorApp() {
        return {
            activeTab: 'search',
            search: '',
            selectedBrand: '',
            selectedGlassType: '',
            stocks: [],
            reorderItems: [],
            availableBrands: [],
            availableModels: [],
            modelBrandMap: {},
            modelDimensionMap: {},
            glassTypes: ['Loose Glasses', 'Aokus Thin 3D Touch', 'Aokus Cover Edge 9D', 'Aokus Loose', 'Aokus 9H', 'Ven-Dens 9H'],
            loading: false,
            toast: false,
            toastMsg: '',
            
            // Intake Form Data
            brand: 'Apple',
            model: '',
            glass_type: 'Aokus Cover Edge 9D',
            screen_size_inch: '',
            dimensions_mm: '',
            stock_qty: 10,
            min_threshold: 3,
            bin_location: '',
            isSaving: false,

            parseGsmArenaText(raw) {
                if (!raw) return;
                const str = raw.trim();

                // 1. Extract mm (Height x Width)
                const mmMatch = str.match(/(\d+(?:\.\d+)?)\s*x\s*(\d+(?:\.\d+)?)(?:\s*x\s*\d+(?:\.\d+)?)?\s*mm/i);
                if (mmMatch) {
                    this.dimensions_mm = mmMatch[1] + ' x ' + mmMatch[2] + ' mm';
                }

                // 2. Extract inches if present in brackets or standalone
                const inchMatch = str.match(/\((?:.*?(\d+(?:\.\d+)?)\s*x\s*(\d+(?:\.\d+)?).*?in|.*?(\d+(?:\.\d+)?)\s*"\s*)\)/i) || str.match(/(\d+(?:\.\d+)?)\s*"/);
                if (inchMatch) {
                    const size = inchMatch[1] || inchMatch[3];
                    if (size) {
                        this.screen_size_inch = size + '"';
                    }
                }
            },

            detectBrand() {
                const m = (this.model || '').trim();
                if (!m) return;

                // 1. Auto-fill Brand
                if (this.modelBrandMap[m]) {
                    this.brand = this.modelBrandMap[m];
                } else {
                    const foundKey = Object.keys(this.modelBrandMap).find(k => k.toLowerCase() === m.toLowerCase());
                    if (foundKey) {
                        this.brand = this.modelBrandMap[foundKey];
                    } else {
                        const mLower = m.toLowerCase();
                        if (mLower.startsWith('iphone') || mLower.startsWith('17') || mLower.startsWith('16') || mLower.startsWith('15') || mLower.startsWith('14') || mLower.startsWith('13') || mLower.startsWith('12') || mLower.startsWith('11') || mLower.includes('xs') || mLower.includes('xr') || mLower.includes('se')) {
                            this.brand = 'Apple';
                        } else if (mLower.startsWith('s2') || mLower.startsWith('s1') || mLower.startsWith('z fold') || mLower.startsWith('z flip') || mLower.startsWith('note') || mLower.startsWith('a1') || mLower.startsWith('a3') || mLower.startsWith('a5') || mLower.startsWith('a7') || mLower.startsWith('galaxy')) {
                            this.brand = 'Samsung';
                        } else if (mLower.includes('redmi') || mLower.includes('xiaomi')) {
                            this.brand = 'Xiaomi';
                        } else if (mLower.includes('pixel')) {
                            this.brand = 'Google';
                        }
                    }
                }

                // 2. Auto-fill Dimensions (Inches & mm)
                if (this.modelDimensionMap[m]) {
                    this.screen_size_inch = this.modelDimensionMap[m].screen_size_inch || '';
                    this.dimensions_mm = this.modelDimensionMap[m].dimensions_mm || '';
                } else {
                    const foundKey = Object.keys(this.modelDimensionMap).find(k => k.toLowerCase() === m.toLowerCase());
                    if (foundKey && this.modelDimensionMap[foundKey]) {
                        this.screen_size_inch = this.modelDimensionMap[foundKey].screen_size_inch || '';
                        this.dimensions_mm = this.modelDimensionMap[foundKey].dimensions_mm || '';
                    }
                }
            },

            showToast(msg) {
                this.toastMsg = msg;
                this.toast = true;
                setTimeout(() => { this.toast = false; }, 3500);
            },

            async fetchStocks() {
                this.loading = true;
                try {
                    const url = 'api.php?action=get_protector_stocks&search=' + encodeURIComponent(this.search) + '&brand=' + encodeURIComponent(this.selectedBrand) + '&glass_type=' + encodeURIComponent(this.selectedGlassType);
                    const res = await fetch(url);
                    const data = await res.json();
                    if (data.status === 'success') {
                        this.stocks = data.data;
                        this.reorderItems = data.reorder;
                        this.availableBrands = data.brands;
                        if (data.models) {
                            this.availableModels = data.models;
                        }
                        if (data.modelBrandMap) {
                            this.modelBrandMap = data.modelBrandMap;
                        }
                        if (data.modelDimensionMap) {
                            this.modelDimensionMap = data.modelDimensionMap;
                        }
                    }
                } catch(e) {
                } finally {
                    this.loading = false;
                }
            },

            async saveStock() {
                if (!this.brand || !this.model || !this.glass_type) {
                    alert('Please fill in Brand, Model, and Glass Type.');
                    return;
                }
                this.isSaving = true;
                try {
                    const res = await fetch('api.php?action=save_protector_stock', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            brand: this.brand,
                            model: this.model,
                            glass_type: this.glass_type,
                            screen_size_inch: this.screen_size_inch,
                            dimensions_mm: this.dimensions_mm,
                            stock_qty: parseInt(this.stock_qty || 0),
                            min_threshold: parseInt(this.min_threshold || 3),
                            bin_location: this.bin_location
                        })
                    });
                    const data = await res.json();
                    if (data.status === 'success') {
                        this.showToast(data.message);
                        this.model = '';
                        this.bin_location = '';
                        this.stock_qty = 10;
                        this.fetchStocks();
                        this.activeTab = 'search';
                    } else {
                        alert(data.message || 'Error saving stock.');
                    }
                } catch(e) {
                    alert('Failed to save stock.');
                } finally {
                    this.isSaving = false;
                }
            },

            async updateQty(id, change) {
                const item = this.stocks.find(i => i.id == id);
                if (item) {
                    item.stock_qty = Math.max(0, parseInt(item.stock_qty) + change);
                }
                try {
                    await fetch('api.php?action=update_protector_stock_qty', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: id, change: change })
                    });
                } catch(e) {}
            },

            init() {
                this.fetchStocks();
            }
        };
    }
    </script>

    <!-- Main Container -->
    <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex-1" x-data="screenProtectorApp()">
        
        <?php require __DIR__ . '/nav_buttons.php'; ?>

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
        <div class="bg-white border border-[#e0e0e0] rounded-[6px] p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-xs mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-[6px] bg-amber-50 border border-amber-200 flex items-center justify-center text-xl">
                    📱
                </div>
                <div>
                    <h1 class="text-xl font-extrabold text-[#242424] tracking-tight">Phone Screen Protector Inventory</h1>
                    <p class="text-xs text-[#5c5c5c] font-medium">Real-time stock checking, quick intake & procurement list</p>
                </div>
            </div>

            <!-- Alpine.js Instant Client-Side Tab Navigation -->
            <div class="inline-flex p-1 bg-[#f3f3f3] border border-[#e0e0e0] rounded-[6px] gap-1">
                <button @click="activeTab = 'search'" 
                        :class="activeTab === 'search' ? 'bg-white text-[#00a4ef] font-bold shadow-xs' : 'text-[#5c5c5c] hover:text-[#242424] font-medium'"
                        class="px-3.5 py-1.5 text-xs rounded-[4px] transition-colors flex items-center gap-1.5">
                    <span>🔍 Live Inventory</span>
                    <span class="px-1.5 py-0.2 bg-[#00a4ef]/10 text-[#00a4ef] text-[10px] rounded-full font-bold" x-text="stocks.length"></span>
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
                    <template x-if="reorderItems.length > 0">
                        <span class="px-1.5 py-0.2 bg-[#f25022] text-white text-[10px] rounded-full font-bold" x-text="reorderItems.length"></span>
                    </template>
                </button>
            </div>
        </div>

        <!-- TAB 1: QUICK INTAKE / RESTOCK FORM -->
        <div x-show="activeTab === 'add'" x-cloak class="space-y-4" style="display: none;">
            <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-xs p-6 max-w-3xl mx-auto space-y-5">
                <div class="border-b border-[#e0e0e0] pb-3 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-[#242424]">⚡ Fast Stock Intake & Variant Restock</h2>
                        <p class="text-xs text-[#5c5c5c]">Add new protector models or restock existing variant quantities seamlessly.</p>
                    </div>
                    <span class="text-xs px-2.5 py-1 bg-[#008272]/10 text-[#008272] font-bold rounded-[4px]">Auto-Upsert Enabled</span>
                </div>

                <form @submit.prevent="saveStock" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Brand -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Brand *</label>
                            <select x-model="brand" class="w-full px-2.5 py-1.5 text-sm font-medium border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                                <option value="Apple">Apple</option>
                                <option value="Samsung">Samsung</option>
                                <option value="Xiaomi">Xiaomi</option>
                                <option value="Google">Google Pixel</option>
                                <option value="OnePlus">OnePlus</option>
                                <option value="Huawei">Huawei</option>
                                <option value="Motorola">Motorola</option>
                                <option value="Other">Other Brand</option>
                            </select>
                        </div>

                        <!-- Model Name with Typed Autocomplete Suggestions Popup -->
                        <div class="relative" x-data="{ openSuggestions: false }" @click.outside="openSuggestions = false">
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Phone Model *</label>
                            <input type="text" 
                                   x-model="model" 
                                   @input="openSuggestions = true; detectBrand()"
                                   @focus="if(model.trim().length > 0) openSuggestions = true"
                                   placeholder="e.g. iPhone 15 Pro Max, S24" 
                                   required 
                                   class="w-full px-2.5 py-1.5 text-sm font-medium border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                            
                            <!-- Custom Popup suggestions list (only when typing > 0 chars) -->
                            <div x-show="openSuggestions && model.trim().length > 0" 
                                 x-cloak
                                 class="absolute z-20 left-0 right-0 top-full mt-1 bg-white border border-[#e0e0e0] rounded-[4px] shadow-lg max-h-48 overflow-y-auto divide-y divide-[#f3f3f3]">
                                <template x-for="m in availableModels.filter(item => item.toLowerCase().includes(model.toLowerCase()))" :key="m">
                                    <div @click="model = m; openSuggestions = false; detectBrand()" 
                                         class="px-3 py-2 text-xs font-medium text-[#242424] hover:bg-[#f3f3f3] hover:text-[#00a4ef] cursor-pointer transition-colors"
                                         x-text="m"></div>
                                </template>
                            </div>
                        </div>

                        <!-- Glass Variant -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Glass Protector Type *</label>
                            <select x-model="glass_type" class="w-full px-2.5 py-1.5 text-sm font-medium border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                                <template x-for="gt in glassTypes" :key="gt">
                                    <option :value="gt" x-text="gt"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <!-- GSMArena Quick Paste & Auto-Parse Box -->
                    <div class="bg-[#f3f3f3] border border-[#e0e0e0] rounded-[6px] p-3 space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-[#242424] flex items-center gap-1.5">
                                <span>📋</span>
                                <span>Paste GSMArena Specs Text (Auto-Parses mm & Inches)</span>
                            </label>
                            <span class="text-[10px] text-[#00a4ef] font-semibold">e.g. 163 x 77.6 x 8.3 mm (6.42 x 3.06 x 0.33 in)</span>
                        </div>
                        <input type="text" 
                               @input="parseGsmArenaText($event.target.value)" 
                               @paste="setTimeout(() => parseGsmArenaText($event.target.value), 50)" 
                               placeholder="Paste GSMArena specs line here... (e.g. 163 x 77.6 x 8.3 mm (6.42 x 3.06 x 0.33 in))" 
                               class="w-full px-2.5 py-1.5 text-xs font-mono border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <!-- Stock Qty with Quick Buttons -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Intake Quantity *</label>
                            <div class="flex items-center gap-1.5">
                                <input type="number" min="1" x-model.number="stock_qty" class="w-full px-2.5 py-1.5 text-sm font-bold border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                                <button type="button" @click="stock_qty = parseInt(stock_qty || 0) + 5" class="px-2 py-1 bg-[#f3f3f3] hover:bg-[#e8e8e8] text-xs font-bold rounded border border-[#e0e0e0]">+5</button>
                                <button type="button" @click="stock_qty = parseInt(stock_qty || 0) + 10" class="px-2 py-1 bg-[#f3f3f3] hover:bg-[#e8e8e8] text-xs font-bold rounded border border-[#e0e0e0]">+10</button>
                            </div>
                        </div>

                        <!-- Screen Size (Inches) -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Display Size (Inch)</label>
                            <input type="text" x-model="screen_size_inch" placeholder="e.g. 6.42&quot;" class="w-full px-2.5 py-1.5 text-sm font-medium border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                        </div>

                        <!-- Physical Dimensions (mm) -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Dimensions (mm)</label>
                            <input type="text" x-model="dimensions_mm" placeholder="e.g. 163 x 77.6 mm" class="w-full px-2.5 py-1.5 text-sm font-medium border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                        </div>
                    </div>

                    <div class="pt-3 border-t border-[#e0e0e0] flex items-center justify-end gap-3">
                        <button type="button" @click="activeTab = 'search'" class="px-4 py-2 bg-white border border-[#e0e0e0] hover:bg-[#f3f3f3] text-[#242424] text-xs font-bold uppercase rounded-[4px]">
                            Cancel
                        </button>
                        <button type="submit" class="px-6 py-2 bg-[#008272] hover:bg-[#006e60] text-white text-xs font-bold uppercase tracking-wider rounded-[4px] transition-colors shadow-xs" :disabled="isSaving">
                            <span x-show="!isSaving">⚡ Save Stock Intake</span>
                            <span x-show="isSaving" class="animate-spin text-xs">🌀</span>
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
                           x-model="search" 
                           @input.debounce.300ms="fetchStocks()" 
                           placeholder="Type Brand or Model (e.g. iPhone 15 Pro, S24)..." 
                           class="w-full pl-9 pr-3 py-2 text-sm font-medium border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                    <template x-if="search">
                        <button @click="search = ''; fetchStocks()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs text-gray-400 hover:text-gray-600">&times;</button>
                    </template>
                </div>

                <!-- Filter Brand -->
                <div class="w-full md:w-48">
                    <select x-model="selectedBrand" @change="fetchStocks()" class="w-full px-2.5 py-2 text-xs font-semibold border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                        <option value="">All Brands</option>
                        <template x-for="b in availableBrands" :key="b">
                            <option :value="b" x-text="b"></option>
                        </template>
                    </select>
                </div>

                <!-- Filter Glass Variant -->
                <div class="w-full md:w-56">
                    <select x-model="selectedGlassType" @change="fetchStocks()" class="w-full px-2.5 py-2 text-xs font-semibold border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                        <option value="">All Glass Variants</option>
                        <template x-for="gt in glassTypes" :key="gt">
                            <option :value="gt" x-text="gt"></option>
                        </template>
                    </select>
                </div>
            </div>

            <!-- Inventory Data Table -->
            <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-xs overflow-hidden">
                <div x-show="loading" class="text-center py-12">
                    <span class="animate-spin text-xl text-[#00a4ef] block mb-2">🌀</span>
                    <p class="text-xs text-[#5c5c5c]">Loading inventory...</p>
                </div>

                <div x-show="!loading && stocks.length === 0" class="text-center py-10 text-[#5c5c5c]">
                    <span class="text-3xl block mb-2">🔍</span>
                    <p class="text-sm font-bold text-[#242424]">No in-stock screen protectors matched your query.</p>
                    <p class="text-xs text-gray-500">Only items with available stock (> 0) are listed here. Use Quick Intake to add stock.</p>
                </div>

                <div x-show="!loading && stocks.length > 0" class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[#fafafa] border-b border-[#e0e0e0] text-xs font-bold uppercase tracking-wider text-[#5c5c5c]">
                                <th class="px-3 py-2">Brand & Model</th>
                                <th class="px-3 py-2">Glass Type Variant</th>
                                <th class="px-3 py-2 text-center">Stock Level</th>
                                <th class="px-3 py-2 text-center">Status</th>
                                <th class="px-3 py-2 text-right">Inline Quantity Adjust</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#e0e0e0]">
                            <template x-for="item in stocks" :key="item.id">
                                <tr class="hover:bg-[#f9f9f9] transition-colors" :class="parseInt(item.stock_qty) <= parseInt(item.min_threshold) ? 'bg-red-50/40' : ''">
                                    <!-- Brand & Model with Dimensions -->
                                    <td class="px-3 py-2">
                                        <div class="font-bold text-sm text-[#242424]" x-text="item.brand + ' ' + item.model"></div>
                                        <template x-if="item.screen_size_inch || item.dimensions_mm">
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <template x-if="item.screen_size_inch">
                                                    <span class="px-1.5 py-0.2 bg-blue-50 text-[#00a4ef] border border-blue-200 text-[10px] font-bold rounded" x-text="item.screen_size_inch"></span>
                                                </template>
                                                <template x-if="item.dimensions_mm">
                                                    <span class="px-1.5 py-0.2 bg-gray-100 text-[#5c5c5c] border border-gray-200 text-[10px] font-mono font-medium rounded" x-text="item.dimensions_mm"></span>
                                                </template>
                                            </div>
                                        </template>
                                    </td>

                                    <!-- Variant -->
                                    <td class="px-3 py-2">
                                        <span class="inline-block px-2.5 py-0.5 bg-[#f3f3f3] text-[#242424] font-semibold text-xs rounded-[4px] border border-[#e0e0e0]" x-text="item.glass_type"></span>
                                    </td>

                                    <!-- Stock Level -->
                                    <td class="px-3 py-2 text-center">
                                        <span class="text-base font-extrabold" 
                                              :class="parseInt(item.stock_qty) <= parseInt(item.min_threshold) ? 'text-[#f25022]' : 'text-[#7fba00]'" 
                                              x-text="item.stock_qty"></span>
                                        <span class="text-[10px] text-gray-400 block" x-text="'(min: ' + item.min_threshold + ')'"></span>
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="px-3 py-2 text-center">
                                        <template x-if="parseInt(item.stock_qty) === 0">
                                            <span class="px-2 py-0.5 bg-red-100 text-[#f25022] text-[10px] font-bold uppercase rounded-full">Out of Stock</span>
                                        </template>
                                        <template x-if="parseInt(item.stock_qty) > 0 && parseInt(item.stock_qty) <= parseInt(item.min_threshold)">
                                            <span class="px-2 py-0.5 bg-amber-100 text-amber-800 text-[10px] font-bold uppercase rounded-full">Low Stock</span>
                                        </template>
                                        <template x-if="parseInt(item.stock_qty) > parseInt(item.min_threshold)">
                                            <span class="px-2 py-0.5 bg-green-100 text-[#7fba00] text-[10px] font-bold uppercase rounded-full">In Stock</span>
                                        </template>
                                    </td>

                                    <!-- Inline Quick Adjust Buttons (-5, -1, +1, +5) -->
                                    <td class="px-3 py-2 text-right">
                                        <div class="inline-flex items-center gap-1">
                                            <button @click="updateQty(item.id, -5)" 
                                                    class="px-2 py-1 bg-white border border-[#e0e0e0] hover:bg-red-50 hover:text-[#f25022] hover:border-red-200 text-xs font-bold rounded transition-colors"
                                                    title="Deduct 5 Units">-5</button>

                                            <button @click="updateQty(item.id, -1)" 
                                                    class="px-2 py-1 bg-white border border-[#e0e0e0] hover:bg-red-50 hover:text-[#f25022] hover:border-red-200 text-xs font-bold rounded transition-colors"
                                                    title="Deduct 1 Unit">-1</button>

                                            <button @click="updateQty(item.id, 1)" 
                                                    class="px-2 py-1 bg-white border border-[#e0e0e0] hover:bg-green-50 hover:text-[#7fba00] hover:border-green-200 text-xs font-bold rounded transition-colors"
                                                    title="Add 1 Unit">+1</button>

                                            <button @click="updateQty(item.id, 5)" 
                                                    class="px-2 py-1 bg-white border border-[#e0e0e0] hover:bg-green-50 hover:text-[#7fba00] hover:border-green-200 text-xs font-bold rounded transition-colors"
                                                    title="Add 5 Units">+5</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 3: REORDER PROCUREMENT LIST -->
        <div x-show="activeTab === 'reorder'" x-cloak class="space-y-4" style="display: none;">
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

                <template x-if="reorderItems.length === 0">
                    <div class="text-center py-12 bg-[#fafafa] border border-[#e0e0e0] rounded-[4px]">
                        <span class="text-4xl block mb-2">🎉</span>
                        <h3 class="text-sm font-bold text-[#7fba00] mb-1">All Stock Thresholds Satisfied!</h3>
                        <p class="text-xs text-[#5c5c5c]">No glass protectors are currently low on stock.</p>
                    </div>
                </template>

                <template x-if="reorderItems.length > 0">
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
                                <template x-for="item in reorderItems" :key="item.id">
                                    <tr class="hover:bg-[#f9f9f9]">
                                        <td class="px-3 py-2 font-bold text-sm text-[#242424]" x-text="item.brand + ' ' + item.model"></td>
                                        <td class="px-3 py-2">
                                            <span class="px-2 py-0.5 bg-gray-100 border border-gray-200 text-xs font-semibold rounded" x-text="item.glass_type"></span>
                                        </td>
                                        <td class="px-3 py-2 text-center font-mono text-xs font-bold text-[#5c5c5c]" x-text="item.bin_location || 'N/A'"></td>
                                        <td class="px-3 py-2 text-center font-bold text-sm text-[#f25022]" x-text="item.stock_qty"></td>
                                        <td class="px-3 py-2 text-center font-bold text-xs text-[#5c5c5c]" x-text="item.min_threshold"></td>
                                        <td class="px-3 py-2 text-center font-extrabold text-sm text-[#008272]" x-text="'+' + Math.max(10, (parseInt(item.min_threshold) * 3) - parseInt(item.stock_qty))"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>
        </div>

    </main>

    <!-- Standard Footer -->
    <?php require_once __DIR__ . '/footer.php'; ?>

</body>
</html>
