@php
    $isLoggedIn = auth()->check() || session()->has('user_id');
    $username = auth()->check() ? auth()->user()->username : session('username', '');
@endphp

<header class="navbar navbar-expand navbar-light bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center shadow-sm">
    <a href="/" class="d-flex align-items-center gap-2 text-decoration-none">
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2px; width: 18px; height: 18px;">
            <div style="width: 8px; height: 8px; background-color: #f25022;"></div>
            <div style="width: 8px; height: 8px; background-color: #7fba00;"></div>
            <div style="width: 8px; height: 8px; background-color: #00a4ef;"></div>
            <div style="width: 8px; height: 8px; background-color: #ffb900;"></div>
        </div>
        <span class="fs-5 fw-bold text-dark mb-0" style="letter-spacing: -0.5px;">TechInbox</span>
        <span class="text-muted border-start ps-2 mb-0 d-none d-sm-inline" style="font-size: 14px;">Portal</span>
    </a>
    
    <div class="d-flex align-items-center gap-3">
        @if ($isLoggedIn)
            <div class="d-flex align-items-center gap-2">
                <span class="small text-muted d-none d-sm-inline">
                    Signed in as 
                    <a href="/profile" class="text-dark fw-semibold text-decoration-underline">{{ $username }}</a>
                </span>
                <a href="/profile" class="text-secondary d-inline-flex align-items-center p-1" title="Settings" style="transition: color 0.15s ease-in-out;" onmouseover="this.style.color='#00a4ef';" onmouseout="this.style.color='#5c5c5c';">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                </a>
                <form action="/logout" method="POST" class="m-0 d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1 border-0">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                        Sign Out
                    </button>
                </form>
            </div>
        @else
            <span class="small text-muted d-none d-sm-inline">Not signed in</span>
            <button 
                type="button" 
                class="btn btn-sm btn-primary px-3 rounded-1 fw-medium" 
                style="background-color: var(--brand-blue); border-color: var(--brand-blue);"
                x-data 
                x-on:click="$dispatch('open-login-modal')"
            >
                Sign In
            </button>
        @endif
    </div>
</header>
