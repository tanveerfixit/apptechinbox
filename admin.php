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
            $userId = preg_replace('/[^a-z0-9\-]/', '', strtolower(str_replace(' ', '-', $username)));
            
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
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="flex flex-col min-h-screen bg-[#f3f3f3] text-[#242424] font-sans antialiased">

    <!-- Header Navigation -->
    <?php require_once __DIR__ . '/header.php'; ?>

    <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-1 space-y-6"
          x-data="{
              activeTab: 'users',
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
        <?php require __DIR__ . '/nav_buttons.php'; ?>
        
        <!-- Header Title -->
        <div class="flex items-center justify-between border-b border-[#e0e0e0] pb-4">
            <div>
                <h1 class="text-2xl font-bold text-[#242424] tracking-tight">🔑 Admin Central Panel</h1>
                <p class="text-xs text-[#5c5c5c] mt-0.5">Manage businesses, user privileges, and track global activity history.</p>
            </div>
        </div>

        <?php if ($successMsg): ?>
            <div class="bg-green-50 border-l-4 border-[#7fba00] p-3 text-xs text-green-900 rounded-[4px]">
                🟢 <?php echo htmlspecialchars($successMsg); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <div class="bg-red-50 border-l-4 border-[#f25022] p-3 text-xs text-red-900 rounded-[4px]">
                🔴 <?php echo htmlspecialchars($errorMsg); ?>
            </div>
        <?php endif; ?>

        <!-- Nav Tabs -->
        <div class="border-b border-[#e0e0e0] flex gap-2">
            <button @click="activeTab = 'users'" :class="activeTab === 'users' ? 'border-[#00a4ef] text-[#00a4ef] font-bold' : 'border-transparent text-[#5c5c5c] hover:text-[#242424]'" class="px-4 py-2.5 text-xs border-b-2 transition-colors">
                👥 Users & Privileges
            </button>
            <button @click="activeTab = 'businesses'" :class="activeTab === 'businesses' ? 'border-[#00a4ef] text-[#00a4ef] font-bold' : 'border-transparent text-[#5c5c5c] hover:text-[#242424]'" class="px-4 py-2.5 text-xs border-b-2 transition-colors">
                🏢 Businesses Setup
            </button>
            <button @click="activeTab = 'history'" :class="activeTab === 'history' ? 'border-[#00a4ef] text-[#00a4ef] font-bold' : 'border-transparent text-[#5c5c5c] hover:text-[#242424]'" class="px-4 py-2.5 text-xs border-b-2 transition-colors">
                📜 Global Activity Log
            </button>
            <button @click="activeTab = 'printer'" :class="activeTab === 'printer' ? 'border-[#00a4ef] text-[#00a4ef] font-bold' : 'border-transparent text-[#5c5c5c] hover:text-[#242424]'" class="px-4 py-2.5 text-xs border-b-2 transition-colors">
                🖨️ Printer Settings
            </button>
        </div>

        <!-- Tab Content -->
        <div>
            
            <!-- Users Pane -->
            <div x-show="activeTab === 'users'" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    <!-- Left: Create User -->
                    <div class="lg:col-span-4">
                        <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-xs p-6 space-y-4">
                            <h2 class="text-sm font-bold text-[#242424]">Add New User</h2>
                            <form method="POST" class="space-y-3">
                                <input type="hidden" name="action" value="create_user">
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Username *</label>
                                    <input type="text" name="username" class="w-full px-3 py-2 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]" required placeholder="e.g. JohnDoe">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Temporary Password *</label>
                                    <input type="password" name="password" class="w-full px-3 py-2 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]" required placeholder="Password">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Assign Business Access</label>
                                    <select name="assigned_business" class="w-full px-3 py-2 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                                        <option value="">Full Access (All Businesses)</option>
                                        <?php foreach ($businesses as $biz): ?>
                                            <option value="<?php echo htmlspecialchars($biz['id']); ?>"><?php echo htmlspecialchars($biz['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="text-[10px] text-[#5c5c5c] block mt-0.5">Restricts the user to this branch.</span>
                                </div>
                                <div class="flex items-center gap-2 pt-1">
                                    <input type="checkbox" name="is_admin" class="rounded border-[#e0e0e0] text-[#00a4ef] focus:ring-0" id="isAdminCheck">
                                    <label class="text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c]" for="isAdminCheck">Grant Admin Access</label>
                                </div>
                                <div class="pt-2">
                                    <button type="submit" class="w-full py-2.5 px-4 bg-[#00a4ef] hover:bg-[#0086c4] text-white text-xs font-bold uppercase tracking-wider rounded-[4px] transition-colors shadow-xs">Create User</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Right: Users List -->
                    <div class="lg:col-span-8">
                        <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-xs p-6 space-y-4">
                            <h2 class="text-sm font-bold text-[#242424]">User Directory</h2>
                            <div class="overflow-x-auto border border-[#e0e0e0] rounded-[4px]">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead>
                                        <tr class="bg-[#fafafa] border-b border-[#e0e0e0] text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c]">
                                            <th class="px-4 py-2.5">Username</th>
                                            <th class="px-4 py-2.5">Role</th>
                                            <th class="px-4 py-2.5">Assigned Branch Access</th>
                                            <th class="px-4 py-2.5 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#e0e0e0]">
                                        <?php foreach ($users as $u): ?>
                                            <tr class="hover:bg-[#f9f9f9]">
                                                <td class="px-4 py-2.5 font-bold text-[#242424]"><?php echo htmlspecialchars($u['username']); ?></td>
                                                <td class="px-4 py-2.5">
                                                    <?php if ($u['is_admin']): ?>
                                                        <span class="px-2 py-0.5 bg-red-100 text-[#f25022] font-bold text-[10px] rounded-[4px]">Admin</span>
                                                    <?php else: ?>
                                                        <span class="px-2 py-0.5 bg-[#f3f3f3] text-[#5c5c5c] font-semibold text-[10px] rounded-[4px]">Staff</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-2.5">
                                                    <?php 
                                                    if ($u['assigned_business_id']) {
                                                        foreach ($businesses as $b) {
                                                            if ($b['id'] === $u['assigned_business_id']) {
                                                                echo '<span class="text-[#242424] font-medium">' . htmlspecialchars($b['name']) . '</span>';
                                                                break;
                                                            }
                                                        }
                                                    } else {
                                                        echo '<span class="text-[#7fba00] font-semibold">Global (All Branches)</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td class="px-4 py-2.5 text-right">
                                                    <form method="POST" class="inline-flex items-center gap-2 justify-end">
                                                        <input type="hidden" name="action" value="update_privileges">
                                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                        <select name="assigned_business" class="px-2 py-1 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424]">
                                                            <option value="">Global Access</option>
                                                            <?php foreach ($businesses as $biz): ?>
                                                                <option value="<?php echo htmlspecialchars($biz['id']); ?>" <?php echo $u['assigned_business_id'] === $biz['id'] ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($biz['name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <label class="inline-flex items-center gap-1 text-[11px] text-[#5c5c5c]">
                                                            <input type="checkbox" name="is_admin" <?php echo $u['is_admin'] ? 'checked' : ''; ?> class="rounded border-[#e0e0e0]"> Admin
                                                        </label>
                                                        <button type="submit" class="px-2.5 py-1 bg-white border border-[#e0e0e0] hover:bg-[#f3f3f3] text-[#242424] text-[11px] font-semibold rounded-[4px]">Save</button>
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
            <div x-show="activeTab === 'businesses'" class="space-y-6" style="display: none;">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    <!-- Left: Create Business -->
                    <div class="lg:col-span-4">
                        <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-xs p-6 space-y-4">
                            <h2 class="text-sm font-bold text-[#242424]">Add New Business</h2>
                            <form method="POST" class="space-y-3">
                                <input type="hidden" name="action" value="create_business">
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Business ID / Slug *</label>
                                    <input type="text" name="biz_id" class="w-full px-3 py-2 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#008272]" required placeholder="e.g. phone-lab">
                                    <span class="text-[10px] text-[#5c5c5c] block mt-0.5">Unique string code (lowercase, letters and hyphens only).</span>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Business Name *</label>
                                    <input type="text" name="biz_name" class="w-full px-3 py-2 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#008272]" required placeholder="e.g. Phone Lab Ennis">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Contact Number</label>
                                    <input type="text" name="biz_contact" class="w-full px-3 py-2 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#008272]" placeholder="(065) XXX XXXX">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Contact Email</label>
                                    <input type="email" name="biz_email" class="w-full px-3 py-2 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#008272]" placeholder="email@example.com">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Address</label>
                                    <textarea name="biz_address" class="w-full px-3 py-2 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#008272]" rows="2" placeholder="Store Address"></textarea>
                                </div>
                                <div class="pt-2">
                                    <button type="submit" class="w-full py-2.5 px-4 bg-[#008272] hover:bg-[#006b5e] text-white text-xs font-bold uppercase tracking-wider rounded-[4px] transition-colors shadow-xs">Create Business</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Right: Businesses List -->
                    <div class="lg:col-span-8">
                        <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-xs p-6 space-y-4">
                            <h2 class="text-sm font-bold text-[#242424]">Configured Businesses</h2>
                            <div class="overflow-x-auto border border-[#e0e0e0] rounded-[4px]">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead>
                                        <tr class="bg-[#fafafa] border-b border-[#e0e0e0] text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c]">
                                            <th class="px-4 py-2.5">Display Name</th>
                                            <th class="px-4 py-2.5">Immutable ID (Slug)</th>
                                            <th class="px-4 py-2.5">Hostinger Database Name</th>
                                            <th class="px-4 py-2.5">Details</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#e0e0e0]">
                                        <?php foreach ($businesses as $b): ?>
                                            <tr class="hover:bg-[#f9f9f9]">
                                                <td class="px-4 py-2.5 font-bold text-[#242424]"><?php echo htmlspecialchars($b['name']); ?></td>
                                                <td class="px-4 py-2.5"><code class="text-[#f25022] font-semibold text-xs"><?php echo htmlspecialchars($b['id']); ?></code></td>
                                                <td class="px-4 py-2.5"><code class="text-[#008272] font-semibold text-xs"><?php echo htmlspecialchars($b['db_name']); ?></code></td>
                                                <td class="px-4 py-2.5 text-[11px] leading-relaxed">
                                                    <strong>Tel:</strong> <?php echo htmlspecialchars($b['contact'] ?: 'N/A'); ?><br>
                                                    <strong>Email:</strong> <?php echo htmlspecialchars($b['email'] ?: 'N/A'); ?><br>
                                                    <strong>Addr:</strong> <?php echo htmlspecialchars($b['address'] ?: 'N/A'); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="p-4 bg-[#fafafa] border border-[#e0e0e0] rounded-[4px] space-y-1">
                                <h4 class="text-xs font-bold text-[#242424]">💡 Hostinger Database Mapping Notice</h4>
                                <p class="text-xs text-[#5c5c5c] leading-relaxed">When you add a new business here, write down the corresponding <strong>Hostinger Database Name</strong>. You will need to create this database in your Hostinger control panel using your standard credentials so that the tenant setup works correctly.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- History Pane -->
            <div x-show="activeTab === 'history'" class="space-y-6" style="display: none;">
                <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-xs p-6 space-y-4">
                    <h2 class="text-sm font-bold text-[#242424]">Central Shift & Duty History (Last 50 Entries)</h2>
                    <div class="overflow-x-auto border border-[#e0e0e0] rounded-[4px]">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-[#fafafa] border-b border-[#e0e0e0] text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c]">
                                    <th class="px-4 py-2.5">User</th>
                                    <th class="px-4 py-2.5">Logged-In Branch</th>
                                    <th class="px-4 py-2.5">Work Date</th>
                                    <th class="px-4 py-2.5">Login Timestamp</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#e0e0e0]">
                                <?php if (count($activities) === 0): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-[#5c5c5c] py-6">No duty history logs recorded yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($activities as $act): ?>
                                        <tr class="hover:bg-[#f9f9f9]">
                                            <td class="px-4 py-2.5 font-bold text-[#242424]"><?php echo htmlspecialchars($act['username']); ?></td>
                                            <td class="px-4 py-2.5 font-semibold text-[#008272]"><?php echo htmlspecialchars($act['business_name']); ?></td>
                                            <td class="px-4 py-2.5"><?php echo htmlspecialchars($act['work_date']); ?></td>
                                            <td class="px-4 py-2.5 text-[#5c5c5c]"><?php echo htmlspecialchars($act['login_time']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Printer Settings Pane -->
            <div x-show="activeTab === 'printer'" class="space-y-6" style="display: none;">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                    <div>
                        <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-xs p-6 space-y-4">
                            <h2 class="text-sm font-bold text-[#242424]">🖨️ Thermal Printer Configuration</h2>
                            <p class="text-xs text-[#5c5c5c] leading-relaxed">Modify the default font styling and print scaling size applied across all branch customer receipts and shift closing sheets.</p>
                            
                            <template x-if="successMsg">
                                <div class="bg-green-50 border border-[#7fba00]/40 text-[#7fba00] text-xs py-2 px-3 rounded-[4px] font-medium" x-text="successMsg"></div>
                            </template>
                            <template x-if="errorMsg">
                                <div class="bg-red-50 border border-[#f25022]/40 text-[#f25022] text-xs py-2 px-3 rounded-[4px] font-medium" x-text="errorMsg"></div>
                            </template>

                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Default Printer Font</label>
                                <select class="w-full px-3 py-2 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]" x-model="fontFamily">
                                    <option value="'Courier New', Courier, monospace">Courier Monospace (Default Thermal)</option>
                                    <option value="'Consolas', 'Monaco', 'Lucida Console', monospace">Consolas Monospace (Ultra Clear)</option>
                                    <option value="'Segoe UI', system-ui, sans-serif">Segoe UI System (High Contrast)</option>
                                    <option value="Arial, Helvetica, sans-serif">Arial Standard</option>
                                    <option value="'Outfit', 'Segoe UI', sans-serif">Outfit (Brand Font)</option>
                                    <option value="Georgia, serif">Georgia Serif</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Font Print Size</label>
                                <div class="flex items-center gap-3">
                                    <button type="button" @click="if(fontSize > 8) fontSize--" class="px-3 py-1 bg-white border border-[#e0e0e0] hover:bg-[#f3f3f3] text-[#242424] font-bold rounded-[4px] text-base">−</button>
                                    <span class="text-base font-bold text-[#242424] min-w-12 text-center"><span x-text="fontSize"></span> px</span>
                                    <button type="button" @click="if(fontSize < 24) fontSize++" class="px-3 py-1 bg-white border border-[#e0e0e0] hover:bg-[#f3f3f3] text-[#242424] font-bold rounded-[4px] text-base">+</button>
                                </div>
                            </div>

                            <div class="pt-2">
                                <button type="button" @click="saveSettings()" class="py-2.5 px-6 bg-[#00a4ef] hover:bg-[#0086c4] text-white text-xs font-bold uppercase tracking-wider rounded-[4px] transition-colors shadow-xs" :disabled="isSaving">
                                    <span x-show="!isSaving">💾 Save Settings</span>
                                    <span x-show="isSaving" class="animate-spin text-xs">🌀</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side: Live Thermal Receipt Preview -->
                    <div>
                        <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-xs p-6 space-y-4">
                            <h2 class="text-sm font-bold text-[#242424]">Print Preview</h2>
                            
                            <div class="border border-dashed border-[#c0c0c0] p-4 bg-white w-[80mm] max-w-full">
                                <div :style="'font-family: ' + fontFamily + '; font-size: ' + fontSize + 'px; line-height: 1.35; color: #000;'">
                                    <div class="text-center mb-2 pb-2 border-b border-dashed border-black">
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

    <!-- Standard Footer -->
    <?php require_once __DIR__ . '/footer.php'; ?>

</body>
</html>
