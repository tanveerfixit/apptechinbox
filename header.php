<?php
// header.php
$username = $_SESSION['username'] ?? '';
$isLoggedIn = isset($_SESSION['user_id']);
$currentScript = basename($_SERVER['PHP_SELF']);

// Dynamically resolve compiled Tailwind CSS asset path from Vite manifest
$tailwindCssPath = 'resources/css/app.css';
$manifestPath = __DIR__ . '/public/build/manifest.json';
if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);
    if (isset($manifest['resources/css/app.css']['file'])) {
        $tailwindCssPath = 'public/build/' . $manifest['resources/css/app.css']['file'];
    }
}

// Generate robust host-relative path for Hostinger
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$cleanDir = ($scriptDir === '/' || $scriptDir === '\\') ? '' : rtrim($scriptDir, '/\\');
$absoluteCssUrl = $cleanDir . '/' . $tailwindCssPath;
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($absoluteCssUrl); ?>">

<header class="bg-white border-b border-[#e0e0e0] px-4 py-2 flex justify-between items-center shadow-xs relative z-30">
    <div class="flex items-center gap-3">
        <!-- Hamburger Button (Mobile only) -->
        <button onclick="toggleMobileNav()" class="lg:hidden p-1 text-[#242424] hover:text-[#00a4ef] focus:outline-none" type="button" aria-label="Toggle navigation">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        
        <a href="index.php" class="flex items-center gap-2 text-decoration-none group">
            <div class="grid grid-cols-2 gap-[2px] w-[18px] h-[18px]">
                <div class="w-[8px] h-[8px] bg-[#f25022]"></div>
                <div class="w-[8px] h-[8px] bg-[#7fba00]"></div>
                <div class="w-[8px] h-[8px] bg-[#00a4ef]"></div>
                <div class="w-[8px] h-[8px] bg-[#ffb900]"></div>
            </div>
            <span class="text-base font-bold text-[#242424] tracking-tight group-hover:text-[#00a4ef] transition-colors">TechInbox</span>
            <span class="text-xs text-[#5c5c5c] border-l border-[#e0e0e0] pl-2 hidden sm:inline font-medium">Portal</span>
        </a>
    </div>
    
    <div class="flex items-center gap-3 text-xs">
        <?php if ($isLoggedIn): ?>
            <span class="hidden md:inline text-xs text-[#5c5c5c]">
                Signed in as <a href="profile.php" class="text-[#242424] font-semibold underline hover:text-[#00a4ef]"><?php echo htmlspecialchars($username); ?></a>
            </span>
            <a href="duty_history.php" class="hidden md:inline-flex font-semibold text-[#00a4ef] hover:underline">
                Duty History
            </a>
            <?php if (!empty($_SESSION['is_admin'])): ?>
                <a href="admin.php" class="hidden md:inline-flex font-semibold text-[#f25022] hover:underline">
                    Admin Portal
                </a>
            <?php endif; ?>
            <a href="logout.php" class="inline-flex items-center px-2.5 py-1 text-xs font-semibold text-[#f25022] hover:bg-[#fef2f2] rounded-[4px] border border-[#f25022]/30 transition-colors">
                Sign Out
            </a>
        <?php else: ?>
            <span class="hidden sm:inline text-xs text-[#5c5c5c]">Not signed in</span>
            <a href="login.php" class="inline-flex items-center px-3 py-1 text-xs font-semibold text-white bg-[#00a4ef] hover:bg-[#0086c4] rounded-[4px] shadow-xs transition-colors" onclick="event.preventDefault(); openLoginModal();">
                Sign In
            </a>
        <?php endif; ?>
    </div>
</header>

<!-- Mobile Navigation Drawer Overlay -->
<div id="mobileNavDrawer" class="fixed inset-0 bg-black/40 z-50 hidden transition-opacity duration-200" onclick="toggleMobileNav()">
    <div class="fixed top-0 left-0 bottom-0 w-[260px] bg-white shadow-xl flex flex-column justify-between transform -translate-x-full transition-transform duration-200" id="mobileDrawerContent" onclick="event.stopPropagation()">
        <div class="p-3 border-b border-[#e0e0e0] flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="grid grid-cols-2 gap-[2px] w-[18px] h-[18px]">
                    <div class="w-[8px] h-[8px] bg-[#f25022]"></div>
                    <div class="w-[8px] h-[8px] bg-[#7fba00]"></div>
                    <div class="w-[8px] h-[8px] bg-[#00a4ef]"></div>
                    <div class="w-[8px] h-[8px] bg-[#ffb900]"></div>
                </div>
                <span class="font-bold text-[#242424] text-sm">TechInbox</span>
            </div>
            <button onclick="toggleMobileNav()" class="text-[#5c5c5c] hover:text-[#242424] p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-3 flex-1 overflow-y-auto space-y-1">
            <div class="text-[10px] font-bold text-[#5c5c5c] uppercase tracking-wider px-2 mb-1.5">Applications</div>
            <a href="bookings.php" class="flex items-center gap-2 px-2.5 py-1.5 text-xs font-medium rounded-[4px] transition-colors <?php echo ($currentScript === 'bookings.php') ? 'bg-[#f3f3f3] font-semibold border-l-4 border-[#00a4ef] text-[#242424]' : 'text-[#242424] hover:bg-[#f3f3f3]'; ?>">
                <span>🛠️</span>
                <span>Booked Jobs</span>
            </a>
            <a href="booking.php" class="flex items-center gap-2 px-2.5 py-1.5 text-xs font-medium rounded-[4px] transition-colors <?php echo ($currentScript === 'booking.php') ? 'bg-[#f3f3f3] font-semibold border-l-4 border-[#008272] text-[#242424]' : 'text-[#242424] hover:bg-[#f3f3f3]'; ?>">
                <span>📋</span>
                <span>Device Booking</span>
            </a>
            <a href="daily-closer.php" class="flex items-center gap-2 px-2.5 py-1.5 text-xs font-medium rounded-[4px] transition-colors <?php echo ($currentScript === 'daily-closer.php') ? 'bg-[#f3f3f3] font-semibold border-l-4 border-[#7fba00] text-[#242424]' : 'text-[#242424] hover:bg-[#f3f3f3]'; ?>">
                <span>📊</span>
                <span>Daily Closer</span>
            </a>
            <a href="screen-protector-finder.php" class="flex items-center gap-2 px-2.5 py-1.5 text-xs font-medium rounded-[4px] transition-colors <?php echo ($currentScript === 'screen-protector-finder.php') ? 'bg-[#f3f3f3] font-semibold border-l-4 border-[#ffb900] text-[#242424]' : 'text-[#242424] hover:bg-[#f3f3f3]'; ?>">
                <span>📱</span>
                <span>Phone Screen Protector</span>
            </a>
        </div>
        <div class="p-3 border-t border-[#e0e0e0] bg-[#fafafa]">
            <?php if ($isLoggedIn): ?>
                <div class="px-1 mb-2">
                    <span class="block text-[10px] text-[#5c5c5c]">Signed in as</span>
                    <a href="profile.php" class="block font-bold text-[#242424] hover:underline text-xs">👤 <?php echo htmlspecialchars($username); ?></a>
                </div>
                <div class="space-y-1">
                    <a href="duty_history.php" class="flex items-center gap-2 px-2.5 py-1 text-xs text-[#242424] hover:bg-[#f3f3f3] rounded-[4px]">
                        <span>🕒</span> Duty History
                    </a>
                    <?php if (!empty($_SESSION['is_admin'])): ?>
                        <a href="admin.php" class="flex items-center gap-2 px-2.5 py-1 text-xs text-[#f25022] font-semibold hover:bg-[#fef2f2] rounded-[4px]">
                            <span>🔑</span> Admin Portal
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <a href="login.php" class="block w-full text-center py-1.5 px-3 text-xs font-semibold text-white bg-[#00a4ef] hover:bg-[#0086c4] rounded-[4px]" onclick="event.preventDefault(); toggleMobileNav(); openLoginModal();">
                    Sign In
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleMobileNav() {
    const drawer = document.getElementById('mobileNavDrawer');
    const content = document.getElementById('mobileDrawerContent');
    if (drawer.classList.contains('hidden')) {
        drawer.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('-translate-x-full');
        }, 10);
    } else {
        content.classList.add('-translate-x-full');
        setTimeout(() => {
            drawer.classList.add('hidden');
        }, 200);
    }
}
function openLoginModal() {
    const modal = document.getElementById('loginModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}
</script>

<div class="flex flex-1">
    <!-- Sidebar Navigation (Desktop only) -->
    <aside class="bg-white border-r border-[#e0e0e0] hidden lg:flex flex-col py-4 px-2 w-[180px] shrink-0 min-h-[calc(100vh-49px)]">
        <div class="space-y-2">
            <!-- Booked Jobs -->
            <a href="bookings.php" class="flex flex-col items-center justify-center text-center p-3 rounded-[6px] transition-all border <?php echo ($currentScript === 'bookings.php') ? 'bg-[#f3f3f3] border-l-4 border-l-[#00a4ef] border-[#e0e0e0] text-[#00a4ef] font-bold shadow-xs' : 'border-transparent text-[#242424] hover:bg-[#f3f3f3] hover:border-[#e0e0e0]'; ?>">
                <span class="text-2xl mb-1.5 leading-none">🛠️</span>
                <span class="text-sm font-semibold tracking-tight leading-snug">Booked Jobs</span>
            </a>

            <!-- Device Booking -->
            <a href="booking.php" class="flex flex-col items-center justify-center text-center p-3 rounded-[6px] transition-all border <?php echo ($currentScript === 'booking.php') ? 'bg-[#f3f3f3] border-l-4 border-l-[#008272] border-[#e0e0e0] text-[#008272] font-bold shadow-xs' : 'border-transparent text-[#242424] hover:bg-[#f3f3f3] hover:border-[#e0e0e0]'; ?>">
                <span class="text-2xl mb-1.5 leading-none">📋</span>
                <span class="text-sm font-semibold tracking-tight leading-snug">Device Booking</span>
            </a>

            <!-- Daily Closer -->
            <a href="daily-closer.php" class="flex flex-col items-center justify-center text-center p-3 rounded-[6px] transition-all border <?php echo ($currentScript === 'daily-closer.php') ? 'bg-[#f3f3f3] border-l-4 border-l-[#7fba00] border-[#e0e0e0] text-[#7fba00] font-bold shadow-xs' : 'border-transparent text-[#242424] hover:bg-[#f3f3f3] hover:border-[#e0e0e0]'; ?>">
                <span class="text-2xl mb-1.5 leading-none">📊</span>
                <span class="text-sm font-semibold tracking-tight leading-snug">Daily Closer</span>
            </a>

            <!-- Phone Screen Protector -->
            <a href="screen-protector-finder.php" class="flex flex-col items-center justify-center text-center p-3 rounded-[6px] transition-all border <?php echo ($currentScript === 'screen-protector-finder.php') ? 'bg-[#f3f3f3] border-l-4 border-l-[#ffb900] border-[#e0e0e0] text-[#d99b00] font-bold shadow-xs' : 'border-transparent text-[#242424] hover:bg-[#f3f3f3] hover:border-[#e0e0e0]'; ?>">
                <span class="text-2xl mb-1.5 leading-none">📱</span>
                <span class="text-sm font-semibold tracking-tight leading-snug">Phone Screen Protector</span>
            </a>
        </div>
    </aside>
    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col bg-[#f3f3f3]">
