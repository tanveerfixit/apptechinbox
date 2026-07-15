<div class="container-fluid px-2 px-sm-3" 
     x-data="{ 
         flavorInput: @entangle('flavorInput'),
         flavors: @js($flavors),
         showSuggestions: false,
         get filteredFlavors() {
             if (!this.flavorInput) return [];
             const val = this.flavorInput.toLowerCase().trim();
             return this.flavors.filter(f => f.toLowerCase().includes(val));
         }
     }">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 fw-bold text-dark mb-0">Vape Order Builder</h1>
        <a href="/" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 rounded-1" style="font-size: 13px;">
            &larr; Back to Portal
        </a>
    </div>

    <!-- Toast Notifications -->
    @if ($successMessage)
        <div class="alert alert-success py-2 px-3 small mb-3 border-0 shadow-sm" style="background-color: #d1e7dd; color: #0f5132; border-radius: 4px;" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)">
            {{ $successMessage }}
        </div>
    @endif
    @if ($errorMessage)
        <div class="alert alert-danger py-2 px-3 small mb-3 border-0 shadow-sm" style="background-color: #f8d7da; color: #842029; border-radius: 4px;" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)">
            {{ $errorMessage }}
        </div>
    @endif

    <div class="row g-4">
        
        <!-- Left Column: Add Item & Database Management -->
        <div class="col-12 col-lg-6">
            
            <!-- Add Item Form -->
            <div class="card shadow-sm border-1 p-4 mb-4 bg-white" style="border-radius: 6px; border-color: var(--card-border);">
                <h2 class="small fw-bold text-uppercase text-muted mb-3" style="letter-spacing: 0.5px; font-size: 11px; color: var(--text-secondary) !important;">Add Item</h2>
                
                <!-- Category selection container -->
                <div class="mb-3">
                    <label class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px; color: var(--text-secondary) !important;">Category</label>
                    <div class="d-flex flex-wrap gap-2 py-1">
                        @foreach ($categories as $cat)
                            <button 
                                type="button" 
                                class="btn btn-sm px-3 py-2 fw-medium rounded-1 {{ $selectedCategoryId === $cat->id ? 'text-white' : 'btn-light border text-secondary' }}"
                                style="{{ $selectedCategoryId === $cat->id ? 'background-color: var(--brand-red); border-color: var(--brand-red);' : '' }}"
                                wire:click="selectCategory({{ $cat->id }})"
                            >
                                {{ $cat->name }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Brand & Line Selectors -->
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label for="brand" class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px; color: var(--text-secondary) !important;">Brand</label>
                        <select id="brand" class="form-select rounded-1" wire:model.live="selectedBrand" style="font-size: 14px;">
                            @foreach ($brands as $b)
                                <option value="{{ $b }}">{{ $b }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label for="line" class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px; color: var(--text-secondary) !important;">Model / Line</label>
                        <select id="line" class="form-select rounded-1" wire:model="selectedLine" style="font-size: 14px;">
                            @foreach ($lines as $l)
                                <option value="{{ $l }}">{{ $l ?: '(Standard)' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Flavor & Quantity inputs -->
                <div class="row g-3 mb-4">
                    <div class="col-7 position-relative">
                        <label for="flavour" class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px; color: var(--text-secondary) !important;">Flavor</label>
                        <input 
                            id="flavour" 
                            class="form-control rounded-1" 
                            placeholder="Flavor..." 
                            autocomplete="off"
                            x-model="flavorInput"
                            x-on:focus="showSuggestions = true"
                            x-on:click.away="showSuggestions = false"
                            style="font-size: 14px;"
                        >
                        <!-- Suggestions Dropdown Box -->
                        <div class="position-absolute top-100 start-0 end-0 bg-white border rounded shadow-sm overflow-auto" 
                             style="max-height: 200px; z-index: 1000;" 
                             x-show="showSuggestions && filteredFlavors.length > 0" 
                             x-cloak>
                            <template x-for="flavor in filteredFlavors" :key="flavor">
                                <div class="px-3 py-2 small cursor-pointer hover-bg-light"
                                     style="cursor: pointer;"
                                     x-on:mousedown="flavorInput = flavor; showSuggestions = false;">
                                    <span x-text="flavor"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                    
                    <div class="col-5">
                        <label class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px; color: var(--text-secondary) !important;">Qty</label>
                        <div class="input-group">
                            <button class="btn btn-outline-secondary py-1" type="button" wire:click="adjustQty(-1)">-</button>
                            <input id="qty" class="form-control text-center fw-bold bg-white text-dark py-1" type="number" readonly value="{{ $quantity }}" style="font-size: 14px;">
                            <button class="btn btn-outline-secondary py-1" type="button" wire:click="adjustQty(1)">+</button>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn text-white w-100 py-2 text-uppercase fw-bold rounded-1" style="background-color: var(--brand-red); font-size: 13px; letter-spacing: 0.5px;" wire:click="addItem">
                    Add to Order
                </button>
            </div>

            <!-- Database Management (Collapsible Admin Panel) -->
            <div class="card shadow-sm border-1 p-3 mb-4 bg-white" style="border-radius: 6px; border-color: var(--card-border);">
                <div wire:click="toggleAdminPanel" class="d-flex justify-content-between align-items-center" style="cursor: pointer; user-select: none;">
                    <h2 class="small fw-bold text-muted text-uppercase mb-0" style="letter-spacing: 0.5px; font-size: 11px; color: var(--text-secondary) !important;">⚙️ Database Management</h2>
                    <span class="small text-muted fw-bold" style="font-size: 10px;">{{ $adminExpanded ? '- COLLAPSE' : '+ EXPAND' }}</span>
                </div>

                <div class="mt-3 pt-3 border-top" style="display: {{ $adminExpanded ? 'block' : 'none' }};">
                    <!-- Add Category -->
                    <div class="mb-4">
                        <h3 class="small fw-bold text-dark text-uppercase mb-2" style="font-size: 10px; letter-spacing: 0.5px;">Add New Category</h3>
                        <div class="d-flex gap-2">
                            <input class="form-control form-control-sm rounded-1" placeholder="e.g. Nicotine Pouches..." wire:model="newCategoryName">
                            <button wire:click="addCategory" class="btn btn-sm btn-primary px-3 fw-bold rounded-1" style="background-color: var(--brand-blue); border-color: var(--brand-blue);">Add</button>
                        </div>
                    </div>

                    <!-- Add Product -->
                    <div>
                        <h3 class="small fw-bold text-dark text-uppercase mb-2" style="font-size: 10px; letter-spacing: 0.5px;">Add New Product</h3>
                        <div class="mb-2">
                            <label class="small text-muted text-uppercase mb-1" style="font-size: 9px;">Under Category</label>
                            <select class="form-select form-select-sm rounded-1" wire:model="newProductCategoryId">
                                <option value="">Select category...</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="small text-muted text-uppercase mb-1" style="font-size: 9px;">Brand</label>
                                <input class="form-control form-control-sm rounded-1" placeholder="e.g. Lost Mary" wire:model="newProductBrand">
                            </div>
                            <div class="col-6">
                                <label class="small text-muted text-uppercase mb-1" style="font-size: 9px;">Model / Line</label>
                                <input class="form-control form-control-sm rounded-1" placeholder="e.g. BM6000" wire:model="newProductLine">
                            </div>
                        </div>
                        <button wire:click="addProduct" class="btn btn-sm btn-primary w-100 fw-bold rounded-1" style="background-color: var(--brand-blue); border-color: var(--brand-blue);">Add Product</button>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Column: Current Items & Order Summary -->
        <div class="col-12 col-lg-6">

            <!-- Current Items Table -->
            <div class="card shadow-sm border-1 p-0 mb-4 overflow-hidden bg-white" style="border-radius: 6px; border-color: var(--card-border);">
                <div class="d-flex justify-content-between align-items-center py-2 px-3 border-bottom" style="background-color: #fafafa;">
                    <h2 class="small fw-bold text-muted text-uppercase mb-0" style="letter-spacing: 0.5px; font-size: 11px; color: var(--text-secondary) !important;">Current Items</h2>
                    <span class="badge bg-secondary text-uppercase fw-semibold" style="font-size: 10px; padding: 4px 8px;">{{ count($orderItems) }} {{ count($orderItems) === 1 ? 'Item' : 'Items' }}</span>
                </div>

                <div class="table-responsive" style="max-height: 310px;">
                    <table class="table table-hover table-striped align-middle mb-0" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px; padding: 12px 8px;">#</th>
                                <th style="padding: 12px 8px;">Product</th>
                                <th style="padding: 12px 8px;">Flavor</th>
                                <th class="text-end" style="width: 70px; padding: 12px 8px;">Qty</th>
                                <th class="text-center" style="width: 80px; padding: 12px 8px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($orderItems as $idx => $item)
                                <tr>
                                    <td class="text-muted" style="padding: 10px 8px;">{{ $idx + 1 }}</td>
                                    <td style="padding: 10px 8px;"><strong>{{ $item->brand }}{{ $item->line ? ' (' . $item->line . ')' : '' }}</strong></td>
                                    <td style="padding: 10px 8px;">{{ $item->flavour }}</td>
                                    <td class="text-end fw-bold" style="padding: 10px 8px;">{{ $item->quantity }}</td>
                                    <td class="text-center" style="padding: 10px 8px;">
                                        <button class="btn btn-sm btn-outline-danger py-1 px-2 rounded-1" wire:click="removeItem({{ $item->id }})" style="font-size: 11px;">Remove</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted fst-italic py-4">
                                        No items added yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Order Summary Output Preview -->
            <div class="card shadow-sm border-1 p-4 mb-4 bg-white" style="border-radius: 6px; border-color: var(--card-border);">
                <h2 class="small fw-bold text-muted text-uppercase mb-3" style="letter-spacing: 0.5px; font-size: 11px; color: var(--text-secondary) !important;">Order Summary</h2>
                
                <textarea 
                    id="orderOutput" 
                    class="form-control text-monospace bg-light mb-3 rounded-1" 
                    rows="10" 
                    readonly 
                    placeholder="WhatsApp text preview..." 
                    style="font-family: monospace; font-size: 12px; border: 1px solid var(--card-border);"
                >{{ $orderSummary }}</textarea>
                
                <div class="d-flex gap-2" x-data="{
                    copyTextToClipboard() {
                        const textarea = document.getElementById('orderOutput');
                        if (!textarea.value) {
                            alert('Add items to your order first.');
                            return;
                        }
                        navigator.clipboard.writeText(textarea.value).then(() => {
                            alert('Order copied to clipboard!');
                        });
                    }
                }">
                    <button 
                        x-on:click="copyTextToClipboard(); $wire.submitOrder();" 
                        class="btn btn-primary flex-grow-1 fw-bold rounded-1" 
                        style="font-size: 13px; background-color: var(--brand-red); border-color: var(--brand-red);"
                        @if (empty($orderItems)) disabled @endif
                    >
                        Copy Order
                    </button>
                    <button 
                        wire:confirm="Are you sure you want to clear this active order?"
                        wire:click="clearOrder" 
                        class="btn btn-outline-danger rounded-1" 
                        style="font-size: 13px;"
                        @if (empty($orderItems)) disabled @endif
                    >
                        Clear
                    </button>
                    <a href="/past-orders" class="btn btn-outline-secondary rounded-1" style="font-size: 13px;">
                        History
                    </a>
                </div>
            </div>

        </div>

    </div>

    <!-- Style additions for suggestion items styling -->
    <style>
        .hover-bg-light:hover {
            background-color: #f3f3f3;
        }
    </style>
</div>
