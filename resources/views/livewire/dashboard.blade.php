<div class="container-fluid px-2 px-sm-3" style="max-width: 1200px; margin: 0 auto;">
    <!-- Header Hero Section -->
    <div class="text-center mx-auto mb-5 animate-fade-in" style="max-width: 600px;">
        <h1 class="h2 fw-semibold text-dark mb-2" style="letter-spacing: -0.5px;">Applications Dashboard</h1>
        <p class="small text-muted">Select an application below to get started with your TechInbox workspace utilities.</p>
    </div>

    <!-- Apps Grid Layout -->
    <div class="row g-4">
        @foreach ($apps as $app)
            <x-app-card 
                :url="$app['url']" 
                :color="$app['color']" 
                :icon="$app['icon']" 
                :badge="$app['badge']" 
                :name="$app['name']" 
                :desc="$app['desc']" 
            />
        @endforeach
    </div>

    <!-- Livewire Login Modal Popup -->
    <livewire:login-modal />
</div>
