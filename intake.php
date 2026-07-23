<?php
// intake.php
$sessionId = $_GET['session_id'] ?? '';
$businessName = $_GET['b'] ?? 'Store';
$businessId = $_GET['bid'] ?? '';
$timestamp = intval($_GET['t'] ?? 0);
$currentTime = time();
$isExpired = ($timestamp > 0 && ($currentTime - $timestamp) > 180);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Booking Intake</title>
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

    <div class="w-full max-w-md"
          x-data="{
              sessionId: '<?php echo htmlspecialchars($sessionId); ?>',
              businessId: '<?php echo htmlspecialchars($businessId); ?>',
              name: '',
              phone: '',
              deviceName: '',
              email: '',
              isSubmitting: false,
              success: false,
              errorMessage: '',
              remainingSeconds: <?php echo max(0, 180 - ($currentTime - $timestamp)); ?>,
              isExpired: <?php echo $isExpired ? 'true' : 'false'; ?>,
              timerInterval: null,
              init() {
                  if (this.isExpired || this.remainingSeconds <= 0) {
                      this.isExpired = true;
                      return;
                  }
                  this.timerInterval = setInterval(() => {
                      this.remainingSeconds--;
                      if (this.remainingSeconds <= 0) {
                          clearInterval(this.timerInterval);
                          this.isExpired = true;
                      }
                  }, 1000);
              },
              async submitForm() {
                  if (this.isExpired) {
                      this.errorMessage = 'This form session has expired. Please ask the merchant for a new QR code.';
                      return;
                  }
                  if (!this.name.trim() || !this.phone.trim()) {
                      this.errorMessage = 'Please fill out Name and Phone fields.';
                      return;
                  }

                  this.isSubmitting = true;
                  this.errorMessage = '';

                  try {
                      const res = await fetch('api.php?action=submit_intake', {
                          method: 'POST',
                          headers: { 'Content-Type': 'application/json' },
                          body: JSON.stringify({
                              session_id: this.sessionId,
                              business_id: this.businessId,
                              name: this.name,
                              phone: this.phone,
                              device_name: this.deviceName,
                              email: this.email
                          })
                      });
                      const data = await res.json();
                      if (data.status === 'success') {
                          this.success = true;
                          if (this.timerInterval) {
                              clearInterval(this.timerInterval);
                          }
                      } else {
                          this.errorMessage = data.message || 'Submission failed.';
                      }
                  } catch (e) {
                      this.errorMessage = 'Network connection failed.';
                  } finally {
                      this.isSubmitting = false;
                  }
              }
          }">
         
        <?php require __DIR__ . '/nav_buttons.php'; ?>
        
        <div class="bg-white border border-[#e0e0e0] rounded-[6px] shadow-md p-6 sm:p-8 space-y-6">
             <!-- Form Intake / Success / Expired Cards -->
             <template x-if="isExpired">
                 <div class="text-center py-4 space-y-2">
                     <span class="text-4xl text-[#f25022] block mb-2">&times;</span>
                     <h2 class="text-lg font-bold text-[#f25022]">QR Code Expired</h2>
                     <p class="text-xs text-[#5c5c5c] leading-relaxed">This booking session expired after 3 minutes. Please scan a fresh QR code from the merchant's screen to try again.</p>
                 </div>
             </template>

             <template x-if="success && !isExpired">
                 <div class="text-center py-4 space-y-2">
                     <span class="text-4xl text-[#008272] block mb-2">&check;</span>
                     <h2 class="text-lg font-bold text-[#242424]">Thank you!</h2>
                     <p class="text-xs text-[#5c5c5c] leading-relaxed">Your details have been received successfully. You can now put your phone away.</p>
                 </div>
             </template>

             <template x-if="!success && !isExpired">
                 <div>
                     <!-- Business Header & Instructions -->
                     <div class="text-center mb-6">
                         <h1 class="text-xl font-bold text-[#242424] tracking-tight mb-1"><?php echo htmlspecialchars($businessName); ?></h1>
                         <p class="text-xs text-[#5c5c5c] leading-relaxed">Please enter your contact and device details below to book your repair.</p>
                     </div>
                     
                     <!-- Session Timer -->
                     <div class="text-center mb-6">
                         <span class="inline-block px-3 py-1 bg-[#f3f3f3] text-[#5c5c5c] text-[10px] font-semibold border border-[#e0e0e0] rounded-full">
                             Session Expires In: <span class="font-bold text-[#242424]" x-text="Math.floor(remainingSeconds / 60) + ':' + String(remainingSeconds % 60).padStart(2, '0')"></span>
                         </span>
                     </div>
                    
                     <form @submit.prevent="submitForm" class="space-y-4">
                         <template x-if="errorMessage">
                             <div class="bg-red-50 border border-[#f25022]/30 text-[#f25022] text-xs py-2.5 px-3 rounded-[4px] text-center font-medium" x-text="errorMessage"></div>
                         </template>

                         <div>
                             <label for="name" class="block text-xs font-bold text-[#5c5c5c] mb-1">Your Name *</label>
                             <input type="text" id="name" x-model="name" class="w-full px-3.5 py-3 text-base border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#008272]" placeholder="e.g. John Doe" required autocomplete="off">
                         </div>

                         <div>
                             <label for="phone" class="block text-xs font-bold text-[#5c5c5c] mb-1">Phone Number *</label>
                             <input type="tel" id="phone" x-model="phone" class="w-full px-3.5 py-3 text-base border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#008272]" placeholder="e.g. 0891234567" required autocomplete="off">
                         </div>

                         <div>
                              <label for="email" class="block text-xs font-bold text-[#5c5c5c] mb-1">Email Address <span class="font-normal text-[#5c5c5c]">(Optional)</span></label>
                              <input type="email" id="email" x-model="email" class="w-full px-3.5 py-3 text-base border border-[#e0e0e0] rounded-[4px] bg-white text-[#242424] focus:outline-none focus:border-[#008272]" placeholder="e.g. customer@example.com" autocomplete="off">
                         </div>

                         <div class="pt-2">
                             <button type="submit" class="w-full py-3 px-4 bg-[#008272] hover:bg-[#006b5e] text-white text-base font-bold rounded-[4px] transition-colors shadow-xs" :disabled="isSubmitting">
                                 <span x-show="!isSubmitting">Submit Form</span>
                                 <span x-show="isSubmitting" class="animate-spin text-xs">🌀</span>
                             </button>
                         </div>
                     </form>
                 </div>
             </template>
        </div>
        
        <p class="text-center text-xs text-[#5c5c5c] mt-4">Powered by <?php echo htmlspecialchars($businessName); ?></p>
    </div>

</body>
</html>
