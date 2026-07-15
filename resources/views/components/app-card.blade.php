@props([
    'url',
    'color' => '#00a4ef',
    'icon' => '📦',
    'badge' => 'Utility',
    'name',
    'desc'
])

<div class="col-12 col-sm-6 col-md-4 d-flex">
    <a href="{{ $url }}" 
       class="card w-100 border-1 shadow-sm d-flex flex-column justify-content-between p-4 text-decoration-none text-dark position-relative overflow-hidden" 
       style="transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); border-radius: 6px;"
       onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 24px rgba(0,0,0,0.06)'; this.style.borderColor='{{ $color }}';"
       onmouseout="this.style.transform='none'; this.style.boxShadow='none'; this.style.borderColor='var(--card-border)';"
    >
        <!-- Top border colored accent line -->
        <div class="position-absolute top-0 start-0 w-100" style="height: 3px; background-color: {{ $color }};"></div>
        
        <div class="mt-2">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div style="width: 42px; height: 42px; background-color: #f8f9fa; font-size: 22px; display: flex; align-items: center; justify-content: center; border-radius: 4px; border: 1px solid #e9ecef;">
                    {{ $icon }}
                </div>
                <span class="badge bg-secondary-subtle text-secondary border-0 px-2 py-1 small text-uppercase fw-semibold" style="font-size: 10px; letter-spacing: 0.5px;">{{ $badge }}</span>
            </div>
            <div>
                <h3 class="h6 fw-bold text-dark mb-1">{{ $name }}</h3>
                <p class="text-muted mb-4" style="font-size: 12.5px; line-height: 1.4;">{{ $desc }}</p>
            </div>
        </div>
        <div class="text-primary fw-semibold small d-flex align-items-center gap-1 mt-auto" style="color: var(--brand-blue) !important;">
            <span>Open Application</span>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="transition: transform 0.2s ease;" class="arrow-icon"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
        </div>
    </a>
</div>
