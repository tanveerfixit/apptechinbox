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
        $stmt = $db->prepare("SELECT id, username, password FROM users WHERE LOWER(username) = LOWER(?)");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // Update the user's business details dynamically based on selection
            $selectedBusiness = trim($_POST['business'] ?? '');
            if ($selectedBusiness) {
                $bizStmt = $db->prepare("SELECT * FROM businesses WHERE name = ?");
                $bizStmt->execute([$selectedBusiness]);
                $bizDetails = $bizStmt->fetch();
                
                $updateStmt = $db->prepare("UPDATE users SET name = ?, contact = ?, email = ?, address = ? WHERE id = ?");
                $updateStmt->execute([
                    $selectedBusiness, 
                    $bizDetails['contact'] ?? NULL, 
                    $bizDetails['email'] ?? NULL, 
                    $bizDetails['address'] ?? NULL, 
                    $user['id']
                ]);
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
$stmtUsers = $db->query("SELECT username FROM users ORDER BY username");
$allUsers = $stmtUsers->fetchAll();

// Fetch all businesses to populate the business selection dropdown
$stmtBiz = $db->query("SELECT name FROM businesses ORDER BY name");
$allBusinesses = $stmtBiz->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - TechInbox</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --bg-color: #f3f3f3; /* Microsoft Fluent Light Gray */
            --card-bg: #ffffff;
            --card-border: #e0e0e0;
            --text-primary: #242424;
            --text-secondary: #5c5c5c;
            --brand-blue: #0078d4;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-primary);
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background: #ffffff;
            border-bottom: 1px solid var(--card-border);
            padding: 14px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .ms-logo {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2px;
            width: 18px;
            height: 18px;
        }

        .ms-square {
            width: 8px;
            height: 8px;
        }

        .logo-text {
            font-size: 18px;
            font-weight: 600;
            color: #242424;
        }

        .logo-sub {
            font-weight: 400;
            color: var(--text-secondary);
            font-size: 14px;
            border-left: 1px solid var(--card-border);
            padding-left: 10px;
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .welcome-msg {
            font-size: 13px;
            color: var(--text-secondary);
        }

        .btn-portal {
            color: var(--brand-blue);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-portal:hover {
            text-decoration: underline;
        }

        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
        }

        .login-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 6px;
            padding: 32px 24px;
            width: 100%;
            max-width: 380px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .login-card h2 {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0 0 8px 0;
            text-align: center;
            letter-spacing: -0.3px;
        }

        .login-card p {
            font-size: 13px;
            color: var(--text-secondary);
            margin: 0 0 24px 0;
            text-align: center;
        }

        .input-group {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .input-group label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-group input, .input-group select {
            background-color: #ffffff;
            border: 1px solid #d0d0d0;
            border-radius: 6px;
            color: var(--text-primary);
            padding: 12px;
            font-size: 14px;
            width: 100%;
            outline: none;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }

        .input-group input:focus, .input-group select:focus {
            border-color: var(--brand-blue);
            box-shadow: 0 0 0 3px rgba(0, 120, 212, 0.15);
        }

        .btn-submit {
            background-color: var(--brand-blue);
            color: #ffffff;
            font-weight: 600;
            font-size: 14px;
            padding: 12px;
            border: none;
            border-radius: 6px;
            width: 100%;
            cursor: pointer;
            transition: background-color 0.15s ease;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-submit:hover {
            background-color: #106ebe;
        }

        .error-banner {
            background-color: #fde7e9;
            border: 1px solid #e0b4b4;
            border-radius: 6px;
            color: #a80000;
            padding: 10px 12px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: center;
        }

        footer {
            background-color: #ffffff;
            border-top: 1px solid var(--card-border);
            padding: 20px;
            text-align: center;
            margin-top: auto;
        }

        .footer-text {
            font-size: 12px;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        .footer-dev {
            color: var(--text-primary);
            font-weight: 600;
        }

        .footer-email {
            color: var(--brand-blue);
            text-decoration: none;
            font-weight: 600;
        }

        .footer-email:hover {
            text-decoration: underline;
        }

        @media (max-width: 640px) {
            header {
                padding: 12px 16px;
            }
            .logo-sub {
                display: none;
            }
        }
    </style>
</head>
<body>

    <header>
        <a href="index.php" class="logo-section">
            <div class="ms-logo">
                <div class="ms-square" style="background-color: #f25022;"></div>
                <div class="ms-square" style="background-color: #7fba00;"></div>
                <div class="ms-square" style="background-color: #00a4ef;"></div>
                <div class="ms-square" style="background-color: #ffb900;"></div>
            </div>
            <div class="logo-text">TechInbox</div>
            <div class="logo-sub">Portal</div>
        </a>
        <div class="user-section">
            <a href="index.php" class="btn-portal">&larr; Back to Portal</a>
        </div>
    </header>

    <main>
        <div class="login-card">
            <h2>Sign in to Business Portal</h2>
            <p>Sign in to access your inventory builder</p>

            <?php if ($error): ?>
                <div class="error-banner">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="input-group">
                    <label for="business">Business Name</label>
                    <select id="business" name="business" required>
                        <option value="" disabled selected>Select business...</option>
                        <?php foreach ($allBusinesses as $biz): ?>
                            <option value="<?php echo htmlspecialchars($biz['name']); ?>"><?php echo htmlspecialchars($biz['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label for="username_select">User Name</label>
                    <select id="username_select" name="username" required>
                        <option value="" disabled selected>Select user...</option>
                        <?php foreach ($allUsers as $u): ?>
                            <option value="<?php echo htmlspecialchars($u['username']); ?>"><?php echo htmlspecialchars($u['username']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter password..." required>
                </div>
                <button type="submit" class="btn-submit">Sign In</button>
            </form>
        </div>
    </main>

    <footer>
        <div class="footer-text">
            These system apps and Utility are Developer: <span class="footer-dev">Tanveer</span> | Support: <a href="mailto:support@techinbox.ie" class="footer-email">support@techinbox.ie</a>
        </div>
    </footer>

</body>
</html>
