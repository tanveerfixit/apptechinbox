<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VapeOrderBuilder extends Component
{
    // Autocomplete & DB Cache
    public array $categories = [];
    public array $products = [];
    public array $flavors = [];

    // Current State
    public ?int $selectedCategoryId = null;
    public string $selectedBrand = '';
    public string $selectedLine = '';
    public string $flavorInput = '';
    public int $quantity = 1;

    // Cart Items
    public array $orderItems = [];

    // Admin Fields
    public bool $adminExpanded = false;
    public string $newCategoryName = '';
    public ?int $newProductCategoryId = null;
    public string $newProductBrand = '';
    public string $newProductLine = '';

    // Error and success display
    public string $errorMessage = '';
    public string $successMessage = '';

    public function mount()
    {
        $isLoggedIn = auth()->check() || session()->has('user_id');
        if (!$isLoggedIn) {
            return redirect()->to('/login');
        }

        $this->loadDbData();
        $this->loadActiveOrder();
    }

    public function loadDbData()
    {
        try {
            $this->categories = DB::table('categories')->orderBy('name')->get()->toArray();
            $this->products = DB::table('products')->orderBy('brand')->orderBy('line')->get()->toArray();
            $this->flavors = DB::table('flavors')->orderBy('name')->pluck('name')->toArray();

            // Set default category if not set
            if (!empty($this->categories) && is_null($this->selectedCategoryId)) {
                $this->selectCategory($this->categories[0]->id);
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to load database details.';
        }
    }

    public function selectCategory($categoryId)
    {
        $this->selectedCategoryId = (int)$categoryId;
        $this->selectedBrand = '';
        $this->selectedLine = '';
        
        $brands = $this->getBrandsForCategory();
        if (!empty($brands)) {
            $this->selectedBrand = $brands[0];
            $lines = $this->getLinesForBrand();
            if (!empty($lines)) {
                $this->selectedLine = $lines[0];
            }
        }
    }

    public function getBrandsForCategory(): array
    {
        return collect($this->products)
            ->where('category_id', $this->selectedCategoryId)
            ->pluck('brand')
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    public function getLinesForBrand(): array
    {
        return collect($this->products)
            ->where('category_id', $this->selectedCategoryId)
            ->where('brand', $this->selectedBrand)
            ->pluck('line')
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    public function updatedSelectedBrand($value)
    {
        $lines = $this->getLinesForBrand();
        $this->selectedLine = !empty($lines) ? $lines[0] : '';
    }

    public function adjustQty($amount)
    {
        $this->quantity = max(1, $this->quantity + $amount);
    }

    public function loadActiveOrder()
    {
        try {
            // Find active order
            $order = DB::table('orders')->where('status', 'active')->first();
            if (!$order) {
                $orderId = DB::table('orders')->insertGetId([
                    'status' => 'active',
                    'created_at' => now(),
                ]);
            } else {
                $orderId = $order->id;
            }

            // Fetch order items
            $this->orderItems = DB::table('order_items as oi')
                ->join('products as p', 'oi.product_id', '=', 'p.id')
                ->join('flavors as f', 'oi.flavor_id', '=', 'f.id')
                ->join('categories as c', 'p.category_id', '=', 'c.id')
                ->where('oi.order_id', $orderId)
                ->select('oi.id', 'oi.quantity', 'oi.status', 'p.brand', 'p.line', 'f.name as flavour', 'c.name as category_name')
                ->orderBy('oi.id', 'desc')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to load active order list: ' . $e->getMessage();
        }
    }

    public function addItem()
    {
        $this->flavorInput = trim($this->flavorInput);
        if (empty($this->selectedBrand) || empty($this->flavorInput)) {
            $this->errorMessage = 'Please enter a flavor and ensure products are loaded.';
            return;
        }

        // Find matching product
        $matchingProduct = collect($this->products)
            ->where('category_id', $this->selectedCategoryId)
            ->where('brand', $this->selectedBrand)
            ->where('line', $this->selectedLine)
            ->first();

        if (!$matchingProduct) {
            $this->errorMessage = 'Selected product not found in database.';
            return;
        }

        try {
            // Ensure flavor exists
            $flavor = DB::table('flavors')->whereRaw('LOWER(name) = LOWER(?)', [$this->flavorInput])->first();
            if (!$flavor) {
                $flavorId = DB::table('flavors')->insertGetId(['name' => $this->flavorInput]);
            } else {
                $flavorId = $flavor->id;
            }

            // Get or create active order
            $order = DB::table('orders')->where('status', 'active')->first();
            $orderId = $order ? $order->id : DB::table('orders')->insertGetId(['status' => 'active', 'created_at' => now()]);

            // Save order item
            DB::table('order_items')->insert([
                'order_id' => $orderId,
                'product_id' => $matchingProduct->id,
                'flavor_id' => $flavorId,
                'quantity' => $this->quantity,
                'status' => 'pending',
            ]);

            $this->flavorInput = '';
            $this->quantity = 1;
            $this->successMessage = 'Item added successfully!';
            $this->errorMessage = '';

            $this->loadActiveOrder();
            $this->loadDbData();
        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to add item: ' . $e->getMessage();
        }
    }

    public function removeItem($itemId)
    {
        try {
            DB::table('order_items')->where('id', $itemId)->delete();
            $this->successMessage = 'Item removed.';
            $this->loadActiveOrder();
        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to remove item.';
        }
    }

    public function clearOrder()
    {
        try {
            $order = DB::table('orders')->where('status', 'active')->first();
            if ($order) {
                DB::table('order_items')->where('order_id', $order->id)->delete();
            }
            $this->successMessage = 'Active order cleared!';
            $this->loadActiveOrder();
        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to clear order.';
        }
    }

    public function submitOrder()
    {
        try {
            $order = DB::table('orders')->where('status', 'active')->first();
            if ($order) {
                DB::table('orders')->where('id', $order->id)->update(['status' => 'pending']);
            }
            $this->successMessage = 'Order marked as pending and finalized.';
            $this->loadActiveOrder();
        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to submit order.';
        }
    }

    public function toggleAdminPanel()
    {
        $this->adminExpanded = !$this->adminExpanded;
    }

    public function addCategory()
    {
        $name = trim($this->newCategoryName);
        if (empty($name)) {
            $this->errorMessage = 'Enter a category name.';
            return;
        }

        try {
            DB::table('categories')->insert(['name' => $name]);
            $this->newCategoryName = '';
            $this->successMessage = 'Category added!';
            $this->loadDbData();
        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to add category: ' . $e->getMessage();
        }
    }

    public function addProduct()
    {
        $catId = $this->newProductCategoryId;
        $brand = trim($this->newProductBrand);
        $line = trim($this->newProductLine);

        if (!$catId || empty($brand)) {
            $this->errorMessage = 'Category and Brand are required.';
            return;
        }

        try {
            DB::table('products')->insert([
                'category_id' => $catId,
                'brand' => $brand,
                'line' => $line,
            ]);

            $this->newProductBrand = '';
            $this->newProductLine = '';
            $this->successMessage = 'Product added!';
            $this->loadDbData();
        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to add product: ' . $e->getMessage();
        }
    }

    public function getOrderSummaryProperty(): string
    {
        if (empty($this->orderItems)) {
            return '';
        }

        $grouped = [];
        foreach ($this->orderItems as $item) {
            $catName = $item->category_name ?? 'Uncategorized';
            $lineText = $item->line ? " ({$item->line})" : '';
            $prodKey = $item->brand . $lineText;

            if (!isset($grouped[$catName])) {
                $grouped[$catName] = [];
            }
            if (!isset($grouped[$catName][$prodKey])) {
                $grouped[$catName][$prodKey] = [];
            }
            $grouped[$catName][$prodKey][] = $item;
        }

        $text = "*VAPE ORDER*\n\n";
        foreach ($grouped as $catName => $products) {
            $text .= "*" . strtoupper($catName) . "*\n";
            foreach ($products as $prodKey => $items) {
                $text .= "_{$prodKey}_\n";
                foreach ($items as $item) {
                    $displayQty = $item->quantity ?? 1;
                    $text .= "- {$item->flavour} x {$displayQty}\n";
                }
                $text .= "\n";
            }
            $text .= "\n";
        }

        return trim($text);
    }

    public function render()
    {
        return view('livewire.vape-order-builder', [
            'brands' => $this->getBrandsForCategory(),
            'lines' => $this->getLinesForBrand(),
            'orderSummary' => $this->orderSummary,
        ])->layout('layouts.app');
    }
}
