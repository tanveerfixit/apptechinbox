<?php
// api.php
session_start();
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action !== 'submit_intake' && $action !== 'customer_lookup' && !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/db.php';

try {
    switch ($action) {
        case 'submit_intake':
            $input = json_decode(file_get_contents('php://input'), true);
            $sessionId = $input['session_id'] ?? '';
            $businessId = $input['business_id'] ?? '';
            $name = $input['name'] ?? '';
            $phone = $input['phone'] ?? '';
            $deviceName = $input['device_name'] ?? '';
            $email = $input['email'] ?? '';

            if (empty($sessionId)) {
                echo json_encode(['status' => 'error', 'message' => 'Session ID is required.']);
                break;
            }

            // Route to correct tenant database dynamically using master DB mapping lookup
            $localDb = $db;
            if ($businessId) {
                $bizStmt = $masterDb->prepare("SELECT db_name, db_user, db_password FROM businesses WHERE id = ?");
                $bizStmt->execute([$businessId]);
                $bizDetails = $bizStmt->fetch();
                if ($bizDetails) {
                    $tName = $bizDetails['db_name'];
                    $tUser = $bizDetails['db_user'] ?? $user;
                    $tPass = $bizDetails['db_password'] ?? $password;
                    try {
                        $localDb = new PDO("mysql:host=$host;port=$port;dbname={$tName};charset=utf8mb4", $tUser, $tPass, [
                            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
                        ]);
                        $localDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    } catch (PDOException $e) {
                        // Fallback
                    }
                }
            }

            // Remove existing intake session (in case of double submission) and insert fresh entry in tenant DB
            $stmtDel = $localDb->prepare("DELETE FROM booking_intakes WHERE session_id = ?");
            $stmtDel->execute([$sessionId]);

            $stmtIns = $localDb->prepare("INSERT INTO booking_intakes (session_id, name, phone, device_name, email) VALUES (?, ?, ?, ?, ?)");
            $stmtIns->execute([$sessionId, $name, $phone, $deviceName, $email]);

            echo json_encode(['status' => 'success', 'message' => 'Intake submitted.']);
            break;

        case 'check_intake':
            $sessionId = $_GET['session_id'] ?? '';
            if (empty($sessionId)) {
                echo json_encode(['status' => 'error', 'message' => 'Session ID is required.']);
                break;
            }

            $stmt = $db->prepare("SELECT name, phone, device_name, email FROM booking_intakes WHERE session_id = ?");
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

        case 'save_booking':
            if ($db === null || !$tenantDbConnected) {
                echo json_encode(['status' => 'error', 'message' => "Database Connection Error: This business is not connected to its relevant database. Please create the database '{$tenantDbName}' in Hostinger and assign user privileges."]);
                break;
            }
            $input = json_decode(file_get_contents('php://input'), true);
            $customer_name = trim($input['name'] ?? '');
            $phone_number = trim($input['phone'] ?? '');
            $email = trim($input['email'] ?? '');
            $device_model = trim($input['device'] ?? '');
            $problem_description = trim($input['fault'] ?? '');
            $total_quote = floatval($input['quote'] ?? 0);
            $deposit_paid = floatval($input['deposit'] ?? 0);
            $balance_due = max(0, $total_quote - $deposit_paid);
            $business_name = trim($input['business_name'] ?? '');
            $booked_by = $_SESSION['username'] ?? 'Guest';
            
            // Format Ticket ID as: TI-YYYYMMDDHHMM
            $ticket_id = 'TI-' . date('YmdHi');
            
            if (!$customer_name || !$phone_number || !$device_model || !$problem_description) {
                throw new Exception('Customer name, phone, device, and problem description are required.');
            }
            
            $stmt = $db->prepare("INSERT INTO bookings (ticket_id, customer_name, phone_number, email, device_model, problem_description, total_quote, deposit_paid, balance_due, business_name, booked_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $ticket_id,
                $customer_name,
                $phone_number,
                $email,
                $device_model,
                $problem_description,
                $total_quote,
                $deposit_paid,
                $balance_due,
                $business_name,
                $booked_by
            ]);
            
            echo json_encode([
                'status' => 'success',
                'ticket_id' => $ticket_id
            ]);
            break;

        case 'get_bookings':
            if ($db === null || !$tenantDbConnected) {
                echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
                break;
            }
            $search = trim($_GET['search'] ?? '');
            $status = trim($_GET['status'] ?? '');
            
            $query = "SELECT * FROM bookings WHERE 1=1";
            $params = [];
            
            if ($status !== '') {
                $query .= " AND status = ?";
                $params[] = $status;
            }
            
            if ($search !== '') {
                $query .= " AND (ticket_id LIKE ? OR customer_name LIKE ? OR phone_number LIKE ?)";
                $like = "%$search%";
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }
            
            $query .= " ORDER BY created_at DESC LIMIT 200";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'status' => 'success',
                'data' => $jobs
            ]);
            break;

        case 'update_booking':
            if ($db === null || !$tenantDbConnected) {
                echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
                break;
            }
            $input = json_decode(file_get_contents('php://input'), true);
            $id = intval($input['id'] ?? 0);
            
            if (!$id) {
                throw new Exception('Invalid booking ID.');
            }
            
            // Determine if status update only, or full edit
            if (isset($input['status_only']) && $input['status_only']) {
                $status = trim($input['status'] ?? 'Pending');
                $stmt = $db->prepare("UPDATE bookings SET status = ? WHERE id = ?");
                $stmt->execute([$status, $id]);
            } else {
                $name = trim($input['name'] ?? '');
                $phone = trim($input['phone'] ?? '');
                $email = trim($input['email'] ?? '');
                $device = trim($input['device'] ?? '');
                $fault = trim($input['fault'] ?? '');
                $quote = floatval($input['quote'] ?? 0);
                $deposit = floatval($input['deposit'] ?? 0);
                $status = trim($input['status'] ?? 'Pending');
                $notes = trim($input['notes'] ?? '');
                $balance = max(0, $quote - $deposit);
                
                if (!$name || !$phone || !$device || !$fault) {
                    throw new Exception('Name, Phone, Device, and Fault Description are required fields.');
                }
                
                $stmt = $db->prepare("UPDATE bookings SET customer_name = ?, phone_number = ?, email = ?, device_model = ?, problem_description = ?, total_quote = ?, deposit_paid = ?, balance_due = ?, status = ?, notes = ? WHERE id = ?");
                $stmt->execute([$name, $phone, $email, $device, $fault, $quote, $deposit, $balance, $status, $notes, $id]);
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Booking updated successfully.'
            ]);
            break;

        case 'customer_lookup':
            $businessId = trim($_GET['business_id'] ?? '');
            $search = trim($_GET['search'] ?? '');
            
            if (!$businessId) {
                throw new Exception('Business ID is required.');
            }
            if (!$search) {
                throw new Exception('Search query is required.');
            }
            
            // Connect to tenant DB dynamically
            $localDb = $db;
            $bizStmt = $masterDb->prepare("SELECT db_name, db_user, db_password FROM businesses WHERE id = ?");
            $bizStmt->execute([$businessId]);
            $bizDetails = $bizStmt->fetch();
            if ($bizDetails) {
                $tName = $bizDetails['db_name'];
                $tUser = $bizDetails['db_user'] ?? $user;
                $tPass = $bizDetails['db_password'] ?? $password;
                try {
                    $localDb = new PDO("mysql:host=$host;port=$port;dbname={$tName};charset=utf8mb4", $tUser, $tPass, [
                        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
                    ]);
                } catch (PDOException $e) {
                    throw new Exception('Could not connect to tenant database.');
                }
            } else {
                throw new Exception('Invalid Business ID.');
            }
            
            // Fetch matching jobs
            $stmt = $localDb->prepare("SELECT ticket_id, customer_name, device_model, problem_description, status, total_quote, deposit_paid, balance_due, created_at FROM bookings WHERE ticket_id = ? OR phone_number = ? ORDER BY created_at DESC");
            $stmt->execute([$search, $search]);
            $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'status' => 'success',
                'data' => $jobs
            ]);
            break;

        case 'get_payments':
            if ($db === null || !$tenantDbConnected) {
                echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
                break;
            }
            $bookingId = intval($_GET['booking_id'] ?? 0);
            if (!$bookingId) {
                throw new Exception('Invalid booking ID.');
            }
            $stmt = $db->prepare("SELECT * FROM booking_payments WHERE booking_id = ? ORDER BY created_at ASC");
            $stmt->execute([$bookingId]);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $payments]);
            break;

        case 'add_payment':
            if ($db === null || !$tenantDbConnected) {
                echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
                break;
            }
            $input = json_decode(file_get_contents('php://input'), true);
            $bookingId = intval($input['booking_id'] ?? 0);
            $amount = floatval($input['amount'] ?? 0);
            $method = trim($input['payment_method'] ?? 'Cash');
            $type = trim($input['payment_type'] ?? 'Partial');
            $ref = trim($input['reference_code'] ?? '');
            
            if (!$bookingId) {
                throw new Exception('Invalid booking ID.');
            }
            if ($amount <= 0) {
                throw new Exception('Payment amount must be greater than zero.');
            }

            // Get Ticket ID
            $stmtTicket = $db->prepare("SELECT ticket_id, total_quote FROM bookings WHERE id = ?");
            $stmtTicket->execute([$bookingId]);
            $bookingData = $stmtTicket->fetch(PDO::FETCH_ASSOC);
            if (!$bookingData) {
                throw new Exception('Booking not found.');
            }
            $ticketId = $bookingData['ticket_id'];
            $quote = floatval($bookingData['total_quote']);
            
            // Check for duplicate payment (same booking, amount, and method within the last 10 seconds)
            $stmtCheckDup = $db->prepare("SELECT id FROM booking_payments WHERE booking_id = ? AND amount = ? AND payment_method = ? AND created_at >= NOW() - INTERVAL 10 SECOND LIMIT 1");
            $stmtCheckDup->execute([$bookingId, $amount, $method]);
            if ($stmtCheckDup->fetch()) {
                throw new Exception('Duplicate payment detected. Please wait a moment.');
            }

            // Check remaining balance before adding payment
            $stmtSumBefore = $db->prepare("SELECT SUM(amount) FROM booking_payments WHERE booking_id = ?");
            $stmtSumBefore->execute([$bookingId]);
            $totalPaidBefore = floatval($stmtSumBefore->fetchColumn() ?: 0);
            $currentBalanceDue = max(0, $quote - $totalPaidBefore);
            
            if ($currentBalanceDue <= 0) {
                throw new Exception('This booking has already been fully paid.');
            }
            
            if ($amount > $currentBalanceDue + 0.01) {
                throw new Exception('Payment amount (€' . number_format($amount, 2) . ') cannot exceed the remaining balance due (€' . number_format($currentBalanceDue, 2) . ').');
            }

            // Insert ledger entry
            $username = $_SESSION['username'] ?? 'System';
            $stmtIns = $db->prepare("INSERT INTO booking_payments (booking_id, ticket_id, amount, payment_method, payment_type, reference_code, received_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtIns->execute([$bookingId, $ticketId, $amount, $method, $type, $ref, $username]);
            
            // Recalculate totals
            $stmtSum = $db->prepare("SELECT SUM(amount) FROM booking_payments WHERE booking_id = ?");
            $stmtSum->execute([$bookingId]);
            $totalPaid = floatval($stmtSum->fetchColumn() ?: 0);
            
            $balanceDue = max(0, $quote - $totalPaid);
            
            // Update booking table
            $stmtUpd = $db->prepare("UPDATE bookings SET deposit_paid = ?, balance_due = ? WHERE id = ?");
            $stmtUpd->execute([$totalPaid, $balanceDue, $bookingId]);
            
            // Fetch updated payments list
            $stmtPayments = $db->prepare("SELECT * FROM booking_payments WHERE booking_id = ? ORDER BY created_at ASC");
            $stmtPayments->execute([$bookingId]);
            $payments = $stmtPayments->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Payment recorded successfully.',
                'data' => [
                    'payments' => $payments,
                    'deposit_paid' => $totalPaid,
                    'balance_due' => $balanceDue
                ]
            ]);
            break;
        case 'get_printer_settings':
            $stmt = $db->query("SELECT font_size, font_family FROM printer_settings LIMIT 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$settings) {
                $settings = ['font_size' => 12, 'font_family' => "'Courier New', Courier, monospace"];
            }
            echo json_encode([
                'status' => 'success',
                'data' => $settings
            ]);
            break;

        case 'save_printer_settings':
            $input = json_decode(file_get_contents('php://input'), true);
            $fontSize = intval($input['font_size'] ?? 12);
            $fontFamily = trim($input['font_family'] ?? "'Courier New', Courier, monospace");
            
            // Check if there is any row
            $count = intval($db->query("SELECT COUNT(*) FROM printer_settings")->fetchColumn());
            if ($count === 0) {
                $stmt = $db->prepare("INSERT INTO printer_settings (font_size, font_family) VALUES (?, ?)");
                $stmt->execute([$fontSize, $fontFamily]);
            } else {
                $stmt = $db->prepare("UPDATE printer_settings SET font_size = ?, font_family = ?");
                $stmt->execute([$fontSize, $fontFamily]);
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Printer settings saved successfully.',
                'data' => [
                    'font_size' => $fontSize,
                    'font_family' => $fontFamily
                ]
            ]);
            break;

        case 'search_products':
            if ($db === null || !$tenantDbConnected) {
                echo json_encode(['status' => 'error', 'message' => 'Database not connected']);
                break;
            }

            // Self-seed if categories table is empty to make testing simple
            try {
                $countCat = intval($db->query("SELECT COUNT(*) FROM categories")->fetchColumn());
                if ($countCat === 0) {
                    // 1. Insert Categories
                    $db->exec("INSERT INTO categories (id, name, type, created_at, updated_at) VALUES 
                        (1, 'Parts', 'part', NOW(), NOW()),
                        (2, 'Services', 'service', NOW(), NOW()),
                        (3, 'Retail Accessories', 'retail', NOW(), NOW())
                    ");
                    
                    // 2. Insert Products
                    $db->exec("INSERT INTO products (id, category_id, brand, name, description, created_at, updated_at) VALUES 
                        (1, 1, 'Apple', 'iPhone 11 Battery', 'Replacement Li-Ion Battery', NOW(), NOW()),
                        (2, 1, 'Apple', 'iPhone 13 Pro Screen', 'OLED Display Panel Assembly', NOW(), NOW()),
                        (3, 2, 'Software', 'OS Software Reset', 'Full factory restore and update', NOW(), NOW()),
                        (4, 3, 'Belkin', 'USB-C Charging Cable 1m', 'Fast charge compatible cable', NOW(), NOW()),
                        (5, 3, 'TechInbox', 'Tempered Glass Screen Protector', 'Premium 9H glass protection', NOW(), NOW())
                    ");

                    // 3. Insert Product Variants
                    $db->exec("INSERT INTO product_variants (product_id, sku, barcode, attribute_summary, cost_price, retail_price, stock_quantity, is_serialized, created_at, updated_at) VALUES 
                        (1, 'IP11-BATT', '880112233', 'Internal Battery', 5.00, 20.00, 15, 0, NOW(), NOW()),
                        (2, 'IP13P-SCRN', '880112244', 'Original OLED', 45.00, 120.00, 8, 0, NOW(), NOW()),
                        (3, 'SRV-RST', '880112255', 'Diagnostic Lab', 0.00, 15.00, 9999, 0, NOW(), NOW()),
                        (4, 'BEL-USBC-1M', '880112266', 'Black Cable', 2.50, 10.00, 25, 0, NOW(), NOW()),
                        (5, 'TI-TG-GEN', '880112277', 'Clear Glass', 0.50, 5.00, 100, 0, NOW(), NOW())
                    ");
                }
            } catch (Exception $e) {}

            $query = trim($_GET['q'] ?? '');
            if (empty($query)) {
                echo json_encode(['status' => 'success', 'data' => []]);
                break;
            }
            
            // Search in products and product_variants tables
            $stmt = $db->prepare("
                SELECT pv.id, p.name as product_name, pv.sku, pv.attribute_summary, pv.retail_price, pv.stock_quantity, pv.is_serialized 
                FROM product_variants pv
                JOIN products p ON pv.product_id = p.id
                WHERE p.name LIKE ? OR pv.sku LIKE ? OR pv.attribute_summary LIKE ?
                LIMIT 15
            ");
            $searchTerm = '%' . $query . '%';
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'status' => 'success',
                'data' => $results
            ]);
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
