<div class="container-fluid px-2 px-sm-3" style="max-width: 550px; margin: 0 auto;" 
     x-data="{ 
         cash: @entangle('cashSale'), 
         boi: @entangle('cardBoi'), 
         fixed: @entangle('cardFixed'),
         get total() {
             return (parseFloat(this.cash || 0) + parseFloat(this.boi || 0) + parseFloat(this.fixed || 0)).toFixed(2);
         },
         printTicket() {
             const now = new Date();
             const dateStr = now.toLocaleDateString() + ' ' + now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
             document.getElementById('receiptDateTime').textContent = dateStr;
             
             document.getElementById('pCash').textContent = '€' + parseFloat(this.cash || 0).toFixed(2);
             document.getElementById('pBoi').textContent = '€' + parseFloat(this.boi || 0).toFixed(2);
             document.getElementById('pFixed').textContent = '€' + parseFloat(this.fixed || 0).toFixed(2);
             document.getElementById('pTotal').textContent = '€' + this.total;
             
             window.print();
         }
     }">
     
    <div class="card shadow-sm border-1 overflow-hidden bg-white" style="border-radius: 6px; border-color: var(--card-border);">
        <div class="card-header bg-white py-3 px-4 border-bottom" style="border-left: 4px solid var(--brand-green) !important;">
            <h1 class="h5 fw-bold text-dark mb-1">Daily Sales Closure</h1>
            <div class="small text-muted fw-semibold">{{ $currentDateStr }}</div>
        </div>

        <div class="card-body p-4 bg-white">
            <!-- Notifications -->
            @if ($successMessage)
                <div class="alert alert-success py-2 px-3 small text-center mb-3" style="font-size: 13px; border-radius: 4px;">
                    {{ $successMessage }}
                </div>
            @endif
            @if ($errorMessage)
                <div class="alert alert-danger py-2 px-3 small text-center mb-3" style="font-size: 13px; border-radius: 4px;">
                    {{ $errorMessage }}
                </div>
            @endif

            <form wire:submit.prevent="save">
                <div class="mb-3">
                    <label for="cash_sale" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Cash Sale (€)</label>
                    <input type="number" step="0.01" min="0" id="cash_sale" class="form-control py-2 text-end fw-bold fs-5 rounded-1" x-model.number="cash" style="font-size: 18px;">
                </div>

                <div class="mb-3">
                    <label for="card_boi" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Card BOI (€)</label>
                    <input type="number" step="0.01" min="0" id="card_boi" class="form-control py-2 text-end fw-bold fs-5 rounded-1" x-model.number="boi" style="font-size: 18px;">
                </div>

                <div class="mb-4">
                    <label for="card_fixed" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Card Fixed (€)</label>
                    <input type="number" step="0.01" min="0" id="card_fixed" class="form-control py-2 text-end fw-bold fs-5 rounded-1" x-model.number="fixed" style="font-size: 18px;">
                </div>

                <div class="d-flex justify-content-between align-items-center bg-light border p-3 rounded mb-4" style="border-radius: 4px;">
                    <span class="small fw-bold text-uppercase text-muted" style="font-size: 11px; letter-spacing: 0.5px;">Total Sale</span>
                    <span class="h3 fw-bold mb-0" style="color: var(--brand-green);">€<span x-text="total">0.00</span></span>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn text-white py-2 flex-grow-1 fw-bold text-uppercase rounded-1" style="background-color: var(--brand-green); font-size: 13px; letter-spacing: 0.5px;">Save</button>
                    <button type="button" x-on:click="printTicket()" class="btn btn-outline-secondary py-2 flex-grow-1 fw-bold text-uppercase rounded-1" style="font-size: 13px; letter-spacing: 0.5px;">Print</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Hidden Printable Ticket Layout -->
    <div id="printArea" style="display: none;">
        <div class="receipt-header">
            <h2>DAILY CLOSURE</h2>
            <p style="font-weight: bold; font-size: 11px; margin-bottom: 4px;">{{ strtoupper($businessName) }}</p>
            @if ($businessAddress) <p>{{ $businessAddress }}</p> @endif
            @if ($businessContact) <p>Phone: {{ $businessContact }}</p> @endif
            @if ($businessEmail) <p>Email: {{ $businessEmail }}</p> @endif
        </div>
        <div class="receipt-divider"></div>
        <div class="receipt-details">
            <p><strong>Business:</strong> <span>{{ $businessName }}</span></p>
            <p><strong>Staff Name:</strong> <span>{{ $username }}</span></p>
            <p><strong>Date & Time:</strong> <span id="receiptDateTime"></span></p>
        </div>
        <div class="receipt-divider"></div>
        <div class="receipt-row">
            <span>Cash Sale:</span>
            <span id="pCash">€0.00</span>
        </div>
        <div class="receipt-row">
            <span>Card BOI:</span>
            <span id="pBoi">€0.00</span>
        </div>
        <div class="receipt-row">
            <span>Card Fixed:</span>
            <span id="pFixed">€0.00</span>
        </div>
        <div class="receipt-divider"></div>
        <div class="receipt-row" style="font-weight: bold; font-size: 14px;">
            <span>Total Sale:</span>
            <span id="pTotal">€0.00</span>
        </div>
    </div>

    <!-- Print styling scoped for printing -->
    <style>
        @media print {
            @page {
                size: auto;
                margin: 0mm;
            }
            body * {
                visibility: hidden;
            }
            #printArea, #printArea * {
                visibility: visible;
            }
            #printArea {
                display: block !important;
                position: absolute;
                left: 0;
                top: 0;
                width: 72mm;
                max-width: 72mm;
                font-family: Arial, Helvetica, sans-serif;
                color: #000000;
                background-color: #ffffff;
                padding: 4mm 2mm;
                font-size: 12px;
                line-height: 1.25;
            }
            #printArea p, #printArea h2, #printArea h3, #printArea span, #printArea div {
                margin: 0;
                padding: 0;
            }
            .receipt-header {
                text-align: center;
                margin-bottom: 8px;
            }
            .receipt-header h2 {
                font-size: 14px;
                margin-bottom: 3px;
                font-weight: bold;
            }
            .receipt-header p {
                font-size: 11px;
                margin-bottom: 2px;
            }
            .receipt-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 4px;
            }
            .receipt-divider {
                border-top: 1px dashed #000000;
                margin: 6px 0;
            }
            .receipt-details {
                margin-bottom: 6px;
            }
            .receipt-details p {
                display: flex;
                justify-content: space-between;
                margin-bottom: 3px;
                font-size: 11.5px;
            }
            .receipt-details strong {
                font-weight: bold;
            }
        }
    </style>
</div>
