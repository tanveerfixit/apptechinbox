<?php
// booking.php
session_start();
require_once __DIR__ . '/db.php';

// Enforce login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$isLoggedIn = true;
$username = $_SESSION['username'] ?? '';

// Fetch active user details (to get active business name, contact, email)
$stmtUser = $db->prepare("SELECT name, contact, email FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$profile = $stmtUser->fetch();
$businessName = !empty($profile['name']) ? $profile['name'] : 'Store';
$businessContact = !empty($profile['contact']) ? $profile['contact'] : '';
$businessEmail = !empty($profile['email']) ? $profile['email'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Repair Booking - TechInbox</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <style>
        #printArea {
            display: none;
        }

        /* Printing adjustments */
        @media print {
            body * {
                visibility: hidden;
            }
            #printArea, #printArea * {
                visibility: visible;
            }
            #printArea {
                display: block;
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                font-family: 'Courier New', Courier, monospace;
                color: #000000;
                padding: 10px;
                font-size: 16px; /* Larger print font */
                line-height: 1.4;
            }
            .receipt-header {
                text-align: center;
                margin-bottom: 24px;
            }
            .receipt-header h2 {
                font-size: 22px; /* Larger header */
                margin-bottom: 6px;
                font-weight: bold;
            }
            .receipt-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 8px;
            }
            .receipt-divider {
                border-top: 1px dashed #000000;
                margin: 12px 0;
            }
            .receipt-details {
                margin-bottom: 12px;
            }
            .receipt-details strong {
                display: inline-block;
                width: 120px;
            }
        }
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <!-- Header Navigation -->
    <header class="navbar navbar-expand navbar-light bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
        <a href="index.php" class="d-flex align-items-center gap-2 text-decoration-none">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2px; width: 18px; height: 18px;">
                <div style="width: 8px; height: 8px; background-color: #f25022;"></div>
                <div style="width: 8px; height: 8px; background-color: #7fba00;"></div>
                <div style="width: 8px; height: 8px; background-color: #00a4ef;"></div>
                <div style="width: 8px; height: 8px; background-color: #ffb900;"></div>
            </div>
            <span class="fs-5 fw-bold text-dark mb-0 leading-none">TechInbox</span>
            <span class="text-muted border-start ps-2 mb-0 d-none d-sm-inline" style="font-size: 14px;">Portal</span>
        </a>
        <div class="user-section d-flex align-items-center gap-3">
            <a href="index.php" class="btn-portal text-decoration-none fw-semibold text-primary" style="font-size: 14px;">&larr; Back to Portal</a>
            <?php if ($isLoggedIn): ?>
                <span class="small text-muted d-none d-sm-inline">Signed in as <a href="profile.php" class="text-dark fw-semibold text-decoration-underline"><?php echo htmlspecialchars($username); ?></a></span>
                <a href="logout.php" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1">
                    Sign Out
                </a>
            <?php else: ?>
                <span class="small text-muted d-none d-sm-inline">Not signed in</span>
                <a href="login.php" class="btn btn-sm btn-primary">
                    Sign In
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Container -->
    <main class="container py-5 flex-grow-1" style="max-width: 600px;">
        <div class="card shadow-sm border-1 overflow-hidden" style="border-radius: 6px;">
            <div class="card-header bg-white py-3 px-4 border-bottom" style="border-left: 4px solid #008272 !important;">
                <h1 class="h5 fw-bold text-dark mb-1">New Repair Booking</h1>
                <div class="small text-muted fw-semibold">Enter customer and device details below</div>
            </div>

            <div class="card-body p-4 bg-white">
                <form id="bookingForm" onsubmit="event.preventDefault(); printReceipt();">
                    
                    <!-- Customer Name & Phone -->
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="customerName" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Customer Name</label>
                            <input type="text" id="customerName" class="form-control py-2" placeholder="Full Name" required autocomplete="off">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="phoneNumber" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Phone Number *</label>
                            <input type="text" id="phoneNumber" class="form-control py-2" placeholder="08X XXX XXXX" required autocomplete="off">
                        </div>
                    </div>

                    <!-- Device Model -->
                    <div class="mb-3">
                        <label for="deviceModel" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Device Model *</label>
                        <input type="text" id="deviceModel" class="form-control py-2" placeholder="e.g. iPhone 13, Samsung S22" required autocomplete="off">
                    </div>

                    <!-- Problem Description -->
                    <div class="mb-4">
                        <label for="problemDescription" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Problem Description *</label>
                        <textarea id="problemDescription" class="form-control" rows="3" placeholder="Describe the fault..." required></textarea>
                    </div>

                    <div class="border-top my-4"></div>

                    <!-- Pricing Row -->
                    <div class="row g-3 mb-4 align-items-end">
                        <div class="col-12 col-md-4">
                            <label for="totalQuote" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Total Quote (€)</label>
                            <input type="number" step="0.01" min="0" value="0.00" id="totalQuote" class="form-control py-2 text-end fw-bold" oninput="updateBalance()">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="depositPaid" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Deposit Paid (€)</label>
                            <input type="number" step="0.01" min="0" value="0.00" id="depositPaid" class="form-control py-2 text-end fw-bold" oninput="updateBalance()">
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="p-3 text-center border" style="background-color: #fde7e9; border-color: #e0b4b4 !important; color: #a80000; border-radius: 6px;">
                                <span class="d-block small fw-bold text-uppercase mb-1" style="font-size: 9px; letter-spacing: 0.5px;">Remaining Balance</span>
                                <span id="balanceVal" class="fs-5 fw-bold">€0.00</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <button type="submit" class="btn text-white w-100 py-3 text-uppercase fw-bold" style="background-color: #f25022; font-size: 13px; letter-spacing: 0.5px;" onmouseover="this.style.backgroundColor='#d83b01'" onmouseout="this.style.backgroundColor='#f25022'">
                        Generate & Print Receipt
                    </button>

                </form>
            </div>
        </div>
    </main>

    <!-- Hidden Container for thermal ticket layout during printing -->
    <div id="printArea">
        <div class="receipt-header">
            <h2><?php echo htmlspecialchars(strtoupper($businessName)); ?> REPAIR TICKET</h2>
            <?php if (!empty($businessContact)): ?>
                <p>Phone: <?php echo htmlspecialchars($businessContact); ?></p>
            <?php endif; ?>
            <?php if (!empty($businessEmail)): ?>
                <p>Email: <?php echo htmlspecialchars($businessEmail); ?></p>
            <?php endif; ?>
            <p id="receiptDate"></p>
            <p id="receiptTicketNum"></p>
        </div>
        <div class="receipt-divider"></div>
        <div class="receipt-details">
            <p><strong>Customer:</strong> <span id="rCustomer"></span></p>
            <p><strong>Phone:</strong> <span id="rPhone"></span></p>
            <p><strong>Device:</strong> <span id="rDevice"></span></p>
            <p><strong>Booked By:</strong> <span id="rUser"><?php echo htmlspecialchars($username ?: 'Guest'); ?></span></p>
        </div>
        <div class="receipt-divider"></div>
        <div class="receipt-details">
            <p><strong>Fault Description:</strong></p>
            <p id="rFault" style="padding-left: 10px; margin-top: 4px; font-style: italic;"></p>
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
        <div class="receipt-row" style="font-weight: bold; font-size: 18px;">
            <span>Balance Due:</span>
            <span id="rBalance">€0.00</span>
        </div>
    </div>

    <!-- Standard Footer -->
    <footer class="bg-white border-top py-3 text-center mt-auto w-100">
        <p class="small text-muted mb-0">
            These system apps and Utility are Developer: <span class="fw-semibold text-dark">Tanveer</span> | Support: <a href="mailto:support@techinbox.ie" class="text-decoration-none fw-semibold text-primary">support@techinbox.ie</a>
        </p>
    </footer>

    <!-- Bootstrap 5 JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
        function updateBalance() {
            const totalQuote = parseFloat(document.getElementById('totalQuote').value) || 0;
            const depositPaid = parseFloat(document.getElementById('depositPaid').value) || 0;
            const balance = Math.max(0, totalQuote - depositPaid);
            
            document.getElementById('balanceVal').textContent = '€' + balance.toFixed(2);
        }

        function printReceipt() {
            const customerName = document.getElementById('customerName').value;
            const phoneNumber = document.getElementById('phoneNumber').value;
            const deviceModel = document.getElementById('deviceModel').value;
            const problemDescription = document.getElementById('problemDescription').value;
            const totalQuote = parseFloat(document.getElementById('totalQuote').value) || 0;
            const depositPaid = parseFloat(document.getElementById('depositPaid').value) || 0;
            const balance = Math.max(0, totalQuote - depositPaid);

            // Populate printable ticket elements
            document.getElementById('rCustomer').textContent = customerName;
            document.getElementById('rPhone').textContent = phoneNumber;
            document.getElementById('rDevice').textContent = deviceModel;
            document.getElementById('rFault').textContent = problemDescription;
            document.getElementById('rQuote').textContent = '€' + totalQuote.toFixed(2);
            document.getElementById('rDeposit').textContent = '€' + depositPaid.toFixed(2);
            document.getElementById('rBalance').textContent = '€' + balance.toFixed(2);

            // Generate Ticket ID and timestamp
            const now = new Date();
            const dateStr = now.toLocaleDateString() + ' ' + now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            const ticketNum = 'TI-' + now.getFullYear() + String(now.getMonth() + 1).padStart(2, '0') + String(now.getDate()).padStart(2, '0') + String(now.getHours()).padStart(2, '0') + String(now.getMinutes()).padStart(2, '0');

            document.getElementById('receiptDate').textContent = 'Date: ' + dateStr;
            document.getElementById('receiptTicketNum').textContent = 'Ticket #: ' + ticketNum;

            // Trigger window print dialog
            window.print();
        }

        // Initialize balance calculation on page load
        updateBalance();
    </script>
</body>
</html>
