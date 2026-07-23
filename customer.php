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
    
    <!-- Include compiled Tailwind CSS stylesheet from build manifest -->
    <?php
    $tailwindCssPath = '/resources/css/app.css';
    $manifestPath = __DIR__ . '/public/build/manifest.json';
    if (file_exists($manifestPath)) {
        $manifest = json_decode(file_get_contents($manifestPath), true);
        if (isset($manifest['resources/css/app.css']['file'])) {
            $tailwindCssPath = '/public/build/' . $manifest['resources/css/app.css']['file'];
        }
    }
    ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($tailwindCssPath); ?>">
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-[#f3f3f3] text-[#242424] font-sans antialiased py-8 flex items-center justify-center p-4">

    <div class="w-full max-w-lg" 
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
         
        <?php require __DIR__ . '/nav_buttons.php'; ?>

        <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-md p-6 space-y-6">
            <!-- Header -->
            <div class="text-center">
                <h1 class="text-xl font-bold text-[#242424] tracking-tight mb-1"><?php echo htmlspecialchars($businessName); ?></h1>
                <p class="text-xs text-[#5c5c5c]">Track the real-time progress of your device repair.</p>
            </div>

            <!-- Search Form -->
            <form @submit.prevent="performLookup">
                <div>
                    <label class="block text-xs font-bold text-[#5c5c5c] mb-1">Ticket ID or Phone Number</label>
                    <div class="flex gap-2">
                        <input type="text" x-model="searchQuery" class="flex-1 px-3.5 py-2.5 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#008272]" placeholder="e.g. TI-20260717 or 0891234567" required autocomplete="off">
                        <button type="submit" class="py-2.5 px-5 bg-[#008272] hover:bg-[#006b5e] text-white text-xs font-bold rounded-[4px] transition-colors shadow-xs">
                            <span x-show="!loading">Track</span>
                            <span x-show="loading" class="animate-spin text-xs">🌀</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Loading Spinner -->
            <div x-show="loading" class="text-center py-4">
                <span class="animate-spin text-lg text-[#008272]">🌀</span>
                <span class="ml-2 text-xs text-[#5c5c5c]">Searching records...</span>
            </div>

            <!-- Error Notification -->
            <div x-show="errorMsg" class="bg-red-50 border border-[#f25022]/30 text-[#f25022] text-xs py-2 px-3 rounded-[4px] text-center font-medium" x-text="errorMsg"></div>

            <!-- Lookup Results -->
            <div x-show="!loading && searched">
                <!-- No Jobs Found -->
                <div x-show="jobs.length === 0" class="text-center py-6">
                    <span class="text-3xl mb-2 block">🔍</span>
                    <h3 class="text-sm font-bold text-[#5c5c5c] mb-1">No Active Repairs Found</h3>
                    <p class="text-xs text-[#5c5c5c]">Double check your Ticket ID or phone number, or contact the store.</p>
                </div>

                <!-- Jobs List -->
                <div x-show="jobs.length > 0" class="space-y-4">
                    <template x-for="job in jobs" :key="job.ticket_id">
                        <div class="border border-[#e0e0e0] rounded-[6px] p-4 bg-[#fafafa] space-y-4">
                            <!-- Header ID and Date -->
                            <div class="flex justify-between items-center">
                                <span class="px-2 py-0.5 bg-[#242424] text-white font-mono text-[10px] rounded-[4px]" x-text="job.ticket_id"></span>
                                <span class="text-[#5c5c5c] text-[11px]" x-text="new Date(job.created_at).toLocaleDateString()"></span>
                            </div>

                            <!-- Progress Tracker -->
                            <div class="flex items-center justify-between py-2 relative">
                                <div class="flex-1 text-center relative z-10">
                                    <div class="w-8 h-8 mx-auto rounded-full flex items-center justify-center text-xs font-bold transition-colors"
                                         :class="job.status === 'Pending' ? 'bg-[#008272] text-white ring-4 ring-[#008272]/20' : 'bg-[#7fba00] text-white'">1</div>
                                    <div class="text-[11px] font-semibold mt-1" :class="job.status === 'Pending' ? 'text-[#008272]' : 'text-[#7fba00]'">Pending</div>
                                </div>
                                <div class="flex-1 text-center relative z-10">
                                    <div class="w-8 h-8 mx-auto rounded-full flex items-center justify-center text-xs font-bold transition-colors"
                                         :class="job.status === 'Processing' ? 'bg-[#008272] text-white ring-4 ring-[#008272]/20' : (job.status === 'Completed' ? 'bg-[#7fba00] text-white' : 'bg-[#e0e0e0] text-white')">2</div>
                                    <div class="text-[11px] font-semibold mt-1" :class="job.status === 'Processing' ? 'text-[#008272]' : (job.status === 'Completed' ? 'text-[#7fba00]' : 'text-[#5c5c5c]')">Processing</div>
                                </div>
                                <div class="flex-1 text-center relative z-10">
                                    <div class="w-8 h-8 mx-auto rounded-full flex items-center justify-center text-xs font-bold transition-colors"
                                         :class="job.status === 'Completed' ? 'bg-[#7fba00] text-white ring-4 ring-[#7fba00]/20' : 'bg-[#e0e0e0] text-white'">3</div>
                                    <div class="text-[11px] font-semibold mt-1" :class="job.status === 'Completed' ? 'text-[#7fba00]' : 'text-[#5c5c5c]'">Completed</div>
                                </div>
                            </div>

                            <!-- Details list -->
                            <div class="text-xs text-[#242424] space-y-2 pt-2 border-t border-[#e0e0e0]">
                                <div><strong class="font-bold">Device:</strong> <span x-text="job.device_model"></span></div>
                                <div><strong class="font-bold">Fault Description:</strong> <span class="text-[#5c5c5c]" x-text="job.problem_description"></span></div>
                                <div class="border-t border-[#e0e0e0] pt-2 space-y-1">
                                    <div class="flex justify-between">
                                        <span>Total Quote:</span>
                                        <strong class="font-bold">€<span x-text="parseFloat(job.total_quote).toFixed(2)"></span></strong>
                                    </div>
                                    <div class="flex justify-between text-[#7fba00]">
                                        <span>Deposit Paid:</span>
                                        <span>€<span x-text="parseFloat(job.deposit_paid).toFixed(2)"></span></span>
                                    </div>
                                    <div class="flex justify-between border-t border-[#e0e0e0] pt-1 font-bold" :class="parseFloat(job.balance_due) > 0 ? 'text-[#f25022]' : 'text-[#5c5c5c]'">
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

        <p class="text-center text-xs text-[#5c5c5c] mt-4">Powered by <?php echo htmlspecialchars($businessName); ?></p>
    </div>

</body>
</html>
