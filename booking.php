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

// Fetch active user details
$stmtUser = $masterDb->prepare("SELECT name, contact, email, address FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$profile = $stmtUser->fetch();
$businessName = !empty($profile['name']) ? $profile['name'] : 'Store';
$businessContact = !empty($profile['contact']) ? $profile['contact'] : '';
$businessEmail = !empty($profile['email']) ? $profile['email'] : '';
$businessAddress = !empty($profile['address']) ? $profile['address'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Repair Booking - TechInbox</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Outfit Font & Bootstrap 5 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        :root {
            --bg-color: #f3f3f3; /* Microsoft Fluent Light Gray */
            --card-bg: #ffffff;
            --card-border: #e0e0e0;
            --text-primary: #242424;
            --text-secondary: #5c5c5c;
            --brand-teal: #008272;
            --brand-blue: #00a4ef;
            --brand-green: #7fba00;
            --font-family: 'Outfit', 'Segoe UI', system-ui, sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-primary);
            font-family: var(--font-family);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 6px;
        }

        .btn-brand {
            background-color: #f25022;
            border-color: #f25022;
            color: #ffffff;
        }

        .btn-brand:hover {
            background-color: #d83b01;
            border-color: #d83b01;
            color: #ffffff;
        }

        /* Pulse Status Animation */
        @keyframes pulse {
            0% { transform: scale(0.95); opacity: 0.5; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.5; }
        }
        .pulse-dot {
            width: 8px;
            height: 8px;
            background-color: var(--brand-green);
            border-radius: 50%;
            display: inline-block;
            animation: pulse 1.8s infinite ease-in-out;
        }
        .status-badge {
            background-color: #f3f3f3;
            color: var(--text-secondary);
            font-size: 11px;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 12px;
            border: 1px solid var(--card-border);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* Scan viewfinder styling */
        .scan-frame {
            border: 2px dashed var(--brand-teal);
            border-radius: 8px;
            padding: 8px;
            background: #fafafa;
            display: inline-block;
            box-shadow: inset 0 2px 6px rgba(0,0,0,0.02);
        }
    </style>
    <!-- QR Code Generator Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Header Navigation -->
    <?php require_once __DIR__ . '/header.php'; ?>

    <main class="container-fluid px-2 px-md-4 py-3 py-md-4 flex-grow-1" 
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
                          foreground: '#008272' // Teal theme color for QR code
                      });
                  });
              },
              init() {
                  // Do not automatically generate QR code or start session on init
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
                              
                              // Populate print ticket items
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
          
        <div class="row g-4 align-items-start">
            <!-- Left Side / Sidebar: QR Customer Intake Code Block -->
            <div class="col-12 col-md-5 col-lg-4 col-xl-3">
                <div class="card shadow-sm border-1 p-4 bg-white text-center" style="border-radius: 6px; border-color: var(--card-border) !important; min-height: 425px; display: flex; flex-direction: column;">
                    <h3 class="small fw-bold text-uppercase text-muted mb-3" style="font-size: 11px; letter-spacing: 0.5px; color: var(--brand-teal) !important;">
                        📲 Mobile Customer Intake
                    </h3>
                    
                    <div x-show="!isExpired" class="d-flex flex-column flex-grow-1 justify-content-between">
                        <div>
                            <div class="mb-3">
                                <span class="status-badge">
                                    <span class="pulse-dot"></span> Active Scan Session
                                </span>
                            </div>
                            
                            <p class="text-muted mb-3" style="font-size: 12px; line-height: 1.4;">Scan this QR code with a phone camera to quickly enter customer Name, Phone, and Device details.</p>
                            
                            <div class="d-flex justify-content-center mb-3">
                                <div class="scan-frame">
                                    <canvas id="intakeQr" style="width: 145px; height: 145px; display: block;"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="small text-muted d-flex align-items-center justify-content-center gap-2 mb-3" style="font-size: 11px; background: #f8f9fa; padding: 6px; border-radius: 4px; border: 1px solid var(--card-border);">
                                <span>ID: <strong class="text-dark" x-text="sessionId"></strong></span>
                                <button type="button" @click="navigator.clipboard.writeText(window.location.origin + '/intake.php?session_id=' + sessionId + '&t=' + timestamp + '&b=' + encodeURIComponent(businessName) + '&bid=' + encodeURIComponent(businessId)); alert('Intake link copied to clipboard!')" class="btn p-0 border-0 d-inline-flex align-items-center" title="Copy Intake Link" style="color: var(--brand-teal); transition: opacity 0.15s ease;" onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                </button>
                            </div>
                            
                            <button type="button" @click="checkIntake()" class="btn btn-sm btn-light border w-100 py-2 text-teal d-inline-flex align-items-center justify-content-center gap-2 rounded-1" style="font-size: 11.5px; border-color: var(--card-border) !important; color: var(--brand-teal) !important;" :disabled="isPulling">
                                <span x-show="!isPulling">⚡ Check Submission</span>
                                <span x-show="isPulling" class="spinner-border spinner-border-sm" role="status"></span>
                            </button>
                        </div>
                    </div>
                    
                    <div x-show="isExpired" class="d-flex flex-column align-items-center justify-content-center flex-grow-1">
                        <span class="fs-4 mb-2">⏳</span>
                        <h4 class="h6 fw-bold text-secondary mb-1">QR Code Expired</h4>
                        <p class="text-muted small mb-4" style="font-size: 11px; max-width: 200px; line-height: 1.4;">This intake session has timed out or has not been generated yet.</p>
                        <button type="button" @click="refreshSession()" class="btn btn-sm btn-teal text-white w-100 py-2 rounded-1" style="font-size: 12px; background-color: var(--brand-teal); border: 0; font-weight: 600; letter-spacing: 0.3px;">
                            Generate QR Code
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Side / Main: New Repair Booking Form -->
            <div class="col-12 col-md-7 col-lg-8 col-xl-9">
                <div class="card shadow-sm border-1 overflow-hidden" style="border-radius: 6px; border-color: var(--card-border) !important;">
                    <div class="card-header bg-white py-3 px-4 border-bottom" style="border-left: 4px solid var(--brand-teal) !important;">
                        <h1 class="h5 fw-bold text-dark mb-1">New Repair Booking</h1>
                        <div class="small text-muted fw-semibold">Enter customer and device details below</div>
                    </div>

                    <div class="card-body p-4 bg-white">
                        <?php if (!$tenantDbConnected): ?>
                            <div class="alert alert-danger p-3 mb-4 border-0 shadow-sm" style="font-size: 13.5px; border-radius: 6px; border-left: 4px solid var(--brand-teal) !important; background-color: #fdf2f2; color: #9b1c1c;">
                                <strong>⚠️ Database Not Connected</strong><br>
                                This business is not connected to its relevant database. Please create the database <strong>`<?php echo htmlspecialchars($tenantDbName); ?>`</strong> in Hostinger and assign user privileges to allow saving repair bookings.
                            </div>
                        <?php endif; ?>
                        <form x-on:submit.prevent="saveBooking(true)">
                            
                            <!-- Customer Name, Phone & Email -->
                            <div class="row g-3 mb-3">
                                <div class="col-12 col-md-4">
                                    <label for="c_cust_name" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Customer Name *</label>
                                    <input type="text" id="c_cust_name" name="c_cust_name" class="form-control py-2 rounded-1" placeholder="Full Name" required autocomplete="new-password" x-model="name" style="font-size: 14px;">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label for="phoneNumber" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Phone Number *</label>
                                    <input type="text" id="phoneNumber" class="form-control py-2 rounded-1" placeholder="08X XXX XXXX" required autocomplete="off" x-model="phone" style="font-size: 14px;">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label for="customerEmail" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Email Address <span class="text-muted fw-normal">(Optional)</span></label>
                                    <input type="email" id="customerEmail" class="form-control py-2 rounded-1" placeholder="email@example.com" autocomplete="off" x-model="email" style="font-size: 14px;">
                                </div>
                            </div>

                            <!-- Device Model -->
                            <div class="mb-3">
                                <label for="deviceModel" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Device Model *</label>
                                <input type="text" id="deviceModel" class="form-control py-2 rounded-1" placeholder="e.g. iPhone 13, Samsung S22" required autocomplete="off" x-model="device" style="font-size: 14px;">
                            </div>

                            <!-- Problem Description -->
                            <div class="mb-4">
                                <label for="problemDescription" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Problem Description *</label>
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

                            <!-- Action Buttons -->
                            <div class="row g-2">
                                <div class="col-6">
                                    <button type="button" @click="saveBooking(false)" class="btn btn-secondary w-100 py-3 text-uppercase fw-bold rounded-1" style="font-size: 13px; letter-spacing: 0.5px; background-color: #5c5c5c; border-color: #5c5c5c; color: #ffffff;">
                                        Save
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button type="submit" class="btn btn-brand w-100 py-3 text-uppercase fw-bold rounded-1" style="font-size: 13px; letter-spacing: 0.5px;">
                                        Save and Print
                                    </button>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Hidden Container for thermal ticket layout during printing -->
    <div id="printArea" style="display: none;">
        <div class="receipt-header">
            <h2><?php echo htmlspecialchars(strtoupper($businessName)); ?></h2>
            <p style="font-weight: bold; font-size: 12px; margin-bottom: 4px;">REPAIR TICKET</p>
            <?php if (!empty($businessAddress)): ?>
                <p><?php echo htmlspecialchars($businessAddress); ?></p>
            <?php endif; ?>
            <?php if (!empty($businessContact)): ?>
                <p>Phone: <?php echo htmlspecialchars($businessContact); ?></p>
            <?php endif; ?>
            <?php if (!empty($businessEmail)): ?>
                <p>Email: <?php echo htmlspecialchars($businessEmail); ?></p>
            <?php endif; ?>
            <p id="receiptDate" style="margin-top: 4px;"></p>
            <p id="receiptTicketNum" style="font-weight: bold;"></p>
        </div>
        <div class="receipt-divider"></div>
        <div class="receipt-details">
            <p><strong>Customer:</strong> <span id="rCustomer"></span></p>
            <p><strong>Phone:</strong> <span id="rPhone"></span></p>
            <p><strong>Email:</strong> <span id="rEmail"></span></p>
            <p><strong>Device:</strong> <span id="rDevice"></span></p>
            <p><strong>Booked By:</strong> <span id="rUser"><?php echo htmlspecialchars($username ?: 'Guest'); ?></span></p>
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

    <!-- Redesigned Clean Footer -->
    <?php require_once __DIR__ . '/footer.php'; ?>

    <!-- Bootstrap 5 JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

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
    <script>
    console.log("=== TECHINBOX DIAGNOSTICS ===");
    console.log("Session User ID:", "<?php echo htmlspecialchars($_SESSION['user_id'] ?? 'NONE'); ?>");
    console.log("Session Business ID:", "<?php echo htmlspecialchars($_SESSION['business_id'] ?? 'NONE'); ?>");
    console.log("Session Tenant DB:", "<?php echo htmlspecialchars($_SESSION['tenant_db_name'] ?? 'NONE'); ?>");
    console.log("Tenant DB Connected:", "<?php echo $tenantDbConnected ? 'YES' : 'NO'; ?>");
    console.log("=============================");
    </script>
</body>
</html>
