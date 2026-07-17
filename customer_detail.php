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

// Retrieve booking details
$bookingId = intval($_GET['id'] ?? 0);
$customer = null;
$historyJobs = [];

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

        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 6px;
        }

        .table-responsive {
            border-radius: 6px;
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

        .form-control:focus, .form-select:focus {
            border-color: var(--brand-teal);
            box-shadow: 0 0 0 3px rgba(0, 130, 114, 0.15);
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Header Navigation -->
    <?php require_once __DIR__ . '/header.php'; ?>

    <main class="container-fluid px-2 px-md-4 py-3 py-md-4 flex-grow-1">
        <!-- Breadcrumb back link -->
        <div class="mb-3">
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
                 }
             }">
             
            <!-- Left Panel: Customer Summary & Edit Form -->
            <div class="col-12 col-lg-5">
                <div class="card shadow-sm border-1 p-4 bg-white" style="border-radius: 6px; border-color: var(--card-border) !important;">
                    <h3 class="h5 fw-bold text-dark mb-3">🛠️ Edit Repair & Customer Details</h3>

                    <!-- Success / Error alerts -->
                    <div x-show="successMsg" class="alert alert-success py-2 px-3 small border-0 mb-3" style="background-color: #d1e7dd; color: #0f5132; border-radius: 4px;" x-text="successMsg"></div>
                    <div x-show="errorMsg" class="alert alert-danger py-2 px-3 small border-0 mb-3" style="background-color: #f8d7da; color: #842029; border-radius: 4px;" x-text="errorMsg"></div>

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
                                <input type="number" step="0.01" x-model="deposit" class="form-control form-control-sm">
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

            <!-- Right Panel: Jobs list History Table -->
            <div class="col-12 col-lg-7">
                <div class="card shadow-sm border-1 bg-white p-4">
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

    <!-- Standard Footer -->
    <?php require_once __DIR__ . '/footer.php'; ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
