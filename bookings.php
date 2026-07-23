<?php
// bookings.php
session_start();
require_once __DIR__ . '/db.php';

// Enforce login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
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
    <title>Booked Repair Jobs - TechInbox</title>
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

    <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-1 space-y-4"
          x-data="{
              search: '',
              jobs: [],
              loading: false,
              paymentJob: null,
              amountPaid: 0,
              paymentMethod: 'Cash',
              isProcessingPayment: false,
              showPaymentModal: false,
              
              async fetchBookings() {
                  this.loading = true;
                  try {
                      const query = encodeURIComponent(this.search);
                      const res = await fetch(`api.php?action=get_bookings&search=${query}`);
                      const result = await res.json();
                      if (result.status === 'success') {
                          this.jobs = result.data || [];
                      }
                  } catch (e) {
                      console.error('Fetch error:', e);
                  } finally {
                      this.loading = false;
                  }
              },

              updateStatus(job, newStatus) {
                  if (newStatus === 'Completed') {
                      const balance = parseFloat(job.total_quote) - parseFloat(job.deposit_paid);
                      if (balance > 0) {
                          this.paymentJob = job;
                          this.amountPaid = balance;
                          this.paymentMethod = 'Cash';
                          this.showPaymentModal = true;
                          return;
                      }
                  }
                  this.executeStatusUpdate(job.id, newStatus);
              },

              async executeStatusUpdate(bookingId, status) {
                  try {
                      const res = await fetch('api.php?action=update_booking_status', {
                          method: 'POST',
                          headers: { 'Content-Type': 'application/json' },
                          body: JSON.stringify({ id: bookingId, status: status })
                      });
                      const result = await res.json();
                      if (result.status === 'success') {
                          this.fetchBookings();
                      } else {
                          alert(result.message || 'Failed to update status.');
                      }
                  } catch (e) {
                      alert('Connection error.');
                  }
              },

              async collectAndComplete() {
                  this.isProcessingPayment = true;
                  try {
                      const res = await fetch('api.php?action=collect_balance', {
                          method: 'POST',
                          headers: { 'Content-Type': 'application/json' },
                          body: JSON.stringify({
                              booking_id: this.paymentJob.id,
                              amount: this.amountPaid,
                              payment_method: this.paymentMethod
                          })
                      });
                      const result = await res.json();
                      if (result.status === 'success') {
                          this.executeStatusUpdate(this.paymentJob.id, 'Completed');
                          this.showPaymentModal = false;
                      } else {
                          alert(result.message || 'Payment processing failed.');
                      }
                  } catch (e) {
                      alert('Connection error.');
                  } finally {
                      this.isProcessingPayment = false;
                  }
              },

              completeWithoutPayment() {
                  this.executeStatusUpdate(this.paymentJob.id, 'Completed');
                  this.showPaymentModal = false;
              },

              cancelStatusChange() {
                  this.fetchBookings();
                  this.showPaymentModal = false;
              },

              printReceipt(job) {
                  document.getElementById('rCustomer').textContent = job.customer_name;
                  document.getElementById('rPhone').textContent = job.phone_number;
                  document.getElementById('rEmail').textContent = job.email || 'N/A';
                  document.getElementById('rDevice').textContent = job.device_model;
                  document.getElementById('rFault').textContent = job.problem_description;
                  document.getElementById('rQuote').textContent = '€' + parseFloat(job.total_quote).toFixed(2);
                  document.getElementById('rDeposit').textContent = '€' + parseFloat(job.deposit_paid).toFixed(2);
                  
                  const balance = Math.max(0, parseFloat(job.total_quote) - parseFloat(job.deposit_paid));
                  document.getElementById('rBalance').textContent = '€' + balance.toFixed(2);
                  
                  const dateObj = new Date(job.created_at || Date.now());
                  const dateStr = dateObj.toLocaleDateString() + ' ' + dateObj.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                  
                  document.getElementById('receiptDate').textContent = 'Date: ' + dateStr;
                  document.getElementById('receiptTicketNum').textContent = 'Ticket #: ' + job.ticket_id;
                  
                  window.print();
              },

              init() {
                  this.fetchBookings();
              }
          }">
        <?php require __DIR__ . '/nav_buttons.php'; ?>

        <!-- Header Panel -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[#242424] tracking-tight">📋 Booked Repair Jobs</h1>
            </div>
            
            <!-- Filters -->
            <div class="flex items-center gap-2">
                <input type="text" x-model="search" @input.debounce.300ms="fetchBookings()" class="w-full sm:w-64 px-3 py-1.5 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]" placeholder="Search Customer, Phone, Ticket...">
            </div>
        </div>

        <!-- Bookings Table Card -->
        <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-xs overflow-hidden">
            <div x-show="loading" class="text-center py-12">
                <span class="animate-spin text-xl text-[#00a4ef] d-block mb-2">🌀</span>
                <p class="text-xs text-[#5c5c5c]">Loading bookings...</p>
            </div>

            <div x-show="!loading && jobs.length === 0" class="text-center py-12">
                <span class="text-3xl mb-2 block">📂</span>
                <h3 class="text-sm font-bold text-[#5c5c5c] mb-1">No Bookings Found</h3>
                <p class="text-xs text-[#5c5c5c]">Try matching a different keyword or create a new booking first.</p>
            </div>

            <div x-show="!loading && jobs.length > 0" class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="bg-[#fafafa] border-b border-[#e0e0e0] text-xs font-bold uppercase tracking-wider text-[#5c5c5c]">
                            <th class="px-2 py-1.5">Ticket ID</th>
                            <th class="px-2 py-1.5">Customer Name</th>
                            <th class="px-2 py-1.5">Device Detail</th>
                            <th class="px-2 py-1.5 w-44">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e0e0e0]">
                        <template x-for="job in jobs" :key="job.id">
                            <tr @click="window.location.href='customer_detail.php?id=' + job.id"
                                class="hover:bg-[#f9f9f9] transition-colors cursor-pointer"
                                :class="job.status === 'Completed' ? 'bg-[#fafafa] opacity-75' : ''">
                                 <td class="px-2 py-1.5 font-mono font-bold text-sm text-[#242424]" x-text="job.ticket_id"></td>
                                 <td class="px-2 py-1.5 font-bold text-sm text-[#00a4ef] hover:underline" x-text="job.customer_name"></td>
                                 <td class="px-2 py-1.5 font-medium text-sm text-[#242424]" x-text="job.device_model"></td>
                                 <td class="px-2 py-1.5" @click.stop>
                                      <select class="w-full px-2 py-1 text-xs font-semibold border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]" :value="job.status" @change="updateStatus(job, $event.target.value)">
                                          <option value="Pending">Pending</option>
                                          <option value="Processing">Processing</option>
                                          <option value="Completed">Completed</option>
                                      </select>
                                 </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Collect Payment & Complete Modal Dialog -->
        <div x-show="showPaymentModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" style="display: none;">
            <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-xl w-full max-w-md p-6 space-y-4" @click.outside="cancelStatusChange()">
                <div class="border-b border-[#e0e0e0] pb-3">
                    <h3 class="text-sm font-bold text-[#242424]">💰 Collect Remaining Balance</h3>
                </div>
                <div class="text-center py-2">
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c]">Amount Due</span>
                    <span class="text-3xl font-bold text-[#7fba00]" x-text="'€' + parseFloat(amountPaid).toFixed(2)"></span>
                </div>
                
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Payment Method</label>
                    <select class="w-full px-3 py-2 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]" x-model="paymentMethod">
                        <option value="Cash">💵 Cash</option>
                        <option value="Card">💳 Card</option>
                    </select>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-[#e0e0e0]">
                    <button type="button" class="py-1.5 px-3 bg-white border border-[#e0e0e0] hover:bg-[#f3f3f3] text-[#242424] text-xs font-semibold rounded-[4px]" @click="cancelStatusChange()" :disabled="isProcessingPayment">Cancel</button>
                    <div class="flex gap-2">
                        <button type="button" class="py-1.5 px-3 bg-red-50 hover:bg-red-100 text-[#f25022] text-xs font-semibold rounded-[4px]" @click="completeWithoutPayment()" :disabled="isProcessingPayment">Skip Payment</button>
                        <button type="button" class="py-1.5 px-3 bg-[#00a4ef] hover:bg-[#0086c4] text-white text-xs font-semibold rounded-[4px] shadow-xs" @click="collectAndComplete()" :disabled="isProcessingPayment">
                            <span x-show="!isProcessingPayment">Collect & Complete</span>
                            <span x-show="isProcessingPayment" class="animate-spin text-xs">🌀</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <!-- Receipt Print Template (Hidden standard 80mm format) -->
    <div id="printTicketArea" style="display: none;">
        <div style="text-align: center; margin-bottom: 8px;">
            <h3 style="margin: 0; font-size: 1.25em; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo htmlspecialchars($businessName); ?></h3>
            <p style="margin: 1px 0 0 0; font-size: 0.85em; color: #333;">
                <?php echo htmlspecialchars($businessAddress); ?> 
                <?php if ($businessContact): ?> | Ph: <?php echo htmlspecialchars($businessContact); ?><?php endif; ?>
            </p>
        </div>
        
        <div style="border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 4px 0; margin-bottom: 6px; font-size: 0.85em; display: flex; justify-content: space-between;">
            <span id="receiptTicketNum" style="font-weight: bold;">Ticket #: </span>
            <span id="receiptDate">Date: </span>
        </div>

        <div style="margin-bottom: 6px; font-size: 0.85em; line-height: 1.3;">
            <div style="display: flex; margin-bottom: 2px;">
                <span style="width: 55px; font-weight: bold; flex-shrink: 0;">Client:</span>
                <span id="rCustomer" style="font-weight: 550;"></span>
            </div>
            <div style="display: flex; margin-bottom: 2px;">
                <span style="width: 55px; font-weight: bold; flex-shrink: 0;">Phone:</span>
                <span id="rPhone"></span>
            </div>
            <div style="display: none;">
                <span style="width: 55px; font-weight: bold; flex-shrink: 0;">Email:</span>
                <span id="rEmail"></span>
            </div>
        </div>

        <div style="border-top: 1px dashed #000; padding-top: 4px; margin-bottom: 6px; font-size: 0.85em; line-height: 1.3;">
            <div style="display: flex; margin-bottom: 2px;">
                <span style="width: 55px; font-weight: bold; flex-shrink: 0;">Device:</span>
                <span id="rDevice" style="font-weight: 550;"></span>
            </div>
            <div style="display: none;">
                <span style="width: 55px; font-weight: bold; flex-shrink: 0;">Problem:</span>
                <span id="rFault"></span>
            </div>
        </div>

        <div style="border-top: 1px dashed #000; padding-top: 5px; margin-bottom: 8px; font-size: 0.9em;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 2px;">
                <span>Total Quote:</span>
                <span id="rQuote" style="font-weight: 550;"></span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 2px;">
                <span>Deposit Paid:</span>
                <span id="rDeposit" style="font-weight: 550;"></span>
            </div>
            <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.1em; border-top: 1px dashed #000; padding-top: 3px; margin-top: 3px;">
                <span>Remaining Balance:</span>
                <span id="rBalance"></span>
            </div>
        </div>
    </div>

    <!-- Standard Footer -->
    <?php require_once __DIR__ . '/footer.php'; ?>

    <!-- Print styling scoped for printing -->
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #printTicketArea, #printTicketArea * {
                visibility: visible;
            }
             #printTicketArea {
                  display: block !important;
                  position: absolute;
                  left: 0;
                  top: 0;
                  width: 80mm;
                  font-family: <?php echo $printerFontFamily; ?> !important;
                  color: #000;
                  background: #fff;
                  padding: 10px;
                  font-size: <?php echo $printerFontSize; ?>px !important;
                  line-height: 1.35;
              }
            aside, header, main, footer {
                display: none !important;
            }
        }
    </style>
</body>
</html>
