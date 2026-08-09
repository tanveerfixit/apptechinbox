/* ==========================================================================
   Mileta Triple-Sided Acrylic Poster Maker - Logic
   ========================================================================== */

document.addEventListener('DOMContentLoaded', () => {
  // --- State Variables ---
  let activePanelIndex = 0; // 0: Panel A, 1: Panel B, 2: Panel C
  const panelsState = [
    { zoom: 100, fitMode: 'cover', posX: 0, posY: 0, imageLoaded: false, fileData: null },
    { zoom: 100, fitMode: 'cover', posX: 0, posY: 0, imageLoaded: false, fileData: null },
    { zoom: 100, fitMode: 'cover', posX: 0, posY: 0, imageLoaded: false, fileData: null }
  ];

  // Dragging state
  let isDragging = false;
  let startX = 0;
  let startY = 0;

  // Prism 3D rotation angle
  let currentYRotation = 30; // Matches initial CSS

  // --- Element Selectors ---
  const a4Sheet = document.getElementById('a4-sheet');
  const printBtn = document.getElementById('print-btn');
  const toggleGuides = document.getElementById('toggle-guides');
  const toggleMarks = document.getElementById('toggle-marks');
  const toggleBranding = document.getElementById('toggle-branding');
  const btnDemoImages = document.getElementById('btn-demo-images');
  const btnResetAll = document.getElementById('btn-reset-all');
  
  // 3D mockup elements
  const prism = document.getElementById('prism');
  const rotateLeftBtn = document.getElementById('rotate-left');
  const rotateRightBtn = document.getElementById('rotate-right');
  const toggleSpinBtn = document.getElementById('toggle-spin');
  
  // Fine-tuning tab UI
  const tabBtns = document.querySelectorAll('.tab-btn');
  const zoomSlider = document.getElementById('zoom-slider');
  const zoomValLabel = document.getElementById('zoom-val');
  const fitCoverBtn = document.getElementById('btn-fit-cover');
  const fitContainBtn = document.getElementById('btn-fit-contain');

  // --- SVGs for Mockups / Demo Posters ---
  // Beautiful luxury SVGs as inline URLs
  const createDemoSVG = (title, subtitle, colorTheme, accentColor, items) => {
    const listItemsHTML = items.map((item, i) => `
      <g transform="translate(0, ${180 + i * 55})">
        <text x="50" y="0" font-family="'Outfit', sans-serif" font-size="14" font-weight="600" fill="#ffffff">${item.name}</text>
        <text x="350" y="0" font-family="'Outfit', sans-serif" font-size="14" font-weight="600" fill="${accentColor}" text-anchor="end">${item.price}</text>
        <text x="50" y="20" font-family="'Outfit', sans-serif" font-size="10" fill="#94a3b8">${item.desc}</text>
        <line x1="50" y1="-12" x2="350" y2="-12" stroke="#ffffff" stroke-opacity="0.08" stroke-dasharray="2 2" />
      </g>
    `).join('');

    const svg = `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 850" width="100%" height="100%">
        <defs>
          <linearGradient id="grad-${title}" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" stop-color="${colorTheme[0]}" />
            <stop offset="100%" stop-color="${colorTheme[1]}" />
          </linearGradient>
        </defs>
        <rect width="100%" height="100%" fill="url(#grad-${title})" />
        
        <!-- Luxury pattern overlay -->
        <circle cx="200" cy="50" r="120" fill="none" stroke="${accentColor}" stroke-opacity="0.1" stroke-width="1"/>
        <circle cx="200" cy="50" r="160" fill="none" stroke="${accentColor}" stroke-opacity="0.05" stroke-width="1"/>
        
        <!-- Header -->
        <g transform="translate(200, 100)" text-anchor="middle">
          <text font-family="'Playfair Display', serif" font-size="11" font-weight="bold" fill="${accentColor}" letter-spacing="4">CLAY OVEN SELECTION</text>
          <text y="35" font-family="'Playfair Display', serif" font-size="28" font-style="italic" fill="#ffffff">${title}</text>
          <text y="58" font-family="'Outfit', sans-serif" font-size="9" fill="#94a3b8" letter-spacing="2">${subtitle}</text>
        </g>
        
        <!-- Menu list -->
        ${listItemsHTML}
        
        <!-- Footer Branding -->
        <g transform="translate(200, 800)" text-anchor="middle">
          <text font-family="'Playfair Display', serif" font-size="10" font-weight="bold" fill="${accentColor}" fill-opacity="0.6" letter-spacing="5">CLAY OVEN</text>
        </g>
      </svg>
    `;
    return 'data:image/svg+xml;utf8,' + encodeURIComponent(svg);
  };

  const sampleImages = [
    createDemoSVG("Wine Collection", "PREMIUM CELLAR SELECTION", ["#101626", "#070a10"], "#d4af37", [
      { name: "Château Latour 2015", price: "$140", desc: "Complex nose with notes of blackcurrant and cedarwood." },
      { name: "Domaine de la Romanée-Conti", price: "$320", desc: "Silky texture with deep notes of truffle and red fruits." },
      { name: "Opus One Napa Valley", price: "$195", desc: "Fresh aromas of bright red fruit, herbes de Provence." },
      { name: "Krug Clos d'Ambonnay", price: "$280", desc: "Exquisite sparkling Champagne with high acidity and mineral tones." }
    ]),
    createDemoSVG("Main Dinner", "GOURMET GASTRONOMY", ["#1e1418", "#0f070a"], "#c5a880", [
      { name: "Wagyu Ribeye Steak", price: "$85", desc: "Grade A5 Japanese beef served with truffle butter and asparagus." },
      { name: "Seared Atlantic Salmon", price: "$42", desc: "Wild-caught salmon with wild rice, lemon-herb reduction." },
      { name: "Truffle Tagliolini", price: "$38", desc: "Handmade pasta tossed in creamy butter with shaved black truffles." },
      { name: "Roasted Duck Breast", price: "$48", desc: "Spiced honey glaze, sweet potato puree, cherry reduction." }
    ]),
    createDemoSVG("Craft Cocktails", "ARTISAN MIXOLOGY", ["#0d1d1f", "#04090a"], "#4db6ac", [
      { name: "Smoked Rosemary Old Fashioned", price: "$18", desc: "Bourbon, angostura bitters, smoked rosemary sprig." },
      { name: "Cucumber Basil Gimlet", price: "$16", desc: "Artisanal Gin, fresh lime juice, basil leaves, cucumber ribbons." },
      { name: "Golden Hour Royale", price: "$22", desc: "Champagne, cognac, edible gold leaf, angostura drop." },
      { name: "Spiced Hibiscus Margarita", price: "$17", desc: "Reposado tequila, hibiscus reduction, spicy chili rim." }
    ])
  ];

  // --- Initial Setup ---
  initPanels();

  // --- Utility Functions ---

  function initPanels() {
    for (let i = 0; i < 3; i++) {
      const panelColumn = document.getElementById(`panel-${i}`);
      const fileInput = document.getElementById(`file-${i}`);

      // Click to trigger file input (if no image loaded) or make active panel
      panelColumn.addEventListener('click', (e) => {
        // Prevent trigger if clicking on an image container that is draggable
        if (panelsState[i].imageLoaded && e.target.closest('.image-wrapper')) {
          setActivePanel(i);
          return;
        }
        setActivePanel(i);
        if (!panelsState[i].imageLoaded) {
          fileInput.click();
        }
      });

      // Drag and drop events
      panelColumn.addEventListener('dragover', (e) => {
        e.preventDefault();
        panelColumn.classList.add('drag-over');
      });

      panelColumn.addEventListener('dragleave', () => {
        panelColumn.classList.remove('drag-over');
      });

      panelColumn.addEventListener('drop', (e) => {
        e.preventDefault();
        panelColumn.classList.remove('drag-over');
        if (e.dataTransfer.files.length > 0) {
          handleFile(e.dataTransfer.files[0], i);
        }
      });

      // File input change
      fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
          handleFile(e.target.files[0], i);
        }
      });

      // Mouse drag panning logic
      const wrapper = document.getElementById(`wrapper-${i}`);
      wrapper.addEventListener('mousedown', (e) => {
        if (!panelsState[i].imageLoaded) return;
        isDragging = true;
        setActivePanel(i);
        startX = e.clientX - panelsState[i].posX;
        startY = e.clientY - panelsState[i].posY;
        wrapper.style.cursor = 'grabbing';
      });
    }

    // Global drag move & end
    window.addEventListener('mousemove', (e) => {
      if (!isDragging) return;
      const state = panelsState[activePanelIndex];
      state.posX = e.clientX - startX;
      state.posY = e.clientY - startY;
      updateImageTransform(activePanelIndex);
    });

    window.addEventListener('mouseup', () => {
      if (!isDragging) return;
      isDragging = false;
      const currentWrapper = document.getElementById(`wrapper-${activePanelIndex}`);
      if (currentWrapper) currentWrapper.style.cursor = 'grab';
    });
  }

  function handleFile(file, index) {
    if (!file.type.startsWith('image/')) return;
    const reader = new FileReader();
    reader.onload = (e) => {
      loadImage(e.target.result, index);
    };
    reader.readAsDataURL(file);
  }

  function loadImage(dataUrl, index) {
    const img = document.getElementById(`img-${index}`);
    const placeholder = document.getElementById(`placeholder-${index}`);
    const prismImg = document.getElementById(`prism-img-${index}`);

    img.src = dataUrl;
    img.classList.add('loaded');
    placeholder.style.display = 'none';

    // Update state
    panelsState[index].imageLoaded = true;
    panelsState[index].fileData = dataUrl;
    panelsState[index].posX = 0;
    panelsState[index].posY = 0;

    // Apply fit mode styles
    applyFitMode(index);

    // Apply to 3D Acrylic mockup
    if (prismImg) {
      prismImg.src = dataUrl;
      prismImg.classList.add('loaded');
    }

    // Update selected active control tab settings
    if (index === activePanelIndex) {
      updateTuningUI();
    }
  }

  function setActivePanel(index) {
    activePanelIndex = index;
    // Update active visual panel styling
    document.querySelectorAll('.panel-column').forEach((el, idx) => {
      if (idx === index) {
        el.classList.add('active-panel');
      } else {
        el.classList.remove('active-panel');
      }
    });

    // Update UI tabs
    tabBtns.forEach((btn, idx) => {
      if (idx === index) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });

    updateTuningUI();
  }

  function updateTuningUI() {
    const state = panelsState[activePanelIndex];
    zoomSlider.value = state.zoom;
    zoomValLabel.textContent = `${state.zoom}%`;

    // Disable/enable controls depending on whether an image is loaded
    if (!state.imageLoaded) {
      zoomSlider.disabled = true;
      fitCoverBtn.disabled = true;
      fitContainBtn.disabled = true;
    } else {
      zoomSlider.disabled = false;
      fitCoverBtn.disabled = false;
      fitContainBtn.disabled = false;
    }

    if (state.fitMode === 'cover') {
      fitCoverBtn.classList.add('active');
      fitContainBtn.classList.remove('active');
    } else {
      fitCoverBtn.classList.remove('active');
      fitContainBtn.classList.add('active');
    }
  }

  function updateImageTransform(index) {
    const state = panelsState[index];
    const wrapper = document.getElementById(`wrapper-${index}`);
    const scale = state.zoom / 100;
    wrapper.style.transform = `translate(${state.posX}px, ${state.posY}px) scale(${scale})`;

    // Scale and translate the 3D preview image as well
    const prismWrapper = document.getElementById(`prism-wrapper-${index}`);
    if (prismWrapper) {
      const editorCol = document.getElementById(`panel-${index}`);
      const ratio = editorCol ? (120 / editorCol.clientWidth) : 0.43;
      const prismX = state.posX * ratio;
      const prismY = state.posY * ratio;
      prismWrapper.style.transform = `translate(${prismX}px, ${prismY}px) scale(${scale})`;
    }
  }

  function applyFitMode(index) {
    const state = panelsState[index];
    const img = document.getElementById(`img-${index}`);
    const prismImg = document.getElementById(`prism-img-${index}`);
    
    if (state.fitMode === 'cover') {
      img.style.width = '100%';
      img.style.height = '100%';
      img.style.objectFit = 'cover';
      if (prismImg) {
        prismImg.style.width = '100%';
        prismImg.style.height = '100%';
        prismImg.style.objectFit = 'cover';
      }
    } else {
      img.style.width = '100%';
      img.style.height = '100%';
      img.style.objectFit = 'contain';
      if (prismImg) {
        prismImg.style.width = '100%';
        prismImg.style.height = '100%';
        prismImg.style.objectFit = 'contain';
      }
    }
    updateImageTransform(index);
  }

  // --- Event Listeners for Controls ---

  // Handle active panel tab clicking
  tabBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      const idx = parseInt(btn.getAttribute('data-tab'));
      setActivePanel(idx);
    });
  });

  // Slider Zoom update
  zoomSlider.addEventListener('input', (e) => {
    const zoomVal = parseInt(e.target.value);
    panelsState[activePanelIndex].zoom = zoomVal;
    zoomValLabel.textContent = `${zoomVal}%`;
    updateImageTransform(activePanelIndex);
  });

  // Fit Cover
  fitCoverBtn.addEventListener('click', () => {
    panelsState[activePanelIndex].fitMode = 'cover';
    fitCoverBtn.classList.add('active');
    fitContainBtn.classList.remove('active');
    applyFitMode(activePanelIndex);
  });

  // Fit Contain
  fitContainBtn.addEventListener('click', () => {
    panelsState[activePanelIndex].fitMode = 'contain';
    fitCoverBtn.classList.remove('active');
    fitContainBtn.classList.add('active');
    applyFitMode(activePanelIndex);
  });

  // Toggles for Guides, Marks & Branding
  toggleGuides.addEventListener('change', (e) => {
    if (e.target.checked) {
      a4Sheet.classList.add('show-guides');
    } else {
      a4Sheet.classList.remove('show-guides');
    }
  });

  toggleMarks.addEventListener('change', (e) => {
    if (e.target.checked) {
      a4Sheet.classList.add('show-marks');
    } else {
      a4Sheet.classList.remove('show-marks');
    }
  });

  toggleBranding.addEventListener('change', (e) => {
    if (e.target.checked) {
      a4Sheet.classList.remove('hide-branding');
    } else {
      a4Sheet.classList.add('hide-branding');
    }
  });

  // Load sample posters
  btnDemoImages.addEventListener('click', () => {
    sampleImages.forEach((imgUrl, index) => {
      loadImage(imgUrl, index);
    });
  });

  // Reset workspace
  btnResetAll.addEventListener('click', () => {
    panelsState.forEach((state, i) => {
      state.zoom = 100;
      state.fitMode = 'cover';
      state.posX = 0;
      state.posY = 0;
      state.imageLoaded = false;
      state.fileData = null;

      const img = document.getElementById(`img-${i}`);
      img.src = '';
      img.classList.remove('loaded');

      const placeholder = document.getElementById(`placeholder-${i}`);
      placeholder.style.display = 'flex';

      const prismImg = document.getElementById(`prism-img-${i}`);
      if (prismImg) {
        prismImg.src = '';
        prismImg.classList.remove('loaded');
      }
      
      const wrapper = document.getElementById(`wrapper-${i}`);
      wrapper.style.transform = 'none';

      const prismWrapper = document.getElementById(`prism-wrapper-${i}`);
      if (prismWrapper) {
        prismWrapper.style.transform = 'none';
      }
    });
    setActivePanel(0);
  });

  // --- 3D Stand Interaction Controls ---
  
  rotateLeftBtn.addEventListener('click', () => {
    prism.classList.remove('spinning');
    toggleSpinBtn.classList.remove('btn-active');
    currentYRotation -= 120;
    prism.style.transform = `rotateX(-10deg) rotateY(${currentYRotation}deg)`;
  });

  rotateRightBtn.addEventListener('click', () => {
    prism.classList.remove('spinning');
    toggleSpinBtn.classList.remove('btn-active');
    currentYRotation += 120;
    prism.style.transform = `rotateX(-10deg) rotateY(${currentYRotation}deg)`;
  });

  toggleSpinBtn.addEventListener('click', () => {
    prism.classList.toggle('spinning');
    toggleSpinBtn.classList.toggle('btn-active');
  });

  // Auto-start spinning
  prism.classList.add('spinning');

  // --- Printing Trigger ---
  printBtn.addEventListener('click', () => {
    window.print();
  });
});
