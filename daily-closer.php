<?php
// daily-closer.php
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
$successMsg = '';
$errorMsg = '';

// Fetch active user details (to get active business name, contact, email, address)
$stmtUser = $db->prepare("SELECT name, contact, email, address FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$profile = $stmtUser->fetch();
$businessName = !empty($profile['name']) ? $profile['name'] : 'Store';
$businessContact = !empty($profile['contact']) ? $profile['contact'] : '';
$businessEmail = !empty($profile['email']) ? $profile['email'] : '';
$businessAddress = !empty($profile['address']) ? $profile['address'] : '';

// Format current date exactly like "Monday 13 July 2026"
$currentDateStr = date('l j F Y'); // e.g. "Monday 13 July 2026"
$todayIso = date('Y-m-d');

// Fetch today's closure details if they exist
$fetchStmt = $db->prepare("SELECT cash_sale, card_boi, card_fixed, total_sale FROM daily_closures WHERE closure_date = ?");
$fetchStmt->execute([$todayIso]);
$todayClosure = $fetchStmt->fetch();

$cashVal = $todayClosure ? $todayClosure['cash_sale'] : '0.00';
$boiVal = $todayClosure ? $todayClosure['card_boi'] : '0.00';
$fixedVal = $todayClosure ? $todayClosure['card_fixed'] : '0.00';
$totalVal = $todayClosure ? $todayClosure['total_sale'] : '0.00';

// Handle Save Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_action'])) {
    $cashInput = floatval($_POST['cash_sale'] ?? 0);
    $boiInput = floatval($_POST['card_boi'] ?? 0);
    $fixedInput = floatval($_POST['card_fixed'] ?? 0);
    $totalInput = $cashInput + $boiInput + $fixedInput;

    try {
        $saveStmt = $db->prepare("
            INSERT INTO daily_closures (user_id, business_name, closure_date, cash_sale, card_boi, card_fixed, total_sale)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                user_id = VALUES(user_id),
                business_name = VALUES(business_name),
                cash_sale = VALUES(cash_sale),
                card_boi = VALUES(card_boi),
                card_fixed = VALUES(card_fixed),
                total_sale = VALUES(total_sale)
        ");
        
        if ($saveStmt->execute([$userId, $businessName, $todayIso, $cashInput, $boiInput, $fixedInput, $totalInput])) {
            $successMsg = "Daily closure saved successfully!";
            // Update current fields
            $cashVal = number_format($cashInput, 2, '.', '');
            $boiVal = number_format($boiInput, 2, '.', '');
            $fixedVal = number_format($fixedInput, 2, '.', '');
            $totalVal = number_format($totalInput, 2, '.', '');
        } else {
            $errorMsg = "Failed to save daily closure.";
        }
    } catch (PDOException $e) {
        $errorMsg = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Sales Closure - TechInbox</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <style>
        #printArea {
            display: none;
        }

        /* Printing adjustments */
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
                display: block;
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
    <main class="container py-5 flex-grow-1" style="max-width: 550px;">
        <div class="card shadow-sm border-1 overflow-hidden" style="border-radius: 6px;">
            <div class="card-header bg-white py-3 px-4 border-bottom" style="border-left: 4px solid #7fba00 !important;">
                <h1 class="h5 fw-bold text-dark mb-1">Daily Sales Closure</h1>
                <div class="small text-muted fw-semibold"><?php echo htmlspecialchars($currentDateStr); ?></div>
            </div>

            <div class="card-body p-4 bg-white">
                <?php if ($successMsg): ?>
                    <div class="alert alert-success py-2 px-3 small text-center mb-3" style="font-size: 13px;">
                        <?php echo htmlspecialchars($successMsg); ?>
                    </div>
                <?php endif; ?>

                <?php if ($errorMsg): ?>
                    <div class="alert alert-danger py-2 px-3 small text-center mb-3" style="font-size: 13px;">
                        <?php echo htmlspecialchars($errorMsg); ?>
                    </div>
                <?php endif; ?>

                <form id="closureForm" method="POST" action="daily-closer.php">
                    <input type="hidden" name="save_action" value="1">
                    
                    <div class="mb-3">
                        <label for="cash_sale" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Cash Sale (€)</label>
                        <input type="number" step="0.01" min="0" name="cash_sale" id="cash_sale" class="form-control py-2 text-end fw-bold fs-5" value="<?php echo htmlspecialchars($cashVal); ?>" oninput="calculateTotal()">
                    </div>

                    <div class="mb-3">
                        <label for="card_boi" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Card BOI (€)</label>
                        <input type="number" step="0.01" min="0" name="card_boi" id="card_boi" class="form-control py-2 text-end fw-bold fs-5" value="<?php echo htmlspecialchars($boiVal); ?>" oninput="calculateTotal()">
                    </div>

                    <div class="mb-4">
                        <label for="card_fixed" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Card Fixed (€)</label>
                        <input type="number" step="0.01" min="0" name="card_fixed" id="card_fixed" class="form-control py-2 text-end fw-bold fs-5" value="<?php echo htmlspecialchars($fixedVal); ?>" oninput="calculateTotal()">
                    </div>

                    <div class="d-flex justify-content-between align-items-center bg-light border p-3 rounded mb-4">
                        <span class="small fw-bold text-uppercase text-muted" style="font-size: 11px; letter-spacing: 0.5px;">Total Sale</span>
                        <span id="totalDisplay" class="h3 fw-bold mb-0" style="color: #7fba00;">€<?php echo htmlspecialchars(number_format($totalVal, 2)); ?></span>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn text-white py-2 flex-grow-1 fw-bold text-uppercase" style="background-color: #7fba00; font-size: 13px; letter-spacing: 0.5px;" onmouseover="this.style.backgroundColor='#6da000'" onmouseout="this.style.backgroundColor='#7fba00'">Save</button>
                        <button type="button" class="btn btn-outline-secondary py-2 flex-grow-1 fw-bold text-uppercase" style="font-size: 13px; letter-spacing: 0.5px;" onclick="printClosure()">Print</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Hidden Printable Ticket Layout -->
    <div id="printArea">
        <div class="receipt-header">
            <h2>DAILY CLOSURE</h2>
            <p style="font-weight: bold; font-size: 11px; margin-bottom: 4px;"><?php echo htmlspecialchars(strtoupper($businessName)); ?></p>
            <?php if (!empty($businessAddress)): ?>
                <p><?php echo htmlspecialchars($businessAddress); ?></p>
            <?php endif; ?>
            <?php if (!empty($businessContact)): ?>
                <p>Phone: <?php echo htmlspecialchars($businessContact); ?></p>
            <?php endif; ?>
            <?php if (!empty($businessEmail)): ?>
                <p>Email: <?php echo htmlspecialchars($businessEmail); ?></p>
            <?php endif; ?>
        </div>
        <div class="receipt-divider"></div>
        <div class="receipt-details">
            <p><strong>Business:</strong> <span><?php echo htmlspecialchars($businessName); ?></span></p>
            <p><strong>Staff Name:</strong> <span><?php echo htmlspecialchars($username); ?></span></p>
            <p><strong>Date & Time:</strong> <span id="receiptDateTime"></span></p>
        </div>
        <div class="receipt-divider"></div>
        <div class="receipt-row">
            <span>Cash Sale:</span>
            <span id="pCash">€0.00</span>
        </div>
        <div class="receipt-row">
            <span>Card BOI:</span>
            <span id="pBoi">€0.00</span>
        </div>
        <div class="receipt-row">
            <span>Card Fixed:</span>
            <span id="pFixed">€0.00</span>
        </div>
        <div class="receipt-divider"></div>
        <div class="receipt-row" style="font-weight: bold; font-size: 14px;">
            <span>Total Sale:</span>
            <span id="pTotal">€0.00</span>
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
        function calculateTotal() {
            const cash = parseFloat(document.getElementById('cash_sale').value) || 0;
            const boi = parseFloat(document.getElementById('card_boi').value) || 0;
            const fixed = parseFloat(document.getElementById('card_fixed').value) || 0;
            const total = cash + boi + fixed;
            
            document.getElementById('totalDisplay').textContent = '€' + total.toFixed(2);
        }

        function printClosure() {
            const cash = parseFloat(document.getElementById('cash_sale').value) || 0;
            const boi = parseFloat(document.getElementById('card_boi').value) || 0;
            const fixed = parseFloat(document.getElementById('card_fixed').value) || 0;
            const total = cash + boi + fixed;

            // Populate printable details
            document.getElementById('pCash').textContent = '€' + cash.toFixed(2);
            document.getElementById('pBoi').textContent = '€' + boi.toFixed(2);
            document.getElementById('pFixed').textContent = '€' + fixed.toFixed(2);
            document.getElementById('pTotal').textContent = '€' + total.toFixed(2);

            // Populate current date & time
            const now = new Date();
            const dateStr = now.toLocaleDateString() + ' ' + now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            document.getElementById('receiptDateTime').textContent = dateStr;

            window.print();
        }
    </script>
</body>
</html>
