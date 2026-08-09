<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>The Royal Clay Oven - Tri-Poster Layout Maker</title>
  <link rel="stylesheet" href="style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,600;1,600&display=swap" rel="stylesheet">
</head>
<body class="flex flex-col min-h-screen bg-[#f3f3f3] text-[#242424] font-sans antialiased text-base">

  <?php require_once dirname(__DIR__) . '/header.php'; ?>

  <!-- App Container -->
  <div class="app-container">
    
    <!-- Top Premium Brand Header -->
    <header class="app-header">
      <div class="brand-logo">
        <span class="brand-title" style="font-family: var(--font-serif); font-size: 1.8rem; font-weight: 700; letter-spacing: 2px; color: var(--color-accent); line-height: 1.1;">CLAY OVEN</span>
        <span class="brand-tag">MENU POSTER MAKER</span>
      </div>
      <div class="app-title-container">
        <h1>The Royal Clay Oven Menu Maker</h1>
        <p>A4 Landscape 3-Section (1/3 A4 Panels: 99mm × 210mm)</p>
      </div>
      <div class="header-actions">
        <button id="print-btn" class="btn btn-primary">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
          Print Layout (A4)
        </button>
      </div>
    </header>

    <!-- Main Workspace Layout -->
    <main class="workspace">
      
      <!-- Left Side: Interactive Editor (A4 Landscape Layout) -->
      <section class="editor-section">
        <div class="section-header">
          <h2>1. Place & Align Your Images</h2>
          <p>Drag & drop images. Click and drag within panels to position, or use the controls below.</p>
        </div>

        <div class="a4-sheet-container">
          <!-- The simulated A4 Landscape Sheet -->
          <div id="a4-sheet" class="a4-sheet show-guides show-marks">
            
            <!-- Column 1 (Panel A) -->
            <div class="panel-column" id="panel-0" data-index="0">
              <div class="panel-viewport">
                <div class="image-wrapper" id="wrapper-0">
                  <img src="" alt="" class="uploaded-image" id="img-0">
                </div>
                <div class="upload-placeholder" id="placeholder-0">
                  <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>
                  <span>Upload Image 1</span>
                  <span class="subtext">Panel A (Left Face)</span>
                </div>
                <!-- Mini brand indicator -->
                <div class="panel-brand">CLAY OVEN</div>
              </div>
              <input type="file" id="file-0" accept="image/*" class="file-input">
            </div>

            <!-- Column 2 (Panel B) -->
            <div class="panel-column" id="panel-1" data-index="1">
              <div class="panel-viewport">
                <div class="image-wrapper" id="wrapper-1">
                  <img src="" alt="" class="uploaded-image" id="img-1">
                </div>
                <div class="upload-placeholder" id="placeholder-1">
                  <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>
                  <span>Upload Image 2</span>
                  <span class="subtext">Panel B (Middle Face)</span>
                </div>
                <div class="panel-brand">CLAY OVEN</div>
              </div>
              <input type="file" id="file-1" accept="image/*" class="file-input">
            </div>

            <!-- Column 3 (Panel C) -->
            <div class="panel-column" id="panel-2" data-index="2">
              <div class="panel-viewport">
                <div class="image-wrapper" id="wrapper-2">
                  <img src="" alt="" class="uploaded-image" id="img-2">
                </div>
                <div class="upload-placeholder" id="placeholder-2">
                  <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>
                  <span>Upload Image 3</span>
                  <span class="subtext">Panel C (Right Face)</span>
                </div>
                <div class="panel-brand">CLAY OVEN</div>
              </div>
              <input type="file" id="file-2" accept="image/*" class="file-input">
            </div>

          </div>
        </div>

        <!-- Controls Toolbar -->
        <div class="toolbar">
          <div class="toolbar-group">
            <h3>Layout Options</h3>
            <div class="toggle-container">
              <label class="switch">
                <input type="checkbox" id="toggle-guides" checked>
                <span class="slider"></span>
              </label>
              <span>Folding Guides</span>
            </div>
            <div class="toggle-container">
              <label class="switch">
                <input type="checkbox" id="toggle-marks" checked>
                <span class="slider"></span>
              </label>
              <span>Crop/Cut Marks</span>
            </div>
            <div class="toggle-container">
              <label class="switch">
                <input type="checkbox" id="toggle-branding" checked>
                <span class="slider"></span>
              </label>
              <span>Clay Oven Branding Footer</span>
            </div>
          </div>

          <div class="toolbar-group">
            <h3>Quick Actions</h3>
            <button class="btn btn-secondary" id="btn-demo-images">Load Sample Posters</button>
            <button class="btn btn-secondary" id="btn-reset-all">Reset Workspace</button>
          </div>
        </div>
      </section>

      <!-- Right Side: Live Settings & 3D Stand Preview Mockup -->
      <section class="sidebar-section">
        <div class="section-header">
          <h2>2. 3D Stand Mockup</h2>
          <p>Rotate the mockup to visualize the final triple-sided acrylic stand.</p>
        </div>

        <!-- 3D Stand Visualizer Container -->
        <div class="mockup-container">
          <div class="scene">
            <div class="prism" id="prism">
              <!-- Face 1 (Panel A) -->
              <div class="prism-face face-a">
                <div class="face-content" id="prism-content-0">
                  <div class="prism-img-wrapper" id="prism-wrapper-0">
                    <img src="" alt="" class="prism-image" id="prism-img-0">
                  </div>
                </div>
                <div class="face-acrylic-glare"></div>
              </div>
              <!-- Face 2 (Panel B) -->
              <div class="prism-face face-b">
                <div class="face-content" id="prism-content-1">
                  <div class="prism-img-wrapper" id="prism-wrapper-1">
                    <img src="" alt="" class="prism-image" id="prism-img-1">
                  </div>
                </div>
                <div class="face-acrylic-glare"></div>
              </div>
              <!-- Face 3 (Panel C) -->
              <div class="prism-face face-c">
                <div class="face-content" id="prism-content-2">
                  <div class="prism-img-wrapper" id="prism-wrapper-2">
                    <img src="" alt="" class="prism-image" id="prism-img-2">
                  </div>
                </div>
                <div class="face-acrylic-glare"></div>
              </div>
            </div>
          </div>
          
          <div class="mockup-controls">
            <button class="mockup-btn" id="rotate-left" title="Rotate Left">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
            </button>
            <button class="mockup-btn btn-active" id="toggle-spin" title="Auto Spin">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
            </button>
            <button class="mockup-btn" id="rotate-right" title="Rotate Right">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
            </button>
          </div>
        </div>

        <!-- Fine-Tuning Controls for Uploaded Images -->
        <div class="fine-tuning-panel">
          <h3>3. Fine-Tune Alignment</h3>
          <div class="tuning-tabs">
            <button class="tab-btn active" data-tab="0">Panel A</button>
            <button class="tab-btn" data-tab="1">Panel B</button>
            <button class="tab-btn" data-tab="2">Panel C</button>
          </div>
          
          <div class="tab-contents">
            <!-- Dynamic Controls updated by JS depending on selected tab -->
            <div class="tab-pane active" id="tab-pane">
              <div class="control-row">
                <label>Zoom Scale</label>
                <input type="range" id="zoom-slider" min="100" max="300" value="100">
                <span id="zoom-val">100%</span>
              </div>
              <div class="control-row">
                <label>Fit Options</label>
                <div class="btn-group">
                  <button class="btn btn-sm btn-outline active" id="btn-fit-cover">Cover</button>
                  <button class="btn btn-sm btn-outline" id="btn-fit-contain">Contain</button>
                </div>
              </div>
              <p class="hinttext">Pro tip: You can drag the image within the panel above to adjust position.</p>
            </div>
          </div>
        </div>

      </section>

    </main>
  </div>

  <script src="app.js"></script>
  <?php require_once dirname(__DIR__) . '/footer.php'; ?>
</body>
</html>
