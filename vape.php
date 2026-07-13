<?php
// vape.php
session_start();
require_once __DIR__ . '/db.php';

// Enforce login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vape Order Builder - TechInbox</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <style>
        :root {
            --brand-orange: #f25022; /* Vape orange brand color */
            --brand-orange-hover: #d83b01;
            --brand-blue: #0078d4;
        }

        body {
            background-color: #f3f3f3; /* Fluent Light Gray */
            color: #242424;
        }

        /* Category Responsive Flex Container */
        .category-scroll-container {
            display: flex;
            flex-wrap: wrap; /* Wraps clean on small screens */
            gap: 8px;
            padding: 6px 0;
        }

        /* Suggestions Dropdown Box */
        .suggestions-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background-color: #ffffff;
            border: 1px solid #d0d0d0;
            border-radius: 4px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            display: none;
        }

        .suggestion-item {
            padding: 10px 12px;
            cursor: pointer;
            font-size: 13px;
            color: #242424;
            transition: background-color 0.1s ease;
        }

        .suggestion-item:hover {
            background-color: #f3f3f3;
        }

        /* Toast notifications */
        .toast-container-custom {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 3000;
            display: none;
            background-color: #242424;
            color: #ffffff;
            padding: 12px 20px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-size: 13px;
            font-weight: 600;
            animation: toastFade 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes toastFade {
            from { transform: translateY(10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Header Navigation -->
    <header class="navbar navbar-expand navbar-light bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
        <a href="index.php" class="d-flex align-items-center gap-2 text-decoration-none">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2px; width: 18px; height: 18px;">
                <div style="width: 8px; height: 8px; background-color: #f25022;"></div>
                <div style="width: 8px; height: 8px; background-color: #7fba00;"></div>
                <div style="width: 8px; height: 8px; background-color: #00a4ef;"></div>
                <div style="width: 8px; height: 8px; background-color: #ffb900;"></div>
            </div>
            <span class="fs-5 fw-bold text-dark mb-0 leading-none">TechInbox</span>
            <span class="text-muted border-start ps-2 mb-0 d-none d-sm-inline" style="font-size: 14px;">Portal</span>
        </a>
        <div class="user-section style= d-flex align-items-center gap-3">
            <a href="index.php" class="btn-portal text-decoration-none fw-semibold text-primary" style="font-size: 14px;">&larr; Back to Portal</a>
            <span class="small text-muted d-none d-md-inline">Signed in as <a href="profile.php" class="text-dark fw-semibold text-decoration-underline"><?php echo htmlspecialchars($username); ?></a></span>
            <a href="logout.php" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1">
                Sign Out
            </a>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="container py-4 flex-grow-1">
        
        <h1 class="h4 fw-bold text-dark mb-3">Vape Order Builder</h1>

        <div class="row g-4">
            
            <!-- Left Column: Add Item & Database Management -->
            <div class="col-12 col-lg-6">
                
                <!-- Add Item Form -->
                <div class="card shadow-sm border-1 p-4 mb-4" style="border-radius: 6px; background-color: #ffffff;">
                    <h2 class="small fw-bold text-uppercase text-muted mb-3" style="letter-spacing: 0.5px; font-size: 11px;">Add Item</h2>
                    
                    <!-- Category selection scroll container -->
                    <div class="mb-3">
                        <label class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Category</label>
                        <div id="categoryContainer" class="category-scroll-container"></div>
                    </div>

                    <!-- Brand & Line Selectors -->
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="brand" class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Brand</label>
                            <select id="brand" class="form-select"></select>
                        </div>
                        <div class="col-6">
                            <label for="line" class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Model / Line</label>
                            <select id="line" class="form-select"></select>
                        </div>
                    </div>

                    <!-- Flavor & Quantity inputs -->
                    <div class="row g-3 mb-4">
                        <div class="col-7 position-relative">
                            <label for="flavour" class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Flavor</label>
                            <input id="flavour" class="form-control" placeholder="Flavor..." autocomplete="off">
                            <div id="flavourSuggestions" class="suggestions-dropdown"></div>
                            <p id="flavourHint" class="text-danger small mt-1 mb-0" style="font-size: 11px;"></p>
                        </div>
                        <div class="col-5">
                            <label class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Qty</label>
                            <div class="input-group">
                                <button class="btn btn-outline-secondary" type="button" onclick="adjustQty(-1)">-</button>
                                <input id="qty" class="form-control text-center fw-bold bg-white" type="number" min="1" value="1" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="adjustQty(1)">+</button>
                            </div>
                        </div>
                    </div>

                    <button id="addToOrderBtn" type="button" class="btn text-white w-100 py-2 text-uppercase fw-bold" style="background-color: var(--brand-orange); font-size: 13px; letter-spacing: 0.5px;" onmouseover="this.style.backgroundColor='var(--brand-orange-hover)'" onmouseout="this.style.backgroundColor='var(--brand-orange)'">
                        Add to Order
                    </button>
                </div>

                <!-- Database Management (Admin Panel) -->
                <div class="card shadow-sm border-1 p-3 mb-4" style="border-radius: 6px; background-color: #ffffff;">
                    <div onclick="toggleAdminPanel()" class="d-flex justify-content-between align-items-center" style="cursor: pointer; user-select: none;">
                        <h2 class="small fw-bold text-muted text-uppercase mb-0" style="letter-spacing: 0.5px; font-size: 11px;">⚙️ Database Management</h2>
                        <span id="adminToggleIcon" class="small text-muted fw-bold" style="font-size: 10px;">+ EXPAND</span>
                    </div>

                    <div id="adminPanelContent" class="mt-3 pt-3 border-top" style="display: none;">
                        <!-- Add Category -->
                        <div class="mb-4">
                            <h3 class="small fw-bold text-dark text-uppercase mb-2" style="font-size: 10px; letter-spacing: 0.5px;">Add New Category</h3>
                            <div class="d-flex gap-2">
                                <input id="newCatName" class="form-control form-control-sm" placeholder="e.g. Nicotine Pouches...">
                                <button onclick="addCategory()" class="btn btn-sm btn-primary px-3 fw-bold">Add</button>
                            </div>
                        </div>

                        <!-- Add Product -->
                        <div>
                            <h3 class="small fw-bold text-dark text-uppercase mb-2" style="font-size: 10px; letter-spacing: 0.5px;">Add New Product</h3>
                            <div class="mb-2">
                                <label class="small text-muted text-uppercase mb-1" style="font-size: 9px;">Under Category</label>
                                <select id="newProdCatSelect" class="form-select form-select-sm"></select>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="small text-muted text-uppercase mb-1" style="font-size: 9px;">Brand</label>
                                    <input id="newProdBrand" class="form-control form-control-sm" placeholder="e.g. Lost Mary">
                                </div>
                                <div class="col-6">
                                    <label class="small text-muted text-uppercase mb-1" style="font-size: 9px;">Model / Line</label>
                                    <input id="newProdLine" class="form-control form-control-sm" placeholder="e.g. BM6000">
                                </div>
                            </div>
                            <button onclick="addProduct()" class="btn btn-sm btn-primary w-100 fw-bold">Add Product</button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column: Current Items & Order Summary -->
            <div class="col-12 col-lg-6">

                <!-- Current Items Table -->
                <div class="card shadow-sm border-1 p-0 mb-4 overflow-hidden" style="border-radius: 6px; background-color: #ffffff;">
                    <div class="d-flex justify-content-between align-items-center py-2 px-3 border-bottom" style="background-color: #fafafa;">
                        <h2 class="small fw-bold text-muted text-uppercase mb-0" style="letter-spacing: 0.5px; font-size: 11px;">Current Items</h2>
                        <span id="itemCount" class="badge bg-secondary text-uppercase fw-semibold" style="font-size: 10px; padding: 4px 8px;">0 Items</span>
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
                            <tbody id="orderTable">
                                <tr>
                                    <td colspan="5" class="text-center text-muted fst-italic py-4">
                                        No items added yet.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Order Summary Output preview -->
                <div class="card shadow-sm border-1 p-4 mb-4" style="border-radius: 6px; background-color: #ffffff;">
                    <h2 class="small fw-bold text-muted text-uppercase mb-3" style="letter-spacing: 0.5px; font-size: 11px;">Order Summary</h2>
                    
                    <textarea id="orderOutput" class="form-control text-monospace bg-light mb-3" rows="10" readonly placeholder="WhatsApp text preview..." style="font-family: monospace; font-size: 12px;"></textarea>
                    
                    <div class="d-flex gap-2">
                        <button onclick="copyOrder()" class="btn btn-primary flex-grow-1 fw-bold" style="font-size: 13px;">
                            Copy Order
                        </button>
                        <button onclick="clearOrder()" class="btn btn-outline-danger" style="font-size: 13px;">
                            Clear
                        </button>
                        <button onclick="window.location.href='past_orders.php'" class="btn btn-outline-secondary" style="font-size: 13px;">
                            History
                        </button>
                    </div>
                </div>

            </div>

        </div>

    </main>

    <!-- Hidden toast container -->
    <div id="toast" class="toast-container-custom">
        <span id="toastMsg"></span>
    </div>

    <!-- Standard Footer -->
    <footer class="bg-white border-top py-3 text-center mt-auto w-100">
        <p class="small text-muted mb-0">
            These system apps and Utility are Developer: <span class="fw-semibold text-dark">Tanveer</span> | Support: <a href="mailto:support@techinbox.ie" class="text-decoration-none fw-semibold text-primary">support@techinbox.ie</a>
        </p>
    </footer>

    <!-- Bootstrap 5 JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
    /* GLOBAL STATE */
    let categories = [];
    let products = [];
    let flavors = [];

    let currentOrderItems = [];
    let selectedCategoryId = null;

    /* TOAST HELPER */
    function showToast(message, type = "info") {
        const toast = document.getElementById("toast");
        const msgSpan = document.getElementById("toastMsg");
        msgSpan.textContent = message;
        
        if (type === "warning") {
            toast.style.backgroundColor = "#d83b01";
        } else {
            toast.style.backgroundColor = "#242424";
        }
        
        toast.style.display = "block";
        
        setTimeout(() => {
            toast.style.display = "none";
        }, 2500);
    }

    /* FETCH & SETUP LOGIC */
    async function loadData() {
        try {
            const res = await fetch("api.php?action=get_data");
            const data = await res.json();
            
            categories = data.categories || [];
            products = data.products || [];
            flavors = data.flavors || [];

            renderCategoryContainer();
            renderNewProductCategorySelect();
            
            // Auto-select first category if available and not selected
            if (categories.length > 0 && selectedCategoryId === null) {
                selectCategory(categories[0].id);
            } else {
                updateBrandOptions();
            }
        } catch (e) {
            showToast("Failed to fetch initial database details.", "warning");
        }
    }

    async function loadActiveOrder() {
        try {
            const res = await fetch("api.php?action=get_active_order");
            const data = await res.json();
            currentOrderItems = data.items || [];
            renderOrderItems();
        } catch (e) {
            showToast("Failed to load active order list.", "warning");
        }
    }

    /* RENDER CATEGORIES */
    function renderCategoryContainer() {
        const container = document.getElementById("categoryContainer");
        container.innerHTML = "";
        
        categories.forEach(cat => {
            const btn = document.createElement("button");
            btn.type = "button";
            
            if (cat.id === selectedCategoryId) {
                btn.className = "btn btn-sm px-3 py-2 fw-bold text-white shadow-sm";
                btn.style.backgroundColor = "var(--brand-orange)";
                btn.style.borderColor = "var(--brand-orange)";
            } else {
                btn.className = "btn btn-sm btn-light border px-3 py-2 fw-semibold text-secondary";
                btn.style.transition = "all 0.15s ease-in-out";
            }
            
            btn.textContent = cat.name;
            btn.onclick = () => selectCategory(cat.id);
            container.appendChild(btn);
        });
    }

    function selectCategory(id) {
        selectedCategoryId = id;
        renderCategoryContainer();
        updateBrandOptions();
    }

    function renderNewProductCategorySelect() {
        const select = document.getElementById("newProdCatSelect");
        select.innerHTML = "";
        categories.forEach(cat => {
            const opt = document.createElement("option");
            opt.value = cat.id;
            opt.textContent = cat.name;
            select.appendChild(opt);
        });
    }

    /* BRAND & LINE DROPDOWNS */
    function updateBrandOptions() {
        const brandSelect = document.getElementById("brand");
        brandSelect.innerHTML = "";
        
        // Filter products of active category
        const filteredProds = products.filter(p => p.category_id === selectedCategoryId);
        
        // Get unique brands
        const uniqueBrands = [...new Set(filteredProds.map(p => p.brand))].sort();
        
        uniqueBrands.forEach(b => {
            const opt = document.createElement("option");
            opt.value = b;
            opt.textContent = b;
            brandSelect.appendChild(opt);
        });
        
        brandSelect.onchange = updateLineOptions;
        updateLineOptions();
    }

    function updateLineOptions() {
        const brandSelect = document.getElementById("brand");
        const lineSelect = document.getElementById("line");
        lineSelect.innerHTML = "";
        
        const selectedBrand = brandSelect.value;
        if (!selectedBrand) return;
        
        const filteredProds = products.filter(p => p.category_id === selectedCategoryId && p.brand === selectedBrand);
        
        // Get unique lines
        const uniqueLines = [...new Set(filteredProds.map(p => p.line))].sort();
        
        uniqueLines.forEach(l => {
            const opt = document.createElement("option");
            opt.value = l;
            opt.textContent = l || "(Standard)";
            lineSelect.appendChild(opt);
        });
        
        updateFlavorSuggestions();
    }

    /* SUGGESTIONS & AUTOCOMPLETE FOR FLAVORS */
    function updateFlavorSuggestions() {
        // Since flavors are globally stored in the database, we suggest the entire master list
        const activeSuggestions = flavors.map(f => f.name);
        setupAutocomplete(document.getElementById("flavour"), activeSuggestions);
    }

    function setupAutocomplete(input, list) {
        const dropdown = document.getElementById("flavourSuggestions");
        
        input.oninput = function() {
            const val = this.value.trim().toLowerCase();
            dropdown.innerHTML = "";
            document.getElementById("flavourHint").textContent = "";
            
            if (!val) {
                dropdown.style.display = "none";
                return;
            }
            
            const filtered = list.filter(item => item.toLowerCase().includes(val));
            if (filtered.length === 0) {
                dropdown.style.display = "none";
                return;
            }
            
            filtered.forEach(item => {
                const row = document.createElement("div");
                row.className = "suggestion-item";
                row.textContent = item;
                row.onclick = function() {
                    input.value = item;
                    dropdown.style.display = "none";
                };
                dropdown.appendChild(row);
            });
            dropdown.style.display = "block";
        };
        
        // Close dropdown when clicking outside
        document.addEventListener("click", (e) => {
            if (e.target !== input && e.target !== dropdown) {
                dropdown.style.display = "none";
            }
        });
    }

    /* QUANTITY ADJUSTER */
    function adjustQty(amount) {
        const qtyInput = document.getElementById("qty");
        let val = parseInt(qtyInput.value) || 1;
        val += amount;
        if (val < 1) val = 1;
        qtyInput.value = val;
    }

    /* ADD TO ORDER ACTION */
    document.getElementById("addToOrderBtn").onclick = async function() {
        const brand = document.getElementById("brand").value;
        const line = document.getElementById("line").value;
        const flavourInput = document.getElementById("flavour");
        const flavour = flavourInput.value.trim();
        const qty = parseInt(document.getElementById("qty").value) || 1;
        
        if (!brand || !flavour) {
            showToast("Please enter a flavor.", "warning");
            return;
        }

        // Find matching product
        const matchingProd = products.find(p => {
            const productLine = p.line || "";
            const filterLine = line || "";
            return p.category_id === selectedCategoryId && p.brand === brand && productLine === filterLine;
        });
        if (!matchingProd) {
            showToast("Selected product not found in database.", "warning");
            return;
        }

        try {
            const res = await fetch("api.php?action=add_item", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ product_id: matchingProd.id, flavour, qty })
            });
            const data = await res.json();
            
            if (data.status === 'success') {
                showToast("Item added successfully!");
                flavourInput.value = "";
                document.getElementById("qty").value = 1;
                await loadActiveOrder();
                await loadData(); // Reload data to sync new autocomplete entries
            } else {
                showToast(data.message, "warning");
            }
        } catch (e) {
            showToast("Network error saving item.", "warning");
        }
    };

    /* RENDER CURRENT ORDER ITEMS */
    function renderOrderItems() {
        const tbody = document.getElementById("orderTable");
        const countSpan = document.getElementById("itemCount");
        tbody.innerHTML = "";
        
        countSpan.textContent = currentOrderItems.length + (currentOrderItems.length === 1 ? " Item" : " Items");

        if (currentOrderItems.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted fst-italic py-4">No items added yet.</td></tr>`;
            generateOrderSummary();
            return;
        }

        currentOrderItems.forEach((item, index) => {
            const row = document.createElement("tr");
            
            const lineText = item.line ? ` (${item.line})` : "";
            const prodName = `${item.brand}${lineText}`;
            const displayQty = item.qty || item.quantity || 1;

            row.innerHTML = `
                <td class="text-muted" style="padding: 10px 8px;">${index + 1}</td>
                <td style="padding: 10px 8px;"><strong>${prodName}</strong></td>
                <td style="padding: 10px 8px;">${item.flavour}</td>
                <td class="text-end fw-bold" style="padding: 10px 8px;">${displayQty}</td>
                <td class="text-center" style="padding: 10px 8px;">
                    <button class="btn btn-sm btn-outline-danger py-1 px-2" onclick="removeItem(${item.id})" style="font-size: 11px;">Remove</button>
                </td>
            `;
            tbody.appendChild(row);
        });

        generateOrderSummary();
    }

    async function removeItem(itemId) {
        try {
            const res = await fetch("api.php?action=remove_item", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ item_id: itemId })
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast("Item removed.");
                await loadActiveOrder();
            } else {
                showToast(data.message, "warning");
            }
        } catch (e) {
            showToast("Network error deleting item.", "warning");
        }
    }

    /* GENERATE WA MESSAGE TEXT PREVIEW */
    function generateOrderSummary() {
        const output = document.getElementById("orderOutput");
        if (currentOrderItems.length === 0) {
            output.value = "";
            return;
        }

        // Group items by Category and Product
        const grouped = {};
        currentOrderItems.forEach(item => {
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
        
        output.value = text.trim();
    }

    /* COPY & SUBMIT ORDER */
    async function copyOrder() {
        const output = document.getElementById("orderOutput");
        if (!output.value) {
            showToast("Add items to your order first.", "warning");
            return;
        }

        try {
            // First hit the server to submit (mark as pending -> moves to history)
            const res = await fetch("api.php?action=submit_order", { method: "POST" });
            const data = await res.json();
            
            if (data.status === 'success') {
                // Copy to clipboard
                await navigator.clipboard.writeText(output.value);
                showToast("Order copied to clipboard!");
                
                // Clear state
                currentOrderItems = [];
                renderOrderItems();
            } else {
                showToast(data.message, "warning");
            }
        } catch (e) {
            showToast("Copy error or connection failed.", "warning");
        }
    }

    /* CLEAR ORDER COMPLETELY */
    async function clearOrder() {
        if (!confirm("Are you sure you want to clear this active order?")) return;
        
        try {
            const res = await fetch("api.php?action=clear_order", { method: "POST" });
            const data = await res.json();
            if (data.status === 'success') {
                showToast("Active order cleared!");
                await loadActiveOrder();
            } else {
                showToast(data.message, "warning");
            }
        } catch (e) {
            showToast("Network error clearing order.", "warning");
        }
    }

    /* ADMIN PANEL: TOGGLE COLLAPSIBLE CARD */
    function toggleAdminPanel() {
        const panel = document.getElementById("adminPanelContent");
        const icon = document.getElementById("adminToggleIcon");
        if (panel.style.display === "none") {
            panel.style.display = "block";
            icon.textContent = "- COLLAPSE";
        } else {
            panel.style.display = "none";
            icon.textContent = "+ EXPAND";
        }
    }

    /* ADMIN PANEL: OPERATIONS */
    async function addCategory() {
        const input = document.getElementById("newCatName");
        const name = input.value.trim();
        if (!name) {
            showToast("Enter a category name.", "warning");
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
                input.value = "";
                showToast("Category added!");
                await loadData();
            } else {
                showToast(data.message, "warning");
            }
        } catch (e) {
            showToast("Error adding category", "warning");
        }
    }

    async function addProduct() {
        const catId = document.getElementById("newProdCatSelect").value;
        const brand = document.getElementById("newProdBrand").value.trim();
        const line = document.getElementById("newProdLine").value.trim();
        
        if (!catId || !brand) {
            showToast("Category and Brand are required.", "warning");
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
                document.getElementById("newProdBrand").value = "";
                document.getElementById("newProdLine").value = "";
                showToast("Product added!");
                await loadData();
            } else {
                showToast(data.message, "warning");
            }
        } catch (e) {
            showToast("Error adding product", "warning");
        }
    }

    // Start App
    function init() {
        loadData();
        loadActiveOrder();
    }
    init();
    </script>
</body>
</html>
