<?php
// admin.php
session_start();
require_once __DIR__ . '/db.php';

// Enforce admin login
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header("Location: index.php");
    exit();
}

$successMsg = "";
$errorMsg = "";

// Retrieve current printer settings from isolated tenant database
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

// 1. Handle Form Submissions securely
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Create User
    if ($action === 'create_user') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
        $assignedBiz = trim($_POST['assigned_business'] ?? '');
        
        if ($username && $password) {
            // Generate clean string slug for user ID
            $userId = preg_replace('/[^a-z0-9\-]/', '', strtolower(str_replace(' ', '-', $username)));
            
            // Check if username or slug already exists (case-insensitive check)
            $checkUser = $masterDb->prepare("SELECT id FROM users WHERE id = ? OR LOWER(username) = LOWER(?)");
            $checkUser->execute([$userId, $username]);
            
            if ($checkUser->fetch()) {
                $errorMsg = "Username '" . htmlspecialchars($username) . "' is already taken. Please choose a unique username.";
            } else {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                try {
                    $stmt = $masterDb->prepare("INSERT INTO users (id, username, password, is_admin, assigned_business_id) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$userId, $username, $hashed, $isAdmin, $assignedBiz ?: null]);
                    $successMsg = "User '" . htmlspecialchars($username) . "' created successfully.";
                } catch (Exception $e) {
                    $errorMsg = "Failed to create user: " . $e->getMessage();
                }
            }
        } else {
            $errorMsg = "Username and Password are required.";
        }
    }
    
    // Create Business
    elseif ($action === 'create_business') {
        $bizId = trim($_POST['biz_id'] ?? '');
        $bizName = trim($_POST['biz_name'] ?? '');
        $bizContact = trim($_POST['biz_contact'] ?? '');
        $bizEmail = trim($_POST['biz_email'] ?? '');
        $bizAddress = trim($_POST['biz_address'] ?? '');
        
        // Sanitize business ID to lowercase letters, numbers, and dashes
        $bizId = preg_replace('/[^a-z0-9\-]/', '', strtolower($bizId));
        
        if ($bizId && $bizName) {
            $dbName = sanitizeTenantDbName($bizName, $masterDbName);
            try {
                $stmt = $masterDb->prepare("INSERT INTO businesses (id, name, db_name, contact, email, address) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$bizId, $bizName, $dbName, $bizContact, $bizEmail, $bizAddress]);
                $successMsg = "Business '" . htmlspecialchars($bizName) . "' created successfully. Database Target: " . htmlspecialchars($dbName);
            } catch (Exception $e) {
                $errorMsg = "Failed to create business: " . $e->getMessage();
            }
        } else {
            $errorMsg = "Business ID (Slug) and Business Name are required.";
        }
    }
    
    // Update User Roles & Privileges
    elseif ($action === 'update_privileges') {
        $userId = trim($_POST['user_id'] ?? '');
        $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
        $assignedBiz = trim($_POST['assigned_business'] ?? '');
        
        if (!empty($userId)) {
            try {
                $stmt = $masterDb->prepare("UPDATE users SET is_admin = ?, assigned_business_id = ? WHERE id = ?");
                $stmt->execute([$isAdmin, $assignedBiz ?: null, $userId]);
                $successMsg = "User privileges updated successfully.";
            } catch (Exception $e) {
                $errorMsg = "Failed to update privileges: " . $e->getMessage();
            }
        }
    }
}

// 2. Fetch Lists for Dashboard
$users = $masterDb->query("SELECT id, username, is_admin, assigned_business_id FROM users ORDER BY username")->fetchAll();
$businesses = $masterDb->query("SELECT * FROM businesses ORDER BY name")->fetchAll();

$activityQuery = "
    SELECT h.id, u.username, b.name AS business_name, h.work_date, h.login_time
    FROM user_duty_history h
    JOIN users u ON h.user_id = u.id
    JOIN businesses b ON h.business_id = b.id
    ORDER BY h.work_date DESC, h.login_time DESC
    LIMIT 50
";
$activities = $masterDb->query($activityQuery)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - TechInbox</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/public/icons/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/svg+xml" href="/public/icons/icon.svg">
    <link rel="shortcut icon" href="/public/icons/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/public/icons/apple-touch-icon.png">
    <link rel="manifest" href="/public/icons/site.webmanifest">
    
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
            --brand-teal: #008272;
            --brand-green: #7fba00;
            --brand-red: #f25022;
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
        
        .table-responsive {
            font-size: 14px;
        }

        .nav-tabs .nav-link {
            color: var(--text-secondary);
            font-weight: 500;
            border: none;
            border-bottom: 2px solid transparent;
        }

        .nav-tabs .nav-link.active {
            color: var(--brand-blue);
            background: transparent;
            border-bottom: 2px solid var(--brand-blue);
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Header Navigation -->
    <?php require_once __DIR__ . '/header.php'; ?>

    <main class="container-fluid px-2 px-md-4 py-3 py-md-4 flex-grow-1"
          x-data="{
              fontSize: <?php echo intval($printerFontSize); ?>,
              fontFamily: '<?php echo addslashes($printerFontFamily); ?>',
              isSaving: false,
              successMsg: '',
              errorMsg: '',
              async saveSettings() {
                  this.isSaving = true;
                  this.successMsg = '';
                  this.errorMsg = '';
                  try {
                      const res = await fetch('api.php?action=save_printer_settings', {
                          method: 'POST',
                          headers: { 'Content-Type': 'application/json' },
                          body: JSON.stringify({
                              font_size: this.fontSize,
                              font_family: this.fontFamily
                          })
                      });
                      const result = await res.json();
                      if (result.status === 'success') {
                          this.successMsg = 'Printer settings saved successfully.';
                      } else {
                          this.errorMsg = result.message || 'Failed to save settings.';
                      }
                  } catch (e) {
                      this.errorMsg = 'Connection error.';
                  } finally {
                      this.isSaving = false;
                  }
              }
          }">
        
        <!-- Header Title -->
        <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
            <div>
                <h1 class="h3 fw-bold text-dark mb-0">🔑 Admin Central Panel</h1>
                <p class="text-muted small mb-0">Manage businesses, user privileges, and track global activity history.</p>
            </div>
            <a href="index.php" class="btn btn-sm btn-outline-secondary rounded-1">Back to Portal</a>
        </div>

        <?php if ($successMsg): ?>
            <div class="alert alert-success border-0 shadow-sm mb-4" role="alert" style="border-left: 4px solid var(--brand-green) !important;">
                🟢 <?php echo htmlspecialchars($successMsg); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <div class="alert alert-danger border-0 shadow-sm mb-4" role="alert" style="border-left: 4px solid var(--brand-red) !important;">
                🔴 <?php echo htmlspecialchars($errorMsg); ?>
            </div>
        <?php endif; ?>

        <!-- Nav Tabs -->
        <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users-pane" type="button" role="tab" aria-controls="users-pane" aria-selected="true">👥 Users & Privileges</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="businesses-tab" data-bs-toggle="tab" data-bs-target="#businesses-pane" type="button" role="tab" aria-controls="businesses-pane" aria-selected="false">🏢 Businesses Setup</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-pane" type="button" role="tab" aria-controls="history-pane" aria-selected="false">📜 Global Activity Log</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="printer-tab" data-bs-toggle="tab" data-bs-target="#printer-pane" type="button" role="tab" aria-controls="printer-pane" aria-selected="false">🖨️ Printer Settings</button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="adminTabsContent">
            
            <!-- Users Pane -->
            <div class="tab-pane fade show active" id="users-pane" role="tabpanel" aria-labelledby="users-tab">
                <div class="row g-4">
                    <!-- Left: Create User -->
                    <div class="col-12 col-lg-4">
                        <div class="card shadow-sm p-4">
                            <h2 class="h5 fw-bold text-dark mb-3">Add New User</h2>
                            <form method="POST">
                                <input type="hidden" name="action" value="create_user">
                                <div class="mb-3">
                                    <label class="small fw-bold text-muted text-uppercase d-block mb-1">Username *</label>
                                    <input type="text" name="username" class="form-control rounded-1" required placeholder="e.g. JohnDoe">
                                </div>
                                <div class="mb-3">
                                    <label class="small fw-bold text-muted text-uppercase d-block mb-1">Temporary Password *</label>
                                    <input type="password" name="password" class="form-control rounded-1" required placeholder="Password">
                                </div>
                                <div class="mb-3">
                                    <label class="small fw-bold text-muted text-uppercase d-block mb-1">Assign Business Access</label>
                                    <select name="assigned_business" class="form-select rounded-1">
                                        <option value="">Full Access (All Businesses)</option>
                                        <?php foreach ($businesses as $biz): ?>
                                            <option value="<?php echo htmlspecialchars($biz['id']); ?>"><?php echo htmlspecialchars($biz['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="text-muted" style="font-size: 11px;">Restricts the user to this branch.</span>
                                </div>
                                <div class="mb-4 form-check">
                                    <input type="checkbox" name="is_admin" class="form-check-input" id="isAdminCheck">
                                    <label class="form-check-label small fw-bold text-muted text-uppercase" for="isAdminCheck">Grant Admin Access</label>
                                </div>
                                <button type="submit" class="btn w-100 text-white rounded-1" style="background-color: var(--brand-blue); font-weight: 600;">Create User</button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Right: Users List -->
                    <div class="col-12 col-lg-8">
                        <div class="card shadow-sm p-4">
                            <h2 class="h5 fw-bold text-dark mb-3">User Directory</h2>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr class="table-light">
                                            <th>Username</th>
                                            <th>Role</th>
                                            <th>Assigned Branch Access</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $u): ?>
                                            <tr>
                                                <td class="fw-semibold text-dark"><?php echo htmlspecialchars($u['username']); ?></td>
                                                <td>
                                                    <?php if ($u['is_admin']): ?>
                                                        <span class="badge bg-danger">Admin</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Staff</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if ($u['assigned_business_id']) {
                                                        foreach ($businesses as $b) {
                                                            if ($b['id'] === $u['assigned_business_id']) {
                                                                echo '<span class="text-dark fw-medium">' . htmlspecialchars($b['name']) . '</span>';
                                                                break;
                                                            }
                                                        }
                                                    } else {
                                                        echo '<span class="text-success fw-semibold">Global (All Branches)</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td class="text-end">
                                                    <!-- Simple Inline Role/Privilege Edit Form -->
                                                    <form method="POST" class="d-inline-flex gap-2 align-items-center">
                                                        <input type="hidden" name="action" value="update_privileges">
                                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                        <select name="assigned_business" class="form-select form-select-sm rounded-1" style="width: auto; font-size: 12px;">
                                                            <option value="">Global Access</option>
                                                            <?php foreach ($businesses as $biz): ?>
                                                                <option value="<?php echo htmlspecialchars($biz['id']); ?>" <?php echo $u['assigned_business_id'] === $biz['id'] ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($biz['name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <div class="form-check form-check-inline m-0">
                                                            <input class="form-check-input" type="checkbox" name="is_admin" <?php echo $u['is_admin'] ? 'checked' : ''; ?> style="transform: scale(0.85);">
                                                            <label class="small text-muted" style="font-size: 11px;">Admin</label>
                                                        </div>
                                                        <button type="submit" class="btn btn-sm btn-light border" style="font-size: 11px;">Save</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Businesses Pane -->
            <div class="tab-pane fade" id="businesses-pane" role="tabpanel" aria-labelledby="businesses-tab">
                <div class="row g-4">
                    <!-- Left: Create Business -->
                    <div class="col-12 col-lg-4">
                        <div class="card shadow-sm p-4">
                            <h2 class="h5 fw-bold text-dark mb-3">Add New Business</h2>
                            <form method="POST">
                                <input type="hidden" name="action" value="create_business">
                                <div class="mb-3">
                                    <label class="small fw-bold text-muted text-uppercase d-block mb-1">Business ID / Slug *</label>
                                    <input type="text" name="biz_id" class="form-control rounded-1" required placeholder="e.g. phone-lab">
                                    <span class="text-muted" style="font-size: 10px;">Unique string code (lowercase, letters and hyphens only).</span>
                                </div>
                                <div class="mb-3">
                                    <label class="small fw-bold text-muted text-uppercase d-block mb-1">Business Name *</label>
                                    <input type="text" name="biz_name" class="form-control rounded-1" required placeholder="e.g. Phone Lab Ennis">
                                </div>
                                <div class="mb-3">
                                    <label class="small fw-bold text-muted text-uppercase d-block mb-1">Contact Number</label>
                                    <input type="text" name="biz_contact" class="form-control rounded-1" placeholder="(065) XXX XXXX">
                                </div>
                                <div class="mb-3">
                                    <label class="small fw-bold text-muted text-uppercase d-block mb-1">Contact Email</label>
                                    <input type="email" name="biz_email" class="form-control rounded-1" placeholder="email@example.com">
                                </div>
                                <div class="mb-3">
                                    <label class="small fw-bold text-muted text-uppercase d-block mb-1">Address</label>
                                    <textarea name="biz_address" class="form-control rounded-1" rows="2" placeholder="Store Address"></textarea>
                                </div>
                                <button type="submit" class="btn w-100 text-white rounded-1" style="background-color: var(--brand-teal); font-weight: 600;">Create Business</button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Right: Businesses List -->
                    <div class="col-12 col-lg-8">
                        <div class="card shadow-sm p-4">
                            <h2 class="h5 fw-bold text-dark mb-3">Configured Businesses</h2>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr class="table-light">
                                            <th>Display Name</th>
                                            <th>Immutable ID (Slug)</th>
                                            <th>Hostinger Database Name</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($businesses as $b): ?>
                                            <tr>
                                                <td class="fw-bold text-dark"><?php echo htmlspecialchars($b['name']); ?></td>
                                                <td><code class="text-danger fw-semibold"><?php echo htmlspecialchars($b['id']); ?></code></td>
                                                <td><code class="text-teal fw-semibold"><?php echo htmlspecialchars($b['db_name']); ?></code></td>
                                                <td style="font-size: 12px; line-height: 1.3;">
                                                    <strong>Tel:</strong> <?php echo htmlspecialchars($b['contact'] ?: 'N/A'); ?><br>
                                                    <strong>Email:</strong> <?php echo htmlspecialchars($b['email'] ?: 'N/A'); ?><br>
                                                    <strong>Addr:</strong> <?php echo htmlspecialchars($b['address'] ?: 'N/A'); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3 p-3 bg-light border rounded-1">
                                <h4 class="h6 fw-bold text-dark mb-2">💡 Hostinger Database Mapping Notice</h4>
                                <p class="text-muted mb-0" style="font-size: 12px;">When you add a new business here, write down the corresponding **Hostinger Database Name**. You will need to create this database in your Hostinger control panel using your standard credentials so that the tenant setup works correctly.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- History Pane -->
            <div class="tab-pane fade" id="history-pane" role="tabpanel" aria-labelledby="history-tab">
                <div class="card shadow-sm p-4">
                    <h2 class="h5 fw-bold text-dark mb-3">Central Shift & Duty History (Last 50 Entries)</h2>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr class="table-light">
                                    <th>User</th>
                                    <th>Logged-In Branch</th>
                                    <th>Work Date</th>
                                    <th>Login Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($activities) === 0): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No duty history logs recorded yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($activities as $act): ?>
                                        <tr>
                                            <td class="fw-semibold text-dark"><?php echo htmlspecialchars($act['username']); ?></td>
                                            <td class="fw-medium text-teal"><?php echo htmlspecialchars($act['business_name']); ?></td>
                                            <td><?php echo htmlspecialchars($act['work_date']); ?></td>
                                            <td class="text-muted" style="font-size: 13px;"><?php echo htmlspecialchars($act['login_time']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Printer Settings Pane -->
            <div class="tab-pane fade" id="printer-pane" role="tabpanel" aria-labelledby="printer-tab">
                <div class="row g-4">
                    <div class="col-12 col-md-6">
                        <div class="card shadow-sm p-4">
                            <h2 class="h5 fw-bold text-dark mb-3">🖨️ Thermal Printer Configuration</h2>
                            <p class="text-muted small mb-4">Modify the default font styling and print scaling size applied across all branch customer receipts and shift closing sheets.</p>
                            
                            <template x-if="successMsg">
                                <div class="alert alert-success border-0 py-2 small mb-3 text-success" style="background-color: #d4edda; border-left: 4px solid var(--brand-green) !important;" x-text="successMsg"></div>
                            </template>
                            <template x-if="errorMsg">
                                <div class="alert alert-danger border-0 py-2 small mb-3 text-danger" style="background-color: #f8d7da; border-left: 4px solid var(--brand-red) !important;" x-text="errorMsg"></div>
                            </template>

                            <div class="mb-4">
                                <label class="form-label small fw-bold text-secondary">Default Printer Font</label>
                                <select class="form-select" x-model="fontFamily">
                                    <option value="'Courier New', Courier, monospace">Courier Monospace (Default Thermal)</option>
                                    <option value="'Consolas', 'Monaco', 'Lucida Console', monospace">Consolas Monospace (Ultra Clear)</option>
                                    <option value="'Segoe UI', system-ui, sans-serif">Segoe UI System (High Contrast)</option>
                                    <option value="Arial, Helvetica, sans-serif">Arial Standard</option>
                                    <option value="'Outfit', 'Segoe UI', sans-serif">Outfit (Brand Font)</option>
                                    <option value="Georgia, serif">Georgia Serif</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-bold text-secondary d-block">Font Print Size</label>
                                <div class="d-flex align-items-center gap-3">
                                    <button type="button" @click="if(fontSize > 8) fontSize--" class="btn btn-outline-secondary px-3" style="font-weight: bold; font-size: 16px;">−</button>
                                    <span class="fs-5 fw-bold text-dark" style="min-width: 60px; text-align: center;"><span x-text="fontSize"></span> px</span>
                                    <button type="button" @click="if(fontSize < 24) fontSize++" class="btn btn-outline-secondary px-3" style="font-weight: bold; font-size: 16px;">+</button>
                                </div>
                            </div>

                            <button type="button" @click="saveSettings()" class="btn btn-primary px-4 py-2 text-white" :disabled="isSaving">
                                <span x-show="!isSaving">💾 Save Settings</span>
                                <span x-show="isSaving" class="spinner-border spinner-border-sm" role="status"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Right Side: Live Thermal Receipt Preview -->
                    <div class="col-12 col-md-6">
                        <div class="card shadow-sm p-4">
                            <h2 class="h5 fw-bold text-dark mb-3">Print Preview</h2>
                            
                            <div class="border p-3 bg-white" style="width: 80mm; max-width: 100%; border-style: dashed !important; border-color: #c0c0c0 !important; border-radius: 0 !important;">
                                <div :style="'font-family: ' + fontFamily + '; font-size: ' + fontSize + 'px; line-height: 1.35; color: #000;'">
                                    <div class="text-center mb-2" style="border-bottom: 1px dashed #000; padding-bottom: 6px;">
                                        <h3 style="font-size: 1.25em; font-weight: bold; margin: 0;">PHONE LAB</h3>
                                        <p style="font-size: 0.85em; margin: 2px 0 0 0;">32 O'Connell St, Ennis</p>
                                    </div>
                                    
                                    <div style="font-size: 0.9em; margin-bottom: 6px;">
                                        <strong>Ticket #:</strong> TKT-9831<br>
                                        <strong>Date:</strong> 17/07/2026 12:45<br>
                                        <strong>Client:</strong> John Doe (0891234567)
                                    </div>
                                    
                                    <div style="border-bottom: 1px dashed #000; margin-bottom: 6px;"></div>
                                    
                                    <div style="font-size: 0.9em; margin-bottom: 6px;">
                                        <strong>Device:</strong> iPhone 15 Pro Max<br>
                                        <strong>Fault:</strong> Screen Replacement
                                    </div>
                                    
                                    <div style="border-bottom: 1px dashed #000; padding-bottom: 4px; margin-bottom: 6px;"></div>
                                    
                                    <div style="font-size: 0.9em; display: flex; justify-content: space-between;">
                                        <span>Total Quote:</span>
                                        <strong>€150.00</strong>
                                    </div>
                                    <div style="font-size: 0.9em; display: flex; justify-content: space-between;">
                                        <span>Deposit Paid:</span>
                                        <strong>€50.00</strong>
                                    </div>
                                    <div style="font-size: 1em; font-weight: bold; display: flex; justify-content: space-between; border-top: 1px dashed #000; padding-top: 4px; margin-top: 4px;">
                                        <span>Balance Due:</span>
                                        <span>€100.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
