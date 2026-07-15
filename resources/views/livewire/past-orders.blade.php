<div class="container-fluid px-2 px-sm-3" style="max-width: 600px; margin: 0 auto;"
     x-data="{
         showReceived: false,
         hasPending: {{ $totalPendingItems > 0 ? 'true' : 'false' }},
         reorderSingle(item) {
             const stored = localStorage.getItem('vape_order_items');
             let orderItems = [];
             if (stored) {
                 try {
                     orderItems = JSON.parse(stored);
                 } catch (e) {
                     orderItems = [];
                 }
             }

             const tempId = 'temp_' + Date.now() + '_' + Math.floor(Math.random() * 1000000);
             orderItems.push({
                 id: tempId,
                 product_id: item.product_id,
                 brand: item.brand,
                 line: item.line,
                 flavor: item.flavor,
                 quantity: item.quantity || 1,
                 category: ''
             });

             localStorage.setItem('vape_order_items', JSON.stringify(orderItems));
             alert('Added ' + item.brand + ' to builder!');
             window.location.href = 'vape.php';
         }
     }">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 fw-bold text-dark mb-0">Past Orders</h1>
        <div class="d-flex align-items-center gap-2">
            @if ($totalReceivedItems > 0)
                <button x-on:click="showReceived = !showReceived" class="btn btn-sm btn-light border rounded-1 p-2 d-flex align-items-center justify-content-center" :title="showReceived ? 'Hide received products' : 'Show received products'">
                    <span x-text="showReceived ? '👁️‍🗨️' : '👁️'"></span>
                </button>
            @endif
            <a href="vape.php" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 rounded-1" style="font-size: 13px;">
                &larr; Builder
            </a>
        </div>
    </div>

    @if ($successMessage)
        <div class="alert alert-success py-2 px-3 small text-center mb-3 border-0 shadow-sm" style="background-color: #d1e7dd; color: #0f5132; border-radius: 4px;" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 2500)">
            {{ $successMessage }}
        </div>
    @endif

    <!-- POPULAR ITEMS / QUICK REORDER -->
    @if (!empty($popularItems))
        <div class="card shadow-sm border-1 p-3 mb-3 bg-white" style="border-radius: 6px; border-left: 4px solid var(--brand-blue) !important; border-color: var(--card-border);">
            <h3 class="small fw-bold text-uppercase text-muted mb-3" style="letter-spacing: 0.5px; font-size: 11px; color: var(--brand-blue) !important;">
                🔥 Frequently Ordered
            </h3>
            <div class="overflow-auto" style="max-height: 250px;">
                <table class="table table-sm align-middle mb-0" style="font-size: 12.5px;">
                    <tbody>
                        @foreach ($popularItems as $pItem)
                            <tr style="border-bottom: 1px solid var(--card-border);">
                                <td style="padding: 8px 4px; width: 45%;">
                                    <div class="fw-bold text-dark">{{ $pItem->brand }}</div>
                                    <div class="small text-muted" style="font-size: 10px;">{{ $pItem->line }}</div>
                                </td>
                                <td style="padding: 8px 4px; color: var(--text-primary); width: 35%;">
                                    {{ $pItem->flavor }}
                                </td>
                                <td style="padding: 8px 4px; text-align: right; width: 20%;">
                                    <button x-on:click="reorderSingle(@js($pItem))" class="btn btn-sm px-2 py-1 fw-bold text-uppercase rounded-1" style="font-size: 10px; background-color: #e8f2ff; color: var(--brand-blue); border: none;">
                                        Reorder
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- MAIN ORDERS CONTAINER -->
    @if (empty($orders))
        <div class="card shadow-sm border-1 p-5 text-center bg-white" style="border-radius: 6px; border-color: var(--card-border);">
            <p class="text-muted mb-0 fst-italic">No past orders found in the database.</p>
        </div>
    @else
        <!-- No Pending Messages Alert -->
        <div id="noPendingMessage" class="card shadow-sm border-1 p-4 text-center bg-white mb-3" style="border-radius: 6px; border-color: var(--card-border); line-height: 1.5;" x-show="!hasPending && !showReceived">
            <p class="text-muted mb-0 fst-italic">All products have been received.<br>Click <span class="fw-bold cursor-pointer" style="color: var(--brand-blue); cursor: pointer;" x-on:click="showReceived = true">👁️</span> to view hidden products.</p>
        </div>

        @foreach ($orders as $order)
            @php
                $hasPendingItems = collect($order['items'])->contains('item_status', 'pending');
            @endphp
            <div class="card shadow-sm border-1 p-3 bg-white mb-3" 
                 style="border-radius: 6px; border-color: var(--card-border);" 
                 x-show="showReceived || {{ $hasPendingItems ? 'true' : 'false' }}">
                 
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3" style="border-color: var(--card-border) !important;">
                    <span class="fw-bold text-dark">Order #{{ $order['id'] }}</span>
                    <span class="small text-muted" style="font-size: 11px;">{{ date('M d, Y h:i A', strtotime($order['created_at'])) }}</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0" style="font-size: 12.5px;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--card-border);">
                                <th class="text-muted" style="font-size: 10px; text-transform: uppercase; padding: 6px 4px;">Product</th>
                                <th class="text-muted" style="font-size: 10px; text-transform: uppercase; padding: 6px 4px;">Flavor</th>
                                <th class="text-end text-muted" style="font-size: 10px; text-transform: uppercase; padding: 6px 4px; width: 60px;">Qty</th>
                                <th class="text-end text-muted" style="font-size: 10px; text-transform: uppercase; padding: 6px 4px; width: 140px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order['items'] as $item)
                                <tr style="border-bottom: 1px solid var(--card-border);" 
                                    x-show="showReceived || '{{ $item->item_status }}' === 'pending'">
                                    
                                    <td style="padding: 8px 4px;">
                                        <div class="fw-bold text-dark">{{ $item->brand }}</div>
                                        <div class="small text-muted" style="font-size: 10px;">{{ $item->line }}</div>
                                    </td>
                                    <td style="padding: 8px 4px; color: var(--text-primary);">
                                        {{ $item->flavor }}
                                    </td>
                                    <td class="text-end fw-bold text-dark" style="padding: 8px 4px;">
                                        x{{ $item->quantity }}
                                    </td>
                                    <td style="padding: 8px 4px; text-align: right;">
                                        <div class="d-inline-flex gap-1">
                                            @if ($item->item_status === 'pending')
                                                <button wire:click="markItemAsReceived({{ $item->item_id }})" class="btn btn-sm px-2 py-1 fw-bold text-uppercase rounded-1" style="font-size: 10px; background-color: #e8f9ee; color: var(--brand-green); border: none;">
                                                    Recv
                                                </button>
                                            @else
                                                <span class="badge bg-success-subtle text-success px-2 py-1 fw-bold" style="font-size: 10px; border-radius: 4px;">✓</span>
                                            @endif
                                            <button x-on:click="reorderSingle(@js($item))" class="btn btn-sm px-2 py-1 fw-bold text-uppercase rounded-1" style="font-size: 10px; background-color: #e8f2ff; color: var(--brand-blue); border: none;">
                                                Reorder
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    @endif
</div>
