<?php
// past_orders.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/db.php';

// Redirect to Laravel route if Laravel application is bootstrapped
if (defined('LARAVEL_START')) {
    return redirect()->to('/past-orders');
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';

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

// Fetch top 20 most frequently ordered products
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

        .cursor-pointer {
            cursor: pointer;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Header Component -->
    <?php require_once __DIR__ . '/header.php'; ?>

    <!-- Main Content Container with Alpine.js -->
    <main class="container-fluid px-2 px-sm-3 py-3 py-md-4 flex-grow-1" 
          style="max-width: 600px; margin: 0 auto;"
          x-data="{
              showReceived: false,
              hasPending: <?php echo ($totalPendingItems > 0) ? 'true' : 'false'; ?>,
              toast: { show: false, message: '' },
              showToast(msg) {
                  this.toast.message = msg;
                  this.toast.show = true;
                  setTimeout(() => this.toast.show = false, 2500);
              },
              async markItemAsReceived(itemId) {
                  try {
                      const res = await fetch('api.php?action=receive_order_item', {
                          method: 'POST',
                          headers: { 'Content-Type': 'application/json' },
                          body: JSON.stringify({ id: itemId })
                      });
                      const data = await res.json();
                      if (data.status === 'success') {
                          this.showToast('Product marked as received!');
                          setTimeout(() => window.location.reload(), 1000);
                      } else {
                          this.showToast(data.message);
                      }
                  } catch (e) {
                      this.showToast('Error updating status.');
                  }
              },
              reorderSingle(item) {
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
                      flavor: item.flavor || item.flavor,
                      quantity: item.quantity || 1,
                      category: ''
                  });

                  localStorage.setItem('vape_order_items', JSON.stringify(orderItems));
                  alert('Added ' + item.brand + ' to builder!');
                  window.location.href = 'vape.php';
              }
          }">
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 fw-bold text-dark mb-0">Past Orders</h1>
            <div class="d-flex align-items-center gap-2">
                <?php if ($totalReceivedItems > 0): ?>
                    <button x-on:click="showReceived = !showReceived" class="btn btn-sm btn-light border rounded-1 p-2 d-flex align-items-center justify-content-center" :title="showReceived ? 'Hide received products' : 'Show received products'">
                        <span x-text="showReceived ? '👁️‍🗨️' : '👁️'"></span>
                    </button>
                <?php endif; ?>
                <a href="vape.php" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 rounded-1" style="font-size: 13px;">
                    &larr; Builder
                </a>
            </div>
        </div>

        <!-- Inline Toast Notification -->
        <div x-show="toast.show" 
             x-transition 
             class="alert alert-success py-2 px-3 small text-center mb-3 border-0 shadow-sm"
             style="background-color: #d1e7dd; color: #0f5132; border-radius: 4px; display: none;">
            <span x-text="toast.message"></span>
        </div>

        <?php if (!empty($ordersMap) && !empty($popularItems)): ?>
            <!-- POPULAR ITEMS / QUICK REORDER -->
            <div class="card shadow-sm border-1 p-3 mb-3 bg-white" style="border-radius: 6px; border-left: 4px solid var(--brand-blue) !important; border-color: var(--card-border);">
                <h3 class="small fw-bold text-uppercase text-muted mb-3" style="letter-spacing: 0.5px; font-size: 11px; color: var(--brand-blue) !important;">
                    🔥 Frequently Ordered
                </h3>
                <div class="overflow-auto" style="max-height: 250px;">
                    <table class="table table-sm align-middle mb-0" style="font-size: 12.5px;">
                        <tbody>
                            <?php foreach ($popularItems as $pItem): ?>
                                <tr style="border-bottom: 1px solid var(--card-border);">
                                    <td style="padding: 8px 4px; width: 45%;">
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($pItem['brand']); ?></div>
                                        <div class="small text-muted" style="font-size: 10px;"><?php echo htmlspecialchars($pItem['line']); ?></div>
                                    </td>
                                    <td style="padding: 8px 4px; color: var(--text-primary); width: 35%;">
                                        <?php echo htmlspecialchars($pItem['flavor']); ?>
                                    </td>
                                    <td style="padding: 8px 4px; text-align: right; width: 20%;">
                                        <button x-on:click="reorderSingle(<?php echo htmlspecialchars(json_encode($pItem), ENT_QUOTES, 'UTF-8'); ?>)" class="btn btn-sm px-2 py-1 fw-bold text-uppercase rounded-1" style="font-size: 10px; background-color: #e8f2ff; color: var(--brand-blue); border: none;">
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
            <div class="card shadow-sm border-1 p-5 text-center bg-white" style="border-radius: 6px; border-color: var(--card-border);">
                <p class="text-muted mb-0 fst-italic">No past orders found in the database.</p>
            </div>
        <?php else: ?>
            <!-- No Pending Messages Alert -->
            <div id="noPendingMessage" class="card shadow-sm border-1 p-4 text-center bg-white mb-3" style="border-radius: 6px; border-color: var(--card-border); line-height: 1.5;" x-show="!hasPending && !showReceived">
                <p class="text-muted mb-0 fst-italic">All products have been received.<br>Click <span class="fw-bold cursor-pointer" style="color: var(--brand-blue);" x-on:click="showReceived = true">👁️</span> to view hidden products.</p>
            </div>

            <?php foreach ($ordersMap as $order): 
                $hasPendingItems = false;
                foreach ($order['items'] as $item) {
                    if ($item['item_status'] === 'pending') {
                        $hasPendingItems = true;
                        break;
                    }
                }
            ?>
                <div class="card shadow-sm border-1 p-3 bg-white mb-3" 
                     style="border-radius: 6px; border-color: var(--card-border);" 
                     x-show="showReceived || <?php echo $hasPendingItems ? 'true' : 'false'; ?>">
                     
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3" style="border-color: var(--card-border) !important;">
                        <span class="fw-bold text-dark">Order #<?php echo $order['id']; ?></span>
                        <span class="small text-muted" style="font-size: 11px;"><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0" style="font-size: 12.5px;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--card-border);">
                                    <th class="text-muted" style="font-size: 10px; text-transform: uppercase; padding: 6px 4px;">Product</th>
                                    <th class="text-muted" style="font-size: 10px; text-transform: uppercase; padding: 6px 4px;">Flavor</th>
                                    <th class="text-end text-muted" style="font-size: 10px; text-transform: uppercase; padding: 6px 4px; width: 60px;">Qty</th>
                                    <th class="text-end text-muted" style="font-size: 10px; text-transform: uppercase; padding: 6px 4px; width: 140px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order['items'] as $item): ?>
                                    <tr style="border-bottom: 1px solid var(--card-border);" 
                                        x-show="showReceived || '<?php echo $item['item_status']; ?>' === 'pending'">
                                        
                                        <td style="padding: 8px 4px;">
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($item['brand']); ?></div>
                                            <div class="small text-muted" style="font-size: 10px;"><?php echo htmlspecialchars($item['line']); ?></div>
                                        </td>
                                        <td style="padding: 8px 4px; color: var(--text-primary);">
                                            <?php echo htmlspecialchars($item['flavor']); ?>
                                        </td>
                                        <td class="text-end fw-bold text-dark" style="padding: 8px 4px;">
                                            x<?php echo $item['quantity']; ?>
                                        </td>
                                        <td style="padding: 8px 4px; text-align: right;">
                                            <div class="d-inline-flex gap-1">
                                                <?php if ($item['item_status'] === 'pending'): ?>
                                                    <button x-on:click="markItemAsReceived(<?php echo $item['item_id']; ?>)" class="btn btn-sm px-2 py-1 fw-bold text-uppercase rounded-1" style="font-size: 10px; background-color: #e8f9ee; color: var(--brand-green); border: none;">
                                                        Recv
                                                    </button>
                                                <?php else: ?>
                                                    <span class="badge bg-success-subtle text-success px-2 py-1 fw-bold" style="font-size: 10px; border-radius: 4px;">✓</span>
                                                <?php endif; ?>
                                                <button x-on:click="reorderSingle(<?php echo htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8'); ?>)" class="btn btn-sm px-2 py-1 fw-bold text-uppercase rounded-1" style="font-size: 10px; background-color: #e8f2ff; color: var(--brand-blue); border: none;">
                                                    Reorder
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <!-- Footer Component -->
    <?php require_once __DIR__ . '/footer.php'; ?>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
