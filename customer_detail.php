<?php
// customer_detail.php
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

// Retrieve booking details
$bookingId = intval($_GET['id'] ?? 0);
$customer = null;
$historyJobs = [];
$payments = [];

if ($bookingId && $db !== null && $tenantDbConnected) {
    try {
        // Fetch current customer profile info from this booking ID
        $stmt = $db->prepare("SELECT * FROM bookings WHERE id = ?");
        $stmt->execute([$bookingId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer) {
            // Fetch all jobs matching this customer's phone number to get full history
            $stmtHist = $db->prepare("SELECT * FROM bookings WHERE phone_number = ? ORDER BY created_at DESC");
            $stmtHist->execute([$customer['phone_number']]);
            $historyJobs = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

            // Fetch payment receipts ledger
            $stmtPay = $db->prepare("SELECT * FROM booking_payments WHERE booking_id = ? ORDER BY created_at ASC");
            $stmtPay->execute([$bookingId]);
            $payments = $stmtPay->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {}
}

if (!$customer) {
    header("Location: bookings.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Profile: <?php echo htmlspecialchars($customer['customer_name']); ?> - TechInbox</title>
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
            --brand-blue: #00a4ef;
            --brand-teal: #008272;
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

        .form-control:focus, .form-select:focus {
            border-color: var(--brand-teal) !important;
        }

        /* Print formatting overlay styles */
        @media print {
            body * {
                visibility: hidden;
            }
            #printPaymentReceiptArea, #printPaymentReceiptArea * {
                visibility: visible;
            }
            #printPaymentReceiptArea {
                position: absolute;
                left: 0;
                top: 0;
                width: 80mm;
                font-family: <?php echo $printerFontFamily; ?> !important;
                font-size: <?php echo $printerFontSize + 3; ?>px !important;
                line-height: 1.35;
            }
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Header Navigation -->
    <?php require_once __DIR__ . '/header.php'; ?>

    <main class="container-fluid px-2 px-md-4 py-3 py-md-4 flex-grow-1">
        <!-- Breadcrumb back link -->
        <div class="mb-3 d-print-none">
            <a href="bookings.php" class="text-decoration-none fw-semibold text-primary" style="font-size: 14px; color: var(--brand-blue) !important;">&larr; Back to Bookings</a>
        </div>

        <div class="row g-4"
             x-data="{
                 id: <?php echo $customer['id']; ?>,
                 name: '<?php echo htmlspecialchars($customer['customer_name'], ENT_QUOTES, 'UTF-8'); ?>',
                 phone: '<?php echo htmlspecialchars($customer['phone_number'], ENT_QUOTES, 'UTF-8'); ?>',
                 email: '<?php echo htmlspecialchars($customer['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>',
                 device: '<?php echo htmlspecialchars($customer['device_model'], ENT_QUOTES, 'UTF-8'); ?>',
                 fault: '<?php echo htmlspecialchars($customer['problem_description'], ENT_QUOTES, 'UTF-8'); ?>',
                 quote: '<?php echo htmlspecialchars($customer['total_quote'], ENT_QUOTES, 'UTF-8'); ?>',
                 deposit: '<?php echo htmlspecialchars($customer['deposit_paid'], ENT_QUOTES, 'UTF-8'); ?>',
                 status: '<?php echo htmlspecialchars($customer['status'], ENT_QUOTES, 'UTF-8'); ?>',
                 notes: '<?php echo htmlspecialchars($customer['notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?>',
                 isSaving: false,
                 successMsg: '',
                 errorMsg: '',

                 // Payments Ledger state
                 payments: <?php echo htmlspecialchars(json_encode($payments), ENT_QUOTES, 'UTF-8'); ?>,
                 payAmount: '<?php echo htmlspecialchars($customer['balance_due'], ENT_QUOTES, 'UTF-8'); ?>',
                 payMethod: 'Cash',
                 payType: 'Final Balance',
                 payRef: '',
                 isAddingPayment: false,
                 paySuccessMsg: '',
                 payErrorMsg: '',

                 async saveChanges() {
                     this.isSaving = true;
                     this.successMsg = '';
                     this.errorMsg = '';
                     try {
                         const res = await fetch('api.php?action=update_booking', {
                             method: 'POST',
                             headers: { 'Content-Type': 'application/json' },
                             body: JSON.stringify({
                                 id: this.id,
                                 name: this.name,
                                 phone: this.phone,
                                 email: this.email,
                                 device: this.device,
                                 fault: this.fault,
                                 quote: parseFloat(this.quote || 0),
                                 deposit: parseFloat(this.deposit || 0),
                                 status: this.status,
                                 notes: this.notes
                             })
                         });
                         const result = await res.json();
                         if (result.status === 'success') {
                             this.successMsg = 'Changes saved successfully!';
                             setTimeout(() => {
                                 window.location.reload();
                             }, 1000);
                         } else {
                             this.errorMsg = result.message || 'Failed to save changes.';
                         }
                     } catch(e) {
                         this.errorMsg = 'Network connection failed.';
                     } finally {
                         this.isSaving = false;
                     }
                 },

                 async collectPayment() {
                     if (parseFloat(this.payAmount) <= 0) {
                         this.payErrorMsg = 'Please enter a valid amount.';
                         return;
                     }
                     this.isAddingPayment = true;
                     this.paySuccessMsg = '';
                     this.payErrorMsg = '';
                     try {
                         const res = await fetch('api.php?action=add_payment', {
                             method: 'POST',
                             headers: { 'Content-Type': 'application/json' },
                             body: JSON.stringify({
                                 booking_id: this.id,
                                 amount: parseFloat(this.payAmount),
                                 payment_method: this.payMethod,
                                 payment_type: this.payType,
                                 reference_code: this.payRef
                             })
                         });
                         const result = await res.json();
                         if (result.status === 'success') {
                             this.paySuccessMsg = 'Payment recorded successfully!';
                             this.payments = result.data.payments;
                             this.deposit = result.data.deposit_paid;
                             this.payAmount = result.data.balance_due;
                             this.payRef = '';
                             setTimeout(() => {
                                 window.location.reload();
                             }, 1000);
                         } else {
                             this.payErrorMsg = result.message || 'Failed to add payment.';
                         }
                     } catch(e) {
                         this.payErrorMsg = 'Network error.';
                     } finally {
                         this.isAddingPayment = false;
                     }
                 },

                 printPaymentReceipt(pay) {
                     document.getElementById('pRecStore').textContent = '<?php echo htmlspecialchars($businessName, ENT_QUOTES, 'UTF-8'); ?>';
                     document.getElementById('pRecTicket').textContent = '<?php echo htmlspecialchars($customer['ticket_id'], ENT_QUOTES, 'UTF-8'); ?>';
                     document.getElementById('pRecCust').textContent = '<?php echo htmlspecialchars($customer['customer_name'], ENT_QUOTES, 'UTF-8'); ?>';
                     document.getElementById('pRecPhone').textContent = '<?php echo htmlspecialchars($customer['phone_number'], ENT_QUOTES, 'UTF-8'); ?>';
                     document.getElementById('pRecDevice').textContent = this.device;
                     
                     document.getElementById('pRecPayDate').textContent = new Date(pay.created_at).toLocaleString();
                     document.getElementById('pRecPayAmt').textContent = '€' + parseFloat(pay.amount).toFixed(2);
                     document.getElementById('pRecPayMethod').textContent = pay.payment_method + ' (' + pay.payment_type + ')';
                     document.getElementById('pRecPayRef').textContent = pay.reference_code || 'N/A';
                     document.getElementById('pRecStaff').textContent = pay.received_by;
                     
                     const currentPaid = parseFloat(this.deposit);
                     const totalQuote = parseFloat(this.quote);
                     const balanceLeft = Math.max(0, totalQuote - currentPaid);
                     
                     document.getElementById('pRecQuote').textContent = '€' + totalQuote.toFixed(2);
                     document.getElementById('pRecTotalPaid').textContent = '€' + currentPaid.toFixed(2);
                     document.getElementById('pRecBalDue').textContent = '€' + balanceLeft.toFixed(2);
                     
                     window.print();
                 }
             }">
             
            <!-- Left Panel: Customer Summary & Edit Form -->
            <div class="col-12 col-lg-5 d-print-none">
                <div class="card p-4 bg-white">
                    <h3 class="h5 fw-bold text-dark mb-3">🛠️ Edit Repair & Customer Details</h3>

                    <!-- Success / Error alerts -->
                    <div x-show="successMsg" class="alert alert-success py-2 px-3 small border-0 mb-3" style="background-color: #d1e7dd; color: #0f5132;" x-text="successMsg"></div>
                    <div x-show="errorMsg" class="alert alert-danger py-2 px-3 small border-0 mb-3" style="background-color: #f8d7da; color: #842029;" x-text="errorMsg"></div>

                    <form @submit.prevent="saveChanges">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Customer Name</label>
                            <input type="text" x-model="name" class="form-control form-control-sm bg-light" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Phone Number</label>
                            <input type="text" x-model="phone" class="form-control form-control-sm bg-light" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Email Address</label>
                            <input type="email" x-model="email" class="form-control form-control-sm">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Device Model</label>
                            <input type="text" x-model="device" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Problem / Fault Description</label>
                            <textarea x-model="fault" class="form-control form-control-sm" rows="3" required></textarea>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-secondary">Quote (€)</label>
                                <input type="number" step="0.01" x-model="quote" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-secondary">Deposit (€)</label>
                                <input type="number" step="0.01" x-model="deposit" class="form-control form-control-sm" disabled title="Deposit/Total Paid is dynamically updated via the Payments ledger.">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Repair Status</label>
                            <select x-model="status" class="form-select form-select-sm">
                                <option value="Pending">Pending</option>
                                <option value="Processing">Processing</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">Internal Technician Notes</label>
                            <textarea x-model="notes" class="form-control form-control-sm" rows="4" placeholder="Enter special notes, parts required, or progress updates..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm w-100 py-2" style="background-color: var(--brand-teal); border-color: var(--brand-teal);" :disabled="isSaving">
                            <span x-show="!isSaving">💾 Save Changes</span>
                            <span x-show="isSaving" class="spinner-border spinner-border-sm" role="status"></span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right Panel: Payments, Finances & History -->
            <div class="col-12 col-lg-7 d-print-none d-flex flex-column gap-4">
                
                <!-- Finances & Collect Payment Panel -->
                <div class="card p-4 bg-white">
                    <h3 class="h5 fw-bold text-dark mb-3">💰 Finances & Collect Payment</h3>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-4">
                            <div class="p-3 border rounded text-center" style="background: #fafafa;">
                                <div class="text-muted small" style="font-size: 11px; text-transform: uppercase;">Total Quote</div>
                                <div class="h5 fw-bold mb-0 mt-1">€<span x-text="parseFloat(quote).toFixed(2)"></span></div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 border rounded text-center" style="background: #fafafa;">
                                <div class="text-success small" style="font-size: 11px; text-transform: uppercase;">Total Paid</div>
                                <div class="h5 fw-bold text-success mb-0 mt-1">€<span x-text="parseFloat(deposit).toFixed(2)"></span></div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 border rounded text-center" style="background: #fdf2f2; border-color: #fde8e8 !important;">
                                <div class="text-danger small" style="font-size: 11px; text-transform: uppercase;">Balance Due</div>
                                <div class="h5 fw-bold text-danger mb-0 mt-1">€<span x-text="Math.max(0, parseFloat(quote) - parseFloat(deposit)).toFixed(2)"></span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Collection Form -->
                    <template x-if="parseFloat(quote) - parseFloat(deposit) > 0">
                        <form @submit.prevent="collectPayment" class="border-top pt-3">
                            <span class="d-block fw-bold text-secondary mb-2" style="font-size: 13px;">Record Receipt / Payment</span>
                            
                            <div x-show="paySuccessMsg" class="alert alert-success py-2 px-3 small border-0 mb-3" x-text="paySuccessMsg"></div>
                            <div x-show="payErrorMsg" class="alert alert-danger py-2 px-3 small border-0 mb-3" x-text="payErrorMsg"></div>

                            <div class="row g-2">
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-secondary">Amount (€)</label>
                                    <input type="number" step="0.01" x-model="payAmount" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-secondary">Method</label>
                                    <select x-model="payMethod" class="form-select form-select-sm">
                                        <option value="Cash">Cash</option>
                                        <option value="Card BOI">Card BOI</option>
                                        <option value="Card Fixed">Card Fixed</option>
                                    </select>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-secondary">Payment Type</label>
                                    <select x-model="payType" class="form-select form-select-sm">
                                        <option value="Deposit">Deposit</option>
                                        <option value="Partial">Partial</option>
                                        <option value="Final Balance">Final Balance</option>
                                    </select>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-secondary">Reference Code</label>
                                    <input type="text" x-model="payRef" class="form-control form-control-sm" placeholder="e.g. Card Auth">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm mt-3 w-100 py-2" style="background-color: var(--brand-green); border-color: var(--brand-green);" :disabled="isAddingPayment">
                                <span x-show="!isAddingPayment">💳 Record Payment Receipt</span>
                                <span x-show="isAddingPayment" class="spinner-border spinner-border-sm" role="status"></span>
                            </button>
                        </form>
                    </template>
                    <template x-if="parseFloat(quote) - parseFloat(deposit) <= 0">
                        <div class="alert alert-success border-0 py-2 text-center mb-0 small">
                            🎉 This repair job is fully paid. Balance is €0.00.
                        </div>
                    </template>
                </div>

                <!-- Payment Receipts Ledger List -->
                <div class="card p-4 bg-white">
                    <h3 class="h5 fw-bold text-dark mb-3">🧾 Issued Payment Receipts</h3>
                    
                    <div class="table-responsive border">
                        <table class="table align-middle mb-0" style="font-size: 13px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Type</th>
                                    <th>Staff</th>
                                    <th class="text-end">Voucher</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="pay in payments" :key="pay.id">
                                    <tr>
                                        <td class="text-muted" x-text="new Date(pay.created_at).toLocaleDateString() + ' ' + new Date(pay.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})"></td>
                                        <td><strong class="text-dark">€<span x-text="parseFloat(pay.amount).toFixed(2)"></span></strong></td>
                                        <td x-text="pay.payment_method"></td>
                                        <td x-text="pay.payment_type"></td>
                                        <td x-text="pay.received_by"></td>
                                        <td class="text-end">
                                            <button @click="printPaymentReceipt(pay)" class="btn btn-sm btn-light border" style="font-size: 11px;">
                                                🖨️ Print
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="payments.length === 0">
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">No payments recorded.</td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Historical Repair Jobs -->
                <div class="card bg-white p-4">
                    <h3 class="h5 fw-bold text-dark mb-3">🛠️ Repair Job History</h3>
                    <p class="text-muted small mb-4">Detailed lists of all repair bookings corresponding to this customer's registered phone number.</p>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Ticket ID</th>
                                    <th>Device Detail</th>
                                    <th>Problem Description</th>
                                    <th>Finances</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historyJobs as $job): ?>
                                    <tr style="<?php echo $job['id'] == $bookingId ? 'background-color: #f7f9fa; border-left: 3px solid var(--brand-teal);' : ''; ?>">
                                        <td>
                                            <span class="fw-bold" style="font-size: 13px; font-family: monospace;"><?php echo htmlspecialchars($job['ticket_id']); ?></span>
                                        </td>
                                        <td class="fw-semibold text-dark">
                                            <?php echo htmlspecialchars($job['device_model']); ?>
                                        </td>
                                        <td class="text-muted small" style="max-width: 200px; white-space: normal;">
                                            <?php echo htmlspecialchars($job['problem_description']); ?>
                                        </td>
                                        <td>
                                            <div class="small">Quote: <strong>€<?php echo number_format($job['total_quote'], 2); ?></strong></div>
                                            <div class="small text-success">Paid: €<?php echo number_format($job['deposit_paid'], 2); ?></div>
                                            <div class="small text-danger">Due: €<?php echo number_format($job['balance_due'], 2); ?></div>
                                        </td>
                                        <td>
                                            <span class="badge px-2 py-1 rounded-1 text-uppercase fw-semibold" style="font-size: 10px; letter-spacing: 0.5px;
                                                <?php
                                                    if ($job['status'] === 'Pending') echo 'background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba;';
                                                    elseif ($job['status'] === 'Processing') echo 'background-color: #cce5ff; color: #004085; border: 1px solid #b8daff;';
                                                    else echo 'background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;';
                                                ?>">
                                                <?php echo htmlspecialchars($job['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Payment Receipt Print Template (Standard thermal voucher style) -->
    <div id="printPaymentReceiptArea" class="d-none d-print-block" style="font-family: monospace; line-height: 1.4; color: #000; width: 80mm; margin: 0 auto; padding: 10px;">
        <div style="text-align: center; border-bottom: 1px dashed #000; padding-bottom: 10px; margin-bottom: 10px;">
            <h3 style="margin: 0; font-size: 20px; font-weight: bold;" id="pRecStore">Store</h3>
            <p style="margin: 3px 0 0 0; font-size: 14px;">PAYMENT RECEIPT</p>
        </div>
        
        <div style="font-size: 14px; margin-bottom: 8px;">
            <strong>Date:</strong> <span id="pRecPayDate"></span><br>
            <strong>Job Ticket ID:</strong> <span id="pRecTicket"></span>
        </div>

        <div style="border-bottom: 1px dashed #000; padding-bottom: 5px; margin-bottom: 8px; font-size: 14px;">
            <strong>CUSTOMER DETAILS</strong><br>
            Name: <span id="pRecCust"></span><br>
            Phone: <span id="pRecPhone"></span>
        </div>

        <div style="border-bottom: 1px dashed #000; padding-bottom: 5px; margin-bottom: 8px; font-size: 14px;">
            <strong>DEVICE</strong><br>
            Model: <span id="pRecDevice"></span>
        </div>

        <div style="border-bottom: 1px dashed #000; padding-bottom: 5px; margin-bottom: 8px; font-size: 14px;">
            <strong>TRANSACTION DETAILS</strong><br>
            Amount Received: <span id="pRecPayAmt" style="font-weight: bold;"></span><br>
            Payment Method: <span id="pRecPayMethod"></span><br>
            Reference Code: <span id="pRecPayRef"></span><br>
            Received By: <span id="pRecStaff"></span>
        </div>

        <div style="text-align: right; font-size: 14px; line-height: 1.5; margin-top: 8px;">
            Total Job Quote: <span id="pRecQuote"></span><br>
            Cumulative Paid: <span id="pRecTotalPaid"></span><br>
            <div style="border-top: 1px dashed #000; margin-top: 4px; font-weight: bold; font-size: 16px;">
                Remaining Balance: <span id="pRecBalDue"></span>
            </div>
        </div>

        <div style="text-align: center; margin-top: 20px; font-size: 13px; border-top: 1px dashed #000; padding-top: 8px;">
            Thank you for your payment!<br>
            Keep this receipt for account reference.
        </div>
    </div>

    <!-- Standard Footer -->
    <?php require_once __DIR__ . '/footer.php'; ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
