<?php
// daily-closer.php
session_start();
require_once __DIR__ . '/db.php';

// Enforce login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Redirect to Laravel route if Laravel application is bootstrapped
if (defined('LARAVEL_START')) {
    return redirect()->to('/daily-closer');
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';
$successMsg = '';
$errorMsg = '';

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

// Format current date
$currentDateStr = date('l j F Y');
$todayIso = date('Y-m-d');

// Fetch today's closure details if tenant database is connected
$todayClosure = null;
if ($tenantDbConnected && $db !== null) {
    try {
        $fetchStmt = $db->prepare("SELECT cash_sale, card_boi, card_fixed, total_sale FROM daily_closures WHERE closure_date = ?");
        $fetchStmt->execute([$todayIso]);
        $todayClosure = $fetchStmt->fetch();
    } catch (PDOException $e) {}
}

$cashVal = $todayClosure ? (float)$todayClosure['cash_sale'] : 0.00;
$boiVal = $todayClosure ? (float)$todayClosure['card_boi'] : 0.00;
$fixedVal = $todayClosure ? (float)$todayClosure['card_fixed'] : 0.00;

// Handle Save Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_action'])) {
    if (!$tenantDbConnected || $db === null) {
        $errorMsg = "Database Connection Error: This business is not connected to its relevant database. Please create the database '{$tenantDbName}' in Hostinger to resolve this.";
    } else {
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
                $cashVal = $cashInput;
                $boiVal = $boiInput;
                $fixedVal = $fixedInput;
            } else {
                $errorMsg = "Failed to save daily closure.";
            }
        } catch (PDOException $e) {
            $errorMsg = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Sales Closure - TechInbox</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/public/icons/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/svg+xml" href="/public/icons/icon.svg">
    <link rel="shortcut icon" href="/public/icons/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/public/icons/apple-touch-icon.png">
    <link rel="manifest" href="/public/icons/site.webmanifest">
    
    <!-- Outfit Font & Bootstrap 5 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
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
            --brand-green: #7fba00;
            --brand-blue: #00a4ef;
            --font-family: 'Roboto', 'Segoe UI', system-ui, sans-serif;
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
            background-color: var(--brand-green);
            border-color: var(--brand-green);
            color: #ffffff;
        }

        .btn-brand:hover {
            background-color: #6da000;
            border-color: #6da000;
            color: #ffffff;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Header Navigation -->
    <?php require_once __DIR__ . '/header.php'; ?>

    <!-- Main Container -->
    <main class="container-fluid px-2 px-sm-3 py-3 py-md-4 flex-grow-1" style="max-width: 550px; margin: 0 auto;" 
          x-data="{ 
              isDbConnected: <?php echo $tenantDbConnected ? 'true' : 'false'; ?>,
              suggestedDbName: '<?php echo htmlspecialchars($tenantDbName); ?>',
              cash: <?php echo $cashVal; ?>, 
              boi: <?php echo $boiVal; ?>, 
              fixed: <?php echo $fixedVal; ?>,
              get total() {
                  return (parseFloat(this.cash || 0) + parseFloat(this.boi || 0) + parseFloat(this.fixed || 0)).toFixed(2);
              },
              printTicket() {
                  const now = new Date();
                  const dateStr = now.toLocaleDateString() + ' ' + now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                  document.getElementById('receiptDateTime').textContent = dateStr;
                  
                  document.getElementById('pCash').textContent = '€' + parseFloat(this.cash || 0).toFixed(2);
                  document.getElementById('pBoi').textContent = '€' + parseFloat(this.boi || 0).toFixed(2);
                  document.getElementById('pFixed').textContent = '€' + parseFloat(this.fixed || 0).toFixed(2);
                  document.getElementById('pTotal').textContent = '€' + this.total;
                  
                  window.print();
              }
          }">
          
        <div class="card shadow-sm border-1 overflow-hidden" style="border-radius: 6px;">
            <div class="card-header bg-white py-3 px-4 border-bottom" style="border-left: 4px solid var(--brand-green) !important;">
                <h1 class="h5 fw-bold text-dark mb-1">Daily Sales Closure</h1>
                <div class="small text-muted fw-semibold"><?php echo htmlspecialchars($currentDateStr); ?></div>
            </div>

            <div class="card-body p-4 bg-white">
                <?php if (!$tenantDbConnected): ?>
                    <div class="alert alert-danger p-3 mb-4 border-0 shadow-sm" style="font-size: 13.5px; border-radius: 6px; border-left: 4px solid var(--brand-red) !important; background-color: #fdf2f2; color: #9b1c1c;">
                        <strong>⚠️ Database Not Connected</strong><br>
                        This business is not connected to its relevant database. Please create the database <strong>`<?php echo htmlspecialchars($tenantDbName); ?>`</strong> in Hostinger and assign user privileges to allow saving daily closing records.
                    </div>
                <?php endif; ?>
                <?php if ($successMsg): ?>
                    <div class="alert alert-success py-2 px-3 small text-center mb-3" style="font-size: 13px; border-radius: 4px;">
                        <?php echo htmlspecialchars($successMsg); ?>
                    </div>
                <?php endif; ?>

                <?php if ($errorMsg): ?>
                    <div class="alert alert-danger py-2 px-3 small text-center mb-3" style="font-size: 13px; border-radius: 4px;">
                        <?php echo htmlspecialchars($errorMsg); ?>
                    </div>
                <?php endif; ?>

                <form id="closureForm" method="POST" action="daily-closer.php" x-on:submit="if (!isDbConnected) { event.preventDefault(); alert('⚠️ Database Connection Error\n\nThis business is not connected to its relevant database.\n\nPlease create the database \'' + suggestedDbName + '\' in Hostinger and assign user privileges to save closing records.'); }">
                    <input type="hidden" name="save_action" value="1">
                    
                    <div class="mb-3">
                        <label for="cash_sale" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Cash Sale (€)</label>
                        <input type="number" step="0.01" min="0" name="cash_sale" id="cash_sale" class="form-control py-2 text-end fw-bold fs-5 rounded-1" x-model.number="cash" style="font-size: 18px;">
                    </div>

                    <div class="mb-3">
                        <label for="card_boi" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Card BOI (€)</label>
                        <input type="number" step="0.01" min="0" name="card_boi" id="card_boi" class="form-control py-2 text-end fw-bold fs-5 rounded-1" x-model.number="boi" style="font-size: 18px;">
                    </div>

                    <div class="mb-4">
                        <label for="card_fixed" class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Card Fixed (€)</label>
                        <input type="number" step="0.01" min="0" name="card_fixed" id="card_fixed" class="form-control py-2 text-end fw-bold fs-5 rounded-1" x-model.number="fixed" style="font-size: 18px;">
                    </div>

                    <div class="d-flex justify-content-between align-items-center bg-light border p-3 rounded mb-4" style="border-radius: 4px;">
                        <span class="small fw-bold text-uppercase text-muted" style="font-size: 11px; letter-spacing: 0.5px;">Total Sale</span>
                        <span class="h3 fw-bold mb-0" style="color: var(--brand-green);">€<span x-text="total">0.00</span></span>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-brand py-2 flex-grow-1 fw-bold text-uppercase rounded-1" style="font-size: 13px; letter-spacing: 0.5px;">Save</button>
                        <button type="button" x-on:click="printTicket()" class="btn btn-outline-secondary py-2 flex-grow-1 fw-bold text-uppercase rounded-1" style="font-size: 13px; letter-spacing: 0.5px;">Print</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Hidden Printable Ticket Layout -->
    <div id="printArea" style="display: none;">
        <div class="receipt-header">
            <h2>DAILY CLOSURE</h2>
            <p style="font-weight: bold; font-size: 14px; margin-bottom: 4px;"><?php echo htmlspecialchars(strtoupper($businessName)); ?></p>
            <?php if (!empty($businessContact)): ?>
                <p style="font-size: 13px;">Phone: <?php echo htmlspecialchars($businessContact); ?></p>
            <?php endif; ?>
            <?php if (!empty($businessEmail)): ?>
                <p style="font-size: 13px;">Email: <?php echo htmlspecialchars($businessEmail); ?></p>
            <?php endif; ?>
        </div>
        <div class="receipt-divider"></div>
        <div class="receipt-details">
            <p style="font-size: 13px;"><strong>Business:</strong> <span><?php echo htmlspecialchars($businessName); ?></span></p>
            <p style="font-size: 13px;"><strong>Staff Name:</strong> <span><?php echo htmlspecialchars($username); ?></span></p>
            <p style="font-size: 13px;"><strong>Date & Time:</strong> <span id="receiptDateTime"></span></p>
        </div>
        <div class="receipt-divider"></div>
        <div class="receipt-row" style="font-size: 14px;">
            <span>Cash Sale:</span>
            <span id="pCash">€0.00</span>
        </div>
        <div class="receipt-row" style="font-size: 14px;">
            <span>Card BOI:</span>
            <span id="pBoi">€0.00</span>
        </div>
        <div class="receipt-row" style="font-size: 14px;">
            <span>Card Fixed:</span>
            <span id="pFixed">€0.00</span>
        </div>
        <div class="receipt-divider"></div>
        <div class="receipt-row" style="font-weight: bold; font-size: 18px;">
            <span>Total Sale:</span>
            <span id="pTotal">€0.00</span>
        </div>
    </div>

    <!-- Redesigned Clean Footer -->
    <footer class="bg-white border-top py-3 mt-auto w-100 shadow-sm" style="border-color: var(--card-border) !important;">
        <div class="container-fluid px-3 text-center">
            <span class="text-muted" style="font-size: 12px; letter-spacing: 0.1px;">
                Developer: <span class="fw-semibold text-dark">Tanveer</span>
                <span class="mx-2" style="color: var(--card-border);">&bull;</span>
                Support: <a href="mailto:support@techinbox.ie" class="text-decoration-none fw-semibold" style="color: var(--brand-blue) !important;">support@techinbox.ie</a>
            </span>
        </div>
    </footer>

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
