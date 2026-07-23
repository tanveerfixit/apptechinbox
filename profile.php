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
$stmt = $masterDb->prepare("SELECT username, name, contact, email, address FROM users WHERE id = ?");
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
        $checkStmt = $masterDb->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?) AND id <> ?");
        $checkStmt->execute([$newUsername, $userId]);
        if ($checkStmt->fetch()) {
            $errorMsg = "Username is already taken.";
        } else {
            // Update user details
            $updateStmt = $masterDb->prepare("UPDATE users SET username = ?, name = ?, contact = ?, email = ?, address = ? WHERE id = ?");
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
    <link rel="icon" type="image/png" href="/public/icons/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/svg+xml" href="/public/icons/icon.svg">
    <link rel="shortcut icon" href="/public/icons/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/public/icons/apple-touch-icon.png">
    <link rel="manifest" href="/public/icons/site.webmanifest">
</head>
<body class="flex flex-col min-h-screen bg-[#f3f3f3] text-[#242424] font-sans antialiased">

    <!-- Header Navigation -->
    <?php require_once __DIR__ . '/header.php'; ?>

    <main class="w-full max-w-md mx-auto px-4 sm:px-6 py-8 flex-1">
        <?php require __DIR__ . '/nav_buttons.php'; ?>

        <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-sm overflow-hidden">
            <div class="p-5 border-b border-[#e0e0e0] border-l-4 border-l-[#00a4ef] bg-white flex items-center gap-2">
                <span class="text-lg">👤</span>
                <h1 class="text-lg font-bold text-[#242424] tracking-tight">Edit User Profile</h1>
            </div>

            <div class="p-6 bg-white space-y-4">
                <?php if ($successMsg): ?>
                    <div class="bg-green-50 border border-[#7fba00]/40 text-[#7fba00] text-xs py-2 px-3 rounded-[4px] font-medium text-center">
                        <?php echo htmlspecialchars($successMsg); ?>
                    </div>
                <?php endif; ?>

                <?php if ($errorMsg): ?>
                    <div class="bg-red-50 border border-[#f25022]/40 text-[#f25022] text-xs py-2 px-3 rounded-[4px] font-medium text-center">
                        <?php echo htmlspecialchars($errorMsg); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="profile.php" class="space-y-4">
                    <div>
                        <label for="name" class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Business Name</label>
                        <select id="name" name="name" class="w-full px-3 py-2 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                            <option value="" disabled <?php echo empty($user['name']) ? 'selected' : ''; ?>>Select a business name...</option>
                            <option value="Phone Lab" <?php echo ($user['name'] ?? '') === 'Phone Lab' ? 'selected' : ''; ?>>Phone Lab</option>
                            <option value="FIXD GORT" <?php echo ($user['name'] ?? '') === 'FIXD GORT' ? 'selected' : ''; ?>>FIXD GORT</option>
                            <option value="Gadget Repair & Vape shop" <?php echo ($user['name'] ?? '') === 'Gadget Repair & Vape shop' ? 'selected' : ''; ?>>Gadget Repair & Vape shop</option>
                            <option value="iPear Ennis" <?php echo ($user['name'] ?? '') === 'iPear Ennis' ? 'selected' : ''; ?>>iPear Ennis</option>
                            <option value="iPear in Tesco" <?php echo ($user['name'] ?? '') === 'iPear in Tesco' ? 'selected' : ''; ?>>iPear in Tesco</option>
                            <option value="Phone Shop Town Loughrea" <?php echo ($user['name'] ?? '') === 'Phone Shop Town Loughrea' ? 'selected' : ''; ?>>Phone Shop Town Loughrea</option>
                        </select>
                    </div>

                    <div>
                        <label for="username" class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Username (User) *</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" placeholder="Enter login username..." required class="w-full px-3 py-2 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                    </div>

                    <div>
                        <label for="contact" class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Contact Number</label>
                        <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars($user['contact'] ?? ''); ?>" placeholder="Enter phone/contact number..." class="w-full px-3 py-2 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                    </div>

                    <div>
                        <label for="email" class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="Enter email address..." class="w-full px-3 py-2 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                    </div>

                    <div>
                        <label for="address" class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Business Address</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" placeholder="Enter business address..." class="w-full px-3 py-2 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#00a4ef]">
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full py-2.5 px-4 bg-[#00a4ef] hover:bg-[#0086c4] text-white text-xs font-bold uppercase tracking-wider rounded-[4px] transition-colors shadow-xs">
                            Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Standard Footer -->
    <?php require_once __DIR__ . '/footer.php'; ?>

</body>
</html>
