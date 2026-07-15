<?php
// vape.php
session_start();
require_once __DIR__ . '/db.php';

// Enforce login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Redirect to Laravel route if Laravel application is bootstrapped
if (defined('LARAVEL_START')) {
    return redirect()->to('/vape');
}

$username = $_SESSION['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vape Order Builder - TechInbox</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
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
            --brand-red: #f25022;
            --brand-green: #7fba00;
            --brand-blue: #00a4ef;
            --brand-yellow: #ffb900;
            --font-family: 'Outfit', 'Segoe UI', system-ui, sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-primary);
            font-family: var(--font-family);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 6px;
        }

        .btn-brand {
            background-color: var(--brand-red);
            border-color: var(--brand-red);
            color: #ffffff;
        }

        .btn-brand:hover {
            background-color: #d83b01;
            border-color: #d83b01;
            color: #ffffff;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Header Navigation -->
    <?php require_once __DIR__ . '/header.php'; ?>

    <!-- Main Content Container -->
    <main class="container-fluid px-2 px-sm-3 py-3 py-md-4 flex-grow-1" style="max-width: 1200px; margin: 0 auto;" 
          x-data="vapeOrderBuilderApp()" 
          x-init="init()">
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 fw-bold text-dark mb-0">Vape Order Builder</h1>
        </div>

        <!-- Alert Notifications -->
        <div x-show="toast.show" 
             x-transition 
             class="alert py-2 px-3 small mb-3 border-0 shadow-sm"
             :class="toast.type === 'danger' ? 'alert-danger bg-danger-subtle text-danger' : 'alert-success bg-success-subtle text-success'"
             style="border-radius: 4px; display: none;">
            <span x-text="toast.message"></span>
        </div>

        <div class="row g-4">
            
            <!-- Left Column: Add Item & Database Management -->
            <div class="col-12 col-lg-6">
                
                <!-- Add Item Form -->
                <div class="card shadow-sm border-1 p-4 mb-4">
                    <h2 class="small fw-bold text-uppercase text-muted mb-3" style="letter-spacing: 0.5px; font-size: 11px;">Add Item</h2>
                    
                    <!-- Category selection scroll container -->
                    <div class="mb-3">
                        <label class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Category</label>
                        <div class="d-flex flex-wrap gap-2 py-1">
                            <template x-for="cat in categories" :key="cat.id">
                                <button 
                                    type="button" 
                                    class="btn btn-sm px-3 py-2 fw-medium rounded-1"
                                    :class="selectedCategoryId === cat.id ? 'btn-brand text-white' : 'btn-light border text-secondary'"
                                    x-text="cat.name"
                                    x-on:click="selectCategory(cat.id)"
                                ></button>
                            </template>
                        </div>
                    </div>

                    <!-- Brand & Line Selectors -->
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="brand" class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Brand</label>
                            <select id="brand" class="form-select rounded-1" x-model="selectedBrand" x-on:change="updateLineOptions()" style="font-size: 14px;">
                                <template x-for="b in filteredBrands" :key="b">
                                    <option :value="b" x-text="b"></option>
                                </template>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="line" class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Model / Line</label>
                            <select id="line" class="form-select rounded-1" x-model="selectedLine" style="font-size: 14px;">
                                <template x-for="l in filteredLines" :key="l">
                                    <option :value="l" x-text="l || '(Standard)'"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <!-- Flavor & Quantity inputs -->
                    <div class="row g-3 mb-4">
                        <div class="col-7 position-relative">
                            <label for="flavour" class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Flavor</label>
                            <input 
                                id="flavour" 
                                class="form-control rounded-1" 
                                placeholder="Flavor..." 
                                autocomplete="off"
                                x-model="flavorInput"
                                x-on:focus="showSuggestions = true"
                                x-on:click.away="showSuggestions = false"
                                style="font-size: 14px;"
                            >
                            <!-- Suggestions Dropdown Box -->
                            <div class="position-absolute top-100 start-0 end-0 bg-white border rounded shadow-sm overflow-auto" 
                                 style="max-height: 200px; z-index: 1000; display: none;" 
                                 x-show="showSuggestions && filteredFlavors.length > 0">
                                <template x-for="flavor in filteredFlavors" :key="flavor">
                                    <div class="px-3 py-2 small cursor-pointer hover-bg-light"
                                         style="cursor: pointer;"
                                         x-on:mousedown="flavorInput = flavor; showSuggestions = false;">
                                        <span x-text="flavor"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div class="col-5">
                            <label class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Qty</label>
                            <div class="input-group">
                                <button class="btn btn-outline-secondary py-1" type="button" x-on:click="adjustQty(-1)">-</button>
                                <input id="qty" class="form-control text-center fw-bold bg-white py-1" type="number" readonly :value="quantity" style="font-size: 14px;">
                                <button class="btn btn-outline-secondary py-1" type="button" x-on:click="adjustQty(1)">+</button>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-brand w-100 py-2 text-uppercase fw-bold rounded-1" style="font-size: 13px; letter-spacing: 0.5px;" x-on:click="addItem()">
                        Add to Order
                    </button>
                </div>

                <!-- Database Management (Admin Panel) -->
                <div class="card shadow-sm border-1 p-3 mb-4">
                    <div x-on:click="adminExpanded = !adminExpanded" class="d-flex justify-content-between align-items-center" style="cursor: pointer; user-select: none;">
                        <h2 class="small fw-bold text-muted text-uppercase mb-0" style="letter-spacing: 0.5px; font-size: 11px;">⚙️ Database Management</h2>
                        <span class="small text-muted fw-bold" style="font-size: 10px;" x-text="adminExpanded ? '- COLLAPSE' : '+ EXPAND'"></span>
                    </div>

                    <div class="mt-3 pt-3 border-top" x-show="adminExpanded" style="display: none;">
                        <!-- Add Category -->
                        <div class="mb-4">
                            <h3 class="small fw-bold text-dark text-uppercase mb-2" style="font-size: 10px; letter-spacing: 0.5px;">Add New Category</h3>
                            <div class="d-flex gap-2">
                                <input class="form-control form-control-sm rounded-1" placeholder="e.g. Nicotine Pouches..." x-model="newCategoryName">
                                <button x-on:click="addCategory()" class="btn btn-sm btn-primary px-3 fw-bold rounded-1" style="background-color: var(--brand-blue); border-color: var(--brand-blue);">Add</button>
                            </div>
                        </div>

                        <!-- Add Product -->
                        <div>
                            <h3 class="small fw-bold text-dark text-uppercase mb-2" style="font-size: 10px; letter-spacing: 0.5px;">Add New Product</h3>
                            <div class="mb-2">
                                <label class="small text-muted text-uppercase mb-1" style="font-size: 9px;">Under Category</label>
                                <select class="form-select form-select-sm rounded-1" x-model="newProductCategoryId">
                                    <option value="">Select category...</option>
                                    <template x-for="cat in categories" :key="cat.id">
                                        <option :value="cat.id" x-text="cat.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="small text-muted text-uppercase mb-1" style="font-size: 9px;">Brand</label>
                                    <input class="form-control form-control-sm rounded-1" placeholder="e.g. Lost Mary" x-model="newProductBrand">
                                </div>
                                <div class="col-6">
                                    <label class="small text-muted text-uppercase mb-1" style="font-size: 9px;">Model / Line</label>
                                    <input class="form-control form-control-sm rounded-1" placeholder="e.g. BM6000" x-model="newProductLine">
                                </div>
                            </div>
                            <button x-on:click="addProduct()" class="btn btn-sm btn-primary w-100 fw-bold rounded-1" style="background-color: var(--brand-blue); border-color: var(--brand-blue);">Add Product</button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column: Current Items & Order Summary -->
            <div class="col-12 col-lg-6">

                <!-- Current Items Table -->
                <div class="card shadow-sm border-1 p-0 mb-4 overflow-hidden">
                    <div class="d-flex justify-content-between align-items-center py-2 px-3 border-bottom" style="background-color: #fafafa;">
                        <h2 class="small fw-bold text-muted text-uppercase mb-0" style="letter-spacing: 0.5px; font-size: 11px;">Current Items</h2>
                        <span class="badge bg-secondary text-uppercase fw-semibold" style="font-size: 10px; padding: 4px 8px;" x-text="orderItems.length + (orderItems.length === 1 ? ' Item' : ' Items')">0 Items</span>
                    </div>

                    <div class="table-responsive" style="max-height: 310px;">
                        <table class="table table-hover table-striped align-middle mb-0" style="font-size: 13px;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px; padding: 12px 8px;">#</th>
                                    <th style="padding: 12px 8px;">Product</th>
                                    <th style="padding: 12px 8px;">Flavor</th>
                                    <th class="text-end" style="width: 70px; padding: 12px 8px;">Qty</th>
                                    <th class="text-center" style="width: 80px; padding: 12px 8px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, idx) in orderItems" :key="item.id">
                                    <tr>
                                        <td class="text-muted" style="padding: 10px 8px;" x-text="idx + 1"></td>
                                        <td style="padding: 10px 8px;"><strong x-text="item.brand + (item.line ? ' (' + item.line + ')' : '')"></strong></td>
                                        <td style="padding: 10px 8px;" x-text="item.flavour"></td>
                                        <td class="text-end fw-bold" style="padding: 10px 8px;" x-text="item.quantity || item.qty"></td>
                                        <td class="text-center" style="padding: 10px 8px;">
                                            <button class="btn btn-sm btn-outline-danger py-1 px-2 rounded-1" x-on:click="removeItem(item.id)" style="font-size: 11px;">Remove</button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="orderItems.length === 0">
                                    <td colspan="5" class="text-center text-muted fst-italic py-4">
                                        No items added yet.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Order Summary Output preview -->
                <div class="card shadow-sm border-1 p-4 mb-4">
                    <h2 class="small fw-bold text-muted text-uppercase mb-3" style="letter-spacing: 0.5px; font-size: 11px;">Order Summary</h2>
                    
                    <textarea id="orderOutput" class="form-control text-monospace bg-light mb-3 rounded-1" rows="10" readonly placeholder="WhatsApp text preview..." style="font-family: monospace; font-size: 12px;" :value="orderSummary"></textarea>
                    
                    <div class="d-flex gap-2">
                        <button x-on:click="copyOrder()" class="btn btn-brand flex-grow-1 fw-bold rounded-1" style="font-size: 13px;">
                            Copy Order
                        </button>
                        <button x-on:click="clearOrder()" class="btn btn-outline-danger rounded-1" style="font-size: 13px;">
                            Clear
                        </button>
                        <a href="past_orders.php" class="btn btn-outline-secondary rounded-1" style="font-size: 13px;">
                            History
                        </a>
                    </div>
                </div>

            </div>

        </div>

    </main>

    <!-- Redesigned Clean Footer -->
    <footer class="bg-white border-top py-3 mt-auto w-100 shadow-sm" style="border-color: var(--card-border) !important;">
        <div class="container-fluid px-3 text-center">
            <span class="text-muted" style="font-size: 12px; letter-spacing: 0.1px;">
                Developer: <span class="fw-semibold text-dark">Tanveer</span>
                <span class="mx-2" style="color: var(--card-border);">&bull;</span>
                Support: <a href="mailto:support@techinbox.ie" class="text-decoration-none fw-semibold" style="color: var(--brand-blue) !important;">support@techinbox.ie</a>
            </span>
        </div>
    </footer>

    <!-- Bootstrap 5 JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <!-- Single Page Application Logic with Alpine.js -->
    <script>
    function vapeOrderBuilderApp() {
        return {
            categories: [],
            products: [],
            flavors: [],
            orderItems: [],

            selectedCategoryId: null,
            selectedBrand: '',
            selectedLine: '',
            flavorInput: '',
            quantity: 1,

            showSuggestions: false,
            adminExpanded: false,
            newCategoryName: '',
            newProductCategoryId: '',
            newProductBrand: '',
            newProductLine: '',

            toast: {
                show: false,
                message: '',
                type: 'success'
            },

            get filteredBrands() {
                const prods = this.products.filter(p => p.category_id === this.selectedCategoryId);
                return [...new Set(prods.map(p => p.brand))].sort();
            },

            get filteredLines() {
                const prods = this.products.filter(p => p.category_id === this.selectedCategoryId && p.brand === this.selectedBrand);
                return [...new Set(prods.map(p => p.line))].sort();
            },

            get filteredFlavors() {
                if (!this.flavorInput) return [];
                const val = this.flavorInput.toLowerCase().trim();
                return this.flavors.map(f => f.name).filter(name => name.toLowerCase().includes(val));
            },

            get orderSummary() {
                if (this.orderItems.length === 0) return '';
                const grouped = {};
                this.orderItems.forEach(item => {
                    const catName = item.category_name || "Uncategorized";
                    const lineText = item.line ? ` (${item.line})` : "";
                    const prodKey = `${item.brand}${lineText}`;

                    if (!grouped[catName]) grouped[catName] = {};
                    if (!grouped[catName][prodKey]) grouped[catName][prodKey] = [];
                    grouped[catName][prodKey].push(item);
                });

                let text = "*VAPE ORDER*\n\n";
                for (const catName in grouped) {
                    text += `*${catName.toUpperCase()}*\n`;
                    for (const prodKey in grouped[catName]) {
                        text += `_${prodKey}_\n`;
                        grouped[catName][prodKey].forEach(item => {
                            const displayQty = item.qty || item.quantity || 1;
                            text += `- ${item.flavour} x ${displayQty}\n`;
                        });
                        text += "\n";
                    }
                    text += "\n";
                }
                return text.trim();
            },

            showToast(msg, type = 'success') {
                this.toast.message = msg;
                this.toast.type = type;
                this.toast.show = true;
                setTimeout(() => {
                    this.toast.show = false;
                }, 3000);
            },

            async init() {
                await this.loadData();
                await this.loadActiveOrder();
            },

            async loadData() {
                try {
                    const res = await fetch("api.php?action=get_data");
                    const data = await res.json();
                    this.categories = data.categories || [];
                    this.products = data.products || [];
                    this.flavors = data.flavors || [];

                    if (this.categories.length > 0 && this.selectedCategoryId === null) {
                        this.selectCategory(this.categories[0].id);
                    }
                } catch (e) {
                    this.showToast("Failed to fetch initial database details.", "danger");
                }
            },

            async loadActiveOrder() {
                try {
                    const res = await fetch("api.php?action=get_active_order");
                    const data = await res.json();
                    this.orderItems = data.items || [];
                } catch (e) {
                    this.showToast("Failed to load active order list.", "danger");
                }
            },

            selectCategory(id) {
                this.selectedCategoryId = id;
                const brands = this.filteredBrands;
                if (brands.length > 0) {
                    this.selectedBrand = brands[0];
                    this.updateLineOptions();
                } else {
                    this.selectedBrand = '';
                    this.selectedLine = '';
                }
            },

            updateLineOptions() {
                const lines = this.filteredLines;
                this.selectedLine = lines.length > 0 ? lines[0] : '';
            },

            adjustQty(val) {
                this.quantity = Math.max(1, this.quantity + val);
            },

            async addItem() {
                if (!this.selectedBrand || !this.flavorInput.trim()) {
                    this.showToast("Please enter a flavor.", "danger");
                    return;
                }

                const matchingProd = this.products.find(p => {
                    const productLine = p.line || "";
                    const filterLine = this.selectedLine || "";
                    return p.category_id === this.selectedCategoryId && p.brand === this.selectedBrand && productLine === filterLine;
                });

                if (!matchingProd) {
                    this.showToast("Selected product not found in database.", "danger");
                    return;
                }

                try {
                    const res = await fetch("api.php?action=add_item", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ product_id: matchingProd.id, flavour: this.flavorInput.trim(), qty: this.quantity })
                    });
                    const data = await res.json();
                    if (data.status === 'success') {
                        this.showToast("Item added successfully!");
                        this.flavorInput = "";
                        this.quantity = 1;
                        await this.loadActiveOrder();
                        await this.loadData();
                    } else {
                        this.showToast(data.message, "danger");
                    }
                } catch (e) {
                    this.showToast("Network error saving item.", "danger");
                }
            },

            async removeItem(itemId) {
                try {
                    const res = await fetch("api.php?action=remove_item", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ item_id: itemId })
                    });
                    const data = await res.json();
                    if (data.status === 'success') {
                        this.showToast("Item removed.");
                        await this.loadActiveOrder();
                    } else {
                        this.showToast(data.message, "danger");
                    }
                } catch (e) {
                    this.showToast("Network error deleting item.", "danger");
                }
            },

            async clearOrder() {
                try {
                    const res = await fetch("api.php?action=clear_order", { method: "POST" });
                    const data = await res.json();
                    if (data.status === 'success') {
                        this.showToast("Active order cleared!");
                        await this.loadActiveOrder();
                    } else {
                        this.showToast(data.message, "danger");
                    }
                } catch (e) {
                    this.showToast("Network error clearing order.", "danger");
                }
            },

            async copyOrder() {
                const output = document.getElementById("orderOutput");
                if (!output.value) {
                    this.showToast("Add items to your order first.", "danger");
                    return;
                }

                try {
                    const res = await fetch("api.php?action=submit_order", { method: "POST" });
                    const data = await res.json();
                    if (data.status === 'success') {
                        await navigator.clipboard.writeText(output.value);
                        this.showToast("Order copied to clipboard!");
                        this.orderItems = [];
                    } else {
                        this.showToast(data.message, "danger");
                    }
                } catch (e) {
                    this.showToast("Copy error or connection failed.", "danger");
                }
            },

            async addCategory() {
                const name = this.newCategoryName.trim();
                if (!name) {
                    this.showToast("Enter a category name.", "danger");
                    return;
                }

                try {
                    const res = await fetch("api.php?action=add_category", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ name })
                    });
                    const data = await res.json();
                    if (data.status === 'success') {
                        this.newCategoryName = "";
                        this.showToast("Category added!");
                        await this.loadData();
                    } else {
                        this.showToast(data.message, "danger");
                    }
                } catch (e) {
                    this.showToast("Error adding category", "danger");
                }
            },

            async addProduct() {
                const catId = this.newProductCategoryId;
                const brand = this.newProductBrand.trim();
                const line = this.newProductLine.trim();

                if (!catId || !brand) {
                    this.showToast("Category and Brand are required.", "danger");
                    return;
                }

                try {
                    const res = await fetch("api.php?action=add_product", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ category_id: catId, brand, line })
                    });
                    const data = await res.json();
                    if (data.status === 'success') {
                        this.newProductBrand = "";
                        this.newProductLine = "";
                        this.showToast("Product added!");
                        await this.loadData();
                    } else {
                        this.showToast(data.message, "danger");
                    }
                } catch (e) {
                    this.showToast("Error adding product", "danger");
                }
            }
        };
    }
    </script>
    <style>
        .hover-bg-light:hover {
            background-color: #f3f3f3;
        }
    </style>
</body>
</html>
