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

        .form-control {
            border-radius: 4px;
            border-color: #d1d1d1;
            padding: 12px 14px;
            font-size: 17px; /* Enlarged for clear reading */
        }

        .form-control:focus {
            border-color: var(--brand-teal);
            box-shadow: 0 0 0 3px rgba(0, 130, 114, 0.15);
        }

        .form-label {
            font-size: 16px !important; /* Enlarged for clear reading */
        }

        .btn-submit {
            background-color: var(--brand-teal);
            border-color: var(--brand-teal);
            color: #ffffff;
            font-weight: 600;
            padding: 12px 16px;
            font-size: 17px; /* Enlarged button font size */
            border-radius: 4px;
            width: 100%;
            transition: all 0.15s ease-in-out;
        }

        .btn-submit:hover {
            background-color: #006b5e;
            border-color: #006b5e;
            color: #ffffff;
        }

        p {
            font-size: 14.5px !important;
        }

        h1.h5 {
            font-size: 1.5rem !important; /* Enlarged business title */
        }
    </style>
</head>
<body class="py-4">

    <div class="container" style="max-width: 440px;"
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
         
        <div class="card p-4">
             <!-- Form Intake / Success / Expired Cards -->
             <template x-if="isExpired">
                 <div class="text-center py-3">
                     <span class="fs-1 d-block mb-3" style="color: var(--brand-red);">&times;</span>
                     <h2 class="h5 fw-bold text-danger mb-2">QR Code Expired</h2>
                     <p class="text-muted small mb-0">This booking session expired after 3 minutes. Please scan a fresh QR code from the merchant's screen to try again.</p>
                 </div>
             </template>

             <template x-if="success && !isExpired">
                 <div class="text-center py-3">
                     <span class="fs-1 d-block mb-3" style="color: var(--brand-teal);">&check;</span>
                     <h2 class="h5 fw-bold text-dark mb-2">Thank you!</h2>
                     <p class="text-muted small mb-0">Your details have been received successfully. You can now put your phone away.</p>
                 </div>
             </template>

             <template x-if="!success && !isExpired">
                 <div>
                     <!-- Business Header & Instructions -->
                     <div class="text-center mb-4">
                         <h1 class="h5 fw-bold text-dark mb-1" style="letter-spacing: -0.2px;"><?php echo htmlspecialchars($businessName); ?></h1>
                         <p class="text-muted mb-0" style="font-size: 12px; line-height: 1.4;">Please enter your contact and device details below to book your repair.</p>
                     </div>
                     
                     <!-- Session Timer -->
                     <div class="text-center mb-4">
                         <span class="badge bg-secondary-subtle text-secondary px-2 py-1" style="font-size: 10px; border: 1px solid var(--card-border);">
                             Session Expires In: <span class="fw-bold text-dark" x-text="Math.floor(remainingSeconds / 60) + ':' + String(remainingSeconds % 60).padStart(2, '0')"></span>
                         </span>
                     </div>
                    
                    <form @submit.prevent="submitForm">
                        <template x-if="errorMessage">
                            <div class="alert alert-danger py-2 px-3 small border-0 mb-3" style="background-color: #f8d7da; color: #842029; border-radius: 4px;" x-text="errorMessage"></div>
                        </template>

                        <div class="mb-3">
                            <label for="name" class="form-label small fw-bold text-secondary">Your Name</label>
                            <input type="text" id="name" x-model="name" class="form-control" placeholder="e.g. John Doe" required autocomplete="off">
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label small fw-bold text-secondary">Phone Number</label>
                            <input type="tel" id="phone" x-model="phone" class="form-control" placeholder="e.g. 0891234567" required autocomplete="off">
                        </div>

                         <div class="mb-4">
                             <label for="email" class="form-label small fw-bold text-secondary">Email Address <span class="text-muted fw-normal">(Optional)</span></label>
                             <input type="email" id="email" x-model="email" class="form-control" placeholder="e.g. customer@example.com" autocomplete="off">
                         </div>

                        <button type="submit" class="btn btn-submit" :disabled="isSubmitting">
                            <span x-show="!isSubmitting">Submit Form</span>
                            <span x-show="isSubmitting" class="spinner-border spinner-border-sm" role="status"></span>
                        </button>
                    </form>
                </div>
            </template>
        </div>
        
                <p class="text-center text-muted mt-3" style="font-size: 11px;">Powered by <?php echo htmlspecialchars($businessName); ?></p>
    </div>

</body>
</html>
