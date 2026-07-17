<?php
// header.php
$username = $_SESSION['username'] ?? '';
$isLoggedIn = isset($_SESSION['user_id']);
$currentScript = basename($_SERVER['PHP_SELF']);
$showBackBtn = ($currentScript !== 'index.php');
?>
<header class="navbar navbar-light bg-light border-bottom py-2 px-3 px-md-4 d-flex justify-content-between align-items-center shadow-sm" style="background-color: #f3f3f3 !important; border-color: #e0e0e0 !important; min-height: 56px;">
    <div class="d-flex align-items-center gap-2">
        <!-- Hamburger Button (Mobile only) -->
        <button class="btn btn-link p-0 text-dark d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNavDrawer" aria-controls="mobileNavDrawer" style="display: inline-flex; align-items: center;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="4" y1="12" x2="20" y2="12"></line>
                <line x1="4" y1="6" x2="20" y2="6"></line>
                <line x1="4" y1="18" x2="20" y2="18"></line>
            </svg>
        </button>
        
        <a href="index.php" class="d-flex align-items-center gap-2 text-decoration-none ms-1 ms-md-0">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2px; width: 18px; height: 18px;">
                <div style="width: 8px; height: 8px; background-color: #f25022;"></div>
                <div style="width: 8px; height: 8px; background-color: #7fba00;"></div>
                <div style="width: 8px; height: 8px; background-color: #00a4ef;"></div>
                <div style="width: 8px; height: 8px; background-color: #ffb900;"></div>
            </div>
            <span class="fs-5 fw-bold mb-0" style="color: #005a9e; letter-spacing: -0.3px;">TechInbox</span>
        </a>
    </div>
    
    <!-- Centered Search Bar matching the reference -->
    <div class="d-none d-md-flex align-items-center bg-white border px-3 rounded" style="width: 320px; height: 32px; border-color: #d1d1d1 !important;">
        <span class="text-muted small me-2" style="font-size: 11px;">🔍</span>
        <input type="text" placeholder="Search here..." class="border-0 w-100 small" style="outline: none; font-size: 12px; height: 26px;">
    </div>
    
    <!-- Right side options -->
    <div class="d-flex align-items-center gap-2">
        <a href="#" class="btn btn-sm text-white px-3 py-1 fw-semibold text-decoration-none" style="background-color: #b31010; border-radius: 4px; font-size: 12.5px;">Support?</a>
        
        <div class="dropdown">
            <button class="btn btn-sm btn-link text-decoration-none dropdown-toggle fw-semibold px-2" type="button" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false" style="color: #005a9e; font-size: 13px;">
                👤 <?php echo htmlspecialchars($username ?: 'Staff'); ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border" aria-labelledby="userMenuButton">
                <?php if ($isLoggedIn): ?>
                    <li><a class="dropdown-item small" href="profile.php">My Profile</a></li>
                    <li><a class="dropdown-item small" href="duty_history.php">Duty History</a></li>
                    <?php if (!empty($_SESSION['is_admin'])): ?>
                        <li><a class="dropdown-item small text-danger" href="admin.php">Admin Portal</a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item small text-danger" href="logout.php">Sign Out</a></li>
                <?php else: ?>
                    <li><a class="dropdown-item small" href="login.php">Sign In</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</header>

<!-- Mobile Navigation Offcanvas Drawer -->
<div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="mobileNavDrawer" aria-labelledby="mobileNavDrawerLabel" style="width: 280px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold d-flex align-items-center gap-2" id="mobileNavDrawerLabel">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2px; width: 18px; height: 18px;">
                <div style="width: 8px; height: 8px; background-color: #f25022;"></div>
                <div style="width: 8px; height: 8px; background-color: #7fba00;"></div>
                <div style="width: 8px; height: 8px; background-color: #00a4ef;"></div>
                <div style="width: 8px; height: 8px; background-color: #ffb900;"></div>
            </div>
            TechInbox
        </h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column justify-content-between">
        <!-- Navigation Links -->
        <div class="d-flex flex-column gap-1">
            <span class="small text-muted text-uppercase fw-bold px-3 mb-2" style="font-size: 10px; letter-spacing: 0.5px;">Applications</span>
            <a href="bookings.php" class="nav-link-sidebar d-flex align-items-center gap-2 px-3 py-2 rounded-1 text-decoration-none <?php echo ($currentScript === 'bookings.php') ? 'active-sidebar' : ''; ?>">
                <span style="font-size: 16px; color: #00a4ef;">🛠️</span>
                <span>Booked Jobs</span>
            </a>
            <a href="booking.php" class="nav-link-sidebar d-flex align-items-center gap-2 px-3 py-2 rounded-1 text-decoration-none <?php echo ($currentScript === 'booking.php') ? 'active-sidebar' : ''; ?>">
                <span style="font-size: 16px; color: #008272;">📋</span>
                <span>Device Booking</span>
            </a>
            <a href="daily-closer.php" class="nav-link-sidebar d-flex align-items-center gap-2 px-3 py-2 rounded-1 text-decoration-none <?php echo ($currentScript === 'daily-closer.php') ? 'active-sidebar' : ''; ?>">
                <span style="font-size: 16px; color: #7fba00;">📊</span>
                <span>Daily Closer</span>
            </a>
            <a href="screen-protector-finder" class="nav-link-sidebar d-flex align-items-center gap-2 px-3 py-2 rounded-1 text-decoration-none <?php echo ($currentScript === 'screen-protector-finder') ? 'active-sidebar' : ''; ?>">
                <span style="font-size: 16px; color: #ffb900;">📱</span>
                <span>Screen Protector</span>
            </a>
        </div>

        <!-- User / Session Management inside Drawer on Mobile -->
        <div class="border-top pt-3">
            <?php if ($isLoggedIn): ?>
                <div class="px-3 mb-3">
                    <span class="d-block small text-muted">Signed in as</span>
                    <a href="profile.php" class="d-block fw-bold text-dark text-decoration-none" style="font-size: 14.5px;">👤 <?php echo htmlspecialchars($username); ?></a>
                </div>
                <div class="d-flex flex-column gap-2">
                    <a href="duty_history.php" class="btn btn-light text-start border-0 py-2 px-3 d-flex align-items-center gap-2" style="font-size: 13.5px; background: none;">
                        <span>🕒</span> Duty History
                    </a>
                    <?php if (!empty($_SESSION['is_admin'])): ?>
                        <a href="admin.php" class="btn btn-light text-start border-0 py-2 px-3 d-flex align-items-center gap-2 text-danger" style="font-size: 13.5px; background: none;">
                            <span>🔑</span> Admin Portal
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="px-3 mb-3">
                    <span class="small text-muted">Not signed in</span>
                </div>
                <a href="login.php" class="btn btn-primary w-100 py-2" onclick="event.preventDefault(); document.getElementById('loginModal').style.setProperty('display', 'flex', 'important');" data-bs-dismiss="offcanvas">
                    Sign In
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.offcanvas.showing, .offcanvas.hiding, .offcanvas.show {
    transition: transform 0.15s ease-in-out !important;
}
.nav-link-sidebar {
    color: #5c5c5c !important;
    font-size: 11px;
    font-weight: 500;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 12px 4px;
    gap: 4px;
    transition: background-color 0.15s ease, color 0.15s ease;
    border-left: 3px solid transparent;
}
.nav-link-sidebar:hover {
    background-color: #f3f3f3;
    color: #242424 !important;
}
.active-sidebar {
    background-color: #f3f3f3;
    color: #005a9e !important;
    font-weight: 600;
    border-left-color: #005a9e !important;
}
</style>

<div class="d-flex flex-grow-1">
    <!-- Sidebar Navigation (Desktop only) -->
    <aside class="bg-white border-end d-none d-lg-flex flex-column py-3 px-1" style="width: 100px; min-height: calc(100vh - 56px); border-color: var(--card-border) !important;">
        <div class="d-flex flex-column gap-2">
            <a href="bookings.php" class="nav-link-sidebar rounded-1 text-decoration-none <?php echo ($currentScript === 'bookings.php') ? 'active-sidebar' : ''; ?>">
                <span style="font-size: 18px;">🛠️</span>
                <span style="line-height: 1.2;">Repairs</span>
            </a>
            <a href="booking.php" class="nav-link-sidebar rounded-1 text-decoration-none <?php echo ($currentScript === 'booking.php') ? 'active-sidebar' : ''; ?>">
                <span style="font-size: 18px;">📋</span>
                <span style="line-height: 1.2;">Booking</span>
            </a>
            <a href="daily-closer.php" class="nav-link-sidebar rounded-1 text-decoration-none <?php echo ($currentScript === 'daily-closer.php') ? 'active-sidebar' : ''; ?>">
                <span style="font-size: 18px;">📊</span>
                <span style="line-height: 1.2;">Closer</span>
            </a>
            <a href="screen-protector-finder" class="nav-link-sidebar rounded-1 text-decoration-none <?php echo ($currentScript === 'screen-protector-finder') ? 'active-sidebar' : ''; ?>">
                <span style="font-size: 18px;">📱</span>
                <span style="line-height: 1.2;">Protector</span>
            </a>
        </div>
    </aside>
    <!-- Main Content Container -->
    <div class="flex-grow-1 d-flex flex-column" style="background-color: #f3f3f3;">
