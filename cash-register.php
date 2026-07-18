<?php
// cash-register.php
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

// Fetch active business details dynamically from master
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cash Register - Businesses Apps By TechInbox</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Point of Sale Cash Register for <?php echo htmlspecialchars($businessName); ?>">
    <link rel="icon" type="image/png" href="/public/icons/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/svg+xml" href="/public/icons/icon.svg">
    <link rel="shortcut icon" href="/public/icons/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/public/icons/apple-touch-icon.png">
    <link rel="manifest" href="/public/icons/site.webmanifest">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        :root {
            --bg-page: #f3f3f3;
            --card-bg: #ffffff;
            --card-border: #e0e0e0;
            --text-primary: #242424;
            --text-secondary: #5c5c5c;
            --text-tertiary: #8a8a8a;
            --brand-blue: #00a4ef;
            --brand-blue-hover: #0078d4;
            --brand-green: #107c10;
            --brand-green-hover: #0b6a0b;
            --brand-orange: #f25022;
            --brand-yellow: #ffb900;
            --row-hover: #f9f9fb;
            --search-focus: #e8f4fd;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
        }

        /* ── Page Header ────────────────────────────────── */
        .pos-header {
            background: var(--card-bg);
            border-bottom: 1px solid var(--card-border);
            padding: 14px 20px;
        }
        .pos-header h1 {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.3px;
            margin: 0;
        }
        .pos-header .header-meta {
            font-size: 12px;
            color: var(--text-tertiary);
        }
        .pos-header .header-meta strong {
            color: var(--text-secondary);
        }

        /* ── Search Bar ─────────────────────────────────── */
        .search-wrap {
            position: relative;
        }
        .search-wrap .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-tertiary);
            pointer-events: none;
        }
        .search-wrap input {
            padding: 10px 14px 10px 42px;
            font-size: 14px;
            border: 1px solid var(--card-border);
            background: var(--card-bg);
            color: var(--text-primary);
            width: 100%;
            outline: none;
        }
        .search-wrap input:focus {
            border-color: var(--brand-blue);
            background: var(--search-focus);
        }
        .search-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 50;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-top: none;
            max-height: 280px;
            overflow-y: auto;
        }
        .search-dropdown-item {
            padding: 10px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
        }
        .search-dropdown-item:hover {
            background: var(--search-focus);
        }
        .search-dropdown-item .item-name { font-weight: 600; color: var(--text-primary); }
        .search-dropdown-item .item-meta { font-size: 11px; color: var(--text-tertiary); margin-top: 1px; }
        .search-dropdown-item .item-price {
            font-weight: 700;
            font-size: 14px;
            color: var(--brand-blue);
            white-space: nowrap;
        }

        /* ── Cart Table ─────────────────────────────────── */
        .cart-panel {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
        }
        .cart-panel table { margin-bottom: 0; }
        .cart-panel thead th {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: var(--text-tertiary);
            background: #fafafa;
            border-bottom: 1px solid var(--card-border);
            padding: 10px 14px;
            white-space: nowrap;
        }
        .cart-panel tbody td {
            padding: 12px 14px;
            font-size: 13px;
            vertical-align: middle;
            border-bottom: 1px solid #f4f4f4;
        }
        .cart-panel tbody tr:hover { background: var(--row-hover); }
        .cart-empty {
            padding: 48px 20px;
            text-align: center;
            color: var(--text-tertiary);
        }
        .cart-empty svg { margin-bottom: 12px; opacity: 0.35; }
        .cart-empty p { font-size: 13px; margin: 0; }

        /* Quantity Stepper */
        .qty-stepper {
            display: inline-flex;
            align-items: center;
            border: 1px solid var(--card-border);
            background: #fafafa;
            overflow: hidden;
        }
        .qty-stepper button {
            width: 28px;
            height: 28px;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }
        .qty-stepper button:hover { background: #eee; }
        .qty-stepper .qty-value {
            width: 32px;
            text-align: center;
            font-size: 13px;
            font-weight: 700;
            color: var(--text-primary);
            border-left: 1px solid var(--card-border);
            border-right: 1px solid var(--card-border);
            line-height: 28px;
        }
        .btn-delete {
            border: none;
            background: transparent;
            color: #c4314b;
            cursor: pointer;
            padding: 4px;
            opacity: 0.6;
        }
        .btn-delete:hover { opacity: 1; }

        /* ── Activity Log ───────────────────────────────── */
        .activity-panel { background: var(--card-bg); border: 1px solid var(--card-border); }
        .activity-toggle {
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            user-select: none;
            border-bottom: 1px solid var(--card-border);
        }
        .activity-toggle:hover { background: #fafafa; }
        .activity-toggle h2 { font-size: 13px; font-weight: 700; margin: 0; color: var(--text-primary); }
        .activity-body { padding: 12px 16px; max-height: 160px; overflow-y: auto; }
        .log-entry {
            display: flex;
            gap: 10px;
            padding: 6px 0;
            border-bottom: 1px solid #f4f4f4;
            font-size: 12px;
        }
        .log-entry:last-child { border-bottom: none; }
        .log-time { color: var(--text-tertiary); font-weight: 600; flex-shrink: 0; width: 70px; }
        .log-text { color: var(--text-secondary); }

        /* ── Sidebar ────────────────────────────────────── */
        .sidebar-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            margin-bottom: 12px;
        }
        .sidebar-card-header {
            padding: 12px 16px 8px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-tertiary);
        }
        .sidebar-card-body { padding: 0 16px 16px; }

        /* Totals */
        .totals-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            font-size: 13px;
        }
        .totals-row + .totals-row { border-top: 1px solid #f0f0f0; }
        .totals-row .label { color: var(--text-secondary); }
        .totals-row .value { font-weight: 700; color: var(--text-primary); }
        .totals-grand {
            background: #f0faf0;
            margin: 0 -16px;
            padding: 14px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 2px solid var(--brand-green);
        }
        .totals-grand .label { font-size: 14px; font-weight: 700; color: var(--text-primary); }
        .totals-grand .value { font-size: 22px; font-weight: 700; color: var(--brand-green); }

        /* Payment Inputs */
        .payment-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 0;
        }
        .payment-row + .payment-row { border-top: 1px solid #f4f4f4; }
        .payment-icon {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }
        .payment-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            width: 44px;
            flex-shrink: 0;
        }
        .payment-input {
            flex: 1;
            border: 1px solid var(--card-border);
            padding: 6px 10px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            outline: none;
            text-align: right;
            background: #fafafa;
        }
        .payment-input:focus { border-color: var(--brand-blue); background: var(--search-focus); }
        .payment-summary {
            background: #fafafa;
            margin: 0 -16px;
            padding: 10px 16px;
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: var(--text-secondary);
        }
        .payment-summary strong { color: var(--text-primary); }
        .payment-warning {
            background: #fff5f5;
            margin: 0 -16px;
            padding: 6px 16px;
            font-size: 11px;
            font-weight: 600;
            color: #c4314b;
            display: flex;
            justify-content: space-between;
        }
        .preset-btns {
            display: flex;
            gap: 6px;
            padding-top: 10px;
        }
        .preset-btn {
            flex: 1;
            padding: 6px 0;
            font-size: 11px;
            font-weight: 600;
            text-align: center;
            border: 1px solid var(--card-border);
            background: #fafafa;
            color: var(--text-secondary);
            cursor: pointer;
        }
        .preset-btn:hover { background: var(--search-focus); color: var(--brand-blue); border-color: var(--brand-blue); }

        /* Action Buttons */
        .btn-checkout {
            display: block;
            width: 100%;
            padding: 14px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            border: none;
            cursor: pointer;
            color: #fff;
            background: var(--brand-green);
            margin-bottom: 8px;
        }
        .btn-checkout:hover { background: var(--brand-green-hover); }
        .btn-checkout:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-clear {
            display: block;
            width: 100%;
            padding: 10px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid var(--card-border);
            background: var(--card-bg);
            color: #c4314b;
            cursor: pointer;
        }
        .btn-clear:hover { background: #fff5f5; border-color: #c4314b; }

        /* Small utility for inline SVG icons */
        .icon-sm { width: 16px; height: 16px; }
        .icon-xs { width: 12px; height: 12px; }

        /* ── Responsive ─────────────────────────────────── */
        @media (max-width: 991px) {
            .pos-layout { flex-direction: column; }
            .pos-left, .pos-right { width: 100% !important; }
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <?php require_once __DIR__ . '/header.php'; ?>

    <!-- ═══════════════════════════════════════════════════
         POS WORKSPACE — AlpineJS Reactive Controller
         ═══════════════════════════════════════════════════ -->
    <main class="flex-grow-1 d-flex flex-column"
          x-data="cashRegister()"
          @keydown.escape="showSearchResults = false">

        <!-- Page Header Bar -->
        <div class="pos-header d-flex justify-content-between align-items-center">
            <div>
                <h1>Cash Register</h1>
                <span class="header-meta">
                    <strong><?php echo htmlspecialchars($businessName); ?></strong>
                    &nbsp;·&nbsp; <?php echo htmlspecialchars($username); ?>
                    &nbsp;·&nbsp; <span x-text="new Date().toLocaleDateString('en-IE', {weekday:'short', day:'numeric', month:'short', year:'numeric'})"></span>
                </span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="header-meta d-none d-md-inline" x-text="'Items: ' + totalQty"></span>
                <span class="header-meta d-none d-md-inline fw-bold" style="color: var(--brand-green);" x-text="'€' + grandTotal.toFixed(2)"></span>
            </div>
        </div>

        <!-- Two-Column Layout -->
        <div class="d-flex flex-grow-1 pos-layout" style="overflow: hidden;">

            <!-- ═══ LEFT COLUMN: Cart Workspace ═══ -->
            <div class="pos-left d-flex flex-column" style="width: 72%; border-right: 1px solid var(--card-border);">

                <!-- Search Bar -->
                <div style="padding: 12px 16px; border-bottom: 1px solid var(--card-border); background: var(--card-bg);">
                    <div class="search-wrap" @click.away="showSearchResults = false">
                        <svg class="search-icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text"
                               x-model="searchQuery"
                               @input.debounce.250ms="searchProducts()"
                               @focus="if(searchResults.length > 0) showSearchResults = true"
                               @keydown.escape="showSearchResults = false"
                               placeholder="Search product name, SKU or barcode…"
                               autocomplete="off"
                               spellcheck="false">
                        
                        <!-- Autocomplete Dropdown -->
                        <div class="search-dropdown" x-show="showSearchResults" x-transition.opacity>
                            <template x-for="item in searchResults" :key="item.id">
                                <div class="search-dropdown-item"
                                     @click="addToCart(item.product_name, item.retail_price, 1, item.stock_quantity); showSearchResults = false; searchQuery = '';">
                                    <div>
                                        <div class="item-name" x-text="item.product_name"></div>
                                        <div class="item-meta" x-text="'SKU: ' + item.sku + '  ·  Stock: ' + item.stock_quantity"></div>
                                    </div>
                                    <div class="item-price" x-text="'€' + parseFloat(item.retail_price).toFixed(2)"></div>
                                </div>
                            </template>
                            <template x-if="searchResults.length === 0 && searchQuery.length >= 2">
                                <div style="padding: 20px; text-align: center; color: var(--text-tertiary); font-size: 12px;">No products found</div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Cart Table -->
                <div class="flex-grow-1" style="overflow-y: auto; background: var(--card-bg);">
                    <table class="table" style="margin: 0;">
                        <thead style="position: sticky; top: 0; z-index: 2;">
                            <tr>
                                <th style="width: 46px; text-align: center;">#</th>
                                <th>Product</th>
                                <th style="width: 90px; text-align: center;">Stock</th>
                                <th style="width: 120px; text-align: center;">Qty</th>
                                <th style="width: 100px; text-align: right;">Price</th>
                                <th style="width: 100px; text-align: right;">Total</th>
                                <th style="width: 44px;"></th>
                            </tr>
                        </thead>
                    </table>

                    <!-- Empty State -->
                    <template x-if="cart.length === 0">
                        <div class="cart-empty">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                            <p>Search and add products to start a sale</p>
                        </div>
                    </template>

                    <!-- Cart Rows -->
                    <table class="table" style="margin: 0;" x-show="cart.length > 0">
                        <tbody>
                            <template x-for="(item, idx) in cart" :key="item.id">
                                <tr>
                                    <td style="width: 46px; text-align: center; color: var(--text-tertiary); font-size: 12px;" x-text="idx + 1"></td>
                                    <td>
                                        <span style="font-weight: 600;" x-text="item.description"></span>
                                    </td>
                                    <td style="width: 90px; text-align: center;">
                                        <span style="font-size: 11px; color: var(--text-tertiary);" x-text="item.stockQty"></span>
                                    </td>
                                    <td style="width: 120px; text-align: center;">
                                        <div class="qty-stepper">
                                            <button @click="if(item.qty > 1) item.qty--">−</button>
                                            <span class="qty-value" x-text="item.qty"></span>
                                            <button @click="item.qty++">+</button>
                                        </div>
                                    </td>
                                    <td style="width: 100px; text-align: right; font-weight: 600;" x-text="'€' + parseFloat(item.price).toFixed(2)"></td>
                                    <td style="width: 100px; text-align: right; font-weight: 700; color: var(--text-primary);" x-text="'€' + (parseFloat(item.price) * item.qty).toFixed(2)"></td>
                                    <td style="width: 44px; text-align: center;">
                                        <button class="btn-delete" @click="removeFromCart(item.id)" title="Remove">
                                            <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Activity Log -->
                <div class="activity-panel" style="flex-shrink: 0;">
                    <div class="activity-toggle" @click="showActivityLog = !showActivityLog">
                        <h2>
                            <svg class="icon-xs" style="margin-right: 6px; transform: rotate(0deg);" :style="showActivityLog ? 'transform:rotate(90deg)' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                            Activity Log
                            <span style="font-weight: 400; color: var(--text-tertiary); margin-left: 6px;" x-text="'(' + notes.length + ')'"></span>
                        </h2>
                        <div class="d-flex gap-2" @click.stop>
                            <button @click="addNote()" style="font-size: 11px; font-weight: 600; padding: 4px 10px; border: 1px solid var(--card-border); background: #fafafa; color: var(--text-secondary); cursor: pointer;">+ Note</button>
                        </div>
                    </div>
                    <div class="activity-body" x-show="showActivityLog" x-transition>
                        <template x-if="notes.length === 0">
                            <div style="font-size: 12px; color: var(--text-tertiary); padding: 8px 0;">No activity yet</div>
                        </template>
                        <template x-for="log in notes" :key="log.time + log.text">
                            <div class="log-entry">
                                <span class="log-time" x-text="log.time"></span>
                                <span class="log-text" x-text="log.text"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- ═══ RIGHT COLUMN: Checkout Sidebar ═══ -->
            <div class="pos-right d-flex flex-column" style="width: 28%; background: #fafafa; overflow-y: auto;">

                <!-- Customer -->
                <div class="sidebar-card">
                    <div class="sidebar-card-header">Customer</div>
                    <div class="sidebar-card-body">
                        <div class="d-flex gap-2">
                            <input type="text" x-model="selectedCustomer"
                                   style="flex: 1; border: 1px solid var(--card-border); padding: 7px 10px; font-size: 13px; outline: none; color: var(--text-primary); background: #fafafa;"
                                   placeholder="Walk-in Customer">
                            <button @click="selectedCustomer = prompt('Customer name:') || selectedCustomer"
                                    style="padding: 7px 12px; font-size: 12px; font-weight: 700; border: 1px solid var(--card-border); background: var(--card-bg); color: var(--brand-blue); cursor: pointer; white-space: nowrap;">+ New</button>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="sidebar-card">
                    <div class="sidebar-card-header">Order Summary</div>
                    <div class="sidebar-card-body">
                        <div class="totals-row">
                            <span class="label">Subtotal</span>
                            <span class="value" x-text="'€' + taxableTotal.toFixed(2)"></span>
                        </div>
                        <div class="totals-row">
                            <span class="label d-flex align-items-center gap-1">
                                Tax
                                <select x-model.number="taxRate"
                                        style="border: none; background: transparent; font-size: 11px; font-weight: 600; color: var(--brand-blue); cursor: pointer; outline: none; padding: 0;">
                                    <option value="0.00">0%</option>
                                    <option value="0.135">13.5%</option>
                                    <option value="0.23">23%</option>
                                </select>
                            </span>
                            <span class="value" x-text="'€' + taxAmount.toFixed(2)"></span>
                        </div>
                        <div class="totals-row">
                            <span class="label">Items</span>
                            <span class="value" x-text="totalQty"></span>
                        </div>
                        <div class="totals-grand">
                            <span class="label">Total</span>
                            <span class="value" x-text="'€' + grandTotal.toFixed(2)"></span>
                        </div>
                    </div>
                </div>

                <!-- Payment Split -->
                <div class="sidebar-card">
                    <div class="sidebar-card-header">Payment</div>
                    <div class="sidebar-card-body">
                        <div class="payment-row">
                            <span class="payment-icon">💳</span>
                            <span class="payment-label">Card</span>
                            <input type="number" step="0.01" x-model.number="cardAmount" class="payment-input" placeholder="0.00">
                        </div>
                        <div class="payment-row">
                            <span class="payment-icon">💵</span>
                            <span class="payment-label">Cash</span>
                            <input type="number" step="0.01" x-model.number="cashAmount" class="payment-input" placeholder="0.00">
                        </div>
                        <div class="payment-row">
                            <span class="payment-icon">🔄</span>
                            <span class="payment-label">Other</span>
                            <input type="number" step="0.01" x-model.number="otherAmount" class="payment-input" placeholder="0.00">
                        </div>

                        <div class="payment-summary">
                            <span>Tendered</span>
                            <strong x-text="'€' + totalPaid.toFixed(2)"></strong>
                        </div>

                        <template x-if="grandTotal > 0 && Math.abs(grandTotal - totalPaid) > 0.01">
                            <div class="payment-warning">
                                <span x-text="totalPaid > grandTotal ? 'Change Due' : 'Remaining'"></span>
                                <span x-text="'€' + Math.abs(grandTotal - totalPaid).toFixed(2)"></span>
                            </div>
                        </template>

                        <div class="preset-btns">
                            <button class="preset-btn" @click="cardAmount = grandTotal; cashAmount = 0; otherAmount = 0;">All Card</button>
                            <button class="preset-btn" @click="cashAmount = grandTotal; cardAmount = 0; otherAmount = 0;">All Cash</button>
                            <button class="preset-btn" @click="if(grandTotal > 0){ cardAmount = Math.round(grandTotal/2*100)/100; cashAmount = grandTotal - cardAmount; otherAmount = 0; }">50 / 50</button>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="padding: 0 12px 16px; margin-top: auto;">
                    <button class="btn-checkout" @click="checkout()" :disabled="cart.length === 0">
                        <svg class="icon-sm" style="margin-right: 6px; vertical-align: -2px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Checkout & Complete
                    </button>
                    <button class="btn-clear" @click="clearCart()" x-show="cart.length > 0">
                        Clear Sale & Start Over
                    </button>
                </div>
            </div>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // ═══════════════════════════════════════════════════
    // Cash Register — AlpineJS Component
    // ═══════════════════════════════════════════════════
    function cashRegister() {
        return {
            // State
            cart: [],
            searchQuery: '',
            searchResults: [],
            showSearchResults: false,
            selectedCustomer: '',
            taxRate: 0.00,
            showActivityLog: false,
            notes: [],
            cardAmount: 0,
            cashAmount: 0,
            otherAmount: 0,
            _searchTimer: null,

            init() {
                // Auto-assign full amount to card when total changes
                this.$watch('grandTotal', (val) => {
                    this.cardAmount = parseFloat(val) || 0;
                    this.cashAmount = 0;
                    this.otherAmount = 0;
                });
            },

            // ── Computed ──────────────────────────────
            get taxableTotal() {
                return this.cart.reduce((sum, i) => sum + (parseFloat(i.price) * parseInt(i.qty)), 0);
            },
            get taxAmount() {
                return this.taxableTotal * this.taxRate;
            },
            get grandTotal() {
                return this.taxableTotal + this.taxAmount;
            },
            get totalQty() {
                return this.cart.reduce((sum, i) => sum + parseInt(i.qty), 0);
            },
            get totalPaid() {
                return parseFloat(this.cardAmount || 0) + parseFloat(this.cashAmount || 0) + parseFloat(this.otherAmount || 0);
            },

            // ── Product Search ────────────────────────
            async searchProducts() {
                const q = this.searchQuery.trim();
                if (q.length < 2) {
                    this.searchResults = [];
                    this.showSearchResults = false;
                    return;
                }
                try {
                    const res = await fetch(`api.php?action=search_products&q=${encodeURIComponent(q)}`);
                    const json = await res.json();
                    this.searchResults = (json.status === 'success') ? (json.data || []) : [];
                    this.showSearchResults = true;
                } catch (e) {
                    console.error('Search error:', e);
                }
            },

            // ── Cart Operations ───────────────────────
            addToCart(name, price, qty = 1, stockQty = 0) {
                if (!name || price === '' || price === null) return;
                const existing = this.cart.find(i => i.description.toLowerCase() === name.toLowerCase());
                if (existing) {
                    existing.qty += parseInt(qty);
                } else {
                    this.cart.push({
                        id: Date.now() + Math.random(),
                        description: name,
                        qty: parseInt(qty),
                        price: parseFloat(price),
                        stockQty: stockQty
                    });
                }
                this.log('Added: ' + name + ' ×' + qty);
            },

            removeFromCart(id) {
                const item = this.cart.find(i => i.id === id);
                if (!item) return;
                this.cart = this.cart.filter(i => i.id !== id);
                this.log('Removed: ' + item.description);
            },

            clearCart() {
                if (this.cart.length === 0) return;
                if (!confirm('Clear the entire sale?')) return;
                this.cart = [];
                this.searchQuery = '';
                this.searchResults = [];
                this.showSearchResults = false;
                this.selectedCustomer = '';
                this.cardAmount = 0;
                this.cashAmount = 0;
                this.otherAmount = 0;
                this.taxRate = 0;
                this.notes = [];
                this.log('Sale cleared');
            },

            checkout() {
                if (this.cart.length === 0) return;
                
                if (this.totalPaid < this.grandTotal - 0.01) {
                    if (!confirm('Payment (€' + this.totalPaid.toFixed(2) + ') is less than the total (€' + this.grandTotal.toFixed(2) + ').\nContinue anyway?')) return;
                }

                const summary = [
                    '✅ Sale Complete',
                    '',
                    'Total: €' + this.grandTotal.toFixed(2),
                    'Card: €' + parseFloat(this.cardAmount || 0).toFixed(2),
                    'Cash: €' + parseFloat(this.cashAmount || 0).toFixed(2),
                    this.otherAmount > 0 ? 'Other: €' + parseFloat(this.otherAmount).toFixed(2) : '',
                    '',
                    'Customer: ' + (this.selectedCustomer || 'Walk-in'),
                    'Items: ' + this.totalQty
                ].filter(Boolean).join('\n');

                alert(summary);
                this.cart = [];
                this.searchQuery = '';
                this.searchResults = [];
                this.showSearchResults = false;
                this.selectedCustomer = '';
                this.cardAmount = 0;
                this.cashAmount = 0;
                this.otherAmount = 0;
                this.taxRate = 0;
                this.notes = [];
            },

            // ── Utility ───────────────────────────────
            log(text) {
                this.notes.unshift({
                    time: new Date().toLocaleTimeString('en-IE', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
                    text: text
                });
            },

            addNote() {
                const note = prompt('Add a note:');
                if (note) this.log('Note: ' + note);
            }
        };
    }
    </script>
</body>
</html>
