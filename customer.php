<?php
// customer.php
require_once __DIR__ . '/db.php';

$businessId = $_GET['bid'] ?? '';
$businessName = 'Store';

if ($businessId) {
    try {
        $stmt = $masterDb->prepare("SELECT name FROM businesses WHERE id = ?");
        $stmt->execute([$businessId]);
        $businessName = $stmt->fetchColumn() ?: 'Store';
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Track My Repair - <?php echo htmlspecialchars($businessName); ?></title>
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
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        :root {
            --bg-color: #f3f3f3; /* Microsoft Fluent Light Gray */
            --card-bg: #ffffff;
            --card-border: #e0e0e0;
            --text-primary: #242424;
            --text-secondary: #5c5c5c;
            --brand-blue: #00a4ef;
            --brand-teal: #008272;
            --brand-green: #7fba00;
            --font-family: 'Outfit', 'Segoe UI', system-ui, sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-primary);
            font-family: var(--font-family);
            min-height: 100vh;
        }

        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .progress-step {
            position: relative;
            text-align: center;
            flex-grow: 1;
        }

        .progress-step::after {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            width: 100%;
            height: 3px;
            background-color: #e0e0e0;
            z-index: 1;
        }

        .progress-step:last-child::after {
            display: none;
        }

        .step-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #e0e0e0;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }

        .progress-step.active .step-icon {
            background-color: var(--brand-teal);
            box-shadow: 0 0 0 4px rgba(0, 130, 114, 0.2);
        }

        .progress-step.completed .step-icon {
            background-color: var(--brand-green);
        }

        .progress-step.completed::after {
            background-color: var(--brand-green);
        }

        .step-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-top: 6px;
        }

        .progress-step.active .step-label {
            color: var(--brand-teal);
        }

        .progress-step.completed .step-label {
            color: var(--brand-green);
        }
    </style>
</head>
<body class="d-flex align-items-center py-4">

    <div class="container" style="max-width: 500px;" 
         x-data="{
             businessId: '<?php echo htmlspecialchars($businessId); ?>',
             searchQuery: '',
             jobs: [],
             searched: false,
             loading: false,
             errorMsg: '',

             async performLookup() {
                 if (!this.searchQuery.trim()) return;
                 this.loading = true;
                 this.errorMsg = '';
                 this.searched = true;
                 try {
                     const res = await fetch('api.php?action=customer_lookup&business_id=' + this.businessId + '&search=' + encodeURIComponent(this.searchQuery.trim()));
                     const result = await res.json();
                     if (result.status === 'success') {
                         this.jobs = result.data;
                     } else {
                         this.errorMsg = result.message || 'Lookup failed.';
                     }
                 } catch (e) {
                     this.errorMsg = 'Network connection failed.';
                 } finally {
                     this.loading = false;
                 }
             }
         }">
         
        <div class="card p-4">
            <!-- Header -->
            <div class="text-center mb-4">
                <h1 class="h4 fw-bold text-dark mb-1" style="letter-spacing: -0.2px;"><?php echo htmlspecialchars($businessName); ?></h1>
                <p class="text-muted mb-0" style="font-size: 13px;">Track the real-time progress of your device repair.</p>
            </div>

            <!-- Search Form -->
            <form @submit.prevent="performLookup" class="mb-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Ticket ID or Phone Number</label>
                    <div class="input-group">
                        <input type="text" x-model="searchQuery" class="form-control" placeholder="e.g. TI-20260717 or 0891234567" required autocomplete="off">
                        <button type="submit" class="btn btn-primary" style="background-color: var(--brand-teal); border-color: var(--brand-teal);">
                            <span x-show="!loading">Track</span>
                            <span x-show="loading" class="spinner-border spinner-border-sm" role="status"></span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Loading Spinner -->
            <div x-show="loading" class="text-center py-4">
                <div class="spinner-border text-secondary spinner-border-sm" role="status"></div>
                <span class="ms-2 text-muted small">Searching records...</span>
            </div>

            <!-- Error Notification -->
            <div x-show="errorMsg" class="alert alert-danger py-2 px-3 small border-0 mb-3" style="background-color: #f8d7da; color: #842029; border-radius: 4px;" x-text="errorMsg"></div>

            <!-- Lookup Results -->
            <div x-show="!loading && searched">
                <!-- No Jobs Found -->
                <div x-show="jobs.length === 0" class="text-center py-4">
                    <span class="fs-1 d-block mb-2">🔍</span>
                    <h3 class="h6 fw-bold text-secondary mb-1">No Active Repairs Found</h3>
                    <p class="text-muted small mb-0">Double check your Ticket ID or phone number, or contact the store.</p>
                </div>

                <!-- Jobs List -->
                <div x-show="jobs.length > 0" class="d-flex flex-column gap-4">
                    <template x-for="job in jobs" :key="job.ticket_id">
                        <div class="border rounded p-3" style="background: #fafafa; border-color: #e0e0e0 !important;">
                            <!-- Header ID and Date -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-dark px-2 py-1" style="font-family: monospace; font-size: 11px;" x-text="job.ticket_id"></span>
                                <span class="text-muted" style="font-size: 11px;" x-text="new Date(job.created_at).toLocaleDateString()"></span>
                            </div>

                            <!-- Progress Tracker -->
                            <div class="d-flex align-items-center justify-content-between mb-4 mt-2">
                                <div class="progress-step" :class="job.status === 'Pending' ? 'active' : 'completed'">
                                    <div class="step-icon">1</div>
                                    <div class="step-label">Pending</div>
                                </div>
                                <div class="progress-step" :class="job.status === 'Processing' ? 'active' : (job.status === 'Completed' ? 'completed' : '')">
                                    <div class="step-icon">2</div>
                                    <div class="step-label">Processing</div>
                                </div>
                                <div class="progress-step" :class="job.status === 'Completed' ? 'active' : ''">
                                    <div class="step-icon">3</div>
                                    <div class="step-label">Completed</div>
                                </div>
                            </div>

                            <!-- Details list -->
                            <div class="small text-dark">
                                <div class="mb-2"><strong>Device:</strong> <span x-text="job.device_model"></span></div>
                                <div class="mb-2"><strong>Fault Description:</strong> <span class="text-secondary" x-text="job.problem_description"></span></div>
                                <div class="border-top pt-2 mt-2">
                                    <div class="d-flex justify-content-between">
                                        <span>Total Quote:</span>
                                        <strong>€<span x-text="parseFloat(job.total_quote).toFixed(2)"></span></strong>
                                    </div>
                                    <div class="d-flex justify-content-between text-success">
                                        <span>Deposit Paid:</span>
                                        <span>€<span x-text="parseFloat(job.deposit_paid).toFixed(2)"></span></span>
                                    </div>
                                    <div class="d-flex justify-content-between border-top mt-1 pt-1 fw-bold" :class="parseFloat(job.balance_due) > 0 ? 'text-danger' : 'text-muted'">
                                        <span>Balance Due:</span>
                                        <span>€<span x-text="parseFloat(job.balance_due).toFixed(2)"></span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <p class="text-center text-muted mt-3" style="font-size: 11px;">Powered by <?php echo htmlspecialchars($businessName); ?></p>
    </div>

    <!-- Bootstrap 5 JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
