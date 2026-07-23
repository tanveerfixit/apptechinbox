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

        case 'get_protector_stocks':
            // Auto-create protector_stocks table in tenant DB if missing
            $db->exec("CREATE TABLE IF NOT EXISTS protector_stocks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                brand VARCHAR(255) NOT NULL,
                model VARCHAR(255) NOT NULL,
                glass_type VARCHAR(255) NOT NULL,
                screen_size_inch VARCHAR(50) NULL,
                dimensions_mm VARCHAR(100) NULL,
                stock_qty INT DEFAULT 0,
                min_threshold INT DEFAULT 3,
                bin_location VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_brand_model_glass_variant (brand, model, glass_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            // Ensure new columns exist on older tables
            try { $db->exec("ALTER TABLE protector_stocks ADD COLUMN screen_size_inch VARCHAR(50) NULL AFTER glass_type;"); } catch(Exception $e){}
            try { $db->exec("ALTER TABLE protector_stocks ADD COLUMN dimensions_mm VARCHAR(100) NULL AFTER screen_size_inch;"); } catch(Exception $e){}

            // Master dimension presets
            $dimensionPresets = [
                // Apple
                'iPhone 17 Pro Max' => ['screen_size_inch' => '6.9"', 'dimensions_mm' => '163.0 x 77.5 mm'],
                'iPhone 17 Pro'     => ['screen_size_inch' => '6.3"', 'dimensions_mm' => '149.6 x 71.5 mm'],
                'iPhone 17 Air'     => ['screen_size_inch' => '6.6"', 'dimensions_mm' => '156.0 x 74.0 mm'],
                'iPhone 17'         => ['screen_size_inch' => '6.3"', 'dimensions_mm' => '149.6 x 71.5 mm'],
                'iPhone 17e'        => ['screen_size_inch' => '6.1"', 'dimensions_mm' => '146.7 x 71.5 mm'],
                'iPhone 16 Pro Max' => ['screen_size_inch' => '6.9"', 'dimensions_mm' => '163.0 x 77.6 mm'],
                'iPhone 16 Pro'     => ['screen_size_inch' => '6.3"', 'dimensions_mm' => '149.6 x 71.5 mm'],
                'iPhone 16 Plus'    => ['screen_size_inch' => '6.7"', 'dimensions_mm' => '160.9 x 77.8 mm'],
                'iPhone 16'         => ['screen_size_inch' => '6.1"', 'dimensions_mm' => '147.6 x 71.6 mm'],
                'iPhone 16e'        => ['screen_size_inch' => '6.1"', 'dimensions_mm' => '146.7 x 71.5 mm'],
                'iPhone 15 Pro Max' => ['screen_size_inch' => '6.7"', 'dimensions_mm' => '159.9 x 76.7 mm'],
                'iPhone 15 Pro'     => ['screen_size_inch' => '6.1"', 'dimensions_mm' => '146.6 x 70.6 mm'],
                'iPhone 15 Plus'    => ['screen_size_inch' => '6.7"', 'dimensions_mm' => '160.9 x 77.8 mm'],
                'iPhone 15'         => ['screen_size_inch' => '6.1"', 'dimensions_mm' => '147.6 x 71.6 mm'],
                'iPhone 14 Pro Max' => ['screen_size_inch' => '6.7"', 'dimensions_mm' => '160.7 x 77.6 mm'],
                'iPhone 14 Pro'     => ['screen_size_inch' => '6.1"', 'dimensions_mm' => '147.5 x 71.5 mm'],
                'iPhone 14 Plus'    => ['screen_size_inch' => '6.7"', 'dimensions_mm' => '160.8 x 78.1 mm'],
                'iPhone 14'         => ['screen_size_inch' => '6.1"', 'dimensions_mm' => '146.7 x 71.5 mm'],
                'iPhone 13 Pro Max' => ['screen_size_inch' => '6.7"', 'dimensions_mm' => '160.8 x 78.1 mm'],
                'iPhone 13 Pro'     => ['screen_size_inch' => '6.1"', 'dimensions_mm' => '146.7 x 71.5 mm'],
                'iPhone 13'         => ['screen_size_inch' => '6.1"', 'dimensions_mm' => '146.7 x 71.5 mm'],
                'iPhone 13 mini'    => ['screen_size_inch' => '5.4"', 'dimensions_mm' => '131.5 x 64.2 mm'],
                'iPhone 12 Pro Max' => ['screen_size_inch' => '6.7"', 'dimensions_mm' => '160.8 x 78.1 mm'],
                'iPhone 12 Pro'     => ['screen_size_inch' => '6.1"', 'dimensions_mm' => '146.7 x 71.5 mm'],
                'iPhone 12'         => ['screen_size_inch' => '6.1"', 'dimensions_mm' => '146.7 x 71.5 mm'],
                'iPhone 12 mini'    => ['screen_size_inch' => '5.4"', 'dimensions_mm' => '131.5 x 64.2 mm'],
                'iPhone 11 Pro Max' => ['screen_size_inch' => '6.5"', 'dimensions_mm' => '158.0 x 77.8 mm'],
                'iPhone 11 Pro'     => ['screen_size_inch' => '5.8"', 'dimensions_mm' => '144.0 x 71.4 mm'],
                'iPhone 11'         => ['screen_size_inch' => '6.1"', 'dimensions_mm' => '150.9 x 75.7 mm'],
                'SE (3rd Gen)'      => ['screen_size_inch' => '4.7"', 'dimensions_mm' => '138.4 x 67.3 mm'],
                'SE (2nd Gen)'      => ['screen_size_inch' => '4.7"', 'dimensions_mm' => '138.4 x 67.3 mm'],
                'XS Max'            => ['screen_size_inch' => '6.5"', 'dimensions_mm' => '157.5 x 77.4 mm'],
                'XS'                => ['screen_size_inch' => '5.8"', 'dimensions_mm' => '143.6 x 70.9 mm'],
                'XR'                => ['screen_size_inch' => '6.1"', 'dimensions_mm' => '150.9 x 75.7 mm'],
                'X'                 => ['screen_size_inch' => '5.8"', 'dimensions_mm' => '143.6 x 70.9 mm'],
                
                // Samsung
                'S26 Ultra'         => ['screen_size_inch' => '6.9"', 'dimensions_mm' => '163.5 x 79.0 mm'],
                'S26+'              => ['screen_size_inch' => '6.7"', 'dimensions_mm' => '158.5 x 75.9 mm'],
                'S26'               => ['screen_size_inch' => '6.2"', 'dimensions_mm' => '147.0 x 70.6 mm'],
                'S25 Ultra'         => ['screen_size_inch' => '6.9"', 'dimensions_mm' => '162.8 x 77.6 mm'],
                'S25+'              => ['screen_size_inch' => '6.7"', 'dimensions_mm' => '158.4 x 75.8 mm'],
                'S25'               => ['screen_size_inch' => '6.2"', 'dimensions_mm' => '146.9 x 70.4 mm'],
                'S24 Ultra'         => ['screen_size_inch' => '6.8"', 'dimensions_mm' => '162.3 x 79.0 mm'],
                'S24+'              => ['screen_size_inch' => '6.7"', 'dimensions_mm' => '158.5 x 75.9 mm'],
                'S24'               => ['screen_size_inch' => '6.2"', 'dimensions_mm' => '147.0 x 70.6 mm'],
                'S23 Ultra'         => ['screen_size_inch' => '6.8"', 'dimensions_mm' => '163.4 x 78.1 mm'],
                'S23+'              => ['screen_size_inch' => '6.6"', 'dimensions_mm' => '157.8 x 76.2 mm'],
                'S23'               => ['screen_size_inch' => '6.1"', 'dimensions_mm' => '146.3 x 70.9 mm'],
                'S22 Ultra'         => ['screen_size_inch' => '6.8"', 'dimensions_mm' => '163.3 x 77.9 mm'],
                'S22+'              => ['screen_size_inch' => '6.6"', 'dimensions_mm' => '157.4 x 75.8 mm'],
                'S22'               => ['screen_size_inch' => '6.1"', 'dimensions_mm' => '146.0 x 70.6 mm'],
                'A55'               => ['screen_size_inch' => '6.6"', 'dimensions_mm' => '161.1 x 77.4 mm'],
                'A35'               => ['screen_size_inch' => '6.6"', 'dimensions_mm' => '161.7 x 78.0 mm'],
                'A15'               => ['screen_size_inch' => '6.5"', 'dimensions_mm' => '160.1 x 76.8 mm'],
                'A54'               => ['screen_size_inch' => '6.4"', 'dimensions_mm' => '158.2 x 76.7 mm'],
                'A34'               => ['screen_size_inch' => '6.6"', 'dimensions_mm' => '161.3 x 78.1 mm'],
                'A14'               => ['screen_size_inch' => '6.6"', 'dimensions_mm' => '167.7 x 78.0 mm'],
            ];

            // Update database rows with missing dimension presets
            $upPresetStmt = $db->prepare("UPDATE protector_stocks SET screen_size_inch = ?, dimensions_mm = ? WHERE model = ? AND (screen_size_inch IS NULL OR dimensions_mm IS NULL)");
            foreach ($dimensionPresets as $mName => $dimInfo) {
                $upPresetStmt->execute([$dimInfo['screen_size_inch'], $dimInfo['dimensions_mm'], $mName]);
            }

            // Seed master models list using INSERT IGNORE so all models exist
            $samsungModels = [
                'S26 Ultra', 'S26+', 'S26', 
                'S25 Ultra', 'S25+', 'S25', 'S25 Edge', 'S25 FE', 
                'S24 Ultra', 'S24+', 'S24', 'S24 FE', 
                'S23 Ultra', 'S23+', 'S23', 'S23 FE', 
                'S22 Ultra', 'S22+', 'S22', 
                'S21 Ultra', 'S21+', 'S21', 'S21 FE', 
                'S20 Ultra', 'S20+', 'S20', 'S20 FE', 
                'S10+', 'S10', 'S10e', 
                'Note20 Ultra', 'Note20', 'Note10+', 'Note10',
                'Z Fold8 Ultra', 'Z Fold8', 'Z Flip8', 
                'Z Fold7', 'Z Flip7', 'Z Flip7 FE', 
                'Z Fold6', 'Z Flip6', 
                'Z Fold5', 'Z Flip5', 
                'Z Fold4', 'Z Flip4', 
                'Z Fold3', 'Z Flip3', 
                'Z Fold2', 'Z Flip',
                'A57', 'A37', 'A17', 
                'A56', 'A36', 'A16', 
                'A55', 'A35', 'A15', 
                'A54', 'A34', 'A14', 
                'A53', 'A33', 'A13', 
                'A52', 'A72'
            ];

            $appleModels = [
                'iPhone 17 Pro Max', 'iPhone 17 Pro', 'iPhone 17 Air', 'iPhone 17', 'iPhone 17e',
                'iPhone 16e', 'iPhone 16 Pro Max', 'iPhone 16 Pro', 'iPhone 16 Plus', 'iPhone 16',
                'iPhone 15 Pro Max', 'iPhone 15 Pro', 'iPhone 15 Plus', 'iPhone 15',
                'iPhone 14 Pro Max', 'iPhone 14 Pro', 'iPhone 14 Plus', 'iPhone 14',
                'SE (3rd Gen)', 'SE (2nd Gen)',
                'iPhone 13 Pro Max', 'iPhone 13 Pro', 'iPhone 13', 'iPhone 13 mini',
                'iPhone 12 Pro Max', 'iPhone 12 Pro', 'iPhone 12', 'iPhone 12 mini',
                'iPhone 11 Pro Max', 'iPhone 11 Pro', 'iPhone 11',
                'XS Max', 'XS', 'XR', 'X'
            ];

            $glassTypes = ['Loose Glasses', 'Aokus Thin 3D Touch', 'Aokus Cover Edge 9D', 'Aokus Loose', 'Aokus 9H', 'Ven-Dens 9H'];
            $insStmt = $db->prepare("INSERT IGNORE INTO protector_stocks (brand, model, glass_type, screen_size_inch, dimensions_mm, stock_qty, min_threshold, bin_location) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            foreach ($samsungModels as $idx => $m) {
                $gt = $glassTypes[$idx % count($glassTypes)];
                $sInch = $dimensionPresets[$m]['screen_size_inch'] ?? null;
                $sMm = $dimensionPresets[$m]['dimensions_mm'] ?? null;
                $insStmt->execute(['Samsung', $m, $gt, $sInch, $sMm, 0, 3, null]);
            }

            foreach ($appleModels as $idx => $m) {
                $gt = $glassTypes[$idx % count($glassTypes)];
                $sInch = $dimensionPresets[$m]['screen_size_inch'] ?? null;
                $sMm = $dimensionPresets[$m]['dimensions_mm'] ?? null;
                $insStmt->execute(['Apple', $m, $gt, $sInch, $sMm, 0, 3, null]);
            }

            $search = trim($_GET['search'] ?? '');
            $brand = trim($_GET['brand'] ?? '');
            $glassType = trim($_GET['glass_type'] ?? '');

            // Only return items with stock_qty > 0 in Live Inventory
            $query = "SELECT * FROM protector_stocks WHERE stock_qty > 0";
            $params = [];

            if (!empty($search)) {
                $query .= " AND (model LIKE ? OR brand LIKE ? OR screen_size_inch LIKE ? OR dimensions_mm LIKE ?)";
                $searchTerm = '%' . $search . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            if (!empty($brand)) {
                $query .= " AND brand = ?";
                $params[] = $brand;
            }
            if (!empty($glassType)) {
                $query .= " AND glass_type = ?";
                $params[] = $glassType;
            }

            $query .= " ORDER BY brand ASC, model ASC";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch distinct brands, models, and model-to-brand & dimension map for auto selection
            $brands = $db->query("SELECT DISTINCT brand FROM protector_stocks WHERE brand IS NOT NULL AND brand != '' ORDER BY brand")->fetchAll(PDO::FETCH_COLUMN);
            $models = $db->query("SELECT DISTINCT model FROM protector_stocks WHERE model IS NOT NULL AND model != '' ORDER BY model")->fetchAll(PDO::FETCH_COLUMN);
            
            $modelDetails = $db->query("SELECT DISTINCT model, brand, screen_size_inch, dimensions_mm FROM protector_stocks WHERE model IS NOT NULL AND model != ''")->fetchAll(PDO::FETCH_ASSOC);
            $modelBrandMap = [];
            $modelDimensionMap = [];
            foreach ($modelDetails as $row) {
                $modelBrandMap[$row['model']] = $row['brand'];
                $modelDimensionMap[$row['model']] = [
                    'screen_size_inch' => $row['screen_size_inch'] ?? '',
                    'dimensions_mm' => $row['dimensions_mm'] ?? ''
                ];
            }

            // Fetch reorder list
            $reorderItems = $db->query("SELECT * FROM protector_stocks WHERE stock_qty <= min_threshold ORDER BY stock_qty ASC, brand ASC, model ASC")->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'data' => $items,
                'reorder' => $reorderItems,
                'brands' => $brands,
                'models' => $models,
                'modelBrandMap' => $modelBrandMap,
                'modelDimensionMap' => $modelDimensionMap
            ]);
            break;

        case 'save_protector_stock':
            $input = json_decode(file_get_contents('php://input'), true);
            $brand = trim($input['brand'] ?? '');
            $model = trim($input['model'] ?? '');
            $glassType = trim($input['glass_type'] ?? '');
            $screenSizeInch = trim($input['screen_size_inch'] ?? '');
            $dimensionsMm = trim($input['dimensions_mm'] ?? '');
            $stockQty = intval($input['stock_qty'] ?? 0);
            $minThreshold = intval($input['min_threshold'] ?? 3);
            $binLocation = trim($input['bin_location'] ?? '');

            if (empty($brand) || empty($model) || empty($glassType)) {
                echo json_encode(['status' => 'error', 'message' => 'Brand, Model, and Glass Type are required.']);
                break;
            }

            // Check if record exists
            $checkStmt = $db->prepare("SELECT id, stock_qty FROM protector_stocks WHERE brand = ? AND model = ? AND glass_type = ?");
            $checkStmt->execute([$brand, $model, $glassType]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $newQty = $existing['stock_qty'] + $stockQty;
                $updateStmt = $db->prepare("UPDATE protector_stocks SET stock_qty = ?, min_threshold = ?, screen_size_inch = COALESCE(NULLIF(?, ''), screen_size_inch), dimensions_mm = COALESCE(NULLIF(?, ''), dimensions_mm) WHERE id = ?");
                $updateStmt->execute([$newQty, $minThreshold, $screenSizeInch, $dimensionsMm, $existing['id']]);
                $msg = "Restocked {$stockQty}x {$brand} {$model} ({$glassType})!";
            } else {
                $insertStmt = $db->prepare("INSERT INTO protector_stocks (brand, model, glass_type, screen_size_inch, dimensions_mm, stock_qty, min_threshold, bin_location) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $insertStmt->execute([$brand, $model, $glassType, $screenSizeInch, $dimensionsMm, $stockQty, $minThreshold, $binLocation]);
                $msg = "Created {$brand} {$model} ({$glassType}) with {$stockQty} units!";
            }

            echo json_encode(['status' => 'success', 'message' => $msg]);
            break;

        case 'update_protector_stock_qty':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = intval($input['id'] ?? 0);
            $change = intval($input['change'] ?? 0);

            if ($id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
                break;
            }

            $stmt = $db->prepare("UPDATE protector_stocks SET stock_qty = GREATEST(0, stock_qty + ?) WHERE id = ?");
            $stmt->execute([$change, $id]);

            echo json_encode(['status' => 'success']);
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
