<?php
// api.php
session_start();
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action !== 'submit_intake' && !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/db.php';

try {
    switch ($action) {
        case 'get_data':
            // Get categories
            $categories = $db->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
            
            // Get products
            $products = $db->query("
                SELECT p.id, p.category_id, c.name AS category_name, p.brand, p.line 
                FROM products p
                JOIN categories c ON p.category_id = c.id
                ORDER BY p.brand ASC, p.line ASC
            ")->fetchAll();

            // Get flavors
            $flavors = $db->query("SELECT id, name FROM flavors ORDER BY name ASC")->fetchAll();

            // Get active order (we assume there's one active order, if not, one was created in db.php)
            $orderIdStmt = $db->query("SELECT id FROM orders WHERE status = 'active' LIMIT 1");
            $orderId = $orderIdStmt->fetchColumn();

            // Get active order items
            $orderItems = [];
            if ($orderId) {
                $orderItems = $db->query("
                    SELECT oi.id, oi.quantity, p.id AS product_id, p.brand, p.line, f.name AS flavor, c.name AS category
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    JOIN categories c ON p.category_id = c.id
                    JOIN flavors f ON oi.flavor_id = f.id
                    WHERE oi.order_id = $orderId
                ")->fetchAll();
            }

            echo json_encode([
                'status' => 'success',
                'categories' => $categories,
                'products' => $products,
                'flavors' => $flavors,
                'orderItems' => $orderItems
            ]);
            break;

        case 'add_category':
            $input = json_decode(file_get_contents('php://input'), true);
            $name = trim($input['name'] ?? '');

            if (!$name) {
                throw new Exception('Category name is required.');
            }

            $stmt = $db->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$name]);

            echo json_encode([
                'status' => 'success',
                'id' => $db->lastInsertId(),
                'name' => $name
            ]);
            break;

        case 'add_product':
            $input = json_decode(file_get_contents('php://input'), true);
            $categoryId = intval($input['category_id'] ?? 0);
            $brand = trim($input['brand'] ?? '');
            $line = trim($input['line'] ?? '');

            if (!$categoryId || !$brand || !$line) {
                throw new Exception('Category, brand, and model line are required.');
            }

            $stmt = $db->prepare("INSERT INTO products (category_id, brand, line) VALUES (?, ?, ?)");
            $stmt->execute([$categoryId, $brand, $line]);

            echo json_encode([
                'status' => 'success',
                'id' => $db->lastInsertId(),
                'category_id' => $categoryId,
                'brand' => $brand,
                'line' => $line
            ]);
            break;

        case 'add_item':
        case 'add_order_item':
            $input = json_decode(file_get_contents('php://input'), true);
            $productId = intval($input['product_id'] ?? 0);
            $flavorName = trim($input['flavor'] ?? $input['flavour'] ?? '');
            $qty = intval($input['qty'] ?? 1);

            if (!$productId || !$flavorName || $qty < 1) {
                throw new Exception('Invalid product, flavor, or quantity.');
            }

            // Find or create flavor
            $flavorStmt = $db->prepare("SELECT id FROM flavors WHERE LOWER(name) = LOWER(?)");
            $flavorStmt->execute([$flavorName]);
            $flavorId = $flavorStmt->fetchColumn();

            if (!$flavorId) {
                $insertFlavor = $db->prepare("INSERT INTO flavors (name) VALUES (?)");
                $insertFlavor->execute([$flavorName]);
                $flavorId = $db->lastInsertId();
            }

            // Get active order ID
            $orderIdStmt = $db->query("SELECT id FROM orders WHERE status = 'active' LIMIT 1");
            $orderId = $orderIdStmt->fetchColumn();
            if (!$orderId) {
                $db->exec("INSERT INTO orders (status) VALUES ('active')");
                $orderId = $db->lastInsertId();
            }

            // Add item to active order
            $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, flavor_id, quantity) VALUES (?, ?, ?, ?)");
            $stmt->execute([$orderId, $productId, $flavorId, $qty]);

            echo json_encode(['status' => 'success']);
            break;

        case 'remove_item':
        case 'remove_order_item':
            $input = json_decode(file_get_contents('php://input'), true);
            $itemId = intval($input['id'] ?? $input['item_id'] ?? 0);

            if (!$itemId) {
                throw new Exception('Invalid item ID.');
            }

            $stmt = $db->prepare("DELETE FROM order_items WHERE id = ?");
            $stmt->execute([$itemId]);

            echo json_encode(['status' => 'success']);
            break;

        case 'get_active_order':
            // Get active order ID
            $orderIdStmt = $db->query("SELECT id FROM orders WHERE status = 'active' LIMIT 1");
            $orderId = $orderIdStmt->fetchColumn();

            $orderItems = [];
            if ($orderId) {
                $orderItems = $db->query("
                    SELECT oi.id, oi.quantity, p.id AS product_id, p.brand, p.line, f.name AS flavour, f.name AS flavor, c.name AS category_name, c.name AS category
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    JOIN categories c ON p.category_id = c.id
                    JOIN flavors f ON oi.flavor_id = f.id
                    WHERE oi.order_id = $orderId
                    ORDER BY oi.id ASC
                ")->fetchAll();
            }

            echo json_encode([
                'status' => 'success',
                'items' => $orderItems
            ]);
            break;

        case 'clear_order':
            // Get active order ID
            $orderIdStmt = $db->query("SELECT id FROM orders WHERE status = 'active' LIMIT 1");
            $orderId = $orderIdStmt->fetchColumn();
            if ($orderId) {
                $stmt = $db->prepare("DELETE FROM order_items WHERE order_id = ?");
                $stmt->execute([$orderId]);
            }
            echo json_encode(['status' => 'success']);
            break;

        case 'save_order':
            $input = json_decode(file_get_contents('php://input'), true);
            $items = $input['items'] ?? [];

            $db->beginTransaction();

            try {
                // Get active order ID
                $orderIdStmt = $db->query("SELECT id FROM orders WHERE status = 'active' LIMIT 1");
                $orderId = $orderIdStmt->fetchColumn();
                if (!$orderId) {
                    $db->exec("INSERT INTO orders (status) VALUES ('active')");
                    $orderId = $db->lastInsertId();
                }

                // Clear existing items for this active order
                $deleteStmt = $db->prepare("DELETE FROM order_items WHERE order_id = ?");
                $deleteStmt->execute([$orderId]);

                // Insert new items
                if (!empty($items)) {
                    $flavorCache = [];
                    
                    $findFlavor = $db->prepare("SELECT id FROM flavors WHERE LOWER(name) = LOWER(?)");
                    $insertFlavor = $db->prepare("INSERT INTO flavors (name) VALUES (?)");
                    $insertOrderItem = $db->prepare("INSERT INTO order_items (order_id, product_id, flavor_id, quantity) VALUES (?, ?, ?, ?)");

                    foreach ($items as $item) {
                        $productId = intval($item['product_id'] ?? 0);
                        $flavorName = trim($item['flavor'] ?? '');
                        $qty = intval($item['qty'] ?? 1);

                        if (!$productId || !$flavorName || $qty < 1) {
                            throw new Exception('Invalid product, flavor, or quantity in items list.');
                        }

                        // Get or insert flavor
                        $lowerFlavor = strtolower($flavorName);
                        if (isset($flavorCache[$lowerFlavor])) {
                            $flavorId = $flavorCache[$lowerFlavor];
                        } else {
                            $findFlavor->execute([$flavorName]);
                            $flavorId = $findFlavor->fetchColumn();

                            if (!$flavorId) {
                                $insertFlavor->execute([$flavorName]);
                                $flavorId = $db->lastInsertId();
                            }
                            $flavorCache[$lowerFlavor] = $flavorId;
                        }

                        // Insert order item
                        $insertOrderItem->execute([$orderId, $productId, $flavorId, $qty]);
                    }
                }

                // Mark the current active order as completed
                $db->prepare("UPDATE orders SET status = 'completed' WHERE id = ?")->execute([$orderId]);

                $db->commit();
                echo json_encode(['status' => 'success']);
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

        case 'submit_order':
            // Get active order ID
            $orderIdStmt = $db->query("SELECT id FROM orders WHERE status = 'active' LIMIT 1");
            $orderId = $orderIdStmt->fetchColumn();
            
            if ($orderId) {
                // Mark current order as completed
                $stmt = $db->prepare("UPDATE orders SET status = 'completed' WHERE id = ?");
                $stmt->execute([$orderId]);
            }
            
            // Create a new active order for next use
            $db->exec("INSERT INTO orders (status) VALUES ('active')");
            
            echo json_encode(['status' => 'success']);
            break;

        case 'receive_order':
            $input = json_decode(file_get_contents('php://input'), true);
            $orderId = intval($input['id'] ?? 0);

            if (!$orderId) {
                throw new Exception('Invalid order ID.');
            }

            $stmt = $db->prepare("UPDATE orders SET status = 'received' WHERE id = ?");
            $stmt->execute([$orderId]);

            echo json_encode(['status' => 'success']);
            break;

        case 'receive_order_item':
            $input = json_decode(file_get_contents('php://input'), true);
            $itemId = intval($input['id'] ?? 0);

            if (!$itemId) {
                throw new Exception('Invalid item ID.');
            }

            $stmt = $db->prepare("UPDATE order_items SET status = 'received' WHERE id = ?");
            $stmt->execute([$itemId]);

            echo json_encode(['status' => 'success']);
            break;

        case 'submit_intake':
            $input = json_decode(file_get_contents('php://input'), true);
            $sessionId = $input['session_id'] ?? '';
            $name = $input['name'] ?? '';
            $phone = $input['phone'] ?? '';
            $deviceName = $input['device_name'] ?? '';

            if (empty($sessionId)) {
                echo json_encode(['status' => 'error', 'message' => 'Session ID is required.']);
                break;
            }

            // Remove existing intake session (in case of double submission) and insert fresh entry
            $stmtDel = $db->prepare("DELETE FROM booking_intakes WHERE session_id = ?");
            $stmtDel->execute([$sessionId]);

            $stmtIns = $db->prepare("INSERT INTO booking_intakes (session_id, name, phone, device_name) VALUES (?, ?, ?, ?)");
            $stmtIns->execute([$sessionId, $name, $phone, $deviceName]);

            echo json_encode(['status' => 'success', 'message' => 'Intake submitted.']);
            break;

        case 'check_intake':
            $sessionId = $_GET['session_id'] ?? '';
            if (empty($sessionId)) {
                echo json_encode(['status' => 'error', 'message' => 'Session ID is required.']);
                break;
            }

            $stmt = $db->prepare("SELECT name, phone, device_name FROM booking_intakes WHERE session_id = ?");
            $stmt->execute([$sessionId]);
            $intake = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($intake) {
                // Delete intake from database so it doesn't double-poll
                $stmtDel = $db->prepare("DELETE FROM booking_intakes WHERE session_id = ?");
                $stmtDel->execute([$sessionId]);

                echo json_encode(['status' => 'success', 'found' => true, 'data' => $intake]);
            } else {
                echo json_encode(['status' => 'success', 'found' => false]);
            }
            break;

        default:
            throw new Exception('Invalid action.');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
