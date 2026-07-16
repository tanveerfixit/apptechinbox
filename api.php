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
