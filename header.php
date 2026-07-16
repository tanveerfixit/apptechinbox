<?php
// header.php
$username = $_SESSION['username'] ?? '';
$isLoggedIn = isset($_SESSION['user_id']);
$currentScript = basename($_SERVER['PHP_SELF']);
$showBackBtn = ($currentScript !== 'index.php');
?>
<header class="navbar navbar-expand navbar-light bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center shadow-sm">
    <a href="index.php" class="d-flex align-items-center gap-2 text-decoration-none">
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2px; width: 18px; height: 18px;">
            <div style="width: 8px; height: 8px; background-color: #f25022;"></div>
            <div style="width: 8px; height: 8px; background-color: #7fba00;"></div>
            <div style="width: 8px; height: 8px; background-color: #00a4ef;"></div>
            <div style="width: 8px; height: 8px; background-color: #ffb900;"></div>
        </div>
        <span class="fs-5 fw-bold text-dark mb-0">TechInbox</span>
        <span class="text-muted border-start ps-2 mb-0 d-none d-sm-inline" style="font-size: 14px;">Portal</span>
    </a>
    
    <div class="d-flex align-items-center gap-3">
        <?php if ($showBackBtn): ?>
            <a href="index.php" class="text-decoration-none fw-semibold text-primary" style="font-size: 14px; color: var(--brand-blue) !important;">&larr; Back to Portal</a>
        <?php endif; ?>
        
        <?php if ($isLoggedIn): ?>
            <span class="small text-muted d-none d-md-inline">Signed in as <a href="profile.php" class="text-dark fw-semibold text-decoration-underline"><?php echo htmlspecialchars($username); ?></a></span>
            <a href="duty_history.php" class="text-decoration-none fw-semibold text-primary ms-2" style="font-size: 14px; color: #0078d4 !important;">Duty History</a>
            <?php if (!empty($_SESSION['is_admin'])): ?>
                <a href="admin.php" class="text-decoration-none fw-semibold text-danger ms-2" style="font-size: 14px; color: #d83b01 !important;">Admin Portal</a>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1 border-0">
                Sign Out
            </a>
        <?php else: ?>
            <span class="small text-muted d-none d-sm-inline">Not signed in</span>
            <a href="login.php" class="btn btn-sm btn-primary px-3 rounded-1" onclick="event.preventDefault(); document.getElementById('loginModal').style.setProperty('display', 'flex', 'important');">
                Sign In
            </a>
        <?php endif; ?>
    </div>
</header>
