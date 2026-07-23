<?php

namespace App\Livewire;

use App\Models\ProtectorStock;
use Livewire\Component;
use Livewire\WithPagination;

class InventoryManager extends Component
{
    use WithPagination;

    // Active tab state: 'add', 'search', 'reorder'
    public string $activeTab = 'search';

    // Search and filter properties
    public string $search = '';
    public string $selectedBrand = '';
    public string $selectedGlassType = '';

    // Form inputs for Quick Intake / Add / Restock
    public string $brand = 'Apple';
    public string $model = '';
    public string $glass_type = 'Aokus Cover Edge 9D';
    public int $stock_qty = 10;
    public int $min_threshold = 3;
    public string $bin_location = '';

    // Success toast notification message
    public string $toastMessage = '';

    protected $paginationTheme = 'tailwind';

    /**
     * Validation rules for adding/updating stock.
     */
    protected function rules(): array
    {
        return [
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'glass_type' => 'required|string|in:' . implode(',', ProtectorStock::GLASS_TYPES),
            'stock_qty' => 'required|integer|min:0',
            'min_threshold' => 'required|integer|min:0',
            'bin_location' => 'nullable|string|max:255',
        ];
    }

    /**
     * Reset pagination when search query or filters change.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedBrand(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedGlassType(): void
    {
        $this->resetPage();
    }

    /**
     * Quick stock creation or quantity upserting.
     */
    public function saveStock(): void
    {
        $this->validate();

        $stock = ProtectorStock::where('brand', trim($this->brand))
            ->where('model', trim($this->model))
            ->where('glass_type', trim($this->glass_type))
            ->first();

        if ($stock) {
            // Upsert: Add to existing stock quantity
            $stock->stock_qty += $this->stock_qty;
            if (!empty($this->bin_location)) {
                $stock->bin_location = trim($this->bin_location);
            }
            $stock->min_threshold = $this->min_threshold;
            $stock->save();

            $this->toastMessage = "Restocked {$this->stock_qty}x {$stock->brand} {$stock->model} ({$stock->glass_type})!";
        } else {
            // Create new inventory record
            $newStock = ProtectorStock::create([
                'brand' => trim($this->brand),
                'model' => trim($this->model),
                'glass_type' => trim($this->glass_type),
                'stock_qty' => $this->stock_qty,
                'min_threshold' => $this->min_threshold,
                'bin_location' => trim($this->bin_location),
            ]);

            $this->toastMessage = "Created {$newStock->brand} {$newStock->model} ({$newStock->glass_type}) with {$newStock->stock_qty} units!";
        }

        // Reset intake form model input
        $this->reset(['model', 'bin_location']);
        $this->stock_qty = 10;

        $this->dispatch('stock-saved');
    }

    /**
     * Inline quick quantity adjustments (-5, -1, +1, +5).
     */
    public function updateQty(int $id, int $change): void
    {
        $stock = ProtectorStock::find($id);

        if ($stock) {
            $newQty = max(0, $stock->stock_qty + $change);
            $stock->update(['stock_qty' => $newQty]);
        }
    }

    /**
     * Render the component with filtered datasets.
     */
    public function render()
    {
        // Query for Live Search Inventory
        $inventoryQuery = ProtectorStock::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('model', 'like', '%' . $this->search . '%')
                      ->orWhere('brand', 'like', '%' . $this->search . '%')
                      ->orWhere('bin_location', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->selectedBrand, function ($query) {
                $query->where('brand', $this->selectedBrand);
            })
            ->when($this->selectedGlassType, function ($query) {
                $query->where('glass_type', $this->selectedGlassType);
            })
            ->orderBy('brand')
            ->orderBy('model');

        // Query for Reorder procurement list (stock_qty <= min_threshold)
        $reorderItems = ProtectorStock::lowStock()
            ->orderBy('stock_qty', 'asc')
            ->orderBy('brand')
            ->orderBy('model')
            ->get();

        // Get unique brands for quick filter tabs/dropdowns
        $availableBrands = ProtectorStock::distinct()->pluck('brand')->filter()->values();

        return view('livewire.inventory-manager', [
            'stocks' => $inventoryQuery->paginate(12),
            'reorderItems' => $reorderItems,
            'availableBrands' => $availableBrands,
            'glassTypes' => ProtectorStock::GLASS_TYPES,
        ]);
    }
}
