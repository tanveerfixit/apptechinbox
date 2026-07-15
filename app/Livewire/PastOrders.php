<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class PastOrders extends Component
{
    public array $orders = [];
    public array $popularItems = [];
    public int $totalPendingItems = 0;
    public int $totalReceivedItems = 0;

    // Toast messages
    public string $successMessage = '';
    public string $errorMessage = '';

    public function mount()
    {
        $isLoggedIn = auth()->check() || session()->has('user_id');
        if (!$isLoggedIn) {
            return redirect()->to('/login');
        }

        $this->loadOrdersData();
    }

    public function loadOrdersData()
    {
        try {
            // Fetch past completed and received orders
            $dbOrders = DB::table('orders')
                ->whereIn('status', ['completed', 'received'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Fetch popular products / quick reorder lists
            $this->popularItems = DB::table('order_items as oi')
                ->join('orders as o', 'oi.order_id', '=', 'o.id')
                ->join('products as p', 'oi.product_id', '=', 'p.id')
                ->join('flavors as f', 'oi.flavor_id', '=', 'f.id')
                ->whereIn('o.status', ['completed', 'received'])
                ->select('p.id as product_id', 'p.brand', 'p.line', 'f.name as flavor', DB::raw('SUM(oi.quantity) as total_qty'))
                ->groupBy('p.id', 'p.brand', 'p.line', 'f.name')
                ->orderBy('total_qty', 'desc')
                ->limit(20)
                ->get()
                ->toArray();

            // Build orders map with item lists
            $ordersMap = [];
            foreach ($dbOrders as $order) {
                $ordersMap[$order->id] = [
                    'id' => $order->id,
                    'created_at' => $order->created_at,
                    'status' => $order->status,
                    'items' => []
                ];
            }

            if (!empty($ordersMap)) {
                $dbItems = DB::table('order_items as oi')
                    ->join('orders as o', 'oi.order_id', '=', 'o.id')
                    ->join('products as p', 'oi.product_id', '=', 'p.id')
                    ->join('flavors as f', 'oi.flavor_id', '=', 'f.id')
                    ->whereIn('o.status', ['completed', 'received'])
                    ->select('oi.id as item_id', 'oi.order_id', 'oi.quantity', 'oi.status as item_status', 'p.id as product_id', 'p.brand', 'p.line', 'f.name as flavor')
                    ->orderBy('o.created_at', 'desc')
                    ->get();

                $this->totalPendingItems = 0;
                $this->totalReceivedItems = 0;

                foreach ($dbItems as $item) {
                    if (isset($ordersMap[$item->order_id])) {
                        $ordersMap[$item->order_id]['items'][] = $item;
                        if ($item->item_status === 'pending') {
                            $this->totalPendingItems++;
                        } else {
                            $this->totalReceivedItems++;
                        }
                    }
                }
            }

            $this->orders = array_values($ordersMap);
        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to load order history database: ' . $e->getMessage();
        }
    }

    public function markItemAsReceived($itemId)
    {
        try {
            DB::table('order_items')->where('id', $itemId)->update(['status' => 'received']);
            
            // Check if all items in this order are received, and update order status
            $item = DB::table('order_items')->where('id', $itemId)->first();
            if ($item) {
                $pendingCount = DB::table('order_items')
                    ->where('order_id', $item->order_id)
                    ->where('status', 'pending')
                    ->count();

                if ($pendingCount === 0) {
                    DB::table('orders')->where('id', $item->order_id)->update(['status' => 'received']);
                }
            }

            $this->successMessage = 'Product marked as received!';
            $this->loadOrdersData();
        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to update product status.';
        }
    }

    public function render()
    {
        return view('livewire.past-orders')->layout('layouts.app');
    }
}
