<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LoginModal extends Component
{
    public bool $isOpen = false;
    public string $business = '';
    public string $username = '';
    public string $password = '';
    public string $loginError = '';

    public array $businesses = [];
    public array $users = [];

    protected $listeners = [
        'open-login-modal' => 'show',
        'close-login-modal' => 'hide'
    ];

    public function mount()
    {
        // Load businesses and users to populate dropdowns
        try {
            $this->businesses = DB::table('businesses')->orderBy('name')->pluck('name')->toArray();
            $this->users = DB::table('users')->orderBy('username')->pluck('username')->toArray();
        } catch (\Exception $e) {
            // Fallback for mock/local environments without actual DB
            $this->businesses = ['TechInbox Business A', 'TechInbox Business B'];
            $this->users = ['admin', 'staff'];
        }

        $isLoggedIn = auth()->check() || session()->has('user_id');
        $skipLogin = session()->get('skip_login', false);

        // Open modal by default if user is not authenticated and hasn't skipped
        if (!$isLoggedIn && !$skipLogin) {
            $this->isOpen = true;
        }
    }

    public function show()
    {
        $this->isOpen = true;
        $this->loginError = '';
    }

    public function hide()
    {
        $this->isOpen = false;
    }

    public function skip()
    {
        session()->put('skip_login', true);
        $this->isOpen = false;
        $this->dispatch('auth:success'); // Refresh parents
    }

    public function login()
    {
        $this->validate([
            'business' => 'required',
            'username' => 'required',
            'password' => 'required',
        ]);

        try {
            // Query user using Laravel DB query builder
            $user = DB::table('users')
                ->whereRaw('LOWER(username) = LOWER(?)', [$this->username])
                ->first();

            if ($user && Hash::check($this->password, $user->password)) {
                // Set session variables (matching original legacy logic)
                session()->put('user_id', $user->id);
                session()->put('username', $user->username);

                // Perform update on business details
                $bizDetails = DB::table('businesses')->where('name', $this->business)->first();
                if ($bizDetails) {
                    DB::table('users')->where('id', $user->id)->update([
                        'name' => $this->business,
                        'contact' => $bizDetails->contact ?? null,
                        'email' => $bizDetails->email ?? null,
                        'address' => $bizDetails->address ?? null,
                    ]);
                }

                // Remove skip login session
                session()->forget('skip_login');
                
                $this->isOpen = false;
                $this->dispatch('auth:success');
                return redirect()->to('/');
            } else {
                $this->loginError = 'Invalid username or password.';
            }
        } catch (\Exception $e) {
            $this->loginError = 'Authentication service error: ' . $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.login-modal');
    }
}
