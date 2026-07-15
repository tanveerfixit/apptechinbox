<?php

namespace App\Livewire;

use Livewire\Component;

class Dashboard extends Component
{
    public array $apps = [
        [
            'name' => 'Vap Order',
            'url' => 'vape.php',
            'desc' => 'Build vape orders, manage categories, brands, lines and track current order items.',
            'icon' => '💨',
            'color' => '#f25022', // Microsoft Red/Orange
            'badge' => 'Active'
        ],
        [
            'name' => 'Daily Closer',
            'url' => 'daily-closer.php',
            'desc' => 'Track daily end-of-day closings, registers, safe drops, and financial reports.',
            'icon' => '📊',
            'color' => '#7fba00', // Microsoft Green
            'badge' => 'Utility'
        ],
        [
            'name' => 'POS',
            'url' => '/pos',
            'desc' => 'Access point of sale terminal, process orders, checkout, and view live transactions.',
            'icon' => '💻',
            'color' => '#00a4ef', // Microsoft Blue
            'badge' => 'Terminal'
        ],
        [
            'name' => 'Screen Protector Finder',
            'url' => '/screen-protector-finder',
            'desc' => 'Search and locate screen protector inventory and device compatibility matching.',
            'icon' => '📱',
            'color' => '#ffb900', // Microsoft Yellow
            'badge' => 'Search'
        ],
        [
            'name' => 'Device Booking',
            'url' => 'booking.php',
            'desc' => 'Book repair devices, record faults, quote repairs, accept deposits, and print tickets.',
            'icon' => '📋',
            'color' => '#008272', // Teal
            'badge' => 'Repairs'
        ],
    ];

    /**
     * Listen for login success event to trigger dashboard re-render
     */
    protected $listeners = [
        'auth:success' => '$refresh',
        'auth:logout' => '$refresh'
    ];

    public function render()
    {
        $isLoggedIn = auth()->check() || session()->has('user_id');
        $skipLogin = session()->get('skip_login', false);

        return view('livewire.dashboard', [
            'isLoggedIn' => $isLoggedIn,
            'skipLogin' => $skipLogin,
        ])->layout('layouts.app');
    }
}
