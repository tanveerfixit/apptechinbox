<?php
// customer_detail.php
session_start();
require_once __DIR__ . '/db.php';

// Enforce login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';
$businessId = $_SESSION['business_id'] ?? '';

if (empty($businessId) && !empty($userId)) {
    try {
        $stmtUserAssigned = $masterDb->prepare("SELECT assigned_business_id FROM users WHERE id = ?");
        $stmtUserAssigned->execute([$userId]);
        $businessId = $stmtUserAssigned->fetchColumn() ?: '';
    } catch (Exception $e) {}
}

$businessName = 'Store';

if ($businessId) {
    try {
        $stmtBiz = $masterDb->prepare("SELECT name FROM businesses WHERE id = ?");
        $stmtBiz->execute([$businessId]);
        $businessName = $stmtBiz->fetchColumn() ?: 'Store';
    } catch (Exception $e) {}
}

// Retrieve printer configuration from isolated tenant database
$printerFontSize = 12;
$printerFontFamily = "'Courier New', Courier, monospace";
try {
    $pStmt = $db->query("SELECT font_size, font_family FROM printer_settings LIMIT 1");
    $pSettings = $pStmt->fetch();
    if ($pSettings) {
        $printerFontSize = intval($pSettings['font_size']);
        $printerFontFamily = $pSettings['font_family'];
    }
} catch (Exception $e) {}

// Retrieve booking details
$bookingId = intval($_GET['id'] ?? 0);
$customer = null;
$historyJobs = [];
$payments = [];

if ($bookingId && $db !== null && $tenantDbConnected) {
    try {
        // Fetch current customer profile info from this booking ID
        $stmt = $db->prepare("SELECT * FROM bookings WHERE id = ?");
        $stmt->execute([$bookingId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer) {
            // Fetch all jobs matching this customer's phone number to get full history
            $stmtHist = $db->prepare("SELECT * FROM bookings WHERE phone_number = ? ORDER BY created_at DESC");
            $stmtHist->execute([$customer['phone_number']]);
            $historyJobs = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

            // Fetch payment receipts ledger
            $stmtPay = $db->prepare("SELECT * FROM booking_payments WHERE booking_id = ? ORDER BY created_at ASC");
            $stmtPay->execute([$bookingId]);
            $payments = $stmtPay->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {}
}

if (!$customer) {
    header("Location: bookings.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Profile: <?php echo htmlspecialchars($customer['customer_name']); ?> - TechInbox</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/public/icons/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/svg+xml" href="/public/icons/icon.svg">
    <link rel="shortcut icon" href="/public/icons/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/public/icons/apple-touch-icon.png">
    <link rel="manifest" href="/public/icons/site.webmanifest">
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="flex flex-col min-h-screen bg-[#f3f3f3] text-[#242424] font-sans antialiased">

    <!-- Header Navigation -->
    <?php require_once __DIR__ . '/header.php'; ?>

    <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-1 space-y-4">
        <?php require __DIR__ . '/nav_buttons.php'; ?>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start"
             x-data="{
                 id: <?php echo $customer['id']; ?>,
                 name: '<?php echo htmlspecialchars($customer['customer_name'], ENT_QUOTES, 'UTF-8'); ?>',
                 phone: '<?php echo htmlspecialchars($customer['phone_number'], ENT_QUOTES, 'UTF-8'); ?>',
                 email: '<?php echo htmlspecialchars($customer['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>',
                 device: '<?php echo htmlspecialchars($customer['device_model'], ENT_QUOTES, 'UTF-8'); ?>',
                 fault: '<?php echo htmlspecialchars($customer['problem_description'], ENT_QUOTES, 'UTF-8'); ?>',
                 quote: '<?php echo htmlspecialchars($customer['total_quote'], ENT_QUOTES, 'UTF-8'); ?>',
                 deposit: '<?php echo htmlspecialchars($customer['deposit_paid'], ENT_QUOTES, 'UTF-8'); ?>',
                 status: '<?php echo htmlspecialchars($customer['status'], ENT_QUOTES, 'UTF-8'); ?>',
                 notes: '<?php echo htmlspecialchars($customer['notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?>',
                 isSaving: false,
                 successMsg: '',
                 errorMsg: '',

                 // Payments Ledger state
                 payments: <?php echo htmlspecialchars(json_encode($payments), ENT_QUOTES, 'UTF-8'); ?>,
                 payAmount: '<?php echo htmlspecialchars($customer['balance_due'], ENT_QUOTES, 'UTF-8'); ?>',
                 payMethod: 'Cash',
                 payType: 'Final Balance',
                 payRef: '',
                 isAddingPayment: false,
                 paySuccessMsg: '',
                 payErrorMsg: '',

                 async saveChanges() {
                     this.isSaving = true;
                     this.successMsg = '';
                     this.errorMsg = '';
                     try {
                         const res = await fetch('api.php?action=update_booking', {
                             method: 'POST',
                             headers: { 'Content-Type': 'application/json' },
                             body: JSON.stringify({
                                 id: this.id,
                                 name: this.name,
                                 phone: this.phone,
                                 email: this.email,
                                 device: this.device,
                                 fault: this.fault,
                                 quote: parseFloat(this.quote || 0),
                                 deposit: parseFloat(this.deposit || 0),
                                 status: this.status,
                                 notes: this.notes
                             })
                         });
                         const result = await res.json();
                         if (result.status === 'success') {
                             this.successMsg = 'Changes saved successfully!';
                             setTimeout(() => {
                                 window.location.reload();
                             }, 1000);
                         } else {
                             this.errorMsg = result.message || 'Failed to save changes.';
                         }
                     } catch(e) {
                         this.errorMsg = 'Network connection failed.';
                     } finally {
                         this.isSaving = false;
                     }
                 },

                 async collectPayment() {
                     if (parseFloat(this.payAmount) <= 0) {
                         this.payErrorMsg = 'Please enter a valid amount.';
                         return;
                     }
                     this.isAddingPayment = true;
                     this.paySuccessMsg = '';
                     this.payErrorMsg = '';
                     try {
                         const res = await fetch('api.php?action=add_payment', {
                             method: 'POST',
                             headers: { 'Content-Type': 'application/json' },
                             body: JSON.stringify({
                                 booking_id: this.id,
                                 amount: parseFloat(this.payAmount),
                                 payment_method: this.payMethod,
                                 payment_type: this.payType,
                                 reference_code: this.payRef
                             })
                         });
                         const result = await res.json();
                         if (result.status === 'success') {
                             this.paySuccessMsg = 'Payment recorded successfully!';
                             this.payments = result.data.payments;
                             this.deposit = result.data.deposit_paid;
                             this.payAmount = result.data.balance_due;
                             this.payRef = '';
                             setTimeout(() => {
                                 window.location.reload();
                             }, 1000);
                         } else {
                             this.payErrorMsg = result.message || 'Failed to add payment.';
                         }
                     } catch(e) {
                         this.payErrorMsg = 'Network error.';
                     } finally {
                         this.isAddingPayment = false;
                     }
                 },

                 printPaymentReceipt(pay) {
                     document.getElementById('pRecStore').textContent = '<?php echo htmlspecialchars($businessName, ENT_QUOTES, 'UTF-8'); ?>';
                     document.getElementById('pRecTicket').textContent = '<?php echo htmlspecialchars($customer['ticket_id'], ENT_QUOTES, 'UTF-8'); ?>';
                     document.getElementById('pRecCust').textContent = '<?php echo htmlspecialchars($customer['customer_name'], ENT_QUOTES, 'UTF-8'); ?>';
                     document.getElementById('pRecPhone').textContent = '<?php echo htmlspecialchars($customer['phone_number'], ENT_QUOTES, 'UTF-8'); ?>';
                     document.getElementById('pRecDevice').textContent = this.device;
                     
                     document.getElementById('pRecPayDate').textContent = new Date(pay.created_at).toLocaleString();
                     document.getElementById('pRecPayAmt').textContent = '€' + parseFloat(pay.amount).toFixed(2);
                     document.getElementById('pRecPayMethod').textContent = pay.payment_method + ' (' + pay.payment_type + ')';
                     document.getElementById('pRecPayRef').textContent = pay.reference_code || 'N/A';
                     document.getElementById('pRecStaff').textContent = pay.received_by;
                     
                     const currentPaid = parseFloat(this.deposit);
                     const totalQuote = parseFloat(this.quote);
                     const balanceLeft = Math.max(0, totalQuote - currentPaid);
                     
                     document.getElementById('pRecQuote').textContent = '€' + totalQuote.toFixed(2);
                     document.getElementById('pRecTotalPaid').textContent = '€' + currentPaid.toFixed(2);
                     document.getElementById('pRecBalDue').textContent = '€' + balanceLeft.toFixed(2);
                     
                     window.print();
                 }
              }">
              
             <!-- Left Panel: Customer Summary & Edit Form -->
             <div class="lg:col-span-5 print:hidden">
                 <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-xs p-6 space-y-4">
                     <h3 class="text-sm font-bold text-[#242424] pb-2 border-b border-[#e0e0e0]">🛠️ Edit Repair & Customer Details</h3>

                     <!-- Success / Error alerts -->
                     <div x-show="successMsg" class="bg-green-50 border border-[#7fba00]/40 text-[#7fba00] text-xs py-2 px-3 rounded-[4px] font-medium" x-text="successMsg"></div>
                     <div x-show="errorMsg" class="bg-red-50 border border-[#f25022]/40 text-[#f25022] text-xs py-2 px-3 rounded-[4px] font-medium" x-text="errorMsg"></div>

                     <form @submit.prevent="saveChanges" class="space-y-3">
                         <div>
                             <label class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Customer Name</label>
                             <input type="text" x-model="name" class="w-full px-3 py-1.5 text-xs border border-[#e0e0e0] rounded-[4px] bg-[#f3f3f3] text-[#5c5c5c]" disabled>
                         </div>
                         <div>
                             <label class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Phone Number</label>
                             <input type="text" x-model="phone" class="w-full px-3 py-1.5 text-xs border border-[#e0e0e0] rounded-[4px] bg-[#f3f3f3] text-[#5c5c5c]" disabled>
                         </div>
                         <div>
                             <label class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Email Address</label>
                             <input type="email" x-model="email" class="w-full px-3 py-1.5 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#008272]">
                         </div>
                         <div>
                             <label class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Device Model</label>
                             <input type="text" x-model="device" class="w-full px-3 py-1.5 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#008272]" required>
                         </div>
                         <div>
                             <label class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Problem / Fault Description</label>
                             <textarea x-model="fault" class="w-full px-3 py-1.5 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#008272]" rows="3" required></textarea>
                         </div>
                         <div class="grid grid-cols-2 gap-3">
                             <div>
                                 <label class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Quote (€)</label>
                                 <input type="number" step="0.01" x-model="quote" class="w-full px-3 py-1.5 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#008272]">
                             </div>
                             <div>
                                 <label class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Deposit (€)</label>
                                 <input type="number" step="0.01" x-model="deposit" class="w-full px-3 py-1.5 text-xs border border-[#e0e0e0] rounded-[4px] bg-[#f3f3f3] text-[#5c5c5c]" disabled title="Deposit/Total Paid is dynamically updated via the Payments ledger.">
                             </div>
                         </div>
                         <div>
                             <label class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Repair Status</label>
                             <select x-model="status" class="w-full px-3 py-1.5 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#008272]">
                                 <option value="Pending">Pending</option>
                                 <option value="Processing">Processing</option>
                                 <option value="Completed">Completed</option>
                             </select>
                         </div>
                         <div>
                             <label class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Internal Technician Notes</label>
                             <textarea x-model="notes" class="w-full px-3 py-1.5 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#008272]" rows="4" placeholder="Enter special notes, parts required, or progress updates..."></textarea>
                         </div>

                         <div class="pt-2">
                             <button type="submit" class="w-full py-2.5 px-4 bg-[#008272] hover:bg-[#006b5e] text-white text-xs font-bold uppercase tracking-wider rounded-[4px] transition-colors shadow-xs" :disabled="isSaving">
                                 <span x-show="!isSaving">💾 Save Changes</span>
                                 <span x-show="isSaving" class="animate-spin text-xs">🌀</span>
                             </button>
                         </div>
                     </form>
                 </div>
             </div>

             <!-- Right Panel: Payments, Finances & History -->
             <div class="lg:col-span-7 print:hidden space-y-6">
                 
                 <!-- Finances & Collect Payment Panel -->
                 <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-xs p-6 space-y-4">
                     <h3 class="text-sm font-bold text-[#242424] pb-2 border-b border-[#e0e0e0]">💰 Finances & Collect Payment</h3>
                     
                     <div class="grid grid-cols-3 gap-4">
                         <div class="p-3 border border-[#e0e0e0] rounded-[6px] text-center bg-[#fafafa]">
                             <div class="text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c]">Total Quote</div>
                             <div class="text-base font-bold text-[#242424] mt-1">€<span x-text="parseFloat(quote).toFixed(2)"></span></div>
                         </div>
                         <div class="p-3 border border-[#e0e0e0] rounded-[6px] text-center bg-[#fafafa]">
                             <div class="text-[10px] font-bold uppercase tracking-wider text-[#7fba00]">Total Paid</div>
                             <div class="text-base font-bold text-[#7fba00] mt-1">€<span x-text="parseFloat(deposit).toFixed(2)"></span></div>
                         </div>
                         <div class="p-3 border border-[#f25022]/30 bg-red-50/50 rounded-[6px] text-center">
                             <div class="text-[10px] font-bold uppercase tracking-wider text-[#f25022]">Balance Due</div>
                             <div class="text-base font-bold text-[#f25022] mt-1">€<span x-text="Math.max(0, parseFloat(quote) - parseFloat(deposit)).toFixed(2)"></span></div>
                         </div>
                     </div>

                     <!-- Payment Collection Form -->
                     <template x-if="parseFloat(quote) - parseFloat(deposit) > 0">
                         <form @submit.prevent="collectPayment" class="border-t border-[#e0e0e0] pt-4 space-y-3">
                             <span class="block font-bold text-xs text-[#5c5c5c]">Record Receipt / Payment</span>
                             
                             <div x-show="paySuccessMsg" class="bg-green-50 border border-[#7fba00]/40 text-[#7fba00] text-xs py-2 px-3 rounded-[4px] font-medium" x-text="paySuccessMsg"></div>
                             <div x-show="payErrorMsg" class="bg-red-50 border border-[#f25022]/40 text-[#f25022] text-xs py-2 px-3 rounded-[4px] font-medium" x-text="payErrorMsg"></div>

                             <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                 <div>
                                     <label class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Amount (€)</label>
                                     <input type="number" step="0.01" x-model="payAmount" class="w-full px-3 py-1.5 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#008272]" required>
                                 </div>
                                 <div>
                                     <label class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Method</label>
                                     <select x-model="payMethod" class="w-full px-3 py-1.5 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#008272]">
                                         <option value="Cash">Cash</option>
                                         <option value="Card BOI">Card BOI</option>
                                         <option value="Card Fixed">Card Fixed</option>
                                     </select>
                                 </div>
                                 <div>
                                     <label class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Payment Type</label>
                                     <select x-model="payType" class="w-full px-3 py-1.5 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#008272]">
                                         <option value="Deposit">Deposit</option>
                                         <option value="Partial">Partial</option>
                                         <option value="Final Balance">Final Balance</option>
                                     </select>
                                 </div>
                                 <div>
                                     <label class="block text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c] mb-1">Reference Code</label>
                                     <input type="text" x-model="payRef" class="w-full px-3 py-1.5 text-xs border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#008272]" placeholder="e.g. Card Auth">
                                 </div>
                             </div>
                             <div class="pt-2">
                                 <button type="submit" class="w-full py-2.5 px-4 bg-[#7fba00] hover:bg-[#6ea200] text-white text-xs font-bold uppercase tracking-wider rounded-[4px] transition-colors shadow-xs" :disabled="isAddingPayment">
                                     <span x-show="!isAddingPayment">💳 Record Payment Receipt</span>
                                     <span x-show="isAddingPayment" class="animate-spin text-xs">🌀</span>
                                 </button>
                             </div>
                         </form>
                     </template>
                     <template x-if="parseFloat(quote) - parseFloat(deposit) <= 0">
                         <div class="bg-green-50 border border-[#7fba00]/30 text-[#7fba00] py-2 px-3 rounded-[4px] text-center text-xs font-semibold">
                             🎉 This repair job is fully paid. Balance is €0.00.
                         </div>
                     </template>
                 </div>

                 <!-- Payment Receipts Ledger List -->
                 <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-xs p-6 space-y-4">
                     <h3 class="text-sm font-bold text-[#242424] pb-2 border-b border-[#e0e0e0]">🧾 Issued Payment Receipts</h3>
                     
                     <div class="overflow-x-auto border border-[#e0e0e0] rounded-[4px]">
                         <table class="w-full text-left text-xs border-collapse">
                             <thead>
                                 <tr class="bg-[#fafafa] border-b border-[#e0e0e0] text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c]">
                                     <th class="px-4 py-2.5">Date</th>
                                     <th class="px-4 py-2.5">Amount</th>
                                     <th class="px-4 py-2.5">Method</th>
                                     <th class="px-4 py-2.5">Type</th>
                                     <th class="px-4 py-2.5">Staff</th>
                                     <th class="px-4 py-2.5 text-right">Voucher</th>
                                 </tr>
                             </thead>
                             <tbody class="divide-y divide-[#e0e0e0]">
                                 <template x-for="pay in payments" :key="pay.id">
                                     <tr class="hover:bg-[#f9f9f9]">
                                         <td class="px-4 py-2.5 text-[#5c5c5c]" x-text="new Date(pay.created_at).toLocaleDateString() + ' ' + new Date(pay.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})"></td>
                                         <td class="px-4 py-2.5 font-bold text-[#242424]">€<span x-text="parseFloat(pay.amount).toFixed(2)"></span></td>
                                         <td class="px-4 py-2.5" x-text="pay.payment_method"></td>
                                         <td class="px-4 py-2.5" x-text="pay.payment_type"></td>
                                         <td class="px-4 py-2.5" x-text="pay.received_by"></td>
                                         <td class="px-4 py-2.5 text-right">
                                             <button @click="printPaymentReceipt(pay)" class="px-2.5 py-1 bg-white border border-[#e0e0e0] hover:bg-[#f3f3f3] text-[#242424] text-[11px] font-semibold rounded-[4px] transition-colors">
                                                 🖨️ Print
                                             </button>
                                         </td>
                                     </tr>
                                 </template>
                                 <template x-if="payments.length === 0">
                                     <tr>
                                         <td colspan="6" class="text-center text-[#5c5c5c] py-4">No payments recorded.</td>
                                     </tr>
                                 </template>
                             </tbody>
                         </table>
                     </div>
                 </div>

                 <!-- Historical Repair Jobs -->
                 <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-xs p-6 space-y-4">
                     <div>
                         <h3 class="text-sm font-bold text-[#242424]">🛠️ Repair Job History</h3>
                         <p class="text-xs text-[#5c5c5c] mt-0.5">Detailed lists of all repair bookings corresponding to this customer's registered phone number.</p>
                     </div>

                     <div class="overflow-x-auto border border-[#e0e0e0] rounded-[4px]">
                         <table class="w-full text-left text-xs border-collapse">
                             <thead>
                                 <tr class="bg-[#fafafa] border-b border-[#e0e0e0] text-[10px] font-bold uppercase tracking-wider text-[#5c5c5c]">
                                     <th class="px-4 py-2.5">Ticket ID</th>
                                     <th class="px-4 py-2.5">Device Detail</th>
                                     <th class="px-4 py-2.5">Problem Description</th>
                                     <th class="px-4 py-2.5">Finances</th>
                                     <th class="px-4 py-2.5">Status</th>
                                 </tr>
                             </thead>
                             <tbody class="divide-y divide-[#e0e0e0]">
                                 <?php foreach ($historyJobs as $job): ?>
                                     <tr class="<?php echo $job['id'] == $bookingId ? 'bg-[#f0f6ff] border-l-4 border-l-[#008272]' : 'hover:bg-[#f9f9f9]'; ?>">
                                         <td class="px-4 py-2.5 font-mono font-bold text-[#242424]">
                                             <?php echo htmlspecialchars($job['ticket_id']); ?>
                                         </td>
                                         <td class="px-4 py-2.5 font-semibold text-[#242424]">
                                             <?php echo htmlspecialchars($job['device_model']); ?>
                                         </td>
                                         <td class="px-4 py-2.5 text-[#5c5c5c] max-w-xs truncate">
                                             <?php echo htmlspecialchars($job['problem_description']); ?>
                                         </td>
                                         <td class="px-4 py-2.5 space-y-0.5 text-[11px]">
                                             <div>Quote: <strong>€<?php echo number_format($job['total_quote'], 2); ?></strong></div>
                                             <div class="text-[#7fba00]">Paid: €<?php echo number_format($job['deposit_paid'], 2); ?></div>
                                             <div class="text-[#f25022]">Due: €<?php echo number_format($job['balance_due'], 2); ?></div>
                                         </td>
                                         <td class="px-4 py-2.5">
                                             <span class="inline-block px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-[4px]
                                                 <?php
                                                     if ($job['status'] === 'Pending') echo 'bg-yellow-50 text-amber-800 border border-amber-200';
                                                     elseif ($job['status'] === 'Processing') echo 'bg-blue-50 text-blue-800 border border-blue-200';
                                                     else echo 'bg-green-50 text-green-800 border border-green-200';
                                                 ?>">
                                                 <?php echo htmlspecialchars($job['status']); ?>
                                             </span>
                                         </td>
                                     </tr>
                                 <?php endforeach; ?>
                             </tbody>
                         </table>
                     </div>
                 </div>
             </div>
         </div>
    </main>

    <!-- Payment Receipt Print Template (Standard thermal voucher style) -->
    <div id="printPaymentReceiptArea" class="hidden print:block font-mono leading-relaxed text-black w-[80mm] mx-auto p-3 space-y-3">
        <div class="text-center border-b border-dashed border-black pb-2">
            <h3 class="text-xl font-bold" id="pRecStore">Store</h3>
            <p class="text-xs font-semibold">PAYMENT RECEIPT</p>
        </div>
        
        <div class="text-xs space-y-0.5">
            <div><strong>Date:</strong> <span id="pRecPayDate"></span></div>
            <div><strong>Job Ticket ID:</strong> <span id="pRecTicket"></span></div>
        </div>

        <div class="border-b border-dashed border-black pb-2 text-xs space-y-0.5">
            <strong>CUSTOMER DETAILS</strong><br>
            Name: <span id="pRecCust"></span><br>
            Phone: <span id="pRecPhone"></span>
        </div>

        <div class="border-b border-dashed border-black pb-2 text-xs space-y-0.5">
            <strong>DEVICE</strong><br>
            Model: <span id="pRecDevice"></span>
        </div>

        <div class="border-b border-dashed border-black pb-2 text-xs space-y-0.5">
            <strong>TRANSACTION DETAILS</strong><br>
            Amount Received: <span id="pRecPayAmt" class="font-bold"></span><br>
            Payment Method: <span id="pRecPayMethod"></span><br>
            Reference Code: <span id="pRecPayRef"></span><br>
            Received By: <span id="pRecStaff"></span>
        </div>

        <div class="text-right text-xs leading-normal space-y-0.5 pt-1">
            <div>Total Job Quote: <span id="pRecQuote"></span></div>
            <div>Cumulative Paid: <span id="pRecTotalPaid"></span></div>
            <div class="border-t border-dashed border-black pt-1 font-bold text-sm">
                Remaining Balance: <span id="pRecBalDue"></span>
            </div>
        </div>

        <div class="text-center pt-3 text-xs border-t border-dashed border-black">
            Thank you for your payment!<br>
            Keep this receipt for account reference.
        </div>
    </div>

    <!-- Standard Footer -->
    <?php require_once __DIR__ . '/footer.php'; ?>

</body>
</html>
