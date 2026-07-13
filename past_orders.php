<?php
// past_orders.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/db.php';

// Fetch past completed and received orders
$orders = $db->query("SELECT id, created_at, status FROM orders WHERE status IN ('completed', 'received') ORDER BY created_at DESC")->fetchAll();

// Fetch items for these orders
$items = [];
if (!empty($orders)) {
    $items = $db->query("
        SELECT oi.id AS item_id, oi.order_id, oi.quantity, oi.status AS item_status, p.id AS product_id, p.brand, p.line, f.name AS flavor
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN products p ON oi.product_id = p.id
        JOIN flavors f ON oi.flavor_id = f.id
        WHERE o.status IN ('completed', 'received')
        ORDER BY o.created_at DESC
    ")->fetchAll();
}

// Fetch top 5 most frequently ordered products
$popularItems = $db->query("
    SELECT p.id AS product_id, p.brand, p.line, f.name AS flavor, SUM(oi.quantity) AS total_qty
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN products p ON oi.product_id = p.id
    JOIN flavors f ON oi.flavor_id = f.id
    WHERE o.status IN ('completed', 'received')
    GROUP BY p.id, p.brand, p.line, f.name
    ORDER BY total_qty DESC
    LIMIT 20
")->fetchAll();

$ordersMap = [];
foreach ($orders as $order) {
    $ordersMap[$order['id']] = [
        'id' => $order['id'],
        'created_at' => $order['created_at'],
        'status' => $order['status'],
        'items' => []
    ];
}

$totalPendingItems = 0;
$totalReceivedItems = 0;

foreach ($items as $item) {
    if (isset($ordersMap[$item['order_id']])) {
        $ordersMap[$item['order_id']]['items'][] = $item;
        if ($item['item_status'] === 'pending') {
            $totalPendingItems++;
        } else {
            $totalReceivedItems++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Past Orders - Vape Order Builder</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Mobile responsive font sizes and spacing improvements */
        body {
            font-size: 14px;
        }
        h1 {
            font-size: 18px;
        }
        .back-btn {
            font-size: 14px;
            font-weight: 700;
            color: #007aff;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 6px 10px;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        .header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .icon-btn {
            font-size: 18px;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 6px;
            color: #666666;
            transition: color 0.2s;
        }
        .icon-btn:hover {
            color: #007aff;
        }
        
        /* Table padding and typography adjustments for mobile readability */
        th {
            font-size: 10px;
            padding: 8px 4px;
            color: #8e8e93;
        }
        td {
            font-size: 13px;
            padding: 10px 4px;
        }
        .product-brand {
            font-size: 13px;
            font-weight: 700;
            color: #1c1c1e;
        }
        .product-line {
            font-size: 10px;
            color: #8e8e93;
        }
        
        /* Action buttons */
        .action-container {
            display: flex;
            gap: 6px;
            justify-content: flex-end;
            align-items: center;
        }
        .btn-action {
            font-size: 10px;
            font-weight: 700;
            padding: 4px 6px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            transition: opacity 0.15s ease;
        }
        .btn-action:active {
            opacity: 0.7;
        }
        .btn-recv {
            background-color: #e8f9ee;
            color: #34c759;
        }
        .btn-reorder {
            background-color: #e8f2ff;
            color: #007aff;
        }
        .badge-recv {
            font-size: 10px;
            font-weight: 700;
            color: #34c759;
            padding: 4px 6px;
        }
    </style>
</head>
<body>

<div class="container animate-fade">
    <header>
        <h1>Past Orders</h1>
        <div class="header-actions">
            <?php if ($totalReceivedItems > 0): ?>
                <button onclick="toggleShowReceived()" id="toggleReceivedBtn" class="icon-btn" title="Show received products">👁️</button>
            <?php endif; ?>
            <a href="vape.php" class="back-btn">&larr; Builder</a>
        </div>
    </header>

    <main>
        <?php if (!empty($ordersMap) && !empty($popularItems)): ?>
            <!-- POPULAR ITEMS / QUICK REORDER -->
            <div class="card" style="margin-bottom: 16px; border-left: 4px solid #007aff;">
                <h3 style="margin-top: 0; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #007aff; letter-spacing: 0.5px; margin-bottom: 12px;">
                    🔥 Frequently Ordered
                </h3>
                <div style="max-height: 250px; overflow-y: auto; -webkit-overflow-scrolling: touch;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                        <tbody>
                            <?php foreach ($popularItems as $pItem): ?>
                                <tr style="border-bottom: 1px solid #f2f2f7;">
                                    <td style="padding: 8px 4px; vertical-align: middle; width: 40%;">
                                        <div class="product-brand"><?php echo htmlspecialchars($pItem['brand']); ?></div>
                                        <div class="product-line"><?php echo htmlspecialchars($pItem['line']); ?></div>
                                    </td>
                                    <td style="padding: 8px 4px; vertical-align: middle; color: #333333; width: 40%;">
                                        <?php echo htmlspecialchars($pItem['flavor']); ?>
                                    </td>
                                    <td style="padding: 8px 4px; text-align: right; vertical-align: middle; width: 20%;">
                                        <button onclick="reorderSingle(<?php echo htmlspecialchars(json_encode([
                                            'product_id' => $pItem['product_id'],
                                            'brand' => $pItem['brand'],
                                            'line' => $pItem['line'],
                                            'flavor' => $pItem['flavor'],
                                            'quantity' => 1
                                        ]), ENT_QUOTES, 'UTF-8'); ?>)" class="btn-action btn-reorder" title="Quick reorder" style="margin: 0;">
                                            Reorder
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($ordersMap)): ?>
            <div class="card" style="text-align: center; color: #666666; padding: 40px 20px;">
                <p style="margin-top: 0; font-style: italic;">No past orders found in the database.</p>
                <a href="vape.php" class="btn btn-primary" style="display: block; text-decoration: none; text-align: center; line-height: 20px;">Go to Builder</a>
            </div>
        <?php else: ?>
            
            <div id="noPendingMessage" class="card" style="text-align: center; color: #666666; padding: 30px 20px; <?php echo ($totalPendingItems === 0) ? 'display: block;' : 'display: none;'; ?>">
                <p style="margin: 0; font-style: italic; line-height: 1.5;">All products have been received.<br>Click <span style="font-style: normal; cursor: pointer; color: #007aff; font-weight: bold;" onclick="toggleShowReceived()">👁️</span> to view hidden products.</p>
            </div>

            <?php foreach ($ordersMap as $order): 
                // Determine if this order has any pending items to show initially
                $hasPendingItems = false;
                foreach ($order['items'] as $item) {
                    if ($item['item_status'] === 'pending') {
                        $hasPendingItems = true;
                        break;
                    }
                }
            ?>
                <div class="card" data-card-id="<?php echo $order['id']; ?>" style="margin-bottom: 12px; <?php echo (!$hasPendingItems) ? 'display: none;' : ''; ?>">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f2f2f7; padding-bottom: 8px; margin-bottom: 12px;">
                        <span style="font-weight: 700; color: #1c1c1e;">Order #<?php echo $order['id']; ?></span>
                        <span style="font-size: 10px; color: #8e8e93;"><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></span>
                    </div>

                    <table style="width: 100%; border-collapse: collapse; font-size: 12px; margin-bottom: 8px;">
                        <thead>
                            <tr style="border-bottom: 1px solid #f2f2f7;">
                                <th style="text-align: left; width: 30%;">Product</th>
                                <th style="text-align: left; width: 30%;">Flavor</th>
                                <th style="text-align: right; width: 10%;">Qty</th>
                                <th style="text-align: right; width: 30%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalUnits = 0;
                            foreach ($order['items'] as $item): 
                                $totalUnits += $item['quantity'];
                            ?>
                                <tr data-status="<?php echo htmlspecialchars($item['item_status']); ?>" style="border-bottom: 1px solid #f2f2f7; <?php echo ($item['item_status'] === 'received') ? 'display: none;' : ''; ?>">
                                    <td style="vertical-align: middle;">
                                        <div class="product-brand"><?php echo htmlspecialchars($item['brand']); ?></div>
                                        <div class="product-line"><?php echo htmlspecialchars($item['line']); ?></div>
                                    </td>
                                    <td style="vertical-align: middle; color: #333333;">
                                        <?php echo htmlspecialchars($item['flavor']); ?>
                                    </td>
                                    <td style="text-align: right; vertical-align: middle; font-weight: 700; color: #000000;">
                                        x<?php echo $item['quantity']; ?>
                                    </td>
                                    <td style="vertical-align: middle; text-align: right;">
                                        <div class="action-container">
                                            <?php if ($item['item_status'] === 'pending'): ?>
                                                <button onclick="markItemAsReceived(<?php echo $item['item_id']; ?>)" class="btn-action btn-recv" title="Mark received">Recv</button>
                                            <?php else: ?>
                                                <span class="badge-recv" title="Received">✓</span>
                                            <?php endif; ?>
                                            
                                            <button onclick="reorderSingle(<?php echo htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8'); ?>)" class="btn-action btn-reorder" title="Reorder product">Reorder</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>

<!-- TOAST CONTAINER -->
<div id="toast" class="toast">
    <span id="toastMsg"></span>
</div>

<script>
const toastEl = document.getElementById("toast");
const toastMsgEl = document.getElementById("toastMsg");
let showReceived = false;
const hasPending = <?php echo ($totalPendingItems > 0) ? 'true' : 'false'; ?>;

function showToast(text, tone = "success") {
    toastEl.className = "toast" + (tone === "warning" ? " warning" : "") + " show";
    toastMsgEl.textContent = text;
    setTimeout(() => {
        toastEl.className = "toast";
    }, 3000);
}

async function markItemAsReceived(itemId) {
    try {
        const res = await fetch('api.php?action=receive_order_item', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: itemId })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast("Product marked as received!");
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(data.message, "warning");
        }
    } catch (e) {
        showToast("Error updating product status.", "warning");
    }
}

function reorderSingle(item) {
    const stored = localStorage.getItem('vape_order_items');
    let orderItems = [];
    if (stored) {
        try {
            orderItems = JSON.parse(stored);
        } catch (e) {
            orderItems = [];
        }
    }

    const tempId = 'temp_' + Date.now() + '_' + Math.floor(Math.random() * 1000000);
    orderItems.push({
        id: tempId,
        product_id: item.product_id,
        brand: item.brand,
        line: item.line,
        flavor: item.flavor,
        quantity: item.quantity,
        category: ""
    });

    localStorage.setItem('vape_order_items', JSON.stringify(orderItems));
    showToast("Added " + item.brand + " to builder!");
    setTimeout(() => {
        window.location.href = 'vape.php';
    }, 800);
}

function toggleShowReceived() {
    showReceived = !showReceived;
    
    // Toggle all rows with status="received"
    const receivedRows = document.querySelectorAll('tr[data-status="received"]');
    receivedRows.forEach(row => {
        row.style.display = showReceived ? 'table-row' : 'none';
    });
    
    // Toggle all cards: if a card has no pending items, its display depends on showReceived
    const cards = document.querySelectorAll('.card[data-card-id]');
    cards.forEach(card => {
        const pendingInCard = card.querySelectorAll('tr[data-status="pending"]').length;
        if (pendingInCard === 0) {
            card.style.display = showReceived ? 'block' : 'none';
        }
    });
    
    const noPendingMsg = document.getElementById("noPendingMessage");
    if (noPendingMsg && !hasPending) {
        noPendingMsg.style.display = showReceived ? 'none' : 'block';
    }
    
    const btn = document.getElementById("toggleReceivedBtn");
    if (btn) {
        if (showReceived) {
            btn.innerHTML = "👁️‍🗨️";
            btn.style.color = "#007aff";
            btn.title = "Hide received products";
        } else {
            btn.innerHTML = "👁️";
            btn.style.color = "#666666";
            btn.title = "Show received products";
        }
    }
}
</script>

</body>
</html>
