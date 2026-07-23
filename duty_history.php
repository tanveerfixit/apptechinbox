<?php
// duty_history.php
session_start();
require_once __DIR__ . '/db.php';

// Enforce login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';

// Determine active filter (all, week, month)
$filter = $_GET['filter'] ?? 'week';
if (!in_array($filter, ['all', 'week', 'month'])) {
    $filter = 'week';
}

// Build SQL query based on filter
$dateCondition = "";
$params = [$userId];

if ($filter === 'week') {
    // Current week starting Monday
    $dateCondition = "AND work_date >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)";
} elseif ($filter === 'month') {
    // Current calendar month
    $dateCondition = "AND work_date >= DATE_FORMAT(NOW(), '%Y-%m-01')";
}

// 1. Fetch detailed duty history logs
$historyQuery = "
    SELECT h.id, b.name AS business_name, h.work_date, h.login_time 
    FROM user_duty_history h
    JOIN businesses b ON h.business_id = b.id
    WHERE h.user_id = ? $dateCondition
    ORDER BY h.work_date DESC, h.login_time DESC
";
$stmt = $masterDb->prepare($historyQuery);
$stmt->execute($params);
$shifts = $stmt->fetchAll();

// 2. Fetch summary metrics for selected filter
$summaryQuery = "
    SELECT b.name AS business_name, COUNT(*) as shift_count
    FROM user_duty_history h
    JOIN businesses b ON h.business_id = b.id
    WHERE h.user_id = ? $dateCondition
    GROUP BY b.name
    ORDER BY shift_count DESC
";
$stmtSum = $masterDb->prepare($summaryQuery);
$stmtSum->execute($params);
$businessStats = $stmtSum->fetchAll();

$totalShifts = array_sum(array_column($businessStats, 'shift_count'));
$uniqueBusinessesCount = count($businessStats);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Duty History - TechInbox</title>
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

    <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-1 space-y-6">
        <?php require __DIR__ . '/nav_buttons.php'; ?>

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[#242424] tracking-tight">My Duty History</h1>
                <p class="text-xs text-[#5c5c5c] mt-0.5">Track your shifts and duty logs across TechInbox businesses.</p>
            </div>
            <div>
                <div class="inline-flex bg-white p-1 border border-[#e0e0e0] rounded-[6px] shadow-xs">
                    <a href="duty_history.php?filter=week" class="px-3 py-1.5 text-xs font-semibold rounded-[4px] transition-colors <?php echo $filter === 'week' ? 'bg-[#00a4ef] text-white shadow-xs' : 'text-[#5c5c5c] hover:bg-[#f3f3f3] hover:text-[#242424]'; ?>">
                        This Week
                    </a>
                    <a href="duty_history.php?filter=month" class="px-3 py-1.5 text-xs font-semibold rounded-[4px] transition-colors <?php echo $filter === 'month' ? 'bg-[#00a4ef] text-white shadow-xs' : 'text-[#5c5c5c] hover:bg-[#f3f3f3] hover:text-[#242424]'; ?>">
                        This Month
                    </a>
                    <a href="duty_history.php?filter=all" class="px-3 py-1.5 text-xs font-semibold rounded-[4px] transition-colors <?php echo $filter === 'all' ? 'bg-[#00a4ef] text-white shadow-xs' : 'text-[#5c5c5c] hover:bg-[#f3f3f3] hover:text-[#242424]'; ?>">
                        All Time
                    </a>
                </div>
            </div>
        </div>

        <!-- Metrics Overview Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white border border-[#e0e0e0] rounded-[6px] p-5 text-center shadow-xs">
                <div class="text-3xl font-bold text-[#00a4ef] mb-1"><?php echo $totalShifts; ?></div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c]">Total Shifts Worked</div>
            </div>
            <div class="bg-white border border-[#e0e0e0] rounded-[6px] p-5 text-center shadow-xs">
                <div class="text-3xl font-bold text-[#7fba00] mb-1"><?php echo $uniqueBusinessesCount; ?></div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c]">Businesses Visited</div>
            </div>
            <div class="bg-white border border-[#e0e0e0] rounded-[6px] p-5 shadow-xs">
                <div class="text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-2 text-center sm:text-left">Business Breakdowns</div>
                <?php if ($uniqueBusinessesCount === 0): ?>
                    <div class="text-center py-2 text-xs text-[#5c5c5c]">No shift data available.</div>
                <?php else: ?>
                    <div class="max-h-20 overflow-y-auto space-y-1.5 pr-1">
                        <?php foreach ($businessStats as $stat): ?>
                            <div class="flex justify-between items-center text-xs">
                                <span class="font-semibold text-[#242424]"><?php echo htmlspecialchars($stat['business_name']); ?></span>
                                <span class="px-2 py-0.5 bg-[#f3f3f3] text-[#5c5c5c] font-medium rounded-[4px] border border-[#e0e0e0] text-[10px]"><?php echo $stat['shift_count']; ?> shifts</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Shift Log Table -->
        <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-[#e0e0e0] bg-[#fafafa] flex justify-between items-center">
                <h2 class="text-sm font-bold text-[#242424]">Duty Log Entries</h2>
                <span class="text-xs text-[#5c5c5c]">Showing <?php echo count($shifts); ?> entries</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#fafafa] border-b border-[#e0e0e0] text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c]">
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">Business Name</th>
                            <th class="px-5 py-3">Login Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e0e0e0]">
                        <?php if (empty($shifts)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-[#5c5c5c] py-8">
                                    No shifts recorded for the selected period.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($shifts as $shift): ?>
                                <tr class="hover:bg-[#f9f9f9] transition-colors">
                                    <td class="px-5 py-3 font-semibold text-[#242424]">
                                        <?php echo date('l, F j, Y', strtotime($shift['work_date'])); ?>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="inline-block px-2.5 py-1 bg-[#deecf9] text-[#0078d4] font-semibold text-xs rounded-full">
                                            <?php echo htmlspecialchars($shift['business_name']); ?>
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-[#5c5c5c]">
                                        <?php echo date('g:i A', strtotime($shift['login_time'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Standard Footer -->
    <?php require_once __DIR__ . '/footer.php'; ?>

</body>
</html>
