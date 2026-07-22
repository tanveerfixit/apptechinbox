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
    
    <!-- Roboto Font & Bootstrap 5 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        :root {
            --bg-color: #f3f3f3;
            --card-bg: #ffffff;
            --card-border: #e0e0e0;
            --text-primary: #242424;
            --text-secondary: #5c5c5c;
            --brand-teal: #008272;
            --brand-blue: #00a4ef;
            --brand-green: #7fba00;
            --brand-red: #f25022;
            --brand-yellow: #ffb900;
            --font-family: 'Roboto', 'Segoe UI', system-ui, sans-serif;
            --hover-bg: #f2f6fc;
            --active-tab: #008272;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-primary);
            font-family: var(--font-family);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Gmail-style search bar */
        .search-container {
            max-width: 720px;
            margin: 0 auto;
            width: 100%;
        }
        .search-bar {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 28px;
            padding: 8px 18px;
            font-size: 14px;
            width: 100%;
            outline: none;
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
            color: var(--text-primary);
        }
        .search-bar::placeholder { color: #9a9a9a; }
        .search-bar:focus {
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
            border-color: transparent;
        }

        /* Tab filters */
        .tab-filters {
            display: flex;
            gap: 0;
            border-bottom: 1px solid var(--card-border);
            margin-bottom: 0;
            background: var(--card-bg);
            padding: 0 16px;
            border-radius: 8px 8px 0 0;
        }
        .tab-btn {
            background: none;
            border: none;
            padding: 12px 20px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            cursor: pointer;
            position: relative;
            letter-spacing: 0.2px;
            transition: color 0.15s ease;
            white-space: nowrap;
        }
        .tab-btn:hover { color: var(--text-primary); }
        .tab-btn.active {
            color: var(--active-tab);
        }
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 16px;
            right: 16px;
            height: 3px;
            background: var(--active-tab);
            border-radius: 3px 3px 0 0;
        }

        /* Inbox list container */
        .inbox-list {
            background: var(--card-bg);
            border-radius: 0 0 8px 8px;
            overflow: hidden;
        }

        /* Gmail-style row */
        .inbox-row {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            border-bottom: 1px solid #f1f1f1;
            cursor: pointer;
            transition: background-color 0.1s ease, box-shadow 0.1s ease;
            text-decoration: none;
            color: inherit;
            gap: 12px;
            min-height: 52px;
        }
        .inbox-row:hover {
            background-color: var(--hover-bg);
            box-shadow: inset 0 -1px 0 #e8e8e8;
            z-index: 1;
        }
        .inbox-row:last-child { border-bottom: none; }
        .inbox-row.completed-row {
            opacity: 0.6;
            background-color: #fafafa;
        }
        .inbox-row.completed-row:hover {
            opacity: 0.85;
            background-color: var(--hover-bg);
        }

        /* Status dot */
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .status-dot.pending { background: var(--brand-yellow); }
        .status-dot.processing { background: var(--brand-blue); }
        .status-dot.completed { background: var(--brand-green); }

        /* Row sections */
        .inbox-sender {
            width: 180px;
            flex-shrink: 0;
            font-size: 13.5px;
            font-weight: 500;
            color: var(--text-primary);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .inbox-row.completed-row .inbox-sender {
            font-weight: 400;
            color: var(--text-secondary);
        }

        .inbox-subject {
            flex: 1;
            min-width: 0;
            display: flex;
            align-items: baseline;
            gap: 6px;
            overflow: hidden;
        }
        .inbox-device {
            font-size: 13.5px;
            font-weight: 400;
            color: var(--text-primary);
            white-space: nowrap;
        }
        .inbox-snippet {
            font-size: 13px;
            color: #999;
            font-weight: 300;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .inbox-separator {
            color: #ccc;
            font-size: 12px;
            flex-shrink: 0;
        }

        .inbox-ticket {
            font-size: 11px;
            color: var(--text-secondary);
            font-family: 'Roboto Mono', monospace;
            background: #f3f3f3;
            padding: 2px 8px;
            border-radius: 10px;
            flex-shrink: 0;
            letter-spacing: 0.3px;
        }

        .inbox-date {
            font-size: 12px;
            color: var(--text-secondary);
            flex-shrink: 0;
            text-align: right;
            min-width: 70px;
        }

        /* Status dropdown (click-stop zone) */
        .status-select {
            font-size: 11.5px;
            padding: 3px 8px;
            border: 1px solid var(--card-border);
            border-radius: 14px;
            background: #fff;
            color: var(--text-secondary);
            cursor: pointer;
            outline: none;
            flex-shrink: 0;
            min-width: 105px;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%235c5c5c'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 8px center;
            padding-right: 22px;
        }
        .status-select:focus {
            border-color: var(--brand-teal);
        }

        /* Counter badges */
        .tab-count {
            font-size: 11px;
            background: #eee;
            color: var(--text-secondary);
            padding: 1px 7px;
            border-radius: 10px;
            margin-left: 5px;
            font-weight: 400;
        }
        .tab-btn.active .tab-count {
            background: rgba(0,130,114,0.1);
            color: var(--active-tab);
        }

        /* Empty state */
        .empty-state {
            padding: 60px 20px;
            text-align: center;
        }
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 12px;
            opacity: 0.4;
        }

        /* Loading */
        .loading-state {
            padding: 50px 20px;
            text-align: center;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .inbox-sender { width: 120px; font-size: 13px; }
            .inbox-ticket { display: none; }
            .inbox-snippet { display: none; }
            .inbox-row { padding: 10px 12px; gap: 8px; }
            .tab-btn { padding: 10px 12px; font-size: 12px; }
            .status-select { min-width: 90px; font-size: 11px; }
        }
        @media (max-width: 480px) {
            .inbox-sender { width: 100px; }
            .inbox-device { font-size: 12.5px; }
            .inbox-date { font-size: 11px; min-width: 55px; }
        }

        /* Modal */
        .modal-header { border-bottom: 1px solid var(--card-border) !important; }
        .modal-footer { border-top: 1px solid var(--card-border) !important; }

        /* Print receipt */
        #printTicketArea { display: none; }
        @media print {
            body * { visibility: hidden; }
            #printTicketArea, #printTicketArea * { visibility: visible; }
            #printTicketArea {
                display: block !important;
                position: absolute;
                left: 0; top: 0;
                width: 80mm;
                font-family: <?php echo $printerFontFamily; ?> !important;
                color: #000;
                background: #fff;
                padding: 10px;
                font-size: <?php echo $printerFontSize; ?>px !important;
                line-height: 1.35;
            }
            aside, header, main, footer, .modal { display: none !important; }
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Header Navigation -->
    <?php require_once __DIR__ . '/header.php'; ?>

    <main class="container-fluid px-2 px-md-4 py-3 py-md-4 flex-grow-1"
          x-data="{
              search: '',
              activeTab: 'all',
              jobs: [],
              loading: false,
              paymentJob: null,
              amountPaid: 0,
              paymentMethod: 'Cash',
              isProcessingPayment: false,
              
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

              get filteredJobs() {
                  if (this.activeTab === 'all') return this.jobs;
                  const statusMap = { pending: 'Pending', processing: 'Processing', completed: 'Completed' };
                  return this.jobs.filter(j => j.status === statusMap[this.activeTab]);
              },

              get counts() {
                  return {
                      all: this.jobs.length,
                      pending: this.jobs.filter(j => j.status === 'Pending').length,
                      processing: this.jobs.filter(j => j.status === 'Processing').length,
                      completed: this.jobs.filter(j => j.status === 'Completed').length
                  };
              },

              formatDate(dateStr) {
                  if (!dateStr) return '';
                  const d = new Date(dateStr);
                  const now = new Date();
                  const diff = now - d;
                  const mins = Math.floor(diff / 60000);
                  const hrs = Math.floor(diff / 3600000);
                  const days = Math.floor(diff / 86400000);
                  
                  if (mins < 1) return 'Just now';
                  if (mins < 60) return mins + ' min ago';
                  if (hrs < 24 && d.getDate() === now.getDate()) return d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                  if (days < 2) return 'Yesterday';
                  if (days < 7) return d.toLocaleDateString([], {weekday: 'short'});
                  if (d.getFullYear() === now.getFullYear()) return d.toLocaleDateString([], {day: 'numeric', month: 'short'});
                  return d.toLocaleDateString([], {day: 'numeric', month: 'short', year: 'numeric'});
              },

              updateStatus(job, newStatus) {
                  if (newStatus === 'Completed') {
                      const balance = parseFloat(job.total_quote) - parseFloat(job.deposit_paid);
                      if (balance > 0) {
                          this.paymentJob = job;
                          this.amountPaid = balance;
                          this.paymentMethod = 'Cash';
                          const modal = new bootstrap.Modal(document.getElementById('collectPaymentModal'));
                          modal.show();
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
                          bootstrap.Modal.getInstance(document.getElementById('collectPaymentModal')).hide();
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
                  bootstrap.Modal.getInstance(document.getElementById('collectPaymentModal')).hide();
              },

              cancelStatusChange() {
                  this.fetchBookings();
                  bootstrap.Modal.getInstance(document.getElementById('collectPaymentModal')).hide();
              },

              printReceipt(job) {
                  document.getElementById('rCustomer').textContent = job.customer_name;
                  document.getElementById('rPhone').textContent = job.phone_number;
                  document.getElementById('rEmail').textContent = job.email || 'N/A';
                  document.getElementById('rDevice').textContent = job.device_model;
                  document.getElementById('rFault').textContent = job.problem_description;
                  document.getElementById('rQuote').textContent = 'â‚¬' + parseFloat(job.total_quote).toFixed(2);
                  document.getElementById('rDeposit').textContent = 'â‚¬' + parseFloat(job.deposit_paid).toFixed(2);
                  
                  const balance = Math.max(0, parseFloat(job.total_quote) - parseFloat(job.deposit_paid));
                  document.getElementById('rBalance').textContent = 'â‚¬' + balance.toFixed(2);
                  
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

        <!-- Gmail-style Search Bar -->
        <div class="search-container mb-3">
            <input type="text" 
                   x-model="search" 
                   @input.debounce.300ms="fetchBookings()" 
                   class="search-bar" 
                   placeholder="ðŸ”  Search repairs â€” customer, phone, ticket...">
        </div>

        <!-- Inbox Card -->
        <div style="max-width: 1100px; margin: 0 auto;">
            
            <!-- Tab Filters -->
            <div class="tab-filters">
                <button class="tab-btn" :class="{ active: activeTab === 'all' }" @click="activeTab = 'all'">
                    All<span class="tab-count" x-text="counts.all"></span>
                </button>
                <button class="tab-btn" :class="{ active: activeTab === 'pending' }" @click="activeTab = 'pending'">
                    ðŸŸ¡ Pending<span class="tab-count" x-text="counts.pending"></span>
                </button>
                <button class="tab-btn" :class="{ active: activeTab === 'processing' }" @click="activeTab = 'processing'">
                    ðŸ”µ Processing<span class="tab-count" x-text="counts.processing"></span>
                </button>
                <button class="tab-btn" :class="{ active: activeTab === 'completed' }" @click="activeTab = 'completed'">
                    ðŸŸ¢ Completed<span class="tab-count" x-text="counts.completed"></span>
                </button>
            </div>

            <!-- Inbox List -->
            <div class="inbox-list">

                <!-- Loading -->
                <div x-show="loading" class="loading-state">
                    <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                    <p class="text-muted small mt-2 mb-0">Loading repairs...</p>
                </div>

                <!-- Empty State -->
                <div x-show="!loading && filteredJobs.length === 0" class="empty-state">
                    <div class="empty-state-icon">ðŸ“­</div>
                    <h3 style="font-size: 16px; font-weight: 400; color: var(--text-secondary); margin-bottom: 4px;">Nothing here</h3>
                    <p style="font-size: 13px; color: #999; margin: 0;">No repair jobs match your current filter.</p>
                </div>

                <!-- Rows -->
                <template x-for="job in filteredJobs" :key="job.id">
                    <a :href="'customer_detail.php?id=' + job.id" 
                       class="inbox-row" 
                       :class="{ 'completed-row': job.status === 'Completed' }">
                        
                        <!-- Status Dot -->
                        <span class="status-dot" 
                              :class="{
                                  pending: job.status === 'Pending',
                                  processing: job.status === 'Processing',
                                  completed: job.status === 'Completed'
                              }"></span>

                        <!-- Customer Name -->
                        <span class="inbox-sender" x-text="job.customer_name"></span>

                        <!-- Device + Problem Snippet -->
                        <span class="inbox-subject">
                            <span class="inbox-device" x-text="job.device_model"></span>
                            <span class="inbox-separator">â€”</span>
                            <span class="inbox-snippet" x-text="job.problem_description"></span>
                        </span>

                        <!-- Ticket Badge -->
                        <span class="inbox-ticket" x-text="job.ticket_id"></span>

                        <!-- Status Dropdown (stops row navigation) -->
                        <span @click.prevent.stop>
                            <select class="status-select" 
                                    :value="job.status" 
                                    @change="updateStatus(job, $event.target.value)">
                                <option value="Pending">Pending</option>
                                <option value="Processing">Processing</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </span>

                        <!-- Date -->
                        <span class="inbox-date" x-text="formatDate(job.created_at)"></span>
                    </a>
                </template>

            </div>
        </div>

        <!-- Collect Payment & Complete Modal Dialog -->
        <div class="modal fade" id="collectPaymentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="collectPaymentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-medium text-dark" id="collectPaymentModalLabel">ðŸ’° Collect Remaining Balance</h5>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3 text-center">
                            <span class="small text-muted d-block">Amount Due</span>
                            <span class="fs-2 fw-medium text-success" x-text="'â‚¬' + parseFloat(amountPaid).toFixed(2)"></span>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small text-secondary">Payment Method</label>
                            <select class="form-select" x-model="paymentMethod">
                                <option value="Cash">ðŸ’µ Cash</option>
                                <option value="Card">ðŸ’³ Card</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="cancelStatusChange()" :disabled="isProcessingPayment">Cancel</button>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline-danger" @click="completeWithoutPayment()" :disabled="isProcessingPayment">Skip Payment</button>
                            <button type="button" class="btn btn-sm btn-primary text-white" @click="collectAndComplete()" :disabled="isProcessingPayment">
                                <span x-show="!isProcessingPayment">Collect & Complete</span>
                                <span x-show="isProcessingPayment" class="spinner-border spinner-border-sm"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <!-- Receipt Print Template (Hidden standard 80mm format) -->
    <div id="printTicketArea">
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

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
