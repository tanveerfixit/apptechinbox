<?php
// index.php
session_start();
require_once __DIR__ . '/db.php';

// Handle Skip Login request
if (isset($_GET['skip'])) {
    $_SESSION['skip_login'] = true;
    header("Location: index.php");
    exit();
}

$isLoggedIn = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? '';
$loginError = '';

// Handle Login Form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_action'])) {
    $usernameInput = trim($_POST['username'] ?? '');
    $passwordInput = trim($_POST['password'] ?? '');

    if ($usernameInput && $passwordInput) {
        $stmt = $masterDb->prepare("SELECT id, username, password, is_admin, assigned_business_id FROM users WHERE LOWER(username) = LOWER(?)");
        $stmt->execute([$usernameInput]);
        $user = $stmt->fetch();

        if ($user && password_verify($passwordInput, $user['password'])) {
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
            
            // Clear skip login session since user successfully signed in
            unset($_SESSION['skip_login']);
            
            header("Location: index.php");
            exit();
        } else {
            $loginError = "Invalid username or password.";
        }
    } else {
        $loginError = "Please fill in all fields.";
    }
}

// Fetch all users to populate login selectors
$stmtUsers = $masterDb->query("SELECT username FROM users ORDER BY username");
$allUsers = $stmtUsers->fetchAll();

// Fetch all businesses to populate login selectors
$stmtBiz = $masterDb->query("SELECT id, name FROM businesses ORDER BY name");
$allBusinesses = $stmtBiz->fetchAll();

// Define the apps with Microsoft-inspired brand colors and simple styling
$apps = [
    [
        'name' => 'Daily Closer',
        'url' => 'daily-closer.php',
        'desc' => 'Track daily end-of-day closings, registers, safe drops, and financial reports.',
        'icon' => '📊',
        'color' => '#7fba00', // Microsoft Green
        'badge' => 'Utility'
    ],
    [
        'name' => 'Phone Screen Protector',
        'url' => 'screen-protector-finder.php',
        'desc' => 'Search and locate screen protector inventory and device compatibility matching.',
        'icon' => '📱',
        'color' => '#ffb900', // Microsoft Yellow
        'badge' => 'Search'
    ],
    [
        'name' => 'Device Booking',
        'url' => 'booking.php',
        'desc' => 'Book repair devices, record faults, quote repairs, accept deposits, and print tickets.',
        'icon' => '📋',
        'color' => '#008272', // Teal
        'badge' => 'Repairs'
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Businesses Apps By Techinbox</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/public/icons/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/svg+xml" href="/public/icons/icon.svg">
    <link rel="shortcut icon" href="/public/icons/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/public/icons/apple-touch-icon.png">
    <link rel="manifest" href="/public/icons/site.webmanifest">
</head>
<body class="flex flex-col min-h-screen bg-[#f3f3f3] text-[#242424] font-sans antialiased text-base">

    <!-- Header Navigation -->
    <?php require_once __DIR__ . '/header.php'; ?>

    <!-- Main Container Dashboard -->
    <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 flex-1">
        <?php require __DIR__ . '/nav_buttons.php'; ?>

        <div class="text-center max-w-2xl mx-auto mb-10">
            <h1 class="text-3xl sm:text-4xl font-extrabold text-[#242424] tracking-tight mb-3">Applications Dashboard</h1>
            <p class="text-base sm:text-lg text-[#5c5c5c] leading-relaxed">Select an application below to get started with your TechInbox workspace utilities.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($apps as $app): ?>
                <div class="flex">
                    <a href="<?php echo htmlspecialchars($app['url']); ?>" class="w-full bg-white border border-[#e0e0e0] rounded-[6px] p-7 flex flex-col justify-between hover:border-[#d1d1d1] hover:shadow-lg hover:-translate-y-0.5 transition-all duration-150 group text-decoration-none">
                        <div>
                            <div class="flex justify-between items-center mb-5">
                                <div class="w-12 h-12 flex items-center justify-center text-2xl bg-[#f3f3f3] rounded-[6px]">
                                    <?php echo $app['icon']; ?>
                                </div>
                                <span class="text-xs font-bold uppercase tracking-wider px-2.5 py-1 bg-[#f3f3f3] text-[#5c5c5c] rounded-[4px]">
                                    <?php echo htmlspecialchars($app['badge']); ?>
                                </span>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-[#242424] group-hover:text-[#00a4ef] transition-colors mb-2">
                                    <?php echo htmlspecialchars($app['name']); ?>
                                </h3>
                                <p class="text-sm text-[#5c5c5c] leading-relaxed mb-6">
                                    <?php echo htmlspecialchars($app['desc']); ?>
                                </p>
                            </div>
                        </div>
                        <div class="text-sm font-bold text-[#00a4ef] flex items-center gap-2 mt-auto group-hover:translate-x-1 transition-transform">
                            <span>Open Application</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"></path></svg>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Login Modal Prompt -->
    <?php if (!$isLoggedIn): 
        $modalDisplay = (empty($_SESSION['skip_login']) && !$isLoggedIn) ? 'flex' : 'none';
    ?>
    <div id="loginModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 transition-opacity duration-200" style="display: <?php echo $modalDisplay; ?>;">
        <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-2xl w-full max-w-lg p-8 relative">
            <h2 class="text-2xl font-bold text-[#242424] text-center mb-1">Sign in to Business Portal</h2>
            <p class="text-sm text-[#5c5c5c] text-center mb-6">Sign in to access your inventory builder</p>

            <?php if ($loginError): ?>
                <div class="bg-red-50 border border-[#f25022]/30 text-[#f25022] text-sm py-2.5 px-4 rounded-[4px] text-center mb-4 font-medium">
                    <?php echo htmlspecialchars($loginError); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php" class="space-y-5">
                <input type="hidden" name="login_action" value="1">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#5c5c5c] mb-1.5">Business Name</label>
                    <select name="business" required class="w-full px-3.5 py-2.5 text-sm border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                        <option value="" disabled selected>Select business...</option>
                        <?php foreach ($allBusinesses as $biz): ?>
                            <option value="<?php echo htmlspecialchars($biz['id']); ?>"><?php echo htmlspecialchars($biz['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#5c5c5c] mb-1.5">User Name</label>
                    <select name="username" required class="w-full px-3.5 py-2.5 text-sm border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                        <option value="" disabled selected>Select user...</option>
                        <?php foreach ($allUsers as $u): ?>
                            <option value="<?php echo htmlspecialchars($u['username']); ?>"><?php echo htmlspecialchars($u['username']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#5c5c5c] mb-1.5">Password</label>
                    <input type="password" name="password" placeholder="Enter password..." required class="w-full px-3.5 py-2.5 text-sm border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                </div>
                <div class="pt-2 space-y-3">
                    <button type="submit" class="w-full py-3 px-4 bg-[#00a4ef] hover:bg-[#0086c4] text-white text-sm font-bold uppercase tracking-wider rounded-[4px] transition-colors shadow-xs">
                        Sign In
                    </button>
                    <button type="button" onclick="skipLogin()" class="w-full py-3 px-4 bg-[#f3f3f3] hover:bg-[#e8e8e8] text-[#242424] text-sm font-semibold rounded-[4px] transition-colors">
                        Skip for Now
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Standard Footer -->
    <?php require_once __DIR__ . '/footer.php'; ?>

    <script>
        function skipLogin() {
            window.location.href = 'index.php?skip=1';
        }
    </script>
</body>
</html>
