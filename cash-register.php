<?php
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

// Fetch active business details dynamically from master
$businessName = 'Store';
$businessContact = '';
$businessEmail = '';
$businessAddress = '';

if ($businessId) {
    try {
        $stmtBiz = $masterDb->prepare("SELECT name, contact, email, address FROM businesses WHERE id = ?");
        $stmtBiz->execute([$businessId]);
        $bizProfile = $stmtBiz->fetch();
        if ($bizProfile) {
            $businessName = $bizProfile['name'];
            $businessContact = $bizProfile['contact'] ?? '';
            $businessEmail = $bizProfile['email'] ?? '';
            $businessAddress = $bizProfile['address'] ?? '';
        }
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cash Register - TechInbox</title>
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- AlpineJS -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        :root {
            --bg-color: #f3f3f3; /* Microsoft Fluent Light Gray */
            --card-bg: #ffffff;
            --card-border: #e0e0e0;
            --text-primary: #242424; /* Dark Charcoal */
            --text-secondary: #5c5c5c; /* Muted Gray */
            --brand-blue: #00a4ef; /* Microsoft Blue */
            --brand-green: #7fba00; /* Microsoft Green */
            --brand-orange: #f25022; /* Microsoft Orange/Red */
            --brand-yellow: #ffb900; /* Microsoft Yellow */
        }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-primary);
        }
        .register-container {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 6px;
        }
        .header-dropdown .dropdown-toggle::after {
            vertical-align: middle;
            margin-left: 6px;
        }
        .btn-brand-orange {
            background-color: var(--brand-orange);
            color: #fff;
            border: none;
        }
        .btn-brand-orange:hover {
            background-color: #d83b01;
            color: #fff;
        }
        .btn-brand-blue {
            background-color: var(--brand-blue);
            color: #fff;
            border: none;
        }
        .btn-brand-blue:hover {
            background-color: #0078d4;
            color: #fff;
        }
        .btn-brand-green {
            background-color: var(--brand-green);
            color: #fff;
            border: none;
        }
        .btn-brand-green:hover {
            background-color: #689900;
            color: #fff;
        }
        .segment-control .btn {
            border: 1px solid var(--card-border);
            background: #fff;
            color: var(--text-secondary);
        }
        .segment-control .btn.active {
            background-color: var(--brand-blue);
            color: #fff;
            border-color: var(--brand-blue);
        }
        .activity-header {
            cursor: pointer;
            user-select: none;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <?php require_once __DIR__ . '/header.php'; ?>

    <!-- Main Workspace Container -->
    <main class="container-fluid px-3 py-3 flex-grow-1" 
          x-data="{
              cart: [],
              searchQuery: '',
              selectedCustomer: '',
              paymentMethod: 'Card',
              taxRate: 0.00,
              showActivityLog: true,
              digitalSignatures: [],
              notes: [],
              showAddProductModal: false,
              newProdName: '',
              newProdPrice: '',
              newProdQty: 1,

              // Computed Values
              get taxableTotal() {
                  return this.cart.reduce((sum, item) => sum + (parseFloat(item.price) * parseInt(item.qty)), 0);
              },
              get taxAmount() {
                  return this.taxableTotal * this.taxRate;
              },
              get grandTotal() {
                  return this.taxableTotal + this.taxAmount;
              },
              get totalQty() {
                  return this.cart.reduce((sum, item) => sum + parseInt(item.qty), 0);
              },

              // Actions
              addToCart(name, price, qty = 1, need = 'Yes', have = 'No', onPO = 'No') {
                  if(!name || price === '') return;
                  const existing = this.cart.find(item => item.description.toLowerCase() === name.toLowerCase());
                  if (existing) {
                      existing.qty = parseInt(existing.qty) + parseInt(qty);
                  } else {
                      this.cart.push({
                          id: Date.now(),
                          description: name,
                          need: need,
                          have: have,
                          onPO: onPO,
                          qty: parseInt(qty),
                          price: parseFloat(price)
                      });
                  }
                  this.logActivity(`Added product: ${name} (Qty: ${qty})`);
              },
              removeFromCart(id) {
                  const item = this.cart.find(i => i.id === id);
                  if (item) {
                      this.cart = this.cart.filter(i => i.id !== id);
                      this.logActivity(`Removed product: ${item.description}`);
                  }
              },
              clearCart() {
                  if(confirm('Are you sure you want to clear the sale?')) {
                      this.cart = [];
                      this.searchQuery = '';
                      this.selectedCustomer = '';
                      this.paymentMethod = 'Card';
                      this.taxRate = 0.00;
                      this.digitalSignatures = [];
                      this.notes = [];
                      this.logActivity('Cleared sale and started over.');
                  }
              },
              checkout() {
                  if(this.cart.length === 0) {
                      alert('Your cart is empty.');
                      return;
                  }
                  alert(`Transaction Complete!\nTotal Amount: €${this.grandTotal.toFixed(2)}\nPayment Method: ${this.paymentMethod}\nCustomer: ${this.selectedCustomer || 'Anonymous'}`);
                  
                  // Clear everything
                  this.cart = [];
                  this.searchQuery = '';
                  this.selectedCustomer = '';
                  this.paymentMethod = 'Card';
                  this.taxRate = 0.00;
                  this.digitalSignatures = [];
                  this.notes = [];
              },
              logActivity(text) {
                  this.notes.unshift({
                      time: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'}),
                      text: text
                  });
              },
              addSignature() {
                  const sign = prompt('Enter Digital Signature Initials:');
                  if (sign) {
                      this.digitalSignatures.push(sign);
                      this.logActivity(`Added digital signature: ${sign}`);
                  }
              },
              addNote() {
                  const note = prompt('Enter custom note:');
                  if (note) {
                      this.logActivity(`Custom Note: ${note}`);
                  }
              }
          }">
        
        <!-- Top Header Area -->
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
            <div>
                <h1 class="h3 fw-bold text-dark mb-0">Cash Register</h1>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <!-- User Profile Container -->
                <div class="dropdown header-dropdown">
                    <button class="btn btn-light bg-white border dropdown-toggle d-inline-flex align-items-center gap-2 rounded-1 py-2 px-3 text-dark small fw-semibold" type="button" id="businessMenuBtn" data-bs-toggle="dropdown" aria-expanded="false">
                        <span>👤</span> <?php echo htmlspecialchars($businessName); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border" aria-labelledby="businessMenuBtn">
                        <li><a class="dropdown-item small" href="profile.php">My Profile</a></li>
                        <li><a class="dropdown-item small" href="daily-closer.php">Daily Closer</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item small text-danger" href="logout.php">Sign Out</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Workspace (70-75%) -->
            <div class="col-12 col-lg-8">
                
                <!-- Search/Input Row -->
                <div class="d-flex gap-2 mb-3">
                    <div class="position-relative flex-grow-1">
                        <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-muted">🔍</span>
                        <input type="text" 
                               x-model="searchQuery" 
                               @keyup.enter="if(searchQuery.trim()){ addToCart(searchQuery, 10.00); searchQuery=''; }"
                               class="form-control ps-5 py-2 rounded-1 text-dark" 
                               placeholder="Scan or Search Item...">
                    </div>
                    
                    <!-- Quick Add Modal Trigger -->
                    <button type="button" @click="showAddProductModal = true" class="btn btn-brand-blue rounded-1 d-inline-flex align-items-center justify-content-center" style="width: 42px; height: 42px;" title="Add Custom Item">
                        <span class="fs-5 fw-bold">+</span>
                    </button>
                    
                    <button type="button" class="btn btn-light bg-white border rounded-1 d-inline-flex align-items-center justify-content-center" style="width: 42px; height: 42px;" title="Grid View">
                        <span>🎛️</span>
                    </button>
                </div>

                <!-- Custom Product Modal (AlpineJS driven) -->
                <div class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,0.4);" x-show="showAddProductModal" x-transition>
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content rounded-1">
                            <div class="modal-header border-bottom py-2 px-3">
                                <h5 class="modal-title fw-bold text-dark fs-6">➕ Add Item manually to Cart</h5>
                                <button type="button" class="btn-close" @click="showAddProductModal = false"></button>
                            </div>
                            <div class="modal-body p-3">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-secondary">Description</label>
                                    <input type="text" x-model="newProdName" class="form-control form-control-sm text-dark" placeholder="e.g. Charging Cable">
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold text-secondary">Unit Price (€)</label>
                                        <input type="number" step="0.01" x-model="newProdPrice" class="form-control form-control-sm text-dark" placeholder="0.00">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold text-secondary">Quantity</label>
                                        <input type="number" x-model="newProdQty" class="form-control form-control-sm text-dark" min="1">
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer border-top py-2 px-3 d-flex justify-content-end gap-1">
                                <button type="button" class="btn btn-sm btn-light border" @click="showAddProductModal = false">Cancel</button>
                                <button type="button" class="btn btn-sm btn-primary text-white" @click="addToCart(newProdName, newProdPrice, newProdQty); showAddProductModal = false; newProdName=''; newProdPrice=''; newProdQty=1;">Add to Cart</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cart Table -->
                <div class="card border shadow-sm p-0 mb-4 rounded-1" style="background-color: #fff;">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light text-secondary small uppercase fw-bold border-bottom">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Description</th>
                                    <th style="width: 140px;">Need/Have/OnPO</th>
                                    <th style="width: 100px;">Time/Qty</th>
                                    <th style="width: 110px;">Unit Price</th>
                                    <th style="width: 110px;">Total</th>
                                    <th class="text-center" style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="cart.length === 0">
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted fst-italic">
                                            No product in cart.
                                        </td>
                                    </tr>
                                </template>
                                <template x-for="(item, idx) in cart" :key="item.id">
                                    <tr class="border-bottom">
                                        <td class="small text-muted" x-text="idx + 1"></td>
                                        <td>
                                            <span class="fw-semibold text-dark text-break" x-text="item.description"></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border small" x-text="`${item.need}/${item.have}/${item.onPO}`"></span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-1">
                                                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1 border" @click="if(item.qty > 1) item.qty--">-</button>
                                                <span class="fw-bold px-1" x-text="item.qty"></span>
                                                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1 border" @click="item.qty++">+</button>
                                            </div>
                                        </td>
                                        <td class="fw-semibold text-dark" x-text="`€${parseFloat(item.price).toFixed(2)}`"></td>
                                        <td class="fw-bold text-dark" x-text="`€${(parseFloat(item.price) * item.qty).toFixed(2)}`"></td>
                                        <td class="text-center">
                                            <button type="button" @click="removeFromCart(item.id)" class="btn btn-sm btn-link text-danger p-0" title="Delete">
                                                🗑️
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Activity Log Panel -->
                <div class="card border shadow-sm rounded-1" style="background-color: #fff;">
                    <div class="card-header bg-white border-bottom py-3 px-3 d-flex justify-content-between align-items-center activity-header" @click="showActivityLog = !showActivityLog">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-secondary" x-text="showActivityLog ? '▼' : '▶'"></span>
                            <h2 class="h6 fw-bold text-dark mb-0">Activity Log</h2>
                        </div>
                        
                        <div class="d-flex align-items-center gap-2" @click.stop>
                            <select class="form-select form-select-sm" style="width: 140px; font-size: 12px;">
                                <option>All Activities</option>
                            </select>
                            <button type="button" @click="addSignature()" class="btn btn-sm btn-light border text-dark fw-semibold" style="font-size: 12px;">Add Digital Signature</button>
                            <button type="button" @click="addNote()" class="btn btn-sm btn-light border text-dark fw-semibold" style="font-size: 12px;">Add New Note</button>
                        </div>
                    </div>
                    
                    <div class="card-body p-3" x-show="showActivityLog" x-transition>
                        <div class="d-flex flex-column gap-2" style="max-height: 200px; overflow-y: auto;">
                            <template x-if="notes.length === 0">
                                <span class="text-muted small">No logs yet. Try adding custom products or signatures.</span>
                            </template>
                            <template x-for="log in notes">
                                <div class="d-flex gap-2 text-dark small border-bottom pb-1">
                                    <span class="text-muted fw-semibold" x-text="log.time"></span>
                                    <span x-text="log.text"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column: Sidebar (25-30%) -->
            <div class="col-12 col-lg-4">
                
                <!-- Customer Selection -->
                <div class="card border shadow-sm p-3 mb-4 rounded-1" style="background-color: #fff;">
                    <label class="form-label small fw-bold text-secondary mb-2">Customer Selection</label>
                    <div class="d-flex gap-2">
                        <input type="text" x-model="selectedCustomer" class="form-control text-dark rounded-1" placeholder="Search Customers">
                        <button type="button" @click="selectedCustomer = prompt('Enter customer name:') || ''" class="btn btn-light border rounded-1 fw-bold">+ New</button>
                    </div>
                </div>

                <!-- Financial Totals Box -->
                <div class="card border shadow-sm p-3 mb-4 rounded-1" style="background-color: #fff; border-color: var(--card-border) !important;">
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <span class="small text-secondary fw-semibold">Taxable Total :</span>
                        <span class="fw-bold text-dark" x-text="`€${taxableTotal.toFixed(2)}`"></span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <div class="d-flex align-items-center gap-1">
                            <span class="small text-secondary fw-semibold">Tax:</span>
                            <select class="form-select form-select-sm py-0 border-0 bg-transparent text-secondary small fw-bold" style="width: auto; outline: none; box-shadow: none;" x-model.number="taxRate">
                                <option value="0.00">Vat0 (0.000%)</option>
                                <option value="0.135">Vat13.5 (13.500%)</option>
                                <option value="0.23">Vat23 (23.000%)</option>
                            </select>
                        </div>
                        <span class="fw-bold text-dark" x-text="`€${taxAmount.toFixed(2)}`"></span>
                    </div>

                    <div class="text-secondary small mb-3">
                        Total Time/QTY: <strong class="text-dark" x-text="totalQty"></strong>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <span class="fs-6 fw-bold text-dark">Grand Total :</span>
                        <span class="fs-5 fw-bold text-success" x-text="`€${grandTotal.toFixed(2)}`"></span>
                    </div>
                </div>

                <!-- Payment Method Section -->
                <div class="card border shadow-sm p-3 mb-4 rounded-1" style="background-color: #fff;">
                    <label class="form-label small fw-bold text-secondary mb-2">Payment Method</label>
                    <div class="d-flex gap-2 segment-control">
                        <button type="button" @click="paymentMethod = 'Card'" :class="{'active': paymentMethod === 'Card'}" class="btn btn-sm flex-fill rounded-1 fw-bold py-2">Card</button>
                        <button type="button" @click="paymentMethod = 'Cash'" :class="{'active': paymentMethod === 'Cash'}" class="btn btn-sm flex-fill rounded-1 fw-bold py-2">Cash</button>
                        <button type="button" @click="paymentMethod = 'Other'" :class="{'active': paymentMethod === 'Other'}" class="btn btn-sm flex-fill rounded-1 fw-bold py-2">Other</button>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex flex-column gap-2">
                    <button type="button" @click="checkout()" class="btn btn-brand-green w-100 py-3 rounded-1 fw-bold text-uppercase" style="letter-spacing: 0.5px;">
                        Checkout & Complete
                    </button>
                    
                    <button type="button" @click="clearCart()" class="btn btn-outline-danger w-100 py-2 rounded-1 fw-semibold">
                        Clear Sale & Start Over
                    </button>
                </div>

            </div>
        </div>

    </main>

    <?php require_once __DIR__ . '/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
