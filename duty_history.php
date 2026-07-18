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
    
    <!-- Outfit Font & Bootstrap 5 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <style>
        :root {
            --bg-color: #f3f3f3; /* Microsoft Fluent Light Gray */
            --card-bg: #ffffff;
            --card-border: #e0e0e0;
            --text-primary: #242424; /* Dark Charcoal */
            --text-secondary: #5c5c5c;
            --brand-blue: #0078d4; /* Microsoft Blue */
            --brand-blue-hover: #106ebe;
            --brand-green: #7fba00;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-primary);
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .fluent-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
            padding: 24px;
            margin-bottom: 24px;
        }

        .metric-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--brand-blue);
        }

        .metric-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            font-weight: 600;
        }

        .nav-pills .nav-link {
            border-radius: 4px;
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 14px;
            padding: 8px 16px;
            transition: all 0.15s ease;
        }

        .nav-pills .nav-link.active {
            background-color: var(--brand-blue);
            color: #ffffff;
        }

        .nav-pills .nav-link:hover:not(.active) {
            background-color: #eaeaea;
            color: var(--text-primary);
        }

        .table thead th {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            color: var(--text-secondary);
            border-bottom: 2px solid var(--card-border);
            background-color: #fafafa;
            padding: 12px 16px;
        }

        .table tbody td {
            font-size: 14px;
            padding: 12px 16px;
            vertical-align: middle;
            border-bottom: 1px solid var(--card-border);
        }

        .business-badge {
            background-color: #deecf9;
            color: #0078d4;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
    </style>
</head>
<body class="bg-light">

    <!-- Header Navigation -->
    <?php require_once __DIR__ . '/header.php'; ?>

    <main class="container-fluid px-2 px-md-4 py-3 py-md-4 flex-grow-1">
        <div class="row mb-4">
            <div class="col-12 col-md-6">
                <h1 class="h3 fw-bold text-dark mb-1">My Duty History</h1>
                <p class="small text-muted mb-0">Track your shifts and duty logs across TechInbox businesses.</p>
            </div>
            <div class="col-12 col-md-6 d-flex justify-content-md-end align-items-center mt-3 mt-md-0">
                <ul class="nav nav-pills bg-white p-1 border rounded-1" style="border-radius: 6px !important;">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter === 'week' ? 'active' : ''; ?>" href="duty_history.php?filter=week">This Week</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter === 'month' ? 'active' : ''; ?>" href="duty_history.php?filter=month">This Month</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter === 'all' ? 'active' : ''; ?>" href="duty_history.php?filter=all">All Time</a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Metrics Overview Row -->
        <div class="row">
            <div class="col-12 col-sm-6 col-md-4">
                <div class="fluent-card text-center">
                    <div class="metric-value mb-1"><?php echo $totalShifts; ?></div>
                    <div class="metric-label">Total Shifts Worked</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <div class="fluent-card text-center">
                    <div class="metric-value mb-1" style="color: var(--brand-green);"><?php echo $uniqueBusinessesCount; ?></div>
                    <div class="metric-label">Businesses Visited</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="fluent-card p-3">
                    <div class="metric-label mb-2 text-center text-md-start">Business Breakdowns</div>
                    <?php if ($uniqueBusinessesCount === 0): ?>
                        <div class="text-center py-2 text-muted small">No shift data available.</div>
                    <?php else: ?>
                        <div style="max-height: 80px; overflow-y: auto;">
                            <?php foreach ($businessStats as $stat): ?>
                                <div class="d-flex justify-content-between align-items-center mb-1 small">
                                    <span class="fw-semibold text-dark"><?php echo htmlspecialchars($stat['business_name']); ?></span>
                                    <span class="badge bg-light text-dark border"><?php echo $stat['shift_count']; ?> shifts</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Shift Log Table -->
        <div class="fluent-card p-0 overflow-hidden">
            <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center bg-light-subtle">
                <h5 class="mb-0 fw-semibold text-dark" style="font-size: 16px;">Duty Log Entries</h5>
                <span class="small text-muted">Showing <?php echo count($shifts); ?> entries</span>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Business Name</th>
                            <th>Login Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($shifts)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-5">
                                    No shifts recorded for the selected period.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($shifts as $shift): ?>
                                <tr>
                                    <td class="fw-semibold text-dark">
                                        <?php echo date('l, F j, Y', strtotime($shift['work_date'])); ?>
                                    </td>
                                    <td>
                                        <span class="business-badge">
                                            <?php echo htmlspecialchars($shift['business_name']); ?>
                                        </span>
                                    </td>
                                    <td class="text-muted">
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

    <!-- Bootstrap 5 JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
