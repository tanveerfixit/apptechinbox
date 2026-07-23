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
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="flex flex-col min-h-screen bg-[#f3f3f3] text-[#242424] font-sans antialiased">

    <!-- Header Navigation -->
    <?php require_once __DIR__ . '/header.php'; ?>

    <!-- Main Container -->
    <main class="w-full max-w-xl mx-auto px-4 sm:px-6 py-6 flex-1" 
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
          
        <?php require __DIR__ . '/nav_buttons.php'; ?>

        <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-sm overflow-hidden">
            <div class="p-5 border-b border-[#e0e0e0] border-l-4 border-l-[#7fba00] bg-white">
                <h1 class="text-xl font-bold text-[#242424] tracking-tight">Daily Sales Closure</h1>
                <div class="text-xs text-[#5c5c5c] font-semibold mt-0.5"><?php echo htmlspecialchars($currentDateStr); ?></div>
            </div>

            <div class="p-6 bg-white space-y-4">
                <?php if (!$tenantDbConnected): ?>
                    <div class="bg-red-50 border-l-4 border-[#f25022] p-4 text-xs text-red-900 rounded-[4px]">
                        <strong class="font-bold">⚠️ Database Not Connected</strong><br>
                        This business is not connected to its relevant database. Please create the database <strong>`<?php echo htmlspecialchars($tenantDbName); ?>`</strong> in Hostinger and assign user privileges to allow saving daily closing records.
                    </div>
                <?php endif; ?>
                <?php if ($successMsg): ?>
                    <div class="bg-green-50 border border-[#7fba00]/40 text-[#7fba00] text-xs py-2 px-3 rounded-[4px] text-center font-medium">
                        <?php echo htmlspecialchars($successMsg); ?>
                    </div>
                <?php endif; ?>

                <?php if ($errorMsg): ?>
                    <div class="bg-red-50 border border-[#f25022]/40 text-[#f25022] text-xs py-2 px-3 rounded-[4px] text-center font-medium">
                        <?php echo htmlspecialchars($errorMsg); ?>
                    </div>
                <?php endif; ?>

                <form id="closureForm" method="POST" action="daily-closer.php" class="space-y-4" x-on:submit="if (!isDbConnected) { event.preventDefault(); alert('⚠️ Database Connection Error\n\nThis business is not connected to its relevant database.\n\nPlease create the database \'' + suggestedDbName + '\' in Hostinger and assign user privileges to save closing records.'); }">
                    <input type="hidden" name="save_action" value="1">
                    
                    <div>
                        <label for="cash_sale" class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Cash Sale (€)</label>
                        <input type="number" step="0.01" min="0" name="cash_sale" id="cash_sale" class="w-full px-3 py-2 text-lg font-bold text-right border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]" x-model.number="cash">
                    </div>

                    <div>
                        <label for="card_boi" class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Card BOI (€)</label>
                        <input type="number" step="0.01" min="0" name="card_boi" id="card_boi" class="w-full px-3 py-2 text-lg font-bold text-right border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]" x-model.number="boi">
                    </div>

                    <div>
                        <label for="card_fixed" class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Card Fixed (€)</label>
                        <input type="number" step="0.01" min="0" name="card_fixed" id="card_fixed" class="w-full px-3 py-2 text-lg font-bold text-right border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]" x-model.number="fixed">
                    </div>

                    <div class="flex justify-between items-center bg-[#fafafa] border border-[#e0e0e0] p-4 rounded-[4px] my-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#5c5c5c]">Total Sale</span>
                        <span class="text-2xl font-bold text-[#7fba00]">€<span x-text="total">0.00</span></span>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="flex-1 py-2.5 px-4 bg-[#7fba00] hover:bg-[#6ea200] text-white text-xs font-bold uppercase tracking-wider rounded-[4px] transition-colors shadow-xs">
                            Save
                        </button>
                        <button type="button" x-on:click="printTicket()" class="flex-1 py-2.5 px-4 bg-white border border-[#e0e0e0] hover:bg-[#f3f3f3] text-[#242424] text-xs font-bold uppercase tracking-wider rounded-[4px] transition-colors">
                            Print
                        </button>
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

    <!-- Standard Footer -->
    <?php require_once __DIR__ . '/footer.php'; ?>

    <!-- Print styling scoped for thermal printing -->
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
