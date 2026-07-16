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
        $stmt = $masterDb->prepare("SELECT id, username, password FROM users WHERE LOWER(username) = LOWER(?)");
        $stmt->execute([$usernameInput]);
        $user = $stmt->fetch();

        if ($user && password_verify($passwordInput, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // Update the user's business details dynamically based on selection
            $selectedBusiness = trim($_POST['business'] ?? '');
            if ($selectedBusiness) {
                $bizStmt = $masterDb->prepare("SELECT * FROM businesses WHERE name = ?");
                $bizStmt->execute([$selectedBusiness]);
                $bizDetails = $bizStmt->fetch();
                
                if ($bizDetails) {
                    $_SESSION['tenant_db_name'] = $bizDetails['db_name'] ?? null;
                    $_SESSION['business_name'] = $bizDetails['name'];
                }
                
                $updateStmt = $masterDb->prepare("UPDATE users SET name = ?, contact = ?, email = ?, address = ? WHERE id = ?");
                $updateStmt->execute([
                    $selectedBusiness, 
                    $bizDetails['contact'] ?? NULL, 
                    $bizDetails['email'] ?? NULL, 
                    $bizDetails['address'] ?? NULL, 
                    $user['id']
                ]);

                // CENTRALIZED DUTY LOGGING
                $logStmt = $masterDb->prepare("
                    INSERT INTO user_duty_history (user_id, business_name, work_date)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE login_time = CURRENT_TIMESTAMP
                ");
                $logStmt->execute([$user['id'], $selectedBusiness, date('Y-m-d')]);
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
$stmtBiz = $masterDb->query("SELECT name FROM businesses ORDER BY name");
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
        'name' => 'Screen Protector Finder',
        'url' => '/screen-protector-finder',
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
    <title>TechInbox - Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <!-- Header Navigation -->
    <?php require_once __DIR__ . '/header.php'; ?>

    <!-- Main Container Dashboard -->
    <main class="container py-5 flex-grow-1">
        <div class="text-center mx-auto mb-5" style="max-width: 600px;">
            <h1 class="h2 fw-semibold text-dark mb-2">Applications Dashboard</h1>
            <p class="small text-muted">Select an application below to get started with your TechInbox workspace utilities.</p>
        </div>

        <div class="row g-4">
            <?php foreach ($apps as $app): ?>
                <div class="col-12 col-sm-6 col-md-4 d-flex">
                    <a href="<?php echo htmlspecialchars($app['url']); ?>" class="card w-100 border-1 shadow-sm d-flex flex-column justify-content-between p-4 text-decoration-none text-dark" style="transition: transform 0.15s ease-in-out, box-shadow 0.15s ease-in-out; border-radius: 6px;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div style="width: 40px; height: 40px; border-left: 3.5px solid <?php echo $app['color']; ?>; background-color: #f8f9fa; font-size: 20px; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                                    <?php echo $app['icon']; ?>
                                </div>
                                <span class="badge bg-secondary-subtle text-secondary border-0 px-2 py-1 small text-uppercase fw-semibold" style="font-size: 10px; letter-spacing: 0.5px;"><?php echo htmlspecialchars($app['badge']); ?></span>
                            </div>
                            <div>
                                <h3 class="h6 fw-bold text-dark mb-1"><?php echo htmlspecialchars($app['name']); ?></h3>
                                <p class="text-muted mb-4" style="font-size: 12.5px; line-height: 1.4;"><?php echo htmlspecialchars($app['desc']); ?></p>
                            </div>
                        </div>
                        <div class="text-primary fw-semibold small d-flex align-items-center gap-1 mt-auto">
                            <span>Open Application</span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
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
    <div id="loginModal" class="modal fade show align-items-center justify-content-center" style="display: <?php echo $modalDisplay; ?>; background: rgba(9, 13, 22, 0.65); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 2050;">
        <div class="card shadow-lg p-4 border-1" style="width: 100%; max-width: 380px; border-radius: 6px;">
            <h2 class="h5 fw-semibold text-dark text-center mb-1">Sign in to Business Portal</h2>
            <p class="small text-muted text-center mb-4">Sign in to access your inventory builder</p>

            <?php if ($loginError): ?>
                <div class="alert alert-danger py-2 px-3 small text-center mb-3" style="font-size: 12.5px;">
                    <?php echo htmlspecialchars($loginError); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php">
                <input type="hidden" name="login_action" value="1">
                <div class="mb-3">
                    <label class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Business Name</label>
                    <select class="form-select py-2" name="business" required>
                        <option value="" disabled selected>Select business...</option>
                        <?php foreach ($allBusinesses as $biz): ?>
                            <option value="<?php echo htmlspecialchars($biz['name']); ?>"><?php echo htmlspecialchars($biz['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px;">User Name</label>
                    <select class="form-select py-2" name="username" required>
                        <option value="" disabled selected>Select user...</option>
                        <?php foreach ($allUsers as $u): ?>
                            <option value="<?php echo htmlspecialchars($u['username']); ?>"><?php echo htmlspecialchars($u['username']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Password</label>
                    <input type="password" class="form-control py-2" name="password" placeholder="Enter password..." required>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 text-uppercase fw-bold mb-2" style="font-size: 13px; letter-spacing: 0.5px;">Sign In</button>
                <button type="button" class="btn btn-outline-secondary w-100 py-2" style="font-size: 13px;" onclick="skipLogin()">Skip for Now</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Standard Footer -->
    <?php require_once __DIR__ . '/footer.php'; ?>

    <!-- Bootstrap 5 JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        function skipLogin() {
            window.location.href = 'index.php?skip=1';
        }
    </script>
</body>
</html>
