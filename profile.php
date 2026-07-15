<?php
// profile.php
session_start();
require_once __DIR__ . '/db.php';

// Enforce login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$successMsg = '';
$errorMsg = '';

// Fetch current user details
$stmt = $db->prepare("SELECT username, name, contact, email, address FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsername = trim($_POST['username'] ?? '');
    $newName = trim($_POST['name'] ?? '');
    $newContact = trim($_POST['contact'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    $newAddress = trim($_POST['address'] ?? '');

    if (empty($newUsername)) {
        $errorMsg = "Username cannot be empty.";
    } else {
        // Check if username is already taken by another user
        $checkStmt = $db->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?) AND id <> ?");
        $checkStmt->execute([$newUsername, $userId]);
        if ($checkStmt->fetch()) {
            $errorMsg = "Username is already taken.";
        } else {
            // Update user details
            $updateStmt = $db->prepare("UPDATE users SET username = ?, name = ?, contact = ?, email = ?, address = ? WHERE id = ?");
            if ($updateStmt->execute([$newUsername, $newName, $newContact, $newEmail, $newAddress, $userId])) {
                $_SESSION['username'] = $newUsername;
                $successMsg = "Profile updated successfully!";
                
                // Refresh local user data
                $user['username'] = $newUsername;
                $user['name'] = $newName;
                $user['contact'] = $newContact;
                $user['email'] = $newEmail;
                $user['address'] = $newAddress;
            } else {
                $errorMsg = "Failed to update profile. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile - TechInbox</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --bg-color: #f3f3f3; /* Microsoft Fluent Light Gray */
            --card-bg: #ffffff;
            --card-border: #e0e0e0;
            --text-primary: #242424; /* Dark Charcoal */
            --text-secondary: #5c5c5c;
            --brand-blue: #0078d4; /* Microsoft Blue */
            --brand-blue-hover: #106ebe;
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

        /* Portal Nav Header */
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

        /* Container & Grid Layout */
        main {
            flex: 1;
            width: 100%;
            max-width: 550px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .profile-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 6px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .card-header {
            background-color: #ffffff;
            color: var(--text-primary);
            padding: 18px 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid var(--card-border);
            border-left: 4px solid var(--brand-blue);
        }

        .card-header h1 {
            font-size: 18px;
            font-weight: 600;
            letter-spacing: -0.3px;
        }

        .card-header-icon {
            font-size: 20px;
            color: var(--brand-blue);
        }

        .card-body {
            padding: 28px 24px;
        }

        /* Form Controls */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 20px;
        }

        label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
        }

        input[type="text"], input[type="email"], select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d0d0d0;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            color: var(--text-primary);
            outline: none;
            transition: border-color 0.15s ease;
            background-color: #ffffff;
        }

        input:focus, select:focus {
            border-color: var(--brand-blue);
            box-shadow: 0 0 0 3px rgba(0, 120, 212, 0.15);
        }

        /* Action Buttons */
        .btn-submit {
            background-color: var(--brand-blue);
            color: #ffffff;
            width: 100%;
            border: none;
            border-radius: 6px;
            padding: 14px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.15s ease;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background-color: var(--brand-blue-hover);
        }

        /* Banner alerts */
        .alert {
            border-radius: 6px;
            padding: 12px 16px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #dff6dd;
            border: 1px solid #c3e6cb;
            color: #107c41; /* Fluent Excel Green */
        }

        .alert-danger {
            background-color: #fde7e9;
            border: 1px solid #e0b4b4;
            color: #a80000;
        }

        .welcome-msg {
            font-size: 13px;
            color: var(--text-secondary);
        }

        .username {
            color: var(--text-primary);
            font-weight: 600;
        }

        .btn-auth {
            padding: 6px 14px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border: 1px solid #d0d0d0;
        }

        .btn-logout {
            background: #ffffff;
            color: #a80000;
        }

        .btn-logout:hover {
            background: #fde7e9;
            border-color: #a80000;
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
            main {
                margin: 20px auto;
            }
            .card-body {
                padding: 20px 16px;
            }
            .logo-sub {
                display: none;
            }
            header {
                padding: 12px 16px;
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
        <div class="user-section" style="display: flex; align-items: center; gap: 16px;">
            <a href="index.php" class="btn-portal" style="margin-right: 8px;">&larr; Back to Portal</a>
            <div class="welcome-msg">Signed in as <span class="username"><?php echo htmlspecialchars($user['username']); ?></span></div>
            <a href="profile.php" class="settings-link" title="Settings" style="display: inline-flex; align-items: center; color: var(--text-secondary); transition: color 0.15s ease-in-out;" onmouseover="this.style.color='var(--brand-blue)';" onmouseout="this.style.color='var(--text-secondary)';">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            </a>
            <a href="logout.php" class="btn-auth btn-logout">
                Sign Out
            </a>
        </div>
    </header>

    <main>
        <div class="profile-card">
            <div class="card-header">
                <span class="card-header-icon">👤</span>
                <h1>Edit User Profile</h1>
            </div>

            <div class="card-body">
                <?php if ($successMsg): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div>
                <?php endif; ?>

                <?php if ($errorMsg): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($errorMsg); ?></div>
                <?php endif; ?>

                <form method="POST" action="profile.php">
                    <div class="form-group">
                        <label for="name">Business Name</label>
                        <select id="name" name="name">
                            <option value="" disabled <?php echo empty($user['name']) ? 'selected' : ''; ?>>Select a business name...</option>
                            <option value="Phone Lab" <?php echo ($user['name'] ?? '') === 'Phone Lab' ? 'selected' : ''; ?>>Phone Lab</option>
                            <option value="FIXD GORT" <?php echo ($user['name'] ?? '') === 'FIXD GORT' ? 'selected' : ''; ?>>FIXD GORT</option>
                            <option value="Gadget Repair & Vape shop" <?php echo ($user['name'] ?? '') === 'Gadget Repair & Vape shop' ? 'selected' : ''; ?>>Gadget Repair & Vape shop</option>
                            <option value="iPear Ennis" <?php echo ($user['name'] ?? '') === 'iPear Ennis' ? 'selected' : ''; ?>>iPear Ennis</option>
                            <option value="iPear in Tesco" <?php echo ($user['name'] ?? '') === 'iPear in Tesco' ? 'selected' : ''; ?>>iPear in Tesco</option>
                            <option value="Phone Shop Town Loughrea" <?php echo ($user['name'] ?? '') === 'Phone Shop Town Loughrea' ? 'selected' : ''; ?>>Phone Shop Town Loughrea</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="username">Username (User) *</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" placeholder="Enter login username..." required>
                    </div>

                    <div class="form-group">
                        <label for="contact">Contact Number</label>
                        <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars($user['contact'] ?? ''); ?>" placeholder="Enter phone/contact number...">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="Enter email address...">
                    </div>

                    <div class="form-group">
                        <label for="address">Business Address</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" placeholder="Enter business address...">
                    </div>

                    <button type="submit" class="btn-submit">
                        Update Profile
                    </button>
                </form>
            </div>
        </div>
    </main>

    <footer>
        <div class="footer-text">
            These system apps and Utility are Developer: <span class="footer-dev">Tanveer</span> | Support: <a href="mailto:support@techinbox.ie" class="footer-email">support@techinbox.ie</a>
        </div>
    </footer>

</body>
</html>
