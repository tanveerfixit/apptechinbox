<?php
// login.php
session_start();
require_once __DIR__ . '/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $stmt = $masterDb->prepare("SELECT id, username, password, is_admin, assigned_business_id FROM users WHERE LOWER(username) = LOWER(?)");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = (int)$user['is_admin'];
            $_SESSION['assigned_business_id'] = $user['assigned_business_id'];
            
            // Update the user's business details dynamically based on selection
            $selectedBusinessId = trim($_POST['business'] ?? '');
            
            // Enforce assigned business for non-admin users to guarantee database isolation
            if (empty($user['is_admin']) || (int)$user['is_admin'] !== 1) {
                $selectedBusinessId = $user['assigned_business_id'];
            }

            if ($selectedBusinessId) {
                $bizStmt = $masterDb->prepare("SELECT * FROM businesses WHERE id = ?");
                $bizStmt->execute([$selectedBusinessId]);
                $bizDetails = $bizStmt->fetch();
                
                if ($bizDetails) {
                    $_SESSION['tenant_db_name'] = $bizDetails['db_name'] ?? null;
                    $_SESSION['tenant_db_user'] = $bizDetails['db_user'] ?? null;
                    $_SESSION['tenant_db_password'] = $bizDetails['db_password'] ?? null;
                    $_SESSION['business_name'] = $bizDetails['name'];
                    $_SESSION['business_id'] = $bizDetails['id'];
                }

                // CENTRALIZED DUTY LOGGING
                $logStmt = $masterDb->prepare("
                    INSERT INTO user_duty_history (user_id, business_id, work_date)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE login_time = CURRENT_TIMESTAMP
                ");
                $logStmt->execute([$user['id'], $selectedBusinessId, date('Y-m-d')]);
            }
            
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}

// Fetch all users to populate the username selection dropdown
$stmtUsers = $masterDb->query("SELECT username FROM users ORDER BY username");
$allUsers = $stmtUsers->fetchAll();

// Fetch all businesses to populate the business selection dropdown
$stmtBiz = $masterDb->query("SELECT id, name FROM businesses ORDER BY name");
$allBusinesses = $stmtBiz->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - TechInbox</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/public/icons/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/svg+xml" href="/public/icons/icon.svg">
    <link rel="shortcut icon" href="/public/icons/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/public/icons/apple-touch-icon.png">
    <link rel="manifest" href="/public/icons/site.webmanifest">
</head>
<body class="flex flex-col min-h-screen bg-[#f3f3f3] text-[#242424] font-sans antialiased">

    <!-- Header Navigation -->
    <?php require_once __DIR__ . '/header.php'; ?>

    <!-- Main Container Login Card -->
    <main class="flex-1 flex flex-col items-center justify-center p-4 sm:p-6 my-auto">
        <div class="w-full max-w-md">
            <?php require __DIR__ . '/nav_buttons.php'; ?>
        </div>
        <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-md w-full max-w-md p-6 sm:p-8">
            <h2 class="text-xl font-bold text-[#242424] text-center tracking-tight mb-1">Sign in to Business Portal</h2>
            <p class="text-xs text-[#5c5c5c] text-center mb-6">Sign in to access your inventory builder and workspace utilities</p>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-[#f25022]/30 text-[#f25022] text-xs py-2.5 px-3 rounded-[4px] text-center mb-5 font-medium">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="space-y-4">
                <div>
                    <label for="business" class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Business Name</label>
                    <select id="business" name="business" required class="w-full px-3 py-2 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                        <option value="" disabled selected>Select business...</option>
                        <?php foreach ($allBusinesses as $biz): ?>
                            <option value="<?php echo htmlspecialchars($biz['id']); ?>"><?php echo htmlspecialchars($biz['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="username_select" class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">User Name</label>
                    <select id="username_select" name="username" required class="w-full px-3 py-2 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                        <option value="" disabled selected>Select user...</option>
                        <?php foreach ($allUsers as $u): ?>
                            <option value="<?php echo htmlspecialchars($u['username']); ?>"><?php echo htmlspecialchars($u['username']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="password" class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter password..." required class="w-full px-3 py-2 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                </div>
                <div class="pt-2">
                    <button type="submit" class="w-full py-2.5 px-4 bg-[#00a4ef] hover:bg-[#0086c4] text-white text-xs font-bold uppercase tracking-wider rounded-[4px] transition-colors shadow-xs">
                        Sign In
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- Standard Footer -->
    <?php require_once __DIR__ . '/footer.php'; ?>

</body>
</html>
