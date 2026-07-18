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

// Fetch active user details
$stmtUser = $masterDb->prepare("SELECT name, contact, email, address FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$profile = $stmtUser->fetch();
$businessName = !empty($profile['name']) ? $profile['name'] : 'Store';
$businessContact = !empty($profile['contact']) ? $profile['contact'] : '';
$businessEmail = !empty($profile['email']) ? $profile['email'] : '';
$businessAddress = !empty($profile['address']) ? $profile['address'] : '';

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

        /* Table-specific styles (all global resets handled by header.php) */
        .table thead th {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            color: var(--text-secondary);
            background-color: #f5f5f5;
            border-bottom: 1px solid var(--card-border) !important;
        }

        .table tbody td {
            font-size: 13.5px;
            border-bottom: 1px solid var(--card-border) !important;
        }

        .modal-header {
            border-bottom: 1px solid var(--card-border) !important;
        }

        .modal-footer {
            border-top: 1px solid var(--card-border) !important;
        }

        #printTicketArea {
            display: none;
        }

        /* Receipt print format overrides */
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
                  width: 80mm; /* Standard receipt roll width */
                  font-family: <?php echo $printerFontFamily; ?> !important;
                  color: #000;
                  background: #fff;
                  padding: 10px;
                  font-size: <?php echo $printerFontSize; ?>px !important;
                  line-height: 1.35;
              }
            aside, header, main, footer, .modal {
                display: none !important;
            }
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Header Navigation -->
    <?php require_once __DIR__ . '/header.php'; ?>

    <main class="container-fluid px-2 px-md-4 py-3 py-md-4 flex-grow-1"
          x-data="{
              search: '',
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

              updateStatus(job, newStatus) {
                  if (newStatus === 'Completed') {
                      // Check if balance remains
                      const balance = parseFloat(job.total_quote) - parseFloat(job.deposit_paid);
                      if (balance > 0) {
                          // Trigger Collect Payment Modal
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
                  document.getElementById('rQuote').textContent = '€' + parseFloat(job.total_quote).toFixed(2);
                  document.getElementById('rDeposit').textContent = '€' + parseFloat(job.deposit_paid).toFixed(2);
                  
                  const balance = Math.max(0, parseFloat(job.total_quote) - parseFloat(job.deposit_paid));
                  document.getElementById('rBalance').textContent = '€' + balance.toFixed(2);
                  
                  // Date format
                  const dateObj = new Date(job.created_at || Date.now());
                  const dateStr = dateObj.toLocaleDateString() + ' ' + dateObj.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                  
                  document.getElementById('receiptDate').textContent = 'Date: ' + dateStr;
                  document.getElementById('receiptTicketNum').textContent = 'Ticket #: ' + job.ticket_id;
                  
                  // Trigger Print dialog
                  window.print();
              },

              init() {
                  this.fetchBookings();
              }
          }">

        <!-- Header Panel -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-0">📋 Booked Repair Jobs</h1>
            </div>
            
            <!-- Filters -->
            <div class="d-flex flex-wrap align-items-center gap-2">
                <input type="text" x-model="search" @input.debounce.300ms="fetchBookings()" class="form-control form-control-sm" style="max-width: 230px;" placeholder="Search Customer, Phone, Ticket...">
            </div>
        </div>

        <!-- Bookings Table Card -->
        <div class="card shadow-sm border p-3 p-md-4" style="background-color: #ffffff !important; border-radius: 6px !important; border-color: #e0e0e0 !important;">
            <div x-show="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="text-muted small mt-2 mb-0">Loading bookings...</p>
            </div>

            <div x-show="!loading && jobs.length === 0" class="text-center py-5">
                <span class="fs-2 mb-2 d-block">📂</span>
                <h3 class="h6 fw-bold text-secondary mb-1">No Bookings Found</h3>
                <p class="text-muted small mb-0">Try matching a different keyword or create a new booking first.</p>
            </div>

            <div x-show="!loading && jobs.length > 0" class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>Customer Name</th>
                            <th>Device Detail</th>
                            <th style="width: 160px;">Status</th>
                            <th class="text-end" style="width: 160px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="job in jobs" :key="job.id">
                            <tr :style="job.status === 'Completed' ? 'background-color: #fafafa; opacity: 0.85;' : ''">
                                 <td>
                                     <span class="fw-bold" style="font-size: 13px; font-family: monospace;" x-text="job.ticket_id"></span>
                                 </td>
                                 <td>
                                     <div>
                                         <a :href="'customer_detail.php?id=' + job.id" class="fw-bold text-decoration-none text-primary" x-text="job.customer_name"></a>
                                     </div>
                                 </td>
                                <td>
                                    <span class="fw-semibold text-dark" x-text="job.device_model"></span>
                                </td>
                                <td>
                                     <select class="form-select form-select-sm" :value="job.status" @change="updateStatus(job, $event.target.value)" style="font-size: 12.5px;">
                                         <option value="Pending">Pending</option>
                                         <option value="Processing">Processing</option>
                                         <option value="Completed">Completed</option>
                                     </select>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <button class="btn btn-sm btn-outline-secondary" style="font-size: 12px;" @click="printReceipt(job)">
                                            🖨️ Print
                                        </button>
                                         <a :href="'customer_detail.php?id=' + job.id" class="btn btn-sm btn-outline-primary" style="font-size: 12px; display: inline-flex; align-items: center; text-decoration: none;">
                                             ✏️ Edit
                                         </a>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Collect Payment & Complete Modal Dialog -->
        <div class="modal fade" id="collectPaymentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="collectPaymentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-dark" id="collectPaymentModalLabel">💰 Collect Remaining Balance</h5>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3 text-center">
                            <span class="small text-muted d-block uppercase font-weight-bold">Amount Due</span>
                            <span class="fs-2 fw-bold text-success" x-text="'€' + parseFloat(amountPaid).toFixed(2)"></span>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Payment Method</label>
                            <select class="form-select" x-model="paymentMethod">
                                <option value="Cash">💵 Cash</option>
                                <option value="Card">💳 Card</option>
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
