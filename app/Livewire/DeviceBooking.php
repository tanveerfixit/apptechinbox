<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class DeviceBooking extends Component
{
    // Form variables
    public string $customerName = '';
    public string $phoneNumber = '';
    public string $deviceModel = '';
    public string $problemDescription = '';
    public float $totalQuote = 0.00;
    public float $depositPaid = 0.00;

    // Profile details
    public string $businessName = 'Store';
    public string $businessAddress = '';
    public string $businessContact = '';
    public string $businessEmail = '';
    public string $username = '';

    public function mount()
    {
        $userId = auth()->id() ?? session()->get('user_id');
        if (!$userId) {
            return redirect()->to('/login');
        }

        $this->username = auth()->user()->username ?? session()->get('username', 'Guest');

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
            // Ignore/suppress
        }
    }

    public function getBalanceDueProperty(): float
    {
        return max(0.00, $this->totalQuote - $this->depositPaid);
    }

    public function render()
    {
        return view('livewire.device-booking', [
            'balanceDue' => $this->balanceDue,
        ])->layout('layouts.app');
    }
}
