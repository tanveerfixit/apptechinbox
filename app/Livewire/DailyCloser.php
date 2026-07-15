<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class DailyCloser extends Component
{
    // Form Inputs
    public float $cashSale = 0.00;
    public float $cardBoi = 0.00;
    public float $cardFixed = 0.00;

    // Messages
    public string $successMessage = '';
    public string $errorMessage = '';

    // Profile details
    public string $businessName = 'Store';
    public string $businessAddress = '';
    public string $businessContact = '';
    public string $businessEmail = '';
    public string $username = '';
    public string $currentDateStr = '';

    public function mount()
    {
        $userId = auth()->id() ?? session()->get('user_id');
        if (!$userId) {
            return redirect()->to('/login');
        }

        $this->username = auth()->user()->username ?? session()->get('username', '');
        $this->currentDateStr = now()->format('l j F Y');

        // Fetch user profile business info
        try {
            $profile = DB::table('users')
                ->where('id', $userId)
                ->select('name', 'contact', 'email', 'address')
                ->first();

            if ($profile) {
                $this->businessName = $profile->name ?: 'Store';
                $this->businessContact = $profile->contact ?: '';
                $this->businessEmail = $profile->email ?: '';
                $this->businessAddress = $profile->address ?: '';
            }
        } catch (\Exception $e) {
            // Suppress or log
        }

        // Fetch today's closure data
        try {
            $todayIso = now()->format('Y-m-d');
            $closure = DB::table('daily_closures')
                ->where('closure_date', $todayIso)
                ->first();

            if ($closure) {
                $this->cashSale = (float)$closure->cash_sale;
                $this->cardBoi = (float)$closure->card_boi;
                $this->cardFixed = (float)$closure->card_fixed;
            }
        } catch (\Exception $e) {
            // Ignore/suppress
        }
    }

    public function getTodayTotalProperty(): float
    {
        return $this->cashSale + $this->cardBoi + $this->cardFixed;
    }

    public function save()
    {
        $userId = auth()->id() ?? session()->get('user_id');
        if (!$userId) {
            $this->errorMessage = 'User session expired. Please sign in again.';
            return;
        }

        $todayIso = now()->format('Y-m-d');
        $totalInput = $this->todayTotal;

        try {
            DB::table('daily_closures')->updateOrInsert(
                ['closure_date' => $todayIso],
                [
                    'user_id' => $userId,
                    'business_name' => $this->businessName,
                    'cash_sale' => $this->cashSale,
                    'card_boi' => $this->cardBoi,
                    'card_fixed' => $this->cardFixed,
                    'total_sale' => $totalInput,
                ]
            );

            $this->successMessage = 'Daily closure saved successfully!';
            $this->errorMessage = '';
        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to save daily closure: ' . $e->getMessage();
            $this->successMessage = '';
        }
    }

    public function render()
    {
        return view('livewire.daily-closer', [
            'todayTotal' => $this->todayTotal,
        ])->layout('layouts.app');
    }
}
