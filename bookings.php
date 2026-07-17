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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booked Repair Jobs - TechInbox</title>
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
            border-radius: 0;
        }

        .table-responsive {
            border-radius: 0;
        }

        /* Remove border radius universally on this page */
        .card, .table-responsive, .form-control, .form-select, .btn, .modal-content, .badge {
            border-radius: 0 !important;
        }

        .table thead th {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            color: var(--text-secondary);
            background-color: #fcfcfc;
            border-bottom: 1px solid var(--card-border);
            padding: 12px 16px;
        }

        .table tbody td {
            font-size: 13.5px;
            padding: 16px;
            border-bottom: 1px solid #f0f0f0;
        }

        /* Modal dialog Fluent overrides */
        .modal-content {
            border-radius: 6px;
            border: 1px solid var(--card-border);
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }

        .modal-header {
            border-bottom: 1px solid #f0f0f0;
        }

        .modal-footer {
            border-top: 1px solid #f0f0f0;
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
                position: absolute;
                left: 0;
                top: 0;
                width: 80mm; /* Standard receipt roll width */
                font-family: 'Courier New', Courier, monospace;
                color: #000;
                background: #fff;
                padding: 10px;
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
              jobs: [],
              search: '',
              statusFilter: '',
              loading: false,

              // Collect Payment validation states
              paymentJob: null,
              payAmount: 0,
              payMethod: 'Cash',
              payRef: '',
              isProcessingPayment: false,

              async fetchBookings() {
                  this.loading = true;
                  try {
                      const res = await fetch('api.php?action=get_bookings&status=' + this.statusFilter + '&search=' + encodeURIComponent(this.search));
                      const result = await res.json();
                      if (result.status === 'success') {
                          this.jobs = result.data;
                      }
                  } catch (e) {
                      console.error('Error loading bookings:', e);
                  } finally {
                      this.loading = false;
                  }
              },

              async updateStatus(job, newStatus) {
                  // Intercept Completed status if balance is due
                  if (newStatus === 'Completed' && parseFloat(job.balance_due) > 0) {
                      this.paymentJob = job;
                      this.payAmount = parseFloat(job.balance_due);
                      this.payMethod = 'Cash';
                      this.payRef = '';
                      const modal = new bootstrap.Modal(document.getElementById('collectPaymentModal'));
                      modal.show();
                      return;
                  }
                  this.executeStatusUpdate(job.id, newStatus);
              },

              async executeStatusUpdate(jobId, newStatus) {
                  try {
                      const res = await fetch('api.php?action=update_booking', {
                          method: 'POST',
                          headers: { 'Content-Type': 'application/json' },
                          body: JSON.stringify({
                              id: jobId,
                              status: newStatus,
                              status_only: true
                          })
                      });
                      const result = await res.json();
                      if (result.status === 'success') {
                          this.fetchBookings();
                      } else {
                          alert(result.message || 'Error updating status');
                          this.fetchBookings();
                      }
                  } catch (e) {
                      alert('Connection error. Please try again.');
                      this.fetchBookings();
                  }
              },

              async recordPaymentAndComplete() {
                  if (parseFloat(this.payAmount) <= 0) {
                      alert('Please enter a valid amount.');
                      return;
                  }
                  this.isProcessingPayment = true;
                  try {
                      const payRes = await fetch('api.php?action=add_payment', {
                          method: 'POST',
                          headers: { 'Content-Type': 'application/json' },
                          body: JSON.stringify({
                              booking_id: this.paymentJob.id,
                              amount: parseFloat(this.payAmount),
                              payment_method: this.payMethod,
                              payment_type: 'Final Balance',
                              reference_code: this.payRef
                          })
                      });
                      const payResult = await payRes.json();
                      if (payResult.status === 'success') {
                          await this.executeStatusUpdate(this.paymentJob.id, 'Completed');
                          bootstrap.Modal.getInstance(document.getElementById('collectPaymentModal')).hide();
                      } else {
                          alert(payResult.message || 'Failed to record payment.');
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
                
                <select x-model="statusFilter" @change="fetchBookings()" class="form-select form-select-sm" style="width: 140px;">
                    <option value="">All Statuses</option>
                    <option value="Pending">Pending</option>
                    <option value="Processing">Processing</option>
                    <option value="Completed">Completed</option>
                </select>
            </div>
        </div>

        <!-- Bookings Table Card -->
        <div class="card shadow-sm border-1 bg-white flex-grow-1">
            <div x-show="loading" class="text-center py-5">
                <div class="spinner-border text-secondary spinner-border-sm" role="status"></div>
                <span class="ms-2 text-muted small">Loading bookings data...</span>
            </div>

            <div x-show="!loading && jobs.length === 0" class="text-center py-5">
                <span class="fs-2 d-block mb-2">📂</span>
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
                                     <div class="text-muted small" style="font-size: 12px;" x-text="job.phone_number"></div>
                                 </td>
                                <td>
                                    <span class="fw-semibold text-dark" x-text="job.device_model"></span>
                                </td>
                                <td>
                                     <select class="form-select form-select-sm" :value="job.status" @change="updateStatus(job, $event.target.value)" style="font-size: 12.5px; border-radius: 4px;">
                                         <option value="Pending">Pending</option>
                                         <option value="Processing">Processing</option>
                                         <option value="Completed">Completed</option>
                                     </select>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <button class="btn btn-sm btn-outline-secondary" style="font-size: 12px; border-radius: 4px;" @click="printReceipt(job)">
                                            🖨️ Print
                                        </button>
                                         <a :href="'customer_detail.php?id=' + job.id" class="btn btn-sm btn-outline-primary" style="font-size: 12px; border-radius: 4px; display: inline-flex; align-items: center; text-decoration: none;">
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
                        <button type="button" class="btn-close" @click="cancelStatusChange()" aria-label="Close"></button>
                    </div>
                    <form @submit.prevent="recordPaymentAndComplete()">
                        <div class="modal-body">
                            <p class="small text-muted mb-3">
                                This job has an outstanding balance of <strong class="text-danger">€<span x-text="paymentJob ? parseFloat(paymentJob.balance_due).toFixed(2) : '0.00'"></span></strong>. 
                                Please record the customer's payment to complete the job.
                            </p>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-secondary">Amount to Collect (€)</label>
                                    <input type="number" step="0.01" x-model="payAmount" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold text-secondary">Payment Method</label>
                                    <select x-model="payMethod" class="form-select form-select-sm">
                                        <option value="Cash">Cash</option>
                                        <option value="Card BOI">Card BOI</option>
                                        <option value="Card Fixed">Card Fixed</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold text-secondary">Reference Code</label>
                                    <input type="text" x-model="payRef" class="form-control form-control-sm" placeholder="e.g. Terminal Auth">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer d-flex justify-content-between">
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="cancelStatusChange()">Cancel</button>
                            <div class="d-inline-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-danger" @click="completeWithoutPayment()">Complete Without Payment</button>
                                <button type="submit" class="btn btn-sm btn-success text-white" style="background-color: var(--brand-green); border-color: var(--brand-green);" :disabled="isProcessingPayment">
                                    <span x-show="!isProcessingPayment">Record & Complete</span>
                                    <span x-show="isProcessingPayment" class="spinner-border spinner-border-sm" role="status"></span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Receipt Print Template (Hidden standard 80mm format) -->
        <div id="printTicketArea" class="d-none d-print-block">
            <div style="text-align: center; border-bottom: 1px dashed #000; padding-bottom: 10px; margin-bottom: 10px;">
                <h3 style="margin: 0; font-size: 16px; font-weight: bold;"><?php echo htmlspecialchars($businessName); ?></h3>
                <?php if ($businessAddress): ?>
                    <p style="margin: 3px 0 0 0; font-size: 11px;"><?php echo htmlspecialchars($businessAddress); ?></p>
                <?php endif; ?>
                <?php if ($businessContact): ?>
                    <p style="margin: 2px 0 0 0; font-size: 11px;">Ph: <?php echo htmlspecialchars($businessContact); ?></p>
                <?php endif; ?>
            </div>
            
            <div style="font-size: 11px; margin-bottom: 8px;">
                <span id="receiptDate">Date: </span><br>
                <span id="receiptTicketNum">Ticket #: </span>
            </div>

            <div style="border-bottom: 1px dashed #000; padding-bottom: 5px; margin-bottom: 8px; font-size: 11px;">
                <strong>CUSTOMER DETAILS</strong><br>
                Name: <span id="rCustomer"></span><br>
                Phone: <span id="rPhone"></span><br>
                Email: <span id="rEmail"></span>
            </div>

            <div style="border-bottom: 1px dashed #000; padding-bottom: 5px; margin-bottom: 8px; font-size: 11px;">
                <strong>DEVICE & FAULT</strong><br>
                Device: <span id="rDevice"></span><br>
                Problem: <span id="rFault"></span>
            </div>

            <div style="text-align: right; font-size: 12px; line-height: 1.5;">
                Quote: <span id="rQuote"></span><br>
                Deposit: <span id="rDeposit"></span><br>
                <div style="border-top: 1px dashed #000; margin-top: 4px; font-weight: bold; font-size: 13px;">
                    Balance: <span id="rBalance"></span>
                </div>
            </div>

            <div style="text-align: center; margin-top: 20px; font-size: 10px; border-top: 1px dashed #000; padding-top: 8px;">
                Thank you for choosing <?php echo htmlspecialchars($businessName); ?>!<br>
                Please retain this ticket to collect your device.
            </div>
        </div>
    </main>

    <!-- Standard Footer -->
    <?php require_once __DIR__ . '/footer.php'; ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
