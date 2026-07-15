<div class="container-fluid px-2 px-sm-3" style="max-width: 600px; margin: 0 auto;"
     x-data="{
         name: @entangle('customerName'),
         phone: @entangle('phoneNumber'),
         email: @entangle('customerEmail'),
         device: @entangle('deviceModel'),
         fault: @entangle('problemDescription'),
         quote: @entangle('totalQuote'),
         deposit: @entangle('depositPaid'),
         sessionId: '',
         timestamp: 0,
         businessName: @entangle('businessName'),
         isExpired: false,
         expirationTimeout: null,
         startExpirationTimer() {
             if (this.expirationTimeout) clearTimeout(this.expirationTimeout);
             this.expirationTimeout = setTimeout(() => {
                 this.isExpired = true;
                 if (this.pollInterval) {
                     clearInterval(this.pollInterval);
                     this.pollInterval = null;
                 }
             }, 180000); // 3 minutes
         },
         refreshSession() {
             this.sessionId = 'INT-' + Math.random().toString(36).substring(2, 8).toUpperCase();
             this.timestamp = Math.floor(Date.now() / 1000);
             this.isExpired = false;
             this.name = '';
             this.phone = '';
             this.device = '';
             this.email = '';
             this.generateQrCode();
             this.startExpirationTimer();
             if (!this.pollInterval) {
                 this.pollInterval = setInterval(async () => {
                     if (document.hidden || this.isExpired) return;
                     this.checkIntake();
                 }, 15000);
             }
         },
         generateQrCode() {
             this.$nextTick(() => {
                 const intakeUrl = window.location.origin + '/intake.php?session_id=' + this.sessionId + '&t=' + this.timestamp + '&b=' + encodeURIComponent(this.businessName);
                 new QRious({
                     element: document.getElementById('intakeQr'),
                     value: intakeUrl,
                     size: 300,
                     foreground: '#008272' // Teal theme color for QR code
                 });
             });
         },
         init() {
             this.sessionId = 'INT-' + Math.random().toString(36).substring(2, 8).toUpperCase();
             this.timestamp = Math.floor(Date.now() / 1000);
             this.generateQrCode();
             this.startExpirationTimer();

             // Start polling every 15 seconds
             this.pollInterval = setInterval(async () => {
                 if (document.hidden || this.isExpired) return;
                 this.checkIntake();
             }, 15000);
         },
         pollInterval: null,
         isPulling: false,
         async checkIntake() {
             if (this.isPulling) return;
             this.isPulling = true;
             try {
                 const res = await fetch('api.php?action=check_intake&session_id=' + this.sessionId);
                 const data = await res.json();
                 if (data.status === 'success' && data.found) {
                     this.name = data.data.name;
                     this.phone = data.data.phone;
                     this.device = data.data.device_name;
                     this.email = data.data.email || '';
                     
                     if (this.pollInterval) {
                         clearInterval(this.pollInterval);
                         this.pollInterval = null;
                     }
                     
                     // Play modern notification chime
                     const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                     const osc = audioCtx.createOscillator();
                     const gain = audioCtx.createGain();
                     osc.connect(gain);
                     gain.connect(audioCtx.destination);
                     osc.type = 'sine';
                     osc.frequency.setValueAtTime(659.25, audioCtx.currentTime); // E5
                     gain.gain.setValueAtTime(0.05, audioCtx.currentTime);
                     osc.start();
                     osc.stop(audioCtx.currentTime + 0.1);
                     
                     setTimeout(() => {
                         const osc2 = audioCtx.createOscillator();
                         osc2.connect(gain);
                         osc2.type = 'sine';
                         osc2.frequency.setValueAtTime(880.00, audioCtx.currentTime); // A5
                         osc2.start();
                         osc2.stop(audioCtx.currentTime + 0.15);
                     }, 100);
                 }
             } catch (e) {} finally {
                 this.isPulling = false;
             }
         },
         get balance() {
             return Math.max(0.00, parseFloat(this.quote || 0) - parseFloat(this.deposit || 0)).toFixed(2);
         },
         printReceipt() {
             // Populate print ticket items
             document.getElementById('rCustomer').textContent = this.name;
             document.getElementById('rPhone').textContent = this.phone;
             document.getElementById('rEmail').textContent = this.email || 'N/A';
             document.getElementById('rDevice').textContent = this.device;
             document.getElementById('rFault').textContent = this.fault;
             document.getElementById('rQuote').textContent = '€' + parseFloat(this.quote || 0).toFixed(2);
             document.getElementById('rDeposit').textContent = '€' + parseFloat(this.deposit || 0).toFixed(2);
             document.getElementById('rBalance').textContent = '€' + this.balance;

             // Generate Ticket ID and timestamp
             const now = new Date();
             const dateStr = now.toLocaleDateString() + ' ' + now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
             const ticketNum = 'TI-' + now.getFullYear() + String(now.getMonth() + 1).padStart(2, '0') + String(now.getDate()).padStart(2, '0') + String(now.getHours()).padStart(2, '0') + String(now.getMinutes()).padStart(2, '0');

             document.getElementById('receiptDate').textContent = 'Date: ' + dateStr;
             document.getElementById('receiptTicketNum').textContent = 'Ticket #: ' + ticketNum;

             window.print();
         }
     }">
     
    <!-- QR Customer Intake Code Block -->
    <div class="card shadow-sm border-1 p-3 mb-3 bg-white text-center" style="border-radius: 6px; border-color: var(--card-border) !important;">
        <h3 class="small fw-bold text-uppercase text-muted mb-2" style="font-size: 11px; letter-spacing: 0.5px; color: var(--brand-teal) !important;">
            📲 Mobile Customer Intake
        </h3>
        
        <template x-if="!isExpired">
            <div>
                <p class="text-muted mb-3" style="font-size: 12px; max-width: 420px; margin: 0 auto;">Scan this QR code with a phone camera to quickly enter customer Name, Phone, and Device details.</p>
                <div class="d-flex justify-content-center mb-2">
                    <canvas id="intakeQr" style="width: 140px; height: 140px;"></canvas>
                </div>
                <div class="small text-muted d-flex align-items-center justify-content-center gap-2" style="font-size: 11px;">
                    Session: <span class="fw-semibold text-dark" x-text="sessionId"></span>
                    <button type="button" @click="navigator.clipboard.writeText(window.location.origin + '/intake.php?session_id=' + sessionId + '&t=' + timestamp + '&b=' + encodeURIComponent(businessName)); alert('Intake link copied to clipboard!')" class="btn p-0 border-0 d-inline-flex align-items-center" title="Copy Intake Link" style="color: var(--brand-teal); transition: opacity 0.15s ease;" onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                    </button>
                </div>
                
                <div class="mt-2">
                    <button type="button" @click="checkIntake()" class="btn btn-sm btn-light border px-3 py-1 text-teal d-inline-flex align-items-center gap-1 rounded-1" style="font-size: 11.5px; border-color: var(--card-border) !important; color: var(--brand-teal) !important;" :disabled="isPulling">
                        <span x-show="!isPulling">⚡ Check Mobile Submission</span>
                        <span x-show="isPulling" class="spinner-border spinner-border-sm" role="status"></span>
                    </button>
                </div>
            </div>
        </template>
        
        <template x-if="isExpired">
            <div class="py-4 px-2 d-flex flex-column align-items-center justify-content-center">
                <span class="fs-4 mb-2">⏳</span>
                <h4 class="h6 fw-bold text-secondary mb-1">QR Code Expired</h4>
                <p class="text-muted small mb-3" style="font-size: 11px; max-width: 250px;">This intake session has timed out after 3 minutes of inactivity to protect your connection limit.</p>
                <button type="button" @click="refreshSession()" class="btn btn-sm btn-teal text-white d-inline-flex align-items-center gap-1 px-3 py-1.5 rounded-1" style="font-size: 12px; background-color: var(--brand-teal); border: 0;">
                    <span>🔄 Refresh QR Code</span>
                </button>
            </div>
        </template>
    </div>

    <div class="card shadow-sm border-1 overflow-hidden bg-white" style="border-radius: 6px; border-color: var(--card-border);">
        <div class="card-header bg-white py-3 px-4 border-bottom" style="border-left: 4px solid #008272 !important;">
            <h1 class="h5 fw-bold text-dark mb-1">New Repair Booking</h1>
            <div class="small text-muted fw-semibold">Enter customer and device details below</div>
        </div>

        <div class="card-body p-4 bg-white">
            <form x-on:submit.prevent="printReceipt()">
                
                <!-- Customer Name, Phone & Email -->
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-4">
                        <label for="customerName" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px; color: var(--text-secondary) !important;">Customer Name *</label>
                        <input type="text" id="customerName" class="form-control py-2 rounded-1" placeholder="Full Name" required autocomplete="off" x-model="name" style="font-size: 14px;">
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="phoneNumber" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px; color: var(--text-secondary) !important;">Phone Number *</label>
                        <input type="text" id="phoneNumber" class="form-control py-2 rounded-1" placeholder="08X XXX XXXX" required autocomplete="off" x-model="phone" style="font-size: 14px;">
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="customerEmail" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px; color: var(--text-secondary) !important;">Email Address <span class="text-muted fw-normal">(Optional)</span></label>
                        <input type="email" id="customerEmail" class="form-control py-2 rounded-1" placeholder="email@example.com" autocomplete="off" x-model="email" style="font-size: 14px;">
                    </div>
                </div>

                <!-- Device Model -->
                <div class="mb-3">
                    <label for="deviceModel" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px; color: var(--text-secondary) !important;">Device Model *</label>
                    <input type="text" id="deviceModel" class="form-control py-2 rounded-1" placeholder="e.g. iPhone 13, Samsung S22" required autocomplete="off" x-model="device" style="font-size: 14px;">
                </div>

                <!-- Problem Description -->
                <div class="mb-4">
                    <label for="problemDescription" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px; color: var(--text-secondary) !important;">Problem Description *</label>
                    <textarea id="problemDescription" class="form-control rounded-1" rows="3" placeholder="Describe the fault..." required x-model="fault" style="font-size: 14px;"></textarea>
                </div>

                <div class="border-top my-4"></div>

                <!-- Pricing Block -->
                <div class="p-3 rounded-2 mb-4 bg-light border" style="border-radius: 6px;">
                    <div class="row g-3">
                        <div class="col-6">
                            <label for="totalQuote" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px; color: var(--text-secondary) !important;">Total Quote</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-muted" style="font-size: 14px;">€</span>
                                <input type="number" step="0.01" min="0" id="totalQuote" class="form-control fw-semibold border-start-0 rounded-end-1 py-2" x-model.number="quote" style="font-size: 15px;">
                            </div>
                        </div>
                        <div class="col-6">
                            <label for="depositPaid" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px; color: var(--text-secondary) !important;">Deposit Paid</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-muted" style="font-size: 14px;">€</span>
                                <input type="number" step="0.01" min="0" id="depositPaid" class="form-control fw-semibold border-start-0 rounded-end-1 py-2" x-model.number="deposit" style="font-size: 15px;">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top" style="border-top: 1px solid var(--card-border) !important;">
                        <span class="small fw-bold text-uppercase text-muted" style="font-size: 10px; letter-spacing: 0.5px; color: var(--text-secondary) !important;">Remaining Balance Due</span>
                        <span class="h4 fw-bold mb-0 text-danger">€<span x-text="balance">0.00</span></span>
                    </div>
                </div>

                <!-- Action Button -->
                <button type="submit" class="btn text-white w-100 py-3 text-uppercase fw-bold rounded-1" style="background-color: #f25022; font-size: 13px; letter-spacing: 0.5px;">
                    Generate & Print Receipt
                </button>

            </form>
        </div>
    </div>

    <!-- Hidden Container for thermal ticket layout during printing -->
    <div id="printArea" style="display: none;">
        <div class="receipt-header">
            <h2>{{ strtoupper($businessName) }}</h2>
            <p style="font-weight: bold; font-size: 12px; margin-bottom: 4px;">REPAIR TICKET</p>
            @if ($businessAddress) <p>{{ $businessAddress }}</p> @endif
            @if ($businessContact) <p>Phone: {{ $businessContact }}</p> @endif
            @if ($businessEmail) <p>Email: {{ $businessEmail }}</p> @endif
            <p id="receiptDate" style="margin-top: 4px;"></p>
            <p id="receiptTicketNum" style="font-weight: bold;"></p>
        </div>
        <div class="receipt-divider"></div>
        <div class="receipt-details">
            <p><strong>Customer:</strong> <span id="rCustomer"></span></p>
            <p><strong>Phone:</strong> <span id="rPhone"></span></p>
            <p><strong>Email:</strong> <span id="rEmail"></span></p>
            <p><strong>Device:</strong> <span id="rDevice"></span></p>
            <p><strong>Booked By:</strong> <span id="rUser">{{ $username }}</span></p>
        </div>
        <div class="receipt-divider"></div>
        <div class="receipt-details" style="display: block;">
            <p style="display: block; font-weight: bold; margin-bottom: 2px;">Fault Description:</p>
            <p id="rFault" style="display: block; padding-left: 4px; font-style: italic; font-size: 11px; white-space: pre-wrap; line-height: 1.3;"></p>
        </div>
        <div class="receipt-divider"></div>
        <div class="receipt-row">
            <span>Total Quote:</span>
            <span id="rQuote">€0.00</span>
        </div>
        <div class="receipt-row">
            <span>Deposit Paid:</span>
            <span id="rDeposit">€0.00</span>
        </div>
        <div class="receipt-divider"></div>
        <div class="receipt-row" style="font-weight: bold; font-size: 14px;">
            <span>Balance Due:</span>
            <span id="rBalance">€0.00</span>
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
