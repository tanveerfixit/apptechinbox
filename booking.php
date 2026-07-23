<?php
// booking.php
session_start();
require_once __DIR__ . '/db.php';

// Enforce login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Redirect to Laravel route if Laravel application is bootstrapped
if (defined('LARAVEL_START')) {
    return redirect()->to('/booking');
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';
$businessId = $_SESSION['business_id'] ?? '';

if (empty($businessId) && !empty($userId)) {
    try {
        $stmtUserAssigned = $masterDb->prepare("SELECT assigned_business_id FROM users WHERE id = ?");
        $stmtUserAssigned->execute([$userId]);
        $businessId = $stmtUserAssigned->fetchColumn() ?: '';
    } catch (Exception $e) {}
}

$businessName = 'Store';
$businessContact = '';
$businessEmail = '';
$businessAddress = '';

if ($businessId) {
    try {
        $stmtBiz = $masterDb->prepare("SELECT name, contact, email, address FROM businesses WHERE id = ?");
        $stmtBiz->execute([$businessId]);
        $bizProfile = $stmtBiz->fetch();
        if ($bizProfile) {
            $businessName = $bizProfile['name'];
            $businessContact = $bizProfile['contact'] ?? '';
            $businessEmail = $bizProfile['email'] ?? '';
            $businessAddress = $bizProfile['address'] ?? '';
        }
    } catch (Exception $e) {}
}

// Retrieve printer configuration from isolated tenant database
$printerFontSize = 12;
$printerFontFamily = "'Courier New', Courier, monospace";
try {
    $pStmt = $db->query("SELECT font_size, font_family FROM printer_settings LIMIT 1");
    $pSettings = $pStmt->fetch();
    if ($pSettings) {
        $printerFontSize = intval($pSettings['font_size']);
        $printerFontFamily = $pSettings['font_family'];
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Repair Booking - TechInbox</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/public/icons/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/svg+xml" href="/public/icons/icon.svg">
    <link rel="shortcut icon" href="/public/icons/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/public/icons/apple-touch-icon.png">
    <link rel="manifest" href="/public/icons/site.webmanifest">
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- QR Code Generator Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
</head>
<body class="flex flex-col min-h-screen bg-[#f3f3f3] text-[#242424] font-sans antialiased">

    <!-- Header Navigation -->
    <?php require_once __DIR__ . '/header.php'; ?>

    <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-1" 
          x-data="{
              isDbConnected: <?php echo $tenantDbConnected ? 'true' : 'false'; ?>,
              suggestedDbName: '<?php echo htmlspecialchars($tenantDbName); ?>',
              name: '',
              phone: '',
              device: '',
              fault: '',
              quote: 0.00,
              deposit: 0.00,
              email: '',
              sessionId: '',
              timestamp: 0,
              businessName: '<?php echo htmlspecialchars($businessName, ENT_QUOTES, 'UTF-8'); ?>',
              businessId: '<?php echo htmlspecialchars($businessId, ENT_QUOTES, 'UTF-8'); ?>',
              isExpired: true,
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
                      const intakeUrl = window.location.origin + '/intake.php?session_id=' + this.sessionId + '&t=' + this.timestamp + '&b=' + encodeURIComponent(this.businessName) + '&bid=' + encodeURIComponent(this.businessId);
                       new QRious({
                            element: document.getElementById('intakeQr'),
                            value: intakeUrl,
                            size: 300,
                            foreground: '#000000',
                            level: 'M',
                            padding: 0
                       });
                  });
              },
              init() {
                  this.isExpired = true;
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
                          
                          const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                          const osc = audioCtx.createOscillator();
                          const gain = audioCtx.createGain();
                          osc.connect(gain);
                          gain.connect(audioCtx.destination);
                          osc.type = 'sine';
                          osc.frequency.setValueAtTime(659.25, audioCtx.currentTime);
                          gain.gain.setValueAtTime(0.05, audioCtx.currentTime);
                          osc.start();
                          osc.stop(audioCtx.currentTime + 0.1);
                          
                          setTimeout(() => {
                              const osc2 = audioCtx.createOscillator();
                              osc2.connect(gain);
                              osc2.type = 'sine';
                              osc2.frequency.setValueAtTime(880.00, audioCtx.currentTime);
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
                  document.getElementById('rCustomer').textContent = this.name;
                  document.getElementById('rPhone').textContent = this.phone;
                  document.getElementById('rEmail').textContent = this.email || 'N/A';
                  document.getElementById('rDevice').textContent = this.device;
                  document.getElementById('rFault').textContent = this.fault;
                  document.getElementById('rQuote').textContent = '€' + parseFloat(this.quote || 0).toFixed(2);
                  document.getElementById('rDeposit').textContent = '€' + parseFloat(this.deposit || 0).toFixed(2);
                  document.getElementById('rBalance').textContent = '€' + this.balance;

                  const now = new Date();
                  const dateStr = now.toLocaleDateString() + ' ' + now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                  const ticketNum = 'TI-' + now.getFullYear() + String(now.getMonth() + 1).padStart(2, '0') + String(now.getDate()).padStart(2, '0') + String(now.getHours()).padStart(2, '0') + String(now.getMinutes()).padStart(2, '0');

                  document.getElementById('receiptDate').textContent = 'Date: ' + dateStr;
                  document.getElementById('receiptTicketNum').textContent = 'Ticket #: ' + ticketNum;

                  window.print();
              },
               async saveBooking(andPrint = false) {
                   if (!this.isDbConnected) {
                       alert('⚠️ Database Connection Error\n\nThis business is not connected to its relevant database.\n\nPlease create the database \'' + this.suggestedDbName + '\' in Hostinger and assign user privileges to save bookings.');
                       return;
                   }
                   if (!this.name || !this.phone || !this.device || !this.fault) {
                      alert('Please fill in all required fields (Name, Phone, Device, and Description).');
                      return;
                   }
                   const bookingData = {
                       name: this.name,
                       phone: this.phone,
                       email: this.email,
                       device: this.device,
                       fault: this.fault,
                       quote: this.quote,
                       deposit: this.deposit,
                       business_name: this.businessName
                   };
                   try {
                       const response = await fetch('api.php?action=save_booking', {
                           method: 'POST',
                           headers: {
                               'Content-Type': 'application/json'
                           },
                           body: JSON.stringify(bookingData)
                       });
                       const result = await response.json();
                       if (result.status === 'success') {
                           if (andPrint) {
                               const ticketId = result.ticket_id || 'TI-' + Math.random().toString(36).substring(2, 8).toUpperCase();
                               
                               document.getElementById('rCustomer').textContent = this.name;
                               document.getElementById('rPhone').textContent = this.phone;
                               document.getElementById('rEmail').textContent = this.email || 'N/A';
                               document.getElementById('rDevice').textContent = this.device;
                               document.getElementById('rFault').textContent = this.fault;
                               document.getElementById('rQuote').textContent = '€' + parseFloat(this.quote || 0).toFixed(2);
                               document.getElementById('rDeposit').textContent = '€' + parseFloat(this.deposit || 0).toFixed(2);
                               document.getElementById('rBalance').textContent = '€' + this.balance;

                               const now = new Date();
                               const dateStr = now.toLocaleDateString() + ' ' + now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                               document.getElementById('receiptDate').textContent = 'Date: ' + dateStr;
                               document.getElementById('receiptTicketNum').textContent = 'Ticket #: ' + ticketId;

                               window.print();
                           }
                           window.location.reload();
                       } else {
                           alert('Error saving booking: ' + (result.message || 'Unknown error'));
                       }
                   } catch (e) {
                       alert('Failed to save booking. Please try again.');
                   }
               }
          }">
          
        <?php require __DIR__ . '/nav_buttons.php'; ?>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-stretch">
            <!-- Left Side / Main: New Repair Booking Form -->
            <div class="md:col-span-7 lg:col-span-8 xl:col-span-9 flex flex-col">
                <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-xs overflow-hidden h-full flex flex-col justify-between">
                    <div>
                        <div class="p-6 border-b border-[#e0e0e0] border-l-4 border-l-[#008272] bg-white">
                            <h1 class="text-2xl font-extrabold text-[#242424] tracking-tight mb-1">New Repair Booking</h1>
                            <div class="text-sm text-[#5c5c5c] font-medium">Enter customer and device details below</div>
                        </div>

                        <div class="p-6 bg-white space-y-5">
                            <?php if (!$tenantDbConnected): ?>
                                <div class="bg-red-50 border-l-4 border-[#008272] p-4 text-sm text-red-900 rounded-[4px]">
                                    <strong class="font-bold">⚠️ Database Not Connected</strong><br>
                                    This business is not connected to its relevant database. Please create the database <strong>`<?php echo htmlspecialchars($tenantDbName); ?>`</strong> in Hostinger and assign user privileges to allow saving repair bookings.
                                </div>
                            <?php endif; ?>
                            <form x-on:submit.prevent="saveBooking(true)" class="space-y-5">
                                
                                <!-- Customer Name, Phone & Email -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="c_cust_name" class="block text-xs font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Customer Name *</label>
                                        <input type="text" id="c_cust_name" name="c_cust_name" class="w-full px-2.5 py-1.5 text-sm font-medium border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]" placeholder="Full Name" required autocomplete="new-password" x-model="name">
                                    </div>
                                    <div>
                                        <label for="phoneNumber" class="block text-xs font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Phone Number *</label>
                                        <input type="text" id="phoneNumber" class="w-full px-2.5 py-1.5 text-sm font-medium border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]" placeholder="08X XXX XXXX" required autocomplete="off" x-model="phone">
                                    </div>
                                    <div>
                                        <label for="customerEmail" class="block text-xs font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Email Address <span class="text-[#5c5c5c] font-normal">(Optional)</span></label>
                                        <input type="email" id="customerEmail" class="w-full px-2.5 py-1.5 text-sm font-medium border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]" placeholder="email@example.com" autocomplete="off" x-model="email">
                                    </div>
                                </div>

                                <!-- Device Model -->
                                <div>
                                    <label for="deviceModel" class="block text-xs font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Device Model *</label>
                                    <input type="text" id="deviceModel" class="w-full px-2.5 py-1.5 text-sm font-medium border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]" placeholder="e.g. iPhone 13, Samsung S22" required autocomplete="off" x-model="device">
                                </div>

                                <!-- Problem Description -->
                                <div>
                                    <label for="problemDescription" class="block text-xs font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Problem Description *</label>
                                    <textarea id="problemDescription" class="w-full px-2.5 py-1.5 text-sm font-medium border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]" rows="3" placeholder="Describe the fault..." required x-model="fault"></textarea>
                                </div>

                                <div class="border-t border-[#e0e0e0] my-3"></div>

                                <!-- Pricing Block -->
                                <div class="p-4 rounded-[6px] bg-[#fafafa] border border-[#e0e0e0] space-y-3">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label for="totalQuote" class="block text-xs font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Total Quote</label>
                                            <div class="flex">
                                                <span class="inline-flex items-center px-2.5 text-sm text-[#5c5c5c] bg-white border border-r-0 border-[#e0e0e0] rounded-l-[4px]">€</span>
                                                <input type="number" step="0.01" min="0" id="totalQuote" class="w-full px-2.5 py-1.5 text-sm font-bold border border-[#e0e0e0] rounded-r-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]" x-model.number="quote">
                                            </div>
                                        </div>
                                        <div>
                                            <label for="depositPaid" class="block text-xs font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Deposit Paid</label>
                                            <div class="flex">
                                                <span class="inline-flex items-center px-2.5 text-sm text-[#5c5c5c] bg-white border border-r-0 border-[#e0e0e0] rounded-l-[4px]">€</span>
                                                <input type="number" step="0.01" min="0" id="depositPaid" class="w-full px-2.5 py-1.5 text-sm font-bold border border-[#e0e0e0] rounded-r-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]" x-model.number="deposit">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center pt-2 border-t border-[#e0e0e0]">
                                        <span class="text-xs font-bold uppercase tracking-wider text-[#5c5c5c]">Remaining Balance Due</span>
                                        <span class="text-2xl font-extrabold text-[#f25022]">€<span x-text="balance">0.00</span></span>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="grid grid-cols-2 gap-3 pt-1">
                                    <button type="button" @click="saveBooking(false)" class="w-full py-2.5 px-4 bg-[#5c5c5c] hover:bg-[#4a4a4a] text-white text-xs font-bold uppercase tracking-wider rounded-[4px] transition-colors shadow-xs">
                                        Save
                                    </button>
                                    <button type="submit" class="w-full py-3 px-4 bg-[#f25022] hover:bg-[#d83b01] text-white text-xs font-bold uppercase tracking-wider rounded-[4px] transition-colors shadow-xs">
                                        Save and Print
                                    </button>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side / Sidebar: QR Customer Intake Code Block -->
            <div class="md:col-span-5 lg:col-span-4 xl:col-span-3 flex flex-col">
                <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-xs p-6 text-center h-full flex flex-col justify-between space-y-4">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-[#008272]">
                        📲 Mobile Customer Intake
                    </h3>
                    
                    <div x-show="!isExpired" class="flex flex-col flex-1 justify-between space-y-4">
                        <div>
                            <div class="mb-3">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-[#f3f3f3] border border-[#e0e0e0] text-xs font-semibold text-[#5c5c5c] rounded-full">
                                    <span class="w-2 h-2 bg-[#7fba00] rounded-full animate-pulse"></span> Active Scan Session
                                </span>
                            </div>
                            
                            <p class="text-xs text-[#5c5c5c] leading-relaxed mb-4">Scan this QR code with a phone camera to quickly enter customer Name, Phone, and Device details.</p>
                            
                            <div class="flex justify-center mb-4">
                                 <div class="border-2 border-dashed border-[#242424] p-3 bg-white inline-flex items-center justify-center">
                                     <canvas id="intakeQr" width="300" height="300" class="w-[180px] h-[180px] block"></canvas>
                                 </div>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <div class="text-xs text-[#5c5c5c] flex items-center justify-center gap-2 bg-[#fafafa] p-2.5 rounded-[4px] border border-[#e0e0e0]">
                                <span>ID: <strong class="text-[#242424] text-xs" x-text="sessionId"></strong></span>
                                <button type="button" @click="navigator.clipboard.writeText(window.location.origin + '/intake.php?session_id=' + sessionId + '&t=' + timestamp + '&b=' + encodeURIComponent(businessName) + '&bid=' + encodeURIComponent(businessId)); alert('Intake link copied to clipboard!')" class="text-[#008272] hover:opacity-75 transition-opacity" title="Copy Intake Link">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                </button>
                            </div>
                            
                            <button type="button" @click="checkIntake()" class="w-full py-2.5 px-3 bg-white border border-[#e0e0e0] hover:bg-[#f3f3f3] text-[#008272] font-bold text-xs rounded-[4px] inline-flex items-center justify-center gap-2 transition-colors" :disabled="isPulling">
                                <span x-show="!isPulling">⚡ Check Submission</span>
                                <span x-show="isPulling" class="animate-spin text-xs">🌀</span>
                            </button>
                        </div>
                    </div>
                    
                    <div x-show="isExpired" class="flex flex-col items-center justify-center flex-1 my-auto space-y-2">
                        <span class="text-3xl">⏳</span>
                        <h4 class="text-base font-bold text-[#5c5c5c]">QR Code Expired</h4>
                        <p class="text-xs text-[#5c5c5c] max-w-[220px] leading-relaxed mb-3">This intake session has timed out or has not been generated yet.</p>
                        <button type="button" @click="refreshSession()" class="w-full py-3 px-4 bg-[#008272] hover:bg-[#006e60] text-white text-xs font-bold uppercase tracking-wider rounded-[4px] transition-colors shadow-xs">
                            Generate QR Code
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Hidden Container for thermal ticket layout during printing -->
    <div id="printArea" style="display: none;">
        <div class="receipt-header">
            <h2><?php echo htmlspecialchars(strtoupper($businessName)); ?></h2>
            <p style="font-weight: bold; font-size: 15px; margin-bottom: 4px;">REPAIR TICKET</p>
            <?php if (!empty($businessAddress)): ?>
                <p style="font-size: 13px;"><?php echo htmlspecialchars($businessAddress); ?></p>
            <?php endif; ?>
            <?php if (!empty($businessContact)): ?>
                <p style="font-size: 13px;">Phone: <?php echo htmlspecialchars($businessContact); ?></p>
            <?php endif; ?>
            <?php if (!empty($businessEmail)): ?>
                <p style="font-size: 13px;">Email: <?php echo htmlspecialchars($businessEmail); ?></p>
            <?php endif; ?>
            <p id="receiptDate" style="margin-top: 4px; font-size: 13px;"></p>
            <p id="receiptTicketNum" style="font-weight: bold; font-size: 14px;"></p>
        </div>
        <div class="receipt-divider"></div>
        <div class="receipt-details">
            <p style="font-size: 14px;"><strong>Customer:</strong> <span id="rCustomer"></span></p>
            <p style="font-size: 14px;"><strong>Phone:</strong> <span id="rPhone"></span></p>
            <p style="font-size: 14px;"><strong>Email:</strong> <span id="rEmail"></span></p>
            <p style="font-size: 14px;"><strong>Device:</strong> <span id="rDevice"></span></p>
            <p style="font-size: 14px;"><strong>Booked By:</strong> <span id="rUser"><?php echo htmlspecialchars($username ?: 'Guest'); ?></span></p>
        </div>
        <div class="receipt-divider"></div>
        <div class="receipt-details" style="display: block;">
            <p style="display: block; font-weight: bold; margin-bottom: 2px; font-size: 14px;">Fault Description:</p>
            <p id="rFault" style="display: block; padding-left: 4px; font-style: italic; font-size: 14px; white-space: pre-wrap; line-height: 1.3;"></p>
        </div>
        <div class="receipt-divider"></div>
        <div class="receipt-row" style="font-size: 14px;">
            <span>Total Quote:</span>
            <span id="rQuote">€0.00</span>
        </div>
        <div class="receipt-row" style="font-size: 14px;">
            <span>Deposit Paid:</span>
            <span id="rDeposit">€0.00</span>
        </div>
        <div class="receipt-divider"></div>
        <div class="receipt-row" style="font-weight: bold; font-size: 18px;">
            <span>Balance Due:</span>
            <span id="rBalance">€0.00</span>
        </div>
    </div>

    <!-- Standard Footer -->
    <?php require_once __DIR__ . '/footer.php'; ?>

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
                font-family: <?php echo $printerFontFamily; ?> !important;
                color: #000000;
                background-color: #ffffff;
                padding: 4mm 2mm;
                font-size: <?php echo $printerFontSize + 3; ?>px !important;
                line-height: 1.35;
            }
            #printArea p, #printArea h2, #printArea h3, #printArea span, #printArea div {
                margin: 0;
                padding: 0;
            }
            .receipt-header {
                text-align: center;
                margin-bottom: 10px;
            }
            .receipt-header h2 {
                font-size: 18px;
                margin-bottom: 4px;
                font-weight: bold;
            }
            .receipt-header p {
                font-size: 14px;
                margin-bottom: 3px;
            }
            .receipt-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 5px;
                font-size: 14px;
            }
            .receipt-divider {
                border-top: 1px dashed #000000;
                margin: 8px 0;
            }
            .receipt-details {
                margin-bottom: 8px;
            }
            .receipt-details p {
                display: flex;
                justify-content: space-between;
                margin-bottom: 4px;
                font-size: 14px;
            }
            .receipt-details strong {
                font-weight: bold;
            }
        }
    </style>
</body>
</html>
