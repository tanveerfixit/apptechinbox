<div 
    x-data="{ show: @entangle('isOpen') }"
    x-show="show"
    x-cloak
    class="modal fade show align-items-center justify-content-center"
    style="display: none; background: rgba(36, 36, 36, 0.45); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); z-index: 2050;"
    :class="{ 'd-flex': show, 'd-none': !show }"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    <div 
        class="card shadow-lg p-4 border-1 bg-white" 
        style="width: 100%; max-width: 380px; border-radius: 6px; border-color: var(--card-border);"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
    >
        <h2 class="h5 fw-semibold text-dark text-center mb-1" style="color: var(--text-primary) !important;">Sign in to Business Portal</h2>
        <p class="small text-muted text-center mb-4" style="color: var(--text-secondary) !important;">Sign in to access your inventory builder</p>

        @if ($loginError)
            <div class="alert alert-danger py-2 px-3 small text-center mb-3" style="font-size: 12.5px; border-radius: 4px;">
                {{ $loginError }}
            </div>
        @endif

        <form wire:submit.prevent="login">
            <div class="mb-3">
                <label class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px; color: var(--text-secondary) !important;">Business Name</label>
                <select class="form-select py-2" wire:model="business" required style="border-radius: 4px; font-size: 14px;">
                    <option value="" selected>Select business...</option>
                    @foreach ($businesses as $biz)
                        <option value="{{ $biz }}">{{ $biz }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="mb-3">
                <label class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px; color: var(--text-secondary) !important;">User Name</label>
                <select class="form-select py-2" wire:model="username" required style="border-radius: 4px; font-size: 14px;">
                    <option value="" selected>Select user...</option>
                    @foreach ($users as $u)
                        <option value="{{ $u }}">{{ $u }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="mb-4">
                <label class="d-block small fw-bold text-uppercase text-muted mb-1" style="font-size: 10px; letter-spacing: 0.5px; color: var(--text-secondary) !important;">Password</label>
                <input type="password" class="form-control py-2" wire:model="password" placeholder="Enter password..." required style="border-radius: 4px; font-size: 14px;">
            </div>
            
            <button type="submit" class="btn btn-primary w-100 py-2 text-uppercase fw-bold mb-2" style="font-size: 13px; letter-spacing: 0.5px; background-color: var(--brand-blue); border-color: var(--brand-blue); border-radius: 4px;">
                Sign In
            </button>
            <button type="button" class="btn btn-outline-secondary w-100 py-2" style="font-size: 13px; border-radius: 4px; color: var(--text-secondary);" wire:click="skip">
                Skip for Now
            </button>
        </form>
    </div>
</div>
